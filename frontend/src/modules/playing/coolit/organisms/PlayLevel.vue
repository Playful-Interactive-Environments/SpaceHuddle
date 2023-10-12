<template>
  <div
    class="gameArea"
    v-if="playStateType === PlayStateType.play && levelType"
  >
    <GameContainer
      v-model:width="gameWidth"
      v-model:height="gameHeight"
      :detect-collision="true"
      :use-gravity="false"
      :use-wind="true"
      :border-category="CollisionGroups.BORDER"
      :background-texture="gameConfig.obstacles[levelType].settings.background"
      :background-position="BackgroundPosition.Cover"
      :background-movement="BackgroundMovement.Auto"
      :collisionRegions="collisionRegions"
      @initRenderer="initRenderer"
      @updateOffset="updateOffset"
      @containerReady="containerReady"
      @backgroundSizeChanged="containerTextureSizeChanged"
      :show-bounds="false"
      :collision-borders="CollisionBorderType.Background"
      :pixi-filter-list="collisionAnimation"
    >
      <template v-slot:default>
        <container v-if="gameWidth && circleGradiant">
          <GameObject
            v-for="obstacle in obstacleList"
            :key="obstacle.uuid"
            v-model:id="obstacle.id"
            :type="obstacle.shape"
            :polygon-shape="obstacle.polygonShape"
            :show-bounds="false"
            :anchor="obstacle.pivot"
            :object-space="ObjectSpace.RelativeToBackground"
            :x="obstacle.position[0]"
            :y="obstacle.position[1]"
            :rotation="obstacle.rotation"
            :scale="obstacle.scale"
            :options="getObstacleTypeOptions(obstacle.type, obstacle.name)"
            :is-static="true"
            :affectedByForce="false"
            :source="obstacle"
            @collision="obstacleCollision"
          >
            <CustomSprite
              :colorOverlay="calculateTintColor(obstacle)"
              :texture="obstacle.texture"
              :anchor="obstacle.pivot"
              :width="obstacle.width"
              :aspect-ration="getObjectAspect(obstacle.type, obstacle.name)"
              :object-space="ObjectSpace.RelativeToBackground"
              :filters="obstacle.hitAnimation"
            >
            </CustomSprite>
          </GameObject>
          <GameObject
            v-for="ray in rayList"
            :key="ray.uuid"
            type="circle"
            :object-space="ObjectSpace.RelativeToBackground"
            v-model:x="ray.position[0]"
            v-model:y="ray.position[1]"
            :rotation="ray.angle"
            :scale="ray.intensity"
            :options="getRayTypeOptions(ray.type)"
            :is-static="false"
            :affectedByForce="false"
            :show-bounds="false"
            :source="ray"
            :fix-size="rayParticleSize"
            @collision="rayCollision"
            @initialised="rayInitialised"
            @initError="rayInitError"
            @outsideDrawingSpace="leaveAtmosphere"
          >
            <container v-if="!ray.hit">
              <sprite
                :texture="circleGradiant"
                :x="ray.displayPoints[0].x * 0.2"
                :width="rayParticleSize"
                :height="rayParticleSize"
                :anchor="0.5"
                :tint="ray.type === RayType.light ? yellowColor : redColor"
              ></sprite>
            </container>
            <template #background>
              <simple-rope
                v-if="lightTexture"
                :texture="lightTexture"
                :x="0"
                :y="0"
                :scale="0.2"
                :tint="ray.type === RayType.light ? yellowColor : redColor"
                :points="ray.displayPoints"
              />
            </template>
          </GameObject>
          <custom-particle-container
            v-for="moleculeName of Object.keys(backgroundParticle)"
            :key="moleculeName"
            :deep-clone-config="false"
            :default-texture="moleculeTextures[moleculeName]"
            :parentEventBus="eventBus"
            :config="backgroundParticle[moleculeName]"
            :x="(-panOffset[0] * (containerTextureSize[0] - gameWidth)) / 100"
            :y="(-panOffset[1] * (containerTextureSize[1] - gameHeight)) / 100"
            :auto-update="false"
          />
          <GameObject
            v-for="molecule of moleculeList"
            :key="molecule.id"
            type="circle"
            :object-space="ObjectSpace.RelativeToBackground"
            v-model:x="molecule.position[0]"
            v-model:y="molecule.position[1]"
            :options="getMoleculeTypeOptions(molecule.type)"
            :is-static="false"
            :fix-size="molecule.size * moleculeSize * 2"
            :source="molecule"
          >
            <CustomSprite
              v-if="getMoleculeTexture(molecule.type)"
              :texture="getMoleculeTexture(molecule.type)"
              :anchor="0.5"
              :tint="molecule.color"
              :width="molecule.size * moleculeSize * 2"
              :height="molecule.size * moleculeSize * 2"
              :alpha="molecule.controllable ? 1 : 0.4"
            />
          </GameObject>
        </container>
      </template>
    </GameContainer>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import * as PIXI from 'pixi.js';
import { Prop, Watch } from 'vue-property-decorator';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import GameContainer, {
  BackgroundPosition,
  BackgroundMovement,
  CollisionRegion,
  CollisionBorderType,
} from '@/components/shared/atoms/game/GameContainer.vue';
import * as placeable from '@/types/game/Placeable';
import * as pixiUtil from '@/utils/pixi';
import { ObjectSpace } from '@/types/enum/ObjectSpace';
import CustomSprite from '@/components/shared/atoms/game/CustomSprite.vue';
import { until, delay } from '@/utils/wait';
import * as tutorialService from '@/services/tutorial-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Tutorial } from '@/types/api/Tutorial';
import * as cashService from '@/services/cash-service';
import * as themeColors from '@/utils/themeColors';
import gameConfig from '@/modules/playing/coolit/data/gameConfig.json';
import backgroundParticle from '@/modules/playing/coolit/data/backgroundParticle.json';
import * as PIXIParticles from '@pixi/particle-emitter';
import { Idea } from '@/types/api/Idea';
import * as configParameter from '@/utils/game/configParameter';
import { v4 as uuidv4 } from 'uuid';
import Vec2 from 'vec2';
import Color from 'colorjs.io';
import { toRadians } from '@/utils/angle';
import Matter from 'matter-js';
import { ShockwaveFilter } from 'pixi-filters';
import * as matterUtil from '@/utils/matter';
import CustomParticleContainer from '@/components/shared/atoms/game/CustomParticleContainer.vue';

/* eslint-disable @typescript-eslint/no-explicit-any*/
const tutorialType = 'find-it-object';

enum RayType {
  light = 'light',
  heat = 'heat',
}

interface Ray {
  uuid: string;
  type: RayType;
  hit: boolean;
  position: [number, number];
  direction: [number, number];
  angle: number;
  initialised: boolean;
  startTime: number;
  points: { x: number; y: number }[];
  displayPoints: { x: number; y: number }[];
  displayPointsCount: number;
  animationIndex: number;
  body: Matter.Body | null;
  intensity: number;
}

export interface MoleculeState {
  startCount: number;
  movedCount: number;
  hitCount: number;
  emitCount: number;
  decreaseCount: number;
}

export interface ObstacleState {
  totalCount: number;
  hitCount: number;
}

enum PlayStateType {
  play,
  win,
  lost,
}

export interface PlayStateResult {
  stars: number;
  time: number;
  moleculeState: { [key: string]: MoleculeState };
  obstacleState: { [key: string]: ObstacleState };
  hitCount: number;
  temperature: number;
}

enum ObstacleType {
  obstacle = 'obstacle',
  carbonSink = 'carbonSink',
  carbonSource = 'carbonSource',
}

interface CoolItHitRegion {
  maxHitCount: number;
  hitCount: number;
  hitAnimation: ShockwaveFilter[];
  heatRationCoefficient: number;
  reflectionProbability: number;
}

interface MoleculeData extends CoolItHitRegion {
  id: string;
  type: string;
  position: [number, number];
  globalWarmingFactor: number;
  size: number;
  controllable: boolean;
  absorbedByTree: boolean;
  color: string;
}

interface CoolItObstacle extends placeable.Placeable, CoolItHitRegion {}

function convertToCoolItObstacle(
  value: placeable.PlaceableBase,
  categoryConfig: placeable.PlaceableThemeConfig,
  texture: string | PIXI.Texture
): CoolItObstacle {
  const result = placeable.convertToDetailData(value, categoryConfig, texture);
  const configParameter = placeable.getConfigParameter(
    value,
    categoryConfig
  ) as any;
  return {
    uuid: result.uuid,
    id: result.id,
    type: result.type,
    name: result.name,
    texture: result.texture,
    width: result.width,
    shape: result.shape,
    polygonShape: result.polygonShape,
    pivot: result.pivot,
    position: result.position,
    rotation: result.rotation,
    scale: result.scale,
    hitCount: 0,
    maxHitCount: configParameter.maxHitCount ?? 10,
    placingRegions: result.placingRegions,
    hitAnimation: [],
    heatRationCoefficient: configParameter.heatRationCoefficient ?? 1,
    reflectionProbability: 1,
  };
}

@Options({
  computed: {
    ObjectSpace() {
      return ObjectSpace;
    },
    BackgroundPosition() {
      return BackgroundPosition;
    },
    BackgroundMovement() {
      return BackgroundMovement;
    },
  },
  components: {
    GameObject,
    GameContainer,
    CustomSprite,
    CustomParticleContainer,
  },
  emits: ['finished'],
})
export default class PlayLevel extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: null }) readonly level!: Idea | null;
  @Prop({ default: EndpointAuthorisationType.PARTICIPANT })
  authHeaderTyp!: EndpointAuthorisationType;
  renderer!: PIXI.Renderer;
  gameWidth = 0;
  gameHeight = 0;
  panOffsetMin = [0, 0];
  panOffsetMax = [100, 100];
  panOffset = [0, 0];
  tutorialSteps: Tutorial[] = [];
  levelType = '';
  gameConfig = gameConfig;
  active = true;

  obstacleList: CoolItObstacle[] = [];
  stylesheets: { [key: string]: PIXI.Spritesheet } = {};
  moleculeStylesheets!: PIXI.Spritesheet;
  lightTexture!: PIXI.Texture;
  startTime = Date.now();
  moleculeSize = 50;

  rayList: Ray[] = [];
  rayPath: { [key: string]: { x: number; y: number }[][] } = {};
  circleGradiant: PIXI.Texture | null = null;
  moleculeTextures: { [key: string]: PIXI.Texture } = {};
  rayParticleSize = 10;
  collisionAnimation: any[] = [];
  moleculeList: MoleculeData[] = [];
  backgroundParticle: { [key: string]: PIXIParticles.EmitterConfigV3 } = {};

  playStateType = PlayStateType.play;
  PlayStateType = PlayStateType;
  RayType = RayType;
  interval!: any;
  readonly intervalTime = 100;

  CollisionGroups = Object.freeze({
    MOUSE: 1 << 0,
    OBSTACLE: 1 << 1,
    CARBON_SINK: 1 << 2,
    CARBON_SOURCE: 1 << 3,
    LIGHT_RAY: 1 << 4,
    HEAT_RAY: 1 << 5,
    MOLECULE: 1 << 6,
    GROUND: 1 << 7,
    BORDER: 1 << 8,
  });
  CollisionBorderType = CollisionBorderType;

  /*get playStateResult(): PlayStateResult {
    return {
      stars: Math.floor((this.collectedCount / this.totalCount) * 3),
      time: Date.now() - this.startTime,
      moleculeState: {},
      obstacleState: {},
      hitCount: 0,
      temperature: 0,
    };
  }*/

  get backgroundColor(): string {
    return themeColors.getBackgroundColor();
  }

  get yellowColor(): string {
    return themeColors.getYellowColor();
  }

  get redColor(): string {
    return themeColors.getRedColor();
  }

  get collisionRegions(): CollisionRegion[] {
    const regions: CollisionRegion[] = [];
    for (const ration of gameConfig.obstacles[this.levelType].settings
      .heatRation) {
      regions.push({
        path: ration.region,
        options: {
          name: 'ground',
          collisionFilter: {
            group: 0,
            category: this.CollisionGroups.GROUND,
          },
        },
        source: {
          heatRationCoefficient: ration.coefficient,
          maxHitCount: ration.maxHitCount,
          hitCount: 0,
          hitAnimation: [],
          reflectionProbability: ration.reflectionProbability,
        } as CoolItHitRegion,
        filter: [],
        color: '#ffffff',
        alpha: 0,
      });
    }
    return regions;
  }

  getObstacleTypeOptions(obstacleType: string, obstacleName: string): any {
    const settings =
      gameConfig.obstacles[this.levelType].categories[obstacleType].settings;
    return {
      isSensor: true,
      name: obstacleName,
      collisionFilter: {
        group: 0,
        category:
          settings.type === ObstacleType.obstacle
            ? this.CollisionGroups.OBSTACLE
            : settings.type === ObstacleType.carbonSink
            ? this.CollisionGroups.CARBON_SINK
            : this.CollisionGroups.CARBON_SOURCE,
      },
    };
  }

  getRayTypeOptions(rayType: RayType): any {
    switch (rayType) {
      case RayType.light:
        return {
          name: 'light',
          collisionFilter: {
            group: 0,
            category: this.CollisionGroups.LIGHT_RAY,
            mask:
              this.CollisionGroups.OBSTACLE |
              this.CollisionGroups.CARBON_SINK |
              this.CollisionGroups.CARBON_SOURCE |
              this.CollisionGroups.GROUND |
              this.CollisionGroups.MOUSE,
          },
        };
      case RayType.heat:
        return {
          name: 'heat',
          collisionFilter: {
            group: 0,
            category: this.CollisionGroups.HEAT_RAY,
            mask: this.CollisionGroups.MOLECULE,
          },
        };
    }
  }

  getMoleculeTypeOptions(moleculeType: string): any {
    const moleculeConfig = gameConfig.molecules[moleculeType];
    let mask = this.CollisionGroups.MOLECULE | this.CollisionGroups.BORDER;
    if (moleculeConfig.controllable)
      mask = mask | this.CollisionGroups.HEAT_RAY | this.CollisionGroups.MOUSE;
    if (moleculeConfig.absorbedByTree)
      mask = mask | this.CollisionGroups.CARBON_SINK;
    return {
      name: moleculeType,
      collisionFilter: {
        group: 0,
        category: this.CollisionGroups.MOLECULE,
        mask: mask,
      },
    };
  }

  getMoleculeTexture(objectName: string): PIXI.Texture | string {
    if (this.moleculeTextures[objectName])
      return this.moleculeTextures[objectName];
    if (this.moleculeStylesheets)
      return this.moleculeStylesheets.textures[objectName];
    return '';
  }

  getMoleculeAspect(objectName: string): number {
    return 1 / pixiUtil.getSpriteAspect(this.moleculeStylesheets, objectName);
  }

  calculateRayPath(type: RayType, shift = 0): { x: number; y: number }[] {
    const rayPoints: { x: number; y: number }[] = [];
    const iPart = (Math.PI * 2) / this.rayPoints;
    const waveCount = type === RayType.light ? 4 : 1.5;
    for (let i = 0; i < this.rayPoints; i++) {
      rayPoints.push({
        x: Math.sin(i * iPart * waveCount + shift) * 40,
        y: -i * this.rayLength,
      });
    }
    return rayPoints;
  }

  readonly animationSteps = 10;
  mounted(): void {
    const initPath = (type: RayType): void => {
      this.rayPath[type] = [];
      for (let i = 0; i < this.animationSteps; i++) {
        this.rayPath[type].push(
          this.calculateRayPath(type, (i * (Math.PI * 2)) / this.animationSteps)
        );
      }
    };
    initPath(RayType.light);
    initPath(RayType.heat);

    tutorialService.registerGetList(this.updateTutorial, this.authHeaderTyp);
    this.interval = setInterval(() => this.updateRays(), this.intervalTime);

    pixiUtil
      .loadTexture('/assets/games/coolit/city/light.png', this.eventBus)
      .then((texture) => {
        this.lightTexture = texture;
      });
    pixiUtil
      .loadTexture('/assets/games/moveit/molecules.json', this.eventBus)
      .then((sheet) => {
        this.moleculeStylesheets = sheet;
        this.generateMoleculeTextures();
      });
  }

  containerTextureSize: [number, number] = [100, 100];
  containerTextureSizeChanged(size: [number, number]): void {
    this.containerTextureSize = size;
  }

  containerReady(): void {
    if (!this.emitStart) {
      this.emitLightRays(200, 0);

      for (const moleculeConfigName of Object.keys(gameConfig.molecules)) {
        const moleculeConfig = gameConfig.molecules[moleculeConfigName];
        if (moleculeConfig.controllable) {
          const moduleCountFactor = moleculeConfig.controllable ? 100 : 1;
          const moleculeCount = moleculeConfig.ration * moduleCountFactor;
          for (let i = 0; i < moleculeCount; i++) {
            this.moleculeList.push({
              id: uuidv4(),
              type: moleculeConfigName,
              position: [Math.random() * 100, Math.random() * 50],
              globalWarmingFactor: moleculeConfig.globalWarmingFactor,
              size: moleculeConfig.size,
              controllable: moleculeConfig.controllable,
              absorbedByTree: moleculeConfig.absorbedByTree,
              color: moleculeConfig.color,
              maxHitCount: 1000,
              hitCount: 0,
              hitAnimation: [],
              heatRationCoefficient: moleculeConfig.globalWarmingFactor,
              reflectionProbability: 1,
            });
          }
        }
      }
    }
  }

  updateTutorial(steps: Tutorial[]): void {
    this.tutorialSteps = steps.filter((step) => step.type === tutorialType);
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTutorial);
  }

  get gameConfigTypes(): string[] {
    return configParameter.getGameConfigTypes(
      gameConfig.obstacles as any,
      this.levelType
    );
  }

  calculateTintColor(
    building: CoolItHitRegion
  ): [number, number, number, number] {
    /*const toHex = (d: number): string => {
      if (d < 0) d = 0;
      if (d > 1) d = 1;
      return ('0' + Number(d * 255).toString(16)).slice(-2).toUpperCase();
    };
    const toColorString = (color: any): string => {
      return `#${toHex(color.r)}${toHex(color.g)}${toHex(color.b)}`;
    };*/
    let mixingFactor = building.hitCount / building.maxHitCount;
    if (mixingFactor > 1) mixingFactor = 1;
    const red = new Color(themeColors.getRedColor()).to('srgb') as any;
    /*const white = new Color('#ffffff').to('srgb');
    const tint = white.range(red, {
      space: 'lch',
      outputSpace: 'srgb',
    }) as any;
    const color = tint(mixingFactor);
    return [color.r, color.g, color.b, 0.4];*/
    return [red.r, red.g, red.b, mixingFactor / 2];
  }

  unmounted(): void {
    this.active = false;
    clearInterval(this.interval);
    this.deregisterAll();
    for (const typeName of this.gameConfigTypes) {
      const settings =
        gameConfig.obstacles[this.levelType].categories[typeName].settings;
      pixiUtil.unloadTexture(settings.spritesheet);
    }
    pixiUtil.unloadTexture('/assets/games/moveit/molecules.json');
  }

  previousLevelType = '';
  isReadyForPlay = false;
  @Watch('level', { immediate: true })
  async onLevelChanged(): Promise<void> {
    if (this.level) {
      this.isReadyForPlay = false;
      this.obstacleList = [];
      const levelType = this.level.parameter.type
        ? this.level.parameter.type
        : configParameter.getDefaultLevelType(gameConfig.obstacles as any);
      if (this.previousLevelType && this.previousLevelType !== levelType) {
        const gameConfigTypes = Object.keys(
          gameConfig.obstacles[levelType].categories
        );
        for (const typeName of gameConfigTypes) {
          const previousSettings =
            gameConfig.obstacles[this.previousLevelType].categories[typeName]
              .settings;
          if (
            previousSettings &&
            previousSettings.spritesheet &&
            this.stylesheets[typeName] &&
            PIXI.Cache.has(previousSettings.spritesheet)
          ) {
            pixiUtil.unloadTexture(previousSettings.spritesheet);
            delete this.stylesheets[typeName];
          }
        }
      }
      this.levelType = levelType;
      const items = configParameter.getItemsForLevel(
        gameConfig.obstacles as any,
        this.level
      );
      const spriteSheetTypes = items
        .map((item) => item.type)
        .filter((value, index, array) => array.indexOf(value) === index);
      for (const spriteSheetType of spriteSheetTypes) {
        await until(() => this.spriteSheetLoaded(spriteSheetType));
      }
      this.obstacleList = items
        .filter((item) => this.hasTexture(item.type, item.name))
        .map((item) =>
          convertToCoolItObstacle(
            item,
            gameConfig.obstacles[levelType],
            this.getTexture(item.type, item.name)
          )
        );
      this.previousLevelType = levelType;
      this.startTime = Date.now();
      this.playStateType = PlayStateType.play;
      this.isReadyForPlay = true;
    }
  }

  @Watch('levelType', { immediate: true })
  onLevelTypeChanged(): void {
    if (this.levelType) {
      for (const typeName of this.gameConfigTypes) {
        const settings =
          gameConfig.obstacles[this.levelType].categories[typeName].settings;
        setTimeout(() => {
          if (
            settings &&
            settings.spritesheet &&
            this.previousLevelType !== this.levelType
          ) {
            pixiUtil
              .loadTexture(settings.spritesheet, this.eventBus)
              .then((sheet) => {
                this.stylesheets[typeName] = sheet;
              });
          }
        }, 100);
      }
    }
  }

  getSpriteSheetForType(objectType: string): PIXI.Spritesheet {
    return this.stylesheets[objectType];
  }

  getTexture(objectType: string, objectName: string): PIXI.Texture | string {
    const spriteSheet = this.getSpriteSheetForType(objectType);
    if (spriteSheet && spriteSheet.textures)
      return spriteSheet.textures[objectName];
    return '';
  }

  spriteSheetLoaded(objectType: string): boolean {
    const spriteSheet = this.getSpriteSheetForType(objectType);
    return !!spriteSheet && !!spriteSheet.textures;
  }

  hasTexture(objectType: string, objectName: string): boolean {
    const spriteSheet = this.getSpriteSheetForType(objectType);
    return (
      !!spriteSheet &&
      !!spriteSheet.textures &&
      objectName in spriteSheet.textures
    );
  }

  getObjectAspect(objectType: string, objectName: string): number {
    const spriteSheet = this.getSpriteSheetForType(objectType);
    return pixiUtil.getSpriteAspect(spriteSheet, objectName);
  }

  initRenderer(renderer: PIXI.Renderer): void {
    this.renderer = renderer;
    this.circleGradiant = pixiUtil.generateCircleGradiantTexture(
      256,
      this.renderer
    );
    this.generateMoleculeTextures();
  }

  async generateMoleculeTextures(): Promise<void> {
    if (
      !this.renderer ||
      !this.circleGradiant ||
      !this.moleculeStylesheets ||
      Object.keys(this.moleculeTextures).length > 0
    )
      return;
    for (const moleculeName of Object.keys(gameConfig.molecules)) {
      if (this.moleculeStylesheets.textures[moleculeName]) {
        this.moleculeTextures[moleculeName] = pixiUtil.generateStackedTexture(
          [
            this.circleGradiant,
            this.moleculeStylesheets.textures[moleculeName],
          ],
          this.renderer,
          60
        );
        if (!gameConfig.molecules[moleculeName].controllable) {
          const moleculeConfig = gameConfig.molecules[moleculeName];
          const particleSettings = JSON.parse(
            JSON.stringify(backgroundParticle)
          );
          particleSettings.maxParticles =
            gameConfig.molecules[moleculeName].ration * 100;
          particleSettings.particlesPerWave = particleSettings.maxParticles;
          particleSettings.behaviors.push({
            type: 'colorStatic',
            config: {
              color: moleculeConfig.color.slice(-6),
            },
          });
          particleSettings.behaviors.push({
            type: 'scaleStatic',
            config: {
              min: moleculeConfig.size * 0.15,
              max: moleculeConfig.size * 0.15,
            },
          });
          if (
            this.containerTextureSize[0] === 100 ||
            this.containerTextureSize[0] === this.gameWidth
          )
            await delay(100);
          particleSettings.behaviors.push({
            type: 'spawnShape',
            config: {
              type: 'rect',
              data: {
                x: 0,
                y: 0,
                w: this.containerTextureSize[0],
                h: (this.containerTextureSize[1] / 3) * 2,
              },
            },
          });
          this.backgroundParticle[moleculeName] = particleSettings;
        }
      }
    }
  }

  readonly rayPoints = 80;
  readonly rayLength = 500 / this.rayPoints;
  emitStart = false;
  emitLightRays(minDelay = 5000, maxDelay = 1000): void {
    this.emitStart = true;
    const delay = minDelay + Math.random() * maxDelay;
    setTimeout(() => {
      const angle = 5 + Math.random() * 10;
      const direction = new Vec2(0, 1).rotate(-toRadians(angle));
      const displayWidth = this.panOffsetMax[0] - this.panOffsetMin[0];
      const position =
        this.panOffsetMin[0] +
        displayWidth / 5 +
        Math.random() * (displayWidth / 2);
      const points = this.calculateInitRayPoints(RayType.light, 1, 0);
      this.rayList.push({
        uuid: uuidv4(),
        type: RayType.light,
        angle: angle,
        position: [position, 0],
        direction: [direction.x, direction.y],
        initialised: false,
        startTime: Date.now(),
        points: points,
        displayPoints: points.map(() => {
          return {
            x: 0,
            y: 0,
          };
        }),
        displayPointsCount: 0,
        animationIndex: 0,
        body: null,
        intensity: 1,
        hit: false,
      });
      if (this.active) this.emitLightRays();
    }, delay);
  }

  calculateInitRayPoints(
    type: RayType,
    intensity: number,
    animationStep: number
  ): { x: number; y: number }[] {
    return this.rayPath[type][animationStep % this.animationSteps].map(
      (item) => {
        return {
          x: item.x / intensity,
          y: item.y / intensity,
        };
      }
    );
  }

  rayInitialised(item: GameObject): void {
    item.source.initialised = true;
    if (item.body) {
      item.source.body = item.body;
      item.body.frictionAir = 0;
      this.calculateRayVelocity(item.source);
    }
  }

  calculateRayVelocity(ray: Ray): void {
    if (ray.body) {
      const force = Matter.Vector.create(ray.direction[0], ray.direction[1]);
      Matter.Body.setVelocity(ray.body, force);
      this.setConstRaySpeed(ray);
    }
  }

  setConstRaySpeed(ray: Ray): void {
    if (ray.body) {
      (Matter.Body as any).setSpeed(ray.body, 3);
    }
  }

  rayInitError(item: GameObject): void {
    const ray = item.source as Ray;
    const index = this.rayList.findIndex((item) => item.uuid === ray.uuid);
    if (index > -1) {
      this.rayList.splice(index, 1);
    }
  }

  updateRays(): void {
    for (const ray of this.rayList) {
      if (ray.initialised && ray.body?.speed) {
        ray.animationIndex++;
        if (ray.hit) {
          if (ray.displayPointsCount > ray.displayPoints.length)
            ray.displayPointsCount = ray.displayPoints.length;
          ray.displayPointsCount -= 10;
          if (ray.displayPointsCount < 1) ray.displayPointsCount = 1;
        } else ray.displayPointsCount += 10;
        const points = this.calculateInitRayPoints(
          ray.type,
          ray.intensity,
          ray.animationIndex
        );
        for (let i = 0; i < points.length; i++) {
          let displayPoint = points[i];
          if (i >= ray.displayPointsCount) {
            displayPoint = points[ray.displayPointsCount - 1];
          }
          ray.displayPoints[i].x = displayPoint.x;
          ray.displayPoints[i].y = displayPoint.y;
        }
        //this.setConstRaySpeed(ray);
      } else if (ray.startTime + 2000 < Date.now()) {
        const index = this.rayList.findIndex((item) => item.uuid === ray.uuid);
        if (index > -1) this.rayList.splice(index, 1);
      }
    }

    /*for (const ray of this.rayList) {
      if (ray.initialised) {
        ray.position[0] += ray.direction[0];
        ray.position[1] += ray.direction[1];
      } else if (ray.startTime + 1000 < Date.now()) {
        const index = this.rayList.findIndex(
          (item) => item.uuid === ray.uuid
        );
        if (index > -1) this.rayList.splice(index, 1);
      }
    }*/

    for (const obstacle of this.obstacleList) {
      obstacle.hitAnimation = obstacle.hitAnimation.filter(
        (item) => item.time < 5
      );
      for (const animation of obstacle.hitAnimation) {
        animation.time += 0.1;
      }
    }

    for (const region of this.collisionRegions) {
      region.source.hitAnimation = region.source.hitAnimation.filter(
        (item) => item.time < 5
      );
      for (const animation of region.source.hitAnimation) {
        animation.time += 0.1;
      }
      this.updateRegionFilter(region);
    }

    this.collisionAnimation = this.collisionAnimation.filter(
      (item) => item.radius < 0 || item.time < item.radius / 10
    );
    for (const filter of this.collisionAnimation) {
      filter.time += filter.wavelength / 500;
    }
  }

  async rayCollision(
    rayObject: GameObject,
    obstacleObject: GameObject | CollisionRegion,
    rayBody: Matter.Body,
    obstacleBody: Matter.Body
  ): Promise<void> {
    const ray = rayObject.source as Ray;
    const index = this.rayList.findIndex((item) => item.uuid === ray.uuid);
    const hitObstacle = obstacleObject?.source as CoolItHitRegion;
    const probability = Math.random();
    if (
      index > -1 &&
      hitObstacle &&
      hitObstacle.reflectionProbability >= probability
    ) {
      if (ray.type === RayType.light && !ray.hit) {
        ray.hit = true;
        await delay(100);
        const rayVelocity = [
          rayObject.body.velocity.x,
          rayObject.body.velocity.y,
        ];
        rayObject.body.isStatic = true;
        if (hitObstacle) {
          const hitPointScreen = matterUtil.calculateVisibleHitPoint(
            obstacleBody,
            rayBody,
            this.gameWidth,
            this.gameHeight
          );
          hitObstacle.hitCount++;
          hitObstacle.hitAnimation.push(
            new ShockwaveFilter(
              hitPointScreen,
              {
                amplitude: 1,
                wavelength: 50,
                speed: 10,
                brightness: 1.2,
                radius: 50,
              },
              0
            )
          );
          if (Object.hasOwn(obstacleObject, 'filter') && hitObstacle) {
            this.collisionAnimation.push(
              new ShockwaveFilter(
                [rayBody.position.x, rayBody.position.y],
                {
                  amplitude: 1,
                  wavelength: 50,
                  speed: 10,
                  brightness: 1.2,
                  radius: 50,
                },
                0
              )
            );
          }
        }
        await delay(1000);
        if (!rayObject.body) return;
        ray.displayPointsCount = 0;
        for (let i = 0; i < ray.displayPoints.length; i++) {
          ray.displayPoints[i].x = 0;
          ray.displayPoints[i].y = 0;
        }
        ray.type = RayType.heat;
        ray.direction[1] *= -1;
        ray.intensity = hitObstacle?.heatRationCoefficient;
        ray.animationIndex = 0;
        const force = Matter.Vector.create(rayVelocity[0], rayVelocity[1] * -1);
        rayObject.body.isStatic = false;
        Matter.Body.setVelocity(rayObject.body, force);
        const options = this.getRayTypeOptions(ray.type);
        rayObject.body.name = options.name;
        rayObject.body.collisionFilter.mask = options.collisionFilter.mask;
        rayObject.body.collisionFilter.category =
          options.collisionFilter.category;
        const points = this.calculateInitRayPoints(
          ray.type,
          ray.intensity,
          ray.animationIndex
        );
        for (let i = 0; i < ray.points.length; i++) {
          ray.points[i].x = points[i].x;
          ray.points[i].y = points[i].y;
        }
        ray.angle *= -1;
        ray.angle += 180;
        ray.hit = false;
      } else if (ray.type === RayType.heat && !ray.hit) {
        ray.hit = true;
        hitObstacle.hitCount++;
        this.collisionAnimation.push(
          new ShockwaveFilter(
            [rayBody.position.x, rayBody.position.y],
            {
              amplitude: 1,
              wavelength: 100 * ray.intensity,
              speed: 10,
              brightness: 1.2,
              radius: hitObstacle.heatRationCoefficient * 100 * ray.intensity,
            },
            0
          )
        );
        this.rayList.splice(index, 1);
      }
    }
    if (Object.hasOwn(obstacleObject, 'filter') && hitObstacle) {
      this.updateRegionFilter(obstacleObject as CollisionRegion);
    }
  }

  obstacleCollision(
    obstacleObject: GameObject,
    moleculeObject: GameObject | CollisionRegion
  ): void {
    if (!moleculeObject) return;
    const obstacleType =
      gameConfig.obstacles[this.levelType].categories[
        obstacleObject.source.type
      ].settings.type;
    if (
      obstacleType === ObstacleType.carbonSink &&
      moleculeObject.source.absorbedByTree
    ) {
      const index = this.moleculeList.findIndex(
        (item) => item.id === moleculeObject.source.id
      );
      if (index > -1) {
        moleculeObject.source.type = 'oxygen';
        const oxygenConfig = gameConfig.molecules.oxygen;
        moleculeObject.source.globalWarmingFactor =
          oxygenConfig.globalWarmingFactor;
        moleculeObject.source.size = oxygenConfig.size;
        moleculeObject.source.controllable = oxygenConfig.controllable;
        moleculeObject.source.absorbedByTree = oxygenConfig.absorbedByTree;
        moleculeObject.source.color = oxygenConfig.color;
        moleculeObject.source.heatRationCoefficient =
          oxygenConfig.globalWarmingFactor;
        //this.moleculeList.splice(index, 1);
      }
    }
  }

  updateRegionFilter(region: CollisionRegion): void {
    const source = region.source as CoolItHitRegion;
    const redHash = themeColors.getRedColor();
    //const red = new Color(redHash).to('srgb') as any;
    let mixingFactor = source.hitCount / source.maxHitCount;
    if (mixingFactor > 1) mixingFactor = 1;
    /*region.filter = [
      new ColorOverlayFilter([red.r, red.g, red.b], mixingFactor / 2),
      ...source.hitAnimation,
    ];*/
    //const alpha = `0${(mixingFactor / 2).toString(16)}`.slice(-2);
    //region.color = `${redHash}${alpha}`;
    region.color = redHash;
    region.alpha = mixingFactor / 2;
    /*region.filter = [
      new ColorOverlayFilter([red.r, red.g, red.b], mixingFactor / 2),
    ];*/
  }

  leaveAtmosphere(
    rayObject: GameObject,
    out: { top: boolean; bottom: boolean; right: boolean; left: boolean }
  ): void {
    const ray = rayObject.source as Ray;
    if (ray.type === RayType.heat && out.top) {
      setTimeout(() => {
        const index = this.rayList.findIndex((item) => item.uuid === ray.uuid);
        if (index > -1) {
          this.rayList.splice(index, 1);
        }
      }, 3000);
    }
  }

  updateOffset(
    value: [number, number],
    min: [number, number],
    max: [number, number]
  ): void {
    this.panOffsetMin = min;
    this.panOffsetMax = max;
    this.panOffset = value;
  }
}
</script>

<style scoped lang="scss">
.gameArea {
  height: calc(100%);
  width: 100%;
  position: relative;
}
</style>
