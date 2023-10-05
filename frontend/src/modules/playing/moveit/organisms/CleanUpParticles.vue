<template>
  <div class="releaseArea">
    <table
      class="container-info"
      :style="{ '--container-font-size': convertFontSizeToScreenSize(24) }"
    >
      <tr>
        <td
          v-for="particle in Object.keys(gameConfig.particles)"
          :key="particle"
          class="animationContainer"
        >
          {{ getParticleDisplayName(particle) }}
          <br />
          <div v-if="particleState[particle]">
            {{ particleState[particle].outsideCount }}
            /
            {{ particleState[particle].totalCount }}
          </div>
          <div
            class="addCountOutside"
            v-if="
              particleState[particle] && particleState[particle].outsideCountAdd
            "
          >
            +{{ particleState[particle].outsideCountAdd }}
          </div>
          <div
            class="addCountInside"
            v-if="
              particleState[particle] && particleState[particle].insideCountAdd
            "
          >
            +{{ particleState[particle].insideCountAdd }}
          </div>
        </td>
      </tr>
    </table>
  </div>
  <div class="chartArea">
    <Line
      ref="chartRef"
      :data="chartData"
      :options="{
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            stacked: true,
          },
        },
        plugins: {
          annotation: {
            annotations: {
              line1: {
                type: 'line',
                mode: 'vertical',
                xMin: activeValue,
                xMax: activeValue,
                borderColor: highlightColor,
                borderWidth: 2,
              },
              box1: {
                type: 'box',
                xMin: 0,
                xMax: normalizedTrackingData.length,
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
    <div class="overlay" v-if="activeValue > normalizedTrackingData.length">
      {{ normalizedTrackingData.length + countdownTime - activeValue + 1 }}
    </div>
  </div>
  <div class="gameArea">
    <table
      class="container-info"
      :style="{ '--container-font-size': convertFontSizeToScreenSize(24) }"
    >
      <tr>
        <td
          v-for="particle in Object.keys(gameConfig.particles)"
          :key="particle"
        >
          {{ getParticleDisplayName(particle) }}
          <br />
          <div v-if="particleState[particle]">
            {{ particleState[particle].collectedCount }}
            /
            {{ particleState[particle].totalCount }}
          </div>
        </td>
      </tr>
    </table>
    <GameContainer
      v-model:width="gameWidth"
      v-model:height="gameHeight"
      background-texture="/assets/games/moveit/road02.png"
      :collisionsFilter="(collision: Matter.Collision) => {
            return collision.bodyA.isStatic !== collision.bodyB.isStatic;
          }"
      :collisionBorders="isFull ? CollisionBorderType.None : CollisionBorderType.Screen"
      :combined-active-collision-to-chain="true"
      @initRenderer="initRenderer"
    >
      <template v-slot:default>
        <container v-if="gameWidth">
          <GameObject
            v-for="(particle, index) in Object.keys(gameConfig.particles)"
            :key="particle"
            :x="index * containerSpace + padding + containerSpace / 2"
            :y="containerHeight / 2"
            type="rect"
            :isStatic="true"
            :options="{
              name: particle,
              label: getParticleDisplayName(particle),
              collisionFilter: {
                group: index + 1,
                category: 0b0010,
              },
            }"
            :render-delay="1000"
            @sizeChanged="containerSizeChanged"
          >
            <sprite
              :anchor="0.5"
              :width="containerWidth"
              :height="containerHeight"
              texture="/assets/games/moveit/dumpster.svg"
              :tint="gameConfig.particles[particle].color"
            >
            </sprite>
          </GameObject>
          <Graphics
            v-if="renderer"
            :x="0"
            :y="particleBorder"
            :color="statusColor"
            @render="drawStatusBackground"
          ></Graphics>
          <GameObject
            v-for="particle in cleanupParticles"
            :key="particle.uuid"
            v-model:id="particle.id"
            type="circle"
            v-model:x="particle.position[0]"
            v-model:y="particle.position[1]"
            :options="{
              name: particle.name,
              label: particle.label,
              collisionFilter: {
                group: particle.group,
                category: 0b0001,
                mask: 0b0001,
              },
            }"
            :collision-handler="particleCollisionHandler"
            v-model:highlighted="particle.highlighted"
            @destroyObject="destroyParticle"
            @outsideDrawingSpace="outsideDrawingSpace"
            @collision="updateTracking"
          >
            <Graphics
              :radius="particleRadius"
              :color="particle.color"
              @render="drawCircle"
            >
              <CustomSprite
                v-if="spritesheet"
                :texture="spritesheet.textures[particle.name]"
                :anchor="0.5"
                :tint="particle.color"
                :width="particleRadius * 1.5"
                :height="
                  (particleRadius * 1.5) / getParticleAspect(particle.name)
                "
                :outline="particle.highlighted ? 'red' : null"
              />
            </Graphics>
          </GameObject>
        </container>
      </template>
    </GameContainer>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Line } from 'vue-chartjs';
import * as gameConfig from '@/modules/playing/moveit/data/gameConfig.json';
import * as PIXI from 'pixi.js';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import GameContainer, {
  CollisionBorderType,
} from '@/components/shared/atoms/game/GameContainer.vue';
import { Chart } from 'chart.js';
import { ParticleCollisionHandler } from '@/modules/playing/moveit/types/ParticleCollisionHandler';
import annotationPlugin from 'chartjs-plugin-annotation';
import { v4 as uuidv4 } from 'uuid';
import { center } from '@turf/turf';
import { delay } from '@/utils/wait';
import Color from 'colorjs.io';
import { TrackingData } from '@/modules/playing/moveit/organisms/DriveToLocation.vue';
import * as configCalculation from '@/modules/playing/moveit/utils/configCalculation';
import * as pixiUtil from '@/utils/pixi';
import * as constants from '@/modules/playing/moveit/utils/consts';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import * as themeColors from '@/utils/themeColors';
import { CalculationType, mapArrayToConstantSize } from '@/utils/statistic';
import CustomSprite from '@/components/shared/atoms/game/CustomSprite.vue';
import * as vehicleCalculation from '@/modules/playing/moveit/types/Vehicle';

Chart.register(annotationPlugin);

/* eslint-disable @typescript-eslint/no-explicit-any*/
interface DrawingParticle {
  uuid: string;
  id: number;
  name: string;
  label: string;
  group: number;
  color: string;
  position: [number, number];
  highlighted: boolean;
}

interface DatasetData {
  name: string;
  label: string;
  backgroundColor: string;
  borderColor: string;
  data: number[];
  fill: any;
}

export interface ParticleState {
  totalCount: number;
  collectedCount: number;
}

interface ParticleStateExtended extends ParticleState {
  outsideCount: number;
  outsideCountAdd: number;
  insideCountAdd: number;
}

@Options({
  methods: { center },
  components: {
    GameObject,
    GameContainer,
    CustomSprite,
    Line,
  },
  emits: ['finished'],
})
export default class CleanUpParticles extends Vue {
  @Prop() readonly trackingManager!: TrackingManager;
  @Prop({ default: { category: 'car', type: 'sport' } })
  readonly vehicle!: vehicleCalculation.Vehicle;
  @Prop({ default: [] })
  readonly trackingData!: TrackingData[];
  normalizedTrackingData: TrackingData[] = [];
  chartData: {
    labels: string[];
    datasets: DatasetData[];
  } = {
    labels: [],
    datasets: [],
  };
  gameConfig = gameConfig;
  gameWidth = 0;
  gameHeight = 0;
  padding = 10;
  readonly particleDivisionFactor = 1;
  readonly playTime = constants.cleanupTime * 1000;
  interval = -1;
  activeValue = 0;
  cleanupParticles: DrawingParticle[] = [];
  particleCollisionHandler = new ParticleCollisionHandler();
  particleState: { [key: string]: ParticleStateExtended } = {};
  renderer!: PIXI.Renderer;
  maxParticleCount = 50;
  spritesheet!: PIXI.Spritesheet;
  countdownTime = 5;
  containerAspectRation = 1.3;

  readonly maxCleanupThreshold = constants.maxCleanupThreshold;
  readonly calcChartHeight = constants.calcChartHeight;

  particleQueueEmit: { [key: string]: number } = {};
  particleQueueEmission: { [key: string]: number } = {};
  CollisionBorderType = CollisionBorderType;

  get intervalTime(): number {
    return this.playTime / this.normalizedTrackingData.length;
  }

  get evaluatingColor(): string {
    return themeColors.getEvaluatingColor();
  }

  get highlightColorTransparent(): string {
    return themeColors.convertToRGBA(themeColors.getHighlightColor(), 0.25);
  }

  get highlightColor(): string {
    return themeColors.convertToRGBA(themeColors.getHighlightColor());
  }

  get containerSpace(): number {
    return this.gameWidth / Object.keys(gameConfig.particles).length;
  }

  get containerWidth(): number {
    return this.containerSpace - this.padding * 2;
  }

  get containerHeight(): number {
    return this.containerWidth / this.containerAspectRation; // this.containerSize[1];
  }

  get particleBorder(): number {
    return this.containerHeight + 50;
  }

  get statusColor(): string {
    const color1 = new Color(themeColors.getBrainstormingColor());
    const color2 = new Color(themeColors.getEvaluatingColor());
    const color = color1.mix(
      color2,
      (1 / (this.maxParticleCount + 1)) * (this.cleanupParticles.length + 1),
      {
        space: 'lch',
        outputSpace: 'srgb',
      }
    );
    return color.toString();
  }

  get isFull(): boolean {
    return this.maxParticleCount <= this.cleanupParticles.length;
  }

  get vehicleParameter(): any {
    return configCalculation.getVehicleParameter(this.vehicle);
  }

  get particleArea(): number {
    return this.gameWidth * (this.gameHeight - this.particleBorder);
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

  getParticleDisplayName(particleName: string): string {
    return this.$t(`module.playing.moveit.enums.particle.${particleName}`);
  }

  getParticleAspect(particleName: string): number {
    return pixiUtil.getSpriteAspect(this.spritesheet, particleName);
  }

  convertFontSizeToScreenSize(fontSize: number): string {
    return constants.convertFontSizeToScreenSize(fontSize, this.gameWidth);
  }

  drawCircle(circle: PIXI.Graphics): void {
    pixiUtil.drawCircleWithGradient(circle, this.renderer);
  }

  drawStatusBackground(background: PIXI.Graphics): void {
    const width = this.gameWidth;
    const height = this.gameHeight - this.particleBorder;
    pixiUtil.drawRectWithGradient(
      background,
      this.renderer,
      width,
      height,
      this.statusColor
    );
  }

  textAdded(container: PIXI.Container): void {
    for (const child of container.children) {
      if (child instanceof PIXI.Text) {
        child.style.fill = this.evaluatingColor;
      }
    }
  }

  async updateChart(): Promise<void> {
    if (this.$refs.chartRef) {
      const chartRef = this.$refs.chartRef as any;
      if (chartRef.chart) {
        chartRef.chart.data = this.chartData;
        chartRef.chart.update();
      }
    }
  }

  initTrackingData(): void {
    if (
      this.trackingManager &&
      this.trackingManager.iterationStep &&
      !this.trackingManager.iterationStep.parameter.cleanUp
    ) {
      this.trackingManager.saveIterationStep({ cleanUp: {} });
    }
  }

  mounted(): void {
    this.initTrackingData();
    for (const particleName in gameConfig.particles) {
      this.particleState[particleName] = {
        totalCount: 0,
        collectedCount: 0,
        outsideCount: 0,
        outsideCountAdd: 0,
        insideCountAdd: 0,
      };
    }
    setTimeout(() => {
      this.loadActiveParticle();
    }, 1500);
    pixiUtil
      .loadTexture('/assets/games/moveit/molecules.json')
      .then((sheet) => (this.spritesheet = sheet));
    this.interval = setInterval(this.updatedLoop, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
    pixiUtil.unloadTexture('/assets/games/moveit/molecules.json');
  }

  updatedLoop(): void {
    this.activeValue++;
    if (this.activeValue <= this.normalizedTrackingData.length) {
      this.loadActiveParticle();
    }
    if (
      this.activeValue >
        this.normalizedTrackingData.length + this.countdownTime ||
      (this.activeValue > this.normalizedTrackingData.length &&
        this.cleanupParticles.length === 0)
    ) {
      this.$emit('finished', this.particleState);
      clearInterval(this.interval);
    }
  }

  @Watch('cleanupParticles.length', { immediate: true })
  onCleanupParticleCountChanged(): void {
    if (
      this.activeValue > this.normalizedTrackingData.length &&
      this.cleanupParticles.length === 0
    ) {
      this.$emit('finished', this.particleState);
      clearInterval(this.interval);
    }
  }

  initRenderer(renderer: PIXI.Renderer): void {
    this.renderer = renderer;
  }

  containerSize: [number, number] = [
    this.containerWidth,
    this.containerWidth / this.containerAspectRation,
  ];

  containerSizeChanged(size: [number, number]): void {
    this.containerSize = size;
  }

  now = Date.now();
  loadActiveParticle(): void {
    const emitParticles = async (
      emitCount: number,
      dataset: DatasetData,
      index: number
    ): Promise<void> => {
      for (let i = 0; i < emitCount; i++) {
        const particle: DrawingParticle = {
          uuid: uuidv4(),
          id: 0,
          name: dataset.name,
          label: dataset.label,
          group: index + 1,
          color: dataset.backgroundColor,
          position: [
            Math.random() * (this.gameWidth - this.particleRadius * 2) +
              this.particleRadius,
            this.particleBorder + this.particleRadius,
          ],
          highlighted: false,
        };
        this.cleanupParticles.push(particle);
        await delay(Math.floor(this.intervalTime / emitCount) - 1);
      }
    };

    this.now = Date.now();
    let allParticleSum = 0;
    for (const index in this.chartData.datasets) {
      const dataset = this.chartData.datasets[index];
      const particleName = dataset.name;
      if (this.activeValue < dataset.data.length) {
        this.particleQueueEmission[particleName] +=
          dataset.data[this.activeValue] / this.particleDivisionFactor;
        const newSum = allParticleSum + dataset.data[this.activeValue];
        if (newSum > this.maxCleanupThreshold) {
          if (allParticleSum < this.maxCleanupThreshold) {
            const outsideDelta = newSum - this.maxCleanupThreshold;
            this.particleQueueEmit[particleName] +=
              (dataset.data[this.activeValue] - outsideDelta) /
              this.particleDivisionFactor;
          }
        } else
          this.particleQueueEmit[particleName] +=
            dataset.data[this.activeValue] / this.particleDivisionFactor;
        allParticleSum = newSum;
        const emissionCount = Math.floor(
          this.particleQueueEmission[particleName]
        );
        this.particleState[particleName].totalCount += emissionCount;
        this.particleQueueEmission[particleName] -= emissionCount;
        const emitCount = Math.floor(this.particleQueueEmit[particleName]);
        this.particleQueueEmit[particleName] -= emitCount;
        if (emitCount < emissionCount) {
          this.particleState[particleName].outsideCountAdd =
            emissionCount - emitCount;
          this.particleState[particleName].outsideCount +=
            emissionCount - emitCount;

          setTimeout(() => {
            this.particleState[particleName].outsideCountAdd = 0;
          }, 900);
        }
        if (emitCount > 0) {
          this.particleState[particleName].insideCountAdd += emitCount;
          setTimeout(() => {
            this.particleState[particleName].insideCountAdd = 0;
          }, 900);
        }

        emitParticles(emitCount, dataset, parseInt(index));
      }
    }
    this.updateTracking();
  }

  updateTracking(): void {
    this.initTrackingData();
    if (this.trackingManager && this.trackingManager.iterationStep) {
      this.trackingManager.iterationStep.parameter.cleanUp.particleState =
        this.particleState;
      this.trackingManager.iterationStep.parameter.cleanUp.totalTime =
        this.chartData.datasets[0].data.length;
      this.trackingManager.iterationStep.parameter.cleanUp.remaindingTime =
        this.chartData.datasets[0].data.length - this.activeValue;
      this.trackingManager.iterationStep.parameter.cleanUp.workingTime =
        this.activeValue;
      this.trackingManager.saveIterationStep();
    }
  }

  destroyParticle(particle: GameObject): void {
    const id = particle.id;
    const index = this.cleanupParticles.findIndex((p) => p.id === id);
    if (index > -1) this.cleanupParticles.splice(index, 1);
    this.particleState[particle.options.name as string].collectedCount++;
  }

  outsideDrawingSpace(particle: GameObject): void {
    const id = particle.id;
    const index = this.cleanupParticles.findIndex((p) => p.id === id);
    if (index > -1) this.cleanupParticles.splice(index, 1);
    this.particleState[particle.options.name as string].outsideCount++;
  }

  maxChartValue = 0;
  @Watch('trackingData', { immediate: true })
  onTrackingDataChanged(): void {
    if (this.trackingData) {
      const normalizedTrackingData: TrackingData[] = [];
      const mappingLength = constants.cleanupTime;
      for (let i = 0; i < mappingLength; i++) {
        normalizedTrackingData[i] = {
          speed: mapArrayToConstantSize(
            this.trackingData,
            (item) => item.speed,
            i,
            mappingLength
          ),
          persons: mapArrayToConstantSize(
            this.trackingData,
            (item) => item.persons,
            i,
            mappingLength
          ),
          distance: mapArrayToConstantSize(
            this.trackingData,
            (item) => item.distance,
            i,
            mappingLength,
            CalculationType.Sum
          ),
          tireWareRate: mapArrayToConstantSize(
            this.trackingData,
            (item) => item.tireWareRate,
            i,
            mappingLength,
            CalculationType.Sum
          ),
          consumption: mapArrayToConstantSize(
            this.trackingData,
            (item) => item.consumption,
            i,
            mappingLength,
            CalculationType.Sum
          ),
        };
      }
      this.normalizedTrackingData = normalizedTrackingData;
      this.chartData.labels = this.normalizedTrackingData.map((data) =>
        Math.round(data.speed).toString()
      );
      let totalValue = 0;
      for (const particleName in gameConfig.particles) {
        const particle = gameConfig.particles[particleName];
        /*const speedFunction = new Function(
          'speed',
          `return ${particle.speedFunction[this.vehicle.category][this.vehicle.type]}`
        );*/
        let maxParticleValue = 0;
        const data = {
          name: particleName,
          label: this.getParticleDisplayName(particleName),
          data: this.normalizedTrackingData.map((data) => {
            const particleValue = configCalculation.statisticsValue(
              particleName,
              data,
              this.vehicleParameter
            ); // speedFunction(data.speed) / data.persons
            if (maxParticleValue < particleValue)
              maxParticleValue = particleValue;
            return particleValue;
          }),
          backgroundColor: particle.color,
          borderColor: particle.color,
          fill: {
            target: 'stack',
            above: `${particle.color}77`,
          },
        };
        this.chartData.datasets.push(data);
        this.particleQueueEmit[particleName] = 0;
        this.particleQueueEmission[particleName] = 0;
        totalValue += maxParticleValue;
      }
      totalValue += 1;
      if (this.maxChartValue < totalValue) this.maxChartValue = totalValue;
      setTimeout(() => {
        this.updateChart();
      }, 1000);
    }
  }
}
</script>

<style scoped lang="scss">
.chartArea {
  position: relative;
  height: 10rem;
  width: 100%;
}

.container-info {
  pointer-events: none;
  width: 100%;
  color: var(--color-primary);
  font-weight: bold;
  font-size: var(--container-font-size);
  text-align: center;

  td {
    width: 25%;
  }
}

.releaseArea {
  height: 4rem;
  background-image: url('../assets/sky.jpg');

  .container-info {
    position: relative;

    td {
      position: relative;
    }
  }
}

.overlay {
  position: absolute;
  top: 0;
  height: 10rem;
  width: 100%;
  background-color: #ffffff99;
  font-size: var(--font-size-xxxxlarge);
  text-align: center;
  vertical-align: center;
}

.gameArea {
  height: calc(100% - 14rem);
  width: 100%;
  position: relative;

  .container-info {
    position: absolute;
    z-index: 100;
    color: white;

    td {
      padding-top: calc(var(--container-font-size) * 2);
    }
  }
}

.custom-renderer-wrapper {
  height: 100%;
}

.animationContainer {
  --persons: 1;
  position: relative;
  font-weight: bold;
  font-size: var(--container-font-size);
  text-align: center;
}

.addCountOutside {
  @keyframes move-outside {
    0% {
      font-size: 0;
      bottom: -100%;
    }
    10% {
      font-size: var(--container-font-size);
      bottom: -100%;
    }
    90% {
      font-size: var(--container-font-size);
      bottom: 50%;
    }
    100% {
      font-size: 0;
      bottom: 50%;
    }
  }

  position: absolute;
  animation-name: move-outside;
  animation-duration: 0.9s;
  width: 100%;
  font-size: 0;
  color: var(--color-highlight);
}

.addCountInside {
  @keyframes move-inside {
    0% {
      font-size: 0;
      bottom: -150%;
    }
    10% {
      font-size: var(--container-font-size);
      bottom: -150%;
    }
    90% {
      font-size: var(--container-font-size);
      bottom: -300%;
    }
    100% {
      font-size: 0;
      bottom: -300%;
    }
  }

  position: absolute;
  animation-name: move-inside;
  animation-duration: 0.9s;
  width: 100%;
  font-size: 0;
  color: var(--color-primary);
}
</style>
