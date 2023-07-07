<template>
  <container
    @render="containerLoad"
    :x="position[0]"
    :y="position[1]"
    :gameObject="123"
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

@Options({
  components: {},
  emits: [
    'update:x',
    'update:y',
    'update:id',
    'destroyObject',
    'outsideDrawingSpace',
    'sizeChanged',
    'collision',
  ],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class GameObject extends Vue {
  @Prop({ default: 100 }) renderDelay!: number;
  @Prop({ default: 0 }) id!: number;
  @Prop({ default: 0 }) x!: number;
  @Prop({ default: 0 }) y!: number;
  @Prop({ default: ObjectSpace.Absolute }) objectSpace!: ObjectSpace;
  @Prop({ default: 'rect' }) readonly type!: 'rect' | 'circle';
  @Prop({ default: {} }) readonly options!: {
    [key: string]: string | number | boolean;
  };
  @Prop({ default: false }) readonly isStatic!: boolean;
  @Prop() readonly collisionHandler!: CollisionHandler;
  body!: typeof Matter.Body;
  position: [number, number] = [0, 0];
  container!: PIXI.Container;
  gameContainer!: GameContainer;

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
  }

  containerLoad(container: PIXI.Container): void {
    this.container = container;
    setTimeout(() => {
      this.$emit('sizeChanged', [container.width, container.height]);
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
    this.body = Matter.Bodies.rectangle(
      x + width / 2,
      y + height / 2,
      width,
      height,
      this.options
    );
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
    if (this.objectSpace === ObjectSpace.Relative && this.gameContainer)
      this.position = [
        (this.x / 100) * this.gameContainer.gameWidth,
        (this.y / 100) * this.gameContainer.gameHeight,
      ];
    else this.position = [this.x, this.y];
  }

  @Watch('x', { immediate: true })
  @Watch('y', { immediate: true })
  onModelValueChanged(): void {
    this.initPosition();
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
      (this.body.position.x !== this.position[0] ||
        this.body.position.y !== this.position[1])
    ) {
      if (this.gameContainer) {
        if (
          this.body.position.x > this.gameContainer.gameWidth ||
          this.body.position.x < 0 ||
          this.body.position.y > this.gameContainer.gameHeight ||
          this.body.position.y < 0
        ) {
          this.$emit('outsideDrawingSpace', this);
        }
        /*let outside = false;
        let x = this.body.position.x;
        if (
          this.body.position.x > this.gameContainer.gameWidth ||
          this.body.position.x < 0
        ) {
          x = this.gameContainer.gameWidth / 2;
          outside = true;
        }
        let y = this.body.position.y;
        if (this.body.position.y > this.gameContainer.gameHeight) {
          y = this.gameContainer.gameHeight / 2;
          outside = true;
        }
        if (outside) Matter.Body.setPosition(this.body, { x: x, y: y });*/
      }
      this.position[0] = this.body.position.x;
      this.position[1] = this.body.position.y;
      this.$emit('update:x', this.body.position.x);
      this.$emit('update:y', this.body.position.y);
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
