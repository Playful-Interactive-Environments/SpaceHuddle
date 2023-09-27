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
      v-if="body && showBounds && loadingFinished"
      @render="drawBorder"
      :x="0"
      :y="0"
      :width="boundsWidth ?? clickWidth"
      :height="boundsHeight ?? clickHeight"
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
import { GrayscaleFilter } from 'pixi-filters';
import Polygon from 'polygon';
import Vec2 from 'vec2';

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
  @Prop({ default: true }) moveWithBackground!: boolean;
  @Prop({ default: null }) triggerDelay!: number | null;
  @Prop({ default: false }) highlighted!: boolean;
  @Prop({ default: false }) disabled!: boolean;
  @Prop({ default: 0 }) anchor!: number | [number, number];
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
      try {
        this.displayWidth = container.width;
        this.displayHeight = container.height;
        this.$emit('sizeChanged', [this.displayWidth, this.displayHeight]);
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
          case 'polygon':
            this.addPolygon(
              container.x,
              container.y,
              container.width,
              container.height,
              this.polygonShape
            );
            break;
        }
        this.$emit('initialised', this);
      } catch (e) {
        this.$emit('initError', this);
      }
    }, this.renderDelay);
  }

  updatedColliderSize(): void {
    if (this.body) {
      const scale = this.container.width / this.displayWidth;
      /*const scaleX = this.container.width / this.displayWidth;
      const scaleY = this.container.height / this.displayHeight;*/
      Matter.Body.scale(this.body, scale, scale);
      this.displayWidth = this.container.width;
      this.displayHeight = this.container.height;
      this.$emit('sizeChanged', [this.displayWidth, this.displayHeight]);
      if (this.boundsGraphic) this.drawBorder();
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
    this.updatePivot();
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
    const radius =
      (width > height ? width / 2 : height / 2) + this.colliderDelta;
    this.body = Matter.Bodies.circle(x, y, radius, this.options);
    this.updatePivot();
    this.onRotationChanged();
    this.onScaleChanged();
    this.$emit('update:id', this.body.id);
    if (this.clickable) {
      this.addBodyToEngine();
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
    const path = shape.map((item) => {
      return { x: (item[0] / 100) * width, y: (item[1] / 100) * height };
    });
    const p = new Polygon(shape.map((point) => new Vec2(point[0], point[1])));
    const centerDelta = new Vec2(50, 50).subtract(p.center());
    const deltaX = (width * centerDelta.x) / 100;
    const deltaY = (height * centerDelta.y) / 100;
    this.body = Matter.Bodies.fromVertices(x, y, [path], this.options);
    Matter.Body.setCentre(this.body, { x: deltaX, y: deltaY }, true);
    Matter.Body.setPosition(this.body, { x: x, y: y });
    this.updatePivot();
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
    if (this.body) {
      Matter.Body.setPosition(this.body, {
        x: this.position[0] - this.offset[0],
        y: this.position[1] - this.offset[1],
      });
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
      /*if (this.body) {
        Matter.Body.setPosition(this.body, {
          x: this.position[0] - this.offset[0],
          y: this.position[1] - this.offset[1],
        });
      }*/
    }
  }

  @Watch('rotation', { immediate: true })
  onRotationChanged(): void {
    this.rotationValue = turf.degreesToRadians(this.rotation);
    if (this.body) {
      Matter.Body.setAngle(this.body, this.rotationValue);
    }
  }

  appliedScaleFactor = 1;
  @Watch('scale', { immediate: true })
  onScaleChanged(): void {
    if (this.body && this.scale !== this.appliedScaleFactor) {
      const scale = (1 / this.appliedScaleFactor) * this.scale;
      Matter.Body.scale(this.body, scale, scale);
      this.appliedScaleFactor = this.scale;
      //if (this.boundsGraphic) this.drawBorder();
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

  handleCollision(collisionObject: GameObject | null): void {
    if (this.collisionHandler) {
      this.collisionHandler.handleCollision(this, collisionObject);
    }

    this.$emit('collision', this, collisionObject);
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
