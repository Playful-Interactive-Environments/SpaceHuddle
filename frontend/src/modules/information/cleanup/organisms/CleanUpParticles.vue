<template>
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
                borderColor: 'rgb(255, 99, 132)',
                borderWidth: 2,
              },
              box1: {
                type: 'box',
                xMin: 0,
                xMax: trackingData.length,
                yMin: maxCleanupThreshold,
                yMax: calcChartHeight(maxChartValue),
                backgroundColor: 'rgba(255, 99, 132, 0.25)',
                borderColor: 'rgb(255, 99, 132)',
              },
            },
          },
        },
      }"
    />
    <div class="overlay" v-if="activeValue > trackingData.length">
      {{ trackingData.length + countdownTime - activeValue + 1 }}
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
      :collisionsFilter="(collision: Matter.Collision) => {
            return collision.bodyA.isStatic !== collision.bodyB.isStatic;
          }"
      :useBorders="!isFull"
      @initRenderer="initRenderer"
    >
      <template v-slot:default>
        <container v-if="gameWidth">
          <sprite
            texture="/assets/games/cleanup/road02.png"
            :width="gameWidth"
            :height="gameHeight"
          ></sprite>
          <GameObject
            v-for="(particle, index) in Object.keys(gameConfig.particles)"
            :key="particle"
            :x="index * containerSpace + padding"
            type="rect"
            :isStatic="true"
            :options="{
              name: particle,
              label: getParticleDisplayName(particle),
              collisionFilter: {
                group: index + 1,
                category: 0x0010,
              },
            }"
            :render-delay="1000"
            @sizeChanged="containerSizeChanged"
          >
            <sprite
              :width="containerWidth"
              :height="containerHeight"
              texture="/assets/games/cleanup/dumpster.svg"
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
          <container
            v-if="outsideCount > 0"
            :x="gameWidth - containerWidth / 2"
            :y="particleBorder"
          >
            <Graphics
              :radius="containerWidth / 3"
              color="#ffffff"
              @render="drawCircle"
            >
              <sprite
                texture="/assets/games/cleanup/explosion.svg"
                tint="#fe6e5d"
                :x="-containerWidth / 6"
                :y="-containerWidth / 6"
                :width="containerWidth / 3"
                :height="containerWidth / 4"
              >
              </sprite>
            </Graphics>
            <text
              :style="{
                fill: '#fe6e5d',
                fontSize: 16,
                fontWeight: 'bold',
                textAlign: 'center',
              }"
              :anchor="0.5"
              :x="0"
              :y="containerWidth / 6"
            >
              {{ outsideCount }}
            </text>
          </container>
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
                category: 0x0001,
                mask: 0x0001,
              },
            }"
            :collision-handler="particleCollisionHandler"
            @destroyObject="destroyParticle"
            @outsideDrawingSpace="outsideDrawingSpace"
            @collision="updateTracking"
          >
            <Graphics
              :radius="particleRadius"
              :color="particle.color"
              @render="drawCircle"
            >
              <!--<text
                :style="{ fill: '#ffffff', fontSize: 16, fontWeight: 'bold' }"
                :anchor="0.5"
              >
                {{ particle.id }}
              </text>-->
              <sprite
                :texture="spritesheet.textures[particle.name]"
                :anchor="0.5"
                :tint="particle.color"
                :width="particleRadius * 1.5"
                :height="
                  getParticleAspect(particle.name) * particleRadius * 1.5
                "
              >
              </sprite>
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
import * as gameConfig from '@/modules/information/cleanup/data/gameConfig.json';
import { Application as Pixi } from 'vue3-pixi';
import * as PIXI from 'pixi.js';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import { Chart } from 'chart.js';
import { ParticleCollisionHandler } from '@/modules/information/cleanup/types/ParticleCollisionHandler';
import annotationPlugin from 'chartjs-plugin-annotation';
import { v4 as uuidv4 } from 'uuid';
import { center } from '@turf/turf';
import { delay } from '@/utils/wait';
import Color from 'colorjs.io';
import { TrackingData } from '@/modules/information/cleanup/organisms/DriveToLocation.vue';
import * as configCalculation from '@/modules/information/cleanup/utils/configCalculation';
import * as pixiUtil from '@/utils/pixi';
import { TaskParticipantState } from '@/types/api/TaskParticipantState';
import { TaskParticipantIteration } from '@/types/api/TaskParticipantIteration';
import * as constants from '@/modules/information/cleanup/utils/consts';
Chart.register(annotationPlugin);

interface DrawingParticle {
  uuid: string;
  id: number;
  name: string;
  label: string;
  group: number;
  color: string;
  position: [number, number];
}

export interface ParticleState {
  totalCount: number;
  collectedCount: number;
}

@Options({
  methods: { center },
  components: {
    GameObject,
    GameContainer,
    Line,
    Pixi,
  },
  emits: ['finished', 'update:trackingState', 'update:trackingIteration'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class CleanUpParticles extends Vue {
  @Prop() readonly trackingState!: TaskParticipantState;
  @Prop() readonly trackingIteration!: TaskParticipantIteration;
  @Prop({ default: 'car' }) readonly vehicle!:
    | 'car'
    | 'bike'
    | 'motorcycle'
    | 'scooter'
    | 'bus';
  @Prop({ default: 'sport' }) readonly vehicleType!: string;
  @Prop({ default: [] })
  readonly trackingData!: TrackingData[];
  chartData: {
    labels: string[];
    datasets: {
      name: string;
      label: string;
      backgroundColor: string;
      borderColor: string;
      data: number[];
      fill: any;
    }[];
  } = {
    labels: [],
    datasets: [],
  };
  gameConfig = gameConfig;
  gameWidth = 0;
  gameHeight = 0;
  padding = 10;
  readonly particleDivisionFactor = 1;
  readonly intervalTime = 5000;
  interval = -1;
  activeValue = 0;
  cleanupParticles: DrawingParticle[] = [];
  particleCollisionHandler = new ParticleCollisionHandler();
  particleState: { [key: string]: ParticleState } = {};
  renderer!: PIXI.Renderer;
  maxParticleCount = 50;
  outsideCount = 0;
  spritesheet!: PIXI.Spritesheet;
  countdownTime = 5;
  containerAspectRation = 1.3;

  readonly maxCleanupThreshold = constants.maxCleanupThreshold;
  readonly calcChartHeight = constants.calcChartHeight;

  particleQueue: { [key: string]: number } = {};

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
    const color1 = new Color('#01cf9e');
    const color2 = new Color('#fe6e5d');
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
    return configCalculation.getVehicleParameter(
      this.vehicle,
      this.vehicleType
    );
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
    return this.$t(`module.information.cleanup.enums.particle.${particleName}`);
  }

  getParticleAspect(particleName: string): number {
    if (
      this.spritesheet?.data?.frames &&
      particleName in this.spritesheet.data.frames
    ) {
      const h = this.spritesheet.data.frames[particleName].sourceSize?.h;
      const w = this.spritesheet.data.frames[particleName].sourceSize?.w;
      if (h && w) return h / w;
    }
    return 1;
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
    if (this.trackingIteration && !this.trackingIteration.parameter.cleanUp) {
      this.trackingIteration.parameter.cleanUp = {};
      this.$emit('update:trackingIteration', this.trackingIteration);
    }
  }

  mounted(): void {
    this.initTrackingData();
    for (const particleName in gameConfig.particles) {
      this.particleState[particleName] = {
        totalCount: 0,
        collectedCount: 0,
      };
    }
    setTimeout(() => {
      this.loadActiveParticle();
    }, 1500);
    PIXI.Assets.load('/assets/games/cleanup/molecules.json').then(
      (sheet) => (this.spritesheet = sheet)
    );
    this.interval = setInterval(this.updatedLoop, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }

  updatedLoop(): void {
    this.activeValue++;
    if (this.activeValue <= this.trackingData.length) {
      this.loadActiveParticle();
    }
    if (
      this.activeValue > this.trackingData.length + this.countdownTime ||
      (this.activeValue > this.trackingData.length &&
        this.cleanupParticles.length === 0)
    ) {
      this.$emit('finished', this.particleState);
      clearInterval(this.interval);
    }
  }

  @Watch('cleanupParticles.length', { immediate: true })
  onCleanupParticleCountChanged(): void {
    if (
      this.activeValue > this.trackingData.length &&
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

  async loadActiveParticle(): Promise<void> {
    let activeParticleEmitCount = 0;
    for (const index in this.chartData.datasets) {
      const dataset = this.chartData.datasets[index];
      const particleName = dataset.name;
      if (this.activeValue < dataset.data.length) {
        const value =
          dataset.data[this.activeValue] / this.particleDivisionFactor;
        this.particleQueue[particleName] += value;
        const emissionCount = Math.floor(this.particleQueue[particleName]);
        this.particleState[particleName].totalCount += emissionCount;
        this.particleQueue[particleName] -= emissionCount;
        const maxEmitCount =
          this.maxCleanupThreshold / this.particleDivisionFactor;
        let emitCount = emissionCount;
        if (activeParticleEmitCount + emissionCount > maxEmitCount) {
          if (activeParticleEmitCount < maxEmitCount)
            emitCount = maxEmitCount - activeParticleEmitCount;
          else emitCount = 0;
        }
        activeParticleEmitCount += emissionCount;
        if (emitCount < emissionCount) {
          this.outsideCount += emissionCount - emitCount;
        }
        for (let i = 0; i < emitCount; i++) {
          const particle: DrawingParticle = {
            uuid: uuidv4(),
            id: 0,
            name: dataset.name,
            label: dataset.label,
            group: parseInt(index) + 1,
            color: dataset.backgroundColor,
            position: [
              Math.random() * (this.gameWidth - this.particleRadius * 2) +
                this.particleRadius,
              this.particleBorder + this.particleRadius,
            ],
          };
          this.cleanupParticles.push(particle);
          await delay(Math.floor(this.intervalTime / emitCount) - 1);
        }
      }
    }
    this.updateTracking();
  }

  updateTracking(): void {
    this.initTrackingData();
    if (this.trackingIteration) {
      this.trackingIteration.parameter.cleanUp.particleState =
        this.particleState;
      this.trackingIteration.parameter.cleanUp.totalTime =
        this.chartData.datasets[0].data.length;
      this.trackingIteration.parameter.cleanUp.remaindingTime =
        this.chartData.datasets[0].data.length - this.activeValue;
      this.trackingIteration.parameter.cleanUp.workingTime = this.activeValue;
      this.$emit('update:trackingIteration', this.trackingIteration);
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
    this.outsideCount++;
  }

  maxChartValue = 0;
  @Watch('trackingData', { immediate: true })
  onTrackingDataChanged(): void {
    if (this.trackingData) {
      this.chartData.labels = this.trackingData.map((data) =>
        Math.round(data.speed).toString()
      );
      let totalValue = 0;
      for (const particleName in gameConfig.particles) {
        const particle = gameConfig.particles[particleName];
        /*const speedFunction = new Function(
          'speed',
          `return ${particle.speedFunction[this.vehicle][this.vehicleType]}`
        );*/
        let maxParticleValue = 0;
        const data = {
          name: particleName,
          label: this.getParticleDisplayName(particleName),
          data: this.trackingData.map((data) => {
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
        this.particleQueue[particleName] = 0;
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
  height: calc(100% - 10rem);
  width: 100%;
  position: relative;
}

.custom-renderer-wrapper {
  height: 100%;
}

.container-info {
  pointer-events: none;
  position: absolute;
  z-index: 100;
  width: 100%;
  color: white;
  font-weight: bold;
  font-size: var(--container-font-size);
  text-align: center;

  td {
    padding-top: calc(var(--container-font-size) * 2);
    width: 25%;
  }
}
</style>
