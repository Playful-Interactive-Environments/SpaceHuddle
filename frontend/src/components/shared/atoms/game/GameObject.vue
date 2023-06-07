<template compiler="pixi.js">
  <container @render="containerLoad" :x="position[0]" :y="position[1]">
    <slot></slot>
  </container>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as Matter from 'matter-js/build/matter';
import * as PIXI from 'pixi.js';

@Options({
  components: {},
  emits: ['update:x', 'update:y', 'destroyObject'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class GameObject extends Vue {
  @Prop({ default: 0 }) id!: number;
  @Prop({ default: 0 }) x!: number;
  @Prop({ default: 0 }) y!: number;
  @Prop() readonly engine!: typeof Matter.Engine;
  @Prop() readonly detector!: typeof Matter.Detector;
  @Prop({ default: 'rect' }) readonly type!: 'rect' | 'circle';
  @Prop({ default: {} }) readonly options!: {
    [key: string]: string | number | boolean;
  };
  @Prop({ default: false }) readonly isStatic!: boolean;
  @Prop({
    default: undefined,
  })
  readonly collisionsFilter!:
    | ((collision: Matter.Collision) => boolean)
    | undefined;
  body!: typeof Matter.Body;
  readonly intervalTime = 50;
  interval = -1;
  position: [number, number] = [0, 0];
  container!: PIXI.Container;

  mounted(): void {
    //
  }

  unmounted(): void {
    this.cleanup();
  }

  containerLoad(container: PIXI.Container): void {
    this.container = container;
    setTimeout(() => {
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

      if (!this.isStatic) {
        clearInterval(this.interval);
        this.interval = setInterval(this.syncronize, this.intervalTime);
      }
    }, 100);
  }

  addRect(x: number, y: number, width: number, height: number): void {
    this.options.isStatic = this.isStatic;
    this.body = Matter.Bodies.rectangle(
      x + width / 2,
      y + height / 2,
      width,
      height,
      this.options
    );
    this.addBodyToEngine();
    this.addBodyToDetector();
  }

  addCircle(x: number, y: number, width: number, height: number): void {
    this.options.isStatic = this.isStatic;
    this.body = Matter.Bodies.circle(
      x + width / 2,
      y + height / 2,
      width > height ? width / 2 : height / 2,
      this.options
    );
    this.addBodyToEngine();
    this.addBodyToDetector();
  }

  addBodyToEngine(): void {
    if (this.engine && this.body) {
      Matter.Composite.add(this.engine.world, this.body);
    }
  }

  addBodyToDetector(): void {
    if (this.detector && this.body) {
      this.detector.bodies.push(this.body);
    }
  }

  @Watch('x', { immediate: true })
  @Watch('y', { immediate: true })
  onModelValueChanged(): void {
    this.position = [this.x, this.y];
  }

  @Watch('engine', { immediate: true })
  onEngineChanged(): void {
    this.addBodyToEngine();
  }

  @Watch('detector', { immediate: true })
  onDetectorChanged(): void {
    this.addBodyToDetector();
  }

  syncronize(): void {
    if (
      this.engine &&
      this.detector &&
      this.body &&
      (this.body.position.x !== this.position[0] ||
        this.body.position.y !== this.position[1])
    ) {
      this.position[0] = this.body.position.x;
      this.position[1] = this.body.position.y;
      this.$emit('update:x', this.body.position.x);
      this.$emit('update:y', this.body.position.y);
      const collisions = Matter.Detector.collisions(this.detector);
      if (collisions.length > 0) {
        const rightAnswer = this.collisionsFilter
          ? collisions.find((collision) => {
              if (
                collision.bodyA.id !== this.body.id &&
                collision.bodyB.id !== this.body.id
              )
                return false;
              if (this.collisionsFilter)
                return this.collisionsFilter(collision);
              return true;
            })
          : collisions;
        if (rightAnswer) {
          clearInterval(this.interval);
          this.$emit('destroyObject', this);
          //this.cleanup();
        }
      }
    }
  }

  cleanup(): void {
    clearInterval(this.interval);
    Matter.Composite.remove(this.engine.world, this.body);
    const index = this.detector.bodies.findIndex((body) => body === this.body);
    if (index > -1) this.detector.bodies.splice(index, 1);
    setTimeout(() => {
      const parent = this.container.parent;
      if (parent) {
        parent.removeChild(this.container);
      }
      this.container.destroy({ children: true });
    }, 100);
  }
}
</script>

<style scoped lang="scss"></style>
