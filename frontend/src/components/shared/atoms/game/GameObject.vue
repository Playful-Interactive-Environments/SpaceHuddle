<template>
  <container
    @render="containerLoad"
    :x="position[0] - offset[0]"
    :y="position[1] - offset[1]"
    :rotation="rotationValue"
    :scale="scale"
    @pointerdown="gameObjectClicked"
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
import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import { ObjectSpace } from '@/types/enum/ObjectSpace';
import { delay } from '@/utils/wait';
import * as turf from '@turf/turf';

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
  @Prop({ default: {} }) readonly options!: {
    [key: string]: string | number | boolean;
  };
  @Prop({ default: false }) readonly isStatic!: boolean;
  @Prop() readonly collisionHandler!: CollisionHandler;
  @Prop() readonly source!: any;
  @Prop({ default: true }) usePhysic!: boolean;
  @Prop({ default: true }) moveWithBackground!: boolean;
  body!: typeof Matter.Body;
  position: [number, number] = [0, 0];
  rotationValue = 0;
  container!: PIXI.Container;
  gameContainer!: GameContainer;
  offset: [number, number] = [0, 0];
  readonly defaultSize = 50;
  displayWidth = this.defaultSize;
  displayHeight = this.defaultSize;

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
    if (this.body && !this.isStatic && !this.usePhysic) {
      this.body.isStatic = false;
    }
    this.$emit('click', this);
    if (this.gameContainer) {
      this.clickTime = Date.now();
      this.gameContainer.$emit('update:selectedObject', this);
      this.gameContainer.activeObject = this;
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
    this.body = Matter.Bodies.rectangle(x, y, width, height, this.options);
    this.$emit('update:id', this.body.id);
    this.addBodyToEngine();
    this.addBodyToDetector();
  }

  addCircle(x: number, y: number, width: number, height: number): void {
    this.options.isStatic = this.isStatic;
    this.body = Matter.Bodies.circle(
      x,
      y,
      width > height ? width / 2 : height / 2,
      this.options
    );
    this.$emit('update:id', this.body.id);
    this.addBodyToEngine();
    this.addBodyToDetector();
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

  initPosition(): void {
    if (
      this.objectSpace === ObjectSpace.RelativeToScreen &&
      this.gameContainer
    ) {
      this.position = [
        (this.x / 100) * this.gameContainer.gameWidth,
        (this.y / 100) * this.gameContainer.gameHeight,
      ];
    } else if (
      this.objectSpace === ObjectSpace.RelativeToBackground &&
      this.gameContainer
    ) {
      this.position = [
        (this.x / 100) * this.gameContainer.backgroundTextureSize[0],
        (this.y / 100) * this.gameContainer.backgroundTextureSize[1],
      ];
    } else {
      this.position = [this.x, this.y];
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
    if (inputPosition[0] !== this.x || inputPosition[1] !== this.y)
      this.initPosition();
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

  @Watch('gameContainer', { immediate: true })
  onEngineChanged(): void {
    setTimeout(() => {
      this.initPosition();
    }, 100);
    this.addBodyToEngine();
    this.addBodyToDetector();
  }

  syncronize(): void {
    if (
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
      this.$emit('update:x', inputPosition[0]);
      this.$emit('update:y', inputPosition[1]);
    }
  }

  notifyDestroy(): void {
    this.$emit('destroyObject', this);
    //this.kill();
  }

  kill(): void {
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
}
</script>

<style scoped lang="scss"></style>
