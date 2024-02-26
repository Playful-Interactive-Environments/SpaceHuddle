<template>
  <div
    ref="gameContainer"
    id="gameContainer"
    class="gameContainer"
    :style="{
      '--game-width': `${gameWidth}px`,
      '--game-height': `${gameHeight}px`,
    }"
    v-loading="!isContainerReady"
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
      @pointerdown="gameContainerClicked"
      @pointerup="gameContainerReleased"
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
import GameObject from '@/components/shared/atoms/game/GameObjectLite.vue';
import * as PIXI from 'pixi.js';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import * as pixiUtil from '@/utils/pixi';
import * as matterUtil from '@/utils/matter';
import { delay, until } from '@/utils/wait';
import { registerDomElement, unregisterDomElement } from '@/vunit';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export interface CollisionRegion {
  path: [number, number][];
  source: any;
  options: {
    [key: string]: string | number | boolean | object;
  };
  filter: any[];
  color: string;
  alpha: number;
  text: string;
}

interface CollisionRegionData {
  region: CollisionRegion;
  position: [number, number];
  relativePosition: [number, number];
  size: [number, number];
  body: Matter.Body | null;
  graphic: PIXI.Graphics | null;
  anchor: number;
}

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
  @Prop({ default: true }) readonly detectCollision!: boolean;
  @Prop({ default: true }) readonly useGravity!: boolean;
  @Prop({ default: [0, 1, 0] }) readonly gravity!: [number, number, number];
  @Prop({ default: 0 }) readonly windForce!: number;
  @Prop({ default: 0 }) readonly borderDelta!: number;
  @Prop({ default: 1 }) readonly borderCategory!: number;
  @Prop({ default: undefined }) readonly width!: number | undefined;
  @Prop({ default: undefined }) readonly height!: number | undefined;
  @Prop({ default: [0, 0] }) readonly offset!: [number, number];
  @Prop({ default: '#f4f4f4' }) readonly backgroundColor!: string;
  @Prop({ default: 1 }) readonly backgroundAlpha!: number;
  @Prop({ default: false }) readonly transparent!: boolean;
  @Prop({ default: null }) readonly selectedObject!: GameObject | null;
  @Prop({ default: false }) readonly waitForDataLoad!: boolean;
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
  regionBodyList: CollisionRegionData[] = [];

  gameObjects: GameObject[] = [];
  activeObject: GameObject | null = null;
  loading = false;
  //#endregion variables

  //#region get
  get gameDisplayHeight(): number {
    return this.gameHeight;
  }

  getGameObjectForBody(body: Matter.Body): GameObject | null {
    if (logStartCalls) console.log('getGameObjectForBody');
    if (body) {
      const obj = this.gameObjects.find(
        (obj) => obj.body && obj.body.id === body.id
      );
      if (obj) return obj;
    }
    return null;
  }

  getCollisionRegionForBody(body: Matter.Body): CollisionRegion | null {
    if (logStartCalls) console.log('getCollisionRegionForBody');
    if (body) {
      const obj = this.regionBodyList.find(
        (obj) => obj.body && obj.body.id === body.id
      );
      if (obj) return obj.region;
    }
    return null;
  }

  get isContainerReady(): boolean {
    //if (logStartCalls) console.log('isContainerReady');
    return this.ready && !this.loading && !this.waitForDataLoad;
  }

  get boundsWidth(): number {
    return this.gameWidth ? this.gameWidth : 100;
  }

  get boundsHeight(): number {
    return this.gameDisplayHeight ? this.gameDisplayHeight : 100;
  }
  //#endregion get

  //#region watch
  @Watch('waitForDataLoad', { immediate: true })
  onWaitForDataLoadChanged(): void {
    //if (logStartCalls) console.log('onWaitForDataLoadChanged');
    if (this.isContainerReady) this.$emit('containerReady');
  }

  @Watch('borderCategory', { immediate: true })
  onBorderCategoryChanged(): void {
    if (logStartCalls) console.log('onBorderCategoryChanged');
    if (this.borders) {
      this.borders.bottom.collisionFilter.category = this.borderCategory;
      this.borders.top.collisionFilter.category = this.borderCategory;
      this.borders.right.collisionFilter.category = this.borderCategory;
      this.borders.left.collisionFilter.category = this.borderCategory;
    }
  }
  //#endregion watch

  //#region load / unload
  private async allTexturesLoaded(): Promise<void> {
    //if (logStartCalls) console.log('allTexturesLoaded');
    this.loading = false;
    if (this.isContainerReady) this.$emit('containerReady');
  }

  private async texturesLoadingStart(): Promise<void> {
    //if (logStartCalls) console.log('texturesLoadingStart');
    this.loading = true;
  }

  domKey = '';
  async mounted(): Promise<void> {
    this.eventBus.on(
      EventType.TEXTURES_LOADING_START,
      this.texturesLoadingStart
    );
    this.eventBus.on(EventType.ALL_TEXTURES_LOADED, this.allTexturesLoaded);
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
      return;
    }
    if (logEndCalls) console.log('hierarchyChanged');
  }

  unmounted(): void {
    if (logStartCalls) console.log('unmounted');
    this.hierarchyObserver.disconnect();
    this.eventBus.off(EventType.REGISTER_GAME_OBJECT, this.registerGameObject);
    pixiUtil.cleanupToken(this.textureToken);
    Matter.Events.off(this.engine, 'collisionStart', this.collisionStart);
    Matter.Events.off(this.engine, 'afterUpdate', this.afterPhysicUpdate);
    Matter.Events.off(this.engine, 'beforeUpdate', this.beforePhysicUpdate);
    Matter.Events.off(this.engine.world, 'afterAdd', this.bodyAdded);
    this.eventBus.off(
      EventType.TEXTURES_LOADING_START,
      this.texturesLoadingStart
    );
    this.eventBus.off(EventType.ALL_TEXTURES_LOADED, this.allTexturesLoaded);

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
      if (this.isContainerReady) this.$emit('containerReady');
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
    if (gameObject.body && this.engine && gameObject.isPartOfEngin) {
      gameObject.isPartOfEngin = false;
      this.removeFromEngin(gameObject.body);
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
      gameObject.body = body;
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
    if (gameObject.body) {
      const body = gameObject.body;
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

    for (const gameObject of this.gameObjects) {
      if (
        gameObject.body &&
        gameObject.affectedByForce &&
        !gameObject.isStatic &&
        gameObject.isVisible()
      ) {
        Matter.Body.setVelocity(gameObject.body, {
          x: gameObject.body.velocity.x + calcForce(gameObject.body),
          y: gameObject.body.velocity.y + calcForce(gameObject.body),
        });
      }
    }
    if (logEndCalls) console.log('addWind');
  }
  //#endregion force

  //#region events
  readonly minClickTimeDelta = 10;
  isMouseDown = false;
  gameContainerClicked(event: any): void {
    if (logStartCalls) console.log('gameContainerClicked');
    const mousePosition = { x: event.offsetX, y: event.offsetY };
    const clickedBodies = Matter.Query.point(
      this.gameObjects
        .filter((gameObj) => gameObj.body)
        .map((gameObj) => gameObj.body),
      mousePosition
    );
    if (clickedBodies.length > 0) {
      const clickedGameObjects = clickedBodies.map((body) => {
        return this.getGameObjectForBody(body);
      });
      for (const obj of clickedGameObjects) {
        obj.gameObjectClicked();
      }
      this.$emit('gameObjectClick', clickedGameObjects, event);
    } else {
      this.$emit('update:selectedObject', null);
    }
    this.isMouseDown = true;
    setTimeout(() => {
      if (!this.activeObject) {
        this.$emit('click', {
          mousePosition: mousePosition,
        });
      }
    }, this.minClickTimeDelta);
  }

  gameContainerReleased(): void {
    if (logStartCalls) console.log('gameContainerReleased');
    this.isMouseDown = false;
    if (this.activeObject) {
      this.activeObject.gameObjectReleased();
    }
  }
  //#endregion events

  //#region bounds
  bordersScreen: undefined | CollisionBounds = undefined;
  getBordersForType(): CollisionBounds | undefined {
    //if (logStartCalls) console.log('getBordersForType');
    return this.bordersScreen;
  }

  setBordersForType(borders: CollisionBounds): void {
    //if (logStartCalls) console.log('setBordersForType');
    this.bordersScreen = borders;
  }

  readonly boundsThickness = 100;
  async setupCollisionBound(
    borderCategory: number
  ): Promise<CollisionBounds | undefined> {
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
    const containerBorders = this.getBordersForType();
    if (this.engine && !containerBorders) {
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
      const borders: CollisionBounds = {
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
      this.setBordersForType(borders);
      return borders;
    } else if (containerBorders) {
      Matter.Body.scale(
        containerBorders.bottom,
        boundsWidth / containerBorders.width,
        1
      );
      Matter.Body.setPosition(containerBorders.bottom, {
        x: bounds.bottom.x + screenCenterX,
        y: bounds.bottom.y + screenCenterY,
      });
      Matter.Body.scale(
        containerBorders.top,
        boundsWidth / containerBorders.width,
        1
      );
      Matter.Body.setPosition(containerBorders.top, {
        x: bounds.top.x + screenCenterX,
        y: bounds.top.y + screenCenterY,
      });
      Matter.Body.scale(
        containerBorders.right,
        1,
        boundsHeight / containerBorders.height
      );
      Matter.Body.setPosition(containerBorders.right, {
        x: bounds.right.x + screenCenterX,
        y: bounds.right.y + screenCenterY,
      });
      Matter.Body.scale(
        containerBorders.left,
        1,
        boundsHeight / containerBorders.height
      );
      Matter.Body.setPosition(containerBorders.left, {
        x: bounds.left.x + screenCenterX,
        y: bounds.left.y + screenCenterY,
      });
      containerBorders.width = boundsWidth;
      containerBorders.height = boundsHeight;
      containerBorders.topPosition = [bounds.top.x, bounds.top.y];
      containerBorders.bottomPosition = [bounds.bottom.x, bounds.bottom.y];
      containerBorders.leftPosition = [bounds.left.x, bounds.left.y];
      containerBorders.rightPosition = [bounds.right.x, bounds.right.y];
      return containerBorders;
    }
    if (logEndCalls) console.log('setupCollisionBound');
    return undefined;
  }

  borders: undefined | CollisionBounds = undefined;
  async setupBound(): Promise<void> {
    if (logStartCalls) console.log('setupBound');
    this.borders = await this.setupCollisionBound(this.borderCategory);
  }
  //#endregion bounds

  //#region matterjs
  setupMatter(): void {
    if (logStartCalls) console.log('setupMatter');
    this.engine = Matter.Engine.create();
    if (this.detectCollision)
      Matter.Events.on(this.engine, 'collisionStart', this.collisionStart);
    Matter.Events.on(this.engine, 'afterUpdate', this.afterPhysicUpdate);
    Matter.Events.on(this.engine, 'beforeUpdate', this.beforePhysicUpdate);
    Matter.Events.on(this.engine.world, 'afterAdd', this.bodyAdded);
    this.$emit('initEngine', this.engine);
    this.setGravity();
    this.runner = Matter.Runner.create({
      deltaMax: 1000 / 15,
      deltaMin: 1000 / 90,
    });
    Matter.Runner.run(this.runner, this.engine);
    if (logEndCalls) console.log('setupMatter');
  }

  readonly defaultGravityScale = 0.0005;
  @Watch('gravity', { immediate: true })
  setGravity(): void {
    if (logStartCalls) console.log('setGravity');
    if (this.engine) {
      this.engine.gravity = {
        x: this.gravity[0],
        y: this.gravity[1],
        scale: this.useGravity
          ? this.defaultGravityScale * (1 - this.gravity[2])
          : 0,
      };
    }
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

  collisionStart(event: Matter.Event): void {
    if (logStartCalls) console.log('collisionStart');
    const collisions = [...event.pairs] as Matter.Collision[];
    this.notifyCollision(collisions);
  }

  notifyCollision(collisions: Matter.Collision[]): void {
    if (logStartCalls) console.log('notifyCollision');
    const handleCollision = (
      gameObject: GameObject,
      collisionObject: GameObject | CollisionRegion | null,
      hitPoint: [number, number],
      hitPointScreen: [number, number],
      objectBody: Matter.Body,
      collisionBody: Matter.Body
    ): void => {
      if (gameObject) gameObject.$emit('update:highlighted', false);
      gameObject.handleCollision(
        collisionObject,
        hitPoint,
        hitPointScreen,
        objectBody,
        collisionBody
      );
    };

    if (collisions.length > 0) {
      for (const validCollision of collisions) {
        const gameObjectA = this.getGameObjectForBody(validCollision.bodyA);
        const gameObjectB = this.getGameObjectForBody(validCollision.bodyB);
        const regionObjectA = this.getCollisionRegionForBody(
          validCollision.bodyA
        );
        const regionObjectB = this.getCollisionRegionForBody(
          validCollision.bodyB
        );
        if (gameObjectA) {
          handleCollision(
            gameObjectA,
            gameObjectB ?? regionObjectB,
            matterUtil.calculateHitPoint(
              validCollision.bodyB,
              validCollision.bodyA
            ),
            matterUtil.calculateVisibleHitPoint(
              validCollision.bodyB,
              validCollision.bodyA,
              this.gameWidth,
              this.gameDisplayHeight
            ),
            validCollision.bodyA,
            validCollision.bodyB
          );
        }
        if (gameObjectB) {
          handleCollision(
            gameObjectB,
            gameObjectA ?? regionObjectA,
            matterUtil.calculateHitPoint(
              validCollision.bodyA,
              validCollision.bodyB
            ),
            matterUtil.calculateVisibleHitPoint(
              validCollision.bodyA,
              validCollision.bodyB,
              this.gameWidth,
              this.gameDisplayHeight
            ),
            validCollision.bodyB,
            validCollision.bodyA
          );
        }
      }
    }
  }
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
      gameObject.afterPhysicUpdate();
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
  loopCount = 0;
  beforePhysicUpdate(): void {
    if (logStartCalls) console.log('beforePhysicUpdate');
    const updateTime = Date.now();
    const deltaTime = updateTime - this.updateTime;
    this.updateTime = updateTime;
    this.loopTime += deltaTime;
    this.loopCount++;
    for (const gameObject of this.gameObjects) {
      gameObject.beforePhysicUpdate();
    }
    this.frameDeltaList.splice(0, 0, deltaTime);
    if (this.frameDeltaList.length > 30) this.frameDeltaList.length = 30;
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
