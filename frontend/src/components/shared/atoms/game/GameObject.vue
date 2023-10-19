<template>
  <container
    :mask="mask"
    @render="containerLoad"
    :x="position[0] - offset[0]"
    :y="position[1] - offset[1]"
    :rotation="rotationValue"
    :scale="scale"
    :filters="objectFilters"
  >
    <slot></slot>
  </container>
  <container
    :mask="mask"
    :x="position[0] - offset[0]"
    :y="position[1] - offset[1]"
    :rotation="rotationValue"
    :scale="scale"
    :filters="objectFilters"
  >
    <slot name="background"></slot>
  </container>
  <Graphics
    :mask="mask"
    v-if="body && showBounds && loadingFinished"
    @render="drawBorder"
    :x="body.position.x"
    :y="body.position.y"
    :width="boundsWidth ?? clickWidth"
    :height="boundsHeight ?? clickHeight"
  ></Graphics>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as Matter from 'matter-js/build/matter';
import * as PIXI from 'pixi.js';
import { EventType } from '@/types/enum/EventType';
import { CollisionHandler } from '@/types/game/CollisionHandler';
import GameContainer, {
  CollisionRegion,
} from '@/components/shared/atoms/game/GameContainer.vue';
import { ObjectSpace } from '@/types/enum/ObjectSpace';
import { delay } from '@/utils/wait';
import { GrayscaleFilter } from 'pixi-filters';
import { toRadians, toDegrees } from '@/utils/angle';
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
  components: {},
  emits: [
    'update:x',
    'update:y',
    'update:id',
    'update:rotation',
    'destroyObject',
    'outsideDrawingSpace',
    'sizeChanged',
    'collision',
    'click',
    'handleTrigger',
    'update:highlighted',
    'positionChanged',
    'initialised',
  ],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class GameObject extends Vue {
  @Prop({ default: 100 }) renderDelay!: number;
  @Prop({ default: 0 }) id!: number;
  @Prop({ default: 0 }) x!: number;
  @Prop({ default: 0 }) y!: number;
  @Prop({ default: null }) fixSize!: [number, number] | number | null;
  @Prop({ default: 0 }) rotation!: number;
  @Prop({ default: 1 }) scale!: number;
  @Prop({ default: ObjectSpace.Absolute }) objectSpace!: ObjectSpace;
  @Prop({ default: 'rect' }) readonly type!: 'rect' | 'circle' | 'polygon';
  @Prop({ default: [] }) readonly polygonShape!: [number, number][];
  @Prop({ default: 0 }) readonly colliderDelta!: number;
  @Prop({ default: false }) readonly showBounds!: boolean;
  @Prop({ default: {} }) readonly options!: {
    [key: string]: string | number | boolean | object;
  };
  @Prop({ default: false }) readonly isStatic!: boolean;
  @Prop({ default: true }) readonly clickable!: boolean;
  @Prop() readonly collisionHandler!: CollisionHandler;
  @Prop() readonly source!: any;
  @Prop({ default: true }) usePhysic!: boolean;
  @Prop({ default: true }) affectedByForce!: boolean;
  @Prop({ default: true }) moveWithBackground!: boolean;
  @Prop({ default: null }) triggerDelay!: number | null;
  @Prop({ default: false }) highlighted!: boolean;
  @Prop({ default: false }) disabled!: boolean;
  @Prop({ default: FastObjectBehaviour.none })
  fastObjectBehaviour!: FastObjectBehaviour;
  @Prop({ default: false }) removeFromEnginIfNotVisible!: boolean;
  @Prop({ default: 0 }) anchor!: number | [number, number];
  @Prop({ default: 0 }) zIndex!: number;
  @Prop({ default: null }) conditionalVelocity!: ConditionalVelocity | null;
  @Prop({ default: null }) mask!:
    | PIXI.Container<PIXI.DisplayObject>
    | PIXI.MaskData
    | null;
  body!: typeof Matter.Body;
  position: [number, number] = [0, 0];
  rotationValue = 0;
  container!: PIXI.Container;
  gameContainer!: GameContainer;
  offset: [number, number] = [0, 0];
  readonly defaultSize = 50;
  displayWidth = this.defaultSize;
  displayHeight = this.defaultSize;
  triggerStartTime: number | null = null;
  destroyed = false;
  objectFilters: any[] = [];
  loadingFinished = false;
  isPartOfEngin = false;

  get displayX(): number {
    return this.position[0] - this.offset[0];
  }

  get displayY(): number {
    return this.position[1] - this.offset[1];
  }

  getContainerWidth(): number {
    if (this.fixSize === null) return this.container.width;
    if (Array.isArray(this.fixSize)) return this.fixSize[0];
    return this.fixSize;
  }

  getContainerHeight(): number {
    if (this.fixSize === null) return this.container.height;
    if (Array.isArray(this.fixSize)) return this.fixSize[1];
    return this.fixSize;
  }

  async mounted(): Promise<void> {
    const container = document.getElementById('gameContainer');
    if (container) {
      const registerGameObject = new CustomEvent(
        EventType.REGISTER_GAME_OBJECT,
        {
          detail: {
            data: this,
          },
        }
      );
      container.dispatchEvent(registerGameObject);
    }
  }

  unmounted(): void {
    this.kill();
    this.gameObjectReleased();
  }

  initOffset(offset: [number, number]): void {
    this.offset = offset;
  }

  updateOffset(offset: [number, number]): void {
    this.offset = offset;
    if (this.body) {
      Matter.Body.setPosition(this.body, {
        x: this.position[0] - this.offset[0],
        y: this.position[1] - this.offset[1],
      });
      if (this.boundsGraphic) this.drawBorder();
    }
  }

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
    if (this.gameContainer && this.gameContainer.activeObject === this) {
      const clickTimeDelta = Date.now() - this.clickTime;
      const releaseDelay =
        this.gameContainer.minClickTimeDelta + 10 - clickTimeDelta;
      if (releaseDelay > 0) await delay(releaseDelay);
      this.gameContainer.activeObject = null;
      this.$emit('update:highlighted', false);
    }
  }

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
      this.objectFilters = [new GrayscaleFilter()];
      this.hasDisabled = true;
    } else if (this.hasDisabled) {
      if (this.body) {
        this.body.collisionFilter.category = this.collisionCategory;
        this.body.collisionFilter.mask = this.collisionMask;
      }
      this.objectFilters = [];
    }
  }

  containerLoad(container: PIXI.Container): void {
    const setupBody = (): void => {
      this.displayWidth = this.getContainerWidth();
      this.displayHeight = this.getContainerHeight();
      this.$emit('sizeChanged', [this.displayWidth, this.displayHeight]);
      switch (this.type) {
        case 'rect':
          this.addRect(
            this.container.x,
            this.container.y,
            this.displayWidth,
            this.displayHeight
          );
          break;
        case 'circle':
          this.addCircle(
            this.container.x,
            this.container.y,
            this.displayWidth,
            this.displayHeight
          );
          break;
        case 'polygon':
          this.addPolygon(
            this.container.x,
            this.container.y,
            this.displayWidth,
            this.displayHeight,
            this.polygonShape
          );
          break;
      }
      this.$emit('initialised', this);
    };

    this.container = container;
    const delay = this.fixSize === null ? 0 : this.renderDelay;
    setTimeout(() => {
      try {
        setupBody();
      } catch (e) {
        this.$emit('initError', this);
      }
    }, delay);
  }

  updatedColliderSize(): void {
    const updateBody = (): void => {
      try {
        const containerWidth = this.getContainerWidth();
        if (
          this.body &&
          this.container &&
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

    if (this.body && this.container) {
      updateBody();
    } else {
      setTimeout(() => {
        updateBody();
      }, 1000);
    }
  }

  updatePivot(delta = 100): void {
    if (this.anchor) {
      const width = this.displayWidth;
      const height = this.displayHeight;
      const deltaX = width * this.anchor[0] - width / 2;
      const deltaY = height * this.anchor[1] - height / 2;
      setTimeout(() => {
        const position = [this.body.position.x, this.body.position.y];
        Matter.Body.setCentre(this.body, { x: deltaX, y: deltaY }, true);
        Matter.Body.setPosition(this.body, { x: position[0], y: position[1] });
        this.loadingFinished = true;
      }, delta);
    } else {
      this.loadingFinished = true;
    }
  }

  addRect(x: number, y: number, width: number, height: number): void {
    this.options.isStatic = this.isStatic;
    const colliderWidth = width + this.colliderDelta * 2;
    const colliderHeight = height + this.colliderDelta * 2;
    this.body = Matter.Bodies.rectangle(
      x,
      y,
      colliderWidth,
      colliderHeight,
      this.options
    );
    (this.body as any).zIndex = this.zIndex;
    this.updatePivot();
    this.onRotationChanged();
    this.onScaleChanged();
    this.$emit('update:id', this.body.id);
    if (this.clickable) {
      this.manageEngin();
      this.addBodyToDetector();
    }
  }

  addCircle(x: number, y: number, width: number, height: number): void {
    this.options.isStatic = this.isStatic;
    const radius =
      (width > height ? width / 2 : height / 2) + this.colliderDelta;
    this.body = Matter.Bodies.circle(x, y, radius, this.options);
    (this.body as any).zIndex = this.zIndex;
    this.updatePivot();
    this.onRotationChanged();
    this.onScaleChanged();
    this.$emit('update:id', this.body.id);
    if (this.clickable) {
      this.manageEngin();
      this.addBodyToDetector();
    }
  }

  addPolygon(
    x: number,
    y: number,
    width: number,
    height: number,
    shape: [number, number][]
  ): void {
    this.options.isStatic = this.isStatic;
    this.body = matterUtil.createPolygonBody(
      this.options,
      x,
      y,
      width,
      height,
      shape
    );
    (this.body as any).zIndex = this.zIndex;
    this.updatePivot();
    this.onRotationChanged();
    this.onScaleChanged();
    this.$emit('update:id', this.body.id);
    if (this.clickable) {
      this.manageEngin();
      this.addBodyToDetector();
    }
  }

  addBodyToEngine(): void {
    if (this.gameContainer) {
      this.gameContainer.addGameObjectToEngin(this);
    }
  }

  manageEngin(): void {
    if (!this.clickable) return;
    if (this.removeFromEnginIfNotVisible) {
      const isVisible = this.isVisible(this.displayWidth * 5);
      if (isVisible && !this.isPartOfEngin) {
        this.addBodyToEngine();
      } else if (!isVisible && this.isPartOfEngin) {
        if (this.gameContainer) {
          this.gameContainer.removeGameObjectFromEngin(this);
        }
      }
    } else if (!this.isPartOfEngin) {
      this.addBodyToEngine();
    }
  }

  addBodyToDetector(): void {
    if (this.gameContainer && this.gameContainer.detector && this.body) {
      this.gameContainer.detector.bodies.push(this.body);
    }
  }

  initPosition(x: number | null = null, y: number | null = null): void {
    if (x === null) x = this.x;
    if (y === null) y = this.y;
    if (
      this.objectSpace === ObjectSpace.RelativeToScreen &&
      this.gameContainer
    ) {
      this.position = [
        (x / 100) * this.gameContainer.gameWidth,
        (y / 100) * this.gameContainer.gameHeight,
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
        (this.position[1] / this.gameContainer.gameHeight) * 100,
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

  @Watch('x', { immediate: true })
  @Watch('y', { immediate: true })
  onModelValueChanged(): void {
    const inputPosition = this.convertPositionToInputFormat();
    if (inputPosition[0] !== this.x || inputPosition[1] !== this.y) {
      this.initPosition();
    }
  }

  updatePosition(position: [number, number]): void {
    const inputPosition = this.convertPositionToInputFormat();
    if (inputPosition[0] !== position[0] || inputPosition[1] !== position[1]) {
      this.initPosition(position[0], position[1]);
    }
  }

  @Watch('rotation', { immediate: true })
  onRotationChanged(): void {
    if (!isNaN(this.rotation)) {
      this.rotationValue = toRadians(360 - this.rotation);
      if (this.body) {
        Matter.Body.setAngle(this.body, this.rotationValue);
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

  setGameContainer(gameContainer: GameContainer): void {
    this.gameContainer = gameContainer;
    this.initPosition();
    this.manageEngin();
    this.addBodyToDetector();
  }

  wasVisible = false;
  beforePhysicUpdate(): void {
    if (!this.destroyed && !this.isStatic && this.body) {
      let isVisible = this.isVisible(-this.displayWidth / 2);
      if (
        this.fastObjectBehaviour === FastObjectBehaviour.bounce &&
        this.gameContainer.mouseConstraint.body?.id !== this.body.id
      ) {
        const combinedMask = this.body.collisionFilter.mask | bounceCategory;
        if (this.wasVisible) {
          const velocityAmount =
            Math.pow(this.body.velocity.x, 2) +
            Math.pow(this.body.velocity.y, 2);
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
              else if (pos[1] > this.gameContainer.gameHeight - delta)
                pos[1] = this.gameContainer.gameHeight - delta;
              Matter.Body.setPosition(this.body, { x: pos[0], y: pos[1] });
              isVisible = true;
            }
            if (this.wasVisible) this.body.collisionFilter.mask = combinedMask;
          } else this.body.collisionFilter.mask = combinedMask ^ bounceCategory;
        } else this.body.collisionFilter.mask = combinedMask ^ bounceCategory;
      }
      this.wasVisible = isVisible;
    }
  }

  afterPhysicUpdate(): void {
    if (
      !this.destroyed &&
      !this.isStatic &&
      this.body &&
      (this.body.position.x + this.offset[0] !== this.position[0] ||
        this.body.position.y + this.offset[1] !== this.position[1])
    ) {
      if (this.gameContainer) {
        /*const outsideRight =
          this.body.position.x + this.offset[0] > this.gameContainer.gameWidth;
        const outsideLeft = this.body.position.x + this.offset[0] < 0;
        const outsideBottom =
          this.body.position.y + this.offset[1] > this.gameContainer.gameHeight;
        const outsideTop = this.body.position.y + this.offset[1] < 0;*/
        const outsideRight =
          this.body.position.x > this.gameContainer.gameWidth;
        const outsideLeft = this.body.position.x < 0;
        const outsideBottom =
          this.body.position.y > this.gameContainer.gameHeight;
        const outsideTop = this.body.position.y < 0;
        if (outsideRight || outsideLeft || outsideBottom || outsideTop) {
          this.$emit('outsideDrawingSpace', this, {
            right: outsideRight,
            left: outsideLeft,
            bottom: outsideBottom,
            top: outsideTop,
          });
        }
      }
      if (
        this.fastObjectBehaviour === FastObjectBehaviour.circle &&
        this.gameContainer.mouseConstraint.body?.id !== this.body.id
      ) {
        const velocityAmount =
          Math.pow(this.body.velocity.x, 2) + Math.pow(this.body.velocity.y, 2);
        if (velocityAmount > 10) {
          const delta = 10;
          const pos: [number, number] = [
            this.body.position.x,
            this.body.position.y,
          ];
          if (pos[0] < -delta) pos[0] = this.gameContainer.gameWidth;
          else if (pos[0] > this.gameContainer.gameWidth + delta) pos[0] = 0;
          if (pos[1] < -delta) pos[1] = this.gameContainer.gameHeight;
          else if (pos[1] > this.gameContainer.gameHeight + delta) pos[1] = 0;
          Matter.Body.setPosition(this.body, { x: pos[0], y: pos[1] });
        }
      }
      if (
        this.conditionalVelocity &&
        this.gameContainer.mouseConstraint.body?.id !== this.body.id
      ) {
        const velocityAmount =
          Math.pow(this.body.velocity.x, 2) + Math.pow(this.body.velocity.y, 2);
        if (velocityAmount < 5) {
          if (this.conditionalVelocity.condition(this)) {
            Matter.Body.setVelocity(
              this.body,
              this.conditionalVelocity.velocity
            );
          }
        }
      }
      this.position = [
        this.body.position.x + this.offset[0],
        this.body.position.y + this.offset[1],
      ];
      this.rotationValue = this.body.angle;
      this.$emit('update:rotation', 360 - toDegrees(this.rotationValue));
      const inputPosition = this.convertPositionToInputFormat();
      if (this.x !== inputPosition[0] || this.y !== inputPosition[1]) {
        this.$emit('positionChanged', inputPosition);
      }
      this.$emit('update:x', inputPosition[0]);
      this.$emit('update:y', inputPosition[1]);
    }
    if (this.boundsGraphic) this.drawBorder();
    if (this.removeFromEnginIfNotVisible) this.manageEngin();
  }

  notifyDestroy(): void {
    this.destroyed = true;
    this.$emit('destroyObject', this);
    //this.kill();
  }

  kill(): void {
    this.destroyed = true;
    if (this.gameContainer) {
      this.gameContainer.deregisterGameObject(this);
      const body = this.body;
      this.body = null;
      try {
        Matter.Composite.remove(this.gameContainer.engine.world, body);
      } catch (e) {
        //
      }
      const index = this.gameContainer.detector.bodies.findIndex(
        (b) => b === body
      );
      if (index > -1) this.gameContainer.detector.bodies.splice(index, 1);
    }
    setTimeout(() => {
      if (this.container) {
        const parent = this.container.parent;
        if (parent) {
          parent.removeChild(this.container);
        }
        this.container.destroy({ children: true });
      }
    }, 100);
  }

  handleCollision(
    collisionObject: GameObject | CollisionRegion | null,
    hitPoint: [number, number],
    hitPointScreen: [number, number],
    objectBody: Matter.Body,
    collisionBody: Matter.Body
  ): void {
    if (this.collisionHandler) {
      this.collisionHandler.handleCollision(
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
  }

  get clickWidth(): number {
    //if (this.container) return this.container.width + this.colliderDelta * 2;
    //if (this.body) return this.body.bounds.max.x - this.body.bounds.min.x;
    return this.displayWidth + this.colliderDelta * 2;
  }

  get clickHeight(): number {
    //if (this.container) return this.container.height + this.colliderDelta * 2;
    //if (this.body) return this.body.bounds.max.y - this.body.bounds.min.y;
    return this.displayHeight + this.colliderDelta * 2;
  }

  isVisible(delta = 0): boolean {
    if (!this.body) return false;
    const x = this.body.position.x; // this.position[0];
    const y = this.body.position.y; // this.position[1];
    return (
      x >= -delta &&
      x <= this.gameContainer.gameWidth + delta &&
      y >= -delta &&
      y <= this.gameContainer.gameHeight + delta
    );
  }

  boundsWidth: number | null = null;
  boundsHeight: number | null = null;
  boundsGraphic: PIXI.Graphics | null = null;
  drawBorder(inputGraphics: PIXI.Graphics | null = null): void {
    const graphics = inputGraphics ?? this.boundsGraphic;
    if (inputGraphics) this.boundsGraphic = inputGraphics;
    if (graphics && this.body) {
      /*const width = this.clickWidth;
      const height = this.clickHeight;

      const centerX =
        this.displayWidth * this.anchor[0] - this.displayWidth / 2;
      const centerY =
        this.displayHeight * this.anchor[1] - this.displayHeight / 2;
      if (this.type === 'rect') {
        graphics.clear();
        graphics.lineStyle(2, '#ff0000');

        graphics.drawRect(
          -width / 2 - centerX,
          -height / 2 - centerY,
          width,
          height
        );
      } else if (this.type === 'circle') {
        graphics.clear();
        graphics.lineStyle(2, '#ff0000');
        graphics.drawCircle(
          graphics.x - centerX,
          graphics.y - centerY,
          (width > height ? width : height) / 2
        );
      } else*/ {
        graphics.clear();
        graphics.lineStyle(2, '#ff0000');
        const path = this.body.vertices.map((item) => {
          return {
            x: item.x - this.body.position.x,
            y: item.y - this.body.position.y,
          };
        });
        graphics.drawPolygon(path);
      }
      this.boundsWidth = graphics.width;
      this.boundsHeight = graphics.height;
    }
  }

  @Watch('triggerDelay', { immediate: true })
  startTriggerListener(): void {
    if (this.triggerDelay) this.triggerStartTime = Date.now();
    else this.triggerStartTime = null;
  }

  checkTrigger(): boolean {
    if (
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
}
</script>

<style scoped lang="scss"></style>
