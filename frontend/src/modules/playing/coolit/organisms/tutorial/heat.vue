<template>
  <div class="gameArea">
    <GameContainer
      ref="gameContainer"
      v-model:width="width"
      v-model:height="height"
      :detect-collision="true"
      :use-gravity="false"
      :wind-force="!isHit && !isDone ? 3 : 0"
      :border-category="CollisionGroups.BORDER"
      :show-bounds="false"
      background-color="#a0d4d9"
      background-texture="/assets/games/coolit/tutorial/sky.png"
      :background-position="BackgroundPosition.Cover"
      :background-movement="
        !isHit && !isDone ? BackgroundMovement.Auto : BackgroundMovement.Break
      "
      :collision-borders="CollisionBorderType.Background"
      :auto-pan-speed="0.8"
      :auto-pan-direction="[0, -1]"
      :endless-panning="true"
      @initRenderer="initRenderer"
    >
      <template v-slot:default>
        <container>
          <container :y="getTotalPanDistance() - height * 1.33">
            <graphics :x="0" :y="height / 3" @render="onDrawLine" />
            <graphics :x="0" :y="(height / 3) * 2" @render="onDrawLine" />
            <graphics :x="0" :y="(height / 3) * 3" @render="onDrawLine" />
            <graphics :x="0" :y="(height / 3) * 4" @render="onDrawLine" />
            <graphics :x="0" :y="(height / 3) * 5" @render="onDrawLine" />
            <graphics :x="0" :y="(height / 3) * 6" @render="onDrawLine" />
            <text
              :anchor="0.5"
              :style="{
                fontFamily: 'Arial',
                fontSize: 18,
                fill: contrastColor,
              }"
              :scale="textScaleFactor"
              :x="width / 2"
              :y="(height / 3) * 2.5"
            >
              {{
                $t('module.playing.coolit.participant.tutorial.heatGame.goal')
              }}
            </text>
          </container>
          <GameObject
            v-if="weatherStylesheets"
            shape="rect"
            :object-space="ObjectSpace.Absolute"
            :posX="width / 2"
            :posY="height / 2"
            :angle="180"
            :scale="3"
            :options="{
              name: 'heat',
              frictionAir: 0,
              mass: 0,
              collisionFilter: {
                group: 0,
                category: this.CollisionGroups.HEAT_RAY,
                mask: this.CollisionGroups.GREENHOUSE_MOLECULE,
              },
            }"
            :is-static="true"
            :affectedByForce="false"
            :objectAnchor="[0.5, 0]"
            @collision="rayCollision"
          >
            <container>
              <sprite
                :texture="weatherStylesheets.textures['arrow.png']"
                :x="rayDisplayPoints[0].x * 0.2"
                :width="rayParticleSize"
                :height="rayParticleSize"
                :anchor="0.5"
                :tint="redColor"
                :rotation="getRotation(rayDisplayPoints)"
                :alpha="1"
              ></sprite>
            </container>
            <simple-rope
              :texture="weatherStylesheets.textures['light.png']"
              :x="0"
              :y="0"
              :scale="0.2"
              :tint="redColor"
              :points="rayDisplayPoints"
              :alpha="1"
            />
          </GameObject>
          <GameObject
            v-for="molecule of moleculeList"
            :key="molecule.uuid"
            shape="circle"
            :object-space="ObjectSpace.RelativeToBackground"
            :posX="molecule.position[0]"
            :posY="molecule.position[1]"
            :options="molecule.options"
            :fix-size="molecule.size * moleculeSize"
            :source="molecule"
            :z-index="1"
            :fast-object-behaviour="FastObjectBehaviour.bounce"
            :sleep-if-not-visible="true"
            :angle="molecule.rotation"
          >
            <sprite
              v-if="getMoleculeTexture(molecule.type)"
              :texture="getMoleculeTexture(molecule.type)"
              :width="molecule.size * moleculeSize"
              :height="molecule.size * moleculeSize"
              :anchor="0.5"
              :tint="molecule.color"
              :alpha="molecule.controllable ? 1 : 0.4"
            />
            <text
              v-if="
                molecule.controllable &&
                molecule.gameObject &&
                molecule.gameObject.transformation.inputPosition[0] < 65 &&
                molecule.gameObject.transformation.inputPosition[0] > 35
              "
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
                $t('module.playing.coolit.participant.tutorial.heatGame.move')
              }}
            </text>
          </GameObject>
        </container>
      </template>
    </GameContainer>
    <DrawerBottomOverlay
      v-model="isHit"
      :title="$t('module.playing.coolit.participant.tutorial.heatGame.hit')"
    >
      <div class="moleculeInfo" v-if="activeMoleculeName">
        <div class="title">
          {{
            $t(
              `module.playing.coolit.participant.moleculeInfo.${activeMoleculeName}.title`
            )
          }}
          ({{ getMoleculeConfig(activeMoleculeName).formula }})
        </div>
        <div class="molecule">
          <div
            class="molecule-image"
            :style="{
              '--molecule-color': getMoleculeConfig(activeMoleculeName).color,
            }"
          >
            <img
              :src="moleculeImages[activeMoleculeName]"
              :alt="activeMoleculeName"
            />
          </div>
        </div>
        <div class="description">
          {{
            $t(
              `module.playing.coolit.participant.moleculeInfo.${activeMoleculeName}.description`
            )
          }}
        </div>
        <div class="columns">
          <div class="column is-half pros">
            <h3>
              {{ $t('module.playing.coolit.participant.moleculeInfo.pro') }}
            </h3>
            {{
              $t(
                `module.playing.coolit.participant.moleculeInfo.${activeMoleculeName}.pros`
              )
            }}
          </div>
          <div class="column is-half cons">
            <h3>
              {{ $t('module.playing.coolit.participant.moleculeInfo.contra') }}
            </h3>
            {{
              $t(
                `module.playing.coolit.participant.moleculeInfo.${activeMoleculeName}.cons`
              )
            }}
          </div>
        </div>
        <div class="reference">
          <a
            v-for="reference of getMoleculeConfig(activeMoleculeName).reference"
            :key="reference"
            :href="reference"
          >
            {{ reference }}
          </a>
        </div>
        <div class="values">
          <table>
            <tr>
              <th>
                {{
                  $t(
                    'module.playing.coolit.participant.moleculeInfo.globalWarmingFactor'
                  )
                }}
              </th>
              <td>
                {{
                  getMoleculeConfig(activeMoleculeName).globalWarmingFactorReal
                }}
                {{
                  $t('module.playing.coolit.participant.moleculeInfo.gwpInfo')
                }}
              </td>
            </tr>
            <tr v-if="getMoleculeConfig(activeMoleculeName).lifespan">
              <th>
                {{
                  $t('module.playing.coolit.participant.moleculeInfo.lifespan')
                }}
              </th>
              <td>
                {{ getMoleculeConfig(activeMoleculeName).lifespan }}
                {{ $t('module.playing.coolit.participant.moleculeInfo.years') }}
              </td>
            </tr>
            <tr v-if="getMoleculeConfig(activeMoleculeName).rationAtmosphere">
              <th>
                {{
                  $t(
                    'module.playing.coolit.participant.moleculeInfo.rationAtmosphere'
                  )
                }}
              </th>
              <td>
                {{ getMoleculeConfig(activeMoleculeName).rationAtmosphere }}%
              </td>
            </tr>
            <tr v-if="getMoleculeConfig(activeMoleculeName).rationGreenhouse">
              <th>
                {{
                  $t(
                    'module.playing.coolit.participant.moleculeInfo.rationGreenhouse'
                  )
                }}
              </th>
              <td>
                {{ getMoleculeConfig(activeMoleculeName).rationGreenhouse }}%
              </td>
            </tr>
            <tr v-if="getMoleculeConfig(activeMoleculeName).impactGreenhouse">
              <th>
                {{
                  $t(
                    'module.playing.coolit.participant.moleculeInfo.impactGreenhouse'
                  )
                }}
              </th>
              <td>
                {{ getMoleculeConfig(activeMoleculeName).impactGreenhouse }}%
              </td>
            </tr>
            <tr v-if="getMoleculeConfig(activeMoleculeName).riseFactor">
              <th>
                {{
                  $t(
                    'module.playing.coolit.participant.moleculeInfo.riseFactor'
                  )
                }}
              </th>
              <td>{{ getMoleculeConfig(activeMoleculeName).riseFactor }}%</td>
            </tr>
          </table>
        </div>
      </div>
    </DrawerBottomOverlay>
    <div v-if="isDone" class="overlay-container">
      <div>
        <div>
          {{
            $t('module.playing.coolit.participant.tutorial.heatGame.hitCount')
          }}
        </div>
        <div>
          {{ hitCount }}
        </div>
        <div>
          {{
            $t(
              'module.playing.coolit.participant.tutorial.heatGame.temperature'
            )
          }}
        </div>
        <div>
          {{ Math.round(temperature) }}°C
          <el-slider
            class="thermometer"
            v-model="temperature"
            disabled
            vertical
            height="200px"
            :max="40"
          />
        </div>
      </div>
    </div>
    <div v-else class="overlay">
      <div>
        <el-slider
          class="thermometer"
          v-model="temperature"
          disabled
          vertical
          height="200px"
          :max="40"
        />
      </div>
      <div>{{ Math.round(temperature) }}°C</div>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Watch } from 'vue-property-decorator';
import GameContainer, {
  BackgroundMovement,
  CollisionBorderType,
  BackgroundPosition,
} from '@/components/shared/atoms/game/GameContainer.vue';
import { ObjectSpaceType } from '@/types/enum/ObjectSpaceType';
import GameObject, {
  FastObjectBehaviour,
} from '@/types/game/gameObject/GameObject';
import Matter from 'matter-js';
import * as PIXI from 'pixi.js';
import * as pixiUtil from '@/utils/pixi';
import * as themeColors from '@/utils/themeColors';
import gameConfig from '@/modules/playing/coolit/data/gameConfig.json';
import { v4 as uuidv4 } from 'uuid';
import DrawerBottomOverlay from '@/components/participant/molecules/DrawerBottomOverlay.vue';
import { delay } from '@/utils/wait';
import * as matterUtil from '@/utils/matter';

enum GasType {
  atmosphericGas = 'atmosphericGas',
  greenhouseGas = 'greenhouseGas',
}
/* eslint-disable @typescript-eslint/no-explicit-any*/

interface MoleculeData {
  gameObject: GameObject | null;
  uuid: string;
  type: string;
  position: [number, number];
  size: number;
  controllable: boolean;
  color: string;
  rotation: number;
  options: any;
}

@Options({
  computed: {
    ObjectSpace() {
      return ObjectSpaceType;
    },
  },
  components: { GameContainer, DrawerBottomOverlay },
  emits: ['done'],
})
export default class heat extends Vue {
  renderer!: PIXI.Renderer;
  width = 0;
  height = 0;
  rayParticleSize = 10;
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
  BackgroundMovement = BackgroundMovement;
  BackgroundPosition = BackgroundPosition;
  FastObjectBehaviour = FastObjectBehaviour;
  CollisionBorderType = CollisionBorderType;
  weatherStylesheets: PIXI.Spritesheet | null = null;
  moleculeStylesheets: PIXI.Spritesheet | null = null;
  readonly rayPoints = 80;
  readonly rayLength = 500 / this.rayPoints;
  readonly animationSteps = 10;
  animationStep = 0;
  textureToken = pixiUtil.createLoadingToken();
  rayPath: { x: number; y: number }[][] = [];
  rayDisplayPoints = [...this.calculateInitRayPoints(1, 0)];
  readonly intervalTime = 100;
  interval!: any;
  moleculeList: MoleculeData[] = [];
  circleGradientTexture: PIXI.Texture | null = null;
  moleculeTextures: { [key: string]: PIXI.Texture } = {};
  activeMoleculeName = '';
  isHit = false;
  isDone = false;
  moleculeImages: { [key: string]: string } = {};
  temperature = 20;
  hitCount = 0;

  get redColor(): string {
    return themeColors.getRedColor();
  }

  get contrastColor(): string {
    return themeColors.getContrastColor();
  }

  get textScaleFactor(): number {
    return this.width / 700;
  }

  get moleculeSize(): number {
    return this.textScaleFactor * 270;
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

  getMoleculeTexture(objectName: string): PIXI.Texture | string {
    if (this.moleculeTextures[objectName])
      return this.moleculeTextures[objectName];
    if (this.moleculeStylesheets)
      return this.moleculeStylesheets.textures[objectName];
    return '';
  }

  getTotalPanDistance(): number {
    const gameContainer = this.$refs['gameContainer'] as GameContainer;
    if (gameContainer) return gameContainer.totalPanDistance;
    return 0;
  }

  mounted(): void {
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
        pixiUtil.convertSpritesheetToBase64(sheet, this.moleculeImages);
      });
    this.initMolecules();

    this.interval = setInterval(() => this.updateLoop(), this.intervalTime);
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
      }
    }
  }

  unmounted(): void {
    pixiUtil.cleanupToken(this.textureToken);
    clearInterval(this.interval);
  }

  closeInfoTime = 0;
  @Watch('isHit', { immediate: true })
  onIsHitChanged(): void {
    if (!this.isHit) {
      this.closeInfoTime = Date.now();
    }
  }

  initRenderer(renderer: PIXI.Renderer): void {
    this.renderer = renderer;
    this.circleGradientTexture = pixiUtil.generateCircleGradientTexture(
      256,
      this.renderer
    );
    this.generateMoleculeTextures();
  }

  calculateRayPath(shift = 0): { x: number; y: number }[] {
    const rayPoints: { x: number; y: number }[] = [];
    const iPart = (Math.PI * 2) / this.rayPoints;
    const waveCount = 1.5;
    for (let i = 0; i < this.rayPoints; i++) {
      rayPoints.push({
        x: Math.sin(i * iPart * waveCount + shift) * 40,
        y: -i * this.rayLength,
      });
    }
    return rayPoints;
  }

  calculateInitRayPoints(
    intensity: number,
    animationStep: number
  ): { x: number; y: number }[] {
    if (this.rayPath.length === 0) {
      for (let i = 0; i < this.animationSteps; i++) {
        this.rayPath.push(
          this.calculateRayPath((i * (Math.PI * 2)) / this.animationSteps)
        );
      }
    }

    return this.rayPath[animationStep % this.animationSteps].map((item) => {
      return {
        x: item.x / intensity,
        y: item.y / intensity,
      };
    });
  }

  readonly reactTime = 2000;
  async rayCollision(
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    rayObject: GameObject,
    obstacleObject: GameObject,
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    rayBody: Matter.Body,
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    obstacleBody: Matter.Body
  ): Promise<void> {
    if (!this.isHit && this.closeInfoTime + this.reactTime < Date.now()) {
      this.activeMoleculeName = obstacleObject.options.name as string;
      this.isHit = true;
      this.hitCount++;
      this.temperature +=
        gameConfig.molecules[this.activeMoleculeName].globalWarmingFactor;

      const gameContainer = this.$refs['gameContainer'] as GameContainer;
      if (gameContainer) {
        matterUtil.resetBody(obstacleBody, gameContainer.mouseConstraint);
      } else {
        Matter.Body.setVelocity(obstacleBody, {x: 0, y: 0});
      }
      Matter.Body.setPosition(obstacleBody, {
        x: this.width * 0.1,
        y: obstacleBody.position.y,
      });
    }
  }

  totalPanDistance = 0;
  updateLoop(): void {
    this.animationStep++;
    const points = this.calculateInitRayPoints(1, this.animationStep);
    for (let i = 0; i < this.rayDisplayPoints.length; i++) {
      this.rayDisplayPoints[i] = points[i];
    }
    this.totalPanDistance = this.getTotalPanDistance();
    if (this.totalPanDistance > 1000) {
      this.done();
    }
  }

  async done(): Promise<void> {
    clearInterval(this.interval);
    this.isDone = true;
    this.isHit = false;
    await delay(10000);
    this.$emit('done');
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

  getMoleculeConfig(objectName: string): {
    formula: string;
    type: string;
    reference: string;
    color: string;
    globalWarmingFactor: number;
    globalWarmingFactorReal: number;
    lifespan: number | string;
    rationAtmosphere: number;
    rationGreenhouse: number;
    riseFactor: number;
    impactGreenhouse: number;
  } {
    if (objectName) {
      return gameConfig.molecules[objectName];
    }
    return {
      formula: '',
      type: 'greenhouseGas',
      reference: '',
      color: '#ffffff',
      globalWarmingFactor: 1,
      globalWarmingFactorReal: 1,
      lifespan: 1,
      rationAtmosphere: 0,
      rationGreenhouse: 0,
      riseFactor: 0,
      impactGreenhouse: 0,
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

  initMolecules(): void {
    if (this.moleculeList.length > 0) return;
    for (const moleculeConfigName of Object.keys(gameConfig.molecules)) {
      const moleculeConfig = gameConfig.molecules[moleculeConfigName];
      const moleculeCount = 2;
      const moleculeList: MoleculeData[] = [];
      for (let i = 0; i < moleculeCount; i++) {
        moleculeList.push({
          gameObject: null,
          uuid: uuidv4(),
          type: moleculeConfigName,
          position: [Math.random() * 100, Math.random() * 100],
          size: moleculeConfig.size,
          controllable: moleculeConfig.controllable,
          color: moleculeConfig.color,
          rotation: 0,
          options: this.getMoleculeTypeOptions(moleculeConfigName),
        });
      }
      this.moleculeList.push(...moleculeList);
    }
  }

  onDrawLine(graphics: PIXI.Graphics): void {
    graphics.lineStyle(2, 0xffffff, 1);
    graphics.moveTo(0, 0);
    graphics.lineTo(this.width, 0);
  }
}
</script>

<style lang="scss" scoped>
.gameArea {
  height: calc(100%);
  width: 100%;
  position: relative;
}

.moleculeInfo {
  .title {
    color: var(--color-playing);
    font-size: var(--font-size-xlarge);
    font-weight: var(--font-weight-bold);
  }

  .subtitle {
    color: var(--color-playing-light);
    font-size: var(--font-size-large);
    font-weight: var(--font-weight-semibold);
  }

  .description {
    margin-bottom: 1.5rem;
  }

  .pros {
    //color: var(--color-green);
    //margin-bottom: 1.5rem;
    //float: left;
    //width: 48%;

    h3 {
      font-weight: var(--font-weight-bold);
    }
  }

  .cons {
    //color: var(--color-red);
    //margin-bottom: 1.5rem;
    //float: right;
    //width: 48%;

    h3 {
      font-weight: var(--font-weight-bold);
    }
  }

  .reference {
    //clear: both;
    font-size: var(--font-size-xxsmall);
    width: 100%;
    text-align: right;

    a {
      display: block;
    }
  }

  .values table {
    margin-top: 2rem;

    th {
      padding-right: 1rem;
    }

    th,
    td {
      border-bottom: 1px solid #ddd;
      padding-bottom: 0.5rem;
      padding-top: 0.5rem;
    }
  }
}

.molecule-image {
  background-image: radial-gradient(
    circle,
    var(--molecule-color) 30%,
    #ffffff00 75%
  );
  border-radius: 50%;
  height: 5rem;
  width: 5rem;
  margin: 0.2rem;
  padding: 1rem;
  display: flex;

  img {
    margin: auto;
  }
}

.thermometer {
  padding: 2rem;
  background-size: cover;
  background-position: center;
  background-image: url('@/modules/playing/coolit/assets/thermometer.png');
}

.thermometer::v-deep(.el-slider__bar) {
  background-color: var(--color-red);
}

.overlay {
  pointer-events: none;
  position: absolute;
  top: 0;
  width: 100%;
  height: 100%;

  div {
    margin: auto;
    text-align: right;
  }
}

.overlay-container {
  position: absolute;
  background-color: #ffffff55;
  top: 0;
  width: 100%;
  height: 100%;
  --footer-height: 7rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;

  div {
    margin: auto;
    text-align: center;
  }
}
</style>
