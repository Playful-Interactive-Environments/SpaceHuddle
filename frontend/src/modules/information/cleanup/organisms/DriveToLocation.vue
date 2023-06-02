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
    <mgl-map :center="mapCenter" :zoom="mapZoom" @map:load="onLoad">
      <mgl-geo-json-source
        v-if="routeCalculated"
        source-id="geojson"
        :data="routePath"
      >
        <mgl-line-layer
          layer-id="geojson"
          :layout="routeLayout"
          :paint="routePaint"
        />
      </mgl-geo-json-source>
      <CustomMapMarker :coordinates="mapStart">
        <template v-slot:icon>
          <font-awesome-icon icon="location-crosshairs" class="pin" />
        </template>
      </CustomMapMarker>
      <CustomMapMarker :coordinates="mapEnd">
        <template v-slot:icon>
          <font-awesome-icon icon="flag-checkered" class="pin" />
        </template>
      </CustomMapMarker>
    </mgl-map>
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
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import Joystick from 'vue-joystick-component';
import { Line } from 'vue-chartjs';
import * as gameConfig from '@/modules/information/cleanup/data/gameConfig.json';
import { OSRM } from '@routingjs/osrm';
import {
  MglDefaults,
  MglGeoJsonSource,
  MglLineLayer,
  MglMarker,
  MglNavigationControl,
  MglMap,
  MglEvent,
} from 'vue-maplibre-gl';
import { LineLayerSpecification } from 'maplibre-gl';
import { FeatureCollection } from 'geojson';
import CustomMapMarker from '@/components/shared/atoms/CustomMapMarker.vue';

MglDefaults.style = `https://api.maptiler.com/maps/streets/style.json?key=${process.env.VUE_APP_MAPTILER_KEY}`;

@Options({
  components: {
    FontAwesomeIcon,
    MglGeoJsonSource,
    MglLineLayer,
    MglMarker,
    MglNavigationControl,
    MglMap,
    CustomMapMarker,
    Joystick,
    Line,
  },
  emits: ['update:useFullSize'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class DriveToLocation extends Vue {
  @Prop({ default: {} }) readonly parameter!: any;
  mapZoomDefault = 14;
  mapCenter: [number, number] = [0, 0];
  mapStart: [number, number] = [0, 0];
  mapEnd: [number, number] = [0, 0];
  mapZoom = this.mapZoomDefault;
  routeCalculated = false;
  routePath: FeatureCollection = {
    type: 'FeatureCollection',
    features: [
      {
        type: 'Feature',
        properties: {},
        geometry: {
          type: 'LineString',
          coordinates: [] as any[],
        },
      },
    ],
  };
  routeLayout: LineLayerSpecification['layout'] = {
    'line-join': 'round',
    'line-cap': 'round',
  };
  routePaint: LineLayerSpecification['paint'] = {
    'line-color': '#FF0000',
    'line-width': 8,
  };

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

  onLoad(e: MglEvent): void {
    const map = e.map;
    const notNeededLayers = map.getStyle().layers.filter((layer) => {
      const layerCategory = layer['source-layer'];
      const layerType = layer['type'];
      if (layerCategory) {
        return layerType === 'symbol' && layerCategory !== 'place';
      }
      return false;
    });
    for (const layer of notNeededLayers) {
      map.removeLayer(layer.id);
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
    if (
      this.parameter.mapStart &&
      this.mapStart[0] === 0 &&
      this.mapStart[1] === 0
    ) {
      this.mapStart = this.parameter.mapStart;
    }
    if (this.parameter.mapEnd && this.mapEnd[0] === 0 && this.mapEnd[1] === 0) {
      this.mapEnd = this.parameter.mapEnd;
    }
    if (this.parameter.mapZoom && this.mapZoom === this.mapZoomDefault) {
      this.mapZoom = this.parameter.mapZoom;
    }

    (this.routePath.features[0].geometry as any).coordinates = [
      this.mapStart,
      this.mapEnd,
    ];

    const osrm = new OSRM(); // URL defaults to https://routing.openstreetmap.de/routed-bike
    osrm
      .directions(
        [
          [this.mapStart[1], this.mapStart[0]],
          [this.mapEnd[1], this.mapEnd[0]],
        ],
        'car'
      )
      .then((d) => {
        d.directions.forEach((direction) => {
          if (direction.feature.geometry) {
            (this.routePath.features[0].geometry as any).coordinates =
              direction.feature.geometry.coordinates.map((position) => [
                position[1],
                position[0],
              ]);
          }
        });
        this.routeCalculated = true;
      });
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

.pin {
  --pin-color: var(--color-primary);
  font-size: var(--font-size-xxxlarge);
  color: var(--pin-color);
}

.pin-small {
  --pin-color: var(--color-primary);
  font-size: var(--font-size-small);
  color: var(--pin-color);
}
</style>
