<template>
  <div class="mapArea">
    <IdeaMap
      :ideas="ideas"
      :parameter="module?.parameter"
      :canChangePosition="() => false"
      v-model:selected-idea="selectedIdea"
    >
      <template v-slot:marker="{ idea }">
        <div class="mapRate">
          <el-rate
            :model-value="getRateForLevel(idea.id)"
            size="large"
            :max="3"
            :disabled="true"
          />
        </div>
      </template>
    </IdeaMap>
    <div class="overlay-bottom-right">
      <el-button-group>
        <el-button
          v-if="Object.keys(moleculeImages).length > 0"
          v-on:click="showMoleculesInfo = true"
        >
          <font-awesome-icon icon="atom" />
        </el-button>
        <el-button
          v-if="Object.keys(obstacleImages).length > 0"
          v-on:click="showBuildingInfo = true"
        >
          <font-awesome-icon icon="building" />
        </el-button>
        <el-button v-on:click="showHighScore = true">
          <font-awesome-icon icon="trophy" />
        </el-button>
      </el-button-group>
    </div>
  </div>
  <DrawerBottomOverlay v-model="showMoleculesInfo">
    <template v-slot:header>
      <div class="moleculeInfo" v-if="activeMoleculeName">
        <div class="title">
          {{
            $t(
              `module.playing.coolit.participant.moleculeInfo.${activeMoleculeName}.title`
            )
          }}
          ({{ getMoleculeConfig(activeMoleculeName).formula }})
        </div>
        <div class="subtitle">
          {{
            $t(
              `module.playing.coolit.participant.moleculeType.${
                getMoleculeConfig(activeMoleculeName).type
              }`
            )
          }}
        </div>
      </div>
      <div class="moleculeInfo" v-else>
        {{
          $t('module.playing.coolit.participant.moleculeInfo.overview.title')
        }}
      </div>
    </template>
    <div class="moleculeInfo" v-if="activeMoleculeName">
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
              {{ $t('module.playing.coolit.participant.moleculeInfo.gwpInfo') }}
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
                $t('module.playing.coolit.participant.moleculeInfo.riseFactor')
              }}
            </th>
            <td>{{ getMoleculeConfig(activeMoleculeName).riseFactor }}%</td>
          </tr>
        </table>
      </div>
    </div>
    <div class="moleculeInfo" v-else>
      <div class="description">
        {{
          $t(
            `module.playing.coolit.participant.moleculeInfo.overview.description`
          )
        }}
      </div>
      <div class="chart">
        <Doughnut
          :data="chartDataAtmosphere"
          :options="{
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'right',
                labels: {
                  color: '#000000',
                },
              },
              title: {
                display: true,
                text: $t(
                  'module.playing.coolit.participant.moleculeInfo.overview.chart-atmosphere'
                ),
              },
            },
          }"
        />
      </div>
      <div class="chart">
        <Doughnut
          :data="chartDataGreenhouse"
          :options="{
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'right',
                labels: {
                  color: '#000000',
                },
              },
              title: {
                display: true,
                text: $t(
                  'module.playing.coolit.participant.moleculeInfo.overview.chart-greenhouse'
                ),
              },
            },
          }"
        />
      </div>
      <div class="chart">
        <Doughnut
          :data="chartDataImpactGreenhouse"
          :options="{
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'right',
                labels: {
                  color: '#000000',
                },
              },
              title: {
                display: true,
                text: $t(
                  'module.playing.coolit.participant.moleculeInfo.overview.chart-impact-greenhouse'
                ),
              },
            },
          }"
        />
      </div>
    </div>
    <template v-slot:footer>
      <el-scrollbar class="moleculeSelection">
        <el-space wrap>
          <div class="clickable molecule" @click="moleculeOverviewClicked()">
            <div
              class="molecule-overview"
              :class="{ selected: !activeMoleculeName }"
            >
              <font-awesome-icon icon="chart-pie" />
            </div>
            {{
              $t(
                `module.playing.coolit.participant.moleculeInfo.overview.title`
              )
            }}
          </div>
          <div
            class="clickable molecule"
            v-for="moleculeName of Object.keys(gameConfig.molecules)"
            :key="moleculeName"
            @click="moleculeNameClicked(moleculeName)"
          >
            <div :class="{ selected: activeMoleculeName === moleculeName }">
              <div
                class="molecule-image"
                :style="{
                  '--molecule-color': getMoleculeConfig(moleculeName).color,
                }"
              >
                <img :src="moleculeImages[moleculeName]" :alt="moleculeName" />
              </div>
            </div>
            {{
              $t(
                `module.playing.coolit.participant.moleculeInfo.${moleculeName}.title`
              )
            }}
          </div>
        </el-space>
      </el-scrollbar>
    </template>
  </DrawerBottomOverlay>
  <DrawerBottomOverlay v-model="showBuildingInfo">
    <template v-slot:header>
      <div class="moleculeInfo" v-if="activeBuildingName">
        <div class="title">
          {{
            $t(
              `module.playing.coolit.participant.buildingInfo.${activeBuildingName}.title`
            )
          }}
        </div>
      </div>
      <div class="moleculeInfo" v-else>
        {{ $t('module.playing.coolit.participant.buildingInfo.none') }}
      </div>
    </template>
    <div class="moleculeInfo" v-if="activeBuildingName">
      <div class="description">
        {{
          $t(
            `module.playing.coolit.participant.buildingInfo.${activeBuildingName}.description`
          )
        }}
      </div>
    </div>
    <template v-slot:footer>
      <el-scrollbar class="moleculeSelection">
        <el-space wrap>
          <div
            class="clickable molecule"
            v-for="buildingName of [
              ...Object.keys(
                gameConfig.obstacles.city.categories.building.items
              ),
              ...Object.keys(gameConfig.obstacles.city.categories.nature.items),
            ]"
            :key="buildingName"
            @click="buildingNameClicked(buildingName)"
          >
            <div
              class="obstacle-image"
              :class="{ selected: activeBuildingName === buildingName }"
            >
              <img :src="obstacleImages[buildingName]" :alt="buildingName" />
            </div>
            {{
              $t(
                `module.playing.coolit.participant.buildingInfo.${buildingName}.title`
              )
            }}
          </div>
        </el-space>
      </el-scrollbar>
    </template>
  </DrawerBottomOverlay>
  <DrawerBottomOverlay v-model="showHighScore" class="highscore">
    <template v-slot:header>
      {{ $t('module.playing.coolit.participant.highScore.header') }}
    </template>
    <el-collapse v-model="openHighScoreLevels">
      <el-collapse-item
        v-for="level of highScoreList"
        :key="level.ideaId"
        :title="getLevelTitle(level.ideaId)"
        :name="level.ideaId"
      >
        <table class="highscore-table">
          <tr v-for="entry of level.details" :key="entry.avatar.symbol">
            <td>
              <font-awesome-icon
                :icon="entry.avatar.symbol"
                :style="{ color: entry.avatar.color }"
              ></font-awesome-icon>
            </td>
            <td>
              {{ Math.round((entry.value.normalisedTime / 60000) * 100) }}
            </td>
            <td>
              <el-rate
                v-model="entry.value.rate"
                size="large"
                :max="3"
                :disabled="true"
              />
            </td>
          </tr>
        </table>
      </el-collapse-item>
    </el-collapse>
    <template v-slot:footer>
      <el-button type="primary" @click="showHighScore = false">
        {{ $t('module.playing.coolit.participant.confirm') }}
      </el-button>
    </template>
  </DrawerBottomOverlay>
  <el-dialog v-model="showPlayDialog" @close="cancelGame" class="levelInfo">
    <template #header>
      <h1 v-if="selectedIdea">
        {{ selectedIdea.keywords }}
      </h1>
    </template>
    <div v-if="selectedIdea">
      {{ selectedIdea.description }}
    </div>
    <div>
      <el-radio-group v-model="difficultyLevel">
        <el-radio-button
          v-for="level in DifficultyLevel"
          :key="level"
          :label="level"
        >
          {{
            $t(
              `module.playing.coolit.participant.difficultyLevel.${level}.title`
            )
          }}
        </el-radio-button>
      </el-radio-group>
    </div>
    <div class="columns is-mobile">
      <div class="column">
        <SpriteCanvas
          v-if="spriteSheetDifficulty"
          :texture="spriteSheetDifficulty.textures['atmosphere.png']"
          :width="200"
          :height="200"
          :background-color="backgroundColor"
          @initRenderer="initRenderer"
        >
          <template v-slot:overlay>
            <custom-particle-container
              v-if="circleGradientTexture"
              :config="difficultySettings.particleConfig"
              :x="100"
              :y="100"
              :auto-update="false"
              :deep-clone-config="false"
              :default-texture="circleGradientTexture"
              :parentEventBus="eventBus"
            />
            <sprite
              v-if="circleGradientTexture"
              :texture="circleGradientTexture"
              :x="0"
              :y="0"
              :width="200"
              :height="200"
              :tint="difficultySettings.color"
              :alpha="0.5"
            />
            <sprite
              v-if="difficultySettings.texture"
              :texture="difficultySettings.texture"
              :x="5"
              :y="5"
              :width="190"
              :height="190"
            />
          </template>
        </SpriteCanvas>
      </div>
      <div class="column">
        <div>
          <font-awesome-icon icon="temperature-arrow-up" />
          {{ difficultySettings.temperatureRise > 0 ? '+' : ''
          }}{{ difficultySettings.temperatureRise }}°C
        </div>
        <div>
          <font-awesome-icon icon="temperature-half" />
          {{ localTemperature }}°C
        </div>
        <div>
          <font-awesome-icon icon="clock" />
          {{ getTimeString(temperatureWinTime) }}
        </div>
      </div>
    </div>
    <div class="info">
      {{
        $t(
          `module.playing.coolit.participant.difficultyLevel.${difficultyLevel}.description`
        )
      }}
    </div>
    <template #footer>
      <el-button @click="cancelGame" class="dialog-button">
        {{ $t('module.playing.coolit.participant.playDialog.cancel') }}
      </el-button>
      <el-button @click="startGame" class="dialog-button">
        {{ $t('module.playing.coolit.participant.playDialog.play') }}
      </el-button>
    </template>
  </el-dialog>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaMap from '@/components/shared/organisms/IdeaMap.vue';
import { Prop, Watch } from 'vue-property-decorator';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import * as ideaService from '@/services/idea-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as taskParticipantService from '@/services/task-participant-service';
import * as votingService from '@/services/voting-service';
import { Idea } from '@/types/api/Idea';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import { GameStep } from '@/modules/playing/coolit/output/Participant.vue';
import { Module } from '@/types/api/Module';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import SpriteCanvas from '@/components/shared/atoms/game/SpriteCanvas.vue';
import * as PIXI from 'pixi.js';
import * as pixiUtil from '@/utils/pixi';
import * as themeColors from '@/utils/themeColors';
import gameConfig from '@/modules/playing/coolit/data/gameConfig.json';
import { until } from '@/utils/wait';
import DrawerBottomOverlay from '@/components/participant/molecules/DrawerBottomOverlay.vue';
import * as CoolItConst from '@/modules/playing/coolit/utils/consts';
import * as cashService from '@/services/cash-service';
import { VoteParameterResult } from '@/types/api/Vote';
import CustomParticleContainer from '@/components/shared/atoms/game/CustomParticleContainer.vue';
import globalWarmingParticle from '@/modules/playing/coolit/data/globalWarming.json';
import { LevelWorkflowType } from '@/types/game/LevelWorkflowType';
import { Doughnut } from 'vue-chartjs';

/* eslint-disable @typescript-eslint/no-explicit-any*/
enum DifficultyLevel {
  veryEasy = 'veryEasy',
  easy = 'easy',
  today = 'today',
  hard = 'hard',
}

interface DifficultySettings {
  temperatureRise: number;
  texture: PIXI.Texture | null;
  color: string;
  particleConfig: any;
}

export interface ChartData {
  labels: string[];
  datasets: {
    label: string;
    backgroundColor: string | string[];
    data: number[];
  }[];
}

@Options({
  components: {
    SpriteCanvas,
    FontAwesomeIcon,
    IdeaMap,
    DrawerBottomOverlay,
    CustomParticleContainer,
    Doughnut,
  },
  emits: ['play'],
})
export default class SelectLevel extends Vue {
  @Prop() readonly trackingManager!: TrackingManager;
  @Prop() readonly taskId!: string;
  @Prop() readonly module!: Module;
  @Prop({ default: 180000 }) readonly winTime!: number;
  @Prop({ default: true }) readonly openHighScore!: boolean;
  ideas: Idea[] = [];
  result: TaskParticipantIterationStep[] = [];
  mapping: { [key: string]: number } = {};
  selectedIdea: Idea | null = null;
  showMoleculesInfo = false;
  showBuildingInfo = false;
  showHighScore = false;
  showPlayDialog = false;
  spritesheet!: PIXI.Spritesheet;
  spritesheetBuilding!: PIXI.Spritesheet;
  spriteSheetDifficulty!: PIXI.Spritesheet;
  activeMoleculeName = ''; // Object.keys(gameConfig.molecules)[0];
  activeBuildingName = Object.keys(
    gameConfig.obstacles.city.categories.building.items
  )[0];
  gameConfig = gameConfig;
  rendererList: { [key: string]: PIXI.Renderer } = {};
  circleGradientTexture: PIXI.Texture | null = null;
  temperatureRise = 0;
  marks = {};
  highScoreList: VoteParameterResult[] = [];
  openHighScoreLevels: string[] = [];
  difficultyLevel = DifficultyLevel.today;
  textureToken = pixiUtil.createLoadingToken();

  chartDataAtmosphere: ChartData = {
    labels: [],
    datasets: [],
  };
  chartDataGreenhouse: ChartData = {
    labels: [],
    datasets: [],
  };
  chartDataImpactGreenhouse: ChartData = {
    labels: [],
    datasets: [],
  };

  MIN_TEMPERATURE_RISE = CoolItConst.MIN_TEMPERATURE_RISE;
  MAX_TEMPERATURE_RISE = CoolItConst.MAX_TEMPERATURE_RISE;
  DifficultyLevel = DifficultyLevel;

  get difficultySettings(): DifficultySettings {
    const config = structuredClone(globalWarmingParticle);
    switch (this.difficultyLevel) {
      case DifficultyLevel.veryEasy:
        config.maxParticles = 100;
        return {
          temperatureRise: -1,
          texture: this.spriteSheetDifficulty
            ? this.spriteSheetDifficulty.textures['planet01.png']
            : null,
          color: '#0000ff',
          particleConfig: config,
        };
      case DifficultyLevel.easy:
        config.maxParticles = 300;
        return {
          temperatureRise: 0,
          texture: this.spriteSheetDifficulty
            ? this.spriteSheetDifficulty.textures['planet02.png']
            : null,
          color: '#00ffff',
          particleConfig: config,
        };
      case DifficultyLevel.today:
        config.maxParticles = 600;
        return {
          temperatureRise: 1,
          texture: this.spriteSheetDifficulty
            ? this.spriteSheetDifficulty.textures['planet03.png']
            : null,
          color: '#ff8800',
          particleConfig: config,
        };
      case DifficultyLevel.hard:
        config.maxParticles = 1000;
        return {
          temperatureRise: 2,
          texture: this.spriteSheetDifficulty
            ? this.spriteSheetDifficulty.textures['planet04.png']
            : null,
          color: '#ff0000',
          particleConfig: config,
        };
    }
    config.maxParticles = 600;
    return {
      temperatureRise: 1,
      texture: this.spriteSheetDifficulty
        ? this.spriteSheetDifficulty.textures['planet02.png']
        : null,
      color: '#ff8800',
      particleConfig: config,
    };
  }

  get inactiveColor(): string {
    return themeColors.getInactiveColor();
  }

  get backgroundColor(): string {
    return themeColors.getBackgroundColor();
  }

  getTimeString(timestamp: number): string {
    const seconds = Math.floor(timestamp / 1000);
    const secondsString = `0${seconds % 60}`;
    return `${Math.floor(seconds / 60)}:${secondsString.slice(-2)}`;
  }

  get temperatureWinTime(): number {
    return CoolItConst.temperatureWinTime(
      this.winTime,
      this.difficultySettings.temperatureRise
    );
  }

  get localTemperature(): number {
    if (this.selectedIdea) {
      return gameConfig.obstacles[this.selectedIdea.parameter.type].settings
        .heatRation[0].initialTemperature;
    }
    return 15;
  }

  getRateForLevel(levelId: string): number {
    const levelSteps = this.trackingManager.stepList
      .filter((item) => item.parameter.level === levelId)
      .sort((a, b) => b.parameter.stars - a.parameter.stars);
    if (levelSteps.length > 0) {
      return levelSteps[0].parameter.stars;
    }
    return 0;
  }

  moleculeImages: { [key: string]: string } = {};
  obstacleImages: { [key: string]: string } = {};
  mounted(): void {
    this.showHighScore = this.openHighScore;
    setTimeout(() => {
      pixiUtil
        .loadTexture(
          '/assets/games/moveit/molecules.json',
          this.textureToken
        )
        .then((sheet) => {
          this.spritesheet = sheet;
          pixiUtil.convertSpritesheetToBase64(sheet, this.moleculeImages);
        });
    }, 100);
    setTimeout(() => {
      pixiUtil
        .loadTexture(
          '/assets/games/coolit/city/city.json',
          this.textureToken
        )
        .then((sheet) => {
          this.spritesheetBuilding = sheet;
          pixiUtil.convertSpritesheetToBase64(sheet, this.obstacleImages);
        });
    }, 100);
    setTimeout(() => {
      pixiUtil
        .loadTexture(
          '/assets/games/coolit/city/difficulty.json',
          this.textureToken
        )
        .then((sheet) => {
          this.spriteSheetDifficulty = sheet;
        });
    }, 100);

    for (
      let i = CoolItConst.MAX_TEMPERATURE_RISE;
      i >= CoolItConst.MIN_TEMPERATURE_RISE;
      i--
    ) {
      this.marks[i] = `${i}°C`;
    }
    votingService.registerGetParameterResult(
      this.taskId,
      '-',
      this.updateHighScore,
      EndpointAuthorisationType.PARTICIPANT,
      5 * 60
    );

    this.chartDataAtmosphere.datasets.push({
      label: this.$t(
        'module.playing.coolit.participant.moleculeInfo.overview.chart-atmosphere'
      ),
      backgroundColor: [],
      data: [],
    });
    this.chartDataGreenhouse.datasets.push(
      {
        label: this.$t(
          'module.playing.coolit.participant.moleculeInfo.overview.chart-greenhouse'
        ),
        backgroundColor: [],
        data: [],
      },
      {
        label: this.$t(
          'module.playing.coolit.participant.moleculeInfo.overview.chart-greenhouse2'
        ),
        backgroundColor: [],
        data: [],
      },
      {
        label: this.$t(
          'module.playing.coolit.participant.moleculeInfo.overview.chart-greenhouse3'
        ),
        backgroundColor: [],
        data: [],
      }
    );
    this.chartDataImpactGreenhouse.datasets.push({
      label: this.$t(
        'module.playing.coolit.participant.moleculeInfo.overview.chart-impact-greenhouse'
      ),
      backgroundColor: [],
      data: [],
    });
    let sumGreenhouse = 0;
    let sumRationGreenhouse = 0;
    for (const moleculeName of Object.keys(gameConfig.molecules)) {
      const moleculeInfo = gameConfig.molecules[moleculeName];
      if (moleculeInfo.rationAtmosphere) {
        if (moleculeInfo.type === 'atmosphericGas') {
          this.chartDataAtmosphere.labels.push(
            this.$t(
              `module.playing.coolit.participant.moleculeInfo.${moleculeName}.title`
            )
          );
          this.chartDataAtmosphere.datasets[0].data.push(
            moleculeInfo.rationAtmosphere
          );
          (
            this.chartDataAtmosphere.datasets[0].backgroundColor as string[]
          ).push(moleculeInfo.color);
        } else {
          sumGreenhouse += moleculeInfo.rationAtmosphere;
        }
      }
      if (moleculeInfo.rationGreenhouse) {
        sumRationGreenhouse += moleculeInfo.rationGreenhouse;
        this.chartDataGreenhouse.labels.push(
          this.$t(
            `module.playing.coolit.participant.moleculeInfo.${moleculeName}.title`
          )
        );
        this.chartDataGreenhouse.datasets[0].data.push(
          moleculeInfo.rationGreenhouse > 1 ? moleculeInfo.rationGreenhouse : 0
        );
        (this.chartDataGreenhouse.datasets[0].backgroundColor as string[]).push(
          moleculeInfo.color
        );
        if (moleculeInfo.rationGreenhouse > 1) {
          this.chartDataGreenhouse.datasets[1].data.push(
            moleculeInfo.rationGreenhouse < 50
              ? moleculeInfo.rationGreenhouse
              : 0
          );
          (
            this.chartDataGreenhouse.datasets[1].backgroundColor as string[]
          ).push(moleculeInfo.color);
        }
        this.chartDataGreenhouse.datasets[2].data.push(
          moleculeInfo.rationGreenhouse < 5 ? moleculeInfo.rationGreenhouse : 0
        );
        (this.chartDataGreenhouse.datasets[2].backgroundColor as string[]).push(
          moleculeInfo.color
        );
      }
      if (moleculeInfo.impactGreenhouse) {
        this.chartDataImpactGreenhouse.labels.push(
          this.$t(
            `module.playing.coolit.participant.moleculeInfo.${moleculeName}.title`
          )
        );
        this.chartDataImpactGreenhouse.datasets[0].data.push(
          moleculeInfo.impactGreenhouse
        );
        (
          this.chartDataImpactGreenhouse.datasets[0].backgroundColor as string[]
        ).push(moleculeInfo.color);
      }
    }
    this.chartDataGreenhouse.labels.push(
      this.$t(`module.playing.coolit.participant.moleculeInfo.others`)
    );
    const others =
      100 -
      this.chartDataGreenhouse.datasets[0].data.reduce(
        (sum, item) => sum + item,
        0
      );
    this.chartDataGreenhouse.datasets[0].data.push(others);
    (this.chartDataGreenhouse.datasets[0].backgroundColor as string[]).push(
      themeColors.getContrastColor()
    );
    this.chartDataGreenhouse.datasets[1].data.push(others);
    (this.chartDataGreenhouse.datasets[1].backgroundColor as string[]).push(
      themeColors.getContrastColor()
    );
    this.chartDataGreenhouse.datasets[2].data.push(100 - sumRationGreenhouse);
    (this.chartDataGreenhouse.datasets[2].backgroundColor as string[]).push(
      themeColors.getContrastColor()
    );
    this.chartDataImpactGreenhouse.labels.push(
      this.$t(`module.playing.coolit.participant.moleculeInfo.others`)
    );
    this.chartDataImpactGreenhouse.datasets[0].data.push(
      100 -
        this.chartDataImpactGreenhouse.datasets[0].data.reduce(
          (sum, item) => sum + item,
          0
        )
    );
    (
      this.chartDataImpactGreenhouse.datasets[0].backgroundColor as string[]
    ).push(themeColors.getContrastColor());
    this.chartDataAtmosphere.labels.push(
      this.$t(`module.playing.coolit.participant.moleculeType.greenhouseGas`)
    );
    this.chartDataAtmosphere.datasets[0].data.push(sumGreenhouse);
    (this.chartDataAtmosphere.datasets[0].backgroundColor as string[]).push(
      themeColors.getGreenColor()
    );
  }

  unmounted(): void {
    pixiUtil.cleanupToken(this.textureToken);
    cashService.deregisterAllGet(this.updateHighScore);
    cashService.deregisterAllGet(this.updateIdeas);
    cashService.deregisterAllGet(this.updateIterationSteps);
  }

  updateHighScore(list: VoteParameterResult[]): void {
    if (this.highScoreList.length === 0)
      this.openHighScoreLevels = list.map((item) => item.ideaId);
    for (const level of list) {
      if (level.details) {
        level.details = level.details.sort(
          (a, b) => b.value.normalisedTime - a.value.normalisedTime
        );
      }
    }
    this.highScoreList = list;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      ideaService.registerGetIdeasForTask(
        this.taskId,
        null,
        null,
        this.updateIdeas,
        EndpointAuthorisationType.PARTICIPANT,
        3
      );
      taskParticipantService.registerGetIterationStepList(
        this.taskId,
        this.updateIterationSteps,
        EndpointAuthorisationType.PARTICIPANT,
        2 * 60
      );
    }
  }

  /*@Watch('temperatureRise', { immediate: true })
  onTemperatureRiseChanged(): void {
    cashService.deregisterAllGet(this.updateHighScore);
    votingService.registerGetParameterResult(
      this.taskId,
      //`${this.temperatureRise}.time`,
      'normalisedTime',
      this.updateHighScore,
      EndpointAuthorisationType.PARTICIPANT,
      5 * 60
    );
  }*/

  @Watch('selectedIdea', { immediate: true })
  onSelectedLevelChanged(): void {
    if (this.selectedIdea) {
      this.showPlayDialog = true;
    }
  }

  startGame(): void {
    if (this.selectedIdea) {
      this.$emit(
        'play',
        this.selectedIdea,
        this.difficultySettings.temperatureRise
      );
    }
  }

  cancelGame(): void {
    this.showPlayDialog = false;
    this.selectedIdea = null;
  }

  updateIdeas(ideas: Idea[]): void {
    this.ideas = ideas.filter(
      (idea) => idea.parameter.state === LevelWorkflowType.approved
    );
    this.calculateResult();
  }

  updateIterationSteps(steps: TaskParticipantIterationStep[]): void {
    const ideaList = steps
      .map((item) => item.ideaId)
      .filter(
        (value, index, self) =>
          self.findIndex((item) => item === value) === index
      );
    this.result = [];
    for (const ideaId of ideaList) {
      const played = steps
        .filter(
          (item) =>
            item.ideaId === ideaId && item.parameter.step === GameStep.Play
        )
        .sort((a, b) => b.parameter.stars - a.parameter.stars);
      if (played.length > 0) this.result.push(played[0]);
    }
    this.calculateResult();
  }

  calculateResult(): void {
    if (this.ideas && this.result) {
      const mapping: { [key: string]: number } = {};
      for (const idea of this.ideas) {
        mapping[idea.id] = this.getStarsForIdea(idea.id);
      }
      this.mapping = mapping;
    }
  }

  getStarsForIdea(ideaId: string): number {
    if (this.result) {
      const resultItem = this.result.find(
        (item) => item && item.ideaId === ideaId
      );
      if (resultItem) return resultItem.parameter.stars;
    }
    return 0;
  }

  getMoleculeTexture(objectName: string): PIXI.Texture | string {
    if (this.spritesheet) return this.spritesheet.textures[objectName];
    return '';
  }

  getMoleculeAspect(objectName: string): number {
    return pixiUtil.getSpriteAspect(this.spritesheet, objectName);
  }

  moleculeNameClicked(objectName: string): void {
    this.activeMoleculeName = objectName;
  }

  moleculeOverviewClicked(): void {
    this.activeMoleculeName = '';
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

  buildingNameClicked(objectName: string): void {
    this.activeBuildingName = objectName;
  }

  getBuildingTexture(objectName: string): PIXI.Texture | string {
    if (this.spritesheetBuilding)
      return this.spritesheetBuilding.textures[objectName];
    return '';
  }

  getBuildingAspect(objectName: string): number {
    return pixiUtil.getSpriteAspect(this.spritesheetBuilding, objectName);
  }

  drawCircle(circle: PIXI.Graphics, objectName: string): void {
    until(() => this.rendererList[objectName]).then(() => {
      const renderer = this.rendererList[objectName];
      if (renderer) {
        (circle as any).radius = renderer.width / 2;
        circle.x = renderer.width / 2;
        circle.y = renderer.height / 2;
        pixiUtil.drawCircleWithGradient(circle, renderer);
      }
    });
  }

  getLevelTitle(levelId: string): string {
    if (this.ideas) {
      const level = this.ideas.find((item) => item.id === levelId);
      if (level) return level.keywords;
    }
    return '';
  }

  initRenderer(renderer: PIXI.Renderer): void {
    this.circleGradientTexture = pixiUtil.generateCircleGradientTexture(
      256,
      renderer
    );
  }
}
</script>

<style scoped lang="scss">
.mapArea {
  height: calc(100%);
  width: 100%;
  position: relative;
}

.overlay-bottom-right {
  position: absolute;
  z-index: 100;
  bottom: 0.5rem;
  right: 0.5rem;
  padding: 1rem 0.1rem;

  .el-button {
    box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.1);
    padding: 0.5rem;
    --el-border-radius-base: 0.5rem;
    margin: 0;
    height: unset;
    min-height: unset;
  }
}

.overlay-top-left {
  position: absolute;
  z-index: 100;
  top: 0.5rem;
  left: 0.5rem;
}

.clickable {
  cursor: pointer;
}

.selected {
  border: red 2px solid;
  border-radius: var(--border-radius);
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

.columns:not(:last-child) {
  margin-bottom: 0;
}

.moleculeSelection {
  width: 100%;
  text-align: center;
}

.molecule {
  font-size: var(--font-size-xxsmall);
}

.highscore-table {
  color: var(--color-playing);
  width: 100%;

  td {
    width: 33%;
  }
}

.mapRate {
  position: absolute;
  left: -1.2rem;
  top: -2.3rem;
  width: 3rem;

  .el-rate {
    --el-rate-disabled-void-color: var(--color-gray-dark);
    --el-rate-fill-color: var(--color-yellow);
  }
}

h1 {
  font-size: var(--font-size-large);
  font-weight: var(--font-weight-bold);
  color: var(--color-playing);
  //margin-bottom: 0.5rem;
}

.dialog-button {
  margin-left: 0.5rem;
}

.info {
  padding-top: 1rem;
  text-align: left;
  font-size: var(--font-size-small);
  max-width: 30rem;
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

.overlay-container::v-deep(.footer) {
  background-color: var(--color-background-dark);
  margin: 0 -1rem;
  padding: 0.5rem 1rem 0 1rem;
}

.highscore::v-deep(.footer) {
  text-align: center;
  background-color: unset;
}

.highscore {
  --footer-height: 4rem;
}

.molecule-overview {
  font-size: 3.5rem;
  height: 5rem;
  width: 5rem;
  margin: 0.2rem;
  display: flex;

  svg {
    margin: auto;
  }
}

.chart {
  height: 30%;
  min-height: 5rem;
}

.columns {
  margin: -0.5rem 0;

  .column {
    padding: 0.5rem 0;
  }

  .column:not(:last-child) {
    padding-right: 1rem;
  }
}
</style>

<style lang="scss">
.levelInfo .el-dialog__body div {
  margin-bottom: 0.5rem;
}
</style>
