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
      :background-texture="gameConfig.obstacles[levelType].settings.background"
      :background-position="BackgroundPosition.Cover"
      :background-movement="BackgroundMovement.Auto"
      @initRenderer="initRenderer"
      @updateOffset="updateOffset"
    >
      <template v-slot:default>
        <container v-if="gameWidth">
          <GameObject
            v-for="obstacle in obstacleList"
            :key="obstacle.uuid"
            v-model:id="obstacle.id"
            :type="obstacle.shape"
            :polygon-shape="obstacle.polygonShape"
            :show-bounds="true"
            :anchor="obstacle.pivot"
            :object-space="ObjectSpace.RelativeToBackground"
            :x="obstacle.position[0]"
            :y="obstacle.position[1]"
            :rotation="obstacle.rotation"
            :scale="obstacle.scale"
            :options="{
              name: obstacle.name,
              collisionFilter: {
                group: 0,
                category: CollisionGroups.OBSTACLE,
              },
            }"
            :is-static="true"
            :source="obstacle"
          >
            <CustomSprite
              :colorOverlay="calculateTintColor(obstacle)"
              :texture="obstacle.texture"
              :anchor="obstacle.pivot"
              :width="obstacle.width"
              :aspect-ration="getObjectAspect(obstacle.type, obstacle.name)"
              :object-space="ObjectSpace.RelativeToBackground"
            >
            </CustomSprite>
          </GameObject>
          <GameObject
            v-for="(ray, index) in lightRayList"
            :key="index"
            type="circle"
            :object-space="ObjectSpace.RelativeToBackground"
            :x="ray.position[0]"
            :y="ray.position[1]"
            :options="{
              name: 'light',
              collisionFilter: {
                group: 0,
                category: CollisionGroups.LIGHT_RAY,
                mask: CollisionGroups.OBSTACLE,
              },
            }"
            :is-static="false"
            :show-bounds="false"
            :source="ray"
            @collision="lightCollision"
            @initialised="rayInitialised"
            @initError="rayInitError"
          >
            <Graphics
              :radius="10"
              :color="yellowColor"
              @render="drawCircle($event)"
            ></Graphics>
          </GameObject>
          <GameObject
            v-for="(ray, index) in heatRayList"
            :key="index"
            type="circle"
            :object-space="ObjectSpace.RelativeToBackground"
            :x="ray.position[0]"
            :y="ray.position[1]"
            :options="{
              name: 'light',
              collisionFilter: {
                group: 0,
                category: CollisionGroups.HEAT_RAY,
                mask: CollisionGroups.MOLECULE,
              },
            }"
            :is-static="false"
            :show-bounds="false"
            :source="ray"
            @collision="heatCollision"
            @initialised="rayInitialised"
            @initError="rayInitError"
          >
            <Graphics
              :radius="10"
              :color="redColor"
              @render="drawCircle($event)"
            ></Graphics>
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
} from '@/components/shared/atoms/game/GameContainer.vue';
import * as placeable from '@/types/game/Placeable';
import * as pixiUtil from '@/utils/pixi';
import { ObjectSpace } from '@/types/enum/ObjectSpace';
import CustomSprite from '@/components/shared/atoms/game/CustomSprite.vue';
import { until } from '@/utils/wait';
import * as tutorialService from '@/services/tutorial-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Tutorial } from '@/types/api/Tutorial';
import * as cashService from '@/services/cash-service';
import * as themeColors from '@/utils/themeColors';
import gameConfig from '@/modules/playing/coolit/data/gameConfig.json';
import { Idea } from '@/types/api/Idea';
import * as configParameter from '@/utils/game/configParameter';
import { v4 as uuidv4 } from 'uuid';
import Vec2 from 'vec2';
import * as turf from '@turf/turf';
import Color from 'colorjs.io';

/* eslint-disable @typescript-eslint/no-explicit-any*/
const tutorialType = 'find-it-object';

interface Ray {
  uuid: string;
  position: [number, number];
  direction: [number, number];
  initialised: boolean;
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

interface CoolItObstacle extends placeable.Placeable {
  maxHitCount: number;
  hitCount: number;
}

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
  tutorialSteps: Tutorial[] = [];
  levelType = '';
  gameConfig = gameConfig;
  active = true;

  obstacleList: CoolItObstacle[] = [];
  stylesheets: { [key: string]: PIXI.Spritesheet } = {};
  startTime = Date.now();

  lightRayList: Ray[] = [];
  heatRayList: Ray[] = [];

  playStateType = PlayStateType.play;
  PlayStateType = PlayStateType;
  interval!: any;
  readonly intervalTime = 100;

  CollisionGroups = Object.freeze({
    CONTROLLABLE: 1 << 0,
    OBSTACLE: 1 << 1,
    LIGHT_RAY: 1 << 2,
    HEAT_RAY: 1 << 3,
    MOLECULE: 1 << 4,
  });

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

  mounted(): void {
    tutorialService.registerGetList(this.updateTutorial, this.authHeaderTyp);
    this.interval = setInterval(() => this.updateRays(), this.intervalTime);
    this.emitLightRays();
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
    building: CoolItObstacle
  ): [number, number, number, number] {
    /*const toHex = (d: number): string => {
      if (d < 0) d = 0;
      if (d > 1) d = 1;
      return ('0' + Number(d * 255).toString(16)).slice(-2).toUpperCase();
    };
    const toColorString = (color: any): string => {
      return `#${toHex(color.r)}${toHex(color.g)}${toHex(color.b)}`;
    };*/
    const white = new Color('#ffffff').to('srgb');
    const red = new Color(themeColors.getRedColor()).to('srgb') as any;
    let mixingFactor = building.hitCount / building.maxHitCount;
    if (mixingFactor > 1) mixingFactor = 1;
    const tint = white.range(red, {
      space: 'lch',
      outputSpace: 'srgb',
    }) as any;
    const color = tint(mixingFactor);
    return [color.r, color.g, color.b, 0.4];
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
            pixiUtil.loadTexture(settings.spritesheet).then((sheet) => {
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
  }

  drawCircle(circle: PIXI.Graphics): void {
    if (this.renderer) {
      pixiUtil.drawCircleWithGradient(circle, this.renderer);
    }
  }

  emitLightRays(): void {
    const delay = 200 + Math.random() * 3000;
    const angle = 5 + Math.random() * 10;
    const direction = new Vec2(0, 1).rotate(-turf.degreesToRadians(angle));
    const displayWidth = this.panOffsetMax[0] - this.panOffsetMin[0];
    const position =
      this.panOffsetMin[0] +
      displayWidth / 5 +
      Math.random() * (displayWidth / 2);
    setTimeout(() => {
      this.lightRayList.push({
        uuid: uuidv4(),
        position: [position, 0],
        direction: [direction.x, direction.y],
        initialised: false,
      });
      if (this.active) this.emitLightRays();
    }, delay);
  }

  rayInitialised(item: GameObject): void {
    item.source.initialised = true;
  }

  rayInitError(item: GameObject): void {
    const ray = item.source as Ray;
    console.log('rayInitError', ray);
    const index = this.lightRayList.findIndex((item) => item.uuid === ray.uuid);
    if (index > -1) {
      this.lightRayList.splice(index, 1);
    } else {
      const index = this.heatRayList.findIndex(
        (item) => item.uuid === ray.uuid
      );
      if (index > -1) {
        this.heatRayList.splice(index, 1);
      }
    }
  }

  updateRays(): void {
    for (const ray of this.lightRayList) {
      if (ray.initialised) {
        ray.position[0] += ray.direction[0];
        ray.position[1] += ray.direction[1];
      }
    }
    for (const ray of this.heatRayList) {
      if (ray.initialised) {
        ray.position[0] += ray.direction[0];
        ray.position[1] += ray.direction[1];
      }
    }
  }

  lightCollision(light: GameObject, building: GameObject): void {
    const ray = light.source as Ray;
    const index = this.lightRayList.findIndex((item) => item.uuid === ray.uuid);
    if (index > -1) {
      const hitBuilding = building?.source as CoolItObstacle;
      if (hitBuilding) {
        hitBuilding.hitCount++;
      }
      this.lightRayList.splice(index, 1);
      ray.direction[1] *= -1;
      ray.initialised = false;
      this.heatRayList.push(ray);
    }
  }

  heatCollision(heat: GameObject, molecule: GameObject): void {
    console.log(heat, molecule);
  }

  updateOffset(
    value: [number, number],
    min: [number, number],
    max: [number, number]
  ): void {
    this.panOffsetMin = min;
    this.panOffsetMax = max;
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
