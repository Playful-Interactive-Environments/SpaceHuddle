<template>
  <div ref="gameContainer">
    <Application
      ref="pixi"
      :width="gameWidth"
      :height="gameHeight"
      v-if="ready"
      backgroundColor="#f4f4f4"
    >
      <container>
        <slot :itemProps="{ engine: engine, detector: detector }"></slot>
      </container>
    </Application>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as Matter from 'matter-js/build/matter';
import { Application } from 'vue3-pixi';

@Options({
  components: {
    Application,
  },
  emits: ['initEngine', 'initDetector'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class GameContainer extends Vue {
  @Prop({ default: true }) readonly hasMouseInput!: boolean;
  ready = false;
  gameWidth = 0;
  gameHeight = 0;

  canvasPosition: [number, number] = [0, 0];
  engine!: typeof Matter.Engine;
  runner!: typeof Matter.Runner;
  detector!: typeof Matter.Detector;
  mouseConstraint!: typeof Matter.MouseConstraint;
  observer = new MutationObserver(this.callback);

  async mounted(): Promise<void> {
    this.setupMatter();
    setTimeout(() => {
      this.setupPixiSpace();
    }, 1000);
    /*setTimeout(() => {
      if (this.$slots.default) {
        const children = this.$slots.default();
        for (const child of children) {
          const gameObject = child as any;
          console.log(gameObject);
          if (gameObject.type.props) {
            if ('engine' in gameObject.type.props)
              gameObject.props.engine = this.engine;
            if ('detector' in gameObject.type.props)
              gameObject.props.detector = this.detector;
            console.log(gameObject.props.engine, gameObject.ctx.components);
          }
        }
      }
    }, 1000);*/
  }

  callback(mutationList: MutationRecord[], observer: MutationObserver): void {
    const cleanUp = (observer as any).cleanUp;
    for (const mutation of mutationList) {
      if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
        const canvas = Array.from(mutation.addedNodes).find(
          (node) => node.nodeName.toLowerCase() === 'canvas'
        ) as HTMLCanvasElement | undefined;
        if (canvas) {
          cleanUp.setupMouseConstraint(canvas);
          return;
        }
      }
    }
  }

  unmounted(): void {
    this.observer.disconnect();
  }

  setupPixiSpace(): void {
    const dom = this.$refs.gameContainer as HTMLElement;
    if (this.hasMouseInput) {
      (this.observer as any).cleanUp = this;
      this.observer.observe(dom, {childList: true, subtree: true});
    }
    if (dom) {
      const targetWidth = dom.parentElement?.offsetWidth;
      const targetHeight = dom.parentElement?.offsetHeight;
      if (targetWidth && targetHeight) {
        (dom as any).style.width = `${targetWidth}px`;
        (dom as any).style.height = `calc(${targetHeight}px - 10rem)`;
      }
      this.gameWidth = dom.offsetWidth;
      this.gameHeight = dom.offsetHeight;
      const bounds = dom.getBoundingClientRect();
      this.canvasPosition = [bounds.left, bounds.top];
      this.setupBound();
      this.ready = true;
    }
  }

  setupMouseConstraint(canvas: HTMLCanvasElement | undefined): void {
    if (this.engine && canvas) {
      // add mouse control
      const mouse = Matter.Mouse.create(canvas);
      this.mouseConstraint = Matter.MouseConstraint.create(this.engine, {
        mouse: mouse,
        constraint: {
          stiffness: 0.2,
        },
      });
      Matter.Composite.add(this.engine.world, this.mouseConstraint);
    }
  }

  setupBound(): void {
    if (this.engine) {
      const borderSize = 1;
      Matter.Composite.add(
        this.engine.world,
        Matter.Bodies.rectangle(
          this.gameWidth / 2,
          this.gameHeight - borderSize / 2,
          this.gameWidth,
          borderSize,
          { isStatic: true, isHidden: true }
        )
      );
      Matter.Composite.add(
        this.engine.world,
        Matter.Bodies.rectangle(
          this.gameWidth / 2,
          borderSize / 2,
          this.gameWidth,
          borderSize,
          { isStatic: true, isHidden: true }
        )
      );
      Matter.Composite.add(
        this.engine.world,
        Matter.Bodies.rectangle(
          borderSize / 2,
          this.gameHeight / 2,
          borderSize,
          this.gameHeight,
          { isStatic: true, isHidden: true }
        )
      );
      Matter.Composite.add(
        this.engine.world,
        Matter.Bodies.rectangle(
          this.gameWidth - borderSize / 2,
          this.gameHeight / 2,
          borderSize,
          this.gameHeight,
          { isStatic: true, isHidden: true }
        )
      );
    }
  }

  setupMatter(): void {
    this.engine = Matter.Engine.create();
    this.$emit('initEngine', this.engine);
    this.engine.gravity = {
      x: 0,
      y: 1,
      scale: 0.0005,
    };
    this.runner = Matter.Runner.create();
    this.detector = Matter.Detector.create({ bodies: [] });
    this.$emit('initDetector', this.detector);
    Matter.Runner.run(this.runner, this.engine);
  }
}
</script>

<style scoped lang="scss"></style>
