<template>
  <div ref="gameContainer" id="gameContainer">
    <Application
      ref="pixi"
      :width="gameWidth"
      :height="gameHeight"
      v-if="ready"
      :backgroundColor="backgroundColor"
      :transparent="transparent"
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
import { EventType } from '@/types/enum/EventType';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';

@Options({
  components: {
    Application,
  },
  emits: [
    'initEngine',
    'initDetector',
    'initRenderer',
    'update:width',
    'update:height',
  ],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class GameContainer extends Vue {
  @Prop({ default: true }) readonly hasMouseInput!: boolean;
  @Prop({ default: true }) readonly detectCollision!: boolean;
  @Prop({ default: true }) readonly useGravity!: boolean;
  @Prop({ default: false }) readonly useWind!: boolean;
  @Prop({ default: true }) readonly useBorders!: boolean;
  @Prop({ default: undefined }) readonly width!: number | undefined;
  @Prop({ default: undefined }) readonly height!: number | undefined;
  @Prop({ default: '#f4f4f4' }) readonly backgroundColor!: string;
  @Prop({ default: false }) readonly transparent!: boolean;
  @Prop({
    default: undefined,
  })
  readonly collisionsFilter!:
    | ((collision: Matter.Collision) => boolean)
    | undefined;
  ready = false;
  gameWidth = 0;
  gameHeight = 0;

  canvasPosition: [number, number] = [0, 0];
  engine!: typeof Matter.Engine;
  runner!: typeof Matter.Runner;
  detector!: typeof Matter.Detector;
  mouseConstraint!: typeof Matter.MouseConstraint;
  hierarchyObserver = new MutationObserver(this.hierarchyChanged);
  resizeObserver = new ResizeObserver(this.sizeChanged);

  gameObjects: GameObject[] = [];

  readonly intervalTimeWind = 50;
  intervalWind = -1;
  readonly intervalTimeCollision = 500;
  intervalCollision = -1;
  readonly intervalTimeSync = 100;
  intervalSync = -1;

  async mounted(): Promise<void> {
    const gameContainer = this.$refs.gameContainer as HTMLElement;
    gameContainer.addEventListener(
      EventType.REGISTER_GAME_OBJECT,
      this.registerGameObject
    );

    this.setupMatter();
    (this.resizeObserver as any).cleanUp = this;
    this.resizeObserver.observe(this.$el.parentElement);
    if (this.hasMouseInput) {
      (this.hierarchyObserver as any).cleanUp = this;
      this.hierarchyObserver.observe(this.$refs.gameContainer as HTMLElement, {
        childList: true,
        subtree: false,
      });
    }

    if (this.detectCollision) {
      this.intervalCollision = setInterval(
        this.lookForCollision,
        this.intervalTimeCollision
      );
    }

    if (this.useWind) {
      this.intervalWind = setInterval(this.addWind, this.intervalTimeWind);
    }
    this.intervalSync = setInterval(this.syncRenderView, this.intervalTimeSync);

    setTimeout(async () => {
      const pixi = this.$refs.pixi as typeof Application;
      pixi.app.transparent = this.transparent;
      this.$emit('initRenderer', pixi.app.renderer);
    }, 100);
  }

  registerGameObject(e: any): void {
    const gameObject = e.detail.data as GameObject;
    this.gameObjects.push(gameObject);
    gameObject.gameContainer = this;
  }

  addWind(): void {
    const calcForce = (): number => {
      const forceMagnitude = 0.05; // (0.05 * body.mass) * timeScale;
      return (
        (forceMagnitude + Matter.Common.random() * forceMagnitude) *
        Matter.Common.choose([1, -1])
      );
    };

    for (const gameObject of this.gameObjects) {
      if (gameObject.body) {
        Matter.Body.setVelocity(gameObject.body, {
          x: gameObject.body.velocity.x + calcForce(),
          y: gameObject.body.velocity.y + calcForce(),
        });

        /*Matter.Body.applyForce(gameObject.body, gameObject.body.position, {
          x:
            (forceMagnitude + Matter.Common.random() * forceMagnitude) *
            Matter.Common.choose([1, -1]),
          y:
            (forceMagnitude + Matter.Common.random() * forceMagnitude) *
            Matter.Common.choose([1, -1]),
        });*/
      }
    }
  }

  deregisterGameObject(gameObject: GameObject): void {
    const index = this.gameObjects.findIndex((obj) => obj === gameObject);
    if (index > -1) {
      this.gameObjects.splice(index, 1);
    }
  }

  hierarchyChanged(
    mutationList: MutationRecord[],
    observer: MutationObserver
  ): void {
    const cleanUp = (observer as any).cleanUp as GameContainer;
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

  sizeChanged(
    mutationList: ResizeObserverEntry[],
    observer: ResizeObserver
  ): void {
    const cleanUp = (observer as any).cleanUp as GameContainer;
    cleanUp.setupPixiSpace();
  }

  unmounted(): void {
    this.hierarchyObserver.disconnect();
    this.resizeObserver.disconnect();
    const gameContainer = this.$refs.gameContainer as HTMLElement;
    if (gameContainer) {
      gameContainer.removeEventListener(
        EventType.REGISTER_GAME_OBJECT,
        this.registerGameObject
      );
    }
    clearInterval(this.intervalCollision);
    clearInterval(this.intervalWind);
    clearInterval(this.intervalSync);
  }

  setupPixiSpace(): void {
    const dom = this.$refs.gameContainer as HTMLElement;
    if (dom) {
      const targetWidth = dom.parentElement?.offsetWidth;
      const targetHeight = dom.parentElement?.offsetHeight;
      if (
        (targetWidth && targetHeight && targetWidth !== this.gameWidth) ||
        targetHeight !== this.gameHeight
      ) {
        if (targetWidth && targetHeight) {
          (dom as any).style.width = `${targetWidth}px`;
          (dom as any).style.height = `${targetHeight}px`;
        }
        this.gameWidth = dom.offsetWidth;
        this.gameHeight = dom.offsetHeight;
        this.$emit('update:width', this.gameWidth);
        this.$emit('update:height', this.gameHeight);
        const bounds = dom.getBoundingClientRect();
        this.canvasPosition = [bounds.left, bounds.top];
        this.setupBound();
        this.ready = true;
      }
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

  borders:
    | undefined
    | {
        top: Matter.Body;
        bottom: Matter.Body;
        left: Matter.Body;
        right: Matter.Body;
        width: number;
        height: number;
      } = undefined;
  readonly boundsThickness = 100;
  setupBound(): void {
    const gameWidth = this.gameWidth ? this.gameWidth : 100;
    const gameHeight = this.gameHeight ? this.gameHeight : 100;
    const bounds = {
      bottom: {
        x: gameWidth / 2,
        y: gameHeight + this.boundsThickness / 2,
        width: gameWidth,
        height: this.boundsThickness,
      },
      top: {
        x: gameWidth / 2,
        y: -this.boundsThickness / 2,
        width: gameWidth,
        height: this.boundsThickness,
      },
      left: {
        x: -this.boundsThickness / 2,
        y: gameHeight / 2,
        width: this.boundsThickness,
        height: gameHeight,
      },
      right: {
        x: gameWidth + this.boundsThickness / 2,
        y: gameHeight / 2,
        width: this.boundsThickness,
        height: gameHeight,
      },
    };
    if (this.engine && !this.borders) {
      const bottom = Matter.Bodies.rectangle(
        bounds.bottom.x,
        bounds.bottom.y,
        bounds.bottom.width,
        bounds.bottom.height,
        { isStatic: true, isHidden: true }
      );
      Matter.Composite.add(this.engine.world, bottom);
      const top = Matter.Bodies.rectangle(
        bounds.top.x,
        bounds.top.y,
        bounds.top.width,
        bounds.top.height,
        { isStatic: true, isHidden: true }
      );
      Matter.Composite.add(this.engine.world, top);
      const right = Matter.Bodies.rectangle(
        bounds.right.x,
        bounds.right.y,
        bounds.right.width,
        bounds.right.height,
        { isStatic: true, isHidden: true }
      );
      Matter.Composite.add(this.engine.world, right);
      const left = Matter.Bodies.rectangle(
        bounds.left.x,
        bounds.left.y,
        bounds.left.width,
        bounds.left.height,
        { isStatic: true, isHidden: true }
      );
      Matter.Composite.add(this.engine.world, left);
      this.borders = {
        top: top,
        bottom: bottom,
        left: left,
        right: right,
        width: gameWidth,
        height: gameHeight,
      };
    } else if (this.borders) {
      Matter.Body.scale(this.borders.bottom, gameWidth / this.borders.width, 1);
      Matter.Body.setPosition(this.borders.bottom, {
        x: bounds.bottom.x,
        y: bounds.bottom.y,
      });
      Matter.Body.scale(this.borders.top, gameWidth / this.borders.width, 1);
      Matter.Body.setPosition(this.borders.top, {
        x: bounds.top.x,
        y: bounds.top.y,
      });
      Matter.Body.scale(
        this.borders.right,
        1,
        gameHeight / this.borders.height
      );
      Matter.Body.setPosition(this.borders.right, {
        x: bounds.right.x,
        y: bounds.right.y,
      });
      Matter.Body.scale(this.borders.left, 1, gameHeight / this.borders.height);
      Matter.Body.setPosition(this.borders.left, {
        x: bounds.left.x,
        y: bounds.left.y,
      });
      this.borders.width = gameWidth;
      this.borders.height = gameHeight;
    }
  }

  setupMatter(): void {
    this.engine = Matter.Engine.create();
    this.$emit('initEngine', this.engine);
    if (this.useGravity) {
      this.engine.gravity = {
        x: 0,
        y: 1,
        scale: 0.0005,
      };
    } else {
      this.engine.gravity = {
        x: 0,
        y: 1,
        scale: 0,
      };
    }
    this.runner = Matter.Runner.create();
    this.detector = Matter.Detector.create({ bodies: [] });
    this.$emit('initDetector', this.detector);
    Matter.Runner.run(this.runner, this.engine);
  }

  lookForCollision(): void {
    const collisions = Matter.Detector.collisions(this.detector);
    if (collisions.length > 0) {
      const validCollision = this.collisionsFilter
        ? collisions.find((collision) => {
            if (this.collisionsFilter) return this.collisionsFilter(collision);
            return true;
          })
        : collisions;
      if (validCollision) {
        const gameObjectA = this.gameObjects.find(
          (obj) => obj.body.id === validCollision.bodyA.id
        );
        const gameObjectB = this.gameObjects.find(
          (obj) => obj.body.id === validCollision.bodyB.id
        );
        if (gameObjectA) gameObjectA.handleCollision();
        if (gameObjectB) gameObjectB.handleCollision();
      }
    }
  }

  syncRenderView(): void {
    for (const gameObject of this.gameObjects) {
      gameObject.syncronize();
    }
  }

  @Watch('useBorders', { immediate: true })
  onUseBordersChanged(): void {
    if (this.borders) {
      if (!this.useBorders) {
        Matter.Composite.remove(this.engine.world, this.borders.bottom);
        Matter.Composite.remove(this.engine.world, this.borders.top);
        Matter.Composite.remove(this.engine.world, this.borders.right);
        Matter.Composite.remove(this.engine.world, this.borders.left);
      } else if (!this.engine.world.bodies.includes(this.borders.bottom)) {
        Matter.Composite.add(this.engine.world, this.borders.bottom);
        Matter.Composite.add(this.engine.world, this.borders.top);
        Matter.Composite.add(this.engine.world, this.borders.right);
        Matter.Composite.add(this.engine.world, this.borders.left);
      }
    }
  }
}
</script>

<style scoped lang="scss"></style>
