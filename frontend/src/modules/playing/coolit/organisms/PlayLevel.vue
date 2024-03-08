<template>
  <div
    class="gameArea"
    v-if="playStateType === PlayStateType.play && levelType"
  >
    <!--

      :pixi-filter-list="[...collisionAnimation, ...hitAnimation]"
      :pixi-filter-list-background="[colorFilter]"
    -->
    <GameContainer
      v-if="!gameOver && spriteSheetsLoaded"
      ref="gameContainer"
      v-model:width="gameWidth"
      v-model:height="gameHeight"
      :detect-collision="true"
      :use-gravity="false"
      :wind-force="windForce"
      :border-category="CollisionGroups.BORDER"
      :background-texture="levelTypeSettings.background"
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
      :auto-pan-speed="autoPanSpeed"
      :reset-position-on-speed-changed="gameOver"
      :waitForDataLoad="waitForDataLoad"
      :endless-panning="!gameOver"
      :use-pre-calculation="!gameOver"
      :showRegionText="false"
    >
      <template v-slot:preRender>
        <container v-if="gameWidth && obstacleList.length > 0">
          <GameObject
            v-for="obstacle in obstacleList"
            :key="obstacle.uuid"
            :shape="obstacle.shape"
            :polygon-shape="obstacle.polygonShape"
            :show-bounds="false"
            :objectAnchor="obstacle.pivot"
            :object-space="ObjectSpace.RelativeToBackground"
            :posX="obstacle.position[0]"
            :posY="obstacle.position[1]"
            :angle="obstacle.rotation"
            :scale="obstacle.scale"
            :options="obstacle.options"
            :is-static="true"
            :affectedByForce="false"
            :source="obstacle"
            :sleep-if-not-visible="true"
            :clickable="obstacle.reflectionProbability > 0"
            @collision="obstacleCollision"
          >
            <SpriteConverter
              :texture="obstacle.texture"
              :anchor="obstacle.pivot"
              :space-width="obstacle.width"
              :aspect-ration="getObjectAspect(obstacle.type, obstacle.name)"
              :object-space="ObjectSpace.RelativeToBackground"
              :saturation="obstacle.saturation"
            >
            </SpriteConverter>
          </GameObject>
        </container>
      </template>
      <template v-slot:default>
        <container v-if="gameWidth && circleGradientTexture">
          <container v-if="!gameOver">
            <container>
              <sprite
                v-if="temperatureScaleTexture"
                :texture="temperatureScaleTexture"
                :width="gameWidth"
              />
              <sprite
                v-for="obstacle in obstacleList.filter(
                  (item) => item.calculateTemperature > 0
                )"
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
            <!--<animated-sprite
              ref="vehicle"
              v-if="vehicleStylesheets && randomVehicleName && active"
              :textures="vehicleStylesheets.animations[randomVehicleName]"
              :animation-speed="0.1"
              :width="vehicleWidth"
              :height="vehicleWidth / getVehicleAspect(randomVehicleName)"
              :x="vehicleXPosition"
              :y="vehicleYPosition"
              :anchor="[0.5, 1]"
              playing
              :loop="vehicleIsActive && !gameOver"
              @frame-change="animationFrameChanged"
            />-->
            <ParticlePlayer
              v-if="weatherStylesheets"
              :config="snow"
              :frequency="snow.frequency"
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
              :deep-clone-config="true"
              :disabled="
                !snow.frequency &&
                snow.startTime + minExtremeWeatherTime < Date.now()
              "
            />
            <ParticlePlayer
              v-if="weatherStylesheets"
              :config="hail"
              :frequency="hail.frequency"
              :default-texture="weatherStylesheets.textures['hail.png']"
              :deep-clone-config="true"
              :disabled="
                !hail.frequency &&
                hail.startTime + minExtremeWeatherTime < Date.now()
              "
            />
            <GameObject
              v-for="ray in rayList"
              :key="ray.uuid"
              shape="circle"
              :object-space="ObjectSpace.RelativeToBackground"
              :posX="ray.position[0]"
              :posY="ray.position[1]"
              :angle="ray.angle"
              :scale="ray.intensity"
              :options="getRayTypeOptions(ray.type)"
              :is-static="false"
              :affectedByForce="false"
              :show-bounds="false"
              :source="ray"
              :fix-size="rayParticleSize"
              :keep-inside="false"
              @collision="rayCollision"
              @initialised="rayInitialised"
              @init_error="rayInitError"
              @outside_drawing_space="leaveAtmosphere"
            >
              <container v-if="!ray.hit">
                <sprite
                  v-if="weatherStylesheets"
                  :texture="weatherStylesheets.textures['arrow.png']"
                  :x="ray.displayPoints[0].x * 0.2"
                  :width="rayParticleSize"
                  :height="rayParticleSize"
                  :anchor="0.5"
                  :tint="ray.type === RayType.light ? yellowColor : redColor"
                  :rotation="getRotation(ray.displayPoints)"
                  :alpha="ray.type === RayType.heatReadonly ? 0.5 : 1"
                ></sprite>
              </container>
              <container v-else>
                <animated-sprite
                  v-if="sunStylesheets && active"
                  :textures="sunStylesheets.animations['sun']"
                  :animation-speed="0.3"
                  :width="rayParticleSize"
                  :height="rayParticleSize"
                  :anchor="0.5"
                  :scale="0.05"
                  playing
                  :loop="false"
                />
              </container>
              <simple-rope
                v-if="
                  weatherStylesheets &&
                  ray.gameObject &&
                  !ray.gameObject?.isSleeping
                "
                :texture="weatherStylesheets.textures['light.png']"
                :x="0"
                :y="0"
                :scale="0.2"
                :tint="ray.type === RayType.light ? yellowColor : redColor"
                :points="ray.displayPoints"
                :alpha="ray.type === RayType.heatReadonly ? 0.5 : 1"
              />
            </GameObject>
            <GameObject
              v-for="molecule of moleculeList"
              :key="molecule.uuid"
              :is-active="molecule.isActive"
              shape="circle"
              :object-space="ObjectSpace.RelativeToBackground"
              :posX="molecule.position[0]"
              :posY="molecule.position[1]"
              :options="molecule.options"
              :is-static="false"
              :fix-size="molecule.size * moleculeSize"
              :source="molecule"
              :z-index="1"
              :fast-object-behaviour="FastObjectBehaviour.bounce"
              :sleep-if-not-visible="true"
              :angle="molecule.rotation"
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
              @hold="moleculeClicked"
              @release="moduleReleased"
              @visibility_changed="
                (visibility) => (molecule.isVisible = visibility)
              "
            >
              <SpriteConverter
                v-if="getMoleculeTexture(molecule.type)"
                :texture="getMoleculeTexture(molecule.type)"
                :anchor="0.5"
                :tint="molecule.color"
                :space-width="molecule.size * moleculeSize"
                :space-height="molecule.size * moleculeSize"
                :alpha="molecule.controllable ? 1 : 0.4"
              />
              <text
                v-if="molecule.gameObject && molecule.isClicked"
                :anchor="[0.5, 3]"
                :style="{
                  fontFamily: 'Arial',
                  fontSize: 18,
                  fill: contrastColor,
                }"
                :scale="textScaleFactor"
                :rotation="-molecule.gameObject.rotation"
              >
                {{
                  $t(
                    `module.playing.coolit.participant.moleculeInfo.${molecule.name}.title`
                  )
                }}
                {{ getMoleculeConfig(molecule.name).globalWarmingFactorReal }}
              </text>
            </GameObject>
          </container>
        </container>
      </template>
    </GameContainer>
    <GameContainer
      v-else-if="gameOver && spriteSheetsLoaded"
      ref="gameContainerReplay"
      v-model:width="gameWidth"
      v-model:height="gameHeight"
      :detect-collision="true"
      :use-gravity="false"
      :wind-force="windForce"
      :background-texture="levelTypeSettings.background"
      :background-position="BackgroundPosition.Cover"
      :background-movement="
        showObstacleSelection
          ? BackgroundMovement.Pause
          : BackgroundMovement.Auto
      "
      :collisionRegions="collisionRegions"
      :pixi-filter-list-background="[colorFilter]"
      :auto-pan-speed="autoPanSpeed"
      @updateOffset="updateOffset"
    >
      <template v-slot:default>
        <container v-if="gameWidth">
          <container v-if="$refs.gameContainerReplay">
            <sprite
              v-if="temperatureScaleResultTexture"
              :texture="temperatureScaleResultTexture"
              :width="gameWidth"
            />
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
            <container
              v-for="obstacle in obstacleList"
              :key="obstacle.uuid"
              :x="
                ((obstacle.position[0] - panOffsetMin[0]) / 100) *
                $refs.gameContainerReplay.backgroundTextureSize[0]
              "
              :y="
                (obstacle.position[1] / 100) *
                $refs.gameContainerReplay.backgroundTextureSize[1]
              "
              :pivot="obstacle.pivot"
              :scale="obstacle.scale"
              @pointerdown="obstacleClicked(obstacle)"
            >
              <SpriteConverter
                :colorOverlay="calculateTintColor(obstacle, 0.7)"
                :texture="obstacle.texture"
                :anchor="obstacle.pivot"
                :space-width="obstacle.width"
                :aspect-ration="getObjectAspect(obstacle.type, obstacle.name)"
                :object-space="ObjectSpace.RelativeToBackground"
                :custom-filters="replayFilter"
              >
              </SpriteConverter>
              <text
                :anchor="[0.5, 1]"
                :style="{ fontFamily: 'Arial', fontSize: 34, fill: '#ffffff' }"
                :scale="textScaleFactor"
              >
                {{ Math.round(obstacle.temperature) }}°C
              </text>
            </container>
          </container>
        </container>
      </template>
    </GameContainer>
    <div class="statusOverlay" v-if="!gameOver">
      {{ getTimeString(playTime) }}
      <el-rate v-model="stars" size="large" :max="3" :disabled="true" />
    </div>
    <div class="statusWeatherWarning" v-if="!gameOver && hypothermia">
      {{ $t('module.playing.coolit.participant.weatherInfo.hypothermia') }}
    </div>
    <div
      class="statusWeatherWarning"
      v-else-if="!gameOver && snow.frequency > 0"
    >
      {{ $t('module.playing.coolit.participant.weatherInfo.snow') }}
    </div>
    <div class="statusWeatherWarning" v-if="!gameOver && overheating">
      {{ $t('module.playing.coolit.participant.weatherInfo.overheating') }}
    </div>
    <div
      class="statusWeatherWarning"
      v-else-if="!gameOver && hail.frequency > 0"
    >
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
    <div
      class="statusOverlayCountDown"
      v-if="countDownEndTime > -1"
      :style="{ color: hypothermia || overheating ? redColor : contrastColor }"
    >
      {{ Math.ceil((countDownEndTime - Date.now()) / 1000) }}
    </div>
    <DrawerBottomOverlay
      v-if="selectedObstacle"
      v-model="showObstacleSelection"
      :title="$t('shared.organism.game.levelBuilder.itemSelection.selectItem')"
    >
      <el-space wrap>
        <div
          v-for="objectName of Object.keys(
            getLevelTypeCategoryItems(selectedObstacle.type)
          )"
          :key="objectName"
          class="clickable"
          @click="activeObstacleName = objectName"
        >
          <div
            class="obstacle-image"
            :class="{ selected: activeObstacleName === objectName }"
          >
            <img
              :src="categoryImages[selectedObstacle.type][objectName]"
              :alt="objectName"
            />
          </div>
          {{
            $t(
              `module.playing.coolit.participant.buildingInfo.${objectName}.title`
            )
          }}
        </div>
      </el-space>
      <div class="obstacleInfo" v-if="activeObstacleName">
        <div class="description">
          {{
            $t(
              `module.playing.coolit.participant.buildingInfo.${activeObstacleName}.description`
            )
          }}
        </div>
        <el-button
          type="primary"
          @click="changeSelectedObstacle(activeObstacleName)"
        >
          {{ $t('module.playing.coolit.participant.selectObstacle') }}
        </el-button>
      </div>
    </DrawerBottomOverlay>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import * as PIXI from 'pixi.js';
import { Prop, Watch } from 'vue-property-decorator';
import GameObject, {
  FastObjectBehaviour,
  IGameObjectSource,
} from '@/types/game/gameObject/GameObject';
import GameContainer, {
  BackgroundMovement,
  BackgroundPosition,
  CollisionBorderType,
  CollisionRegion,
} from '@/components/shared/atoms/game/GameContainer.vue';
import * as placeable from '@/types/game/Placeable';
import * as pixiUtil from '@/utils/pixi';
import { ObjectSpaceType } from '@/types/enum/ObjectSpaceType';
import { delay, until } from '@/utils/wait';
import * as tutorialService from '@/services/tutorial-service';
import * as votingService from '@/services/voting-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Tutorial } from '@/types/api/Tutorial';
import * as cashService from '@/services/cash-service';
import * as themeColors from '@/utils/themeColors';
import gameConfig from '@/modules/playing/coolit/data/gameConfig.json';
import { Idea } from '@/types/api/Idea';
import * as configParameter from '@/utils/game/configParameter';
import { v4 as uuidv4 } from 'uuid';
import Vec2 from 'vec2';
import Color from 'colorjs.io';
import { toRadians } from '@/utils/angle';
import Matter from 'matter-js';
import {
  ShockwaveFilter,
  MultiColorReplaceFilter,
  GlowFilter,
} from 'pixi-filters';
import * as matterUtil from '@/utils/matter';
import { Vote } from '@/types/api/Vote';
import * as CoolItConst from '@/modules/playing/coolit/utils/consts';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { EventType } from '@/types/enum/EventType';
import weatherConfig from '@/modules/playing/coolit/data/weather.json';
import * as preRenderer from '@/modules/playing/coolit/utils/preRender';
import DrawerBottomOverlay from '@/components/participant/molecules/DrawerBottomOverlay.vue';
import SpriteCanvas from '@/components/shared/atoms/game/SpriteCanvas.vue';

/* eslint-disable @typescript-eslint/no-explicit-any*/
const tutorialType = 'find-it-object';

enum RayType {
  light = 'light',
  heat = 'heat',
  heatReadonly = 'heatReadonly',
}

enum GasType {
  atmosphericGas = 'atmosphericGas',
  greenhouseGas = 'greenhouseGas',
}

interface Ray extends IGameObjectSource {
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

interface ObstacleCategoryConfig {
  settings: ObstacleCategorySettings;
  items: { [key: string]: ObstacleConfig };
}

interface ObstacleCategorySettings {
  icon: string;
  order: number;
  spritesheet: string;
  explanationText: string;
  placingRegions: [number, number][][];
}

interface ObstacleConfig {
  heatRationCoefficient: number;
  heatAbsorptionCoefficientLight: number;
  heatAbsorptionCoefficientHeat: number;
  heatRadiationCoefficient: number;
  initialTemperature: number;
  maxCount: number;
  width: number;
  shape: 'rect' | 'circle' | 'polygon';
  pivot: [number, number];
  emits: string[];
  reflectionProbability: number;
  calculateTemperature: boolean;
  type: string | undefined;
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
  options: any;
  hits: Hit[];
}

interface Hit {
  type: RayType;
  timeStamp: number;
  quantifier: number;
  radiationFactor: number;
}

interface MoleculeData extends CoolItHitRegion, IGameObjectSource {
  uuid: string;
  type: string;
  position: [number, number];
  globalWarmingFactor: number;
  size: number;
  controllable: boolean;
  absorbedByTree: boolean;
  color: string;
  rise: boolean;
  isActive: boolean;
  isClicked: boolean;
  isVisible: boolean;
  rotation: number;
}

interface CoolItObstacle
  extends placeable.Placeable,
    CoolItHitRegion,
    IGameObjectSource {}

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
      return ObjectSpaceType;
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
    DrawerBottomOverlay,
    SpriteCanvas,
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
  categoryImages: { [key: string]: { [key: string]: string } } = {};
  moleculeStylesheets: PIXI.Spritesheet | null = null;
  vehicleStylesheets: PIXI.Spritesheet | null = null;
  weatherStylesheets: PIXI.Spritesheet | null = null;
  sunStylesheets: PIXI.Spritesheet | null = null;
  startTime = Date.now();
  playTime = 0;
  autoPanSpeed = 0.4;
  emitRatePerStar = 1000;
  randomMessageNo = 1;
  showObstacleSelection = false;
  selectedObstacle: CoolItObstacle | null = null;
  textureToken = pixiUtil.createLoadingToken();

  temperatureColorSteps: { [key: number]: ColorValues } = {};

  rayList: Ray[] = [];
  rayPath: { [key: string]: { x: number; y: number }[][] } = {};
  circleGradientTexture: PIXI.Texture | null = null;
  temperatureMarkerTexture: PIXI.Texture | null = null;
  riverTexture: PIXI.Texture | null = null;
  moleculeTextures: { [key: string]: PIXI.Texture } = {};
  rayParticleSize = 10;
  collisionAnimation: any[] = [];
  hitAnimation: any[] = [];
  colorFilter: MultiColorReplaceFilter = new MultiColorReplaceFilter(
    [
      [0x7cc269, 0x7cc269],
      [0xafd5a4, 0xafd5a4],
      [0x417b40, 0x417b40],
    ],
    0.1
  );
  moleculeList: MoleculeData[] = [];
  //backgroundParticle: { [key: string]: PIXIParticles.EmitterConfigV3 } = {};
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
  vehicleXPosition = -100;
  vehicleHasEmitted = false;
  vehicleIsActive = false;
  readonly minExtremeWeatherTime = 1000;

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
    HEAT_READONLY_RAY: 1 << 10,
  });
  CollisionBorderType = CollisionBorderType;
  snow = weatherConfig.snow;
  hail = weatherConfig.rain;
  interactionTime = 0;
  isInteracting = false;
  windForce = 1;
  activeObstacleName = '';
  flashFilter: GlowFilter = new GlowFilter({
    outerStrength: 0,
    color: 0xffffff,
    alpha: 0.7,
  });
  replayFilter: PIXI.Filter[] = [this.flashFilter];

  temperatureScaleTexture: PIXI.Texture | null = null;
  temperatureScaleResultTexture: PIXI.Texture | null = null;
  //#endregion variables

  //#region get / set
  getWindForce(): number {
    if (this.isInteracting || !this.isReadyForPlay || this.waitForDataLoad) {
      return 1;
    } else {
      const delta = Date.now() - this.interactionTime;
      const force = Math.ceil(delta / 3000);
      const maxForce = 20;
      if (force > maxForce) return maxForce;
      return force;
    }
  }

  get levelTypeSettings(): any {
    return gameConfig.obstacles[this.levelType].settings;
  }

  get levelTypeCategories(): { [key: string]: ObstacleCategoryConfig } {
    return gameConfig.obstacles[this.levelType].categories;
  }

  getLevelTypeCategorySettings(category: string): ObstacleCategorySettings {
    return this.levelTypeCategories[category].settings;
  }

  getLevelTypeCategoryItems(category: string): {
    [key: string]: ObstacleConfig;
  } {
    return this.levelTypeCategories[category].items;
  }

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
    for (const obstacleType of Object.keys(this.levelTypeCategories)) {
      for (const obstacleName of Object.keys(
        this.getLevelTypeCategoryItems(obstacleType)
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
    for (const region of this.levelTypeSettings.heatRation) {
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
    for (const ration of this.levelTypeSettings.heatRation) {
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
          options: {},
          hits: [],
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

  get speedLevelAutoPanSpeed(): number {
    return 0.4 + this.speedLevel * 0.2;
  }

  get radiationFactor(): number {
    //return this.radiationConst;
    return this.speedLevelAutoPanSpeed / 10;
  }

  getTimeString(timestamp: number): string {
    const seconds = Math.floor(timestamp / 1000);
    const secondsString = `0${seconds % 60}`;
    return `${Math.floor(seconds / 60)}:${secondsString.slice(-2)}`;
  }

  getObstacleTypeOptions(obstacleType: string, obstacleName: string): any {
    const config = this.getLevelTypeCategoryItems(obstacleType)[obstacleName];
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
      case RayType.heatReadonly:
        return {
          name: 'heat readonly',
          frictionAir: 0,
          mass: 0,
          collisionFilter: {
            group: 0,
            category: this.CollisionGroups.HEAT_READONLY_RAY,
            mask: 0,
          },
        };
    }
  }

  getMoleculeTypeOptions(moleculeType: string): any {
    return {
      name: moleculeType,
      frictionAir: 0.01,
      restitution: 1,
      collisionFilter: {
        group: 0,
        category: this.getMoleculeCategory(moleculeType),
        mask: this.getMoleculeMask(moleculeType),
      },
    };
  }

  getMoleculeCategory(moleculeType: string): number {
    const moleculeConfig = gameConfig.molecules[moleculeType];
    if (moleculeConfig.type === GasType.atmosphericGas) {
      return this.CollisionGroups.ATMOSPHERIC_MOLECULE;
    }
    return this.CollisionGroups.GREENHOUSE_MOLECULE;
  }

  getMoleculeMask(moleculeType: string): number {
    const moleculeConfig = gameConfig.molecules[moleculeType];
    let mask =
      this.CollisionGroups.GREENHOUSE_MOLECULE |
      this.CollisionGroups.ATMOSPHERIC_MOLECULE |
      this.CollisionGroups.BORDER;
    if (moleculeConfig.controllable)
      mask = mask | this.CollisionGroups.HEAT_RAY | this.CollisionGroups.MOUSE;
    if (moleculeConfig.absorbedByTree)
      mask = mask | this.CollisionGroups.CARBON_SINK;
    return mask;
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

  getRotation(rayPoints: { x: number; y: number }[]): number {
    const p1 = rayPoints[0];
    const p2 = rayPoints[1];
    if (p1.x !== p2.x || p1.y !== p2.y) {
      const x = p2.x - p1.x;
      const y = p2.y - p1.y;
      const angle = Math.atan2(y, x) + Math.PI / 2; //radians
      return angle / 2;
    }
    return 0;
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

  get spriteSheetsLoaded(): boolean {
    let loaded = true;
    if (this.levelType) {
      for (const typeName of this.gameConfigTypes) {
        const settings = this.getLevelTypeCategorySettings(typeName);
        if (settings) {
          if (settings.spritesheet && !this.stylesheets[typeName]) {
            loaded = false;
            break;
          }
        }
      }
    } else loaded = false;
    return loaded;
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

  getTemperatureRange(temperature: number): number {
    return preRenderer.getTemperatureRange(temperature);
  }

  calculateTemperatureColor(temperature: number): ColorValues {
    let range = this.getTemperatureRange(temperature);
    if (range < 0) range = 0;
    if (range > 1) range = 1;
    const temperatureColor = preRenderer.getColorForTemperature(range);
    const hex = temperatureColor.toString({ format: 'hex', collapse: false });
    let dangerFactor = 0;
    if (temperature < preRenderer.lowerTemperatureLimit)
      dangerFactor =
        (preRenderer.lowerTemperatureLimit - temperature) /
        (preRenderer.lowerTemperatureLimit - preRenderer.minTemperature);
    if (temperature > preRenderer.upperTemperatureLimit)
      dangerFactor =
        (temperature - preRenderer.upperTemperatureLimit) /
        (preRenderer.maxTemperature - preRenderer.upperTemperatureLimit);
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
    if (index > preRenderer.maxTemperature)
      return this.temperatureColorSteps[preRenderer.maxTemperature];
    if (index < preRenderer.minTemperature)
      return this.temperatureColorSteps[preRenderer.minTemperature];
    return this.temperatureColorSteps[Math.round(temperature)];
  }

  get textScaleFactor(): number {
    return this.gameWidth / 700;
  }

  get moleculeSize(): number {
    return this.textScaleFactor * 270;
  }

  get vehicleWidth(): number {
    if (this.gameWidth > 0) return this.gameWidth / 3.5;
    return 100;
  }

  get vehicleYPosition(): number {
    return this.gameHeight * 0.975;
  }

  getMoleculeConfig(objectName: string): {
    type: string;
    reference: string;
    color: string;
    globalWarmingFactor: number;
    globalWarmingFactorReal: number;
    lifespan: number | string;
  } {
    if (objectName) {
      return gameConfig.molecules[objectName];
    }
    return {
      type: 'greenhouseGas',
      reference: '',
      color: '#ffffff',
      globalWarmingFactor: 1,
      globalWarmingFactorReal: 1,
      lifespan: 1,
    };
  }
  //#endregion get / set

  //#region watch
  @Watch('speedLevel', { immediate: true })
  onSpeedLevelChanged(): void {
    this.autoPanSpeed = this.speedLevelAutoPanSpeed;
  }

  @Watch('gameWidth', { immediate: true })
  onGameWidthChanged(): void {
    if (this.gameWidth) {
      this.vehicleXPosition = -this.vehicleWidth;
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
  private async allTexturesLoaded(): Promise<void> {
    this.loading = false;
  }

  private async texturesLoadingStart(): Promise<void> {
    this.loading = true;
  }

  readonly animationSteps = 10;
  loading = false;
  mounted(): void {
    this.eventBus.on(
      EventType.TEXTURES_LOADING_START,
      this.texturesLoadingStart
    );
    this.eventBus.on(EventType.ALL_TEXTURES_LOADED, this.allTexturesLoaded);

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
    initPath(RayType.heatReadonly);

    tutorialService.registerGetList(this.updateTutorial, this.authHeaderTyp);
    this.interval = setInterval(() => this.updateLoop(), this.intervalTime);

    pixiUtil
      .loadTexture('/assets/games/coolit/city/weather.json', this.textureToken)
      .then((sheet) => {
        this.weatherStylesheets = sheet;
      });
    pixiUtil
      .loadTexture('/assets/games/moveit/molecules.json', this.textureToken)
      .then((sheet) => {
        this.moleculeStylesheets = sheet;
        this.generateMoleculeTextures();
      });
    pixiUtil
      .loadTexture(
        '/assets/games/moveit/vehicle/vehicle_animation.json',
        this.textureToken
      )
      .then((sheet) => {
        this.vehicleStylesheets = sheet;
        this.setRandomAnimation();
      });
    pixiUtil
      .loadTexture('/assets/games/coolit/city/river.png', this.textureToken)
      .then((sheet) => {
        this.riverTexture = sheet;
      });
    pixiUtil
      .loadTexture('/assets/games/coolit/city/sun.json', this.textureToken)
      .then((sheet) => {
        this.sunStylesheets = sheet;
      });

    for (
      let i = preRenderer.minTemperature;
      i <= preRenderer.maxTemperature;
      i++
    ) {
      this.temperatureColorSteps[i] = this.calculateTemperatureColor(i);
    }
    this.initRays();
  }

  setRandomAnimation(): void {
    if (this.vehicleStylesheets) {
      const list = ['compact-car', 'e-car', 'sport-car', 'suv']; //Object.keys(this.vehicleStylesheets.animations);
      this.randomVehicleName = list[Math.floor(Math.random() * list.length)];
      this.vehicleXPosition = -this.vehicleWidth;
      this.vehicleHasEmitted = false;
      this.vehicleIsActive = false;
    }
  }

  animationFrameChanged(): void {
    this.vehicleXPosition += this.vehicleWidth / 50;
    this.manageCar();
  }

  stopCar(): void {
    if (this.vehicleSprite && this.vehicleSprite.playing)
      this.vehicleSprite.stop();
  }

  manageCar(): void {
    if (!this.vehicleSprite || !this.vehicleSprite.playing) return;
    if (!this.vehicleIsActive || this.gameOver) {
      this.stopCar();
      return;
    }
    if (this.vehicleXPosition > this.gameWidth + this.vehicleWidth / 2) {
      this.setRandomAnimation();
      setTimeout(() => {
        this.vehicleIsActive = true;
        if (this.vehicleSprite) this.vehicleSprite.play();
      }, 1000 + Math.random() * 10000);
    } else if (
      this.vehicleXPosition > this.gameWidth * 0.5 &&
      !this.vehicleHasEmitted
    ) {
      this.vehicleHasEmitted = true;
      const relativePosition = this.vehicleXPosition / this.gameWidth;
      const displayWidth = this.panOffsetMax[0] - this.panOffsetMin[0];
      const position = this.panOffsetMin[0] + displayWidth * relativePosition;
      this.emitMolecule(
        [position, 94],
        ['carbonDioxide', 'carbonDioxide', 'methane', 'carbonDioxide']
      );
    }
  }

  private emitMolecule(
    position: [number, number],
    moleculeName: string[] | string
  ): void {
    if (Array.isArray(moleculeName)) {
      const index = Math.floor(Math.random() * moleculeName.length);
      moleculeName = moleculeName[index];
    }
    const reactiveMolecule = this.moleculeList.find((item) => !item.isActive);
    if (reactiveMolecule) {
      this.updatedMolecule(reactiveMolecule, moleculeName);
      const gameObject = reactiveMolecule.gameObject;
      if (gameObject && gameObject.physcics.body) {
        matterUtil.resetBody(gameObject.physcics.body);
        gameObject.physcics.body.collisionFilter.category =
          this.getMoleculeCategory(moleculeName);
        gameObject.physcics.body.collisionFilter.mask =
          this.getMoleculeMask(moleculeName);
      }
      reactiveMolecule.position = position;
      reactiveMolecule.rise = true;
      reactiveMolecule.isActive = true;
    } else {
      this.moleculeList.push({
        gameObject: null,
        name: moleculeName,
        uuid: uuidv4(),
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
        isActive: true,
        isClicked: false,
        isVisible: true,
        rotation: 0,
        options: this.getMoleculeTypeOptions(moleculeName),
        hits: [],
      });
    }
  }

  convertToCoolItObstacle(
    value: placeable.PlaceableBase,
    categoryConfig: placeable.PlaceableThemeConfig,
    texture: string | PIXI.Texture
  ): CoolItObstacle {
    const result = placeable.convertToDetailData(
      value,
      categoryConfig,
      texture
    );
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
      gameObject: null,
      uuid: result.uuid,
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
      options: this.getObstacleTypeOptions(result.type, result.name),
      hits: [],
      zIndex: 0,
    };
  }

  lightIntensity = 2;
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
        gameObject: null,
        intensity: this.lightIntensity,
        hit: false,
      });
    }
  }

  initMolecules(): void {
    if (this.moleculeList.length > 0) return;
    for (const moleculeConfigName of Object.keys(gameConfig.molecules)) {
      const moleculeConfig = gameConfig.molecules[moleculeConfigName];
      const moleculeCount =
        moleculeConfig.particleCount +
        moleculeConfig.particleDeltaPerDegree * this.temperatureRise;
      const moleculeList: MoleculeData[] = [];
      for (let i = 0; i < moleculeCount; i++) {
        moleculeList.push({
          gameObject: null,
          name: moleculeConfigName,
          uuid: uuidv4(),
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
          isActive: true,
          isClicked: false,
          isVisible: true,
          rotation: 0,
          options: this.getMoleculeTypeOptions(moleculeConfigName),
          hits: [],
        });
      }
      const distance = 100 / moleculeCount;
      for (let i = 0; i < moleculeCount; i++) {
        moleculeList[i].position[0] = Math.random() * distance + i * distance;
      }
      this.moleculeList.push(...moleculeList);

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

  vehicleSprite: PIXI.AnimatedSprite | null = null;
  vehicleInitStart = false;
  containerReady(): void {
    if (!this.emitStart) {
      this.startTime = Date.now();
      this.emitLightRays(0, 0);
    }

    if (!this.vehicleInitStart) {
      this.vehicleInitStart = true;
      setTimeout(async () => {
        await until(() => !!this.vehicleStylesheets);
        this.vehicleIsActive = true;
        this.vehicleSprite = this.$refs.vehicle as PIXI.AnimatedSprite;
        if (this.vehicleSprite) this.vehicleSprite.play();
      }, Math.random() * 1000);
    }
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
    this.stopCar();
    this.active = false;
    clearInterval(this.interval);
    this.deregisterAll();
    this.vehicleIsActive = false;
    this.moleculeStylesheets = null;
    this.vehicleStylesheets = null;
    this.weatherStylesheets = null;
    this.sunStylesheets = null;
    pixiUtil.cleanupToken(this.textureToken);
    this.eventBus.off(
      EventType.TEXTURES_LOADING_START,
      this.texturesLoadingStart
    );
    this.eventBus.off(EventType.ALL_TEXTURES_LOADED, this.allTexturesLoaded);
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
          this.convertToCoolItObstacle(
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
        const settings = this.getLevelTypeCategorySettings(typeName);
        setTimeout(() => {
          if (
            settings &&
            settings.spritesheet &&
            this.previousLevelType !== this.levelType
          ) {
            pixiUtil
              .loadTexture(settings.spritesheet, this.textureToken)
              .then(async (sheet) => {
                this.stylesheets[typeName] = sheet;
                this.categoryImages[typeName] = {};
                await until(() => this.renderer);
                pixiUtil.convertSpritesheetToBase64(
                  sheet,
                  this.categoryImages[typeName],
                  this.renderer
                );
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
    this.temperatureMarkerTexture = pixiUtil.generateLinearGradientTexture(
      10,
      128,
      this.renderer,
      ['#ffffff', '#ffffff']
    );
    this.generateMoleculeTextures();
    this.temperatureScaleTexture = preRenderer.generateTemperatureScale(
      renderer,
      this.gameWidth,
      this.textScaleFactor,
      this.$t
    );
    this.temperatureScaleResultTexture =
      preRenderer.generateTemperatureScaleResult(
        renderer,
        this.gameWidth,
        this.textScaleFactor
      );
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
        /*if (!gameConfig.molecules[moleculeName].controllable) {
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
        }*/
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
      this.activateRay(
        [position, 0],
        [position, 0],
        [direction.x, direction.y],
        angle
      );
      if (this.active) {
        let minDelay = 3000 - this.emitRatePerStar * this.speedLevel;
        if (minDelay < 500) minDelay = 500;
        this.emitLightRays(minDelay, 1000);
      }
    }, delay);
  }

  activateRay(
    rayRelativePosition: [number, number],
    rayBodyPosition: [number, number],
    movingDirection: [number, number],
    rayAngle: number,
    rayType: RayType = RayType.light,
    rayIntensity = this.lightIntensity,
    checkInit = true
  ): void {
    const poolRay = this.rayList.find(
      (item) =>
        item.gameObject &&
        item.gameObject.physcics.readyForReuse() &&
        item.initialised
    );
    if (poolRay) {
      if (!checkInit || poolRay.type !== rayType) {
        poolRay.type = rayType;
        poolRay.intensity = rayIntensity;
        poolRay.displayPointsCount = 0;
        poolRay.animationIndex = 0;
        const points = this.calculateInitRayPoints(rayType, 1, 0);
        for (let i = 0; i < poolRay.points.length; i++) {
          poolRay.points[i].x = points[i].x;
          poolRay.points[i].y = points[i].y;
        }
        for (let i = 0; i < poolRay.displayPoints.length; i++) {
          poolRay.displayPoints[i].x = 0;
          poolRay.displayPoints[i].y = 0;
        }
        poolRay.hit = false;
        if (poolRay.gameObject?.physcics.body) {
          const options = this.getRayTypeOptions(poolRay.type);
          (poolRay.gameObject.physcics.body as any).name = options.name;
          poolRay.gameObject.physcics.body.collisionFilter.mask =
            options.collisionFilter.mask;
          poolRay.gameObject.physcics.body.collisionFilter.category =
            options.collisionFilter.category;
          Matter.Body.setInertia(poolRay.gameObject.physcics.body, Infinity);
        }
      }
      poolRay.angle = rayAngle;
      poolRay.position = rayRelativePosition;
      poolRay.direction = movingDirection;
      poolRay.startTime = Date.now();
      if (poolRay.gameObject)
        poolRay.gameObject.physcics.activateFromPool(rayBodyPosition);
      this.calculateRayVelocity(poolRay);
    }
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
    const ray = item.source as Ray;
    ray.initialised = true;
    if (item.physcics.body) {
      item.physcics.moveToPool(0);
    }
    const waitForDataLoad = !!this.rayList.find((item) => !item.initialised);
    if (!waitForDataLoad) {
      await until(() => !this.loading);
      const countDownTime = 3000;
      this.countDownEndTime = Date.now() + countDownTime;
      await delay(countDownTime);
      this.countDownEndTime = -1;
      this.interactionTime = Date.now();
      this.waitForDataLoad = false;
    }
  }

  calculateRayVelocity(ray: Ray): void {
    if (ray.gameObject?.physcics.body) {
      const force = Matter.Vector.create(ray.direction[0], ray.direction[1]);
      Matter.Body.setVelocity(ray.gameObject.physcics.body, force);
      this.setConstRaySpeed(ray);
    }
  }

  setConstRaySpeed(ray: Ray): void {
    if (ray.gameObject?.physcics.body) {
      (Matter.Body as any).setSpeed(
        ray.gameObject.physcics.body,
        this.autoPanSpeed * 10
      );
    }
  }

  rayInitError(item: GameObject): void {
    const ray = item.source as Ray;
    const index = this.rayList.indexOf(ray);
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
        rayObject.physcics.moveToPool();
      }, 3000);
    }
    if (ray.type === RayType.heatReadonly && (out.top || out.bottom)) {
      rayObject.physcics.moveToPool();
    }
  }
  //#endregion rays

  //#region loop
  updateTimeStamp = Date.now();
  gameOver = false;
  gameOverTimeStamp = -1;
  hypothermia = false;
  overheating = false;
  emitObstacleList: string[] = [];
  updateLoop(): void {
    if (!this.$refs.gameContainer) return;
    this.windForce = this.getWindForce();
    const updateTimeStamp = Date.now();
    const playTime = updateTimeStamp - this.startTime;
    let updateDelta = updateTimeStamp - this.updateTimeStamp;
    if (playTime < updateDelta) updateDelta = playTime;
    if (updateDelta > this.intervalTime * 3)
      updateDelta = this.intervalTime * 3;
    this.updateTimeStamp = updateTimeStamp;
    this.checkGameOver();
    if (this.gameOver) {
      return;
    }
    if (this.lightCollisionCount > 0) this.playTime += updateDelta;
    const activeRays = this.rayList.filter(
      (item) => item.gameObject && !item.gameObject.physcics.isSleeping
    );
    for (const ray of activeRays) {
      if (ray.initialised && ray.gameObject?.physcics.body?.speed) {
        ray.animationIndex++;
        if (ray.hit) {
          if (ray.displayPointsCount > ray.displayPoints.length)
            ray.displayPointsCount = ray.displayPoints.length;
          ray.displayPointsCount -= 10;
          if (ray.displayPointsCount < 1) ray.displayPointsCount = 1;
        } else {
          ray.displayPointsCount += 20;
        }
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
      this.$refs.gameContainer as GameContainer
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
        const position = this.panOffsetMin[0] + displayWidth * 0.5;
        if (
          position > obstacle.position[0] &&
          !this.emitObstacleList.includes(obstacle.uuid)
        ) {
          this.emitObstacleList.push(obstacle.uuid);
          const aspect = this.getObjectAspect(obstacle.type, obstacle.name);
          this.emitMolecule(
            [
              obstacle.position[0],
              obstacle.position[1] -
                obstacle.scale *
                  obstacle.width *
                  (1 / aspect) *
                  containerAspect,
            ],
            obstacle.emits
          );
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

    this.hitAnimation = this.hitAnimation.filter((item) => item.time < 5);
    for (const filter of this.hitAnimation) {
      filter.time += 0.1;
    }

    if (this.emittedRayCount > 0 && this.lightCollisionCount > 0) {
      for (const obstacle of this.obstacleList) {
        obstacle.temperature = this.calculateObstacleTemperature(
          obstacle.type,
          obstacle.name,
          obstacle.hits
        );
      }

      //const timeFactor = updateDelta / 5000;
      for (const region of this.collisionRegions) {
        let temperature =
          region.source.temperature - preRenderer.minTemperature;
        if (temperature < 0) temperature = 0;
        region.source.temperature = this.calculateObstacleTemperature(
          null,
          region.source.name,
          region.source.hits
        );
      }
    }

    this.calculateWeather();

    const inactiveMolecules = this.moleculeList.filter(
      (item) =>
        item.isActive && item.globalWarmingFactor === 0 && !item.isVisible //!this.isGameObjectVisible(item)
    );
    for (const item of inactiveMolecules) {
      item.isActive = false;
    }
  }

  flashDirection = 1;
  updateLoopReplay(): void {
    if (this.flashFilter.outerStrength >= 10) this.flashDirection = -1;
    if (this.flashFilter.outerStrength <= 0) this.flashDirection = 1;

    this.flashFilter.outerStrength += 2 * this.flashDirection;
  }

  calculateObstacleTemperatureRiseLight(
    heatAbsorptionCoefficientLight: number
  ): number {
    return heatAbsorptionCoefficientLight * this.absorptionFactor;
  }

  calculateObstacleTemperatureRiseHeat(
    heatAbsorptionCoefficientHeat: number,
    quantifier: number
  ): number {
    return heatAbsorptionCoefficientHeat * this.absorptionFactor * quantifier;
  }

  calculateObstacleTemperature(
    obstacleCategory: string | null,
    obstacleType: string,
    hits: Hit[],
    radiationFactor: number | null = null
  ): number {
    const config = obstacleCategory
      ? this.getLevelTypeCategoryItems(obstacleCategory)[obstacleType]
      : this.levelTypeSettings.heatRation.find(
          (item) => item.name === obstacleType
        );
    if (!config) return 0;
    let temperature = config.initialTemperature;
    let time = this.startTime;
    const reduceTemperature = (
      hitTime: number,
      radiationFactor: number
    ): void => {
      const updateDelta = hitTime - time;
      const timeFactor = updateDelta / 5000;
      let temperatureHelper = temperature - preRenderer.minTemperature;
      if (temperatureHelper < 0) temperatureHelper = 0;
      const temperatureDelta =
        config.heatRadiationCoefficient *
        radiationFactor *
        temperatureHelper *
        timeFactor;
      temperature -= temperatureDelta;
    };

    for (const hit of hits) {
      reduceTemperature(hit.timeStamp, hit.radiationFactor);
      if (hit.type === RayType.light) {
        const heatAbsorptionCoefficientLight =
          config.heatAbsorptionCoefficientLight ?? 1;
        temperature += this.calculateObstacleTemperatureRiseLight(
          heatAbsorptionCoefficientLight
        );
      } else {
        const heatAbsorptionCoefficientHeat =
          config.heatAbsorptionCoefficientHeat ?? 1;
        temperature += this.calculateObstacleTemperatureRiseHeat(
          heatAbsorptionCoefficientHeat,
          hit.quantifier
        );
      }
      time = hit.timeStamp;
    }
    reduceTemperature(
      this.startTime + this.playTime,
      radiationFactor ?? this.radiationFactor
    );
    return temperature;
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
      const maxFrequency = 0.5;
      if (this.snow.frequency === 0) this.snow.startTime = Date.now();
      if (frequency < minFrequency) this.snow.frequency = minFrequency;
      else if (frequency > maxFrequency) this.snow.frequency = maxFrequency;
      else this.snow.frequency = frequency;
    } else {
      this.snow.frequency = 0;
    }
    const hailStartTemperature = 26;
    if (averageTemperature > hailStartTemperature) {
      const intensity = averageTemperature - hailStartTemperature;
      const frequency = Math.pow(2, -Math.round(intensity)) * 2;
      const minTotalFrequencyValue = 0.015;
      const minRelativeFrequencyValue = this.hail.frequency * 0.8;
      const maxFrequency = 0.5;
      const minFrequency =
        minRelativeFrequencyValue === 0
          ? minTotalFrequencyValue * 10
          : minRelativeFrequencyValue < minTotalFrequencyValue
          ? minTotalFrequencyValue
          : minRelativeFrequencyValue;
      if (this.hail.frequency === 0) this.hail.startTime = Date.now();
      if (frequency < minFrequency) this.hail.frequency = minFrequency;
      else if (frequency > maxFrequency) this.hail.frequency = maxFrequency;
      else this.hail.frequency = frequency;
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
      (preRenderer.upperTemperatureLimit - preRenderer.lowerTemperatureLimit) /
      2;
    const neutralTemperature =
      temperatureRange + preRenderer.lowerTemperatureLimit;
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
    const index = this.rayList.indexOf(ray);
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
        if (!rayObject.physcics.body) return;
        const rayVelocity = [
          rayObject.physcics.body.velocity.x,
          rayObject.physcics.body.velocity.y,
        ];
        rayObject.physcics.body.isStatic = true;
        if (hitObstacle) {
          const hitPointScreen = matterUtil.calculateVisibleHitPoint(
            obstacleBody,
            rayBody,
            this.gameWidth,
            this.gameHeight
          );
          hitObstacle.hitCount++;
          hitObstacle.temperature += this.calculateObstacleTemperatureRiseLight(
            hitObstacle.heatAbsorptionCoefficientLight
          );
          hitObstacle.hits.push({
            type: RayType.light,
            timeStamp: Date.now(),
            quantifier: 1,
            radiationFactor: this.radiationFactor,
          });
          hitObstacle.hitAnimation.push(
            new ShockwaveFilter(
              hitPointScreen,
              {
                amplitude: 1,
                wavelength: 50,
                speed: 10,
                brightness: 1.5,
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
          } else {
            this.hitAnimation.push(
              new ShockwaveFilter(
                [rayBody.position.x, rayBody.position.y],
                {
                  amplitude: 1,
                  wavelength: 50,
                  speed: 10,
                  brightness: 1.5,
                  radius: 50,
                },
                0
              )
            );
          }
        }
        await delay(1000);
        if (!rayObject.physcics.body) return;
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
        rayObject.physcics.body.isStatic = false;
        Matter.Body.setVelocity(rayObject.physcics.body, force);
        const options = this.getRayTypeOptions(ray.type);
        rayObject.physcics.body.name = options.name;
        rayObject.physcics.body.collisionFilter.mask =
          options.collisionFilter.mask;
        rayObject.physcics.body.collisionFilter.category =
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
        const rayIntensity = ray.intensity;
        const hitPosition = rayObject.transformation.inputPosition;
        const hitPositionBody = [
          rayObject.physcics.body.position.x,
          rayObject.physcics.body.position.y,
        ] as [number, number];
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
        rayObject.physcics.moveToPool();
        for (const obstacle of this.obstacleList) {
          obstacle.temperature += this.calculateObstacleTemperatureRiseHeat(
            obstacle.heatAbsorptionCoefficientHeat,
            heatRadiation
          );
          obstacle.hits.push({
            type: RayType.heat,
            timeStamp: Date.now(),
            quantifier: heatRadiation,
            radiationFactor: this.radiationFactor,
          });
        }
        for (const region of this.collisionRegions) {
          region.source.temperature +=
            this.calculateObstacleTemperatureRiseHeat(
              region.source.heatAbsorptionCoefficientHeat,
              heatRadiation
            );
          region.source.hits.push({
            type: RayType.heat,
            timeStamp: Date.now(),
            quantifier: heatRadiation,
            radiationFactor: this.radiationFactor,
          });
        }

        //visualize heat
        this.activateRay(
          [...hitPosition],
          [...hitPositionBody],
          [0, 1],
          0,
          RayType.heatReadonly,
          (rayIntensity / (hitObstacle.heatAbsorptionCoefficientHeat + 1)) *
            hitObstacle.heatAbsorptionCoefficientHeat,
          false
        );
        this.activateRay(
          [...hitPosition],
          [...hitPositionBody],
          [0, -1],
          180,
          RayType.heatReadonly,
          rayIntensity / (hitObstacle.heatAbsorptionCoefficientHeat + 1),
          false
        );
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
    const obstacle = obstacleObject.source as CoolItObstacle;
    const molecule = moleculeObject.source as MoleculeData;
    const obstacleType = this.getLevelTypeCategoryItems(obstacle.type)[
      obstacle.name
    ].type;
    if (obstacleType === ObstacleType.carbonSink && molecule.absorbedByTree) {
      this.moleculeState[molecule.name].decreaseCount++;
      const moleculeName = 'oxygen';
      this.updatedMolecule(molecule, moleculeName);
      moleculeObject.physcics.body.collisionFilter.category =
        this.getMoleculeCategory(moleculeName);
      moleculeObject.physcics.body.collisionFilter.mask =
        this.getMoleculeMask(moleculeName);
    }
  }

  updatedMolecule(molecule: MoleculeData, newMoleculeName: string): void {
    molecule.type = newMoleculeName;
    molecule.name = newMoleculeName;
    const moleculeConfig = gameConfig.molecules[newMoleculeName];
    molecule.globalWarmingFactor = moleculeConfig.globalWarmingFactor;
    molecule.size = moleculeConfig.size;
    molecule.controllable = moleculeConfig.controllable;
    molecule.absorbedByTree = moleculeConfig.absorbedByTree;
    molecule.color = moleculeConfig.color;
    molecule.heatAbsorptionCoefficientHeat = moleculeConfig.globalWarmingFactor;
    molecule.heatAbsorptionCoefficientLight =
      moleculeConfig.globalWarmingFactor;
    molecule.heatRadiationCoefficient = moleculeConfig.globalWarmingFactor;
    molecule.options = this.getMoleculeTypeOptions(newMoleculeName);
  }

  moleculeClicked(item: any): void {
    this.interactionTime = Date.now();
    this.isInteracting = true;
    this.moleculeState[item.source.name].movedCount++;
    item.source.isClicked = true;
  }

  moduleReleased(item: any): void {
    item.source.isClicked = false;
    this.interactionTime = Date.now();
    this.isInteracting = false;
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
    if (
      this.gameOver &&
      max[0] >= 99.9 &&
      max[1] >= 99.9 &&
      Date.now() - this.gameOverTimeStamp > 1000
    ) {
      this.$emit('replayFinished');
    }
  }

  /*isGameObjectVisible(
    item: { position: [number, number] },
    delta = 10
  ): boolean {
    return (
      (item.position[0] > this.panOffsetMin[0] - delta &&
        item.position[0] < this.panOffsetMax[0] + delta) ||
      (item.position[0] - 100 > this.panOffsetMin[0] - delta &&
        item.position[0] - 100 < this.panOffsetMax[0] + delta)
    );
  }*/
  //#endregion scroll

  //#region finished
  checkGameOver(): boolean {
    if (this.countDownEndTime > -1) return true;
    let hypothermia = false;
    let overheating = false;
    const calculateGameOver = (): boolean => {
      const averageTemperature = this.averageTemperature;
      hypothermia = averageTemperature < preRenderer.lowerTemperatureLimit;
      overheating = averageTemperature > preRenderer.upperTemperatureLimit;
      let gameOver = hypothermia || overheating;
      if (!gameOver) {
        let obstacleHypothermiaCount = 0;
        let obstacleOverheatingCount = 0;
        for (const obstacle of this.obstacleList) {
          if (obstacle.temperature < preRenderer.lowerTemperatureLimit)
            obstacleHypothermiaCount++;
          if (obstacle.temperature > preRenderer.upperTemperatureLimit)
            obstacleOverheatingCount++;
        }
        for (const region of this.collisionRegions) {
          if (region.source.temperature < preRenderer.lowerTemperatureLimit)
            obstacleHypothermiaCount++;
          if (region.source.temperature > preRenderer.upperTemperatureLimit)
            obstacleOverheatingCount++;
        }
        let maxObstacleCount =
          (this.obstacleList.length + this.collisionRegions.length) / 5;
        if (maxObstacleCount < 3) maxObstacleCount = 3;
        if (obstacleHypothermiaCount > maxObstacleCount) hypothermia = true;
        if (obstacleOverheatingCount > maxObstacleCount) overheating = true;
        gameOver = hypothermia || overheating;
      }
      return gameOver;
    };

    const gameOver = calculateGameOver();
    this.hypothermia = hypothermia;
    this.overheating = overheating;
    if (gameOver) {
      const countDownTime = 5000;
      this.countDownEndTime = Date.now() + countDownTime;
      setTimeout(() => {
        this.countDownEndTime = -1;
        const gameOver = calculateGameOver();
        if (gameOver) {
          clearInterval(this.interval);
          this.interval = setInterval(
            () => this.updateLoopReplay(),
            this.intervalTime
          );
          this.gameOverTimeStamp = Date.now();
          this.gameOver = true;
          this.randomMessageNo = Math.round(Math.random() * 2) + 1;
          this.collisionAnimation.splice(0);

          for (const obstacle of this.obstacleList) {
            obstacle.hitAnimation.splice(0);
            if (obstacle.temperature > preRenderer.maxTemperature)
              obstacle.temperature = preRenderer.maxTemperature;
            if (obstacle.temperature < preRenderer.minTemperature)
              obstacle.temperature = preRenderer.minTemperature;
          }
          for (const region of this.collisionRegions) {
            region.source.hitAnimation.splice(0);
            region.filter.splice(0);
            region.alpha = 0.7;
            if (region.source.temperature > preRenderer.maxTemperature)
              region.source.temperature = preRenderer.maxTemperature;
            if (region.source.temperature < preRenderer.minTemperature)
              region.source.temperature = preRenderer.minTemperature;
            region.text = `${Math.round(region.source.temperature)}°C`;
          }
          this.autoPanSpeed = 1.2;
          this.saveHighScore();
        }
      }, countDownTime);
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

  //#region replay
  changeSelectedObstacle(name: string): void {
    if (this.selectedObstacle) {
      this.selectedObstacle.temperature = this.calculateObstacleTemperature(
        this.selectedObstacle.type,
        name,
        this.selectedObstacle.hits
      );
      this.selectedObstacle.name = name;
      this.selectedObstacle.texture = this.getTexture(
        this.selectedObstacle.type,
        name
      );
      this.selectedObstacle.width = this.getLevelTypeCategoryItems(
        this.selectedObstacle.type
      )[name].width;
    }
    this.showObstacleSelection = false;
  }

  obstacleClicked(obstacle: CoolItObstacle): void {
    this.selectedObstacle = obstacle;
    this.replayFilter = [];
    this.showObstacleSelection = true;
    clearInterval(this.interval);
  }
  //#endregion replay
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
  top: 8rem;
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
  top: 5rem;
  right: 1rem;
  left: 1rem;
  text-align: center;
  font-size: var(--font-size-xxlarge);
  color: var(--color-red);
  //background-color: #ffffff77;
  //border-radius: 2rem 2rem 2rem 0;
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

.obstacle-image {
  height: 5rem;
  width: 5rem;
  margin: 0.2rem;
  padding: 0.5rem;
  display: flex;

  img {
    margin: auto;
    max-height: 4rem;
  }
}

.selected {
  border: red 2px solid;
  border-radius: var(--border-radius);
}

.obstacleInfo {
  margin-top: 2rem;
  .description {
    margin-bottom: 1.5rem;
  }
}

.clickable {
  cursor: pointer;
  font-size: var(--font-size-xxsmall);
  text-align: center;
}
</style>
