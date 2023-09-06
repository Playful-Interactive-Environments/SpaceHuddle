<template>
  <div
    ref="gameContainer"
    id="gameContainer"
    class="gameContainer"
    :style="{
      '--game-width': `${gameWidth}px`,
      '--game-height': `${gameHeight}px`,
    }"
    @mouseup="endPan"
    @mouseout="endPan"
    @touchend="endPan"
  >
    <Application
      ref="pixi"
      :width="gameWidth"
      :height="gameHeight"
      v-if="ready"
      :backgroundColor="backgroundColor"
      :transparent="transparent"
      @pointerdown="gameContainerClicked"
      @pointerup="gameContainerReleased"
    >
      <container>
        <sprite
          v-if="backgroundSprite && backgroundSprite.valid"
          :texture="backgroundSprite"
          :anchor="0.5"
          :width="backgroundTextureSize[0]"
          :height="backgroundTextureSize[1]"
          :x="backgroundTexturePosition[0]"
          :y="backgroundTexturePosition[1]"
        ></sprite>
        <slot :itemProps="{ engine: engine, detector: detector }"></slot>
      </container>
    </Application>
    <div
      class="navigation-overlay overlay-right"
      v-if="
        backgroundMovement === BackgroundMovement.Pan &&
        backgroundPositionOffsetMin[0] < backgroundPositionOffset[0]
      "
    >
      <font-awesome-icon
        icon="circle-chevron-right"
        @mousedown="beginPan([-1, 0])"
        @touchstart="beginPan([-1, 0])"
      />
    </div>
    <div
      class="navigation-overlay overlay-left"
      v-if="
        backgroundMovement === BackgroundMovement.Pan &&
        backgroundPositionOffsetMax[0] > backgroundPositionOffset[0]
      "
    >
      <font-awesome-icon
        icon="circle-chevron-left"
        @mousedown="beginPan([1, 0])"
        @touchstart="beginPan([1, 0])"
      />
    </div>
    <div
      class="navigation-overlay overlay-up"
      v-if="
        backgroundMovement === BackgroundMovement.Pan &&
        backgroundPositionOffsetMax[1] > backgroundPositionOffset[1]
      "
    >
      <font-awesome-icon
        icon="circle-chevron-up"
        @mousedown="beginPan([0, 1])"
        @touchstart="beginPan([0, 1])"
      />
    </div>
    <div
      class="navigation-overlay overlay-down"
      v-if="
        backgroundMovement === BackgroundMovement.Pan &&
        backgroundPositionOffsetMin[1] < backgroundPositionOffset[1]
      "
    >
      <font-awesome-icon
        icon="circle-chevron-down"
        @mousedown="beginPan([0, -1])"
        @touchstart="beginPan([0, -1])"
      />
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as Matter from 'matter-js/build/matter';
import { Application } from 'vue3-pixi';
import { EventType } from '@/types/enum/EventType';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import { CustomObject } from '@/types/game/CustomObject';
import * as PIXI from 'pixi.js';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { ObjectSpace } from '@/types/enum/ObjectSpace';

export enum BackgroundPosition {
  Stretch = 'stretch',
  Contain = 'contain',
  Cover = 'cover',
  None = 'none',
}

export enum BackgroundMovement {
  None = 'none',
  Pan = 'pan',
  //Auto = 'auto',
}

@Options({
  computed: {
    BackgroundMovement() {
      return BackgroundMovement;
    },
  },
  components: {
    FontAwesomeIcon,
    Application,
  },
  emits: [
    'initEngine',
    'initDetector',
    'initRenderer',
    'update:width',
    'update:height',
    'click',
    'gameObjectClick',
    'update:selectedObject',
    'update:offset',
    'backgroundSizeChanged',
  ],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class GameContainer extends Vue {
  @Prop({ default: true }) readonly hasMouseInput!: boolean;
  @Prop({ default: true }) readonly detectCollision!: boolean;
  @Prop({ default: true }) readonly useGravity!: boolean;
  @Prop({ default: false }) readonly useWind!: boolean;
  @Prop({ default: true }) readonly useBorders!: boolean;
  @Prop({ default: false }) readonly activatedObjectOnRegister!: boolean;
  @Prop({ default: undefined }) readonly width!: number | undefined;
  @Prop({ default: undefined }) readonly height!: number | undefined;
  @Prop({ default: [0, 0] }) readonly offset!: [number, number];
  @Prop({ default: '#f4f4f4' }) readonly backgroundColor!: string;
  @Prop({ default: null }) readonly backgroundTexture!: string | null;
  @Prop({ default: BackgroundPosition.Cover })
  readonly backgroundPosition!: BackgroundPosition;
  @Prop({ default: BackgroundMovement.None })
  readonly backgroundMovement!: BackgroundMovement;
  @Prop({ default: false }) readonly transparent!: boolean;
  @Prop({ default: null }) readonly selectedObject!: GameObject | null;
  @Prop({
    default: undefined,
  })
  readonly collisionsFilter!:
    | ((collision: Matter.Collision) => boolean)
    | undefined;
  @Prop({ default: false }) readonly combinedActiveCollisionToChain!: boolean;
  ready = false;
  gameWidth = 0;
  gameHeight = 0;
  backgroundSprite: PIXI.Texture | null = null;

  canvasPosition: [number, number] = [0, 0];
  engine!: typeof Matter.Engine;
  runner!: typeof Matter.Runner;
  detector!: typeof Matter.Detector;
  mouseConstraint!: typeof Matter.MouseConstraint;
  hierarchyObserver!: MutationObserver;
  resizeObserver!: ResizeObserver;

  gameObjects: GameObject[] = [];
  customObjects: CustomObject[] = [];
  activeObject: GameObject | null = null;
  activeComposition: Matter.Composite = Matter.Composite.create();

  readonly intervalTimeWind = 50;
  intervalWind = -1;
  readonly intervalTimeCollision = 500;
  intervalCollision = -1;
  readonly intervalTimeSync = 100;
  intervalSync = -1;
  readonly intervalTimePan = 50;
  intervalPan = -1;

  backgroundPositionOffset: [number, number] = [0, 0];
  backgroundPositionOffsetMin: [number, number] = [0, 0];
  backgroundPositionOffsetMax: [number, number] = [0, 0];
  backgroundTextureSize: [number, number] = [100, 100];
  calculateBackgroundSize(): void {
    if (this.backgroundSprite && this.backgroundSprite.orig) {
      const textureWidth = this.backgroundSprite.orig.width;
      const textureHeight = this.backgroundSprite.orig.height;
      const scaleFactorWidth = textureWidth / this.gameWidth;
      const scaleFactorHeight = textureHeight / this.gameHeight;
      switch (this.backgroundPosition) {
        case BackgroundPosition.None:
          this.backgroundTextureSize = [textureWidth, textureHeight];
          break;
        case BackgroundPosition.Stretch:
          this.backgroundTextureSize = [this.gameWidth, this.gameHeight];
          break;
        case BackgroundPosition.Contain:
          if (scaleFactorWidth > scaleFactorHeight)
            this.backgroundTextureSize = [
              this.gameWidth,
              textureHeight / scaleFactorWidth,
            ];
          else
            this.backgroundTextureSize = [
              textureWidth / scaleFactorHeight,
              this.gameHeight,
            ];
          break;
        case BackgroundPosition.Cover:
          if (scaleFactorWidth > scaleFactorHeight) {
            this.backgroundTextureSize = [
              textureWidth / scaleFactorHeight,
              this.gameHeight,
            ];
          } else
            this.backgroundTextureSize = [
              this.gameWidth,
              textureHeight / scaleFactorWidth,
            ];
          break;
      }
    } else this.backgroundTextureSize = [this.gameWidth, this.gameHeight];
    this.backgroundPositionOffset = [this.gameWidth / 2, this.gameHeight / 2];
    this.backgroundPositionOffsetMin = [...this.backgroundPositionOffset];
    this.backgroundPositionOffsetMax = [...this.backgroundPositionOffset];
    const deltaX = this.backgroundTextureSize[0] - this.gameWidth;
    const deltaY = this.backgroundTextureSize[1] - this.gameHeight;
    if (deltaX > 0) {
      this.backgroundPositionOffsetMin[0] -= deltaX / 2;
      this.backgroundPositionOffsetMax[0] += deltaX / 2;
    }
    if (deltaY > 0) {
      this.backgroundPositionOffsetMin[1] -= deltaY / 2;
      this.backgroundPositionOffsetMax[1] += deltaY / 2;
    }
    this.$emit('backgroundSizeChanged');
    for (const customObject of this.customObjects) {
      customObject.calculateRelativePosition();
    }
    for (const gameObject of this.gameObjects) {
      if (gameObject.moveWithBackground) {
        gameObject.initPosition();
        if (gameObject.objectSpace === ObjectSpace.RelativeToBackground)
          gameObject.updateOffset(this.gameObjectOffsetRelativeToBackground);
        else if (gameObject.objectSpace === ObjectSpace.RelativeToScreen)
          gameObject.updateOffset(this.gameObjectOffsetRelativeToScreen);
      }
    }
  }

  getBackgroundAspect(): number {
    if (this.backgroundSprite && this.backgroundSprite.orig) {
      const textureWidth = this.backgroundSprite.orig.width;
      const textureHeight = this.backgroundSprite.orig.height;
      return textureWidth / textureHeight;
    }
    return this.gameWidth / this.gameHeight;
  }

  get backgroundTexturePosition(): [number, number] {
    return this.backgroundPositionOffset;
  }

  @Watch('activeObject', { immediate: true })
  onActiveObjectChanged(): void {
    if (this.combinedActiveCollisionToChain && this.activeComposition) {
      if (!this.activeObject) {
        for (const body of this.activeComposition.bodies) {
          const gameObject = this.getGameObjectForBody(body);
          if (gameObject) gameObject.$emit('update:highlighted', false);
          Matter.Composite.add(this.engine.world, body);
        }
        Matter.Composite.clear(this.activeComposition);
      } else {
        Matter.Composite.remove(this.engine.world, this.activeObject.body);
        Matter.Composite.add(this.activeComposition, this.activeObject.body);
        this.activeObject.$emit('update:highlighted', true);
      }
    }
  }

  @Watch('backgroundTexture', { immediate: true })
  onBackgroundTextureChanged(): void {
    const loadTexture = (): void => {
      if (this.backgroundTexture) {
        PIXI.Assets.load(this.backgroundTexture).then((sprite) => {
          this.backgroundSprite = sprite;
          this.calculateBackgroundSize();
        });
      }
    };

    if (this.backgroundTexture) {
      if (PIXI.Cache.has(this.backgroundTexture)) {
        this.backgroundSprite = PIXI.Assets.get(this.backgroundTexture);
        setTimeout(() => {
          if (this.backgroundSprite?.valid) this.calculateBackgroundSize();
          else loadTexture();
        }, 100);
      } else {
        loadTexture();
      }
    }
  }

  createChain(): void {
    const createConstraintForPair = (
      bodyA: Matter.Body,
      bodyB: Matter.Body
    ): void => {
      const directionVector = [
        bodyA.position.x - bodyB.position.x,
        bodyA.position.y - bodyB.position.y,
      ];
      const normFactor = Math.sqrt(
        Math.pow(directionVector[0], 2) + Math.pow(directionVector[1], 2)
      );
      directionVector[0] = directionVector[0] / normFactor;
      directionVector[1] = directionVector[1] / normFactor;
      const bodyAHeight = bodyA.bounds.max.y - bodyA.bounds.min.y,
        bodyAWidth = bodyA.bounds.max.x - bodyA.bounds.min.x,
        bodyBHeight = bodyB.bounds.max.y - bodyB.bounds.min.y,
        bodyBWidth = bodyB.bounds.max.x - bodyB.bounds.min.x;

      const constraint = Matter.Constraint.create({
        bodyA: bodyA,
        pointA: {
          x: (bodyAWidth * directionVector[0]) / 2,
          y: (bodyAHeight * directionVector[1]) / 2,
        },
        bodyB: bodyB,
        pointB: {
          x: bodyBWidth * (-directionVector[0] / 2),
          y: bodyBHeight * (-directionVector[1] / 2),
        },
        stiffness: 0.1,
        damping: 1,
        length: 2,
        render: { type: 'line' },
      });
      this.activeComposition.constraints.push(constraint);
    };

    /*if (this.activeComposition.constraints.length > 0)
      this.activeComposition.constraints.length--;
    for (
      let i = this.activeComposition.constraints.length + 2;
      i < this.activeComposition.bodies.length;
      i++
    ) {
      const bodyA = this.activeComposition.bodies[i],
        bodyB = this.activeComposition.bodies[i - 1];
      createConstraintForPair(bodyA, bodyB);
    }
    createConstraintForPair(
      this.activeComposition.bodies[this.activeComposition.bodies.length - 1],
      this.activeComposition.bodies[0]
    );*/
    for (
      let i = this.activeComposition.constraints.length + 1;
      i < this.activeComposition.bodies.length;
      i++
    ) {
      const bodyA = this.activeComposition.bodies[i - 1],
        bodyB = this.activeComposition.bodies[i];
      createConstraintForPair(bodyA, bodyB);
    }
  }

  async mounted(): Promise<void> {
    //initialise observer in mounted as otherwise this references observer
    this.hierarchyObserver = new MutationObserver(this.hierarchyChanged);
    this.resizeObserver = new ResizeObserver(this.sizeChanged);

    const gameContainer = this.$refs.gameContainer as HTMLElement;
    gameContainer.addEventListener(
      EventType.REGISTER_GAME_OBJECT,
      this.registerGameObject
    );
    gameContainer.addEventListener(
      EventType.REGISTER_CUSTOM_OBJECT,
      this.registerCustomObject
    );

    this.setupMatter();
    this.resizeObserver.observe(this.$el.parentElement);
    if (this.hasMouseInput) {
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
      if (pixi) {
        pixi.app.transparent = this.transparent;
        this.$emit('initRenderer', pixi.app.renderer);
      }
    }, 100);
    Matter.Composite.add(this.engine.world, this.activeComposition);
  }

  registerGameObject(e: any): void {
    const gameObject = e.detail.data as GameObject;
    if (gameObject.moveWithBackground) {
      if (gameObject.objectSpace === ObjectSpace.RelativeToBackground)
        gameObject.initOffset(this.gameObjectOffsetRelativeToBackground);
      else if (gameObject.objectSpace === ObjectSpace.RelativeToScreen)
        gameObject.initOffset(this.gameObjectOffsetRelativeToScreen);
    }
    this.gameObjects.push(gameObject);
    gameObject.setGameContainer(this);
    if (this.activatedObjectOnRegister) {
      this.$emit('update:selectedObject', gameObject);
      if (this.isMouseDown) {
        this.activeObject = gameObject;
        this.activeObject.$emit('update:highlighted', true);
      } else gameObject.gameObjectReleased();
    }
  }

  deregisterGameObject(gameObject: GameObject): void {
    const index = this.gameObjects.findIndex((obj) => obj === gameObject);
    if (index > -1) {
      this.gameObjects.splice(index, 1);
    }
  }

  registerCustomObject(e: any): void {
    const gameObject = e.detail.data as CustomObject;
    this.customObjects.push(gameObject);
    gameObject.setGameContainer(this);
  }

  deregisterCustomObject(gameObject: CustomObject): void {
    const index = this.customObjects.findIndex((obj) => obj === gameObject);
    if (index > -1) {
      this.customObjects.splice(index, 1);
    }
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
      }
    }
  }

  hierarchyChanged(mutationList: MutationRecord[]): void {
    for (const mutation of mutationList) {
      if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
        const canvas = Array.from(mutation.addedNodes).find(
          (node) => node.nodeName.toLowerCase() === 'canvas'
        ) as HTMLCanvasElement | undefined;
        if (canvas) {
          this.setupMouseConstraint(canvas);
          return;
        }
      }
    }
  }

  sizeChanged(): void {
    this.setupPixiSpace();
    //this.resizeObserver.disconnect();
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
      gameContainer.removeEventListener(
        EventType.REGISTER_CUSTOM_OBJECT,
        this.registerCustomObject
      );
    }
    clearInterval(this.intervalCollision);
    clearInterval(this.intervalWind);
    clearInterval(this.intervalSync);
    clearInterval(this.intervalPan);
    if (this.backgroundTexture && PIXI.Cache.has(this.backgroundTexture)) {
      PIXI.Assets.unload(this.backgroundTexture);
    }
    Matter.Events.off(this.engine, 'collisionStart', this.collisionStart);
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
        this.calculateBackgroundSize();
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

  getGameObjectForBody(body: Matter.Body): GameObject | null {
    if (body) {
      const obj = this.gameObjects.find(
        (obj) => obj.body && obj.body.id === body.id
      );
      if (obj) return obj;
    }
    return null;
  }

  readonly minClickTimeDelta = 10;
  isMouseDown = false;
  gameContainerClicked(event: any): void {
    const point = { x: event.layerX, y: event.layerY }; //this.mouseConstraint.mouse.position;
    const clickedBodies = Matter.Query.point(
      this.gameObjects
        .filter((gameObj) => gameObj.body)
        .map((gameObj) => gameObj.body),
      point
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
        const mousePosition = this.mouseConstraint.mouse.position;
        const relativeMousePositionToScreen = {
          x:
            ((mousePosition.x + this.gameObjectOffsetRelativeToScreen[0]) /
              this.gameWidth) *
            100,
          y:
            ((mousePosition.y + this.gameObjectOffsetRelativeToScreen[1]) /
              this.gameHeight) *
            100,
        };
        const relativeMousePositionToBackground = {
          x:
            ((mousePosition.x + this.gameObjectOffsetRelativeToBackground[0]) /
              this.backgroundTextureSize[0]) *
            100,
          y:
            ((mousePosition.y + this.gameObjectOffsetRelativeToBackground[1]) /
              this.backgroundTextureSize[1]) *
            100,
        };
        this.$emit('click', {
          mousePosition: mousePosition,
          relativeMousePositionToScreen: relativeMousePositionToScreen,
          relativeMousePositionToBackground: relativeMousePositionToBackground,
        });
      }
    }, this.minClickTimeDelta);
  }

  gameContainerReleased(): void {
    this.isMouseDown = false;
    if (this.activeObject) {
      this.activeObject.gameObjectReleased();
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
        { isStatic: true, isHidden: true, collisionFilter: { group: 0b0001 } }
      );
      if (this.useBorders) Matter.Composite.add(this.engine.world, bottom);
      const top = Matter.Bodies.rectangle(
        bounds.top.x,
        bounds.top.y,
        bounds.top.width,
        bounds.top.height,
        { isStatic: true, isHidden: true, collisionFilter: { group: 0b0001 } }
      );
      if (this.useBorders) Matter.Composite.add(this.engine.world, top);
      const right = Matter.Bodies.rectangle(
        bounds.right.x,
        bounds.right.y,
        bounds.right.width,
        bounds.right.height,
        { isStatic: true, isHidden: true, collisionFilter: { group: 0b0001 } }
      );
      if (this.useBorders) Matter.Composite.add(this.engine.world, right);
      const left = Matter.Bodies.rectangle(
        bounds.left.x,
        bounds.left.y,
        bounds.left.width,
        bounds.left.height,
        { isStatic: true, isHidden: true, collisionFilter: { group: 0b0001 } }
      );
      if (this.useBorders) Matter.Composite.add(this.engine.world, left);
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
    Matter.Events.on(this.engine, 'collisionStart', this.collisionStart);
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

  collisionStart(event: Matter.Event): void {
    if (this.activeObject && this.combinedActiveCollisionToChain) {
      const activeId = this.activeObject.id;
      const group = (this.activeObject.options.collisionFilter as any).group;
      const collisionWithActiveObject = event.pairs.filter(
        (collision) =>
          (collision.bodyA.id === activeId ||
            collision.bodyB.id === activeId) &&
          !collision.bodyA.isStatic &&
          !collision.bodyB.isStatic
      );

      if (collisionWithActiveObject.length > 0) {
        for (const collisionObject of collisionWithActiveObject) {
          const collidingObj =
            collisionObject.bodyA.id === activeId
              ? collisionObject.bodyB
              : collisionObject.bodyA;
          if (
            group === collidingObj.collisionFilter.group &&
            !this.activeComposition.bodies.find(
              (item) => item.id === collidingObj.id
            )
          ) {
            Matter.Composite.remove(this.engine.world, collidingObj);
            Matter.Composite.add(this.activeComposition, collidingObj);
            const gameObject = this.getGameObjectForBody(collidingObj);
            if (gameObject) gameObject.$emit('update:highlighted', true);
            this.createChain();
          }
        }
      }
    }
  }

  lookForCollision(): void {
    const handleCollision = (gameObject: GameObject): void => {
      if (
        this.activeComposition.bodies.find(
          (item) => gameObject.body.id === item.id
        )
      ) {
        for (const chainBody of this.activeComposition.bodies) {
          const chainObject = this.getGameObjectForBody(chainBody);
          if (chainObject && chainObject.body.id !== gameObject.body.id) {
            chainObject.$emit('update:highlighted', false);
            chainObject.handleCollision();
          }
        }
        Matter.Composite.clear(this.activeComposition);
      }
      if (gameObject) gameObject.$emit('update:highlighted', false);
      gameObject.handleCollision();
    };

    const collisions = Matter.Detector.collisions(this.detector);
    if (collisions.length > 0) {
      const validCollision = this.collisionsFilter
        ? collisions.find((collision) => {
            if (this.collisionsFilter) return this.collisionsFilter(collision);
            return true;
          })
        : collisions;
      if (validCollision) {
        const gameObjectA = this.getGameObjectForBody(validCollision.bodyA);
        const gameObjectB = this.getGameObjectForBody(validCollision.bodyB);
        if (gameObjectA) handleCollision(gameObjectA);
        if (gameObjectB) handleCollision(gameObjectB);
      }
    }
  }

  syncRenderView(): void {
    for (const gameObject of this.gameObjects) {
      if (gameObject.moveWithBackground && !this.backgroundSprite) continue;
      gameObject.checkTrigger();
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

  panVector: [number, number] = [0, 0];
  beginPan(vector: [number, number]): void {
    this.panVector = [vector[0] * 5, vector[1] * 5];
    clearInterval(this.intervalPan);
    this.intervalPan = setInterval(this.pan, this.intervalTimePan);
  }

  endPan(): void {
    this.panVector = [0, 0];
    clearInterval(this.intervalPan);
  }

  pan(): void {
    const x = this.backgroundPositionOffset[0] + this.panVector[0];
    const y = this.backgroundPositionOffset[1] + this.panVector[1];
    if (
      x < this.backgroundPositionOffsetMin[0] ||
      x > this.backgroundPositionOffsetMax[0] ||
      y < this.backgroundPositionOffsetMin[1] ||
      y > this.backgroundPositionOffsetMax[1]
    )
      this.endPan();
    else {
      this.backgroundPositionOffset = [x, y];
      for (const gameObj of this.gameObjects) {
        if (
          gameObj.moveWithBackground &&
          gameObj.objectSpace === ObjectSpace.RelativeToBackground
        )
          gameObj.updateOffset(this.gameObjectOffsetRelativeToBackground);
        else if (
          gameObj.moveWithBackground &&
          gameObj.objectSpace === ObjectSpace.RelativeToScreen
        )
          gameObj.updateOffset(this.gameObjectOffsetRelativeToScreen);
      }
      this.$emit('update:offset', this.gameObjectOffsetRelativeToBackground);
    }
  }

  get gameObjectOffsetRelativeToBackground(): [number, number] {
    return [
      this.backgroundTextureSize[0] / 2 - this.backgroundPositionOffset[0],
      this.backgroundTextureSize[1] / 2 - this.backgroundPositionOffset[1],
    ] as [number, number];
  }

  get gameObjectOffsetRelativeToScreen(): [number, number] {
    return [
      this.gameWidth / 2 - this.backgroundPositionOffset[0],
      this.gameHeight / 2 - this.backgroundPositionOffset[1],
    ] as [number, number];
    /*return [
      this.backgroundPositionOffset[0] - this.gameWidth / 2,
      this.backgroundPositionOffset[1] - this.gameHeight / 2,
    ] as [number, number];*/
  }
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
</style>
