<template>
  <div class="gameArea">
    <GameContainer
      class="pixiCanvas"
      :detect-collision="false"
      :has-mouse-input="false"
      :use-gravity="false"
      :wind-force="1"
      :background-texture="`/assets/games/moveit/${getParticleBackground(
        activeTabName
      )}`"
      v-model:width="gameWidth"
      v-model:height="gameHeight"
      @initRenderer="initRenderer"
    >
      <template v-slot:default>
        <container
          v-if="
            activeTabName &&
            particleVisualisation[activeTabName] &&
            particleReady &&
            rendererReady &&
            gameWidth
          "
        >
          <GameObject
            v-for="index of particleVisualisation[activeTabName].length"
            :key="`${activeTabName}.${index}`"
            type="circle"
            :x="getX(activeTabName, index - 1)"
            :y="getY(activeTabName, index - 1)"
            :fix-size="particleRadius * 2"
          >
            <sprite
              v-if="getParticleTexture(activeTabName, index - 1)"
              :texture="getParticleTexture(activeTabName, index - 1)"
              :width="particleRadius * 2"
              :height="particleRadius * 2"
              :anchor="0.5"
              :tint="getParticleColor(activeTabName, index - 1)"
            />
          </GameObject>
        </container>
      </template>
    </GameContainer>
    <div class="infoOverlay">
      <el-carousel
        class="resultInfo"
        :height="`${gameHeight}px`"
        :interval="30000"
        trigger="click"
        arrow="always"
        @change="carouselChanged"
      >
        <el-carousel-item class="infoGraphic">
          <h2>
            {{ $t('module.playing.moveit.participant.info.emissions.input') }}
          </h2>
          <div class="chartArea">
            <Line
              :data="chartDataInput"
              :options="{
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                  x: {
                    title: {
                      text: $t(
                        'module.playing.moveit.participant.info.emissions.scale.x'
                      ),
                      display: true,
                    },
                  },
                  y: {
                    stacked: true,
                    title: {
                      text: $t(
                        'module.playing.moveit.participant.info.emissions.scale.y'
                      ),
                      display: true,
                    },
                  },
                },
                plugins: {
                  annotation: {
                    annotations: {
                      box1: {
                        type: 'box',
                        xMin: 0,
                        xMax: chartDataInput.labels.length,
                        yMin: maxCleanupThreshold,
                        yMax: calcChartHeight(maxChartValue),
                        backgroundColor: highlightColorTransparent,
                        borderColor: highlightColor,
                      },
                    },
                  },
                },
              }"
            />
          </div>
          <h2>
            {{
              $t('module.playing.moveit.participant.info.emissions.collected')
            }}
          </h2>
          <div class="chartArea">
            <Line
              :data="chartDataCollected"
              :options="{
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                  x: {
                    title: {
                      text: $t(
                        'module.playing.moveit.participant.info.emissions.scale.x'
                      ),
                      display: true,
                    },
                  },
                  y: {
                    stacked: true,
                    title: {
                      text: $t(
                        'module.playing.moveit.participant.info.emissions.scale.y'
                      ),
                      display: true,
                    },
                  },
                },
              }"
            />
          </div>
          <h2>
            {{ $t('module.playing.moveit.participant.info.emissions.outside') }}
          </h2>
          <div class="chartArea">
            <Line
              :data="chartDataOutside"
              :options="{
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                  x: {
                    title: {
                      text: $t(
                        'module.playing.moveit.participant.info.emissions.scale.x'
                      ),
                      display: true,
                    },
                  },
                  y: {
                    stacked: true,
                    title: {
                      text: $t(
                        'module.playing.moveit.participant.info.emissions.scale.y'
                      ),
                      display: true,
                    },
                  },
                },
              }"
            />
          </div>
          <h2>
            {{ $t('module.playing.moveit.participant.info.speed.title') }}
          </h2>
          <div class="chartArea">
            <Line
              :data="chartDataSpeed"
              :options="{
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                  x: {
                    title: {
                      text: $t(
                        'module.playing.moveit.participant.info.speed.scale.x'
                      ),
                      display: true,
                    },
                  },
                  y: {
                    stacked: true,
                    title: {
                      text: $t(
                        'module.playing.moveit.participant.info.speed.scale.y'
                      ),
                      display: true,
                    },
                  },
                },
              }"
            />
          </div>
          <h2 v-if="vehicle.category === 'bus'">
            {{ $t('module.playing.moveit.participant.info.persons.title') }}
          </h2>
          <div class="chartArea" v-if="vehicle.category === 'bus'">
            <Line
              :data="chartDataPersons"
              :options="{
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                  x: {
                    title: {
                      text: $t(
                        'module.playing.moveit.participant.info.persons.scale.x'
                      ),
                      display: true,
                    },
                  },
                  y: {
                    stacked: true,
                    title: {
                      text: $t(
                        'module.playing.moveit.participant.info.persons.scale.y'
                      ),
                      display: true,
                    },
                  },
                },
              }"
            />
          </div>
        </el-carousel-item>
        <el-carousel-item class="infoGraphic successState">
          <h1>
            <el-rate
              v-model="successRate"
              size="large"
              :max="3"
              :disabled="true"
            />
          </h1>
          <div>
            {{
              $t(
                `module.playing.moveit.participant.result.${successStatusText}`
              )
            }}
          </div>
          <div class="result">
            {{ particleStateSum.collectedCount }}
            /
            {{ particleStateSum.totalCount }}
            {{ $t(`module.playing.moveit.participant.result.collected`) }}
          </div>
        </el-carousel-item>
        <el-carousel-item
          class="infoGraphic"
          v-for="particleSource of Object.keys(gameConfig.particles)"
          :key="particleSource"
        >
          <h1>
            {{ $t(`module.playing.moveit.enums.particle.${particleSource}`) }}
          </h1>
          <div>
            {{
              $t(
                `module.playing.moveit.participant.result.${particleSource}Explanation`
              )
            }}
          </div>
          <div class="result">
            {{
              particleState[particleSource].totalCount -
              particleState[particleSource].collectedCount
            }}
            /
            {{ particleState[particleSource].totalCount }}
            {{ $t(`module.playing.moveit.participant.result.notCollected`) }}
          </div>
        </el-carousel-item>
      </el-carousel>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { ParticleState } from '@/modules/playing/moveit/organisms/CleanUpParticles.vue';
import * as gameConfig from '@/modules/playing/moveit/data/gameConfig.json';
import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import * as PIXI from 'pixi.js';
import * as pixiUtil from '@/utils/pixi';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import * as particleStateUtil from '@/modules/playing/moveit/utils/particleState';
import { Line } from 'vue-chartjs';
import * as constants from '@/modules/playing/moveit/utils/consts';
import * as themeColors from '@/utils/themeColors';
import * as vehicleCalculation from '@/modules/playing/moveit/types/Vehicle';
import {
  TrackingData,
  normalizedTrackingData,
} from '@/modules/playing/moveit/utils/trackingData';

/* eslint-disable @typescript-eslint/no-explicit-any*/

const resultTabName = 'result';

interface DatasetData {
  name: string;
  label: string;
  backgroundColor: string;
  borderColor: string;
  data: number[];
  fill: any;
}

@Options({
  components: { GameObject, GameContainer, Line },
  emits: [],
})
export default class ShowResult extends Vue {
  @Prop() readonly trackingManager!: TrackingManager;
  @Prop({ default: { category: 'car', type: 'sport' } })
  readonly vehicle!: vehicleCalculation.Vehicle;
  @Prop({ default: [] })
  readonly trackingData!: TrackingData[];
  @Prop({
    default: {
      carbonDioxide: {
        collectedCount: 0,
        totalCount: 0,
        timelineOutside: [],
        timelineCollected: [],
        timelineInput: [],
      },
      dust: {
        collectedCount: 0,
        totalCount: 0,
        timelineOutside: [],
        timelineCollected: [],
        timelineInput: [],
      },
      methane: {
        collectedCount: 0,
        totalCount: 0,
        timelineOutside: [],
        timelineCollected: [],
        timelineInput: [],
      },
      microplastic: {
        collectedCount: 0,
        totalCount: 0,
        timelineOutside: [],
        timelineCollected: [],
        timelineInput: [],
      },
    },
  })
  readonly particleState!: {
    [key: string]: ParticleState;
  };
  particleVisualisation: {
    [key: string]: { coordinates: [number, number]; type: string }[];
  } = {};
  gameHeight = 0;
  gameWidth = 0;
  gameConfig = gameConfig;
  renderer!: PIXI.Renderer;
  particleReady = false;
  rendererReady = false;
  activeTabName = '';
  spritesheet: PIXI.Spritesheet | null = null;
  maxParticleCount = 100;
  circleGradientTexture: PIXI.Texture | null = null;
  particleTextures: { [key: string]: PIXI.Texture } = {};
  textureToken = pixiUtil.createLoadingToken();
  chartDataInput: {
    labels: string[];
    datasets: DatasetData[];
  } = {
    labels: [],
    datasets: [],
  };
  chartDataCollected: {
    labels: string[];
    datasets: DatasetData[];
  } = {
    labels: [],
    datasets: [],
  };
  chartDataOutside: {
    labels: string[];
    datasets: DatasetData[];
  } = {
    labels: [],
    datasets: [],
  };
  chartDataSpeed: {
    labels: string[];
    datasets: any[];
  } = {
    labels: [],
    datasets: [],
  };
  chartDataPersons: {
    labels: string[];
    datasets: any[];
  } = {
    labels: [],
    datasets: [],
  };
  readonly maxCleanupThreshold = constants.maxCleanupThreshold;
  readonly calcChartHeight = constants.calcChartHeight;

  get particleStateSum(): ParticleState {
    return particleStateUtil.particleStateSum(this.particleState);
  }

  get successStatusText(): string {
    return particleStateUtil.successStatusText(this.particleState);
  }

  get successRate(): number {
    return particleStateUtil.successRate(this.particleState);
  }

  set successRate(value: number) {
    // do nothing
  }

  get highlightColorTransparent(): string {
    return themeColors.convertToRGBA(themeColors.getHighlightColor(), 0.25);
  }

  get highlightColor(): string {
    return themeColors.convertToRGBA(themeColors.getHighlightColor());
  }

  get particleArea(): number {
    return this.gameWidth * this.gameHeight;
  }

  get particleRadius(): number {
    const minSize = 10;
    const maxSize = 40;
    const circleArea = this.particleArea / (this.maxParticleCount * 2);
    const size = Math.sqrt(circleArea / Math.PI);
    if (size > maxSize) return maxSize;
    if (size < minSize) return minSize;
    return size;
  }

  carouselChanged(index: number): void {
    if (index > 1) {
      this.activeTabName = Object.keys(this.particleState)[index - 2];
    } else if (index === 1) {
      this.activeTabName = resultTabName;
    } else {
      this.activeTabName = '';
    }
  }

  getParticleBackground(name: string): string {
    switch (name) {
      case 'carbonDioxide':
      case 'methane':
        return 'sky.jpg';
      case 'dust':
        return 'body.png';
      case 'microplastic':
        return 'water.jpg';
    }
    return 'sky.jpg';
  }

  getParticleAspect(tabName: string, index: number): number {
    if (
      this.particleVisualisation[tabName] &&
      this.particleVisualisation[tabName].length > index
    ) {
      const particleName = this.particleVisualisation[tabName][index].type;
      if (
        this.spritesheet?.data?.frames &&
        particleName in this.spritesheet.data.frames
      ) {
        const h = this.spritesheet.data.frames[particleName].sourceSize?.h;
        const w = this.spritesheet.data.frames[particleName].sourceSize?.w;
        if (h && w) return h / w;
      }
    }
    return 1;
  }

  getParticleColor(tabName: string, index: number): string {
    if (
      this.particleVisualisation[tabName] &&
      this.particleVisualisation[tabName].length > index
    ) {
      const particleName = this.particleVisualisation[tabName][index].type;
      return gameConfig.particles[particleName].color;
    }
    return '#ffffff';
  }

  getParticleTexture(tabName: string, index: number): PIXI.Texture | null {
    if (
      this.particleVisualisation[tabName] &&
      this.particleVisualisation[tabName].length > index &&
      this.spritesheet
    ) {
      const particleName = this.particleVisualisation[tabName][index].type;
      if (this.particleTextures[particleName])
        return this.particleTextures[particleName];
      return this.spritesheet.textures[particleName];
    }
    return null;
  }

  getRandomPosition(): [number, number] {
    const x = Math.round(
      Math.random() * (this.gameWidth - this.particleRadius * 2) +
        this.particleRadius
    );
    const borderTop = this.gameHeight / 4;
    const y = Math.round(
      Math.random() * (this.gameHeight - borderTop - this.particleRadius * 2) +
        this.particleRadius +
        borderTop
    );
    return [x, y];
  }

  getX(tabName: string, index: number): number {
    if (
      this.particleVisualisation[tabName] &&
      this.particleVisualisation[tabName].length > index
    )
      return this.particleVisualisation[tabName][index].coordinates[0];
    return 0;
  }

  getY(tabName: string, index: number): number {
    if (
      this.particleVisualisation[tabName] &&
      this.particleVisualisation[tabName].length > index
    )
      return this.particleVisualisation[tabName][index].coordinates[1];
    return 0;
  }

  async generateParticleTextures(): Promise<void> {
    if (
      !this.renderer ||
      !this.circleGradientTexture ||
      !this.spritesheet ||
      Object.keys(this.particleTextures).length > 0
    )
      return;
    for (const particleName of Object.keys(gameConfig.particles)) {
      if (this.spritesheet.textures[particleName]) {
        this.particleTextures[particleName] = pixiUtil.generateStackedTexture(
          [this.circleGradientTexture, this.spritesheet.textures[particleName]],
          this.renderer,
          60
        );
      }
    }
  }

  initRenderer(renderer: PIXI.Renderer): void {
    this.renderer = renderer;
    this.circleGradientTexture = pixiUtil.generateCircleGradientTexture(
      this.particleRadius,
      this.renderer
    );
    this.rendererReady = true;
    this.generateParticleTextures();
  }

  async mounted(): Promise<void> {
    this.gameHeight = this.$el.parentElement.offsetHeight;
    if (this.gameWidth) {
      this.particleVisualisation[resultTabName] = [];
      for (const particleName of Object.keys(this.particleState)) {
        this.particleVisualisation[particleName] = [];
      }
    }
    pixiUtil
      .loadTexture(
        '/assets/games/moveit/molecules.json',
        this.eventBus,
        this.textureToken
      )
      .then((sheet) => {
        this.spritesheet = sheet;
        this.generateParticleTextures();
      });
  }

  unmounted(): void {
    pixiUtil.cleanupToken(this.textureToken);
  }

  @Watch('gameWidth', { immediate: true })
  onSizeChanged(): void {
    if (this.gameWidth) {
      this.particleVisualisation[resultTabName] = [];
      for (const particleName of Object.keys(this.particleState)) {
        const collectedCount = this.particleState[particleName].collectedCount;
        const remainingCount =
          this.particleState[particleName].totalCount - collectedCount;
        this.particleVisualisation[particleName] = [];
        for (let i = 0; i < remainingCount; i++) {
          if (
            this.particleVisualisation[particleName].length <
            this.maxParticleCount
          ) {
            this.particleVisualisation[particleName].push({
              coordinates: this.getRandomPosition(),
              type: particleName,
            });
          }
        }
        for (let i = 0; i < collectedCount; i++) {
          if (
            this.particleVisualisation[resultTabName].length <
            this.maxParticleCount
          ) {
            this.particleVisualisation[resultTabName].push({
              coordinates: this.getRandomPosition(),
              type: particleName,
            });
          }
        }
      }
      this.particleReady = true;
    }
  }

  getParticleDisplayName(particleName: string): string {
    return this.$t(`module.playing.moveit.enums.particle.${particleName}`);
  }

  maxChartValue = 0;
  @Watch('particleState', { immediate: true })
  onParticleStateChanged(): void {
    let totalValue = 0;
    const normalizedData = normalizedTrackingData(this.trackingData);
    const labels = normalizedData.map((data) =>
      (Math.round(data.distanceTraveled * 100) / 100).toString()
    );
    const outsideLength = Object.values(this.particleState)[0].timelineOutside
      .length;
    for (let i = labels.length; i < outsideLength; i++) {
      labels.push(labels[labels.length - 1]);
    }
    this.chartDataInput.labels =
      this.chartDataOutside.labels =
      this.chartDataCollected.labels =
      this.chartDataSpeed.labels =
      this.chartDataPersons.labels =
        labels;

    this.chartDataSpeed.datasets.push({
      name: 'speed',
      label: 'speed',
      data: normalizedData.map((data) => Math.round(data.speed)),
    });

    this.chartDataPersons.datasets.push({
      name: 'persons',
      label: 'persons',
      data: normalizedData.map((data) => Math.round(data.persons)),
    });
    for (const particleName of Object.keys(this.particleState)) {
      const particle = gameConfig.particles[particleName];
      this.chartDataInput.datasets.push({
        name: particleName,
        label: this.getParticleDisplayName(particleName),
        data: this.particleState[particleName].timelineInput,
        backgroundColor: particle.color,
        borderColor: particle.color,
        fill: {
          target: 'stack',
          above: `${particle.color}77`,
        },
      });
      this.chartDataOutside.datasets.push({
        name: particleName,
        label: this.getParticleDisplayName(particleName),
        data: this.particleState[particleName].timelineOutside,
        backgroundColor: particle.color,
        borderColor: particle.color,
        fill: {
          target: 'stack',
          above: `${particle.color}77`,
        },
      });
      this.chartDataCollected.datasets.push({
        name: particleName,
        label: this.getParticleDisplayName(particleName),
        data: this.particleState[particleName].timelineCollected,
        backgroundColor: particle.color,
        borderColor: particle.color,
        fill: {
          target: 'stack',
          above: `${particle.color}77`,
        },
      });
      totalValue += Math.max(...this.particleState[particleName].timelineInput);
    }
    totalValue += 1;
    if (this.maxChartValue < totalValue) this.maxChartValue = totalValue;
  }
}
</script>

<style scoped lang="scss">
h1 {
  padding: 1rem;
  font-size: var(--font-size-xxlarge);
  font-width: var(--font-weight-bold);
  text-align: center;
}

.infoGraphic {
  padding: 1rem;
  background-size: cover;
  background-repeat: no-repeat;
  background-position: center;
  background-image: linear-gradient(#ffffffee, #ffffff00);
  overflow-y: auto;
}

.successState {
  background-image: linear-gradient(#ffffffee, #ffffff00),
    url('~@/modules/playing/moveit/assets/net.png');
}

.result {
  padding-top: 5rem;
  font-size: 3rem;
  font-width: var(--font-weight-bold);
  text-align: center;
}

.pixiCanvas {
  position: absolute;
  top: 0;
  right: 0;
}

.infoOverlay {
  position: absolute;
  top: 0;
  bottom: 0;
  right: 0;
  left: 0;
}

.gameArea {
  height: 100%;
  width: 100%;
}

.el-rate::v-deep(.el-icon) {
  height: 5em;
  width: 5em;

  svg {
    height: 5em;
    width: 5em;
  }
}

h2 {
  font-weight: var(--font-weight-bold);
}

.chartArea {
  height: 20%;
  min-height: 10rem;
}
</style>
