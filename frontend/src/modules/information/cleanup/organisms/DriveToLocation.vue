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
    <mgl-map :center="mapVehiclePoint" :zoom="mapZoom" @map:load="onLoad">
      <mgl-geo-json-source
        v-if="routeCalculated"
        source-id="routePath"
        :data="routePath"
        :lineMetrics="true"
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
        :coordinates="mapVehiclePoint"
        :rotation="mapDrivingRotation"
        rotation-alignment="map"
        anchor="top"
      >
        <template v-slot:icon>
          <img
            class="divingVehicle"
            :src="`/assets/games/cleanup/vehicle/${vehicleParameter.imageTop}`"
            alt="car"
            :width="20"
          />
        </template>
      </CustomMapMarker>
      <CustomMapMarker anchor="bottom-left" :coordinates="mapEnd">
        <template v-slot:icon>
          <font-awesome-icon icon="flag-checkered" class="pin" />
        </template>
      </CustomMapMarker>
      <CustomMapMarker
        anchor="bottom"
        :rotation="mapDrivingRotation"
        rotation-alignment="map"
        :hide="!noStreet"
        :coordinates="checkRoutePoint"
      >
        <template v-slot:icon>
          <font-awesome-icon :icon="['fac', 'noEntry']" class="noEntry" />
        </template>
      </CustomMapMarker>
    </mgl-map>
    <div class="overlay-top-left">
      <el-progress
        type="dashboard"
        :percentage="(moveSpeed / vehicleParameter.speed) * 100"
        :color="colors"
      >
        <template #default>
          <div>{{ moveSpeed }} km/h</div>
          <div>
            {{ $t('module.information.cleanup.participant.speed') }}
          </div>
        </template>
      </el-progress>
    </div>
    <div v-if="vehicle === 'bus'" class="overlay-top-right">
      <font-awesome-icon icon="users" />
      {{ personCount }} / {{ vehicleParameter.persons }}
    </div>
    <div class="overlay-bottom">
      <Joystick
        :style="{
          margin: '50px',
        }"
        :size="150"
        :stick-size="50"
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
import { LineLayerSpecification, Map } from 'maplibre-gl';
import { FeatureCollection } from 'geojson';
import CustomMapMarker from '@/components/shared/atoms/CustomMapMarker.vue';
import * as formulas from '@/modules/information/cleanup/utils/formulas';
import * as configCalculation from '@/modules/information/cleanup/utils/configCalculation';
import * as turf from '@turf/turf';
import * as turfUtils from '@/utils/turf';
//import * as tiles from `https://api.maptiler.com/tiles/v3/tiles.json?key=${process.env.VUE_APP_MAPTILER_KEY}`;

MglDefaults.style = `https://api.maptiler.com/maps/streets/style.json?key=${process.env.VUE_APP_MAPTILER_KEY}`;

/* eslint-disable @typescript-eslint/no-explicit-any*/
interface BusStop {
  coordinates: [number, number];
  name: string;
  id: number;
  persons: number;
}

export interface TrackingData {
  speed: number;
  combustion: number;
  persons: number;
}

export interface ChartData {
  labels: string[];
  datasets: {
    name: string;
    label: string;
    backgroundColor: string;
    borderColor: string;
    data: number[];
    fill: any;
  }[];
}

const minToleratedAngleDeviation = 22.5;

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
export default class DriveToLocation extends Vue {
  @Prop({ default: 'car' }) readonly vehicle!:
    | 'car'
    | 'bike'
    | 'motorcycle'
    | 'scooter'
    | 'bus';
  @Prop({ default: 'sport' }) readonly vehicleType!: string;
  @Prop({ default: {} }) readonly parameter!: any;
  osrmProfile = 'car';
  mapZoomDefault = 15;
  mapCenter: [number, number] = [0, 0];
  mapStart: [number, number] = [0, 0];
  mapEnd: [number, number] = [0, 0];
  mapDrivingPoint: [number, number] = [0, 0];
  mapVehiclePoint: [number, number] = [0, 0];
  mapDrivingRotation = 0;
  mapZoom = this.mapZoomDefault;
  routeCalculated = false;
  routePath: FeatureCollection = this.getRouteObject([]);
  drivenPath: FeatureCollection = this.getRouteObject([]);
  routeLayout: LineLayerSpecification['layout'] = {
    'line-join': 'round',
    'line-cap': 'round',
  };
  routePaint: any = {
    'line-color': '#0000FF',
    'line-width': 2,
    'line-gradient': [
      'interpolate',
      ['linear'],
      ['line-progress'],
      0,
      'blue',
      0.1,
      'royalblue',
      0.3,
      'cyan',
      0.5,
      'lime',
      0.7,
      'yellow',
      1,
      'red',
    ],
  };
  drivenPaint: LineLayerSpecification['paint'] = {
    'line-color': '#FF0000',
    'line-width': 8,
  };
  busStopList: BusStop[] = [];
  personCount = 1;
  trackingData: TrackingData[] = [];
  animationIndex = 0;
  animationPoints: [number, number][] = [];
  map!: Map;

  isMoving = false;
  moveSpeed = 0;
  moveDirection: [number, number] = [0, 0];
  checkRoutePoint: [number, number] = [0, 0];

  readonly intervalCalculationTime = 100;
  intervalCalculation = -1;
  readonly intervalAnimationTime = 50;
  intervalAnimation = -1;
  readonly busStopIntervalTime = 10000;
  busStopInterval = -1;

  get colors(): { color: string; percentage: number }[] {
    return [
      {
        color: '#01cf9e',
        percentage: (20 / this.vehicleParameter.speed) * 100,
      },
      {
        color: '#f3a40a',
        percentage: (50 / this.vehicleParameter.speed) * 100,
      },
      {
        color: '#fe6e5d',
        percentage: (100 / this.vehicleParameter.speed) * 100,
      },
    ];
  }

  chartData: ChartData = {
    labels: [],
    datasets: [],
  };

  get vehicleParameter(): any {
    return configCalculation.getVehicleParameter(
      this.vehicle,
      this.vehicleType
    );
  }

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

  streetLayers: string[] = [];
  onLoad(e: MglEvent): void {
    const map = e.map;
    this.map = map;
    map.scrollZoom.disable();
    const notNeededLayers = map.getStyle().layers.filter((layer) => {
      const layerCategory = layer['source-layer'];
      const layerType = layer['type'];
      if (layerCategory) {
        return layerType === 'symbol' && layerCategory !== 'place';
      }
      return false;
    });
    this.streetLayers = map
      .getStyle()
      .layers.filter((layer) => {
        return (
          layer.id.includes('path') &&
          layer.type === 'line' &&
          !notNeededLayers.includes(layer)
        );
      })
      .map((layer) => layer.id)
      .filter((value, index, array) => array.indexOf(value) === index);
    if (this.vehicle === 'bus') {
      const layer = notNeededLayers.find((layer) => layer.id === 'poi_z14');
      const busLayerName = 'bus';
      if (layer) {
        const busLayer = Object.assign({}, layer);
        busLayer.layout = {
          visibility: 'visible',
          'icon-image': '{class}_11',
          'icon-size': 0.9,
        };
        busLayer.id = busLayerName;
        (busLayer as any).filter = [
          'all',
          ['==', '$type', 'Point'],
          ['==', 'class', 'bus'],
        ];
        busLayer.minzoom = 0;
        busLayer['source-layer'] = 'poi';
        map.addLayer(busLayer);
      }
      setTimeout(() => {
        this.updateVisibleBusStops();
      }, 1000);

      for (const layer of notNeededLayers) {
        map.removeLayer(layer.id);
      }
    } else {
      for (const layer of notNeededLayers) {
        map.removeLayer(layer.id);
      }
    }
  }

  updateVisibleBusStops(): void {
    const busLayerName = 'bus';
    const canvas = this.map.getCanvas();
    const features = this.map.queryRenderedFeatures(
      [
        [0, 0],
        [canvas.width, canvas.height],
      ],
      { layers: [busLayerName] }
    );
    this.busStopList = features
      .filter((f) => f.properties.class === 'bus')
      .map((f) => {
        return {
          coordinates: (f.geometry as any).coordinates,
          name: f.properties.name,
          id: f.id,
          persons: Math.ceil(Math.random() * 10),
        } as BusStop;
      });
  }

  checkPixelPosition(): boolean {
    if (this.map) {
      const searchArea = 6;
      const features = this.map.queryRenderedFeatures(
        [
          [
            this.vehicleScreenPoint[0] - searchArea / 2,
            this.vehicleScreenPoint[1] - searchArea / 2,
          ],
          [
            this.vehicleScreenPoint[0] + searchArea / 2,
            this.vehicleScreenPoint[1] + searchArea / 2,
          ],
        ],
        { layers: this.streetLayers }
      );
      const roadList = features.filter((f) => f.properties.subclass === 'path');
      console.log(roadList);
      return roadList.length > 0;
    }
    return false;
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
    this.intervalAnimation = setInterval(
      this.animateVehicle,
      this.intervalAnimationTime
    );
    if (this.vehicle === 'bus') {
      this.personCount = Math.ceil(
        Math.random() * (this.vehicleParameter.persons / 3) + 1
      );
      this.busStopInterval = setInterval(
        this.updateVisibleBusStops,
        this.busStopIntervalTime
      );
    }
    for (const particleName in gameConfig.particles) {
      const particle = gameConfig.particles[particleName];
      const data = {
        name: particleName,
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
    clearInterval(this.intervalCalculation);
    clearInterval(this.intervalAnimation);
    clearInterval(this.busStopInterval);
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
    this.setMapDrivingPoint([...this.mapStart], true);

    (this.routePath.features[0].geometry as any).coordinates = [
      this.mapStart,
      this.mapEnd,
    ];

    this.calculateRoute(this.mapStart, this.mapEnd).then(() => {
      const pathPoints = (this.routePath.features[0].geometry as any)
        .coordinates;
      this.setMapDrivingPoint(pathPoints[0], true);
      this.mapDrivingRotation = turf.bearing(
        turf.point(this.mapDrivingPoint),
        turf.point(pathPoints[1])
      );
      this.routeCalculated = true;
    });
  }

  vehicleScreenPoint: [number, number] = [0, 0];
  setMapDrivingPoint(
    coordinates: [number, number],
    syncVehiclePoint = false
  ): void {
    this.mapDrivingPoint = coordinates;
    if (syncVehiclePoint) this.mapVehiclePoint = coordinates;
  }

  async calculateRoute(
    start: [number, number],
    end: [number, number],
    checkDistance = false
  ): Promise<boolean> {
    const osrm = new OSRM(this.osrmProfile, {
      userAgent: '',
    });
    const drivenPathCoordinates = (this.drivenPath.features[0].geometry as any)
      .coordinates;
    const wayPoints: [number, number][] = [];
    if (drivenPathCoordinates.length > 0) {
      const last = drivenPathCoordinates[drivenPathCoordinates.length - 1];
      wayPoints.push([last[1], last[0]]);
    }
    wayPoints.push([start[1], start[0]]);
    wayPoints.push([end[1], end[0]]);
    const path = await osrm.directions(wayPoints, this.osrmProfile);
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
      return true;
    }
    return false;
  }

  async isOnPossibleRoute(
    point: [number, number]
  ): Promise<{ distance: number; location: [number, number] }> {
    const osrm = new OSRM(this.osrmProfile, {
      userAgent: '',
    });
    this.checkRoutePoint = point;
    const nearest = await osrm.nearest([point[1], point[0]], this.osrmProfile);
    const path = nearest.waypoints[0];
    return {
      distance: path.distance,
      location: [path.location[0], path.location[1]],
    };
  }

  start(event: any): void {
    this.move(event);
    this.isMoving = true;
    this.intervalCalculation = setInterval(
      this.updateTrace,
      this.intervalCalculationTime
    );
    /*this.intervalAnimation = setInterval(
      this.animateVehicle,
      this.intervalAnimationTime
    );*/
  }

  stop(): void {
    clearInterval(this.intervalCalculation);
    //clearInterval(this.intervalAnimation);
    this.isMoving = false;
    this.moveSpeed = 0;

    if (this.vehicle === 'bus') {
      for (const busStop of this.busStopList) {
        const distance = turf.distance(
          turf.point(this.mapDrivingPoint),
          turf.point(busStop.coordinates)
        );
        if (distance < 0.05) {
          let addCount = busStop.persons;
          if (addCount > this.vehicleParameter.persons - this.personCount)
            addCount = this.vehicleParameter.persons - this.personCount;
          this.personCount += addCount;
          busStop.persons -= addCount;
        }
      }
    }
  }

  move(event: any): void {
    this.moveSpeed = (event.distance / 100.0) * this.vehicleParameter.speed;
    this.moveDirection = [event.x, event.y];
    this.noStreet = false;
  }

  getRoute(): turf.Feature<turf.LineString> | turf.LineString {
    return turfUtils.getRoute(this.routePath);
  }

  getDrivenRoute(): turf.Feature<turf.LineString> | turf.LineString {
    return turfUtils.getRoute(this.drivenPath);
  }

  getDrivingDistance(): number {
    const drivingTime = (this.intervalCalculationTime / (1000 * 3600)) * 10;
    return this.moveSpeed * drivingTime;
  }

  getNewDrivingPoint(distance: number): [number, number] {
    return turfUtils.getDestination(
      this.mapDrivingPoint,
      distance,
      this.moveDirection
    );
  }

  goalReached(): boolean {
    return turfUtils.goalReached(this.routePath, this.mapDrivingPoint);
  }

  async recalculateRoute(
    newDrivingPoint: [number, number]
  ): Promise<[number, number] | null> {
    const minSearchRouteDistance = 0.01;
    const possibleRoadPoint = this.getNewDrivingPoint(minSearchRouteDistance);
    const roadPoint = await this.isOnPossibleRoute(possibleRoadPoint);
    const angleDeviation = turfUtils.getAngleDeviation(
      this.mapDrivingPoint,
      possibleRoadPoint,
      roadPoint.location
    );
    const isParallel = angleDeviation < 0.01 && roadPoint.distance > 1;
    if (
      !isParallel &&
      roadPoint.distance < 3 &&
      angleDeviation < minToleratedAngleDeviation
    ) {
      await this.calculateRoute(possibleRoadPoint, this.mapEnd);
      return turfUtils.getNearestPointOnRoute(this.routePath, newDrivingPoint);
    } else {
      this.noStreet = true;
      return null;
    }
  }

  addDrivingDataToChart(newDrivingPoint: [number, number]): void {
    const distance = turf.distance(
      turf.point(this.mapDrivingPoint),
      turf.point(newDrivingPoint)
    );
    const vehicleParameter = this.vehicleParameter;
    let combustion = 0;
    combustion = formulas.acceleration(
      this.moveSpeed,
      distance,
      vehicleParameter
    );
    const trackingData: TrackingData = {
      speed: this.moveSpeed,
      persons: this.personCount,
      combustion: combustion,
    };
    this.trackingData.push(trackingData);
    configCalculation.addValueToStatistics(
      trackingData,
      this.vehicleParameter,
      this.chartData
    );
    this.updateChart();
  }

  updateDrivingPoint(newDrivingPoint: [number, number]): void {
    this.addAnimationSteps(newDrivingPoint);
    this.setMapDrivingPoint(newDrivingPoint);
    if (this.goalReached()) {
      this.$emit('goalReached', this.trackingData);
      clearInterval(this.intervalCalculation);
      //clearInterval(this.intervalAnimation);
      clearInterval(this.busStopInterval);
    }
  }

  updateDrivingPath(newDrivingPoint: [number, number]): void {
    const coordinates = (this.drivenPath.features[0].geometry as any)
      .coordinates;
    coordinates.push(newDrivingPoint);
    this.drivenPath = this.getRouteObject(coordinates);
  }

  get animationIntermediateSteps(): number {
    return Math.floor(
      this.intervalCalculationTime / this.intervalAnimationTime
    );
  }

  get openAnimationSteps(): number {
    return this.animationPoints.length - this.animationIndex;
  }

  addAnimationSteps(newDrivingPoint: [number, number]): void {
    const speedDrivingDistance = this.getDrivingDistance();
    const intermediateSteps = this.animationIntermediateSteps;
    let subRoute = turfUtils.getSubRoute(
      this.routePath,
      this.mapDrivingPoint,
      newDrivingPoint
    );
    let distance = turf.length(subRoute as any);
    if (distance > speedDrivingDistance) {
      subRoute = turf.lineString([this.mapDrivingPoint, newDrivingPoint]);
      distance = turf.distance(
        turf.point(this.mapDrivingPoint),
        turf.point(newDrivingPoint)
      );
    }
    for (let i = 0; i < intermediateSteps; i++) {
      const point = turf.along(
        subRoute,
        (distance / intermediateSteps) * (i + 1)
      );
      this.animationPoints.push([
        point.geometry.coordinates[0],
        point.geometry.coordinates[1],
      ]);
    }
  }

  noStreet = false;
  updateTraceIsRunning = false;
  async updateTrace(): Promise<void> {
    if (this.updateTraceIsRunning) return;
    if (this.openAnimationSteps > this.animationIntermediateSteps / 2) return;
    this.updateTraceIsRunning = true;

    const speedDrivingDistance = this.getDrivingDistance();

    if (this.isMoving && this.moveSpeed) {
      let newDrivingPoint = this.getNewDrivingPoint(speedDrivingDistance);
      this.mapDrivingRotation = turfUtils.getRotation(
        this.mapDrivingPoint,
        newDrivingPoint
      );
      let isOnRoute = !this.noStreet;
      if (isOnRoute) {
        const pointOnLine = turfUtils.moveAlongPath(
          this.routePath,
          this.mapDrivingPoint,
          speedDrivingDistance
        );
        const angleDeviation = turfUtils.getAngleDeviation(
          this.mapDrivingPoint,
          newDrivingPoint,
          pointOnLine
        );
        if (angleDeviation < minToleratedAngleDeviation) {
          newDrivingPoint = pointOnLine;
        } else {
          let recalculateRoute = true;
          if (
            turfUtils.isPointCloseToRoute(
              this.routePath,
              newDrivingPoint,
              0.003
            )
          ) {
            const snapped = turfUtils.getNearestPointOnRoute(
              this.routePath,
              newDrivingPoint
            );
            const distance = turf.distance(this.mapDrivingPoint, snapped);
            if (distance > speedDrivingDistance / 2) {
              newDrivingPoint = snapped;
              recalculateRoute = false;
            }
          }

          if (recalculateRoute) {
            const point = await this.recalculateRoute(newDrivingPoint);
            if (point) newDrivingPoint = point;
            else isOnRoute = false;
          }
        }
      }
      this.addDrivingDataToChart(newDrivingPoint);
      if (isOnRoute) {
        this.updateDrivingPoint(newDrivingPoint);
      }
    }
    this.updateTraceIsRunning = false;
  }

  animateVehicle(): void {
    if (this.animationIndex < this.animationPoints.length) {
      this.mapVehiclePoint = this.animationPoints[this.animationIndex];
      this.updateDrivingPath(this.mapVehiclePoint);
      this.animationIndex++;
    }
  }
}
</script>

<style lang="scss" scoped>
.overlay-top-left {
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
.overlay-top-right {
  position: absolute;
  z-index: 100;
  top: 0.5rem;
  right: 0.5rem;
  font-size: var(--font-size-xxxlarge);
  color: var(--pin-color);
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

.noEntry {
  font-size: var(--font-size-xlarge);
  color: var(--color-red);
}

.pin-small {
  --pin-color: var(--color-primary);
  font-size: var(--font-size-small);
  color: var(--pin-color);
}

.info-icon {
  background-color: #ffffffcc;
  border-radius: 0.1rem;
  padding: 0.2rem;
  --pin-color: var(--color-primary);
  font-size: var(--font-size-small);
  color: var(--pin-color);
}

.divingVehicle {
  border: var(--color-red) solid 2px;
}
</style>
