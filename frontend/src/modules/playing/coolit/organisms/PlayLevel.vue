<template>
  <div
    class="gameArea"
    v-if="playStateType === PlayStateType.play && levelType"
  >
    <GameContainer
      ref="gameContainer"
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
      @sizeReady="containerSizeReady"
      @backgroundSizeChanged="containerTextureSizeChanged"
      :show-bounds="false"
      :collision-borders="CollisionBorderType.Background"
      :pixi-filter-list="collisionAnimation"
      :pixi-filter-list-background="[colorFilter]"
      :auto-pan-speed="autoPanSpeed"
      :reset-position-on-speed-changed="gameOver"
      :waitForDataLoad="waitForDataLoad"
      :endless-panning="!gameOver"
    >
      <template v-slot:default>
        <container v-if="gameWidth && circleGradientTexture">
          <container v-if="!gameOver">
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
              :sleep-if-not-visible="true"
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
            <animated-sprite
              v-if="vehicleStylesheets"
              :textures="vehicleStylesheets.animations[randomVehicleName]"
              :animation-speed="0.2"
              :width="vehicleWidth"
              :height="vehicleWidth / getVehicleAspect(randomVehicleName)"
              :x="vehicleXPosition"
              :y="vehicleYPosition"
              :anchor="[0.5, 1]"
              playing
              @frame-change="animationFrameChanged"
            />
            <custom-particle-container
              v-if="snow.frequency && weatherStylesheets"
              :config="snow"
              :parentEventBus="eventBus"
              :default-texture="[
                weatherStylesheets.textures['snow_01.png'],
                weatherStylesheets.textures['snow_02.png'],
                weatherStylesheets.textures['snow_03.png'],
                weatherStylesheets.textures['snow_04.png'],
                weatherStylesheets.textures['snow_05.png'],
                weatherStylesheets.textures['snow_06.png'],
                weatherStylesheets.textures['snow_07.png'],
                weatherStylesheets.textures['snow_08.png'],
              ]"
              :deep-clone-config="false"
            />
            <custom-particle-container
              v-if="hail.frequency && weatherStylesheets"
              :config="hail"
              :parentEventBus="eventBus"
              :default-texture="weatherStylesheets.textures['hail.png']"
              :deep-clone-config="false"
            />
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
                  v-if="weatherStylesheets"
                  :texture="weatherStylesheets.textures['light.png']"
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
              :fix-size="molecule.size * moleculeSize"
              :source="molecule"
              :z-index="1"
              :fast-object-behaviour="FastObjectBehaviour.bounce"
              :sleep-if-not-visible="true"
              :conditional-velocity="{
                velocity: {x: 0, y: -3},
                condition: (object: GameObject) => {
                  if (molecule.rise) {
                    molecule.rise = false;
                    return true;
                  }
                  return object.position[1] > gameHeight / 3 * 2;
                }
              }"
              @click="moleculeClicked"
            >
              <CustomSprite
                v-if="getMoleculeTexture(molecule.type)"
                :texture="getMoleculeTexture(molecule.type)"
                :anchor="0.5"
                :tint="molecule.color"
                :width="molecule.size * moleculeSize"
                :height="molecule.size * moleculeSize"
                :alpha="molecule.controllable ? 1 : 0.4"
              />
            </GameObject>
          </container>
          <container v-else>
            <container>
              <sprite
                :texture="temperatureGradientTexture"
                :height="10"
                :width="gameWidth"
              ></sprite>
              <sprite
                v-for="x in temperatureScale"
                :key="x"
                :texture="temperatureMarkerTexture"
                :x="getTemperatureRange(x) * gameWidth"
                :width="2"
                :height="15"
                :anchor="[0.5, 0]"
                tint="#ff0000"
              ></sprite>
              <text
                v-for="x in temperatureScale"
                :key="x"
                :anchor="[0.5, 0]"
                :x="getTemperatureRange(x) * gameWidth"
                :y="15"
                :style="{ fontFamily: 'Arial', fontSize: 24, fill: '#ff0000' }"
                :scale="textScaleFactor"
              >
                {{ x }}°C
              </text>
            </container>
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
                :colorOverlay="calculateTintColor(obstacle, 0.7)"
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
    <div class="statusOverlay" v-if="!gameOver">
      {{ getTimeString(playTime) }}
      <el-rate v-model="stars" size="large" :max="3" :disabled="true" />
    </div>
    <div class="statusWeatherWarning" v-if="!gameOver && snow.frequency > 0">
      {{ $t('module.playing.coolit.participant.weatherInfo.snow') }}
    </div>
    <div class="statusWeatherWarning" v-if="!gameOver && hail.frequency > 0">
      {{ $t('module.playing.coolit.participant.weatherInfo.hail') }}
    </div>
    <div class="statusGameOver" v-if="gameOver">
      <h1>
        {{ $t(`module.playing.coolit.participant.playResult.${stars}.title`) }}
      </h1>
      <div class="columns is-mobile">
        <div class="column">
          <div>
            <font-awesome-icon icon="clock" /> {{ getTimeString(playTime) }}
            <span v-if="lastTimeDelta > 0"
              >(+{{ getTimeString(lastTimeDelta) }})</span
            >
          </div>
          <div>
            <font-awesome-icon icon="star" />
            <el-rate v-model="stars" size="large" :max="3" :disabled="true" />
          </div>
        </div>
        <div class="column">
          <div>
            <font-awesome-icon icon="temperature-half" />
            {{ Math.round(averageTemperature * 10) / 10 }}°C
          </div>
          <div>
            <font-awesome-icon icon="trophy" />
            {{ Math.round((normalisedTime / 60000) * 100) }}
          </div>
        </div>
      </div>
      <div>
        {{
          $t(
            `module.playing.coolit.participant.playResult.${stars}.message.${randomMessageNo}`
          )
        }}
      </div>
      <div>
        <el-button type="primary" @click="$emit('replayFinished')">
          {{ $t('module.playing.coolit.participant.confirm') }}
        </el-button>
      </div>
    </div>
    <div class="statusOverlayCountDown" v-if="countDownEndTime > -1">
      {{ Math.ceil((countDownEndTime - Date.now()) / 1000) }}
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import * as PIXI from 'pixi.js';
import { Prop, Watch } from 'vue-property-decorator';
import GameObject, {
  FastObjectBehaviour,
} from '@/components/shared/atoms/game/GameObject.vue';
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
import { ShockwaveFilter, MultiColorReplaceFilter } from 'pixi-filters';
import * as matterUtil from '@/utils/matter';
import CustomParticleContainer from '@/components/shared/atoms/game/CustomParticleContainer.vue';
import { Vote } from '@/types/api/Vote';
import * as CoolItConst from '@/modules/playing/coolit/utils/consts';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { EventType } from '@/types/enum/EventType';
import weatherConfig from '@/modules/playing/coolit/data/weather.json';

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
  gameObject: GameObject | null;
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
  moleculeMovedCount: number;
  moleculeDecreaseCount: number;
  moleculeEmitCount: number;
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
  emits: string[];
  calculateTemperature: boolean;
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
  rise: boolean;
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
  const calculateTemperature = Object.hasOwn(
    configParameter,
    'calculateTemperature'
  )
    ? (configParameter.calculateTemperature as boolean)
    : true;
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
    reflectionProbability: configParameter.reflectionProbability ?? 1,
    calculateTemperature: calculateTemperature,
    saturation: result.saturation,
    temperature: configParameter.initialTemperature,
    emits: configParameter.emits,
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
    FontAwesomeIcon,
    GameObject,
    GameContainer,
    CustomSprite,
    CustomParticleContainer,
  },
  emits: ['finished', 'replayFinished'],
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
  moleculeStylesheets: PIXI.Spritesheet | null = null;
  vehicleStylesheets: PIXI.Spritesheet | null = null;
  weatherStylesheets: PIXI.Spritesheet | null = null;
  startTime = Date.now();
  playTime = 0;
  autoPanSpeed = 0.4;
  emitRatePerStar = 500;
  randomMessageNo = 1;

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
  colorFilter: MultiColorReplaceFilter = new MultiColorReplaceFilter(
    [
      [0x7cc269, 0x7cc269],
      [0xafd5a4, 0xafd5a4],
      [0x417b40, 0x417b40],
    ],
    0.1
  );
  moleculeList: MoleculeData[] = [];
  backgroundParticle: { [key: string]: PIXIParticles.EmitterConfigV3 } = {};
  moleculeState: { [key: string]: MoleculeState } = {};
  highScore: Vote | null = null;

  playStateType = PlayStateType.play;
  PlayStateType = PlayStateType;
  FastObjectBehaviour = FastObjectBehaviour;
  RayType = RayType;
  interval!: any;
  readonly intervalTime = 100;
  waitForDataLoad = true;
  randomVehicleName = '';
  vehicleXPosition = 0;
  vehicleHasEmitted = false;
  vehicleIsActive = false;

  CollisionGroups = Object.freeze({
    MOUSE: 1 << 0,
    OBSTACLE: 1 << 1,
    CARBON_SINK: 1 << 2,
    CARBON_SOURCE: 1 << 3,
    LIGHT_RAY: 1 << 4,
    HEAT_RAY: 1 << 5,
    GREENHOUSE_MOLECULE: 1 << 6,
    ATMOSPHERIC_MOLECULE: 1 << 7,
    GROUND: 1 << 8,
    BORDER: 1 << 9,
  });
  CollisionBorderType = CollisionBorderType;
  snow = weatherConfig.snow;
  hail = weatherConfig.rain;
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
      moleculeMovedCount: Object.values(this.moleculeState).reduce(
        (sum, item) => sum + item.movedCount,
        0
      ),
      moleculeDecreaseCount: Object.values(this.moleculeState).reduce(
        (sum, item) => sum + item.decreaseCount,
        0
      ),
      moleculeEmitCount: Object.values(this.moleculeState).reduce(
        (sum, item) => sum + item.emitCount,
        0
      ),
      rayCount: this.emittedRayCount,
      temperature: this.averageTemperature,
    };
    for (const obstacleType of Object.keys(
      gameConfig.obstacles[this.levelType].categories
    )) {
      for (const obstacleName of Object.keys(
        gameConfig.obstacles[this.levelType].categories[obstacleType].items
      )) {
        const list = this.obstacleList.filter(
          (item) => item.type === obstacleType && item.name === obstacleName
        );
        result.obstacleState[obstacleName] = {
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
          emits: ration.emits,
          calculateTemperature: true,
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
    const obstacleList = this.obstacleList.filter(
      (item) => item.calculateTemperature
    );
    const sumObstacles = obstacleList.reduce(
      (sum, item) => sum + item.temperature,
      0
    );
    const countObstacles = obstacleList.length;
    const sumRegions = this.collisionRegions.reduce(
      (sum, item) => sum + item.source.temperature,
      0
    );
    const countRegions = this.collisionRegions.length;
    return (sumObstacles + sumRegions) / (countObstacles + countRegions);
  }

  get speedLevel(): number {
    return Math.floor((this.playTime / this.temperatureWinTime) * 3);
  }

  get stars(): number {
    const stars = this.speedLevel;
    if (stars < 3) return stars;
    return 3;
  }

  set stars(value: number) {
    // do nothing
  }

  get temperatureWinTime(): number {
    return CoolItConst.temperatureWinTime(this.winTime, this.temperatureRise);
  }

  get normalisedTime(): number {
    return (this.playTime / this.temperatureWinTime) * 120000;
  }

  get absorptionFactor(): number {
    //const size = this.gameWidth * this.gameHeight;
    //const factor = size / 1000000;
    //return this.absorptionConst + factor;

    return 2; // 1.25;
  }

  get radiationFactor(): number {
    //return this.radiationConst;
    return this.autoPanSpeed / 10; // ((this.temperatureRise + 2) * 10);
  }

  getTimeString(timestamp: number): string {
    const seconds = Math.floor(timestamp / 1000);
    const secondsString = `0${seconds % 60}`;
    return `${Math.floor(seconds / 60)}:${secondsString.slice(-2)}`;
  }

  getObstacleTypeOptions(obstacleType: string, obstacleName: string): any {
    const config =
      gameConfig.obstacles[this.levelType].categories[obstacleType].items[
        obstacleName
      ];
    return {
      isSensor: true,
      name: obstacleName,
      collisionFilter: {
        group: 0,
        category:
          config.type === ObstacleType.carbonSink
            ? this.CollisionGroups.CARBON_SINK
            : this.CollisionGroups.OBSTACLE,
      },
    };
  }

  getRayTypeOptions(rayType: RayType): any {
    switch (rayType) {
      case RayType.light:
        return {
          name: 'light',
          frictionAir: 0,
          mass: 0,
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
          mass: 0,
          collisionFilter: {
            group: 0,
            category: this.CollisionGroups.HEAT_RAY,
            mask: this.CollisionGroups.GREENHOUSE_MOLECULE,
          },
        };
    }
  }

  getMoleculeTypeOptions(moleculeType: string): any {
    const moleculeConfig = gameConfig.molecules[moleculeType];
    let mask =
      this.CollisionGroups.GREENHOUSE_MOLECULE |
      this.CollisionGroups.ATMOSPHERIC_MOLECULE |
      this.CollisionGroups.BORDER;
    if (moleculeConfig.controllable)
      mask = mask | this.CollisionGroups.HEAT_RAY | this.CollisionGroups.MOUSE;
    if (moleculeConfig.absorbedByTree)
      mask = mask | this.CollisionGroups.CARBON_SINK;
    return {
      name: moleculeType,
      frictionAir: 0.01,
      restitution: 1,
      collisionFilter: {
        group: 0,
        category: this.CollisionGroups.GREENHOUSE_MOLECULE,
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
    if (this.moleculeStylesheets)
      return 1 / pixiUtil.getSpriteAspect(this.moleculeStylesheets, objectName);
    return 1;
  }

  getVehicleAspect(objectName: string): number {
    if (this.vehicleStylesheets)
      return pixiUtil.getSpriteAspect(
        this.vehicleStylesheets,
        `${objectName}_01.png`
      );
    return 1;
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

  get moleculeSize(): number {
    return this.textScaleFactor * 220;
  }

  get temperatureScale(): number[] {
    return Array.from(
      { length: (this.maxTemperature - this.minTemperature) / 10 - 1 },
      (x, i) => i * 10 + this.minTemperature + 10
    );
  }

  get vehicleWidth(): number {
    return this.gameWidth / 3;
  }

  get vehicleYPosition(): number {
    return (this.gameHeight / 50) * 49;
  }
  //#endregion get / set

  //#region watch
  @Watch('speedLevel', { immediate: true })
  onSpeedLevelChanged(): void {
    this.autoPanSpeed = 0.4 + this.speedLevel * 0.2;
  }

  @Watch('gameWidth', { immediate: true })
  onGameWidthChanged(): void {
    if (this.gameWidth) {
      const spawnShapeSnow = this.snow.behaviors.find(
        (behavior) => behavior.type === 'spawnShape'
      );
      if (spawnShapeSnow && spawnShapeSnow.config.data) {
        spawnShapeSnow.config.data.w =
          this.gameWidth - spawnShapeSnow.config.data.x * 2;
      }
      const spawnShapeHail = this.hail.behaviors.find(
        (behavior) => behavior.type === 'spawnShape'
      );
      if (spawnShapeHail && spawnShapeHail.config.data) {
        spawnShapeHail.config.data.w =
          this.gameWidth - spawnShapeHail.config.data.x * 2;
      }
    }
  }
  //#endregion watch

  //#region load / unload
  readonly animationSteps = 10;
  loading = false;
  mounted(): void {
    this.eventBus.on(EventType.TEXTURES_LOADING_START, async () => {
      this.loading = true;
    });
    this.eventBus.on(EventType.ALL_TEXTURES_LOADED, async () => {
      this.loading = false;
    });

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
      .loadTexture('/assets/games/coolit/city/weather.json', this.eventBus)
      .then((sheet) => {
        this.weatherStylesheets = sheet;
      });
    pixiUtil
      .loadTexture('/assets/games/moveit/molecules.json', this.eventBus)
      .then((sheet) => {
        this.moleculeStylesheets = sheet;
        this.generateMoleculeTextures();
      });
    pixiUtil
      .loadTexture(
        '/assets/games/moveit/vehicle/vehicle_animation.json',
        this.eventBus
      )
      .then((sheet) => {
        this.vehicleStylesheets = sheet;
        this.setRandomAnimation();
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
    this.initRays();
  }

  setRandomAnimation(): void {
    if (this.vehicleStylesheets) {
      const list = ['compact-car', 'e-car', 'sport-car', 'suv']; //Object.keys(this.vehicleStylesheets.animations);
      this.randomVehicleName = list[Math.floor(Math.random() * list.length)];
      this.vehicleXPosition = -this.vehicleWidth / 2;
      this.vehicleHasEmitted = false;
      this.vehicleIsActive = false;
    }
  }

  animationFrameChanged(): void {
    if (!this.vehicleIsActive) return;
    this.vehicleXPosition += this.vehicleWidth / 50;
    if (this.vehicleXPosition > this.gameWidth + this.vehicleWidth / 2) {
      this.setRandomAnimation();
      setTimeout(() => {
        this.vehicleIsActive = true;
      }, Math.random() * 10000);
    } else if (
      this.vehicleXPosition > this.gameWidth * 0.4 &&
      !this.vehicleHasEmitted
    ) {
      this.vehicleHasEmitted = true;
      const relativePosition = this.vehicleXPosition / this.gameWidth;
      this.emitMolecule(
        [(this.panOffsetMin[0] + this.panOffsetMax[0]) * relativePosition, 92],
        'carbonDioxide'
      );
      setTimeout(() => {
        const relativePosition = this.vehicleXPosition / this.gameWidth;
        this.emitMolecule(
          [
            (this.panOffsetMin[0] + this.panOffsetMax[0]) * relativePosition,
            92,
          ],
          'carbonDioxide'
        );
      }, 1000);
      setTimeout(() => {
        const relativePosition = this.vehicleXPosition / this.gameWidth;
        this.emitMolecule(
          [
            (this.panOffsetMin[0] + this.panOffsetMax[0]) * relativePosition,
            92,
          ],
          'methane'
        );
      }, 2000);
    }
  }

  private emitMolecule(position: [number, number], moleculeName: string): void {
    this.moleculeList.push({
      name: moleculeName,
      id: uuidv4(),
      type: moleculeName,
      position: position,
      globalWarmingFactor:
        gameConfig.molecules[moleculeName].globalWarmingFactor,
      size: gameConfig.molecules[moleculeName].size,
      controllable: gameConfig.molecules[moleculeName].controllable,
      absorbedByTree: gameConfig.molecules[moleculeName].absorbedByTree,
      color: gameConfig.molecules[moleculeName].color,
      maxHitCount: 1000,
      hitCount: 0,
      hitAnimation: [],
      heatAbsorptionCoefficientLight:
        gameConfig.molecules[moleculeName].globalWarmingFactor,
      heatAbsorptionCoefficientHeat:
        gameConfig.molecules[moleculeName].globalWarmingFactor,
      heatRadiationCoefficient:
        gameConfig.molecules[moleculeName].globalWarmingFactor,
      reflectionProbability: 1,
      temperature: 0,
      emits: [],
      rise: true,
      calculateTemperature: true,
    });
  }

  initRays(): void {
    const points = this.calculateInitRayPoints(RayType.light, 1, 0);
    for (let i = 0; i < 30; i++) {
      this.rayList.push({
        uuid: uuidv4(),
        type: RayType.light,
        angle: 0,
        position: [-1000, -1000],
        direction: [0, 0],
        initialised: false,
        startTime: Date.now(),
        points: structuredClone(points),
        displayPoints: points.map(() => {
          return {
            x: 0,
            y: 0,
          };
        }),
        displayPointsCount: 0,
        animationIndex: 0,
        body: null,
        gameObject: null,
        intensity: 1,
        hit: false,
      });
    }
  }

  initMolecules(): void {
    for (const moleculeConfigName of Object.keys(gameConfig.molecules)) {
      const moleculeConfig = gameConfig.molecules[moleculeConfigName];
      if (moleculeConfig.controllable) {
        const moleculeCount =
          moleculeConfig.particleCount +
          moleculeConfig.particleDeltaPerDegree * this.temperatureRise;
        const moleculeList: MoleculeData[] = [];
        for (let i = 0; i < moleculeCount; i++) {
          moleculeList.push({
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
            heatAbsorptionCoefficientLight: moleculeConfig.globalWarmingFactor,
            heatAbsorptionCoefficientHeat: moleculeConfig.globalWarmingFactor,
            heatRadiationCoefficient: moleculeConfig.globalWarmingFactor,
            reflectionProbability: 1,
            temperature: 0,
            emits: [],
            rise: false,
            calculateTemperature: true,
          });
        }
        const distance = 100 / moleculeCount;
        for (let i = 0; i < moleculeCount; i++) {
          moleculeList[i].position[0] = Math.random() * distance + i * distance;
        }
        this.moleculeList.push(...moleculeList);
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

  containerTextureSize: [number, number] = [100, 100];
  containerTextureSizeChanged(size: [number, number]): void {
    this.containerTextureSize = size;
  }

  containerReady(): void {
    if (!this.emitStart) {
      this.startTime = Date.now();
      this.emitLightRays(0, 0);
    }

    setTimeout(() => {
      this.vehicleIsActive = true;
    }, Math.random() * 1000);
  }

  containerSizeReady(): void {
    this.initMolecules();
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
    this.eventBus.off(EventType.TEXTURES_LOADING_START);
    this.eventBus.off(EventType.ALL_TEXTURES_LOADED);
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
            gameConfig.molecules[moleculeName].particleCount +
            gameConfig.molecules[moleculeName].particleDeltaPerDegree *
              this.temperatureRise;
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
      this.emittedRayCount++;
      const poolRay = this.rayList.find(
        (item) =>
          item.gameObject && item.gameObject.readyForReuse() && item.initialised
      );
      if (poolRay) {
        if (poolRay.type !== RayType.light) {
          poolRay.type = RayType.light;
          poolRay.intensity = 1;
          poolRay.displayPointsCount = 0;
          poolRay.animationIndex = 0;
          const points = this.calculateInitRayPoints(RayType.light, 1, 0);
          for (let i = 0; i < poolRay.points.length; i++) {
            poolRay.points[i].x = points[i].x;
            poolRay.points[i].y = points[i].y;
          }
          for (let i = 0; i < poolRay.displayPoints.length; i++) {
            poolRay.displayPoints[i].x = 0;
            poolRay.displayPoints[i].y = 0;
          }
          poolRay.hit = false;
          if (poolRay.body) {
            const options = this.getRayTypeOptions(poolRay.type);
            (poolRay.body as any).name = options.name;
            poolRay.body.collisionFilter.mask = options.collisionFilter.mask;
            poolRay.body.collisionFilter.category =
              options.collisionFilter.category;
          }
        }
        poolRay.angle = angle;
        poolRay.position = [position, 0];
        poolRay.direction = [direction.x, direction.y];
        poolRay.startTime = Date.now();
        if (poolRay.gameObject)
          poolRay.gameObject.activateFromPool([position, 0]);
        this.calculateRayVelocity(poolRay);
      }
      if (this.active) {
        let minDelay = 3000 - this.emitRatePerStar * this.speedLevel;
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

  countDownEndTime = -1;
  async rayInitialised(item: GameObject): Promise<void> {
    item.source.initialised = true;
    if (item.body) {
      item.moveToPool(0);
      item.source.gameObject = item;
      item.source.body = item.body;
    }
    const waitForDataLoad = !!this.rayList.find((item) => !item.initialised);
    if (!waitForDataLoad) {
      await until(() => !this.loading);
      const countDownTime = 3000;
      this.countDownEndTime = Date.now() + countDownTime;
      await delay(countDownTime);
      this.countDownEndTime = -1;
      this.waitForDataLoad = false;
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
      (Matter.Body as any).setSpeed(ray.body, this.autoPanSpeed * 10);
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
        rayObject.moveToPool();
      }, 3000);
    }
  }
  //#endregion rays

  //#region loop
  updateTimeStamp = Date.now();
  gameOver = false;
  emitObstacleList: string[] = [];
  updateLoop(): void {
    const updateTimeStamp = Date.now();
    const playTime = updateTimeStamp - this.startTime;
    let updateDelta = updateTimeStamp - this.updateTimeStamp;
    if (playTime < updateDelta) updateDelta = playTime;
    if (updateDelta > this.intervalTime * 3)
      updateDelta = this.intervalTime * 3;
    this.updateTimeStamp = updateTimeStamp;
    if (this.lightCollisionCount > 0) this.playTime += updateDelta;
    if (this.checkGameOver()) {
      return;
    }
    const activeRays = this.rayList.filter(
      (item) => item.gameObject && !item.gameObject.isSleeping
    );
    for (const ray of activeRays) {
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
      }
    }

    const displayWidth = this.panOffsetMax[0] - this.panOffsetMin[0];
    const containerAspect = (
      this.$refs.gameContainer as any
    ).getBackgroundAspect();
    const possibleEmitList = this.obstacleList
      .filter(
        (obstacle) =>
          obstacle.emits.length > 0 &&
          obstacle.position[0] > this.panOffsetMin[0] &&
          obstacle.position[0] < this.panOffsetMax[0]
      )
      .map((item) => item.uuid);
    this.emitObstacleList = this.emitObstacleList.filter((item) =>
      possibleEmitList.includes(item)
    );
    for (const obstacle of this.obstacleList) {
      obstacle.hitAnimation = obstacle.hitAnimation.filter(
        (item) => item.time < 5
      );
      for (const animation of obstacle.hitAnimation) {
        animation.time += 0.1;
      }
      if (
        obstacle.emits.length > 0 &&
        obstacle.position[0] > this.panOffsetMin[0] &&
        obstacle.position[0] < this.panOffsetMax[0] &&
        this.isReadyForPlay &&
        this.lightCollisionCount > 0
      ) {
        const position = this.panOffsetMin[0] + displayWidth * 0.6;
        if (
          position > obstacle.position[0] &&
          !this.emitObstacleList.includes(obstacle.uuid)
        ) {
          this.emitObstacleList.push(obstacle.uuid);
          let i = 0;
          for (const emit of obstacle.emits) {
            const aspect = this.getObjectAspect(obstacle.type, obstacle.name);
            setTimeout(() => {
              this.emitMolecule(
                [
                  obstacle.position[0],
                  obstacle.position[1] -
                    obstacle.scale *
                      obstacle.width *
                      (1 / aspect) *
                      containerAspect,
                ],
                emit
              );
            }, 1000 * i);
            i++;
          }
        }
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
          this.radiationFactor *
          temperature *
          timeFactor;
      }

      for (const region of this.collisionRegions) {
        let temperature = region.source.temperature - this.minTemperature;
        if (temperature < 0) temperature = 0;
        region.source.temperature -=
          region.source.heatRadiationCoefficient *
          this.radiationFactor *
          temperature *
          timeFactor;
      }
    }

    this.calculateWeather();

    this.moleculeList = this.moleculeList.filter(
      (item) =>
        item.globalWarmingFactor > 0 ||
        (item.position[0] > this.panOffsetMin[0] - 10 &&
          item.position[0] < this.panOffsetMax[0] + 10) ||
        (item.position[0] - 100 > this.panOffsetMin[0] - 10 &&
          item.position[0] - 100 < this.panOffsetMax[0] + 10)
    );
  }

  weatherTemperature = 0;
  calculateWeather(): void {
    if (this.weatherTemperature === Math.round(this.averageTemperature)) return;
    const replacements = [...this.colorFilter.replacements];
    replacements[0][1] = this.calculateTemperatureReplaceColor(
      replacements[0][0] as number
    );
    replacements[1][1] = this.calculateTemperatureReplaceColor(
      replacements[1][0] as number
    );
    replacements[2][1] = this.calculateTemperatureReplaceColor(
      replacements[2][0] as number
    );
    this.colorFilter.replacements = replacements;
    const averageTemperature = this.averageTemperature;
    if (averageTemperature < 0) {
      const frequency = Math.pow(2, Math.round(averageTemperature));
      const minFrequency = 0.004;
      if (frequency > minFrequency) this.snow.frequency = frequency;
      else this.snow.frequency = minFrequency;
    } else {
      this.snow.frequency = 0;
    }
    const hailStartTemperature = 26;
    if (averageTemperature > hailStartTemperature) {
      const intensity = averageTemperature - hailStartTemperature;
      const frequency = Math.pow(2, -Math.round(intensity)) * 2;
      const minFrequency = 0.004;
      if (frequency > minFrequency) this.hail.frequency = frequency;
      else this.hail.frequency = minFrequency;
      const scaleStatic = this.hail.behaviors.find(
        (behavior) => behavior.type === 'scaleStatic'
      );
      if (scaleStatic && scaleStatic.config) {
        let scale = intensity / 30;
        if (scale < 0.05) scale = 0.1;
        else if (scale > 0.25) scale = 0.2;
        scaleStatic.config.min = scale;
        scaleStatic.config.max = scale;
      }
    } else {
      this.hail.frequency = 0;
    }
    this.weatherTemperature = Math.round(this.averageTemperature);
  }

  calculateTemperatureReplaceColor(sourceColor: number): number {
    const startColor = new Color(`#${sourceColor.toString(16)}`);
    const temperatureRange =
      (this.upperTemperatureLimit - this.lowerTemperatureLimit) / 2;
    const neutralTemperature = temperatureRange + this.lowerTemperatureLimit;
    const lostColor = new Color(`#${sourceColor.toString(16)}`);
    if (this.averageTemperature < neutralTemperature) {
      lostColor.lch.l += 20;
      lostColor.lch.c -= 40;
      lostColor.lch.h += 100;
    } else {
      lostColor.lch.h -= 100;
    }
    const temperatureDifference =
      this.averageTemperature < neutralTemperature
        ? neutralTemperature - this.averageTemperature
        : this.averageTemperature - neutralTemperature;
    const colorWeight = temperatureDifference / temperatureRange;
    const targetColor = startColor.range(lostColor, {
      space: 'lch',
      outputSpace: 'srgb',
    })(colorWeight) as any;
    const colorString = targetColor.toString({
      format: 'hex',
      collapse: false,
    });
    return parseInt(colorString.substring(1), 16);
  }

  updateRegionFilter(region: CollisionRegion): void {
    const source = region.source as CoolItHitRegion;
    const color = this.getTemperatureColor(source.temperature);
    region.color = color.hex;
    region.alpha = this.gameOver ? 1 : color.alpha;
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
          if (
            obstacleObject &&
            Object.hasOwn(obstacleObject, 'filter') &&
            hitObstacle
          ) {
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
              wavelength: heatRadiation * 100, //100 * ray.intensity,
              speed: 10,
              brightness: 1.2,
              radius: heatRadiation * 100,
            },
            0
          )
        );
        rayObject.moveToPool();
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
    if (
      obstacleObject &&
      Object.hasOwn(obstacleObject, 'filter') &&
      hitObstacle
    ) {
      this.updateRegionFilter(obstacleObject as CollisionRegion);
    }
  }

  obstacleCollision(
    obstacleObject: GameObject,
    moleculeObject: GameObject
  ): void {
    if (!moleculeObject) return;
    const obstacleType =
      gameConfig.obstacles[this.levelType].categories[
        obstacleObject.source.type
      ].items[obstacleObject.source.name].type;
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
        moleculeObject.body.collisionFilter.category =
          this.CollisionGroups.ATMOSPHERIC_MOLECULE;
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
      this.$emit('replayFinished');
    }
  }
  //#endregion scroll

  //#region finished
  checkGameOver(): boolean {
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
      this.randomMessageNo = Math.round(Math.random() * 2) + 1;
      this.collisionAnimation.splice(0);
      clearInterval(this.interval);

      for (const obstacle of this.obstacleList) {
        obstacle.hitAnimation.splice(0);
        if (obstacle.temperature > this.maxTemperature)
          obstacle.temperature = this.maxTemperature;
        if (obstacle.temperature < this.minTemperature)
          obstacle.temperature = this.minTemperature;
      }
      for (const region of this.collisionRegions) {
        region.source.hitAnimation.splice(0);
        region.filter.splice(0);
        region.alpha = 0.7;
        if (region.source.temperature > this.maxTemperature)
          region.source.temperature = this.maxTemperature;
        if (region.source.temperature < this.minTemperature)
          region.source.temperature = this.minTemperature;
        region.text = `${Math.round(region.source.temperature)}°C`;
      }
      this.autoPanSpeed = 1.2;
      this.saveHighScore();
      return true;
    }
    return false;
  }

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

.statusOverlayCountDown {
  pointer-events: none;
  position: absolute;
  z-index: 5000;
  top: 5rem;
  right: 1rem;
  left: 1rem;
  text-align: center;
  font-size: var(--font-size-xxxxlarge);
  font-weight: var(--font-weight-bold);
  color: var(--color-dark-contrast);
}

.statusWeatherWarning {
  pointer-events: none;
  position: absolute;
  z-index: 100;
  bottom: 3rem;
  right: 1rem;
  left: 1rem;
  text-align: center;
  font-size: var(--font-size-xxlarge);
  color: var(--color-red);
  background-color: #ffffff77;
  border-radius: 2rem 2rem 2rem 0;
}

.statusGameOver {
  pointer-events: none;
  position: absolute;
  z-index: 100;
  top: 3rem;
  right: 1rem;
  left: 1rem;
  text-align: center;
  font-size: var(--font-size-large);
  color: var(--color-dark-contrast);
  background-color: #ffffff77;
  border-radius: 2rem 2rem 2rem 0;

  .el-button {
    pointer-events: auto;
    margin-top: 2rem;
  }

  .el-rate {
    height: 1.5rem;
  }

  .el-rate::v-deep(.el-rate__item) {
    //height: 3rem;
    line-height: 1.5rem;
    vertical-align: bottom;
  }

  .el-rate::v-deep(.el-icon) {
    height: 1.5em;
    width: 1.5em;

    svg {
      height: 1.5em;
      width: 1.5em;
    }
  }

  .column > div {
    height: 3rem;
    line-height: 3rem;
  }

  h1 {
    font-weight: var(--font-weight-bold);
    font-size: var(--font-size-xlarge);
  }
}
</style>
