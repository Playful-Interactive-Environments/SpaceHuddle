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
        source-id="routePath"
        :data="routePath"
      >
        <mgl-line-layer
          layer-id="routePath"
          :layout="routeLayout"
          :paint="routePaint"
        />
      </mgl-geo-json-source>
      <mgl-geo-json-source source-id="drivenPath" :data="drivenPath">
        <mgl-line-layer
          layer-id="drivenPath"
          :layout="routeLayout"
          :paint="drivenPaint"
        />
      </mgl-geo-json-source>
      <CustomMapMarker
        :coordinates="mapDrivingPoint"
        :rotation="mapDrivingRotation"
        rotation-alignment="map"
      >
        <template v-slot:icon>
          <img
            src="@/modules/information/cleanup/assets/car.png"
            alt="car"
            :width="20"
          />
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
import { OsrmCustom as OSRM } from '@/utils/osrm';
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
import * as turf from '@turf/turf';

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
  emits: ['update:useFullSize', 'goalReached'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class DriveToLocation extends Vue {
  @Prop({ default: 'car' }) readonly vehicle!: 'car' | 'bike';
  @Prop({ default: {} }) readonly parameter!: any;
  mapZoomDefault = 14;
  mapCenter: [number, number] = [0, 0];
  mapStart: [number, number] = [0, 0];
  mapEnd: [number, number] = [0, 0];
  mapWayPoints: [number, number][] = [];
  mapDrivingPoint: [number, number] = [0, 0];
  mapDrivingRotation = 0;
  mapZoom = this.mapZoomDefault;
  routeCalculated = false;
  routePath: FeatureCollection = this.getRouteObject([]);
  drivenPath: FeatureCollection = this.getRouteObject([]);
  routeLayout: LineLayerSpecification['layout'] = {
    'line-join': 'round',
    'line-cap': 'round',
  };
  routePaint: LineLayerSpecification['paint'] = {
    'line-color': '#0000FF',
    'line-width': 2,
  };
  drivenPaint: LineLayerSpecification['paint'] = {
    'line-color': '#FF0000',
    'line-width': 8,
  };

  readonly intervalTime = 50;
  interval = -1;
  isMoving = false;
  moveSpeed = 0;
  moveDirection: [number, number] = [0, 0];

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

  getRouteObject(pathCoordinates: [number, number][]): FeatureCollection {
    return {
      type: 'FeatureCollection',
      features: [
        {
          type: 'Feature',
          properties: {},
          geometry: {
            type: 'LineString',
            coordinates: pathCoordinates,
          },
        },
      ],
    };
  }

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
        label: this.$t(
          `module.information.cleanup.enums.particle.${particleName}`
        ),
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
    this.mapDrivingPoint = [...this.mapStart];

    (this.routePath.features[0].geometry as any).coordinates = [
      this.mapStart,
      this.mapEnd,
    ];

    this.calculateRoute(this.mapStart, this.mapEnd).then(() => {
      const pathPoints = (this.routePath.features[0].geometry as any)
        .coordinates;
      this.mapDrivingPoint = pathPoints[0];
      this.mapDrivingRotation = turf.bearing(
        turf.point(this.mapDrivingPoint),
        turf.point(pathPoints[1])
      );
      this.routeCalculated = true;
    });
  }

  async calculateRoute(
    start: [number, number],
    end: [number, number],
    checkDistance = false
  ): Promise<boolean> {
    const osrm = new OSRM('car', {
      userAgent: '',
    });
    const wayPoints = [...this.mapWayPoints.map((p) => [p[1], p[0]])] as [
      number,
      number
    ][];
    wayPoints.push([start[1], start[0]]);
    wayPoints.push([end[1], end[0]]);
    const path = await osrm.directions(wayPoints, 'car');
    const pathCoordinates: [number, number][] = [];
    path.directions.forEach((direction) => {
      if (direction.feature.geometry) {
        pathCoordinates.push(
          ...(direction.feature.geometry.coordinates.map((position) => [
            position[1],
            position[0],
          ]) as [number, number][])
        );
      }
    });
    let setNewPath = true;
    if (checkDistance) {
      const line = turf.lineString(pathCoordinates);
      const pt = turf.point(start);
      const distance = turf.pointToLineDistance(pt, line);
      setNewPath = distance < 0.01;
    }
    if (setNewPath) {
      this.routePath = this.getRouteObject(pathCoordinates);
      this.mapWayPoints.push(start);
      return true;
    }
    return false;
  }

  async isOnPossibleRoute(
    point: [number, number]
  ): Promise<{ distance: number; location: [number, number] }> {
    const osrm = new OSRM('car', {
      userAgent: '',
    });
    const path = (await osrm.nearest([point[1], point[0]], 'car')).waypoints[0];
    return {
      distance: path.distance,
      location: [path.location[0], path.location[1]],
    };
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
    this.noStreet = false;
  }

  noStreet = false;
  async updateTrace(): Promise<void> {
    const speedDistanceFactor = 0.00005;
    const speedRoadDistanceFactor = 0.00005;
    const calcAngle = (point: [number, number]): number => {
      return Math.atan2(point[0], point[1]) * (180 / Math.PI);
    };

    if (this.isMoving) {
      const destination = turf.destination(
        turf.point(this.mapDrivingPoint),
        this.moveSpeed * speedDistanceFactor,
        calcAngle(this.moveDirection)
      );
      const newDrivingPoint: [number, number] = [
        destination.geometry.coordinates[0],
        destination.geometry.coordinates[1],
      ];
      let isOnRoute = !this.noStreet;
      const pathPoints = (this.routePath.features[0].geometry as any)
        .coordinates as [number, number][];
      if (isOnRoute) {
        const line = turf.lineString(pathPoints);
        const pt = turf.point(newDrivingPoint);
        const distance = turf.pointToLineDistance(pt, line);
        if (distance < 0.005) {
          const snapped = turf.nearestPointOnLine(line, pt);
          newDrivingPoint[0] = snapped.geometry.coordinates[0];
          newDrivingPoint[1] = snapped.geometry.coordinates[1];
        } else {
          const possibleRoadPointTurf = turf.destination(
            turf.point(this.mapDrivingPoint),
            this.moveSpeed * speedRoadDistanceFactor,
            calcAngle(this.moveDirection)
          );
          const possibleRoadPoint: [number, number] = [
            possibleRoadPointTurf.geometry.coordinates[0],
            possibleRoadPointTurf.geometry.coordinates[1],
          ];
          const roadPoint = await this.isOnPossibleRoute(possibleRoadPoint);
          if (roadPoint.distance < 3) {
            await this.calculateRoute(possibleRoadPoint, this.mapEnd);
            const snapped = turf.nearestPointOnLine(line, pt);
            newDrivingPoint[0] = snapped.geometry.coordinates[0];
            newDrivingPoint[1] = snapped.geometry.coordinates[1];
          } else isOnRoute = false;
        }
      }
      this.mapDrivingRotation = turf.bearing(
        turf.point(this.mapDrivingPoint),
        newDrivingPoint
      );
      if (isOnRoute) {
        const coordinates = (this.drivenPath.features[0].geometry as any)
          .coordinates;
        coordinates.push(newDrivingPoint);
        this.drivenPath = this.getRouteObject(coordinates);
        this.mapDrivingPoint = newDrivingPoint;
        const goalDistance = turf.distance(
          turf.point(this.mapDrivingPoint),
          turf.point(pathPoints[pathPoints.length - 1])
        );
        if (goalDistance < 0.001)
          this.$emit('goalReached', this.getTrackingData());
      } else {
        this.noStreet = true;
      }
      for (const particleName in gameConfig.particles) {
        const label = this.$t(
          `module.information.cleanup.enums.particle.${particleName}`
        );
        const particle = gameConfig.particles[particleName];
        const dataset = this.chartData.datasets.find(
          (ds) => ds.label === label
        );
        if (dataset) {
          const speedFunction = new Function(
            'speed',
            `return ${particle.speedFunction[this.vehicle]}`
          );
          dataset.data = [...dataset.data, speedFunction(this.moveSpeed)];
        }
      }
      this.chartData.labels.push(Math.round(this.moveSpeed).toString());
      this.updateChart();
    }
  }

  getTrackingData(): number[] {
    return this.chartData.labels.map((label) => parseInt(label));
    /*const result: { [key: string]: number[] } = {
      speed: this.chartData.labels.map((label) => parseInt(label)),
    };
    for (const particleName in gameConfig.particles) {
      const label = this.$t(
        `module.information.cleanup.enums.particle.${particleName}`
      );
      const dataset = this.chartData.datasets.find((ds) => ds.label === label);
      if (dataset) {
        result[particleName] = [...dataset.data];
      }
    }
    return result;*/
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
