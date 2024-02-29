<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { h } from 'vue';
import * as Matter from 'matter-js/build/matter';
import * as PIXI from 'pixi.js';
import { CollisionHandler } from '@/types/game/CollisionHandler';
import GameContainer, {
  BackgroundMovement,
  CollisionRegion,
} from '@/components/shared/atoms/game/GameContainer.vue';
import { ObjectSpace } from '@/types/enum/ObjectSpace';
import { delay, until } from '@/utils/wait';
import { GrayscaleFilter } from 'pixi-filters';
import { toDegrees, toRadians } from '@/utils/angle';
import * as matterUtil from '@/utils/matter';

export interface ConditionalVelocity {
  velocity: { x: number; y: number };
  condition: (object: GameObject) => boolean;
}

export enum FastObjectBehaviour {
  none = 'none',
  circle = 'circle',
  bounce = 'bounce',
}

export const bounceCategory = 1 << 31;

@Options({
  name: 'GameObject',
  components: {},
  emits: [
    'update:posX',
    'update:posY',
    'update:id',
    'update:angle',
    'destroyObject',
    'notifyCollision',
    'outsideDrawingSpace',
    'sizeChanged',
    'collision',
    'click',
    'release',
    'handleTrigger',
    'update:highlighted',
    'positionChanged',
    'initialised',
    'isPartOfChainChanged',
    'initError',
    'visibilityChanged',
  ],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class GameObject extends Vue {
  //#region props
  @Prop({ default: 100 }) renderDelay!: number;
  @Prop({ default: 0 }) id!: number;
  @Prop({ default: 0 }) posX!: number;
  @Prop({ default: 0 }) posY!: number;
  @Prop({ default: null }) fixSize!: [number, number] | number | null;
  @Prop({ default: 0 }) angle!: number;
  @Prop({ default: 1 }) scale!: number;
  @Prop({ default: ObjectSpace.Absolute }) objectSpace!: ObjectSpace;
  @Prop({ default: 'rect' }) readonly shape!: 'rect' | 'circle' | 'polygon';
  @Prop({ default: [] }) readonly polygonShape!: [number, number][];
  @Prop({ default: 0 }) readonly colliderDelta!: number;
  @Prop({ default: false }) readonly showBounds!: boolean;
  @Prop({ default: {} }) readonly options!: {
    [key: string]: string | number | boolean | object;
  };
  @Prop({ default: false }) readonly isStatic!: boolean;
  @Prop({ default: true }) readonly isActive!: boolean;
  @Prop({ default: true }) readonly clickable!: boolean;
  @Prop() readonly collisionHandler!: CollisionHandler;
  @Prop() readonly source!: any;
  @Prop({ default: true }) usePhysic!: boolean;
  @Prop({ default: true }) keepInside!: boolean;
  @Prop({ default: true }) affectedByForce!: boolean;
  @Prop({ default: true }) moveWithBackground!: boolean;
  @Prop({ default: null }) triggerDelay!: number | null;
  @Prop({ default: false }) triggerDelayPause!: boolean;
  @Prop({ default: false }) highlighted!: boolean;
  @Prop({ default: false }) disabled!: boolean;
  @Prop({ default: FastObjectBehaviour.none })
  fastObjectBehaviour!: FastObjectBehaviour;
  @Prop({ default: false }) sleepIfNotVisible!: boolean;
  @Prop({ default: 0 }) anchor!: number | [number, number];
  @Prop({ default: 0 }) zIndex!: number;
  @Prop({ default: null }) conditionalVelocity!: ConditionalVelocity | null;
  @Prop({ default: null }) mask!:
    | PIXI.Container<PIXI.DisplayObject>
    | PIXI.MaskData
    | null;
  @Prop({ default: 'gameObject' }) poolingKey!: string;
  //#endregion props

  //#region variables
  bodyId = -1;
  position: [number, number] = [0, 0];
  rotation = 0;
  gameObjectContainer: PIXI.Container | null = null;
  gameContainer!: GameContainer;
  offset: [number, number] = [0, 0];
  readonly defaultSize = 50;
  displayWidth = this.defaultSize;
  displayHeight = this.defaultSize;
  triggerStartTime: number | null = null;
  destroyed = false;
  filter: any[] | null = null;
  loadingFinished = false;
  isPartOfEngin = false;
  //#endregion variables

  //#region get / set
  get body(): Matter.Body | null {
    if (this.bodyId === -1) return null;
    if (this.gameContainer) {
      return this.gameContainer.getBodyForId(this.bodyId);
    }
    return null;
  }

  set body(value: Matter.Body | null) {
    if (value) this.bodyId = value.id;
    else this.bodyId = -1;
  }

  get displayX(): number {
    return this.position[0] - this.offset[0];
  }

  get displayY(): number {
    return this.position[1] - this.offset[1];
  }

  get possibleSpace(): [number, number] {
    if (
      this.objectSpace === ObjectSpace.RelativeToScreen &&
      this.gameContainer
    ) {
      return [
        this.gameContainer.gameWidth,
        this.gameContainer.gameDisplayHeight,
      ];
    } else if (
      this.objectSpace === ObjectSpace.RelativeToBackground &&
      this.gameContainer
    ) {
      return [
        this.gameContainer.backgroundTextureSize[0],
        this.gameContainer.backgroundTextureSize[1],
      ];
    }
    return [this.gameContainer.gameWidth, this.gameContainer.gameHeight];
  }

  getContainerWidth(): number {
    if (this.fixSize === null)
      return this.gameObjectContainer ? this.gameObjectContainer.width : 100;
    if (Array.isArray(this.fixSize)) return this.fixSize[0];
    return this.fixSize;
  }

  getContainerHeight(): number {
    if (this.fixSize === null)
      return this.gameObjectContainer ? this.gameObjectContainer.height : 100;
    if (Array.isArray(this.fixSize)) return this.fixSize[1];
    return this.fixSize;
  }

  get clickWidth(): number {
    //if (this.containerPosition) return this.containerPosition.width + this.colliderDelta * 2;
    //if (this.body) return this.body.bounds.max.x - this.body.bounds.min.x;
    return this.displayWidth + this.colliderDelta * 2;
  }

  get clickHeight(): number {
    //if (this.containerPosition) return this.containerPosition.height + this.colliderDelta * 2;
    //if (this.body) return this.body.bounds.max.y - this.body.bounds.min.y;
    return this.displayHeight + this.colliderDelta * 2;
  }

  isPositionVisible(x: number, y: number, delta = 0): boolean {
    const deltaX = this.displayWidth / 2;
    const deltaY = this.displayHeight / 2;
    if (this.gameContainer) {
      return (
        x >= -delta - deltaX &&
        x <= this.gameContainer.gameWidth + delta + deltaX &&
        y >= -delta - deltaY &&
        y <= this.gameContainer.gameDisplayHeight + delta + deltaY
      );
    }
    return false;
  }

  isVisible(delta = 0): boolean {
    if (!this.body) return false;
    const x = this.body.position.x;
    const y = this.body.position.y;
    let isVisible = this.isPositionVisible(x, y, delta);
    if (this.gameContainer.endlessPanning && !isVisible) {
      isVisible =
        isVisible ||
        this.isPositionVisible(
          x - this.gameContainer.backgroundTextureSize[0],
          y,
          delta
        );
      if (
        !isVisible &&
        this.gameContainer.backgroundMovement === BackgroundMovement.Pan
      ) {
        isVisible =
          isVisible ||
          this.isPositionVisible(
            x,
            y - this.gameContainer.backgroundTextureSize[1],
            delta
          );
        isVisible =
          isVisible ||
          this.isPositionVisible(
            x - this.gameContainer.backgroundTextureSize[0],
            y - this.gameContainer.backgroundTextureSize[1],
            delta
          );
      }
    }
    return isVisible;
  }

  getVelocityAmount(): number {
    return (
      Math.pow(this.body.velocity.x, 2) + Math.pow(this.body.velocity.y, 2)
    );
  }
  //#endregion get / set

  //#region load / unload
  render(): any {
    return h(
      'container',
      {
        mask: this.mask,
        x: this.position[0] - this.offset[0],
        y: this.position[1] - this.offset[1],
        rotation: this.rotation,
        scale: this.scale,
        filter: this.filter,
        onRender: this.onRender,
        onAdded: this.isAdded,
        source: this,
      },
      this.$slots
    );
  }

  isAdded(container: PIXI.Container): void {
    if (this.gameContainer) return;
    setTimeout(() => {
      let parent = container;
      while (parent.parent) {
        parent = parent.parent;
        if (Object.hasOwn(parent, 'gameContainer')) break;
      }
      const gameContainer = (parent as any).gameContainer as GameContainer;
      if (gameContainer) gameContainer.registerGameObject({ data: this });
    }, 100);
  }

  async mounted(): Promise<void> {
    //
  }

  unmounted(): void {
    this.kill();
    this.gameObjectReleased();
  }

  initOffset(offset: [number, number]): void {
    this.offset = offset;
  }

  updateOffset(
    offset: [number, number],
    offsetCircle: [number, number] | null = null
  ): void {
    const testOffset = (offsetX: number, offsetY: number): boolean => {
      const newPosition = {
        x: this.position[0] - offsetX,
        y: this.position[1] - offsetY,
      };
      if (this.isPositionVisible(newPosition.x, newPosition.y, 0)) {
        this.offset = [offsetX, offsetY];
        Matter.Body.setPosition(this.body, newPosition);
        return true;
      }
      return false;
    };

    this.offset = offset;
    if (this.body) {
      if (Array.isArray(offsetCircle)) {
        if (!testOffset(offset[0], offset[1])) {
          if (!testOffset(offsetCircle[0], offset[1])) {
            if (!testOffset(offset[0], offsetCircle[1])) {
              if (!testOffset(offsetCircle[0], offsetCircle[1])) {
                Matter.Body.setPosition(this.body, {
                  x: this.position[0] - this.offset[0],
                  y: this.position[1] - this.offset[1],
                });
              }
            }
          }
        }
      } else {
        Matter.Body.setPosition(this.body, {
          x: this.position[0] - this.offset[0],
          y: this.position[1] - this.offset[1],
        });
      }
      if (this.boundsGraphic) this.drawBorder();
    }
  }

  setGameContainer(gameContainer: GameContainer): void {
    this.gameContainer = gameContainer;
    this.initPosition();
    //this.manageEngin();
    this.addBodyToDetector();
  }

  kill(): void {
    this.destroyed = true;
    if (this.gameContainer) {
      this.gameContainer.deregisterGameObject(this);
      this.body = null;
    }
    setTimeout(() => {
      if (this.gameObjectContainer) {
        const parent = this.gameObjectContainer.parent;
        if (parent) {
          parent.removeChild(this.gameObjectContainer as any);
        }
        this.gameObjectContainer.destroy({ children: true });
      }
    }, 100);
  }
  //#endregion load / unload

  //#region interaction
  clickTime = 0;
  gameObjectClicked(): void {
    if (this.disabled) {
      return;
    }
    if (this.body && !this.isStatic && !this.usePhysic) {
      this.body.isStatic = false;
    }
    this.$emit('click', this);
    if (this.gameContainer) {
      this.clickTime = Date.now();
      this.gameContainer.$emit('update:selectedObject', this);
      this.gameContainer.activeObject = this;
      this.$emit('update:highlighted', true);
    }
  }

  async gameObjectReleased(): Promise<void> {
    if (this.body && !this.isStatic && !this.usePhysic) {
      this.body.isStatic = true;
    } else if (!this.isStatic && !this.usePhysic) {
      setTimeout(() => {
        if (this.body) this.body.isStatic = true;
      }, 100);
    }
    this.$emit('release', this);
    if (this.gameContainer && this.gameContainer.activeObject === this) {
      const clickTimeDelta = Date.now() - this.clickTime;
      const releaseDelay =
        this.gameContainer.minClickTimeDelta + 10 - clickTimeDelta;
      if (releaseDelay > 0) await delay(releaseDelay);
      this.gameContainer.activeObject = null;
      this.$emit('update:highlighted', false);
    }
  }
  //#endregion interaction

  //#region watch
  collisionCategory = 0b0001;
  collisionMask = 0xffffffff; //0b11111111111111111111111111111111
  hasDisabled = false;
  @Watch('disabled', { immediate: true })
  onDisabledChanged(): void {
    if (this.disabled) {
      this.collisionCategory = this.body.collisionFilter.category;
      this.collisionMask = this.body.collisionFilter.mask;
      this.body.collisionFilter.category = 0b0010;
      this.body.collisionFilter.mask = 0b11111111111111111111111111111110;
      this.filter = [new GrayscaleFilter()];
      this.hasDisabled = true;
    } else if (this.hasDisabled) {
      if (this.body) {
        this.body.collisionFilter.category = this.collisionCategory;
        this.body.collisionFilter.mask = this.collisionMask;
      }
      this.filter = null;
    }
  }

  @Watch('isActive', { immediate: true })
  onIsActiveChanged(): void {
    this.manageEngin();
    //this.isVisibleInContainer = this.isVisible();
  }
  //#endregion watch

  //#region init body
  async onRender(container: PIXI.Container): Promise<void> {
    if (this.gameObjectContainer) return;
    /*const setupBody = (): void => {
      if (!this.containerPosition) return;
      switch (this.type) {
        case 'rect':
          this.addRect(
            this.containerPosition.x,
            this.containerPosition.y,
            this.displayWidth,
            this.displayHeight
          );
          break;
        case 'circle':
          this.addCircle(
            this.containerPosition.x,
            this.containerPosition.y,
            this.displayWidth,
            this.displayHeight
          );
          break;
        case 'polygon':
          this.addPolygon(
            this.containerPosition.x,
            this.containerPosition.y,
            this.displayWidth,
            this.displayHeight,
            this.polygonShape
          );
          break;
      }
      this.$emit('initialised', this);
    };*/

    this.gameObjectContainer = container;
    //this.eventBus.emit(EventType.REGISTER_GAME_OBJECT, { data: this });
    const delayTime = this.fixSize === null ? 0 : this.renderDelay;
    await delay(delayTime);
    await until(() => !!this.gameContainer);
    try {
      const containerWidth = this.getContainerWidth();
      const containerHeight = this.getContainerHeight();
      if (
        this.body &&
        (this.displayWidth !== containerWidth ||
          this.displayHeight !== containerHeight)
      ) {
        const scaleX = containerWidth / this.displayWidth;
        const scaleY = containerHeight / this.displayHeight;
        Matter.Body.scale(this.body, scaleX, scaleY);
      }
      this.displayWidth = containerWidth;
      this.displayHeight = containerHeight;
      this.$emit('sizeChanged', [this.displayWidth, this.displayHeight]);
    } catch (e) {
      this.$emit('initError', this);
    }
  }

  assignPoolBody(body: Matter.Body): void {
    if (this.gameObjectContainer) {
      Matter.Body.setPosition(body, {
        x: this.gameObjectContainer.x,
        y: this.gameObjectContainer.y,
      });
    }
    this.body = body;
    this.$emit('update:id', this.bodyId);
    if (this.clickable) {
      this.manageEngin();
      this.addBodyToDetector();
    }
    this.$emit('initialised', this);
    this.loadingFinished = true;
  }

  updatedColliderSize(): void {
    const updateBody = (): void => {
      try {
        const containerWidth = this.getContainerWidth();
        if (
          this.body &&
          this.gameObjectContainer &&
          containerWidth !== this.displayWidth
        ) {
          const scale = containerWidth / this.displayWidth;
          Matter.Body.scale(this.body, scale, scale);
          this.displayWidth = containerWidth;
          this.displayHeight = this.getContainerHeight();
          this.$emit('sizeChanged', [this.displayWidth, this.displayHeight]);
          if (this.boundsGraphic) this.drawBorder();
        }
      } catch (e) {
        //
      }
    };

    if (this.body && this.gameObjectContainer) {
      updateBody();
    } else {
      setTimeout(() => {
        updateBody();
      }, 1000);
    }
  }

  async updatePivot(delta = 100, alwaysUpdate = false): Promise<void> {
    await matterUtil.updatePivot(this.body, this.anchor, delta, alwaysUpdate);
    this.loadingFinished = true;
  }

  @Watch('bodyId', { immediate: true })
  onBodyChanged(): void {
    if (this.bodyId > -1) {
      this.appliedScaleFactor = this.scale;
      this.updatePivot();
      this.onRotationChanged();
      this.onScaleChanged();
      this.$emit('update:id', this.bodyId);
      if (this.clickable) {
        this.manageEngin();
        this.addBodyToDetector();
      }
      this.$emit('initialised', this);
    }
  }
  //#endregion init body

  //#region engine
  manageEngin(): void {
    if (!this.clickable || !this.body) return;
    if (this.sleepIfNotVisible) {
      const isVisible = this.isVisible(this.displayWidth * 3);
      this.body.isStatic = !isVisible || this.isStatic;
      //Matter.Sleeping.set(this.body, !isVisible);
    }
  }

  addBodyToDetector(): void {
    if (this.gameContainer && this.gameContainer.detector && this.body) {
      this.gameContainer.addGameObjectToDetector(this);
    }
  }
  //#endregion engine

  //#region pooling
  isSleeping = false;
  sleepTime = Date.now();
  readyForReuse(): boolean {
    return this.isSleeping && Date.now() > this.sleepTime && !!this.body;
  }

  moveToPool(minSleepTime = 500): void {
    if (!this.body) return;
    this.sleepTime = Date.now() + minSleepTime;
    this.isSleeping = true;
    this.body.isStatic = true;
    matterUtil.resetBody(this.body, this.gameContainer.mouseConstraint);
    //Matter.Sleeping.set(this.body, true);
    Matter.Body.setPosition(this.body, { x: -10000, y: -10000 });
    this.position = [
      this.body.position.x + this.offset[0],
      this.body.position.y + this.offset[1],
    ];
  }

  activateFromPool(position: [number, number]): void {
    if (!this.body) return;
    this.isSleeping = false;
    Matter.Body.setPosition(this.body, { x: position[0], y: position[1] });
    //Matter.Sleeping.set(this.body, false);
    this.body.isStatic = this.isStatic;
  }
  //#endregion pooling

  //#region position / rotation / scale
  initPosition(x: number | null = null, y: number | null = null): void {
    if (x === null) x = this.posX;
    if (y === null) y = this.posY;
    if (
      this.objectSpace === ObjectSpace.RelativeToScreen &&
      this.gameContainer
    ) {
      this.position = [
        (x / 100) * this.gameContainer.gameWidth,
        (y / 100) * this.gameContainer.gameDisplayHeight,
      ];
    } else if (
      this.objectSpace === ObjectSpace.RelativeToBackground &&
      this.gameContainer
    ) {
      this.position = [
        (x / 100) * this.gameContainer.backgroundTextureSize[0],
        (y / 100) * this.gameContainer.backgroundTextureSize[1],
      ];
    } else {
      this.position = [x, y];
    }
    if (this.body) {
      Matter.Body.setPosition(this.body, {
        x: this.position[0] - this.offset[0],
        y: this.position[1] - this.offset[1],
      });
      if (this.boundsGraphic) this.drawBorder();
    }
  }

  convertPositionToInputFormat(): [number, number] {
    if (this.objectSpace === ObjectSpace.RelativeToScreen && this.gameContainer)
      return [
        (this.position[0] / this.gameContainer.gameWidth) * 100,
        (this.position[1] / this.gameContainer.gameDisplayHeight) * 100,
      ];
    if (
      this.objectSpace === ObjectSpace.RelativeToBackground &&
      this.gameContainer
    )
      return [
        (this.position[0] / this.gameContainer.backgroundTextureSize[0]) * 100,
        (this.position[1] / this.gameContainer.backgroundTextureSize[1]) * 100,
      ];
    return [this.position[0], this.position[1]];
  }

  @Watch('posX', { immediate: true })
  @Watch('posY', { immediate: true })
  onModelValueChanged(): void {
    const inputPosition = this.convertPositionToInputFormat();
    if (inputPosition[0] !== this.posX || inputPosition[1] !== this.posY) {
      this.initPosition();
    }
  }

  x = 0;
  y = 0;
  @Watch('position', { immediate: true, deep: true })
  @Watch('offset', { immediate: true, deep: true })
  onPositionChanged(): void {
    this.x = this.position[0] - this.offset[0];
    this.y = this.position[1] - this.offset[1];
  }

  updatePosition(position: [number, number]): void {
    const inputPosition = this.convertPositionToInputFormat();
    if (inputPosition[0] !== position[0] || inputPosition[1] !== position[1]) {
      this.initPosition(position[0], position[1]);
    }
  }

  @Watch('angle', { immediate: true })
  onRotationChanged(): void {
    if (!isNaN(this.angle)) {
      this.rotation = toRadians(360 - this.angle);
      if (this.body) {
        Matter.Body.setAngle(this.body, this.rotation);
        if (this.boundsGraphic) this.drawBorder();
      }
    }
  }

  appliedScaleFactor = 1;
  @Watch('scale', { immediate: true })
  onScaleChanged(): void {
    if (this.body && this.scale !== this.appliedScaleFactor) {
      const scale = (1 / this.appliedScaleFactor) * this.scale;
      Matter.Body.scale(this.body, scale, scale);
      this.appliedScaleFactor = this.scale;
      if (this.boundsGraphic) this.drawBorder();
    }
  }
  //#endregion position / rotation / scale

  //#region matter update
  wasVisible = false;
  wasAtBorder = false;
  beforePhysicUpdate(): void {
    if (
      !this.destroyed &&
      this.body &&
      !this.body.isStatic &&
      !this.body.isSleeping
    ) {
      let isVisible = this.isVisible(-this.displayWidth / 2);
      const isAtBorder = this.isVisible(this.displayWidth / 2) && !isVisible;
      if (
        this.fastObjectBehaviour === FastObjectBehaviour.bounce &&
        this.gameContainer.mouseConstraint?.body?.id !== this.bodyId
      ) {
        const combinedMask = this.body.collisionFilter.mask | bounceCategory;
        if (this.wasVisible || isVisible) {
          const velocityAmount = this.getVelocityAmount();
          if (velocityAmount > 10) {
            if (!isVisible && this.body.collisionFilter.mask !== combinedMask) {
              const delta = this.displayWidth / 2;
              const pos: [number, number] = [
                this.body.position.x,
                this.body.position.y,
              ];
              if (pos[0] < delta) pos[0] = delta;
              else if (pos[0] > this.gameContainer.gameWidth - delta)
                pos[0] = this.gameContainer.gameWidth - delta;
              if (pos[1] < delta) pos[1] = delta;
              else if (pos[1] > this.gameContainer.gameDisplayHeight - delta)
                pos[1] = this.gameContainer.gameDisplayHeight - delta;
              Matter.Body.setPosition(this.body, { x: pos[0], y: pos[1] });
              isVisible = true;
            }
            if (this.wasVisible || isVisible)
              this.body.collisionFilter.mask = combinedMask;
          } else this.body.collisionFilter.mask = combinedMask ^ bounceCategory;
        } else {
          this.body.collisionFilter.mask = combinedMask ^ bounceCategory;
          if (this.wasAtBorder) {
            const velocityAmount = this.getVelocityAmount();
            if (velocityAmount > 10)
              Matter.Body.setVelocity(this.body, { x: 0, y: 0 });
          }
        }
      }
      this.wasVisible = isVisible;
      this.wasAtBorder = isAtBorder;
    }
  }

  isVisibleInContainer = true;
  afterPhysicUpdate(): void {
    if (
      !this.destroyed &&
      this.body &&
      !this.body.isStatic &&
      !this.body.isSleeping
    ) {
      const hasPositionUpdate =
        this.body.position.x + this.offset[0] !== this.position[0] ||
        this.body.position.y + this.offset[1] !== this.position[1];
      if (hasPositionUpdate) {
        const isVisible = this.isVisible();
        if (this.isVisibleInContainer !== isVisible) {
          this.isVisibleInContainer = isVisible;
          this.$emit('visibilityChanged', isVisible);
        }
        if (this.gameContainer) {
          const possibleSpace = this.possibleSpace;
          const maxRight = possibleSpace[0];
          const minLeft = 0;
          const outsideRight = this.body.position.x + this.offset[0] > maxRight;
          const outsideLeft = this.body.position.x + this.offset[0] < minLeft;
          const outsideBottom =
            this.body.position.y + this.offset[1] > possibleSpace[1];
          const outsideTop = this.body.position.y + this.offset[1] < 0;
          if (outsideRight || outsideLeft || outsideBottom || outsideTop) {
            this.$emit('outsideDrawingSpace', this, {
              right: outsideRight,
              left: outsideLeft,
              bottom: outsideBottom,
              top: outsideTop,
            });
            if (
              this.keepInside &&
              !this.isStatic &&
              !this.isSleeping &&
              !this.body?.isStatic
            ) {
              const pos: [number, number] = [
                outsideLeft
                  ? -this.offset[0]
                  : outsideRight
                  ? possibleSpace[0] - this.offset[0]
                  : this.body.position.x,
                outsideTop
                  ? -this.offset[1]
                  : outsideBottom
                  ? possibleSpace[1] - this.offset[1]
                  : this.body.position.y,
              ];
              if (this.gameContainer.endlessPanning) {
                /*if (outsideLeft)
                  pos[0] = this.body.position.x + possibleSpace[0];
                else if (outsideRight)
                  pos[0] = this.body.position.x - possibleSpace[0];*/
                pos[0] = this.body.position.x;
              }
              Matter.Body.setPosition(this.body, { x: pos[0], y: pos[1] });
              Matter.Body.setVelocity(this.body, { x: 0, y: 0 });
              Matter.Body.setAngularVelocity(this.body, 0);
              Matter.Body.setSpeed(this.body, 0);
            }
          }
        }
        if (
          this.fastObjectBehaviour === FastObjectBehaviour.circle &&
          this.gameContainer.mouseConstraint?.body?.id !== this.bodyId
        ) {
          const velocityAmount = this.getVelocityAmount();
          if (velocityAmount > 10) {
            const delta = 10;
            const pos: [number, number] = [
              this.body.position.x,
              this.body.position.y,
            ];
            if (pos[0] < -delta) pos[0] = this.gameContainer.gameWidth;
            else if (pos[0] > this.gameContainer.gameWidth + delta) pos[0] = 0;
            if (pos[1] < -delta) pos[1] = this.gameContainer.gameDisplayHeight;
            else if (pos[1] > this.gameContainer.gameDisplayHeight + delta)
              pos[1] = 0;
            Matter.Body.setPosition(this.body, { x: pos[0], y: pos[1] });
          }
        }
        this.position = [
          this.body.position.x + this.offset[0],
          this.body.position.y + this.offset[1],
        ];
        this.rotation = this.body.angle;
        this.$emit('update:angle', 360 - toDegrees(this.rotation));
        const inputPosition = this.convertPositionToInputFormat();
        if (this.posX !== inputPosition[0] || this.posY !== inputPosition[1]) {
          this.$emit('positionChanged', inputPosition);
        }
        if (this.posX !== inputPosition[0]) {
          this.$emit('update:posX', inputPosition[0]);
        }
        if (this.posY !== inputPosition[1]) {
          this.$emit('update:posY', inputPosition[1]);
        }
      }
      if (
        this.conditionalVelocity &&
        this.gameContainer.mouseConstraint?.body?.id !== this.bodyId
      ) {
        const velocityAmount = this.getVelocityAmount();
        if (velocityAmount < 5) {
          if (this.conditionalVelocity.condition(this)) {
            Matter.Body.setVelocity(
              this.body,
              this.conditionalVelocity.velocity
            );
          }
        }
      }
    }
    if (this.boundsGraphic) this.drawBorder();
    if (this.sleepIfNotVisible) this.manageEngin();

    if (this.gameContainer.endlessPanning) {
      const maxX = this.gameContainer.backgroundTextureSize[0];
      const maxY = this.gameContainer.backgroundTextureSize[1];
      if (this.position[0] < 0) this.position[0] = maxX + this.position[0];
      if (this.position[0] > maxX) this.position[0] = this.position[0] - maxX;
      if (this.gameContainer.backgroundMovement === BackgroundMovement.Pan) {
        if (this.position[1] < 0) this.position[1] = maxY + this.position[1];
        if (this.position[1] > maxY) this.position[1] = this.position[1] - maxY;
      }
    }
  }
  //#endregion matter update

  //#region collision
  notifyDestroy(): void {
    this.destroyed = true;
    this.$emit('destroyObject', this);
    //this.kill();
  }

  notifyCollision(): void {
    this.$emit('notifyCollision', this);
  }

  handleCollision(
    collisionObject: GameObject | CollisionRegion | null,
    hitPoint: [number, number],
    hitPointScreen: [number, number],
    objectBody: Matter.Body,
    collisionBody: Matter.Body
  ): boolean {
    let deleteFlag = false;
    if (this.collisionHandler) {
      deleteFlag = this.collisionHandler.handleCollision(
        this,
        collisionObject,
        hitPoint,
        hitPointScreen,
        objectBody,
        collisionBody
      );
    }

    this.$emit(
      'collision',
      this,
      collisionObject,
      objectBody,
      collisionBody,
      hitPoint,
      hitPointScreen
    );
    return deleteFlag;
  }
  //#endregion collision

  //#region draw bounding box
  boundsWidth: number | null = null;
  boundsHeight: number | null = null;
  boundsGraphic: PIXI.Graphics | null = null;
  drawBorder(inputGraphics: PIXI.Graphics | null = null): void {
    const graphics = inputGraphics ?? this.boundsGraphic;
    if (inputGraphics) this.boundsGraphic = inputGraphics;
    if (graphics && graphics.geometry && this.body) {
      graphics.clear();
      graphics.lineStyle(2, '#ff0000');
      const path = this.body.vertices.map((item) => {
        return {
          x: item.x - this.body.position.x,
          y: item.y - this.body.position.y,
        };
      });
      graphics.drawPolygon(path);
      this.boundsWidth = graphics.width;
      this.boundsHeight = graphics.height;
    }
  }
  //#endregion draw bounding box

  //#region trigger
  @Watch('triggerDelay', { immediate: true })
  startTriggerListener(): void {
    if (this.triggerDelay) this.triggerStartTime = Date.now();
    else this.triggerStartTime = null;
  }

  triggerPauseStartTime: null | number = null;
  @Watch('triggerDelayPause', { immediate: true })
  pauseTriggerDelay(): void {
    if (this.triggerDelayPause) {
      this.triggerPauseStartTime = Date.now();
    } else {
      if (this.triggerStartTime && this.triggerPauseStartTime) {
        const delta = Date.now() - this.triggerPauseStartTime;
        this.triggerStartTime += delta;
      }
      this.triggerPauseStartTime = null;
    }
  }

  checkTrigger(): boolean {
    if (
      !this.triggerDelayPause &&
      !this.triggerPauseStartTime &&
      this.triggerDelay &&
      this.triggerStartTime &&
      this.triggerStartTime + this.triggerDelay * 1000 < Date.now()
    ) {
      this.startTriggerListener();
      this.$emit('handleTrigger', this);
      return true;
    }
    return false;
  }
  //#endregion trigger
}
</script>
