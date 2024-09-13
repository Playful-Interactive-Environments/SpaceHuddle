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
    v-loading="!isContainerReady"
  >
    <Application
      :width="gameWidth"
      :height="mapHeight"
      :backgroundColor="backgroundColor"
      :backgroundAlpha="backgroundAlpha"
      v-if="ready && backgroundMovement === BackgroundMovement.Map"
    >
      <sprite
        v-if="backgroundSprite && backgroundSprite.valid"
        :texture="backgroundSprite"
        :anchor="0"
        :alpha="0.5"
        :width="gameWidth"
        :height="mapHeight"
        :x="0"
        :y="0"
      ></sprite>
      <Graphics
        v-if="backgroundPositionOffset && backgroundPositionOffset.length === 2"
        :x="gameObjectOffsetRelativeToBackground[0] * mapScale"
        @render="drawDisplayRegion"
        @pointermove="moveDisplayArea"
      />
    </Application>
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
      <container
        v-if="$slots.preRender && backgroundSprite"
        :gameContainer="this"
        @render="preRenderData"
      >
        <sprite
          v-if="backgroundSprite && backgroundSprite.valid"
          :texture="backgroundSprite"
          :anchor="0.5"
          :width="backgroundTextureSize[0]"
          :height="backgroundTextureSize[1]"
          :x="backgroundPositionOffset[0]"
          :y="backgroundPositionOffset[1]"
        ></sprite>
        <slot name="preRender"></slot>
      </container>
      <container :filters="pixiFilterList" :gameContainer="this">
        <sprite
          v-if="
            backgroundTexturePositionSprite &&
            backgroundTexturePositionSprite.valid &&
            (!$slots.preRender || preRendered)
          "
          :texture="backgroundTexturePositionSprite"
          :anchor="0.5"
          :width="backgroundTexturePositionSize[0]"
          :height="backgroundTexturePositionSize[1]"
          :x="backgroundTexturePosition[0]"
          :y="backgroundTexturePosition[1]"
          :filters="pixiFilterListBackground"
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
          <text
            v-if="region.region.text && showRegionText"
            :anchor="[region.anchor, 0]"
            :style="{ fontFamily: 'Arial', fontSize: 34, fill: '#ffffff' }"
            :scale="textScaleFactor"
            :x="calculateRegionTextPosition(region)"
          >
            {{ region.region.text }}
          </text>
        </container>
        <slot :itemProps="{ engine: engine }"></slot>
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
      <container v-if="showAllEnginColliders">
        <Graphics
          @render="drawAllCollider($event)"
          :x="0"
          :y="0"
          :width="gameWidth"
          :height="gameDisplayHeight"
        ></Graphics>
      </container>
    </Application>
    <div
      class="navigation-overlay overlay-right"
      v-if="
        backgroundMovement === BackgroundMovement.Pan &&
        (backgroundPositionOffsetMin[0] < backgroundPositionOffset[0] ||
          endlessPanning)
      "
    >
      <font-awesome-icon
        icon="circle-chevron-right"
        @mousedown="beginPan([-manualPanSpeed, 0])"
        @touchstart="beginPan([-manualPanSpeed, 0])"
      />
    </div>
    <div
      class="navigation-overlay overlay-left"
      v-if="
        backgroundMovement === BackgroundMovement.Pan &&
        (backgroundPositionOffsetMax[0] > backgroundPositionOffset[0] ||
          endlessPanning)
      "
    >
      <font-awesome-icon
        icon="circle-chevron-left"
        @mousedown="beginPan([manualPanSpeed, 0])"
        @touchstart="beginPan([manualPanSpeed, 0])"
      />
    </div>
    <div
      class="navigation-overlay overlay-up"
      v-if="
        backgroundMovement === BackgroundMovement.Pan &&
        (backgroundPositionOffsetMax[1] > backgroundPositionOffset[1] ||
          endlessPanning)
      "
    >
      <font-awesome-icon
        icon="circle-chevron-up"
        @mousedown="beginPan([0, manualPanSpeed])"
        @touchstart="beginPan([0, manualPanSpeed])"
      />
    </div>
    <div
      class="navigation-overlay overlay-down"
      v-if="
        backgroundMovement === BackgroundMovement.Pan &&
        (backgroundPositionOffsetMin[1] < backgroundPositionOffset[1] ||
          endlessPanning)
      "
    >
      <font-awesome-icon
        icon="circle-chevron-down"
        @mousedown="beginPan([0, -manualPanSpeed])"
        @touchstart="beginPan([0, -manualPanSpeed])"
      />
    </div>
    <div v-if="showFPS" class="frameInfo">
      {{ Math.round(1000 / frameDelta) }}fps
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as Matter from 'matter-js/build/matter';
import { Application } from 'vue3-pixi';
import { EventType } from '@/types/enum/EventType';
import GameObject, {
  FastObjectBehaviour,
} from '@/types/game/gameObject/GameObject';
import { bounceCategory } from '@/types/game/gameObject/ObjectPhysics';
import { SpaceObject } from '@/types/game/sprite/SpaceObject';
import * as PIXI from 'pixi.js';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { ObjectSpaceType } from '@/types/enum/ObjectSpaceType';
import * as pixiUtil from '@/utils/pixi';
import * as matterUtil from '@/utils/matter';
import { getPolygonCenter } from '@/utils/polygon';
import { delay, until } from '@/utils/wait';
import { registerDomElement, unregisterDomElement } from '@/vunit';

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
  Map = 'map',
  Break = 'break',
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
  @Prop({ default: false }) readonly useObjectPooling!: boolean;
  @Prop({ default: CollisionBorderType.Screen })
  readonly collisionBorders!: CollisionBorderType;
  @Prop({ default: 0 }) readonly borderDelta!: number;
  @Prop({ default: 1 }) readonly borderCategory!: number;
  @Prop({ default: false }) readonly activatedObjectOnRegister!: boolean;
  @Prop({ default: undefined }) readonly width!: number | undefined;
  @Prop({ default: undefined }) readonly height!: number | undefined;
  @Prop({ default: [0, 0] }) readonly offset!: [number, number];
  @Prop({ default: '#f4f4f4' }) readonly backgroundColor!: string;
  @Prop({ default: 1 }) readonly backgroundAlpha!: number;
  @Prop({ default: null }) readonly backgroundTexture!: string | null;
  @Prop({ default: BackgroundPosition.Cover })
  readonly backgroundPosition!: BackgroundPosition;
  @Prop({ default: BackgroundMovement.None })
  readonly backgroundMovement!: BackgroundMovement;
  @Prop({ default: false }) readonly endlessPanning!: boolean;
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
  @Prop({ default: false }) readonly showAllEnginColliders!: boolean;
  @Prop({ default: null }) readonly pixiFilterList!: any[] | null;
  @Prop({ default: null }) readonly pixiFilterListBackground!: any[] | null;
  @Prop({ default: 0.4 }) readonly autoPanSpeed!: number;
  @Prop({ default: [1, 0] }) readonly autoPanDirection!: [number, number];
  @Prop({ default: 2 }) readonly manualPanSpeed!: number;
  @Prop({ default: false }) readonly resetPositionOnSpeedChanged!: boolean;
  @Prop({ default: false }) readonly enableSleeping!: boolean;
  @Prop({ default: false }) readonly waitForDataLoad!: boolean;
  @Prop({ default: true }) readonly usePreCalculation!: boolean;
  @Prop({ default: true }) readonly showRegionText!: boolean;
  //#endregion props

  //#region variables
  ready = false;
  preRendered = false;
  gameWidth = 0;
  gameHeight = 0;
  backgroundSprite: PIXI.Texture | null = null;
  backgroundSpriteEndlessPanning: PIXI.Texture | null = null;
  textureToken = pixiUtil.createLoadingToken();

  canvasPosition: [number, number] = [0, 0];
  engine!: typeof Matter.Engine;
  runner!: typeof Matter.Runner;
  mouseConstraint!: typeof Matter.MouseConstraint;
  hierarchyObserver!: MutationObserver;
  //resizeObserver!: ResizeObserver;
  app: PIXI.Application | null = null;
  regionBodyList: CollisionRegionData[] = [];

  gameObjects: GameObject[] = [];
  spaceObjects: SpaceObject[] = [];
  activeObject: GameObject | null = null;
  activeComposition: Matter.Composite | null = null;

  readonly intervalTimePan = 50;
  intervalPan = -1;
  loading = false;

  backgroundPositionOffset: [number, number] = [0, 0];
  backgroundPositionOffsetMin: [number, number] = [0, 0];
  backgroundPositionOffsetMax: [number, number] = [0, 0];
  backgroundTextureSize: [number, number] = [100, 100];
  CollisionBorderType = CollisionBorderType;

  freePoolBody: { [key: string]: Matter.Body[] } = {};
  //#endregion variables

  //#region get
  get showFPS(): boolean {
    return JSON.parse(process.env.VUE_APP_SHOW_FPS);
  }

  get isBackgroundLoaded(): boolean {
    if (this.backgroundTexture)
      return !!this.backgroundSprite && !!this.backgroundSprite.orig;
    return true;
  }

  get backgroundTextureAspect(): number {
    if (this.backgroundSprite && this.backgroundSprite.orig) {
      const textureWidth = this.backgroundSprite.orig.width;
      const textureHeight = this.backgroundSprite.orig.height;
      return textureWidth / textureHeight;
    }
    return -1;
  }

  get mapHeight(): number {
    if (this.backgroundMovement === BackgroundMovement.Map) {
      if (this.backgroundTextureAspect > 0) {
        return this.gameWidth / this.backgroundTextureAspect;
      }
    }
    return 0;
  }

  get isGameDisplayHeightReady(): boolean {
    if (this.backgroundMovement === BackgroundMovement.Map) {
      if (this.backgroundTextureAspect <= 0) {
        return false;
      }
    }
    return true;
  }

  get gameDisplayHeight(): number {
    return this.gameHeight - this.mapHeight;
  }

  get mapScale(): number {
    return this.mapHeight / this.gameDisplayHeight;
  }

  getBackgroundAspect(): number {
    if (this.backgroundTextureAspect > 0) {
      return this.backgroundTextureAspect;
    }
    return this.gameWidth / this.gameDisplayHeight;
  }

  get backgroundTexturePosition(): [number, number] {
    if (!this.endlessPanning) return this.backgroundPositionOffset;
    if (this.backgroundMovement === BackgroundMovement.Pan) {
      return [
        this.backgroundPositionOffset[0] - this.backgroundTextureSize[0] / 2,
        this.backgroundPositionOffset[1] - this.backgroundTextureSize[1] / 2,
      ];
    }
    return [
      this.backgroundPositionOffset[0] - this.backgroundTextureSize[0] / 2,
      this.backgroundPositionOffset[1],
    ];
  }

  get backgroundTexturePositionSize(): [number, number] {
    if (!this.endlessPanning) return this.backgroundTextureSize;
    if (this.backgroundMovement === BackgroundMovement.Pan) {
      return [
        this.backgroundTextureSize[0] * 2,
        this.backgroundTextureSize[1] * 2,
      ];
    }
    return [this.backgroundTextureSize[0] * 2, this.backgroundTextureSize[1]];
  }

  get backgroundTexturePositionSprite(): PIXI.Texture | null {
    if (this.usePreCalculation) {
      if (
        this.endlessPanning &&
        this.preRenderTextureEndless &&
        this.preRenderTextureEndless.valid
      )
        return this.preRenderTextureEndless;
      if (this.preRenderTexture && this.preRenderTexture.valid)
        return this.preRenderTexture;
    }
    if (
      this.endlessPanning &&
      this.backgroundSpriteEndlessPanning &&
      this.backgroundSpriteEndlessPanning.valid
    )
      return this.backgroundSpriteEndlessPanning;
    if (
      !this.endlessPanning &&
      this.backgroundSprite &&
      this.backgroundSprite.valid
    )
      return this.backgroundSprite;
    return null;
  }

  getGameObjectForBody(body: Matter.Body): GameObject | null {
    if (body) {
      const obj = this.gameObjects.find(
        (obj) => obj.physcics.body && obj.physcics.bodyId === body.id
      );
      if (obj) return obj;
    }
    return null;
  }

  getBodyForId(id: number): Matter.Body | null {
    const body = this.engine.world.bodies.find((item) => item.id === id);
    if (body) return body;
    if (this.combinedActiveCollisionToChain && this.activeComposition) {
      const body = this.activeComposition.bodies.find((item) => item.id === id);
      if (body) return body;
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

  get backgroundPositionOffsetCircle(): [number, number] {
    return [
      this.backgroundPositionOffset[0] - this.backgroundTextureSize[0],
      this.backgroundPositionOffset[1] - this.backgroundTextureSize[1],
    ];
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
      this.gameDisplayHeight / 2 - this.backgroundPositionOffset[1],
    ] as [number, number];
    /*return [
      this.backgroundPositionOffset[0] - this.gameWidth / 2,
      this.backgroundPositionOffset[1] - this.gameDisplayHeight / 2,
    ] as [number, number];*/
  }

  get gameObjectOffsetRelativeToScreenMin(): [number, number] {
    return [
      -(this.gameWidth / 2 - this.backgroundPositionOffsetMin[0]),
      -(this.gameDisplayHeight / 2 - this.backgroundPositionOffsetMin[1]),
    ] as [number, number];
  }

  get gameObjectOffsetRelativeToScreenMax(): [number, number] {
    return [
      -(this.gameWidth / 2 - this.backgroundPositionOffsetMax[0]),
      -(this.gameDisplayHeight / 2 - this.backgroundPositionOffsetMax[1]),
    ] as [number, number];
  }

  get gameObjectOffsetRelativeToBackgroundCircle(): [number, number] {
    return [
      this.backgroundTextureSize[0] / 2 -
        this.backgroundPositionOffsetCircle[0],
      this.backgroundTextureSize[1] / 2 -
        this.backgroundPositionOffsetCircle[1],
    ] as [number, number];
  }

  get gameObjectOffsetRelativeToScreenCircle(): [number, number] {
    return [
      this.gameWidth / 2 - this.backgroundPositionOffsetCircle[0],
      this.gameDisplayHeight / 2 - this.backgroundPositionOffsetCircle[1],
    ] as [number, number];
  }

  get visibleScreenMin(): [number, number] {
    /*return [
      this.gameObjectOffsetRelativeToBackground[0] - this.gameWidth / 2,
      this.gameObjectOffsetRelativeToBackground[1] - this.gameDisplayHeight / 2,
    ];*/
    return [0, 0];
  }

  get visibleScreenMax(): [number, number] {
    /*return [
      this.gameObjectOffsetRelativeToBackground[0] + this.gameWidth / 2,
      this.gameObjectOffsetRelativeToBackground[1] + this.gameDisplayHeight / 2,
    ];*/
    return [this.gameWidth, this.gameDisplayHeight];
  }

  get textScaleFactor(): number {
    return this.gameWidth / 700;
  }

  get isContainerReady(): boolean {
    return this.ready && !this.loading && !this.waitForDataLoad;
  }

  get boundsWidth(): number {
    const gameWidth = this.gameWidth ? this.gameWidth : 100;
    const backgroundTextureWidth =
      this.endlessPanning && this.backgroundMovement !== BackgroundMovement.None
        ? this.backgroundTextureSize[0] * 3
        : this.backgroundTextureSize[0];
    return this.collisionBorders !== CollisionBorderType.Background
      ? gameWidth
      : backgroundTextureWidth;
  }

  get boundsHeight(): number {
    const gameHeight = this.gameDisplayHeight ? this.gameDisplayHeight : 100;
    const backgroundTextureHeight =
      this.endlessPanning && this.backgroundMovement !== BackgroundMovement.None
        ? this.backgroundTextureSize[1] *
          (this.backgroundMovement === BackgroundMovement.Pan ? 3 : 1)
        : this.backgroundTextureSize[1];
    return this.collisionBorders !== CollisionBorderType.Background
      ? gameHeight
      : backgroundTextureHeight;
  }

  calculateRegionTextPosition(region: CollisionRegionData): number {
    const fullyVisible =
      region.body.position.x - region.size[0] / 2 <= 0 &&
      region.body.position.x + region.size[0] / 2 >= this.gameWidth;
    if (fullyVisible) {
      region.anchor = 0.5;
      return -region.body.position.x + this.gameWidth / 2;
    }
    const partlyVisible =
      region.body.position.x - region.size[0] / 2 < this.gameWidth &&
      region.body.position.x + region.size[0] / 2 > 0;
    if (partlyVisible) {
      if (
        region.body.position.x > this.gameWidth &&
        region.body.position.x - region.size[0] / 2 < this.gameWidth
      ) {
        region.anchor = 1;
        return -region.body.position.x + this.gameWidth;
      }
      if (
        region.body.position.x < 0 &&
        region.body.position.x + region.size[0] / 2 > 0
      ) {
        region.anchor = 0;
        return -region.body.position.x;
      }
      region.anchor = region.body.position.x / this.gameWidth;
    }
    return 0;
  }
  //#endregion get

  //#region watch
  @Watch('collisionRegions', { immediate: true })
  async onCollisionRegionChanged(): Promise<void> {
    const regionBodyList: CollisionRegionData[] = this.collisionRegions.map(
      (item) => {
        return {
          region: item,
          position: [0, 0],
          relativePosition: [0, 0],
          size: [100, 100],
          body: null,
          graphic: null,
          anchor: 0.5,
        };
      }
    );
    if (!this.endlessPanning) this.regionBodyList = regionBodyList;
    else {
      this.regionBodyList = regionBodyList;
      if (this.backgroundMovement !== BackgroundMovement.None) {
        this.regionBodyList.push(
          ...this.collisionRegions.map((item) => {
            return {
              region: item,
              position: [0, 0],
              relativePosition: [-100, 0],
              size: [100, 100],
              body: null,
              graphic: null,
              anchor: 0.5,
            } as CollisionRegionData;
          })
        );
      }
      if (this.backgroundMovement === BackgroundMovement.Pan) {
        this.regionBodyList.push(
          ...this.collisionRegions.map((item) => {
            return {
              region: item,
              position: [0, 0],
              relativePosition: [0, -100],
              size: [100, 100],
              body: null,
              graphic: null,
              anchor: 0.5,
            } as CollisionRegionData;
          }),
          ...this.collisionRegions.map((item) => {
            return {
              region: item,
              position: [0, 0],
              relativePosition: [-100, -100],
              size: [100, 100],
              body: null,
              graphic: null,
              anchor: 0.5,
            } as CollisionRegionData;
          })
        );
      }
    }
  }

  private chainObjects: GameObject[] = [];
  private addChainObject(gameObject: GameObject): void {
    this.chainObjects.push(gameObject);
    if (this.activeComposition) {
      const isMouseObject =
        this.mouseConstraint &&
        this.mouseConstraint.body &&
        this.mouseConstraint.body.id === gameObject.physcics.bodyId;
      const body = matterUtil.copyBody(gameObject.physcics.body);
      this.removeFromEngin(gameObject.physcics.body);
      Matter.Composite.add(this.activeComposition, body);
      gameObject.physcics.body = body;
      if (isMouseObject) this.mouseConstraint.body = body;
    }
    gameObject.highlighted = true;
    gameObject.emit('is_part_of_chain_changed', gameObject, true);
  }

  private removeChainObject(gameObject: GameObject, reAddObject = true): void {
    gameObject.highlighted = false;
    gameObject.emit('is_part_of_chain_changed', gameObject, false);
    if (reAddObject) {
      const body = matterUtil.copyBody(gameObject.physcics.body);
      this.addToEngin(body).then(() => (gameObject.physcics.body = body));
      gameObject.physcics.updatePivot(100, true);
    }
  }

  private clearChain(
    reAddObject: ((item: GameObject) => boolean) | null = null
  ): void {
    for (const gameObject of this.chainObjects) {
      if (reAddObject) {
        this.removeChainObject(gameObject, reAddObject(gameObject));
      } else this.removeChainObject(gameObject);
    }
    this.chainObjects.length = 0;
    if (this.activeComposition) Matter.Composite.clear(this.activeComposition);
  }

  @Watch('activeObject', { immediate: true })
  onActiveObjectChanged(): void {
    if (this.combinedActiveCollisionToChain && this.activeComposition) {
      if (!this.activeObject) {
        this.clearChain();
      } else {
        this.addChainObject(this.activeObject);
        if (this.mouseConstraint && this.mouseConstraint.body === null) {
          this.mouseConstraint.body = this.activeObject.physcics.body;
        }
      }
    }
  }

  @Watch('backgroundTexture', { immediate: true })
  onBackgroundTextureChanged(): void {
    const loadTexture = (): void => {
      if (this.backgroundTexture) {
        pixiUtil
          .loadTexture(this.backgroundTexture, this.textureToken)
          .then(async (sprite) => {
            this.backgroundSprite = sprite;
            this.calculateBackgroundSize();
            this.backgroundSpriteEndlessPanning =
              await this.createBackgroundTexture(sprite);
          });
      }
    };

    if (this.backgroundTexture) {
      loadTexture();
    }
  }

  @Watch('autoPanSpeed', { immediate: true })
  onAutoPanSpeedChanged(): void {
    if (this.autoPlayIsRunning)
      this.startAutoPan(this.resetPositionOnSpeedChanged);
  }

  @Watch('waitForDataLoad', { immediate: true })
  onWaitForDataLoadChanged(): void {
    if (this.isContainerReady) this.$emit('containerReady');
  }

  @Watch('borderCategory', { immediate: true })
  onBorderCategoryChanged(): void {
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
    this.loading = false;
    if (this.isContainerReady) this.$emit('containerReady');
  }

  private async texturesLoadingStart(): Promise<void> {
    this.loading = true;
  }

  domKey = '';
  async mounted(): Promise<void> {
    document.body.style.touchAction = 'none';
    this.eventBus.on(
      EventType.TEXTURES_LOADING_START,
      this.texturesLoadingStart
    );
    this.eventBus.on(EventType.ALL_TEXTURES_LOADED, this.allTexturesLoaded);

    //initialise observer in mounted as otherwise this references observer
    this.hierarchyObserver = new MutationObserver(this.hierarchyChanged);
    //this.resizeObserver = new ResizeObserver(this.sizeChanged);

    this.setupMatter();
    //this.resizeObserver.observe(this.$el.parentElement);
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

    setTimeout(() => {
      if (this.backgroundMovement === BackgroundMovement.Auto) {
        this.startAutoPan(true);
      }
    }, 1000);

    this.domKey = registerDomElement(
      this.$refs.gameContainer as HTMLElement,
      this.setupPixiSpace,
      0
    );
  }

  hierarchyChanged(mutationList: MutationRecord[]): void {
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
      until(() => this.isBackgroundLoaded).then(() => {
        this.setupMouseConstraint(canvasList[canvasList.length - 1]);
      });
      return;
    }
  }

  /*sizeChanged(entryList: ResizeObserverEntry[]): void {
    for (const entry of entryList) {
      //this.setupPixiSpace(entry.contentRect.width, entry.contentRect.height);
      //this.resizeObserver.disconnect();
    }
  }*/

  unmounted(): void {
    document.body.style.removeProperty('touch-action');
    this.hierarchyObserver.disconnect();
    //this.resizeObserver.disconnect();
    clearInterval(this.intervalPan);
    pixiUtil.cleanupToken(this.textureToken);
    Matter.Events.off(this.engine, 'collisionStart', this.collisionStart);
    Matter.Events.off(this.engine, 'afterUpdate', this.afterPhysicUpdate);
    Matter.Events.off(this.engine, 'beforeUpdate', this.beforePhysicUpdate);
    Matter.Events.off(this.engine.world, 'afterAdd', this.sortBodies);
    this.eventBus.off(
      EventType.TEXTURES_LOADING_START,
      this.texturesLoadingStart
    );
    this.eventBus.off(EventType.ALL_TEXTURES_LOADED, this.allTexturesLoaded);

    Matter.Runner.stop(this.runner);
    Matter.Composite.clear(this.engine.world);
    Matter.Engine.clear(this.engine);

    unregisterDomElement(this.domKey);
  }

  async setupPixiSpace(width: number, height: number): Promise<void> {
    const dom = this.$refs.gameContainer as HTMLElement;
    if (dom && (width !== this.gameWidth || height !== this.gameHeight)) {
      this.gameWidth = width;
      this.gameHeight = height;
      this.$emit('update:width', this.gameWidth);
      this.$emit('update:height', this.gameHeight);
      until(() => this.isBackgroundLoaded).then(() => {
        this.$emit('update:height', this.gameDisplayHeight);
      });
      this.$emit('update:height', this.gameDisplayHeight);
      const bounds = dom.getBoundingClientRect();
      this.canvasPosition = [bounds.left, bounds.top];
      if (this.collisionBorders !== CollisionBorderType.Background)
        await this.setupBound();
      this.calculateBackgroundSize();
      this.ready = true;
      this.$emit('sizeReady');
      if (this.isContainerReady) this.$emit('containerReady');
    }
  }

  calculateBackgroundSize(): void {
    if (this.backgroundSprite && this.backgroundSprite.orig) {
      const textureWidth = this.backgroundSprite.orig.width;
      const textureHeight = this.backgroundSprite.orig.height;
      const scaleFactorWidth = textureWidth / this.gameWidth;
      const scaleFactorHeight = textureHeight / this.gameDisplayHeight;
      switch (this.backgroundPosition) {
        case BackgroundPosition.None:
          this.backgroundTextureSize = [textureWidth, textureHeight];
          break;
        case BackgroundPosition.Stretch:
          this.backgroundTextureSize = [this.gameWidth, this.gameDisplayHeight];
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
              this.gameDisplayHeight,
            ];
          break;
        case BackgroundPosition.Cover:
          if (scaleFactorWidth > scaleFactorHeight) {
            this.backgroundTextureSize = [
              textureWidth / scaleFactorHeight,
              this.gameDisplayHeight,
            ];
          } else
            this.backgroundTextureSize = [
              this.gameWidth,
              textureHeight / scaleFactorWidth,
            ];
          break;
      }
    } else
      this.backgroundTextureSize = [this.gameWidth, this.gameDisplayHeight];
    this.backgroundPositionOffset = [
      this.gameWidth / 2,
      this.gameDisplayHeight / 2,
    ];
    this.backgroundPositionOffsetMin = [...this.backgroundPositionOffset];
    this.backgroundPositionOffsetMax = [...this.backgroundPositionOffset];
    const deltaX = this.backgroundTextureSize[0] - this.gameWidth;
    const deltaY = this.backgroundTextureSize[1] - this.gameDisplayHeight;
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
    this.$emit('backgroundSizeChanged', this.backgroundTextureSize);
    for (const spaceObject of this.spaceObjects) {
      spaceObject.calculateRelativePosition();
    }
    for (const gameObject of this.gameObjects) {
      if (gameObject.moveWithBackground) {
        gameObject.initPosition();
        if (gameObject.objectSpace === ObjectSpaceType.RelativeToBackground)
          gameObject.physcics.updateOffset(
            this.gameObjectOffsetRelativeToBackground
          );
        else if (gameObject.objectSpace === ObjectSpaceType.RelativeToScreen)
          gameObject.physcics.updateOffset(
            this.gameObjectOffsetRelativeToScreen
          );
      }
    }

    this.setupRegions();
    if (this.collisionBorders === CollisionBorderType.Background)
      this.setupBound();
  }

  setupRegions(): void {
    //const x = this.backgroundTextureSize[0] / 2;
    //const y = this.backgroundTextureSize[1] / 2;
    const width = this.backgroundTextureSize[0];
    const height = this.backgroundTextureSize[1];
    if (!width || !height) return;
    for (const region of this.regionBodyList) {
      const collisionRegion = region.region;
      const center = getPolygonCenter(collisionRegion.path);
      const x =
        this.backgroundTextureSize[0] * (center[0] / 100) +
        (region.relativePosition[0] / 100) * width;
      const y =
        this.backgroundTextureSize[1] * (center[1] / 100) +
        (region.relativePosition[1] / 100) * height;
      if (!region.body) {
        collisionRegion.options.isStatic = true;
        collisionRegion.options.isSensor = true;
        const body = matterUtil.createPolygonBody(
          collisionRegion.options,
          x,
          y,
          width,
          height,
          collisionRegion.path,
          false
        );
        this.addToEngin(body);
        region.body = body;
        region.position = [
          x - this.backgroundPositionOffsetMax[0],
          y - this.backgroundPositionOffsetMax[1],
        ];
        region.size = [this.getBodyWidth(body), this.getBodyHeight(body)];
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
      const mouseConstraint = Matter.MouseConstraint.create(this.engine, {
        mouse: mouse,
        constraint: {
          stiffness: 0.2,
        },
      });
      this.addToEngin(mouseConstraint);
      this.mouseConstraint = mouseConstraint;
    }
  }
  //#endregion load / unload

  //#region register object
  registerGameObject(gameObject: GameObject): void {
    if (this.gameObjects.includes(gameObject)) return;
    if (gameObject.moveWithBackground) {
      if (gameObject.objectSpace === ObjectSpaceType.RelativeToBackground)
        gameObject.transformation.initOffset(
          this.gameObjectOffsetRelativeToBackground
        );
      else if (gameObject.objectSpace === ObjectSpaceType.RelativeToScreen)
        gameObject.transformation.initOffset(
          this.gameObjectOffsetRelativeToScreen
        );
    }
    this.gameObjects.push(gameObject);
    this.setupGameObjectBody(gameObject);
    gameObject.setGameContainer(this);
    if (this.useObjectPooling) {
      if (!this.freePoolBody[gameObject.poolingKey]) {
        this.freePoolBody[gameObject.poolingKey] = [];
      } else if (
        this.freePoolBody[gameObject.poolingKey].length > 0 &&
        gameObject.physcics.bodyId === -1
      ) {
        const poolBody = this.freePoolBody[gameObject.poolingKey].pop();
        gameObject.physcics.assignPoolBody(poolBody);
      }
    }
    if (this.activatedObjectOnRegister) {
      if (!this.selectedObject) {
        this.$emit('update:selectedObject', gameObject);
      }
      if (this.isMouseDown) {
        gameObject.highlighted = true;
        this.activeObject = gameObject;
      } else gameObject.physcics.gameObjectReleased();
    }
    if (
      gameObject.fastObjectBehaviour === FastObjectBehaviour.bounce &&
      !this.bordersScreen
    ) {
      this.setupCollisionBound(CollisionBorderType.Screen, bounceCategory);
    }
  }

  deregisterGameObject(gameObject: GameObject): void {
    if (
      gameObject.physcics.bodyId > -1 &&
      this.engine &&
      gameObject.isPartOfEngin
    ) {
      gameObject.isPartOfEngin = false;
      this.removeFromEngin(gameObject.physcics.body);
    }
    const index = this.gameObjects.indexOf(gameObject);
    if (index > -1) {
      this.gameObjects.splice(index, 1);
    }
    if (this.useObjectPooling) {
      matterUtil.resetBody(gameObject.physcics.body, this.mouseConstraint);
      Matter.Body.setPosition(gameObject.physcics.body, {
        x: -10000,
        y: -10000,
      });
      matterUtil.updatePivot(
        gameObject.physcics.body,
        gameObject.objectAnchor,
        100,
        true
      );
      this.freePoolBody[gameObject.poolingKey].push(gameObject.physcics.body);
    }
  }

  registerSpaceObject(spaceObject: SpaceObject): void {
    if (this.spaceObjects.includes(spaceObject)) return;
    this.spaceObjects.push(spaceObject);
    spaceObject.setGameContainer(this);
  }

  deregisterSpaceObject(spaceObject: SpaceObject): void {
    const index = this.spaceObjects.indexOf(spaceObject);
    if (index > -1) {
      this.spaceObjects.splice(index, 1);
    }
  }
  //#endregion register object

  //#region game object bodies
  async setupGameObjectBody(gameObject: GameObject): Promise<void> {
    await delay(100);
    let body: Matter.Body | null = null;
    switch (gameObject.shape) {
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
      await this.addToEngin(body);
      gameObject.physcics.body = body;
      gameObject.isPartOfEngin = true;
    }
  }

  addRect(gameObject: GameObject): Matter.Body {
    gameObject.options.isStatic = gameObject.isStatic;
    const colliderWidth =
      gameObject.transformation.displayWidth + gameObject.colliderDelta * 2;
    const colliderHeight =
      gameObject.transformation.displayHeight + gameObject.colliderDelta * 2;
    return Matter.Bodies.rectangle(
      gameObject.x,
      gameObject.y,
      colliderWidth,
      colliderHeight,
      { ...gameObject.options }
    );
  }

  addCircle(gameObject: GameObject): Matter.Body {
    gameObject.options.isStatic = gameObject.isStatic;
    const width = gameObject.transformation.displayWidth;
    const height = gameObject.transformation.displayHeight;
    const radius =
      (width > height ? width / 2 : height / 2) + gameObject.colliderDelta;
    return Matter.Bodies.circle(gameObject.x, gameObject.y, radius, {
      ...gameObject.options,
    });
  }

  addPolygon(gameObject: GameObject): Matter.Body {
    gameObject.options.isStatic = gameObject.isStatic;
    return matterUtil.createPolygonBody(
      { ...gameObject.options },
      gameObject.x,
      gameObject.y,
      gameObject.transformation.displayWidth,
      gameObject.transformation.displayHeight,
      [...gameObject.polygonShape]
    );
  }
  //#endregion game object bodies

  //#region force
  addWind(): void {
    const calcForce = (body: Matter.Body): number => {
      const windForce = this.windForce > 0 ? this.windForce : 1;
      const forceMagnitude = (0.05 + body.frictionAir) * windForce;
      return (
        (forceMagnitude + Matter.Common.random() * forceMagnitude) *
        Matter.Common.choose([1, -1])
      );
    };

    for (const gameObject of this.gameObjects) {
      if (
        gameObject.physcics.bodyId > -1 &&
        gameObject.affectedByForce &&
        !gameObject.isStatic &&
        gameObject.physcics.isVisible()
      ) {
        const body = this.getBodyForId(gameObject.physcics.bodyId); //gameObject.body;
        const newVelocity = {
          x: body.velocity.x + calcForce(body),
          y: body.velocity.y + calcForce(body),
        };
        if (body) Matter.Body.setVelocity(body, newVelocity);
      }
    }
  }
  //#endregion force

  //#region events
  readonly minClickTimeDelta = 10;
  isMouseDown = false;
  gameContainerClicked(event: any): void {
    const mousePosition = { x: event.offsetX, y: event.offsetY };
    const clickedBodies = Matter.Query.point(
      this.gameObjects
        .filter((gameObj) => gameObj.physcics.body)
        .map((gameObj) => gameObj.physcics.body),
      mousePosition
    );
    if (clickedBodies.length > 0) {
      const clickedGameObjects = clickedBodies.map((body) => {
        return this.getGameObjectForBody(body);
      });
      for (const obj of clickedGameObjects) {
        obj.physcics.gameObjectClicked();
      }
      this.$emit('gameObjectClick', clickedGameObjects, event);
    } else {
      this.$emit('update:selectedObject', null);
    }
    this.isMouseDown = true;
    setTimeout(() => {
      if (!this.activeObject) {
        const relativeMousePositionToScreen = {
          x:
            ((mousePosition.x + this.gameObjectOffsetRelativeToScreen[0]) /
              this.gameWidth) *
            100,
          y:
            ((mousePosition.y + this.gameObjectOffsetRelativeToScreen[1]) /
              this.gameDisplayHeight) *
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
      this.activeObject.physcics.gameObjectReleased();
    }
  }

  preRenderTexture: PIXI.Texture | null = null;
  preRenderTextureEndless: PIXI.Texture | null = null;
  preRenderStarted = false;
  async preRenderData(container: PIXI.Container): Promise<void> {
    const startTime = Date.now();
    if (this.preRenderStarted) return;
    this.preRenderStarted = true;
    const renderTexture = (renderer: PIXI.IRenderer): PIXI.Texture => {
      const bounds = new PIXI.Rectangle(
        0,
        0,
        Math.round(this.backgroundTextureSize[0]),
        Math.round(this.backgroundTextureSize[1])
      );
      return renderer.generateTexture(container, {
        region: bounds,
      });
    };

    const containerChildren = (
      container: PIXI.Container
    ): PIXI.DisplayObject[] => {
      return container.children.filter(
        (item) => item.constructor.name !== 'Empty'
      );
    };

    const containerContainer = (
      container: PIXI.Container
    ): PIXI.Container[] => {
      return container.children.filter(
        (item) => item.constructor.name === '_Container2'
      ) as PIXI.Container[];
    };

    await until(
      () =>
        !!this.app && !!this.backgroundSprite && this.backgroundSprite?.valid
    );
    //const startPos = [...this.backgroundPositionOffset] as [number, number];
    if (this.app) {
      await until(
        () =>
          containerChildren(container).length > 1 &&
          Date.now() - startTime < 30000
      );
      if (containerChildren(container).length === 0) return;
      const subContainerList = containerContainer(container);
      for (const subContainer of subContainerList) {
        await until(
          () =>
            containerChildren(subContainer).length > 0 &&
            Date.now() - startTime < 30000
        );
        if (containerChildren(subContainer).length === 0) return;
      }
      await delay(1000);
      const localBounds = container.getLocalBounds();
      const mainTile = renderTexture(this.app.renderer);
      const graphics = new PIXI.Graphics();
      graphics.beginTextureFill({
        texture: mainTile,
        alpha: 1,
      });
      graphics.drawRect(0, 0, mainTile.width, mainTile.height);
      graphics.endFill();

      /*this.backgroundPositionOffset = [
        this.backgroundPositionOffsetMax[0] + this.gameWidth / 2,
        this.backgroundPositionOffset[1],
      ];
      await delay(1000);
      const borderTile = renderTexture(this.app.renderer);
      const matrix: PIXI.Matrix = new PIXI.Matrix();
      matrix.translate(
        -this.gameWidth / 2,
        0
      );
      graphics.beginTextureFill({
        texture: borderTile,
        alpha: 1,
        matrix: matrix,
      });
      graphics.drawRect(0, 0, this.gameWidth / 2, borderTile.height);
      graphics.endFill();*/
      if (localBounds.x < 0) {
        const previousTile = this.app.renderer.generateTexture(container, {
          region: new PIXI.Rectangle(
            localBounds.x,
            0,
            -localBounds.x,
            Math.round(this.backgroundTextureSize[1])
          ),
        });

        const matrix: PIXI.Matrix = new PIXI.Matrix();
        matrix.translate(
          Math.round(this.backgroundTextureSize[0]) + localBounds.x,
          0
        );
        graphics.beginTextureFill({
          texture: previousTile,
          alpha: 1,
          matrix: matrix,
        });
        graphics.drawRect(
          Math.round(this.backgroundTextureSize[0]) + localBounds.x,
          0,
          previousTile.width,
          previousTile.height
        );
        graphics.endFill();
      }
      if (
        localBounds.width + localBounds.x >
        Math.round(this.backgroundTextureSize[0])
      ) {
        const nextTile = this.app.renderer.generateTexture(container, {
          region: new PIXI.Rectangle(
            Math.round(this.backgroundTextureSize[0]),
            0,
            localBounds.width -
              (Math.round(this.backgroundTextureSize[0]) - localBounds.x),
            Math.round(this.backgroundTextureSize[1])
          ),
        });

        const matrix: PIXI.Matrix = new PIXI.Matrix();
        matrix.translate(0, 0);
        graphics.beginTextureFill({
          texture: nextTile,
          alpha: 1,
          matrix: matrix,
        });
        graphics.drawRect(0, 0, nextTile.width, nextTile.height);
        graphics.endFill();
      }
      this.preRenderTexture = this.app.renderer.generateTexture(graphics);
      this.preRenderTextureEndless = await this.createBackgroundTexture(
        this.preRenderTexture
      );
    }
    await delay(1000);
    //this.backgroundPositionOffset = startPos;
    if (this.preRenderTexture?.valid) {
      this.preRendered = true;
      container.removeFromParent();
    }
  }
  //#endregion events

  //#region bounds
  bordersScreen: undefined | CollisionBounds = undefined;
  bordersBackground: undefined | CollisionBounds = undefined;
  getBordersForType(type: CollisionBorderType): CollisionBounds | undefined {
    if (type === CollisionBorderType.Background) return this.bordersBackground;
    return this.bordersScreen;
  }

  setBordersForType(type: CollisionBorderType, borders: CollisionBounds): void {
    if (type === CollisionBorderType.Background)
      this.bordersBackground = borders;
    else this.bordersScreen = borders;
  }

  readonly boundsThickness = 100;
  async setupCollisionBound(
    collisionBorderType: CollisionBorderType,
    borderCategory: number
  ): Promise<CollisionBounds | undefined> {
    const gameWidth = this.gameWidth ? this.gameWidth : 100;
    await until(() => this.isGameDisplayHeightReady);
    const gameHeight = this.gameDisplayHeight ? this.gameDisplayHeight : 100;
    const backgroundTextureSize =
      this.endlessPanning && this.backgroundMovement !== BackgroundMovement.None
        ? [
            this.backgroundTextureSize[0] * 3,
            this.backgroundTextureSize[1] *
              (this.backgroundMovement === BackgroundMovement.Pan ? 3 : 1),
          ]
        : this.backgroundTextureSize;
    const boundsWidth =
      collisionBorderType !== CollisionBorderType.Background
        ? gameWidth
        : backgroundTextureSize[0];
    const boundsHeight =
      collisionBorderType !== CollisionBorderType.Background
        ? gameHeight
        : backgroundTextureSize[1];
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
    const containerBorders = this.getBordersForType(collisionBorderType);
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
      if (collisionBorderType !== CollisionBorderType.None)
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
      if (collisionBorderType !== CollisionBorderType.None)
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
      if (collisionBorderType !== CollisionBorderType.None)
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
      if (collisionBorderType !== CollisionBorderType.None)
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
      this.setBordersForType(collisionBorderType, borders);
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
    return undefined;
  }

  borders: undefined | CollisionBounds = undefined;
  async setupBound(): Promise<void> {
    this.borders = await this.setupCollisionBound(
      this.collisionBorders,
      this.borderCategory
    );
  }

  @Watch('collisionBorders', { immediate: true })
  onCollisionBordersChanged(): void {
    if (this.borders) {
      if (this.collisionBorders === CollisionBorderType.None) {
        this.removeFromEngin(this.borders.bottom);
        this.removeFromEngin(this.borders.top);
        this.removeFromEngin(this.borders.right);
        this.removeFromEngin(this.borders.left);
      } else if (!this.getBodyForId(this.borders.bottom.id)) {
        const bottom = matterUtil.copyBody(this.borders.bottom);
        this.addToEngin(bottom);
        this.borders.bottom = bottom;
        const top = matterUtil.copyBody(this.borders.top);
        this.addToEngin(top);
        this.borders.top = top;
        const right = matterUtil.copyBody(this.borders.right);
        this.addToEngin(right);
        this.borders.right = right;
        const left = matterUtil.copyBody(this.borders.left);
        this.addToEngin(left);
        this.borders.left = left;
      }
    }
  }

  previousBackgroundMovement = BackgroundMovement.None;
  @Watch('backgroundMovement', { immediate: true })
  onBackgroundMovementChanged(): void {
    const restartFromPause =
      this.previousBackgroundMovement === BackgroundMovement.Break;
    if (this.isContainerReady) {
      switch (this.backgroundMovement) {
        case BackgroundMovement.Break:
          this.panSpeed = 0;
          this.panVector = [0, 0];
          break;
        case BackgroundMovement.Auto:
          this.startAutoPan(!restartFromPause);
          break;
      }
    }
    this.previousBackgroundMovement = this.backgroundMovement;
  }
  //#endregion bounds

  //#region matterjs
  setupMatter(): void {
    this.engine = Matter.Engine.create();
    this.engine.enableSleeping = this.enableSleeping;
    if (this.detectCollision)
      Matter.Events.on(this.engine, 'collisionStart', this.collisionStart);
    Matter.Events.on(this.engine, 'afterUpdate', this.afterPhysicUpdate);
    Matter.Events.on(this.engine, 'beforeUpdate', this.beforePhysicUpdate);
    Matter.Events.on(this.engine.world, 'afterAdd', this.sortBodies);
    this.$emit('initEngine', this.engine);
    this.setGravity();
    this.runner = Matter.Runner.create({
      deltaMax: 1000 / 15,
      deltaMin: 1000 / 90,
    });
    Matter.Runner.run(this.runner, this.engine);
    if (this.combinedActiveCollisionToChain) {
      const activeComposition = Matter.Composite.create();
      this.addToEngin(activeComposition);
      this.activeComposition = activeComposition;
    }
  }

  readonly defaultGravityScale = 0.0005;
  @Watch('gravity', { immediate: true })
  setGravity(): void {
    if (this.engine) {
      const scaleFactor = 1;
      this.engine.gravity = {
        x: this.gravity[0] * scaleFactor,
        y: this.gravity[1] * scaleFactor,
        scale: this.useGravity
          ? this.defaultGravityScale * (1 - this.gravity[2]) * scaleFactor
          : 0,
      };
    }
  }

  async addToEngin(
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
        )[],
    parent: Matter.Composite | null = null
  ): Promise<void> {
    await until(() => !!this.engine);
    if (!parent && this.engine) {
      parent = this.engine.world;
    }
    if (parent && !this.getBodyForId(physicObject.id)) {
      Matter.Composite.add(parent, physicObject);
    }
  }

  sortBodies(): void {
    this.gameObjects.sort((a, b) => a.zIndex - b.zIndex);
    this.engine.world.bodies.sort((a, b) => a.zIndex - b.zIndex);
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
        )[],
    parent: Matter.Composite | null = null
  ): void {
    try {
      if (!parent && this.engine) {
        parent = this.engine.world;
      }
      if (parent) {
        const body = this.getBodyForId(physicObject.id);
        if (body) Matter.Composite.remove(parent, body);
        else Matter.Composite.remove(parent, physicObject);
      }
    } catch (e) {
      //
    }
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
      const gameObjectIsPartOfChain =
        this.combinedActiveCollisionToChain &&
        this.chainObjects.includes(gameObject);
      if (gameObjectIsPartOfChain) {
        this.clearChain(
          (item) =>
            !item.physcics.handleCollision(
              collisionObject,
              hitPoint,
              hitPointScreen,
              objectBody,
              collisionBody
            )
        );
      } else if (gameObject) {
        gameObject.highlighted = false;
        gameObject.physcics.handleCollision(
          collisionObject,
          hitPoint,
          hitPointScreen,
          objectBody,
          collisionBody
        );
      }
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

  checkChainCollision(collisions: Matter.Collision[]): void {
    if (this.activeObject && this.combinedActiveCollisionToChain) {
      const activeId = this.activeObject.physcics.bodyId;
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
            !this.chainObjects.find(
              (item) => item.physcics.bodyId === collidingObj.id
            )
          ) {
            const gameObject = this.getGameObjectForBody(collidingObj);
            if (gameObject) {
              this.addChainObject(gameObject);
              this.createChain();
            }
          }
        }
      }
    }
  }

  createChain(): void {
    if (!this.activeComposition) return;
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
  loopCount = 0;
  loopTime = 0;
  updateTime = Date.now();
  afterPhysicUpdate(): void {
    if (!this.isBackgroundLoaded) return;
    for (const gameObject of this.gameObjects) {
      if (gameObject.moveWithBackground && !this.isBackgroundLoaded) continue;
      gameObject.checkTrigger();
      gameObject.physcics.afterPhysicUpdate();
      if (
        this.showAllEnginColliders &&
        this.nextDrawUpdateTime < this.loopTime
      ) {
        this.nextDrawUpdateTime = this.loopTime + this.oneTickDelta * 50; // += this.oneTickDelta * 50;
        this.drawAllCollider();
      }
    }
  }

  nextWindUpdateTime = 0;
  nextPanUpdateTime = 0;
  nextDrawUpdateTime = 0;
  readonly oneTickDelta = 50;
  frameDelta = 0;
  frameDeltaList: number[] = [];
  beforePhysicUpdate(): void {
    const updateTime = Date.now();
    const deltaTime = updateTime - this.updateTime;
    this.updateTime = updateTime;
    this.loopTime += deltaTime;
    this.loopCount++;
    this.frameDeltaList.splice(0, 0, deltaTime);
    if (this.frameDeltaList.length > 30) this.frameDeltaList.length = 30;
    this.frameDelta =
      this.frameDeltaList.reduce((sum, a) => sum + a, 0) /
      this.frameDeltaList.length;
    //let velocityIterations = 4;
    //if (deltaTime > 50) velocityIterations -= Math.round((deltaTime - 50) / 10);
    //this.engine.velocityIterations =
    //  velocityIterations > 0 ? velocityIterations : 1;
    for (const gameObject of this.gameObjects) {
      if (gameObject.moveWithBackground && !this.isBackgroundLoaded) continue;
      gameObject.physcics.beforePhysicUpdate();
    }

    if (this.windForce > 0 && this.nextWindUpdateTime < this.loopTime) {
      this.nextWindUpdateTime = this.loopTime + this.oneTickDelta * 20; // += this.oneTickDelta * 10;
      this.addWind();
    }
    if (this.panSpeed > 0 && this.nextPanUpdateTime < this.loopTime) {
      while (this.nextPanUpdateTime < this.loopTime) {
        const panTimeDelta =
          this.oneTickDelta * this.getPanInterval(this.panSpeed);
        this.nextPanUpdateTime = this.loopTime + panTimeDelta; // += panTimeDelta;
        this.pan();
      }
    }
    /*console.log(
      'matter update',
      deltaTime,
      Date.now() - updateTime,
      this.engine.world.bodies.length
    );*/
  }
  //#endregion loop

  //#region pan and scroll
  get panDefaultInterval(): number {
    let interval = Math.round(1400 / this.gameWidth);
    if (interval < 1) interval = 1;
    return interval;
  }

  getPanInterval(panSpeed: number): number {
    let interval = this.panDefaultInterval;
    if (panSpeed > 0) interval = Math.round(interval / panSpeed);
    if (interval < 1) interval = 1;
    return interval;
  }

  autoPlayIsRunning = false;
  async startAutoPan(resetPosition = false): Promise<void> {
    await until(() => this.isContainerReady);
    if (resetPosition)
      this.backgroundPositionOffset = [...this.backgroundPositionOffsetMax];
    this.beginPan([
      this.autoPanDirection[0] * -this.autoPanSpeed,
      this.autoPanDirection[1] * -this.autoPanSpeed,
    ]);
    this.autoPlayIsRunning = true;
  }

  panVector: [number, number] = [0, 0];
  panSpeed = 0;
  beginPan(vector: [number, number]): void {
    /*this.panVector = [
      Math.round(vector[0] * 0.01 * this.gameWidth),
      Math.round(vector[1] * 0.01 * this.gameDisplayHeight),
    ];*/
    const distanceX = Math.round(vector[0] * 5);
    const distanceY = Math.round(vector[1] * 5);
    const panSpeed =
      Math.abs(distanceX) > Math.abs(distanceY)
        ? Math.abs(distanceX)
        : Math.abs(distanceY);
    if (panSpeed > 0) {
      const panIntervalFactor =
        this.panDefaultInterval / this.getPanInterval(panSpeed);
      this.panVector = [
        Math.round(distanceX / panIntervalFactor),
        Math.round(distanceY / panIntervalFactor),
      ];
    } else {
      this.panVector = [0, 0];
    }
    if (vector[0] > 0 && this.panVector[0] === 0) this.panVector[0] = 1;
    if (vector[0] < 0 && this.panVector[0] === 0) this.panVector[0] = -1;
    if (vector[1] > 0 && this.panVector[1] === 0) this.panVector[1] = 1;
    if (vector[1] < 0 && this.panVector[1] === 0) this.panVector[1] = -1;
    //clearInterval(this.intervalPan);
    //this.intervalPan = setInterval(this.pan, this.intervalTimePan / this.panInterval);0
    this.nextPanUpdateTime = this.loopTime;
    this.panSpeed = panSpeed;
  }

  endPan(e: MouseEvent | null = null): void {
    let endPan = !e || e.type !== 'mouseout';
    if (
      e &&
      e.type === 'mouseout' &&
      this.$refs.pixi &&
      e.target === (this.$refs.pixi as any).canvas
    ) {
      const bounds = (this.$refs.pixi as any).canvas.getBoundingClientRect();
      const insideBounds =
        e.clientX > bounds.left &&
        e.clientX < bounds.right &&
        e.clientY > bounds.top &&
        e.clientY < bounds.bottom;
      endPan = !insideBounds;
    }
    if (endPan && this.backgroundMovement === BackgroundMovement.Pan) {
      this.panSpeed = 0;
      this.panVector = [0, 0];
      //clearInterval(this.intervalPan);
    }
  }

  pan(): void {
    const x = this.backgroundPositionOffset[0] + this.panVector[0];
    const y = this.backgroundPositionOffset[1] + this.panVector[1];
    const previousPosition = [...this.backgroundPositionOffset] as [
      number,
      number
    ];
    if (this.endlessPanning) {
      const maxX = this.backgroundPositionOffsetMax[0] + this.gameWidth;
      const maxY = this.backgroundPositionOffsetMax[1] + this.gameDisplayHeight;
      if (
        x < this.backgroundPositionOffsetMin[0] ||
        x > maxX ||
        y < this.backgroundPositionOffsetMin[1] ||
        y > maxY
      ) {
        if (x < this.backgroundPositionOffsetMin[0])
          this.backgroundPositionOffset[0] =
            maxX - (this.backgroundPositionOffsetMin[0] - x);
        if (x > maxX)
          this.backgroundPositionOffset[0] =
            this.backgroundPositionOffsetMin[0] + (x - maxX);
        if (y < this.backgroundPositionOffsetMin[1])
          this.backgroundPositionOffset[1] =
            maxY - (this.backgroundPositionOffsetMin[1] - y);
        if (y > maxY)
          this.backgroundPositionOffset[1] =
            this.backgroundPositionOffsetMin[1] + (y - maxY);
      } else {
        this.backgroundPositionOffset = [x, y];
      }
    } else {
      if (
        x < this.backgroundPositionOffsetMin[0] ||
        x > this.backgroundPositionOffsetMax[0] ||
        y < this.backgroundPositionOffsetMin[1] ||
        y > this.backgroundPositionOffsetMax[1]
      ) {
        if (x < this.backgroundPositionOffsetMin[0])
          this.backgroundPositionOffset[0] =
            this.backgroundPositionOffsetMin[0];
        if (x > this.backgroundPositionOffsetMax[0])
          this.backgroundPositionOffset[0] =
            this.backgroundPositionOffsetMax[0];
        if (y < this.backgroundPositionOffsetMin[1])
          this.backgroundPositionOffset[1] =
            this.backgroundPositionOffsetMin[1];
        if (y > this.backgroundPositionOffsetMax[1])
          this.backgroundPositionOffset[1] =
            this.backgroundPositionOffsetMax[1];
        if (this.backgroundMovement === BackgroundMovement.Pan) this.endPan();
        else this.panVector = [this.panVector[0] * -1, this.panVector[1] * -1];
      } else {
        this.backgroundPositionOffset = [x, y];
      }
    }

    if (
      previousPosition[0] !== this.backgroundPositionOffset[0] ||
      previousPosition[1] !== this.backgroundPositionOffset[1]
    ) {
      this.updateObjectPosition(previousPosition);
    }

    this.notifyCurrentOffset();
    this.$emit('update:offset', this.gameObjectOffsetRelativeToBackground);
  }

  updateObjectPosition(previousPosition: [number, number]): void {
    for (const gameObj of this.gameObjects) {
      if (
        gameObj.moveWithBackground &&
        gameObj.objectSpace === ObjectSpaceType.RelativeToBackground
      )
        gameObj.physcics.updateOffset(
          this.gameObjectOffsetRelativeToBackground,
          this.endlessPanning
            ? this.gameObjectOffsetRelativeToBackgroundCircle
            : null
        );
      else if (
        gameObj.moveWithBackground &&
        gameObj.objectSpace === ObjectSpaceType.RelativeToScreen
      )
        gameObj.physcics.updateOffset(
          this.gameObjectOffsetRelativeToScreen,
          this.endlessPanning
            ? this.gameObjectOffsetRelativeToScreenCircle
            : null
        );
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

    if (this.pixiFilterList) {
      for (const filter of this.pixiFilterList) {
        if (filter.center) {
          filter.center[0] += deltaX;
          filter.center[1] += deltaY;
        }
      }
    }

    if (this.bordersBackground) {
      Matter.Body.setPosition(this.bordersBackground.bottom, {
        x:
          this.bordersBackground.bottomPosition[0] +
          this.backgroundPositionOffset[0],
        y:
          this.bordersBackground.bottomPosition[1] +
          this.backgroundPositionOffset[1],
      });
      Matter.Body.setPosition(this.bordersBackground.top, {
        x:
          this.bordersBackground.topPosition[0] +
          this.backgroundPositionOffset[0],
        y:
          this.bordersBackground.topPosition[1] +
          this.backgroundPositionOffset[1],
      });
      Matter.Body.setPosition(this.bordersBackground.left, {
        x:
          this.bordersBackground.leftPosition[0] +
          this.backgroundPositionOffset[0],
        y:
          this.bordersBackground.leftPosition[1] +
          this.backgroundPositionOffset[1],
      });
      Matter.Body.setPosition(this.bordersBackground.right, {
        x:
          this.bordersBackground.rightPosition[0] +
          this.backgroundPositionOffset[0],
        y:
          this.bordersBackground.rightPosition[1] +
          this.backgroundPositionOffset[1],
      });
    }
  }

  moveDisplayArea(event: PIXI.FederatedPointerEvent): void {
    if (event.buttons === 1) {
      const delta = event.movement.x / this.mapScale;
      let backgroundPositionOffset = this.backgroundPositionOffset[0] - delta;
      if (backgroundPositionOffset < this.backgroundPositionOffsetMin[0])
        backgroundPositionOffset = this.backgroundPositionOffsetMin[0];
      if (backgroundPositionOffset > this.backgroundPositionOffsetMax[0])
        backgroundPositionOffset = this.backgroundPositionOffsetMax[0];
      if (backgroundPositionOffset !== this.backgroundPositionOffset[0]) {
        const previousPosition = [...this.backgroundPositionOffset] as [
          number,
          number
        ];
        this.backgroundPositionOffset[0] = backgroundPositionOffset;
        this.updateObjectPosition(previousPosition);
        this.notifyCurrentOffset();
        this.$emit('update:offset', this.gameObjectOffsetRelativeToBackground);
      }
    }
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
    const deltaY = this.backgroundTextureSize[1] - this.gameDisplayHeight;
    //const min = [-deltaX / 2, -deltaY / 2];
    const max = [this.gameWidth + deltaX / 2, this.gameDisplayHeight + deltaY];
    const minPosition = [
      position[0] - this.gameWidth / 2,
      position[1] - this.gameDisplayHeight / 2,
    ];
    const maxPosition = [
      position[0] + this.gameWidth / 2,
      position[1] + this.gameDisplayHeight / 2,
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

  colliderDrawingArea: PIXI.Graphics | null = null;
  drawAllCollider(inputGraphics: PIXI.Graphics | null = null): void {
    const drawBodies = (
      bodies: Matter.Body[],
      graphics: PIXI.Graphics
    ): void => {
      for (const body of bodies) {
        if (body.bodies) drawBodies(body.bodies, graphics);
        else {
          const path = body.vertices.map((item) => {
            return {
              x:
                item.x < 0
                  ? 0
                  : item.x > this.gameWidth
                  ? this.gameWidth
                  : item.x,
              y:
                item.y < 0
                  ? 0
                  : item.y > this.gameDisplayHeight
                  ? this.gameDisplayHeight
                  : item.y,
            };
          });
          graphics.lineStyle(2, '#ff0000');
          graphics.drawPolygon(path);
        }
      }
    };

    const graphics = inputGraphics ?? this.colliderDrawingArea;
    if (inputGraphics) this.colliderDrawingArea = inputGraphics;
    if (graphics) {
      graphics.clear();
      drawBodies(this.engine.world.bodies, graphics);
      if (this.activeComposition)
        drawBodies(this.activeComposition.bodies, graphics);
    }
  }

  async createBackgroundTexture(texture: PIXI.Texture): Promise<PIXI.Texture> {
    if (this.endlessPanning) {
      await until(() => !!this.app);
      if (this.app) {
        const backgroundPositions: [number, number][] = [
          [0, 0],
          [texture.width, 0],
        ];
        if (this.backgroundMovement === BackgroundMovement.Pan) {
          backgroundPositions.push(
            [0, texture.height],
            [texture.width, texture.height]
          );
        }
        const background = new PIXI.Graphics();
        for (const position of backgroundPositions) {
          const matrix: PIXI.Matrix = new PIXI.Matrix();
          matrix.translate(position[0], position[1]);
          background.beginTextureFill({
            texture: texture,
            alpha: 1,
            matrix: matrix,
          });
          background.drawRect(
            position[0],
            position[1],
            texture.width,
            texture.height
          );
          background.endFill();
        }
        if (this.app.renderer)
          texture = this.app.renderer.generateTexture(background);
      }
    }
    return texture;
  }

  drawDisplayRegion(graphics: PIXI.Graphics): void {
    graphics.clear();
    if (this.backgroundSprite) {
      const textureScale = this.gameWidth / this.backgroundSprite.orig.width;
      const matrix: PIXI.Matrix = new PIXI.Matrix();
      matrix.scale(textureScale, textureScale);
      matrix.translate(
        -this.gameObjectOffsetRelativeToBackground[0] * this.mapScale,
        0
      );
      graphics.beginTextureFill({
        texture: this.backgroundSprite,
        alpha: 1,
        matrix: matrix,
      });
    }
    graphics.lineStyle(10, '#ff0000');
    graphics.drawRect(0, 0, this.gameWidth * this.mapScale, this.mapHeight);
    graphics.endFill();
  }
  //#endregion draw
}
</script>

<style scoped lang="scss">
.gameContainer {
  position: relative;
  overflow: hidden;
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
  background-color: #ffffff66;
  pointer-events: none;
  bottom: 1rem;
  left: 1rem;
  font-size: var(--font-size-xxxlarge);
  position: absolute;
  z-index: 100;
}
</style>
