<template>
  <div
    ref="gameContainer"
    id="gameContainer"
    class="gameContainer"
    :style="{
      '--game-width': `${gameWidth}px`,
      '--game-height': `${gameHeight}px`,
    }"
  >
    <Application
      ref="pixi"
      id="pixiContainer"
      :width="gameWidth"
      :height="gameDisplayHeight"
      v-if="ready"
      :backgroundColor="backgroundColor"
      :backgroundAlpha="backgroundAlpha"
      :transparent="transparent"
    >
      <container>
        <slot :itemProps="{ engine: engine, detector: detector }"></slot>
      </container>
    </Application>
    <div class="frameInfo">{{ Math.round(1000 / frameDelta) }}fps</div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as Matter from 'matter-js/build/matter';
import { Application } from 'vue3-pixi';
import { EventType } from '@/types/enum/EventType';
import GameObject from '@/components/shared/atoms/game/GameObjectLite2.vue';
import * as PIXI from 'pixi.js';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import * as pixiUtil from '@/utils/pixi';
import { delay, until } from '@/utils/wait';
import { registerDomElement, unregisterDomElement } from '@/vunit';
import * as matterUtil from '@/utils/matter';

/* eslint-disable @typescript-eslint/no-explicit-any*/
interface CollisionBounds {
  top: Matter.Body;
  bottom: Matter.Body;
  left: Matter.Body;
  right: Matter.Body;
  topPosition: [number, number];
  bottomPosition: [number, number];
  leftPosition: [number, number];
  rightPosition: [number, number];
  width: number;
  height: number;
}

export interface BodyData {
  id: number;
  position: { x: number; y: number };
  size: number;
  text: string;
  body: Matter.Body | null;
}

const logStartCalls = false;
const logEndCalls = false;

@Options({
  components: {
    FontAwesomeIcon,
    Application,
  },
  emits: [
    'initEngine',
    'initRenderer',
    'update:width',
    'update:height',
    'click',
    'gameObjectClick',
    'update:selectedObject',
    'containerReady',
    'sizeReady',
  ],
})
export default class GameContainer extends Vue {
  //#region props
  @Prop({ default: true }) readonly hasMouseInput!: boolean;
  @Prop({ default: true }) readonly useGravity!: boolean;
  @Prop({ default: [0, 1, 0] }) readonly gravity!: [number, number, number];
  @Prop({ default: 0 }) readonly windForce!: number;
  @Prop({ default: 0 }) readonly borderDelta!: number;
  @Prop({ default: undefined }) readonly width!: number | undefined;
  @Prop({ default: undefined }) readonly height!: number | undefined;
  @Prop({ default: [0, 0] }) readonly offset!: [number, number];
  @Prop({ default: '#f4f4f4' }) readonly backgroundColor!: string;
  @Prop({ default: 1 }) readonly backgroundAlpha!: number;
  @Prop({ default: false }) readonly transparent!: boolean;
  @Prop({ default: null }) readonly selectedObject!: GameObject | null;
  //#endregion props

  //#region variables
  ready = false;
  gameWidth = 0;
  gameHeight = 0;
  textureToken = pixiUtil.createLoadingToken();

  engine!: typeof Matter.Engine;
  runner!: typeof Matter.Runner;
  detector: typeof Matter.Detector | null = null;
  mouseConstraint!: typeof Matter.MouseConstraint;
  hierarchyObserver!: MutationObserver;
  app: PIXI.Application | null = null;

  gameObjects: GameObject[] = [];
  loading = false;
  gameObjectBody: { [key: string]: Matter.Body } = {};
  //#endregion variables

  //#region get
  get gameDisplayHeight(): number {
    return this.gameHeight;
  }

  get boundsWidth(): number {
    return this.gameWidth ? this.gameWidth : 100;
  }

  get boundsHeight(): number {
    return this.gameDisplayHeight ? this.gameDisplayHeight : 100;
  }

  hasBody(gameObject: GameObject): boolean {
    return !!this.gameObjectBody[gameObject.uuid];
  }

  getBody(gameObject: GameObject): Matter.Body {
    return this.gameObjectBody[gameObject.uuid];
  }
  //#endregion get

  //#region load / unload
  domKey = '';
  async mounted(): Promise<void> {
    if (logStartCalls) console.log('mounted');
    this.eventBus.on(EventType.REGISTER_GAME_OBJECT, this.registerGameObject);

    //initialise observer in mounted as otherwise this references observer
    this.hierarchyObserver = new MutationObserver(this.hierarchyChanged);

    this.setupMatter();
    if (this.hasMouseInput) {
      this.hierarchyObserver.observe(this.$refs.gameContainer as HTMLElement, {
        childList: true,
        subtree: false,
      });
    }

    until(() => this.$refs.pixi).then(async () => {
      await delay(100);
      const pixi = this.$refs.pixi as typeof Application;
      if (pixi) {
        this.app = pixi.app;
        pixi.app.transparent = this.transparent;
        this.$emit('initRenderer', pixi.app.renderer);
      }
    });

    this.domKey = registerDomElement(
      this.$refs.gameContainer as HTMLElement,
      this.setupPixiSpace,
      0
    );
    if (logEndCalls) console.log('mounted');
  }

  hierarchyChanged(mutationList: MutationRecord[]): void {
    if (logStartCalls) console.log('hierarchyChanged');
    const addedNodes: Node[] = [];
    for (const mutation of mutationList) {
      if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
        addedNodes.push(...Array.from(mutation.addedNodes));
      }
    }
    const canvasList = addedNodes.filter(
      (node) => node.nodeName.toLowerCase() === 'canvas'
    ) as HTMLCanvasElement[];
    if (canvasList.length) {
      this.setupMouseConstraint(canvasList[canvasList.length - 1]);
    }
    if (logEndCalls) console.log('hierarchyChanged');
  }

  unmounted(): void {
    if (logStartCalls) console.log('unmounted');
    this.hierarchyObserver.disconnect();
    this.eventBus.off(EventType.REGISTER_GAME_OBJECT, this.registerGameObject);
    pixiUtil.cleanupToken(this.textureToken);
    Matter.Events.off(this.engine, 'afterUpdate', this.afterPhysicUpdate);
    Matter.Events.off(this.engine, 'beforeUpdate', this.beforePhysicUpdate);
    Matter.Events.off(this.engine.world, 'afterAdd', this.bodyAdded);

    unregisterDomElement(this.domKey);
    if (logEndCalls) console.log('unmounted');
  }

  async setupPixiSpace(width: number, height: number): Promise<void> {
    if (logStartCalls) console.log('setupPixiSpace');
    const dom = this.$refs.gameContainer as HTMLElement;
    if (dom && (width !== this.gameWidth || height !== this.gameHeight)) {
      this.gameWidth = width;
      this.gameHeight = height;
      this.$emit('update:width', this.gameWidth);
      this.$emit('update:height', this.gameHeight);
      this.$emit('update:height', this.gameDisplayHeight);
      this.$emit('update:height', this.gameDisplayHeight);
      await this.setupBound();
      this.ready = true;
      this.$emit('sizeReady');
    }
    if (logEndCalls) console.log('setupPixiSpace');
  }

  setupMouseConstraint(canvas: HTMLCanvasElement | undefined): void {
    if (logStartCalls) console.log('setupMouseConstraint');
    if (this.engine && canvas) {
      // add mouse control
      const mouse = Matter.Mouse.create(canvas);
      this.mouseConstraint = Matter.MouseConstraint.create(this.engine, {
        mouse: mouse,
        constraint: {
          stiffness: 0.2,
        },
      });
      this.addToEngin(this.mouseConstraint);
    }
    if (logEndCalls) console.log('setupMouseConstraint');
  }
  //#endregion load / unload

  //#region register object
  registerGameObject(data: any): void {
    if (logStartCalls) console.log('registerGameObject');
    const gameObject = data.gameObject as GameObject;
    this.gameObjects.push(gameObject);
    this.setupGameObjectBody(gameObject);
    gameObject.setGameContainer(this);
    if (logEndCalls) console.log('registerGameObject');
  }

  deregisterGameObject(gameObject: GameObject): void {
    if (logStartCalls) console.log('deregisterGameObject');
    if (this.hasBody(gameObject) && this.engine && gameObject.isPartOfEngin) {
      gameObject.isPartOfEngin = false;
      this.removeFromEngin(this.getBody(gameObject));
    }
    const index = this.gameObjects.findIndex((obj) => obj === gameObject);
    if (index > -1) {
      this.gameObjects.splice(index, 1);
    }
    if (logEndCalls) console.log('deregisterGameObject');
  }
  //#endregion register object

  //#region game object bodies
  setupGameObjectBody(gameObject: GameObject): void {
    if (!gameObject.containerPosition) return;
    let body: Matter.Body | null = null;
    switch (gameObject.type) {
      case 'rect':
        body = this.addRect(gameObject);
        break;
      case 'circle':
        body = this.addCircle(gameObject);
        break;
      case 'polygon':
        body = this.addPolygon(gameObject);
        break;
    }
    if (body) {
      (body as any).zIndex = gameObject.zIndex;
      gameObject.setupBodyId(body.id);
      this.gameObjectBody[gameObject.uuid] = body;
      gameObject.isPartOfEngin = true;
      this.addToEngin(body);
    }
  }

  addRect(gameObject: GameObject): Matter.Body {
    if (gameObject.containerPosition) {
      gameObject.options.isStatic = gameObject.isStatic;
      const colliderWidth =
        gameObject.displayWidth + gameObject.colliderDelta * 2;
      const colliderHeight =
        gameObject.displayHeight + gameObject.colliderDelta * 2;
      return Matter.Bodies.rectangle(
        gameObject.containerPosition.x,
        gameObject.containerPosition.y,
        colliderWidth,
        colliderHeight,
        { ...gameObject.options }
      );
    }
  }

  addCircle(gameObject: GameObject): Matter.Body {
    if (gameObject.containerPosition) {
      gameObject.options.isStatic = gameObject.isStatic;
      const width = gameObject.displayWidth;
      const height = gameObject.displayHeight;
      const radius =
        (width > height ? width / 2 : height / 2) + gameObject.colliderDelta;
      return Matter.Bodies.circle(
        gameObject.containerPosition.x,
        gameObject.containerPosition.y,
        radius,
        { ...gameObject.options }
      );
    }
  }

  addPolygon(gameObject: GameObject): Matter.Body {
    if (gameObject.containerPosition) {
      gameObject.options.isStatic = gameObject.isStatic;
      return matterUtil.createPolygonBody(
        { ...gameObject.options },
        gameObject.containerPosition.x,
        gameObject.containerPosition.y,
        gameObject.displayWidth,
        gameObject.displayHeight,
        [...gameObject.polygonShape]
      );
    }
  }

  syncGameObjectBodyData(gameObject: GameObject): void {
    if (this.hasBody(gameObject)) {
      const body = this.getBody(gameObject);
      gameObject.position = [body.position.x, body.position.y];
    }
  }
  //#endregion game object bodies

  //#region force
  addWind(): void {
    if (logStartCalls) console.log('addWind');
    const calcForce = (body: Matter.Body): number => {
      const windForce = this.windForce > 0 ? this.windForce : 1;
      const forceMagnitude = (0.05 + body.frictionAir) * windForce; // (0.05 * body.mass) * timeScale;
      return (
        (forceMagnitude + Matter.Common.random() * forceMagnitude) *
        Matter.Common.choose([1, -1])
      );
    };

    for (const body of this.engine.world.bodies) {
      if (body && !body.isStatic && !body.isSleeping) {
        Matter.Body.setVelocity(body, {
          x: body.velocity.x + calcForce(body),
          y: body.velocity.y + calcForce(body),
        });
      }
    }
    if (logEndCalls) console.log('addWind');
  }
  //#endregion force

  //#region bounds
  bordersScreen: undefined | CollisionBounds = undefined;

  setBordersForType(borders: CollisionBounds): void {
    //if (logStartCalls) console.log('setBordersForType');
    this.bordersScreen = borders;
  }

  readonly boundsThickness = 100;
  async setupCollisionBound(borderCategory = 1): Promise<void> {
    if (logStartCalls) console.log('setupCollisionBound');
    const gameWidth = this.gameWidth ? this.gameWidth : 100;
    const gameHeight = this.gameDisplayHeight ? this.gameDisplayHeight : 100;
    const boundsWidth = gameWidth;
    const boundsHeight = gameHeight;
    const screenCenterX = gameWidth / 2;
    const screenCenterY = gameHeight / 2;
    const xLeft =
      -boundsWidth / 2 - this.boundsThickness / 2 + this.borderDelta;
    const xRight =
      boundsWidth / 2 + this.boundsThickness / 2 - this.borderDelta;
    const xCenter = 0;
    const yTop =
      -boundsHeight / 2 - this.boundsThickness / 2 + this.borderDelta;
    const yBottom =
      boundsHeight / 2 + this.boundsThickness / 2 - this.borderDelta;
    const yCenter = 0;
    const bounds = {
      bottom: {
        x: xCenter,
        y: yBottom,
        width: boundsWidth,
        height: this.boundsThickness,
      },
      top: {
        x: xCenter,
        y: yTop,
        width: boundsWidth,
        height: this.boundsThickness,
      },
      left: {
        x: xLeft,
        y: yCenter,
        width: this.boundsThickness,
        height: boundsHeight,
      },
      right: {
        x: xRight,
        y: yCenter,
        width: this.boundsThickness,
        height: boundsHeight,
      },
    };
    if (this.engine && !this.bordersScreen) {
      const bottom = Matter.Bodies.rectangle(
        bounds.bottom.x + screenCenterX,
        bounds.bottom.y + screenCenterY,
        bounds.bottom.width,
        bounds.bottom.height,
        {
          isStatic: true,
          isHidden: true,
          collisionFilter: { group: 0b0001, category: borderCategory },
        }
      );
      this.addToEngin(bottom);
      const top = Matter.Bodies.rectangle(
        bounds.top.x + screenCenterX,
        bounds.top.y + screenCenterY,
        bounds.top.width,
        bounds.top.height,
        {
          isStatic: true,
          isHidden: true,
          collisionFilter: { group: 0b0001, category: borderCategory },
        }
      );
      this.addToEngin(top);
      const right = Matter.Bodies.rectangle(
        bounds.right.x + screenCenterX,
        bounds.right.y + screenCenterY,
        bounds.right.width,
        bounds.right.height,
        {
          isStatic: true,
          isHidden: true,
          collisionFilter: { group: 0b0001, category: borderCategory },
        }
      );
      this.addToEngin(right);
      const left = Matter.Bodies.rectangle(
        bounds.left.x + screenCenterX,
        bounds.left.y + screenCenterY,
        bounds.left.width,
        bounds.left.height,
        {
          isStatic: true,
          isHidden: true,
          collisionFilter: { group: 0b0001, category: borderCategory },
        }
      );
      this.addToEngin(left);
      this.bordersScreen = {
        top: top,
        bottom: bottom,
        left: left,
        right: right,
        topPosition: [bounds.top.x, bounds.top.y],
        bottomPosition: [bounds.bottom.x, bounds.bottom.y],
        leftPosition: [bounds.left.x, bounds.left.y],
        rightPosition: [bounds.right.x, bounds.right.y],
        width: boundsWidth,
        height: boundsHeight,
      };
    } else if (this.bordersScreen) {
      Matter.Body.scale(
        this.bordersScreen.bottom,
        boundsWidth / this.bordersScreen.width,
        1
      );
      Matter.Body.setPosition(this.bordersScreen.bottom, {
        x: bounds.bottom.x + screenCenterX,
        y: bounds.bottom.y + screenCenterY,
      });
      Matter.Body.scale(
        this.bordersScreen.top,
        boundsWidth / this.bordersScreen.width,
        1
      );
      Matter.Body.setPosition(this.bordersScreen.top, {
        x: bounds.top.x + screenCenterX,
        y: bounds.top.y + screenCenterY,
      });
      Matter.Body.scale(
        this.bordersScreen.right,
        1,
        boundsHeight / this.bordersScreen.height
      );
      Matter.Body.setPosition(this.bordersScreen.right, {
        x: bounds.right.x + screenCenterX,
        y: bounds.right.y + screenCenterY,
      });
      Matter.Body.scale(
        this.bordersScreen.left,
        1,
        boundsHeight / this.bordersScreen.height
      );
      Matter.Body.setPosition(this.bordersScreen.left, {
        x: bounds.left.x + screenCenterX,
        y: bounds.left.y + screenCenterY,
      });
      this.bordersScreen.width = boundsWidth;
      this.bordersScreen.height = boundsHeight;
      this.bordersScreen.topPosition = [bounds.top.x, bounds.top.y];
      this.bordersScreen.bottomPosition = [bounds.bottom.x, bounds.bottom.y];
      this.bordersScreen.leftPosition = [bounds.left.x, bounds.left.y];
      this.bordersScreen.rightPosition = [bounds.right.x, bounds.right.y];
    }
    if (logEndCalls) console.log('setupCollisionBound');
  }

  async setupBound(): Promise<void> {
    if (logStartCalls) console.log('setupBound');
    await this.setupCollisionBound();
    if (logEndCalls) console.log('setupBound');
  }
  //#endregion bounds

  //#region matterjs
  setupMatter(): void {
    if (logStartCalls) console.log('setupMatter');
    this.engine = Matter.Engine.create();
    Matter.Events.on(this.engine, 'afterUpdate', this.afterPhysicUpdate);
    Matter.Events.on(this.engine, 'beforeUpdate', this.beforePhysicUpdate);
    Matter.Events.on(this.engine.world, 'afterAdd', this.bodyAdded);
    this.$emit('initEngine', this.engine);
    this.setGravity();
    this.runner = Matter.Runner.create();
    /*this.runner = Matter.Runner.create({
      deltaMax: 1000 / 15,
      deltaMin: 1000 / 90,
    });*/
    Matter.Runner.run(this.runner, this.engine);
    if (logEndCalls) console.log('setupMatter');
  }

  readonly defaultGravityScale = 0.0005;
  @Watch('gravity', { immediate: true })
  setGravity(): void {
    if (logStartCalls) console.log('start setGravity', [...this.gravity]);
    if (this.engine) {
      const scaleFactor = 5;
      this.engine.gravity = {
        x: this.gravity[0] * scaleFactor,
        y: this.gravity[1] * scaleFactor,
        scale: this.useGravity
          ? this.defaultGravityScale * (1 - this.gravity[2]) * scaleFactor
          : 0,
      };
    }
    if (logEndCalls) console.log('end setGravity', [...this.gravity]);
  }

  addToEngin(
    physicObject:
      | Matter.Body
      | Matter.Composite
      | Matter.Constraint
      | Matter.MouseConstraint
      | (
          | Matter.Body
          | Matter.Composite
          | Matter.Constraint
          | Matter.MouseConstraint
        )[]
  ): void {
    if (logStartCalls) console.log('addToEngin');
    if (
      this.engine &&
      !this.engine.world.bodies.find((item) => item.id === physicObject.id)
    ) {
      Matter.Composite.add(this.engine.world, physicObject);
    }
    if (logEndCalls) console.log('addToEngin');
  }

  bodyAdded(): void {
    if (logStartCalls) console.log('bodyAdded');
    this.engine.world.bodies.sort((a, b) => a.zIndex - b.zIndex);
    if (logEndCalls) console.log('bodyAdded');
  }

  removeFromEngin(
    physicObject:
      | Matter.Body
      | Matter.Composite
      | Matter.Constraint
      | Matter.MouseConstraint
      | (
          | Matter.Body
          | Matter.Composite
          | Matter.Constraint
          | Matter.MouseConstraint
        )[]
  ): void {
    if (logStartCalls) console.log('removeFromEngin');
    try {
      if (this.engine) {
        const body = this.engine.world.bodies.find(
          (item) => item.id === physicObject.id
        );
        if (body) Matter.Composite.remove(this.engine.world, body);
        else Matter.Composite.remove(this.engine.world, physicObject);
      }
    } catch (e) {
      //
    }
    if (logEndCalls) console.log('removeFromEngin');
  }

  /*addGameObjectToEngin(gameObject: GameObject): void {
    if (logStartCalls) console.log('addGameObjectToEngin');
    if (this.hasBody(gameObject) && this.engine && !gameObject.isPartOfEngin) {
      gameObject.isPartOfEngin = true;
      this.addToEngin(this.getBody(gameObject));
    }
    if (logEndCalls) console.log('addGameObjectToEngin');
  }

  removeGameObjectFromEngin(gameObject: GameObject): void {
    if (logStartCalls) console.log('removeGameObjectFromEngin');
    if (this.hasBody(gameObject) && this.engine && gameObject.isPartOfEngin) {
      gameObject.isPartOfEngin = false;
      this.removeFromEngin(this.getBody(gameObject));
    }
    if (logEndCalls) console.log('removeGameObjectFromEngin');
  }*/
  //#endregion matterjs

  //#region loop
  loopTime = 0;
  updateTime = Date.now();
  updateTime2 = Date.now();
  afterPhysicUpdate(): void {
    if (logStartCalls) console.log('afterPhysicUpdate');
    const updateTime = Date.now();
    const deltaTime = updateTime - this.updateTime2;
    this.updateTime2 = updateTime;
    for (const gameObject of this.gameObjects) {
      this.syncGameObjectBodyData(gameObject);
    }
    if (logEndCalls)
      console.log(
        'afterPhysicUpdate',
        deltaTime,
        Date.now() - updateTime,
        this.engine.world.bodies.length
      );
  }

  nextWindUpdateTime = 0;
  readonly oneTickDelta = 50;
  frameDelta = 0;
  frameDeltaList: number[] = [];
  beforePhysicUpdate(): void {
    if (logStartCalls) console.log('beforePhysicUpdate');
    const updateTime = Date.now();
    const deltaTime = updateTime - this.updateTime;
    this.updateTime = updateTime;
    this.loopTime += deltaTime;
    this.frameDeltaList.splice(0, 0, deltaTime);
    if (this.frameDeltaList.length > 10) this.frameDeltaList.length = 10;
    this.frameDelta =
      this.frameDeltaList.reduce((sum, a) => sum + a, 0) /
      this.frameDeltaList.length;

    if (this.windForce > 0 && this.nextWindUpdateTime < this.loopTime) {
      this.nextWindUpdateTime += this.oneTickDelta * 5;
      this.addWind();
    }
    //if (logEndCalls) console.log('beforePhysicUpdate', deltaTime, Date.now() - updateTime);
  }
  //#endregion loop
}
</script>

<style scoped lang="scss">
.gameContainer {
  position: relative;
}

.navigation-overlay {
  font-size: var(--font-size-xxxlarge);
  position: absolute;
  z-index: 100;
}
.overlay-right {
  right: 1rem;
  top: calc(var(--game-height) / 2);
}
.overlay-left {
  left: 1rem;
  top: calc(var(--game-height) / 2);
}
.overlay-up {
  top: 1rem;
  left: calc(var(--game-width) / 2);
}
.overlay-down {
  bottom: 1rem;
  left: calc(var(--game-width) / 2);
}

.frameInfo {
  pointer-events: none;
  bottom: 1rem;
  left: 1rem;
  font-size: var(--font-size-xxxlarge);
  position: absolute;
  z-index: 100;
}
</style>
