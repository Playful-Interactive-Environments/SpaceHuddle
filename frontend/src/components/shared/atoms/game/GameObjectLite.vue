<template>
  <container
    v-if="isVisibleInContainer && isActive"
    :mask="mask"
    :x="position[0] - offset[0]"
    :y="position[1] - offset[1]"
    :rotation="rotationValue"
    :scale="scale"
    :filters="objectFilters"
    @render="containerLoad"
  >
    <slot></slot>
  </container>
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
} from '@/components/shared/atoms/game/GameContainerLite.vue';
import { delay, until } from '@/utils/wait';
import { GrayscaleFilter } from 'pixi-filters';
import { toDegrees, toRadians } from '@/utils/angle';
import * as matterUtil from '@/utils/matter';

const logStartCalls = false;
const logEndCalls = false;

@Options({
  components: {},
  emits: [
    'update:x',
    'update:y',
    'update:id',
    'update:rotation',
    'destroyObject',
    'notifyCollision',
    'outsideDrawingSpace',
    'sizeChanged',
    'collision',
    'click',
    'release',
    'update:highlighted',
    'positionChanged',
    'initialised',
    'initError',
  ],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class GameObject extends Vue {
  //#region props
  @Prop({ default: 100 }) renderDelay!: number;
  @Prop({ default: 0 }) id!: number;
  @Prop({ default: 0 }) x!: number;
  @Prop({ default: 0 }) y!: number;
  @Prop({ default: null }) fixSize!: [number, number] | number | null;
  @Prop({ default: 0 }) rotation!: number;
  @Prop({ default: 1 }) scale!: number;
  @Prop({ default: 'rect' }) readonly type!: 'rect' | 'circle' | 'polygon';
  @Prop({ default: [] }) readonly polygonShape!: [number, number][];
  @Prop({ default: 0 }) readonly colliderDelta!: number;
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
  @Prop({ default: false }) highlighted!: boolean;
  @Prop({ default: false }) disabled!: boolean;
  @Prop({ default: 0 }) anchor!: number | [number, number];
  @Prop({ default: 0 }) zIndex!: number;
  @Prop({ default: null }) mask!:
    | PIXI.Container<PIXI.DisplayObject>
    | PIXI.MaskData
    | null;
  //#endregion props

  //#region variables
  body: typeof Matter.Body | null = null;
  position: [number, number] = [0, 0];
  rotationValue = 0;
  containerSize: PIXI.Container | null = null;
  containerPosition: PIXI.Container | null = null;
  gameContainer!: GameContainer;
  offset: [number, number] = [0, 0];
  readonly defaultSize = 50;
  displayWidth = this.defaultSize;
  displayHeight = this.defaultSize;
  destroyed = false;
  objectFilters: any[] = [];
  loadingFinished = false;
  isPartOfEngin = false;
  //#endregion variables

  //#region get / set
  getContainerWidth(): number {
    //if (logStartCalls) console.log('getContainerWidth');
    if (this.fixSize === null)
      return this.containerSize ? this.containerSize.width : 100;
    if (Array.isArray(this.fixSize)) return this.fixSize[0];
    return this.fixSize;
  }

  getContainerHeight(): number {
    //if (logStartCalls) console.log('getContainerHeight');
    if (this.fixSize === null)
      return this.containerSize ? this.containerSize.height : 100;
    if (Array.isArray(this.fixSize)) return this.fixSize[1];
    return this.fixSize;
  }

  isPositionVisible(x: number, y: number, delta = 0): boolean {
    //if (logStartCalls) console.log('isPositionVisible');
    const deltaX = this.displayWidth / 2;
    const deltaY = this.displayHeight / 2;
    return (
      x >= -delta - deltaX &&
      x <= this.gameContainer.gameWidth + delta + deltaX &&
      y >= -delta - deltaY &&
      y <= this.gameContainer.gameDisplayHeight + delta + deltaY
    );
  }

  isVisible(delta = 0): boolean {
    //if (logStartCalls) console.log('isVisible');
    if (!this.body) return false;
    const x = this.body.position.x;
    const y = this.body.position.y;
    return this.isPositionVisible(x, y, delta);
  }
  //#endregion get / set

  //#region load / unload
  async mounted(): Promise<void> {
    if (logStartCalls) console.log('mounted');
    this.eventBus.emit(EventType.REGISTER_GAME_OBJECT, {
      gameObject: this,
    });
    if (logEndCalls) console.log('mounted');
  }

  unmounted(): void {
    if (logStartCalls) console.log('unmounted');
    this.kill();
    this.gameObjectReleased();
    if (logEndCalls) console.log('unmounted');
  }

  setGameContainer(gameContainer: GameContainer): void {
    if (logStartCalls) console.log('setGameContainer');
    this.gameContainer = gameContainer;
    this.initPosition();
    if (logEndCalls) console.log('setGameContainer');
  }

  kill(): void {
    if (logStartCalls) console.log('kill');
    this.destroyed = true;
    if (this.gameContainer) {
      this.gameContainer.deregisterGameObject(this);
      this.body = null;
    }
    setTimeout(() => {
      if (this.containerPosition) {
        const parent = this.containerPosition.parent;
        if (parent) {
          parent.removeChild(this.containerPosition as any);
        }
        this.containerPosition.destroy({ children: true });
      }
    }, 100);
    if (logEndCalls) console.log('kill');
  }
  //#endregion load / unload

  //#region interaction
  clickTime = 0;
  gameObjectClicked(): void {
    if (logStartCalls) console.log('gameObjectClicked');
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
    if (logStartCalls) console.log('gameObjectReleased');
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
    if (logStartCalls) console.log('onDisabledChanged');
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

  @Watch('body', { immediate: true })
  onBodyChanged(): void {
    if (this.body) {
      this.appliedScaleFactor = this.scale;
      this.updatePivot();
      this.onRotationChanged();
      this.onScaleChanged();
      this.$emit('update:id', this.body.id);
      this.$emit('initialised', this);
    }
  }

  @Watch('isActive', { immediate: true })
  onIsActiveChanged(): void {
    if (logStartCalls) console.log('onIsActiveChanged');
    //this.isVisibleInContainer = this.isVisible();
  }
  //#endregion watch
  index = 0;
  async containerLoad(container: PIXI.Container): Promise<void> {
    if (logStartCalls) console.log('containerLoad', this.containerSize);
    if (this.containerSize) return;

    this.containerSize = container;
    this.containerPosition = container;
    const delayTime = this.fixSize === null ? 0 : this.renderDelay;
    await delay(delayTime);
    await until(() => !!this.gameContainer);
    try {
      this.displayWidth = this.getContainerWidth();
      this.displayHeight = this.getContainerHeight();
    } catch (e) {
      this.$emit('initError', this);
    }
  }

  updatedColliderSize(): void {
    if (logStartCalls) console.log('updatedColliderSize');
    const updateBody = (): void => {
      try {
        const containerWidth = this.getContainerWidth();
        if (
          this.body &&
          this.containerPosition &&
          containerWidth !== this.displayWidth
        ) {
          const scale = containerWidth / this.displayWidth;
          Matter.Body.scale(this.body, scale, scale);
          this.displayWidth = containerWidth;
          this.displayHeight = this.getContainerHeight();
          this.$emit('sizeChanged', [this.displayWidth, this.displayHeight]);
        }
      } catch (e) {
        //
      }
    };

    if (this.body && this.containerPosition) {
      updateBody();
    } else {
      setTimeout(() => {
        updateBody();
      }, 1000);
    }
  }

  async updatePivot(delta = 100, alwaysUpdate = false): Promise<void> {
    if (logStartCalls) console.log('updatePivot');
    await matterUtil.updatePivot(this.body, this.anchor, delta, alwaysUpdate);
    this.loadingFinished = true;
  }
  //#endregion init body

  //#region position / rotation / scale
  initPosition(x: number | null = null, y: number | null = null): void {
    if (logStartCalls) console.log('initPosition');
    if (x === null) x = this.x;
    if (y === null) y = this.y;
    this.position = [x, y];
    if (this.body) {
      Matter.Body.setPosition(this.body, {
        x: this.position[0] - this.offset[0],
        y: this.position[1] - this.offset[1],
      });
    }
  }

  convertPositionToInputFormat(): [number, number] {
    //if (logStartCalls) console.log('convertPositionToInputFormat');
    return [this.position[0], this.position[1]];
  }

  @Watch('x', { immediate: true })
  @Watch('y', { immediate: true })
  onModelValueChanged(): void {
    if (logStartCalls) console.log('onModelValueChanged');
    const inputPosition = this.convertPositionToInputFormat();
    if (inputPosition[0] !== this.x || inputPosition[1] !== this.y) {
      this.initPosition();
    }
  }

  @Watch('rotation', { immediate: true })
  onRotationChanged(): void {
    //if (logStartCalls) console.log('onRotationChanged');
    if (!isNaN(this.rotation)) {
      this.rotationValue = toRadians(360 - this.rotation);
      if (this.body) {
        Matter.Body.setAngle(this.body, this.rotationValue);
      }
    }
  }

  appliedScaleFactor = 1;
  @Watch('scale', { immediate: true })
  onScaleChanged(): void {
    //if (logStartCalls) console.log('onScaleChanged');
    if (this.body && this.scale !== this.appliedScaleFactor) {
      const scale = (1 / this.appliedScaleFactor) * this.scale;
      Matter.Body.scale(this.body, scale, scale);
      this.appliedScaleFactor = this.scale;
    }
  }
  //#endregion position / rotation / scale

  //#region matter update
  wasVisible = false;
  wasAtBorder = false;
  beforePhysicUpdate(): void {
    //if (logStartCalls) console.log('beforePhysicUpdate');
    if (
      !this.destroyed &&
      this.body &&
      !this.body.isStatic &&
      !this.body.isSleeping
    ) {
      const isVisible = this.isVisible(-this.displayWidth / 2);
      const isAtBorder = this.isVisible(this.displayWidth / 2) && !isVisible;
      this.wasVisible = isVisible;
      this.wasAtBorder = isAtBorder;
    }
  }

  isVisibleInContainer = true;
  afterPhysicUpdate(): void {
    //if (logStartCalls) console.log('afterPhysicUpdate');
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
        this.isVisibleInContainer = this.isVisible();
        if (this.gameContainer) {
          const outsideRight =
            this.body.position.x > this.gameContainer.gameWidth;
          const outsideLeft = this.body.position.x < 0;
          const outsideBottom =
            this.body.position.y > this.gameContainer.gameDisplayHeight;
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
    }
  }
  //#endregion matter update

  //#region collision
  notifyCollision(): void {
    if (logStartCalls) console.log('notifyCollision');
    this.$emit('notifyCollision', this);
  }

  handleCollision(
    collisionObject: GameObject | CollisionRegion | null,
    hitPoint: [number, number],
    hitPointScreen: [number, number],
    objectBody: Matter.Body,
    collisionBody: Matter.Body
  ): boolean {
    if (logStartCalls) console.log('handleCollision');
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
}
</script>

<style scoped lang="scss"></style>
