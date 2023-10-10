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
    v-loading="loading"
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
      <container :filters="pixiFilterList">
        <sprite
          v-if="backgroundSprite && backgroundSprite.valid"
          :texture="backgroundSprite"
          :anchor="0.5"
          :width="backgroundTextureSize[0]"
          :height="backgroundTextureSize[1]"
          :x="backgroundTexturePosition[0]"
          :y="backgroundTexturePosition[1]"
        ></sprite>
        <container
          v-for="region of regionBodyList"
          :key="region.body.id"
          :x="region.body.position.x"
          :y="region.body.position.y"
        >
          <Graphics
            v-if="region.body"
            @render="drawRegion($event, region)"
            :x="0"
            :y="0"
            :width="region.size[0]"
            :height="region.size[1]"
            :filters="region.region.filter"
          ></Graphics>
        </container>
        <slot :itemProps="{ engine: engine, detector: detector }"></slot>
        <container
          v-if="
            collisionBorders !== CollisionBorderType.None &&
            borders &&
            showBounds
          "
        >
          <container
            v-if="borders.bottom"
            :x="borders.bottom.position.x"
            :y="borders.bottom.position.y"
          >
            <Graphics
              @render="drawBorder($event, borders.bottom)"
              :x="0"
              :y="0"
              :width="getBodyWidth(borders.bottom)"
              :height="getBodyHeight(borders.bottom)"
            ></Graphics>
          </container>
          <container
            v-if="borders.top"
            :x="borders.top.position.x"
            :y="borders.top.position.y"
          >
            <Graphics
              @render="drawBorder($event, borders.top)"
              :x="0"
              :y="0"
              :width="getBodyWidth(borders.top)"
              :height="getBodyHeight(borders.top)"
            ></Graphics>
          </container>
          <container
            v-if="borders.left"
            :x="borders.left.position.x"
            :y="borders.left.position.y"
          >
            <Graphics
              @render="drawBorder($event, borders.left)"
              :x="0"
              :y="0"
              :width="getBodyWidth(borders.left)"
              :height="getBodyHeight(borders.left)"
            ></Graphics>
          </container>
          <container
            v-if="borders.right"
            :x="borders.right.position.x"
            :y="borders.right.position.y"
          >
            <Graphics
              @render="drawBorder($event, borders.right)"
              :x="0"
              :y="0"
              :width="getBodyWidth(borders.right)"
              :height="getBodyHeight(borders.right)"
            ></Graphics>
          </container>
        </container>
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
    <div v-if="ready">
      <div v-for="region of regionBodyList" :key="region.body.id">
        {{ region.body.position.x }} /
        {{ region.position[0] + backgroundPositionOffset[0] }}
      </div>
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
import * as pixiUtil from '@/utils/pixi';
import * as matterUtil from '@/utils/matter';
import { getPolygonCenter } from '@/utils/polygon';
import { until } from '@/utils/wait';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export enum BackgroundPosition {
  Stretch = 'stretch',
  Contain = 'contain',
  Cover = 'cover',
  None = 'none',
}

export enum BackgroundMovement {
  None = 'none',
  Pan = 'pan',
  Auto = 'auto',
}

export enum CollisionBorderType {
  None = 'none',
  Screen = 'screen',
  Background = 'background',
}

export interface CollisionRegion {
  path: [number, number][];
  source: any;
  options: {
    [key: string]: string | number | boolean | object;
  };
  filter: any[];
  color: string;
  alpha: number;
}

interface CollisionRegionData {
  region: CollisionRegion;
  position: [number, number];
  size: [number, number];
  body: Matter.Body | null;
  graphic: PIXI.Graphics | null;
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
    'updateOffset',
    'backgroundSizeChanged',
    'containerReady',
  ],
})
export default class GameContainer extends Vue {
  //#region props
  @Prop({ default: true }) readonly hasMouseInput!: boolean;
  @Prop({ default: true }) readonly detectCollision!: boolean;
  @Prop({ default: true }) readonly useGravity!: boolean;
  @Prop({ default: false }) readonly useWind!: boolean;
  @Prop({ default: CollisionBorderType.Screen })
  readonly collisionBorders!: CollisionBorderType;
  @Prop({ default: 1 }) readonly borderCategory!: number;
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
  @Prop({ default: [] }) readonly collisionRegions!: CollisionRegion[];
  @Prop({ default: false }) readonly showBounds!: boolean;
  @Prop({ default: [] }) readonly pixiFilterList!: any[];
  //#endregion props

  //#region variables
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
  app: PIXI.Application | null = null;
  regionBodyList: CollisionRegionData[] = [];

  gameObjects: GameObject[] = [];
  customObjects: CustomObject[] = [];
  activeObject: GameObject | null = null;
  activeComposition: Matter.Composite = Matter.Composite.create();

  readonly intervalTimeWind = 50;
  intervalWind = -1;
  readonly intervalTimePan = 50;
  intervalPan = -1;
  loading = false;

  backgroundPositionOffset: [number, number] = [0, 0];
  backgroundPositionOffsetMin: [number, number] = [0, 0];
  backgroundPositionOffsetMax: [number, number] = [0, 0];
  backgroundTextureSize: [number, number] = [100, 100];
  CollisionBorderType = CollisionBorderType;
  //#endregion variables

  //#region get
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

  getGameObjectForBody(body: Matter.Body): GameObject | null {
    if (body) {
      const obj = this.gameObjects.find(
        (obj) => obj.body && obj.body.id === body.id
      );
      if (obj) return obj;
    }
    return null;
  }

  getCollisionRegionForBody(body: Matter.Body): CollisionRegion | null {
    if (body) {
      const obj = this.regionBodyList.find(
        (obj) => obj.body && obj.body.id === body.id
      );
      if (obj) return obj.region;
    }
    return null;
  }

  getBodyWidth(body: Matter.Body): number {
    return body.bounds.max.x - body.bounds.min.x;
  }

  getBodyHeight(body: Matter.Body): number {
    return body.bounds.max.y - body.bounds.min.y;
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

  get visibleScreenMin(): [number, number] {
    /*return [
      this.gameObjectOffsetRelativeToBackground[0] - this.gameWidth / 2,
      this.gameObjectOffsetRelativeToBackground[1] - this.gameHeight / 2,
    ];*/
    return [0, 0];
  }

  get visibleScreenMax(): [number, number] {
    /*return [
      this.gameObjectOffsetRelativeToBackground[0] + this.gameWidth / 2,
      this.gameObjectOffsetRelativeToBackground[1] + this.gameHeight / 2,
    ];*/
    return [this.gameWidth, this.gameHeight];
  }
  //#endregion get

  //#region watch
  @Watch('collisionRegions', { immediate: true })
  onCollisionRegionChanged(): void {
    this.regionBodyList = this.collisionRegions.map((item) => {
      return {
        region: item,
        position: [0, 0],
        size: [100, 100],
        body: null,
        graphic: null,
      };
    });
  }

  @Watch('activeObject', { immediate: true })
  onActiveObjectChanged(): void {
    if (this.combinedActiveCollisionToChain && this.activeComposition) {
      if (!this.activeObject) {
        for (const body of this.activeComposition.bodies) {
          const gameObject = this.getGameObjectForBody(body);
          if (gameObject) gameObject.$emit('update:highlighted', false);
          this.addToEngin(body);
        }
        Matter.Composite.clear(this.activeComposition);
      } else {
        this.removeFromEngin(this.activeObject.body);
        Matter.Composite.add(this.activeComposition, this.activeObject.body);
        this.activeObject.$emit('update:highlighted', true);
      }
    }
  }

  @Watch('backgroundTexture', { immediate: true })
  onBackgroundTextureChanged(): void {
    const loadTexture = (): void => {
      if (this.backgroundTexture) {
        pixiUtil
          .loadTexture(this.backgroundTexture, this.eventBus)
          .then((sprite) => {
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
  //#endregion watch

  //#region load / unload
  async mounted(): Promise<void> {
    this.eventBus.on(EventType.TEXTURES_LOADING_START, async () => {
      this.loading = true;
    });
    this.eventBus.on(EventType.ALL_TEXTURES_LOADED, async () => {
      this.loading = false;
      if (this.ready) this.$emit('containerReady');
    });

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

    if (this.useWind) {
      this.intervalWind = setInterval(this.addWind, this.intervalTimeWind);
    }

    setTimeout(async () => {
      const pixi = this.$refs.pixi as typeof Application;
      if (pixi) {
        this.app = pixi.app;
        pixi.app.transparent = this.transparent;
        this.$emit('initRenderer', pixi.app.renderer);
      }
    }, 100);

    setTimeout(() => {
      if (this.backgroundMovement === BackgroundMovement.Auto) {
        this.startAutoPan();
      }
    }, 1000);
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
    clearInterval(this.intervalWind);
    clearInterval(this.intervalPan);
    pixiUtil.unloadTexture(this.backgroundTexture);
    Matter.Events.off(this.engine, 'collisionStart', this.collisionStart);
    Matter.Events.off(this.engine, 'afterUpdate', this.afterPhysicUpdate);
    this.eventBus.off(EventType.TEXTURES_LOADING_START);
    this.eventBus.off(EventType.ALL_TEXTURES_LOADED);
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
        if (this.collisionBorders !== CollisionBorderType.Background)
          this.setupBound();
        this.calculateBackgroundSize();
        this.ready = true;
        if (!this.loading) this.$emit('containerReady');
      }
    }
  }

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
    if (this.backgroundMovement === BackgroundMovement.Auto)
      this.backgroundPositionOffset = [...this.backgroundPositionOffsetMax];
    this.notifyCurrentOffset();
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

    this.setupRegions();
    if (this.collisionBorders === CollisionBorderType.Background)
      this.setupBound();
  }

  setupRegions(): void {
    for (const region of this.regionBodyList) {
      const collisionRegion = region.region;
      //const x = this.backgroundTextureSize[0] / 2;
      //const y = this.backgroundTextureSize[1] / 2;
      const width = this.backgroundTextureSize[0];
      const height = this.backgroundTextureSize[1];
      const center = getPolygonCenter(collisionRegion.path);
      const x = this.backgroundTextureSize[0] * (center[0] / 100);
      const y = this.backgroundTextureSize[1] * (center[1] / 100);
      if (!region.body) {
        collisionRegion.options.isStatic = true;
        collisionRegion.options.isSensor = true;
        region.body = matterUtil.createPolygonBody(
          collisionRegion.options,
          x,
          y,
          width,
          height,
          collisionRegion.path,
          false
        );
        this.addToEngin(region.body);
        if (this.detector) this.detector.bodies.push(region.body);
      } else {
        collisionRegion.options.isStatic = true;
        const body = matterUtil.createPolygonBody(
          collisionRegion.options,
          x,
          y,
          width,
          height,
          collisionRegion.path,
          false
        );
        Matter.Body.setVertices(region.body, body.vertices);
        Matter.Body.setPosition(region.body, body.position);
        region.position = [
          body.position.x - this.backgroundPositionOffsetMax[0],
          body.position.y - this.backgroundPositionOffsetMax[1],
        ];
        region.size = [
          this.getBodyWidth(region.body),
          this.getBodyHeight(region.body),
        ];
        if (this.showBounds && region.graphic) this.redrawRegion(region);
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
      this.addToEngin(this.mouseConstraint);
    }
  }
  //#endregion load / unload

  //#region register object
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
  //#endregion register object

  //#region force
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
  //#endregion force

  //#region events
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
  //#endregion events

  //#region bounds
  borders:
    | undefined
    | {
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
      } = undefined;
  readonly boundsThickness = 100;
  readonly borderDelta = 0;
  setupBound(): void {
    const gameWidth = this.gameWidth ? this.gameWidth : 100;
    const gameHeight = this.gameHeight ? this.gameHeight : 100;
    const boundsWidth =
      this.collisionBorders !== CollisionBorderType.Background
        ? gameWidth
        : this.backgroundTextureSize[0];
    const boundsHeight =
      this.collisionBorders !== CollisionBorderType.Background
        ? gameHeight
        : this.backgroundTextureSize[1];
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
    if (this.engine && !this.borders) {
      const bottom = Matter.Bodies.rectangle(
        bounds.bottom.x + screenCenterX,
        bounds.bottom.y + screenCenterY,
        bounds.bottom.width,
        bounds.bottom.height,
        {
          isStatic: true,
          isHidden: true,
          collisionFilter: { group: 0b0001, category: this.borderCategory },
        }
      );
      if (this.collisionBorders !== CollisionBorderType.None)
        this.addToEngin(bottom);
      const top = Matter.Bodies.rectangle(
        bounds.top.x + screenCenterX,
        bounds.top.y + screenCenterY,
        bounds.top.width,
        bounds.top.height,
        {
          isStatic: true,
          isHidden: true,
          collisionFilter: { group: 0b0001, category: this.borderCategory },
        }
      );
      if (this.collisionBorders !== CollisionBorderType.None)
        this.addToEngin(top);
      const right = Matter.Bodies.rectangle(
        bounds.right.x + screenCenterX,
        bounds.right.y + screenCenterY,
        bounds.right.width,
        bounds.right.height,
        {
          isStatic: true,
          isHidden: true,
          collisionFilter: { group: 0b0001, category: this.borderCategory },
        }
      );
      if (this.collisionBorders !== CollisionBorderType.None)
        this.addToEngin(right);
      const left = Matter.Bodies.rectangle(
        bounds.left.x + screenCenterX,
        bounds.left.y + screenCenterY,
        bounds.left.width,
        bounds.left.height,
        {
          isStatic: true,
          isHidden: true,
          collisionFilter: { group: 0b0001, category: this.borderCategory },
        }
      );
      if (this.collisionBorders !== CollisionBorderType.None)
        this.addToEngin(left);
      this.borders = {
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
    } else if (this.borders) {
      Matter.Body.scale(
        this.borders.bottom,
        boundsWidth / this.borders.width,
        1
      );
      Matter.Body.setPosition(this.borders.bottom, {
        x: bounds.bottom.x + screenCenterX,
        y: bounds.bottom.y + screenCenterY,
      });
      Matter.Body.scale(this.borders.top, boundsWidth / this.borders.width, 1);
      Matter.Body.setPosition(this.borders.top, {
        x: bounds.top.x + screenCenterX,
        y: bounds.top.y + screenCenterY,
      });
      Matter.Body.scale(
        this.borders.right,
        1,
        boundsHeight / this.borders.height
      );
      Matter.Body.setPosition(this.borders.right, {
        x: bounds.right.x + screenCenterX,
        y: bounds.right.y + screenCenterY,
      });
      Matter.Body.scale(
        this.borders.left,
        1,
        boundsHeight / this.borders.height
      );
      Matter.Body.setPosition(this.borders.left, {
        x: bounds.left.x + screenCenterX,
        y: bounds.left.y + screenCenterY,
      });
      this.borders.width = boundsWidth;
      this.borders.height = boundsHeight;
      this.borders.topPosition = [bounds.top.x, bounds.top.y];
      this.borders.bottomPosition = [bounds.bottom.x, bounds.bottom.y];
      this.borders.leftPosition = [bounds.left.x, bounds.left.y];
      this.borders.rightPosition = [bounds.right.x, bounds.right.y];
    }
  }

  @Watch('collisionBorders', { immediate: true })
  onCollisionBordersChanged(): void {
    if (this.borders) {
      if (this.collisionBorders === CollisionBorderType.None) {
        this.removeFromEngin(this.borders.bottom);
        this.removeFromEngin(this.borders.top);
        this.removeFromEngin(this.borders.right);
        this.removeFromEngin(this.borders.left);
      } else if (!this.engine.world.bodies.includes(this.borders.bottom)) {
        this.addToEngin(this.borders.bottom);
        this.addToEngin(this.borders.top);
        this.addToEngin(this.borders.right);
        this.addToEngin(this.borders.left);
      }
    }
  }
  //#endregion bounds

  //#region matterjs
  setupMatter(): void {
    this.engine = Matter.Engine.create();
    if (this.detectCollision)
      Matter.Events.on(this.engine, 'collisionStart', this.collisionStart);
    Matter.Events.on(this.engine, 'afterUpdate', this.afterPhysicUpdate);
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
    if (this.combinedActiveCollisionToChain)
      this.addToEngin(this.activeComposition);
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
    if (this.engine) Matter.Composite.add(this.engine.world, physicObject);
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
    if (this.engine) Matter.Composite.remove(this.engine.world, physicObject);
  }

  collisionStart(event: Matter.Event): void {
    const collisions = [...event.pairs] as Matter.Collision[];
    this.checkChainCollision(collisions);
    this.notifyCollision(collisions);
  }

  notifyCollision(collisions: Matter.Collision[]): void {
    const handleCollision = (
      gameObject: GameObject,
      collisionObject: GameObject | CollisionRegion | null,
      hitPoint: [number, number],
      hitPointScreen: [number, number],
      objectBody: Matter.Body,
      collisionBody: Matter.Body
    ): void => {
      if (
        this.combinedActiveCollisionToChain &&
        this.activeComposition.bodies.find(
          (item) => gameObject.body.id === item.id
        )
      ) {
        for (const chainBody of this.activeComposition.bodies) {
          const chainObject = this.getGameObjectForBody(chainBody);
          if (chainObject && chainObject.body.id !== gameObject.body.id) {
            chainObject.$emit('update:highlighted', false);
            chainObject.handleCollision(
              collisionObject,
              hitPoint,
              hitPointScreen,
              objectBody,
              collisionBody
            );
          }
        }
        Matter.Composite.clear(this.activeComposition);
      }
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
      const validCollisionList = this.collisionsFilter
        ? collisions.filter((collision) => {
            if (this.collisionsFilter) return this.collisionsFilter(collision);
            return true;
          })
        : collisions;
      for (const validCollision of validCollisionList) {
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
              this.gameHeight
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
              this.gameHeight
            ),
            validCollision.bodyB,
            validCollision.bodyA
          );
        }
      }
    }
  }

  checkChainCollision(collisions: Matter.Collision[]): void {
    if (this.activeObject && this.combinedActiveCollisionToChain) {
      const activeId = this.activeObject.id;
      const group = (this.activeObject.options.collisionFilter as any).group;
      const collisionWithActiveObject = collisions.filter(
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
            this.removeFromEngin(collidingObj);
            Matter.Composite.add(this.activeComposition, collidingObj);
            const gameObject = this.getGameObjectForBody(collidingObj);
            if (gameObject) gameObject.$emit('update:highlighted', true);
            this.createChain();
          }
        }
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
      const bodyAHeight = this.getBodyHeight(bodyA),
        bodyAWidth = this.getBodyWidth(bodyA),
        bodyBHeight = this.getBodyHeight(bodyB),
        bodyBWidth = this.getBodyWidth(bodyB);

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
  //#endregion matterjs

  //#region loop
  afterPhysicUpdate(): void {
    this.syncRenderView();
  }

  syncRenderView(): void {
    for (const gameObject of this.gameObjects) {
      if (gameObject.moveWithBackground && !this.backgroundSprite) continue;
      gameObject.checkTrigger();
      gameObject.syncronize();
    }
  }
  //#endregion loop

  //#region pan and scroll
  async startAutoPan(): Promise<void> {
    await until(() => !this.loading);
    this.backgroundPositionOffset = [...this.backgroundPositionOffsetMax];
    this.beginPan([-0.2, 0]);
  }

  panVector: [number, number] = [0, 0];
  beginPan(vector: [number, number]): void {
    this.panVector = [vector[0] * 5, vector[1] * 5];
    clearInterval(this.intervalPan);
    this.intervalPan = setInterval(this.pan, this.intervalTimePan);
  }

  endPan(): void {
    if (this.backgroundMovement === BackgroundMovement.Pan) {
      this.panVector = [0, 0];
      clearInterval(this.intervalPan);
    }
  }

  pan(): void {
    const x = this.backgroundPositionOffset[0] + this.panVector[0];
    const y = this.backgroundPositionOffset[1] + this.panVector[1];
    const previousPosition = [...this.backgroundPositionOffset];
    if (
      x < this.backgroundPositionOffsetMin[0] ||
      x > this.backgroundPositionOffsetMax[0] ||
      y < this.backgroundPositionOffsetMin[1] ||
      y > this.backgroundPositionOffsetMax[1]
    ) {
      if (x < this.backgroundPositionOffsetMin[0])
        this.backgroundPositionOffset[0] = this.backgroundPositionOffsetMin[0];
      if (x > this.backgroundPositionOffsetMax[0])
        this.backgroundPositionOffset[0] = this.backgroundPositionOffsetMax[0];
      if (y < this.backgroundPositionOffsetMin[1])
        this.backgroundPositionOffset[1] = this.backgroundPositionOffsetMin[1];
      if (y > this.backgroundPositionOffsetMax[1])
        this.backgroundPositionOffset[1] = this.backgroundPositionOffsetMax[1];
      if (this.backgroundMovement === BackgroundMovement.Pan) this.endPan();
      else this.panVector = [this.panVector[0] * -1, this.panVector[1] * -1];
    } else {
      this.backgroundPositionOffset = [x, y];
    }

    if (
      previousPosition[0] !== this.backgroundPositionOffset[0] ||
      previousPosition[1] !== this.backgroundPositionOffset[1]
    ) {
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

      const deltaX = this.backgroundPositionOffset[0] - previousPosition[0];
      const deltaY = this.backgroundPositionOffset[1] - previousPosition[1];
      for (const region of this.regionBodyList) {
        Matter.Body.setPosition(region.body, {
          x: region.position[0] + this.backgroundPositionOffset[0],
          y: region.position[1] + this.backgroundPositionOffset[1],
        });

        for (const filter of region.region.filter) {
          if (filter.center) {
            filter.center[0] += deltaX;
            filter.center[1] += deltaY;
          }
        }
      }

      if (
        this.collisionBorders === CollisionBorderType.Background &&
        this.borders
      ) {
        Matter.Body.setPosition(this.borders.bottom, {
          x: this.borders.bottomPosition[0] + this.backgroundPositionOffset[0],
          y: this.borders.bottomPosition[1] + this.backgroundPositionOffset[1],
        });
        Matter.Body.setPosition(this.borders.top, {
          x: this.borders.topPosition[0] + this.backgroundPositionOffset[0],
          y: this.borders.topPosition[1] + this.backgroundPositionOffset[1],
        });
        Matter.Body.setPosition(this.borders.left, {
          x: this.borders.leftPosition[0] + this.backgroundPositionOffset[0],
          y: this.borders.leftPosition[1] + this.backgroundPositionOffset[1],
        });
        Matter.Body.setPosition(this.borders.right, {
          x: this.borders.rightPosition[0] + this.backgroundPositionOffset[0],
          y: this.borders.rightPosition[1] + this.backgroundPositionOffset[1],
        });
      }
    }

    this.notifyCurrentOffset();
    this.$emit('update:offset', this.gameObjectOffsetRelativeToBackground);
  }

  notifyCurrentOffset(): void {
    const relativeOffset = this.convertToRelativePosition(
      this.backgroundPositionOffset,
      'current'
    );
    const relativeOffsetMin = this.convertToRelativePosition(
      this.backgroundPositionOffset,
      'min'
    );
    const relativeOffsetMax = this.convertToRelativePosition(
      this.backgroundPositionOffset,
      'max'
    );
    this.$emit(
      'updateOffset',
      relativeOffset,
      relativeOffsetMin,
      relativeOffsetMax
    );
  }

  convertToRelativePosition(
    position: [number, number],
    type: 'min' | 'max' | 'current'
  ): [number, number] {
    const deltaX = this.backgroundTextureSize[0] - this.gameWidth;
    const deltaY = this.backgroundTextureSize[1] - this.gameHeight;
    //const min = [-deltaX / 2, -deltaY / 2];
    const max = [this.gameWidth + deltaX / 2, this.gameHeight + deltaY];
    const minPosition = [
      position[0] - this.gameWidth / 2,
      position[1] - this.gameHeight / 2,
    ];
    const maxPosition = [
      position[0] + this.gameWidth / 2,
      position[1] + this.gameHeight / 2,
    ];
    switch (type) {
      case 'current':
        return [
          deltaX > 0
            ? 100 -
              ((position[0] - this.backgroundPositionOffsetMin[0]) / deltaX) *
                100
            : 0,
          deltaY > 0
            ? 100 -
              ((position[1] - this.backgroundPositionOffsetMin[1]) / deltaY) *
                100
            : 0,
        ];
      case 'min':
        return [
          ((maxPosition[0] - max[0]) / this.backgroundTextureSize[0]) *
            100 *
            -1,
          ((maxPosition[1] - max[1]) / this.backgroundTextureSize[1]) *
            100 *
            -1,
        ];
      case 'max':
        return [
          ((minPosition[0] - max[0]) / this.backgroundTextureSize[0]) *
            100 *
            -1,
          ((minPosition[1] - max[1]) / this.backgroundTextureSize[1]) *
            100 *
            -1,
        ];
    }
  }
  //#endregion pan and scroll

  //#region draw
  drawRegion(inputGraphics: PIXI.Graphics, region: CollisionRegionData): void {
    if (inputGraphics) {
      region.graphic = inputGraphics;
      this.redrawRegion(region);
    }
  }

  redrawRegion(region: CollisionRegionData): void {
    if (region.graphic && region.body) {
      region.graphic.clear();
      const path = region.body.vertices.map((item) => {
        return {
          x: item.x - region.body.position.x,
          y: item.y - region.body.position.y,
        };
      });
      if (this.showBounds) {
        region.graphic.lineStyle(2, '#ff0000');
      }
      region.graphic.beginFill(region.region.color, region.region.alpha);
      region.graphic.drawPolygon(path);
      region.graphic.endFill();
    }
    /*if (region.graphic && region.body) {
      region.graphic.clear();
      const path = region.body.vertices.map((item) => {
        return {
          x: item.x - region.body.position.x,
          y: item.y - region.body.position.y,
        };
      });
      if (this.backgroundSprite && this.backgroundSprite.orig) {
        const matrix: PIXI.Matrix = new PIXI.Matrix();

        const textureWidth = this.backgroundSprite.orig.width;
        const backgroundScale = this.backgroundTextureSize[0] / textureWidth;
        matrix.scale(backgroundScale, backgroundScale);
        matrix.translate(
          -this.backgroundTextureSize[0] / 2,
          -this.backgroundTextureSize[1] / 2
        );
        matrix.translate(-region.position[0], -region.position[1]);
        region.graphic.beginTextureFill({
          texture: this.backgroundSprite,
          matrix: matrix,
          alpha: 1,
        });
      }
      if (this.showBounds) {
        region.graphic.lineStyle(2, '#ff0000');
      }
      region.graphic.drawPolygon(path);
      region.graphic.endFill();
    }*/
  }

  drawBorder(inputGraphics: PIXI.Graphics, body: Matter.Body): void {
    if (inputGraphics && body) {
      inputGraphics.clear();
      const path = body.vertices.map((item) => {
        return {
          x: item.x - body.position.x,
          y: item.y - body.position.y,
        };
      });
      inputGraphics.lineStyle(2, '#ff0000');
      inputGraphics.drawPolygon(path);
    }
  }
  //#endregion draw
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
