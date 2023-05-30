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
      }"
    />
  </div>
  <div class="mapArea">
    <mapbox-map :access-token="MapboxKey" :center="mapCenter" :zoom="mapZoom">
    </mapbox-map>
    <div class="overlay-top">
      <el-progress type="dashboard" :percentage="moveSpeed" :color="colors">
        <template #default="{ percentage }">
          <div>{{ percentage }}</div>
          <div>
            {{ $t('module.information.cleanup.participant.speed') }}
          </div>
        </template>
      </el-progress>
    </div>
    <div class="overlay-bottom">
      <Joystick
        :style="{
          margin: '50px',
        }"
        @move="move($event)"
        @start="start"
        @stop="stop"
        stickColor="white"
      />
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import {
  MapboxMap,
  MapboxMarker,
  MapboxNavigationControl,
} from 'vue-mapbox-ts';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import Joystick from 'vue-joystick-component';
import { Line } from 'vue-chartjs';
import * as gameConfig from '@/modules/information/cleanup/data/gameConfig.json';

@Options({
  components: {
    FontAwesomeIcon,
    MapboxMap,
    MapboxNavigationControl,
    MapboxMarker,
    Joystick,
    Line,
  },
  emits: ['update:useFullSize'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class DriveToLocation extends Vue {
  @Prop({ default: {} }) readonly parameter!: any;
  mapZoomDefault = 14;
  mapCenter: number[] = [0, 0];
  mapZoom = this.mapZoomDefault;

  intervalTime = 1000;
  interval = -1;
  isMoving = false;
  moveSpeed = 0;
  moveDirection = [0, 0];

  colors = [
    { color: '#01cf9e', percentage: 20 },
    { color: '#f3a40a', percentage: 50 },
    { color: '#fe6e5d', percentage: 100 },
  ];

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

  get MapboxKey(): string {
    return process.env.VUE_APP_MAPBOX_KEY;
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

  async mounted(): Promise<void> {
    for (const particleName in gameConfig.particles) {
      const particle = gameConfig.particles[particleName];
      const data = {
        label: particleName,
        data: [],
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

  unmounted(): void {
    clearInterval(this.interval);
  }

  @Watch('parameter', { immediate: true, deep: true })
  onParameterChanged(): void {
    if (
      this.parameter.mapCenter &&
      this.mapCenter[0] === 0 &&
      this.mapCenter[1] === 0
    ) {
      this.mapCenter = this.parameter.mapCenter;
    }
    if (this.parameter.mapZoom && this.mapZoom === this.mapZoomDefault) {
      this.mapZoom = this.parameter.mapZoom;
    }
  }

  start(): void {
    this.isMoving = true;
    this.interval = setInterval(this.updateTrace, this.intervalTime);
  }

  stop(): void {
    clearInterval(this.interval);
    this.isMoving = false;
    this.moveSpeed = 0;
  }

  move(event: any): void {
    this.moveSpeed = event.distance;
    this.moveDirection = [event.x, event.y];
  }

  updateTrace(): void {
    if (this.isMoving) {
      for (const particleName in gameConfig.particles) {
        const particle = gameConfig.particles[particleName];
        const dataset = this.chartData.datasets.find(
          (ds) => ds.label === particleName
        );
        if (dataset) {
          dataset.data = [
            ...dataset.data,
            this.moveSpeed * particle.speedFactor,
          ];
        }
      }
      this.chartData.labels.push(Math.round(this.moveSpeed).toString());
      this.updateChart();
    }
  }
}
</script>

<style lang="scss" scoped>
.overlay-top {
  position: absolute;
  z-index: 100;
  top: 0.5rem;
  left: 0.5rem;

  .el-progress {
    background: rgba(0, 0, 0, 0.5);
    border-radius: 50%;
    padding: 0.1rem;
    --el-text-color-regular: white;
  }
}
.overlay-bottom {
  position: absolute;
  z-index: 100;
  bottom: 0.5rem;
  right: 0.5rem;
}

.chartArea {
  height: 10rem;
  width: 100%;
}

.mapArea {
  height: calc(100% - 10rem);
  width: 100%;
  position: relative;
}
</style>
