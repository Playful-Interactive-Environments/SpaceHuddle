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
      :auto-pan-speed="autoPanSpeed"
    >
      <template v-slot:default>
        <container v-if="gameWidth && circleGradientTexture">
          <container>
            <sprite
              :texture="temperatureMarkerTexture"
              :height="15"
              :width="getTemperatureRange(lowerTemperatureLimit) * gameWidth"
              tint="#ff0000"
            ></sprite>
            <sprite
              :texture="temperatureMarkerTexture"
              :x="gameWidth"
              :anchor="[1, 0]"
              :height="15"
              :width="
                (1 - getTemperatureRange(upperTemperatureLimit)) * gameWidth
              "
              tint="#ff0000"
            ></sprite>
            <sprite
              :texture="temperatureGradientTexture"
              :height="10"
              :width="gameWidth"
            ></sprite>
            <sprite
              :texture="temperatureMarkerTexture"
              :x="getTemperatureRange(lowerTemperatureLimit) * gameWidth"
              :width="4"
              :height="15"
              :anchor="[0.5, 0]"
              tint="#ff0000"
            ></sprite>
            <text
              :anchor="[0.5, 0]"
              :x="getTemperatureRange(lowerTemperatureLimit) * gameWidth"
              :y="15"
              :style="{ fontFamily: 'Arial', fontSize: 24, fill: '#ff0000' }"
              :scale="textScaleFactor"
            >
              {{ lowerTemperatureLimit }}°C
            </text>
            <text
              :anchor="[0, 0]"
              :x="5"
              :y="15"
              :style="{ fontFamily: 'Arial', fontSize: 18, fill: '#ff0000' }"
              :scale="textScaleFactor"
            >
              {{
                $t(
                  'module.playing.coolit.participant.temperatureScale.lowerLimit'
                )
              }}
            </text>
            <sprite
              :texture="temperatureMarkerTexture"
              :x="getTemperatureRange(upperTemperatureLimit) * gameWidth"
              :width="4"
              :height="15"
              :anchor="[0.5, 0]"
              tint="#ff0000"
            ></sprite>
            <text
              :anchor="[0.5, 0]"
              :x="getTemperatureRange(upperTemperatureLimit) * gameWidth"
              :y="15"
              :style="{ fontFamily: 'Arial', fontSize: 24, fill: '#ff0000' }"
              :scale="textScaleFactor"
            >
              {{ upperTemperatureLimit }}°C
            </text>
            <text
              :anchor="[1, 0]"
              :x="gameWidth - 5"
              :y="15"
              :style="{ fontFamily: 'Arial', fontSize: 18, fill: '#ff0000' }"
              :scale="textScaleFactor"
            >
              {{
                $t(
                  'module.playing.coolit.participant.temperatureScale.upperLimit'
                )
              }}
            </text>
            <sprite
              v-for="obstacle in obstacleList"
              :key="obstacle.uuid"
              :texture="temperatureMarkerTexture"
              :x="getTemperatureRange(obstacle.temperature) * gameWidth"
              :width="2"
              :height="12"
              :anchor="[0.5, 0]"
              :tint="contrastColor"
            ></sprite>
            <sprite
              :texture="temperatureMarkerTexture"
              :x="getTemperatureRange(averageTemperature) * gameWidth"
              :width="4"
              :height="20"
              :anchor="[0.5, 0]"
              tint="#ff0000"
            ></sprite>
            <text
              :anchor="[0.5, 0]"
              :x="getTemperatureRange(averageTemperature) * gameWidth"
              :y="20"
              :style="{ fontFamily: 'Arial', fontSize: 36, fill: '#ff0000' }"
              :scale="textScaleFactor"
            >
              {{ Math.round(averageTemperature) }}°C
            </text>
          </container>
          <container v-if="!gameOver">
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
                :saturation="obstacle.saturation"
                :outline="getTemperatureColor(obstacle.temperature).code"
                :outline-width="
                  getTemperatureColor(obstacle.temperature).thickness
                "
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
                  :texture="circleGradientTexture"
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
            <!--<custom-particle-container
              v-for="moleculeName of Object.keys(backgroundParticle)"
              :key="moleculeName"
              :deep-clone-config="false"
              :default-texture="moleculeTextures[moleculeName]"
              :parentEventBus="eventBus"
              :config="backgroundParticle[moleculeName]"
              :x="(-panOffset[0] * (containerTextureSize[0] - gameWidth)) / 100"
              :y="
                (-panOffset[1] * (containerTextureSize[1] - gameHeight)) / 100
              "
              :auto-update="false"
            />-->
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
              :z-index="1"
              :circle-fast-objects="true"
              :remove-from-engin-if-not-visible="true"
              :conditional-velocity="{
                velocity: {x: 0, y: -3},
                condition: (object: GameObject) => object.position[1] > gameHeight / 3 * 2
              }"
              @click="moleculeClicked"
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
          <container v-else>
            <sprite
              :texture="riverTexture"
              :width="containerTextureSize[0]"
              :height="containerTextureSize[1]"
              :x="(-panOffset[0] * (containerTextureSize[0] - gameWidth)) / 100"
              :y="
                (-panOffset[1] * (containerTextureSize[1] - gameHeight)) / 100
              "
              tint="#ff4400"
            >
            </sprite>
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
            >
              <CustomSprite
                :colorOverlay="calculateTintColor(obstacle, 1)"
                :texture="obstacle.texture"
                :anchor="obstacle.pivot"
                :width="obstacle.width"
                :aspect-ration="getObjectAspect(obstacle.type, obstacle.name)"
                :object-space="ObjectSpace.RelativeToBackground"
              >
              </CustomSprite>
              <text
                :anchor="[0.5, 1]"
                :style="{ fontFamily: 'Arial', fontSize: 34, fill: '#ffffff' }"
                :scale="textScaleFactor"
              >
                {{ Math.round(obstacle.temperature) }}°C
              </text>
            </GameObject>
          </container>
        </container>
      </template>
    </GameContainer>
    <div class="statusOverlay">
      {{ getTimeString(playTime) }}
      <el-rate v-model="stars" size="large" :max="3" :disabled="true" />
    </div>
    <div class="statusGameOver" v-if="gameOver">
      <div>{{ $t('module.playing.coolit.participant.gameOver') }}</div>
      <div>
        <el-rate v-model="stars" size="large" :max="3" :disabled="true" />
      </div>
      <div>{{ level.keywords }}</div>
      <div>{{ getTimeString(playTime) }}</div>
      <div v-if="lastTimeDelta > 0">+{{ getTimeString(lastTimeDelta) }}</div>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import * as PIXI from 'pixi.js';
import { Prop, Watch } from 'vue-property-decorator';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import GameContainer, {
  BackgroundMovement,
  BackgroundPosition,
  CollisionBorderType,
  CollisionRegion,
} from '@/components/shared/atoms/game/GameContainer.vue';
import * as placeable from '@/types/game/Placeable';
import * as pixiUtil from '@/utils/pixi';
import { ObjectSpace } from '@/types/enum/ObjectSpace';
import CustomSprite from '@/components/shared/atoms/game/CustomSprite.vue';
import { delay, until } from '@/utils/wait';
import * as tutorialService from '@/services/tutorial-service';
import * as votingService from '@/services/voting-service';
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
import { Vote } from '@/types/api/Vote';
import * as CoolItConst from '@/modules/playing/coolit/utils/consts';

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
  avgHitCount: number;
  avgTemperature: number;
  items: {
    hitCount: number;
    temperature: number;
  }[];
}

enum PlayStateType {
  play,
  win,
  lost,
}

export interface PlayStateResult {
  temperatureRise: number;
  winTime: number;
  stars: number;
  time: number;
  normalisedTime: number;
  moleculeState: { [key: string]: MoleculeState };
  obstacleState: { [key: string]: ObstacleState };
  regionState: { [key: string]: ObstacleState };
  obstacleHitCount: number;
  regionHitCount: number;
  moleculeHitCount: number;
  rayCount: number;
  temperature: number;
}

enum ObstacleType {
  obstacle = 'obstacle',
  carbonSink = 'carbonSink',
  carbonSource = 'carbonSource',
}

interface CoolItHitRegion {
  name: string;
  maxHitCount: number;
  hitCount: number;
  hitAnimation: ShockwaveFilter[];
  //heatRationCoefficient: number;
  reflectionProbability: number;
  heatAbsorptionCoefficientLight: number;
  heatAbsorptionCoefficientHeat: number;
  heatRadiationCoefficient: number;
  temperature: number;
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
    //heatRationCoefficient: configParameter.heatRationCoefficient ?? 1,
    heatAbsorptionCoefficientLight:
      configParameter.heatAbsorptionCoefficientLight ?? 1,
    heatAbsorptionCoefficientHeat:
      configParameter.heatAbsorptionCoefficientHeat ?? 1,
    heatRadiationCoefficient: configParameter.heatRadiationCoefficient ?? 1,
    reflectionProbability: 1,
    saturation: result.saturation,
    temperature: configParameter.initialTemperature,
  };
}

interface ColorValues {
  code: number;
  hex: string;
  coords: [number, number, number];
  alpha: number;
  thickness: number;
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
  //#region parameters
  @Prop() readonly taskId!: string;
  @Prop({ default: null }) readonly level!: Idea | null;
  @Prop({ default: 180000 }) readonly winTime!: number;
  @Prop({ default: 0 }) readonly temperatureRise!: number;
  @Prop({ default: EndpointAuthorisationType.PARTICIPANT })
  authHeaderTyp!: EndpointAuthorisationType;
  //#endregion parameters

  //#region variables
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
  playTime = 0;
  moleculeSize = 50;
  autoPanSpeed = 0.2;
  emitRatePerStar = 500;

  readonly absorptionConst = 1.1; //1.25;
  readonly radiationConst = 0.05;
  readonly minTemperature = -40;
  readonly maxTemperature = 60;
  readonly lowerTemperatureLimit = -20;
  readonly upperTemperatureLimit = 40;
  readonly moduleCountFactor = 100;
  temperatureColorSteps: { [key: number]: ColorValues } = {};

  rayList: Ray[] = [];
  rayPath: { [key: string]: { x: number; y: number }[][] } = {};
  circleGradientTexture: PIXI.Texture | null = null;
  temperatureGradientTexture: PIXI.Texture | null = null;
  temperatureMarkerTexture: PIXI.Texture | null = null;
  riverTexture: PIXI.Texture | null = null;
  groundTexture: PIXI.Texture | null = null;
  streetTexture: PIXI.Texture | null = null;
  moleculeTextures: { [key: string]: PIXI.Texture } = {};
  rayParticleSize = 10;
  collisionAnimation: any[] = [];
  moleculeList: MoleculeData[] = [];
  backgroundParticle: { [key: string]: PIXIParticles.EmitterConfigV3 } = {};
  moleculeState: { [key: string]: MoleculeState } = {};
  highScore: Vote | null = null;

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
  //#endregion variables

  //#region get / set
  get playStateResult(): PlayStateResult {
    const result: PlayStateResult = {
      temperatureRise: this.temperatureRise,
      winTime: this.temperatureWinTime,
      stars: this.stars,
      time: this.playTime,
      normalisedTime: this.normalisedTime,
      moleculeState: { ...this.moleculeState },
      obstacleState: {},
      regionState: {},
      obstacleHitCount: 0,
      regionHitCount: 0,
      moleculeHitCount: Object.values(this.moleculeState).reduce(
        (sum, item) => sum + item.hitCount,
        0
      ),
      rayCount: this.emittedRayCount,
      temperature: this.averageTemperature,
    };
    for (const obstacle of Object.keys(
      gameConfig.obstacles[this.levelType].categories
    )) {
      const list = this.obstacleList.filter((item) => item.name === obstacle);
      result.obstacleState[obstacle] = {
        totalCount: list.length,
        avgHitCount:
          list.reduce((sum, item) => sum + item.hitCount, 0) / list.length,
        avgTemperature:
          list.reduce((sum, item) => sum + item.temperature, 0) / list.length,
        items: list.map((item) => {
          return {
            hitCount: item.hitCount,
            temperature: item.temperature,
          };
        }),
      };
      result.obstacleHitCount += list.reduce(
        (sum, item) => sum + item.hitCount,
        0
      );
    }
    for (const region of gameConfig.obstacles[this.levelType].settings
      .heatRation) {
      const regionName = region.name;
      const list = this.collisionRegions.filter(
        (item) => item.source.name === regionName
      );
      result.regionState[regionName] = {
        totalCount: list.length,
        avgHitCount:
          list.reduce((sum, item) => sum + item.source.hitCount, 0) /
          list.length,
        avgTemperature:
          list.reduce((sum, item) => sum + item.source.temperature, 0) /
          list.length,
        items: list.map((item) => {
          return {
            hitCount: item.source.hitCount,
            temperature: item.source.temperature,
          };
        }),
      };
      result.regionHitCount += list.reduce(
        (sum, item) => sum + item.source.hitCount,
        0
      );
    }
    return result;
  }

  get backgroundColor(): string {
    return themeColors.getBackgroundColor();
  }

  get contrastColor(): string {
    return themeColors.getContrastColor();
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
          name: ration.name,
          heatRationCoefficient: ration.heatRationCoefficient,
          maxHitCount: ration.maxHitCount,
          hitCount: 0,
          hitAnimation: [],
          reflectionProbability: ration.reflectionProbability,
          heatAbsorptionCoefficientLight: ration.heatAbsorptionCoefficientLight,
          heatAbsorptionCoefficientHeat: ration.heatAbsorptionCoefficientHeat,
          heatRadiationCoefficient: ration.heatRadiationCoefficient,
          temperature: ration.initialTemperature,
        } as CoolItHitRegion,
        filter: [],
        color: '#ffffff',
        alpha: 0,
        text: '',
      });
    }
    return regions;
  }

  get averageTemperature(): number {
    const sumObstacles = this.obstacleList.reduce(
      (sum, item) => sum + item.temperature,
      0
    );
    const countObstacles = this.obstacleList.length;
    const sumRegions = this.collisionRegions.reduce(
      (sum, item) => sum + item.source.temperature,
      0
    );
    const countRegions = this.collisionRegions.length;
    return (sumObstacles + sumRegions) / (countObstacles + countRegions);
  }

  get stars(): number {
    const stars = Math.floor((this.playTime / this.temperatureWinTime) * 3);
    if (stars < 3) return stars;
    return 3;
  }

  get temperatureWinTime(): number {
    //return this.winTime - Math.abs(this.temperatureRise * 30000);
    return this.winTime - this.temperatureRise * 30000;
  }

  get normalisedTime(): number {
    return (this.playTime / this.temperatureWinTime) * 120000;
  }

  get absorptionFactor(): number {
    const size = this.gameWidth * this.gameHeight;
    const factor = size / 1000000;
    return this.absorptionConst + factor;
  }

  getTimeString(timestamp: number): string {
    const seconds = Math.floor(timestamp / 1000);
    const secondsString = `0${seconds % 60}`;
    return `${Math.floor(seconds / 60)}:${secondsString.slice(-2)}`;
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
          frictionAir: 0,
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
          frictionAir: 0,
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
      frictionAir: 0.1,
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

  get gameConfigTypes(): string[] {
    return configParameter.getGameConfigTypes(
      gameConfig.obstacles as any,
      this.levelType
    );
  }

  calculateTintColor(
    obstacle: CoolItHitRegion,
    alpha = -1
  ): [number, number, number, number] {
    /*let mixingFactor = obstacle.hitCount / obstacle.maxHitCount;
    if (mixingFactor > 1) mixingFactor = 1;
    const red = new Color(themeColors.getRedColor()).to('srgb') as any;
    return [red.r, red.g, red.b, mixingFactor / 2];*/
    const color = this.getTemperatureColor(obstacle.temperature);
    if (color) {
      return [
        color.coords[0],
        color.coords[1],
        color.coords[2],
        alpha > 0 ? alpha : color.alpha,
      ];
    }
    return [255, 255, 255, 0];
  }

  get temperatureGradient(): any {
    const minTempColor = new Color('#0000FF').to('srgb');
    const maxTempColor = new Color('#FF00FF').to('srgb');
    return minTempColor.range(maxTempColor, {
      space: 'lch',
      hue: 'decreasing',
      outputSpace: 'srgb',
    });
  }

  getTemperatureRange(temperature: number): number {
    return (
      (temperature - this.minTemperature) /
      (this.maxTemperature - this.minTemperature)
    );
  }

  calculateTemperatureColor(temperature: number): ColorValues {
    let range = this.getTemperatureRange(temperature);
    if (range < 0) range = 0;
    if (range > 1) range = 1;
    const temperatureColor = this.temperatureGradient(range);
    const hex = temperatureColor.toString({ format: 'hex', collapse: false });
    let dangerFactor = 0;
    if (temperature < this.lowerTemperatureLimit)
      dangerFactor =
        (this.lowerTemperatureLimit - temperature) /
        (this.lowerTemperatureLimit - this.minTemperature);
    if (temperature > this.upperTemperatureLimit)
      dangerFactor =
        (temperature - this.upperTemperatureLimit) /
        (this.maxTemperature - this.upperTemperatureLimit);
    return {
      hex: hex,
      code: parseInt(hex.slice(1), 16),
      coords: [temperatureColor.r, temperatureColor.g, temperatureColor.b],
      alpha: dangerFactor / 2 + 0.1,
      thickness: dangerFactor * 5 + 1,
    };
  }

  getTemperatureColor(temperature: number): ColorValues {
    const index = Math.round(temperature);
    if (index > this.maxTemperature)
      return this.temperatureColorSteps[this.maxTemperature];
    if (index < this.minTemperature)
      return this.temperatureColorSteps[this.minTemperature];
    return this.temperatureColorSteps[Math.round(temperature)];
  }

  get textScaleFactor(): number {
    return this.gameWidth / 700;
  }
  //#endregion get / set

  //#region load / unload
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
    this.interval = setInterval(() => this.updateLoop(), this.intervalTime);

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
    pixiUtil
      .loadTexture('/assets/games/coolit/city/river.png', this.eventBus)
      .then((sheet) => {
        this.riverTexture = sheet;
      });
    pixiUtil
      .loadTexture('/assets/games/coolit/city/ground.png', this.eventBus)
      .then((sheet) => {
        this.groundTexture = sheet;
      });
    pixiUtil
      .loadTexture('/assets/games/coolit/city/street.png', this.eventBus)
      .then((sheet) => {
        this.streetTexture = sheet;
      });

    for (let i = this.minTemperature; i <= this.maxTemperature; i++) {
      this.temperatureColorSteps[i] = this.calculateTemperatureColor(i);
    }
  }

  containerTextureSize: [number, number] = [100, 100];
  containerTextureSizeChanged(size: [number, number]): void {
    this.containerTextureSize = size;
  }

  containerReady(): void {
    if (!this.emitStart) {
      this.startTime = Date.now();
      this.emitLightRays(200, 0);

      for (const moleculeConfigName of Object.keys(gameConfig.molecules)) {
        const moleculeConfig = gameConfig.molecules[moleculeConfigName];
        if (moleculeConfig.controllable) {
          const moleculeCount =
            (moleculeConfig.ration +
              moleculeConfig.rationDeltaPerDegree * this.temperatureRise) *
            this.moduleCountFactor;
          for (let i = 0; i < moleculeCount; i++) {
            this.moleculeList.push({
              name: moleculeConfigName,
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
              //heatRationCoefficient: moleculeConfig.globalWarmingFactor,
              heatAbsorptionCoefficientLight:
                moleculeConfig.globalWarmingFactor,
              heatAbsorptionCoefficientHeat: moleculeConfig.globalWarmingFactor,
              heatRadiationCoefficient: moleculeConfig.globalWarmingFactor,
              reflectionProbability: 1,
              temperature: 0,
            });
          }
        }

        const list = this.moleculeList.filter(
          (item) => item.type === moleculeConfigName
        );
        this.moleculeState[moleculeConfigName] = {
          startCount: list.length,
          movedCount: 0,
          hitCount: 0,
          emitCount: 0,
          decreaseCount: 0,
        };
      }
    }
  }

  updateTutorial(steps: Tutorial[]): void {
    this.tutorialSteps = steps.filter((step) => step.type === tutorialType);
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTutorial);
    cashService.deregisterAllGet(this.updateHighScore);
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
      votingService.registerGetVotes(
        this.taskId,
        this.updateHighScore,
        EndpointAuthorisationType.PARTICIPANT,
        60 * 60
      );
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

  updateHighScore(votes: Vote[]): void {
    if (this.level) {
      const levelId = this.level.id;
      const vote = votes.find((item) => item.ideaId === levelId);
      if (vote) this.highScore = vote;
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
  //#endregion load / unload

  //#region texture / pixi
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
    this.circleGradientTexture = pixiUtil.generateCircleGradientTexture(
      256,
      this.renderer
    );
    const colorStops: string[] = [];
    for (let i = 0; i < 20; i++) {
      colorStops.push(
        this.temperatureGradient(i / 20).toString({
          format: 'hex',
          collapse: false,
        })
      );
    }
    this.temperatureGradientTexture = pixiUtil.generateLinearGradientTexture(
      1024,
      10,
      this.renderer,
      colorStops,
      '#ffffff',
      pixiUtil.GradientDirection.LeftRight
    );
    this.temperatureMarkerTexture = pixiUtil.generateLinearGradientTexture(
      10,
      128,
      this.renderer,
      ['#ffffff', '#ffffff']
    );
    this.generateMoleculeTextures();
  }

  async generateMoleculeTextures(): Promise<void> {
    if (
      !this.renderer ||
      !this.circleGradientTexture ||
      !this.moleculeStylesheets ||
      Object.keys(this.moleculeTextures).length > 0
    )
      return;
    for (const moleculeName of Object.keys(gameConfig.molecules)) {
      if (this.moleculeStylesheets.textures[moleculeName]) {
        this.moleculeTextures[moleculeName] = pixiUtil.generateStackedTexture(
          [
            this.circleGradientTexture,
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
            (gameConfig.molecules[moleculeName].ration +
              gameConfig.molecules[moleculeName].rationDeltaPerDegree *
                this.temperatureRise) *
            this.moduleCountFactor;
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
  //#endregion texture / pixi

  //#region rays
  readonly rayPoints = 80;
  readonly rayLength = 500 / this.rayPoints;
  emitStart = false;
  emittedRayCount = 0;
  emitLightRays(minDelay = 3000, maxDelay = 1000): void {
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
      this.emittedRayCount++;
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
      if (this.active) {
        const stars = Math.floor((this.playTime / this.temperatureWinTime) * 3);
        let minDelay = 3000 - this.emitRatePerStar * stars;
        if (minDelay < 500) minDelay = 500;
        this.emitLightRays(minDelay, 1000);
      }
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
  //#endregion rays

  //#region loop
  updateTimeStamp = Date.now();
  gameOver = false;
  updateLoop(): void {
    const updateTimeStamp = Date.now();
    if (this.lightCollisionCount > 0)
      this.playTime = updateTimeStamp - this.startTime;
    const averageTemperature = this.averageTemperature;
    let gameOver =
      averageTemperature < this.lowerTemperatureLimit ||
      averageTemperature > this.upperTemperatureLimit;
    if (!gameOver) {
      let obstacleGameOverCount = 0;
      for (const obstacle of this.obstacleList) {
        if (
          obstacle.temperature < this.lowerTemperatureLimit ||
          obstacle.temperature > this.upperTemperatureLimit
        )
          obstacleGameOverCount++;
      }
      let regionGameOverCount = 0;
      for (const region of this.collisionRegions) {
        if (
          region.source.temperature < this.lowerTemperatureLimit ||
          region.source.temperature > this.upperTemperatureLimit
        )
          regionGameOverCount++;
      }
      if (obstacleGameOverCount + regionGameOverCount > 3) gameOver = true;
    }
    if (gameOver) {
      this.gameOver = true;
      this.collisionAnimation.splice(0);
      clearInterval(this.interval);

      for (const obstacle of this.obstacleList) {
        obstacle.hitAnimation.splice(0);
      }
      for (const region of this.collisionRegions) {
        region.source.hitAnimation.splice(0);
        region.filter.splice(0);
        region.alpha = 1;
        region.text = `${Math.round(region.source.temperature)}°C`;
      }
      this.autoPanSpeed = 1;
      this.saveHighScore();
      return;
    }
    const updateDelta = updateTimeStamp - this.updateTimeStamp;
    this.updateTimeStamp = updateTimeStamp;
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

    if (this.emittedRayCount > 0 && this.lightCollisionCount > 0) {
      const timeFactor = updateDelta / 5000;
      for (const obstacle of this.obstacleList) {
        let temperature = obstacle.temperature - this.minTemperature;
        if (temperature < 0) temperature = 0;
        obstacle.temperature -=
          obstacle.heatRadiationCoefficient *
          this.radiationConst *
          temperature *
          timeFactor;
      }

      for (const region of this.collisionRegions) {
        let temperature = region.source.temperature - this.minTemperature;
        if (temperature < 0) temperature = 0;
        region.source.temperature -=
          region.source.heatRadiationCoefficient *
          this.radiationConst *
          temperature *
          timeFactor;
      }
    }
  }

  updateRegionFilter(region: CollisionRegion): void {
    const source = region.source as CoolItHitRegion;
    /*const redHash = themeColors.getRedColor();
    let mixingFactor = source.hitCount / source.maxHitCount;
    if (mixingFactor > 1) mixingFactor = 1;*/
    const color = this.getTemperatureColor(source.temperature);
    region.color = color.hex;
    region.alpha = this.gameOver ? 1 : color.alpha; //mixingFactor / 2;
  }
  //#endregion loop

  //#region collision and interaction
  lightCollisionCount = 0;
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
        this.lightCollisionCount++;
        if (this.lightCollisionCount === 1) this.startTime = Date.now();
        const heatAbsorptionCoefficientObstacle =
          hitObstacle.heatAbsorptionCoefficientLight;
        ray.hit = true;
        await delay(100);
        if (!rayObject.body) return;
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
          hitObstacle.temperature +=
            hitObstacle.heatAbsorptionCoefficientLight * this.absorptionFactor;
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
        ray.intensity = heatAbsorptionCoefficientObstacle;
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
        this.moleculeState[hitObstacle.name].hitCount++;
        const heatRadiation =
          hitObstacle.heatAbsorptionCoefficientHeat * ray.intensity;
        this.collisionAnimation.push(
          new ShockwaveFilter(
            [rayBody.position.x, rayBody.position.y],
            {
              amplitude: 1,
              wavelength: 100 * ray.intensity,
              speed: 10,
              brightness: 1.2,
              radius: heatRadiation * 100,
            },
            0
          )
        );
        this.rayList.splice(index, 1);
        for (const obstacle of this.obstacleList) {
          obstacle.temperature +=
            obstacle.heatAbsorptionCoefficientHeat *
            this.absorptionFactor *
            heatRadiation;
        }
        for (const region of this.collisionRegions) {
          region.source.temperature +=
            region.source.heatAbsorptionCoefficientHeat *
            this.absorptionFactor *
            heatRadiation;
        }
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
        this.moleculeState[moleculeObject.source.name].decreaseCount++;
        moleculeObject.source.type = 'oxygen';
        moleculeObject.source.name = 'oxygen';
        const oxygenConfig = gameConfig.molecules.oxygen;
        moleculeObject.source.globalWarmingFactor =
          oxygenConfig.globalWarmingFactor;
        moleculeObject.source.size = oxygenConfig.size;
        moleculeObject.source.controllable = oxygenConfig.controllable;
        moleculeObject.source.absorbedByTree = oxygenConfig.absorbedByTree;
        moleculeObject.source.color = oxygenConfig.color;
        moleculeObject.source.heatAbsorptionCoefficientHeat =
          oxygenConfig.globalWarmingFactor;
        moleculeObject.source.heatAbsorptionCoefficientLight =
          oxygenConfig.globalWarmingFactor;
        moleculeObject.source.heatRadiationCoefficient =
          oxygenConfig.globalWarmingFactor;
        //moleculeObject.source.heatRationCoefficient = oxygenConfig.globalWarmingFactor;
        //this.moleculeList.splice(index, 1);
      }
    }
  }

  moleculeClicked(item: any): void {
    this.moleculeState[item.source.name].movedCount++;
  }
  //#endregion collision and interaction

  //#region scroll
  updateOffset(
    value: [number, number],
    min: [number, number],
    max: [number, number]
  ): void {
    this.panOffsetMin = min;
    this.panOffsetMax = max;
    this.panOffset = value;
    if (this.gameOver && max[0] === 100 && max[1] === 100) {
      this.finished();
    }
  }
  //#endregion scroll

  //#region finished
  lastTimeDelta = 0;
  saveHighScore(): void {
    if (this.highScore) {
      const lastHighScore = this.highScore.parameter[this.temperatureRise];
      this.lastTimeDelta = this.playTime - lastHighScore.time;
      lastHighScore.lastTimeDelta = this.lastTimeDelta;
      lastHighScore.lastStarDelta = this.stars - lastHighScore.stars;
      if (lastHighScore.time < this.playTime)
        lastHighScore.time = this.playTime;
      if (lastHighScore.stars < this.stars) lastHighScore.stars = this.stars;
      if (
        !this.highScore.parameter.normalisedTime ||
        this.highScore.parameter.normalisedTime < this.normalisedTime
      )
        this.highScore.parameter.normalisedTime = this.normalisedTime;
      if (
        !this.highScore.parameter.rate ||
        this.highScore.parameter.rate < this.stars
      )
        this.highScore.parameter.rate = this.stars;
      votingService.putVote(this.highScore);
    } else {
      const parameter: any = {
        normalisedTime: this.normalisedTime,
        rate: this.stars,
      };
      for (
        let i = CoolItConst.MAX_TEMPERATURE_RISE;
        i >= CoolItConst.MIN_TEMPERATURE_RISE;
        i--
      ) {
        if (i === this.temperatureRise) {
          parameter[i] = {
            time: this.playTime,
            stars: this.stars,
            lastTimeDelta: this.playTime,
            lastStarDelta: this.stars,
          };
        } else {
          parameter[i] = {
            time: 0,
            stars: 0,
            lastTimeDelta: 0,
            lastStarDelta: 0,
          };
        }
      }
      votingService
        .postVote(this.taskId, {
          ideaId: this.level?.id,
          rating: this.stars,
          detailRating: this.playTime,
          parameter: parameter,
        })
        .then((vote) => (this.highScore = vote));
    }
  }

  finished(): void {
    this.$emit('finished', this.playStateResult);
  }
  //#endregion finished
}
</script>

<style scoped lang="scss">
.gameArea {
  height: calc(100%);
  width: 100%;
  position: relative;
}

.statusOverlay {
  pointer-events: none;
  position: absolute;
  z-index: 100;
  top: 2rem;
  right: 1rem;
  text-align: right;
  font-size: var(--font-size-large);
  color: var(--color-dark-contrast);
}

.statusGameOver {
  pointer-events: none;
  position: absolute;
  z-index: 100;
  top: 30vh;
  bottom: 1rem;
  right: 1rem;
  left: 1rem;
  text-align: center;
  font-size: var(--font-size-xxxlarge);
  color: var(--color-dark-contrast);
}
</style>
