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
            },
          },
        },
      }"
    />
  </div>
  <div class="gameArea">
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
          <GameObject
            v-for="(particle, index) in Object.keys(gameConfig.particles)"
            :key="particle"
            type="rect"
            :x="index * containerSpace + padding"
            :isStatic="true"
            :options="{
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
              texture="/assets/games/cleanup/dumpster.svg"
              :tint="gameConfig.particles[particle].color"
            >
              <text
                :style="{
                  fill: '#ffffff',
                  fontSize: 16,
                  fontWeight: 'bold',
                  textAlign: 'center',
                }"
                :anchor="0.5"
                :x="containerWidth / 2"
                :y="containerWidth / 2"
              >
                {{ getParticleDisplayName(particle) }}
                \n
                {{
                  particleState[getParticleDisplayName(particle)].collectedCount
                }}
                /
                {{ particleState[getParticleDisplayName(particle)].totalCount }}
              </text>
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
              label: particle.label,
              collisionFilter: {
                group: particle.group,
                category: 0x0001,
                mask: 0x0001,
              },
            }"
            :collisionsFilter="(collision: Matter.Collision) => {
              return collision.bodyA.isStatic !== collision.bodyB.isStatic;
            }"
            :collision-handler="particleCollisionHandler"
            @destroyObject="destroyParticle"
            @outsideDrawingSpace="outsideDrawingSpace"
          >
            <Graphics
              :radius="particleRadius"
              :color="particle.color"
              @render="drawCircle"
            >
              <text
                :style="{ fill: '#ffffff', fontSize: 16, fontWeight: 'bold' }"
                :anchor="0.5"
              >
                {{ particle.id }}
              </text>
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
import { GradientFactory } from '@pixi-essentials/gradients';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import { Chart } from 'chart.js';
import { ParticleCollisionHandler } from '@/modules/information/cleanup/types/ParticleCollisionHandler';
import annotationPlugin from 'chartjs-plugin-annotation';
import { v4 as uuidv4 } from 'uuid';
import { center } from '@turf/turf';
import { delay } from '@/utils/wait';
import Color from 'colorjs.io';
Chart.register(annotationPlugin);

interface DrawingParticle {
  uuid: string;
  id: number;
  label: string;
  group: number;
  color: string;
  position: [number, number];
}

interface ParticleState {
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
  emits: [],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class CleanUpParticles extends Vue {
  @Prop({ default: 'car' }) readonly vehicle!: 'car' | 'bike';
  @Prop({ default: [] })
  readonly trackingData!: number[];
  chartData: {
    labels: string[];
    datasets: {
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
  readonly intervalTime = 10000;
  interval = -1;
  activeValue = 0;
  cleanupParticles: DrawingParticle[] = [];
  particleRadius = 20;
  particleCollisionHandler = new ParticleCollisionHandler();
  particleState: { [key: string]: ParticleState } = {};
  renderer!: PIXI.Renderer;
  maxParticleCount = 50;
  outsideCount = 0;

  get containerSpace(): number {
    return this.gameWidth / Object.keys(gameConfig.particles).length;
  }

  get containerWidth(): number {
    return this.containerSpace - this.padding * 2;
  }

  get containerHeight(): number {
    return this.containerSize[1];
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

  getParticleDisplayName(particleName: string): string {
    return this.$t(`module.information.cleanup.enums.particle.${particleName}`);
  }

  drawCircle(circle: PIXI.Graphics): void {
    const radius = (circle as any).radius;
    const renderTexture = PIXI.RenderTexture.create({
      width: radius * 2,
      height: radius * 2,
    });
    GradientFactory.createRadialGradient(this.renderer, renderTexture, {
      x0: radius,
      y0: radius,
      r0: 0,
      x1: radius,
      y1: radius,
      r1: radius,
      colorStops: [
        { color: '#ffffffff', offset: 0.5 },
        { color: '#ffffff00', offset: 1 },
      ],
    });
    const matrix: PIXI.Matrix = new PIXI.Matrix();
    matrix.translate(-radius, -radius);
    circle.beginTextureFill({
      texture: renderTexture,
      color: (circle as any).color,
      alpha: 0.9,
      matrix: matrix,
    });
    circle.drawCircle(0, 0, (circle as any).radius);
    circle.endFill();
    circle.interactive = true;
  }

  drawStatusBackground(background: PIXI.Graphics): void {
    const width = this.gameWidth;
    const height = this.gameHeight - this.particleBorder;
    const renderTexture = PIXI.RenderTexture.create({
      width: width,
      height: height,
    });
    GradientFactory.createLinearGradient(this.renderer, renderTexture, {
      x0: 0,
      y0: 0,
      x1: 0,
      y1: height,
      colorStops: [
        { color: '#ffffff00', offset: 0 },
        { color: '#ffffffff', offset: 1 },
      ],
    });
    background.beginFill(this.renderer.background.color);
    background.drawRect(0, 0, width, height);
    background.endFill();
    background.beginTextureFill({
      texture: renderTexture,
      color: this.statusColor,
      alpha: 0.3,
    });
    background.drawRect(0, 0, width, height);
    background.endFill();
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

  mounted(): void {
    for (const particleName in gameConfig.particles) {
      this.particleState[this.getParticleDisplayName(particleName)] = {
        totalCount: 0,
        collectedCount: 0,
      };
    }
    setTimeout(() => {
      this.loadActiveParticle();
    }, 1500);
    this.interval = setInterval(this.updatedLoop, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }

  updatedLoop(): void {
    this.activeValue++;
    this.loadActiveParticle();
    if (this.activeValue > this.trackingData.length)
      clearInterval(this.interval);
  }

  initRenderer(renderer: PIXI.Renderer): void {
    this.renderer = renderer;
  }

  containerSize: [number, number] = [this.containerWidth, this.containerWidth];
  containerSizeChanged(size: [number, number]): void {
    this.containerSize = size;
  }

  async loadActiveParticle(): Promise<void> {
    for (const index in this.chartData.datasets) {
      const dataset = this.chartData.datasets[index];
      if (this.activeValue < dataset.data.length) {
        const value = dataset.data[this.activeValue] / 10;
        this.particleState[dataset.label].totalCount += Math.ceil(value);
        for (let i = 0; i < value; i++) {
          const particle: DrawingParticle = {
            uuid: uuidv4(),
            id: 0,
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
          /*if (this.cleanupParticles.length > this.maxParticleCount) {

          }*/
          await delay(500);
        }
      }
    }
  }

  destroyParticle(particle: GameObject): void {
    const id = particle.id;
    const index = this.cleanupParticles.findIndex((p) => p.id === id);
    if (index > -1) this.cleanupParticles.splice(index, 1);
    this.particleState[particle.options.label as string].collectedCount++;
  }

  outsideDrawingSpace(particle: GameObject): void {
    const id = particle.id;
    const index = this.cleanupParticles.findIndex((p) => p.id === id);
    if (index > -1) this.cleanupParticles.splice(index, 1);
    this.outsideCount++;
  }

  @Watch('trackingData', { immediate: true })
  onTrackingDataChanged(): void {
    if (this.trackingData) {
      this.chartData.labels = this.trackingData.map((speed) =>
        speed.toString()
      );
      for (const particleName in gameConfig.particles) {
        const particle = gameConfig.particles[particleName];
        const speedFunction = new Function(
          'speed',
          `return ${particle.speedFunction[this.vehicle]}`
        );
        const data = {
          label: this.getParticleDisplayName(particleName),
          data: this.trackingData.map((speed) => speedFunction(speed)),
          backgroundColor: particle.color,
          borderColor: particle.color,
          fill: {
            target: 'stack',
            above: `${particle.color}77`,
          },
        };
        this.chartData.datasets.push(data);
      }
      setTimeout(() => {
        this.updateChart();
      }, 1000);
    }
  }
}
</script>

<style scoped lang="scss">
.chartArea {
  height: 10rem;
  width: 100%;
}

.gameArea {
  height: calc(100% - 10rem);
  width: 100%;
}

.custom-renderer-wrapper {
  height: 100%;
}
</style>
