<template>
  <container
    @render="containerLoad"
    :x="position[0] - offset[0]"
    :y="position[1] - offset[1]"
    :rotation="rotationValue"
    :scale="scale"
    :filters="objectFilters"
  >
    <slot></slot>
    <Graphics
      v-if="body && showBounds"
      @render="drawBorder"
      :x="0"
      :y="0"
      :width="clickWidth"
      :height="clickHeight"
    ></Graphics>
  </container>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as Matter from 'matter-js/build/matter';
import * as PIXI from 'pixi.js';
import { EventType } from '@/types/enum/EventType';
import { CollisionHandler } from '@/types/game/CollisionHandler';
import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import { ObjectSpace } from '@/types/enum/ObjectSpace';
import { delay } from '@/utils/wait';
import * as turf from '@turf/turf';
import { GrayscaleFilter } from '@pixi/filter-grayscale';

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
  ],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class GameObject extends Vue {
  @Prop({ default: 100 }) renderDelay!: number;
  @Prop({ default: 0 }) id!: number;
  @Prop({ default: 0 }) x!: number;
  @Prop({ default: 0 }) y!: number;
  @Prop({ default: 0 }) rotation!: number;
  @Prop({ default: 1 }) scale!: number;
  //@Prop({ default: 0.5 }) anchor!: number;
  @Prop({ default: ObjectSpace.Absolute }) objectSpace!: ObjectSpace;
  @Prop({ default: 'rect' }) readonly type!: 'rect' | 'circle';
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
  @Prop({ default: true }) moveWithBackground!: boolean;
  @Prop({ default: null }) triggerDelay!: number | null;
  @Prop({ default: false }) highlighted!: boolean;
  @Prop({ default: false }) disabled!: boolean;
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

  get displayX(): number {
    return this.position[0] - this.offset[0];
  }

  get displayY(): number {
    return this.position[1] - this.offset[1];
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
    this.container = container;
    setTimeout(() => {
      this.$emit('sizeChanged', [container.width, container.height]);
      this.displayWidth = container.width;
      this.displayHeight = container.height;
      switch (this.type) {
        case 'rect':
          this.addRect(
            container.x,
            container.y,
            container.width,
            container.height
          );
          break;
        case 'circle':
          this.addCircle(
            container.x,
            container.y,
            container.width,
            container.height
          );
          break;
      }
    }, this.renderDelay);
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
    this.onRotationChanged();
    this.onScaleChanged();
    this.$emit('update:id', this.body.id);
    if (this.clickable) {
      this.addBodyToEngine();
      this.addBodyToDetector();
    }
  }

  addCircle(x: number, y: number, width: number, height: number): void {
    this.options.isStatic = this.isStatic;
    this.body = Matter.Bodies.circle(
      x,
      y,
      (width > height ? width / 2 : height / 2) + this.colliderDelta,
      this.options
    );
    this.onRotationChanged();
    this.onScaleChanged();
    this.$emit('update:id', this.body.id);
    if (this.clickable) {
      this.addBodyToEngine();
      this.addBodyToDetector();
    }
  }

  addBodyToEngine(): void {
    if (this.gameContainer && this.body) {
      Matter.Composite.add(this.gameContainer.engine.world, this.body);
    }
  }

  addBodyToDetector(): void {
    if (this.gameContainer && this.body) {
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
      if (this.body) {
        Matter.Body.setPosition(this.body, {
          x: this.position[0] - this.offset[0],
          y: this.position[1] - this.offset[1],
        });
      }
    }
  }

  @Watch('rotation', { immediate: true })
  onRotationChanged(): void {
    this.rotationValue = turf.degreesToRadians(this.rotation);
    if (this.body) {
      Matter.Body.setAngle(this.body, this.rotationValue);
    }
  }

  @Watch('scale', { immediate: true })
  onScaleChanged(): void {
    if (this.body) {
      Matter.Body.scale(this.body, this.scale, this.scale);
    }
  }

  setGameContainer(gameContainer: GameContainer): void {
    this.gameContainer = gameContainer;
    this.initPosition();
    this.addBodyToEngine();
    this.addBodyToDetector();
  }

  syncronize(): void {
    if (
      !this.destroyed &&
      !this.isStatic &&
      this.body &&
      (this.body.position.x + this.offset[0] !== this.position[0] ||
        this.body.position.y + this.offset[1] !== this.position[1])
    ) {
      if (this.gameContainer) {
        if (
          this.body.position.x + this.offset[0] >
            this.gameContainer.gameWidth ||
          this.body.position.x + this.offset[0] < 0 ||
          this.body.position.y + this.offset[1] >
            this.gameContainer.gameHeight ||
          this.body.position.y + this.offset[1] < 0
        ) {
          this.$emit('outsideDrawingSpace', this);
        }
      }
      this.position = [
        this.body.position.x + this.offset[0],
        this.body.position.y + this.offset[1],
      ];
      this.rotationValue = this.body.angle;
      this.$emit('update:rotation', turf.radiansToDegrees(this.rotationValue));
      const inputPosition = this.convertPositionToInputFormat();
      if (this.x !== inputPosition[0] || this.y !== inputPosition[1]) {
        this.$emit('positionChanged', inputPosition);
      }
      this.$emit('update:x', inputPosition[0]);
      this.$emit('update:y', inputPosition[1]);
    }
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
      const parent = this.container.parent;
      if (parent) {
        parent.removeChild(this.container);
      }
      this.container.destroy({ children: true });
    }, 100);
  }

  handleCollision(): void {
    if (this.collisionHandler) {
      this.collisionHandler.handleCollision(this);
    }

    this.$emit('collision', this);
  }

  get clickWidth(): number {
    if (this.container) return this.container.width + this.colliderDelta * 2;
    //if (this.body) return this.body.bounds.max.x - this.body.bounds.min.x;
    return this.displayWidth;
  }

  get clickHeight(): number {
    if (this.container) return this.container.height + this.colliderDelta * 2;
    //if (this.body) return this.body.bounds.max.y - this.body.bounds.min.y;
    return this.displayHeight;
  }

  drawBorder(graphics: PIXI.Graphics): void {
    if (graphics && this.body) {
      const width = this.clickWidth;
      const height = this.clickHeight;

      if (this.type === 'rect') {
        graphics.clear();
        graphics.lineStyle(2, '#ff0000');
        graphics.drawRect(-width / 2, -height / 2, width, height);
      } else {
        graphics.clear();
        graphics.lineStyle(2, '#ff0000');
        graphics.drawCircle(
          graphics.x,
          graphics.y,
          (width > height ? width : height) / 2
        );
      }
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
