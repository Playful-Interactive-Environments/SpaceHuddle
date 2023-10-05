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
      :border-category="CollisionGroups.BORDER"
      :background-texture="gameConfig.obstacles[levelType].settings.background"
      :background-position="BackgroundPosition.Cover"
      :background-movement="BackgroundMovement.Auto"
      :collisionRegions="collisionRegions"
      @initRenderer="initRenderer"
      @updateOffset="updateOffset"
      :show-bounds="false"
      :collision-borders="CollisionBorderType.Background"
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
              isSensor: true,
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
            :show-bounds="false"
            :source="ray"
            @collision="rayCollision"
            @initialised="rayInitialised"
            @initError="rayInitError"
            @outsideDrawingSpace="leaveAtmosphere"
          >
            <Graphics
              v-if="ray.type === RayType.light"
              :radius="5"
              :color="yellowColor"
              @render="drawCircle($event)"
            ></Graphics>
            <Graphics
              v-else
              :radius="5"
              :color="redColor"
              @render="drawCircle($event)"
            ></Graphics>
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
import Color from 'colorjs.io';
import { toRadians } from '@/utils/angle';
import Matter from 'matter-js';
import { ShockwaveFilter, ColorOverlayFilter } from 'pixi-filters';

/* eslint-disable @typescript-eslint/no-explicit-any*/
const tutorialType = 'find-it-object';

enum RayType {
  light = 'light',
  heat = 'heat',
}

interface Ray {
  uuid: string;
  type: RayType;
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

interface CoolItHitRegion {
  maxHitCount: number;
  hitCount: number;
  hitAnimation: ShockwaveFilter[];
  heatRationCoefficient: number;
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
  lightTexture!: PIXI.Texture;
  startTime = Date.now();

  rayList: Ray[] = [];
  rayPath: { [key: string]: { x: number; y: number }[][] } = {};

  playStateType = PlayStateType.play;
  PlayStateType = PlayStateType;
  RayType = RayType;
  interval!: any;
  readonly intervalTime = 100;

  CollisionGroups = Object.freeze({
    MOUSE: 1 << 0,
    OBSTACLE: 1 << 1,
    LIGHT_RAY: 1 << 2,
    HEAT_RAY: 1 << 3,
    MOLECULE: 1 << 4,
    GROUND: 1 << 5,
    BORDER: 1 << 6,
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
        } as CoolItHitRegion,
        filter: [],
      });
    }
    return regions;
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

  calculateRayPath(type: RayType, shift = 0): { x: number; y: number }[] {
    const rayPoints: { x: number; y: number }[] = [];
    const iPart = (Math.PI * 2) / this.rayPoints;
    const waveCount = type === RayType.light ? 3 : 1;
    for (let i = 0; i < this.rayPoints; i++) {
      rayPoints.push({
        x: Math.sin(i * iPart * waveCount + shift) * 40,
        y: -i * this.rayLength,
      });
    }
    return rayPoints;
  }

  readonly animationSteps = 8;
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
    this.emitLightRays(200, 200);

    pixiUtil
      .loadTexture('/assets/games/coolit/city/light.png')
      .then((texture) => {
        this.lightTexture = texture;
      });
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
    until(() => this.renderer).then(() => {
      pixiUtil.drawCircleWithGradient(circle, this.renderer);
    });
  }

  readonly rayPoints = 80;
  readonly rayLength = 500 / this.rayPoints;
  emitLightRays(minDelay = 2000, maxDelay = 3000): void {
    const delay = minDelay + Math.random() * maxDelay;
    const angle = 5 + Math.random() * 10;
    const direction = new Vec2(0, 1).rotate(-toRadians(angle));
    const displayWidth = this.panOffsetMax[0] - this.panOffsetMin[0];
    const position =
      this.panOffsetMin[0] +
      displayWidth / 5 +
      Math.random() * (displayWidth / 2);
    const points = this.calculateInitRayPoints(RayType.light, 1, 0);
    setTimeout(() => {
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
      (Matter.Body as any).setSpeed(ray.body, 5);
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
        ray.displayPointsCount += 10;
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
  }

  rayCollision(
    rayObject: GameObject,
    obstacleObject: GameObject | CollisionRegion,
    hitPoint: [number, number],
    hitPointScreen: [number, number]
  ): void {
    const ray = rayObject.source as Ray;
    const index = this.rayList.findIndex((item) => item.uuid === ray.uuid);
    const hitObstacle = obstacleObject?.source as CoolItHitRegion;
    if (hitObstacle) {
      hitObstacle.hitCount++;
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
    }
    if (index > -1) {
      if (ray.type === RayType.light) {
        ray.type = RayType.heat;
        ray.direction[1] *= -1;
        ray.intensity = hitObstacle?.heatRationCoefficient;
        ray.animationIndex = 0;
        const force = Matter.Vector.create(
          rayObject.body.velocity.x,
          rayObject.body.velocity.y * -1
        );
        Matter.Body.setVelocity(rayObject.body, force);
        //this.setConstRaySpeed(ray);
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
        ray.displayPointsCount = 0;
      }
    }
    if (Object.hasOwn(obstacleObject, 'filter') && hitObstacle) {
      this.updateRegionFilter(obstacleObject as CollisionRegion);
    }
  }

  updateRegionFilter(region: CollisionRegion): void {
    const source = region.source as CoolItHitRegion;
    const red = new Color(themeColors.getRedColor()).to('srgb') as any;
    let mixingFactor = source.hitCount / source.maxHitCount;
    if (mixingFactor > 1) mixingFactor = 1;
    region.filter = [
      new ColorOverlayFilter([red.r, red.g, red.b], mixingFactor / 2),
      ...source.hitAnimation,
    ];
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
