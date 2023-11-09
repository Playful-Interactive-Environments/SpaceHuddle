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
          <div
            class="personContainer"
            :style="{ '--persons': boardingPersons }"
          >
            <img
              class="divingVehicle"
              :src="`/assets/games/moveit/vehicle/${vehicleParameter.imageTop}`"
              alt="car"
              :width="30"
            />
            <font-awesome-icon
              v-if="boardingPersons > 0"
              icon="user"
              class="person"
            />
            <div class="addCount" v-if="boardingPersons > 0">
              +{{ boardingPersons }}
            </div>
          </div>
        </template>
      </CustomMapMarker>
      <CustomMapMarker :coordinates="mapVehiclePoint" anchor="center">
        <template v-slot:icon>
          <Joystick
            v-if="navigation === NavigationType.drag"
            :size="150"
            :stick-size="15"
            @move="move($event)"
            @start="start"
            @stop="stop"
            @mousedown="disableMapPan"
            v-on:touchstart="disableMapPan"
            stickColor="white"
            :base-color="getSpeedColor(moveSpeed, 0.3)"
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
        :interactive="false"
      >
        <template v-slot:icon>
          <font-awesome-icon :icon="['fac', 'noEntry']" class="noEntry" />
        </template>
      </CustomMapMarker>
    </mgl-map>
    <div class="overlay-top-left">
      <round-slider
        v-model="maxSpeed"
        :max="vehicleParameter.speed"
        circleShape="full"
        start-angle="315"
        end-angle="+270"
        line-cap="round"
        radius="80"
        width="10"
        handleShape="dot"
        :show-tooltip="false"
        pathColor="rgba(0, 0, 0, 0.5)"
        :rangeColor="getSpeedColor(maxSpeed)"
        handle-size="20"
      />
      <el-progress
        type="dashboard"
        :percentage="convertSpeedToColorPercentage(moveSpeed)"
        :color="getSpeedColor(moveSpeed)"
      >
        <template #default>
          <div>{{ Math.round(moveSpeed) }} km/h</div>
          <div>
            {{ $t('module.playing.moveit.participant.maxSpeed') }}
            {{ Math.round(maxSpeed) }} km/h
          </div>
        </template>
      </el-progress>
    </div>
    <div v-if="vehicle.category === 'bus'" class="overlay-top-right">
      <font-awesome-icon icon="users" />
      {{ personCount }} / {{ vehicleParameter.persons }}
    </div>
    <div class="overlay-bottom-right">
      <div>
        <Joystick
          v-if="navigation === NavigationType.joystick"
          :size="150"
          :stick-size="50"
          @move="move($event)"
          @start="start"
          @stop="stop"
          stickColor="white"
          :base-color="getSpeedColor(moveSpeed, 0.3)"
        />
        <!--<el-button @click="createVisibleStreetMask">test</el-button>-->
      </div>
    </div>
    <!--<div class="overlay-bottom-left">
      <el-switch
        v-model="combinedJoystick"
        class="ml-2"
        inline-prompt
        style="
          --el-switch-on-color: var(--color-brainstorming);
          --el-switch-off-color: var(--color-evaluation);
        "
        :active-text="$t('module.playing.moveit.participant.combined')"
        :inactive-text="$t('module.playing.moveit.participant.separate')"
      />
    </div>-->
    <div class="overlay-bottom-left">
      <el-button
        class="overlay-top-right"
        link
        v-if="showMiniMap"
        @click="showMiniMap = false"
      >
        <font-awesome-icon icon="xmark" />
      </el-button>
      <el-button v-if="!showMiniMap" @click="showMiniMap = true">
        <font-awesome-icon icon="map" />
      </el-button>
      <mgl-map
        :style="{ display: showMiniMap ? 'block' : 'none' }"
        width="15rem"
        height="10rem"
        @map:load="miniMapLoaded"
        class="miniMap"
      >
        <CustomMapMarker anchor="bottom" :coordinates="mapDrivingPoint">
          <template v-slot:icon>
            <font-awesome-icon icon="location-dot" class="pin" />
          </template>
        </CustomMapMarker>
        <CustomMapMarker anchor="bottom-left" :coordinates="mapEnd">
          <template v-slot:icon>
            <font-awesome-icon icon="flag-checkered" class="pin" />
          </template>
        </CustomMapMarker>
      </mgl-map>
    </div>
  </div>
  <!--<img v-if="streetMask" :src="streetMask" class="streetMask" />-->
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import Joystick from 'vue-joystick-component';
import { Line } from 'vue-chartjs';
import * as gameConfig from '@/modules/playing/moveit/data/gameConfig.json';
import * as osrmUtils from '@/modules/playing/moveit/utils/osrm';
import {
  MglEvent,
  MglGeoJsonSource,
  MglLineLayer,
  MglMap,
  MglMarker,
  MglNavigationControl,
} from 'vue-maplibre-gl';
import {
  LineLayerSpecification,
  LngLatBoundsLike,
  LngLatLike,
  Map,
} from 'maplibre-gl';
import { FeatureCollection } from 'geojson';
import CustomMapMarker from '@/components/shared/atoms/CustomMapMarker.vue';
import * as formulas from '@/modules/playing/moveit/utils/formulas';
import * as configCalculation from '@/modules/playing/moveit/utils/configCalculation';
import * as turf from '@turf/turf';
import * as turfUtils from '@/utils/turf';
import { Application } from 'vue3-pixi';
import RoundSlider from 'vue-three-round-slider/src';
import Color from 'colorjs.io';
import * as mapStyle from '@/utils/mapStyle';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import * as themeColors from '@/utils/themeColors';
import * as vehicleCalculation from '@/modules/playing/moveit/types/Vehicle';
import {
  MovingType,
  NavigationType,
} from '@/modules/playing/moveit/organisms/SelectChallenge.vue';
import * as mapUtils from '@/modules/playing/moveit/utils/map';

mapStyle.setMapStyleStreets();

/* eslint-disable @typescript-eslint/no-explicit-any*/
interface BusStop {
  coordinates: [number, number];
  name: string;
  id: number;
  persons: number;
}

export interface TrackingData {
  speed: number;
  consumption: number;
  persons: number;
  distance: number;
  tireWareRate: number;
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

interface ColorProp {
  color: string;
  percentage: number;
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
    Application,
    RoundSlider,
  },
  emits: ['update:useFullSize', 'goalReached'],
})
export default class DriveToLocation extends Vue {
  //#region prop
  @Prop() readonly trackingManager!: TrackingManager;
  @Prop({ default: { category: 'car', type: 'sport' } })
  readonly vehicle!: vehicleCalculation.Vehicle;
  @Prop({ default: {} }) readonly parameter!: any;
  @Prop({ default: NavigationType.joystick })
  readonly navigation!: NavigationType;
  @Prop({ default: MovingType.free })
  readonly movingType!: MovingType;
  //#endregion prop

  //#region variables
  showMiniMap = true;
  mapZoomDefault = 15;
  mapCenter: [number, number] = [0, 0];
  mapStart: [number, number] = [0, 0];
  mapEnd: [number, number] = [0, 0];
  mapDrivingPoint: [number, number] = [0, 0];
  mapVehiclePoint: [number, number] = [0, 0];
  //mapDrivingRotation = 0;
  mapZoom = this.mapZoomDefault;
  readonly maxDrivingDistance = 2;
  routeCalculated = false;
  routePath: FeatureCollection = turfUtils.getRouteObject([]);
  drivenPath: FeatureCollection = turfUtils.getRouteObject([]);
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
  animationPoints: [number, number][][] = [];
  map!: Map;
  ready = false;
  gameWidth = 0;
  controlHeight = 0;

  //direction = 0;
  speed = 0;
  maxSpeed = 0;

  isMoving = false;
  moveSpeed = 0;
  moveDirection: [number, number] = [0, 0];
  moveAngle = 0;
  checkRoutePoint: [number, number] = [0, 0];

  boardingPersons = 0;

  readonly intervalCalculationTime = 50;
  intervalCalculation = -1;
  readonly intervalAnimationTime = 50;
  intervalAnimation = -1;
  readonly busStopIntervalTime = 10000;
  busStopInterval = -1;
  NavigationType = NavigationType;

  chartData: ChartData = {
    labels: [],
    datasets: [],
  };
  //#endregion variables

  //#region get / set
  convertSpeedToColorPercentage(speed: number): number {
    return (speed / this.vehicleParameter.speed) * 100;
  }

  get highlightColorTransparent(): string {
    return themeColors.convertToRGBA(themeColors.getHighlightColor(), 0.25);
  }

  get highlightColor(): string {
    return themeColors.convertToRGBA(themeColors.getHighlightColor());
  }

  get backgroundColor(): string {
    return themeColors.getBackgroundColor();
  }

  get colors(): ColorProp[] {
    return [
      {
        color: themeColors.getGreenColor(),
        percentage: this.convertSpeedToColorPercentage(20),
      },
      {
        color: themeColors.getYellowColor(),
        percentage: this.convertSpeedToColorPercentage(50),
      },
      {
        color: themeColors.getRedColor(),
        percentage: this.convertSpeedToColorPercentage(100),
      },
    ];
  }

  getSpeedColor(speed: number, transparent = 1): string {
    const convertColor = (colorProp: ColorProp): Color => {
      return new Color(colorProp.color);
    };
    const percentage = this.convertSpeedToColorPercentage(speed);
    const colorPropList = this.colors;
    const colorMixList: [ColorProp, ColorProp] = [
      colorPropList[0],
      colorPropList[colorPropList.length - 1],
    ];
    for (const colorProp of colorPropList) {
      if (colorProp.percentage >= percentage) {
        colorMixList[1] = colorProp;
        break;
      }
      if (colorProp.percentage <= percentage) {
        colorMixList[0] = colorProp;
      }
    }
    let resultColor = new Color(colorMixList[0].color);
    if (colorMixList[0].percentage >= percentage)
      resultColor = new Color(colorMixList[0].color);
    else if (colorMixList[1].percentage <= percentage)
      resultColor = new Color(colorMixList[1].color);
    else {
      const range = colorMixList[1].percentage - colorMixList[0].percentage;
      if (range === 0) resultColor = new Color(colorMixList[0].color);
      else {
        resultColor = convertColor(colorMixList[0]).mix(
          convertColor(colorMixList[1]),
          (percentage - colorMixList[0].percentage) * (1 / range),
          { space: 'lch', outputSpace: 'srgb' }
        ) as any;
      }
    }
    resultColor.alpha = transparent;
    return resultColor.toString();
  }

  get vehicleParameter(): any {
    return configCalculation.getVehicleParameter(this.vehicle);
  }

  get mapDrivingRotation(): number {
    if (this.moveAngle < 0) return this.moveAngle + 360;
    return this.moveAngle;
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
    return turfUtils.getDestinationFromAngle(
      this.mapDrivingPoint,
      distance,
      this.moveAngle
    );
  }

  testPoints: [number, number][] = [];
  isDrivingAngleInsideNextSegment(
    routePath: FeatureCollection,
    distance: number,
    airlinePoint: [number, number],
    allowedCornerDistance = 0.001
  ): {
    value: boolean;
    endPoint: [number, number];
    corner: [number, number] | null;
    subPath: [number, number][];
  } {
    const minMax = turfUtils.getMinMaxAngleForPathDistanceSegment(
      routePath,
      this.mapDrivingPoint,
      airlinePoint,
      distance,
      minToleratedAngleDeviation,
      allowedCornerDistance
    );
    /*this.testPoints = [
      ...minMax.subPath,
      airlinePoint,
      ...minMax.checkPoints,
      [...this.mapDrivingPoint],
    ];
    console.log(minMax, this.moveAngle);*/
    if (this.moveAngle >= minMax.min && this.moveAngle <= minMax.max)
      return {
        value: true,
        endPoint: minMax.endPoint,
        corner: minMax.corner,
        subPath: minMax.subPath,
      };
    if (
      this.moveAngle - 360 >= minMax.min &&
      this.moveAngle - 360 <= minMax.max
    )
      return {
        value: true,
        endPoint: minMax.endPoint,
        corner: minMax.corner,
        subPath: minMax.subPath,
      };
    if (
      this.moveAngle + 360 >= minMax.min &&
      this.moveAngle + 360 <= minMax.max
    )
      return {
        value: true,
        endPoint: minMax.endPoint,
        corner: minMax.corner,
        subPath: minMax.subPath,
      };
    return {
      value: false,
      endPoint: minMax.endPoint,
      corner: minMax.corner,
      subPath: minMax.subPath,
    };
  }

  vehicleScreenPoint: [number, number] = [0, 0];
  setMapDrivingPoint(
    coordinates: [number, number],
    syncVehiclePoint = false
  ): void {
    this.mapDrivingPoint = coordinates;
    if (syncVehiclePoint) this.mapVehiclePoint = coordinates;
  }
  //#endregion get / set

  //#region load / unload
  streetLayers: string[] = [];
  onLoad(e: MglEvent): void {
    const map = e.map;
    this.map = map;
    const notNeededLayers = mapUtils.getNotNeededLayers(map);
    this.streetLayers = mapUtils.getStreetLayers(map);
    if (this.vehicle.category === 'bus') {
      const layer = notNeededLayers.find(
        (layer) =>
          layer.id === 'poi-level-1' ||
          layer.id === 'poi-level-bus' ||
          layer.id === 'poi_z14'
      );
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
        map.setMinZoom(busLayer.minzoom);
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

    const drivingBound = this.map.getBounds();

    this.showEntireRoute();
    setTimeout(() => {
      this.map.fitBounds(drivingBound, {
        duration: 1000,
        animate: true,
        essential: true,
      });
      /*setTimeout(() => {
        this.createVisibleStreetMask();
      }, 2000);*/
    }, 3000);
  }

  miniMap!: Map;
  miniMapLoaded(e: MglEvent): void {
    this.miniMap = e.map;
    //console.log(this.miniMap);
    this.updateMiniMap();
  }

  showEntireRoute(): void {
    const convertCoordinates = (position: [number, number]): LngLatLike => {
      return {
        lng: position[0],
        lat: position[1],
      };
    };

    const points = turf.points([this.mapStart, this.mapEnd]);

    const bounds = turf.envelope(points).geometry.coordinates[0] as [
      number,
      number
    ][];
    const min = bounds[0];
    const max = bounds[2];
    const delta = 0.002;
    const minLngLat = convertCoordinates([min[0] - delta, min[1] - delta]);
    const maxLngLat = convertCoordinates([max[0] + delta, max[1] + delta]);
    const mapBounds: LngLatBoundsLike = [minLngLat, maxLngLat];
    if (this.map && mapBounds) {
      this.map.fitBounds(mapBounds, {
        duration: 0,
        animate: false,
        essential: true,
      });
    }
  }

  updateMiniMap(): void {
    if (this.miniMap && this.mapDrivingPoint) {
      const convertCoordinates = (position: [number, number]): LngLatLike => {
        return {
          lng: position[0],
          lat: position[1],
        };
      };

      const points = turf.points([this.mapDrivingPoint, this.mapEnd]);

      const bounds = turf.envelope(points).geometry.coordinates[0] as [
        number,
        number
      ][];
      const min = bounds[0];
      const max = bounds[2];
      const width = max[0] - min[0];
      const height = max[1] - min[1];
      const delta = Math.max(width, height) * 0.4;
      const minLngLat = convertCoordinates([
        min[0] - delta / 2,
        min[1] - 0.001,
      ]);
      const maxLngLat = convertCoordinates([
        max[0] + delta / 2,
        max[1] + delta,
      ]);
      const mapBounds: LngLatBoundsLike = [minLngLat, maxLngLat];
      if (mapBounds) {
        this.miniMap.fitBounds(mapBounds, {
          duration: 0,
          animate: false,
          essential: true,
        });
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

  /*streetMask = '';
  createVisibleStreetMask(): void {
    this.streetMask = mapUtils.calculateStreetMask(this.map, this.streetLayers);
  }*/

  initTrackingData(): void {
    if (
      this.trackingManager &&
      this.trackingManager.iterationStep &&
      !this.trackingManager.iterationStep.parameter.drive
    ) {
      this.trackingManager.saveIterationStep({
        drive: { stops: 0, persons: this.personCount },
      });
    }
  }

  async mounted(): Promise<void> {
    this.gameWidth = this.$el.parentElement.offsetWidth;
    if (this.$refs.controlArea)
      this.controlHeight = (this.$refs.controlArea as HTMLElement).offsetHeight;
    this.ready = true;
    this.maxSpeed = this.vehicleParameter.speed;

    if (this.animationIntermediateSteps > 1) {
      this.intervalAnimation = setInterval(
        this.animateVehicle,
        this.intervalAnimationTime
      );
    }
    if (this.vehicle.category === 'bus') {
      this.personCount = Math.ceil(
        Math.random() * (this.vehicleParameter.persons / 3) + 1
      );
      this.busStopInterval = setInterval(
        this.updateVisibleBusStops,
        this.busStopIntervalTime
      );
    }
    this.initTrackingData();
    for (const particleName in gameConfig.particles) {
      const particle = gameConfig.particles[particleName];
      const data = {
        name: particleName,
        label: this.$t(`module.playing.moveit.enums.particle.${particleName}`),
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

    window.addEventListener('mouseup', this.enableMapPan);
    window.addEventListener('touchend', this.enableMapPan);
  }

  unmounted(): void {
    clearInterval(this.intervalCalculation);
    clearInterval(this.intervalAnimation);
    clearInterval(this.busStopInterval);
    window.removeEventListener('mouseup', this.enableMapPan);
    window.removeEventListener('touchend', this.enableMapPan);
  }
  //#endregion load / unload

  //#region navigation
  start(event: any): void {
    this.disableMapPan();
    this.boardingPersons = 0;
    this.move(event);
    this.isMoving = true;
    this.intervalCalculation = setInterval(
      this.updateTrace,
      this.intervalCalculationTime
    );
    this.noStreet = false;
  }

  disableMapPan(): void {
    if (this.map) {
      this.map.scrollZoom.disable();
      this.map.dragPan.disable();
      this.map.dragRotate.disable();
    }
  }

  enableMapPan(): void {
    if (this.map) {
      this.map.scrollZoom.enable();
      this.map.dragPan.enable();
      this.map.dragRotate.enable();
    }
  }

  stop(): void {
    this.enableMapPan();
    clearInterval(this.intervalCalculation);
    this.isMoving = false;
    this.moveSpeed = 0;

    if (this.vehicle.category === 'bus') {
      for (const busStop of this.busStopList) {
        const distance = turf.distance(
          turf.point(this.mapDrivingPoint),
          turf.point(busStop.coordinates)
        );
        if (distance < 0.05) {
          let addCount = busStop.persons;
          if (addCount > this.vehicleParameter.persons - this.personCount)
            addCount = this.vehicleParameter.persons - this.personCount;
          this.boardingPersons = addCount;
          this.personCount += addCount;
          busStop.persons -= addCount;
        }
      }
    }

    this.initTrackingData();
    if (this.trackingManager && this.trackingManager.iterationStep) {
      this.trackingManager.iterationStep.parameter.drive.persons =
        this.personCount;
      this.trackingManager.iterationStep.parameter.drive.stops++;
      this.trackingManager.saveIterationStep();
    }
  }

  move(event: any): void {
    if (event.distance === undefined) return;
    const calcAngle = (point: [number, number]): number => {
      return Math.atan2(point[0], point[1]) * (180 / Math.PI);
    };

    this.moveSpeed = (event.distance / 100.0) * this.maxSpeed;
    this.moveAngle = calcAngle([event.x, event.y]);
    this.moveDirection = [event.x, event.y];
    this.noStreet = false;
  }
  //#endregion navigation

  //#region watcher
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
    if (this.mapEnd[0] === 0 && this.mapEnd[1] === 0) {
      const destination = turf.destination(
        this.mapStart,
        this.maxDrivingDistance,
        Math.random() * 360 - 180
      );
      this.mapEnd = [
        destination.geometry.coordinates[0],
        destination.geometry.coordinates[1],
      ];
    }
    this.setMapDrivingPoint([...this.mapStart], true);

    (this.routePath.features[0].geometry as any).coordinates = [
      this.mapStart,
      this.mapEnd,
    ];

    const intermediateGoals: [number, number][] = [];
    this.calculateRoute(
      this.mapStart,
      this.mapEnd,
      false,
      intermediateGoals
    ).then(() => {
      let pathPoints = (this.routePath.features[0].geometry as any)
        .coordinates as [number, number][];
      const line = turf.lineString(pathPoints);
      const destination = turf.along(line, this.maxDrivingDistance);
      this.mapEnd = [
        destination.geometry.coordinates[0],
        destination.geometry.coordinates[1],
      ];
      pathPoints = turf.lineSliceAlong(line, 0, this.maxDrivingDistance)
        .geometry.coordinates as [number, number][];
      this.routePath = turfUtils.getRouteObject(pathPoints);
      this.setMapDrivingPoint(pathPoints[0], true);
      this.moveAngle = turfUtils.getRotation(
        this.mapDrivingPoint,
        pathPoints[1]
      );
      this.routeCalculated = true;

      this.initTrackingData();
      if (this.trackingManager && this.trackingManager.iterationStep) {
        this.trackingManager.iterationStep.parameter.drive.routePath =
          this.routePath;
        this.trackingManager.iterationStep.parameter.drive.routePathLenght =
          turf.length(this.routePath);
        this.trackingManager.iterationStep.parameter.drive.pathCalculationCount = 1;
        this.trackingManager.saveIterationStep();
      }
    });
  }

  @Watch('moveAngle', { immediate: true })
  onMoveAngleChanged(): void {
    this.noStreet = false;
  }

  /*@Watch('direction', { immediate: true })
  onDirectionChanged(): void {
    this.moveAngle = this.direction;
    this.noStreet = false;
    this.mapDrivingRotation = this.moveAngle;
  }

  @Watch('mapDrivingRotation', { immediate: true })
  onMapDrivingRotationChanged(): void {
    let direction = this.mapDrivingRotation;
    if (this.mapDrivingRotation < 0) direction = this.mapDrivingRotation + 360;
    if (direction !== this.direction) this.direction = direction;
  }*/

  @Watch('speed', { immediate: true })
  onSpeedChanged(): void {
    if (this.moveSpeed === 0 && this.speed > 0) {
      this.isMoving = true;
      this.intervalCalculation = setInterval(
        this.updateTrace,
        this.intervalCalculationTime
      );
    } else if (this.moveSpeed > 0 && this.speed === 0) {
      this.stop();
    }
    this.moveSpeed = this.speed;
  }

  @Watch('moveSpeed', { immediate: true })
  onMoveSpeedChanged(): void {
    if (this.speed !== this.moveSpeed) this.speed = this.moveSpeed;
  }

  @Watch('mapDrivingPoint', { immediate: true })
  onMapDrivingPointChanged(): void {
    this.updateMiniMap();
  }
  //#endregion watcher

  //#region route
  async isOnPossibleRoute(point: [number, number]): Promise<{
    distance: number;
    location: [number, number];
    delta: number;
    pathDelta: number;
    isOnRoutePath: boolean;
  }> {
    this.checkRoutePoint = point;
    return osrmUtils.isOnPossibleRoute(
      this.mapDrivingPoint,
      point,
      this.routePath
    );
  }

  goalReached(maxGoalDistance = 0.001): boolean {
    return turfUtils.goalReached(
      this.routePath,
      this.mapDrivingPoint,
      maxGoalDistance
    );
  }

  async calculateRoute(
    start: [number, number],
    end: [number, number],
    checkDistance = false,
    intermediateGoals: [number, number][] = []
  ): Promise<boolean> {
    const routePath = await osrmUtils.calculateRoute(
      this.drivenPath,
      start,
      end,
      checkDistance,
      intermediateGoals
    );
    if (routePath) {
      this.routePath = routePath;
      return true;
    }
    return false;
  }

  async recalculateRoute(
    newDrivingPoint: [number, number]
  ): Promise<[number, number] | null> {
    const minSearchRouteDistance = 0.01;
    const possibleRoadPoint = this.getNewDrivingPoint(minSearchRouteDistance);
    const roadPoint = await this.isOnPossibleRoute(possibleRoadPoint);

    if (
      !roadPoint.isOnRoutePath &&
      roadPoint.pathDelta < minToleratedAngleDeviation &&
      roadPoint.distance < 10 &&
      roadPoint.delta < minToleratedAngleDeviation
    ) {
      await this.calculateRoute(possibleRoadPoint, this.mapEnd);
      return turfUtils.getNearestPointOnRoute(this.routePath, newDrivingPoint);
    } else {
      this.noStreet = true;
      return null;
    }
  }
  //#endregion route

  //#region chart
  async updateChart(): Promise<void> {
    if (this.$refs.chartRef) {
      const chartRef = this.$refs.chartRef as any;
      if (chartRef.chart) {
        chartRef.chart.data = this.chartData;
        chartRef.chart.update();
      }
    }
  }

  maxChartValue = 0;
  addDrivingDataToChart(newDrivingPoint: [number, number]): void {
    const distance = turf.distance(
      turf.point(this.mapDrivingPoint),
      turf.point(newDrivingPoint)
    );
    if (distance === 0) {
      return;
    }
    const vehicleParameter = this.vehicleParameter;
    const trackingData: TrackingData = {
      speed: this.moveSpeed,
      persons: this.personCount,
      consumption: formulas.consumption(
        this.moveSpeed,
        distance,
        vehicleParameter
      ),
      distance: distance,
      tireWareRate: formulas.tireWareRate(
        this.moveSpeed,
        distance,
        vehicleParameter
      ),
    };
    this.trackingData.push(trackingData);
    const totalValue = configCalculation.addValueToStatistics(
      trackingData,
      this.vehicleParameter,
      this.chartData
    );
    if (this.maxChartValue < totalValue) this.maxChartValue = totalValue;
    this.updateChart();
  }
  //#endregion chart

  //#region update loop
  updateDrivingPoint(
    newDrivingPoint: [number, number],
    subPath: [number, number][],
    maxGoalDistance = 0.001
  ): void {
    this.addAnimationSteps(newDrivingPoint, subPath);
    this.setMapDrivingPoint(newDrivingPoint);
    if (this.goalReached(maxGoalDistance)) {
      if (this.trackingManager) this.trackingManager.saveIterationStep();
      this.$emit('goalReached', this.trackingData);
      clearInterval(this.intervalCalculation);
      clearInterval(this.busStopInterval);
    }
  }

  updateDrivingPath(path: [number, number][]): void {
    const coordinates = (this.drivenPath.features[0].geometry as any)
      .coordinates;
    coordinates.push(...path);
    this.drivenPath = turfUtils.getRouteObject(coordinates);
  }

  getPossibleStreets(): [number, number][][] {
    if (this.map) {
      const mapCanvas = this.map.getCanvas();
      const bounds = this.map.getBounds();
      const speedDrivingDistance = this.getDrivingDistance();
      if (speedDrivingDistance > 0) {
        const searchPoint01 = turf.destination(
          this.mapDrivingPoint,
          speedDrivingDistance,
          this.moveAngle - minToleratedAngleDeviation
        );
        const searchPoint02 = turf.destination(
          this.mapDrivingPoint,
          speedDrivingDistance,
          this.moveAngle + minToleratedAngleDeviation
        );
        const searchRegion = turf.envelope(
          turf.featureCollection([
            searchPoint01,
            searchPoint02,
            turf.point(this.mapDrivingPoint),
          ])
        ).bbox as number[];
        const pixelPos01 = turfUtils.lngLatToPixel(
          [
            searchRegion[0] < searchRegion[2]
              ? searchRegion[0]
              : searchRegion[2],
            searchRegion[1] > searchRegion[3]
              ? searchRegion[1]
              : searchRegion[3],
          ],
          bounds,
          mapCanvas.width,
          mapCanvas.height
        );
        const pixelPos02 = turfUtils.lngLatToPixel(
          [
            searchRegion[0] > searchRegion[2]
              ? searchRegion[0]
              : searchRegion[2],
            searchRegion[1] < searchRegion[3]
              ? searchRegion[1]
              : searchRegion[3],
          ],
          bounds,
          mapCanvas.width,
          mapCanvas.height
        );
        const pixelDelta = 0; // 2;
        return mapUtils.getStreetsInRegion(
          this.map,
          this.streetLayers,
          pixelPos01,
          pixelPos02,
          pixelDelta
        );
      }
    }
    return [];
  }

  noStreet = false;
  updateTraceIsRunning = false;
  //pauseCount = 0;
  //corner: [number, number] = [0, 0];
  async updateTrace(): Promise<void> {
    if (this.updateTraceIsRunning) return;
    if (this.openAnimationSteps > this.animationIntermediateSteps / 2) return;
    this.updateTraceIsRunning = true;

    const speedDrivingDistance = this.getDrivingDistance();

    if (this.isMoving && this.moveSpeed) {
      let newDrivingPoint = this.getNewDrivingPoint(speedDrivingDistance);
      /*this.mapDrivingRotation = turfUtils.getRotation(
        this.mapDrivingPoint,
        newDrivingPoint
      );
      console.log(this.mapDrivingRotation, this.moveAngle);*/
      let isOnRoute = !this.noStreet;
      let subPath: [number, number][] = [];
      const maxGoalDistance =
        this.movingType === MovingType.free ? 0.015 : 0.005;
      const maxCornerDistance =
        this.movingType === MovingType.free ? 0.03 : 0.002;
      //this.movingType === MovingType.free ? speedDrivingDistance / 3 : 0.001;
      if (this.movingType === MovingType.free) {
        const possibleStreets = this.getPossibleStreets();
        if (possibleStreets.length > 0) {
          const streets = possibleStreets.filter((street) =>
            turfUtils.isPointCloseToRoute(
              turfUtils.getRouteObject(street),
              this.mapDrivingPoint,
              maxCornerDistance
            )
          );
          const possibleSegments: {
            value: boolean;
            endPoint: [number, number];
            corner: [number, number] | null;
            subPath: [number, number][];
            distanceToSearchPoint: number;
          }[] = [];
          for (const street of streets) {
            const insideSegment = this.isDrivingAngleInsideNextSegment(
              turfUtils.getRouteObject(street),
              speedDrivingDistance,
              newDrivingPoint,
              maxCornerDistance
            ) as any;
            if (insideSegment.value && insideSegment.subPath.length > 1) {
              const pathLength = turf.length(
                turf.lineString(insideSegment.subPath)
              );
              if (pathLength > 0) {
                insideSegment.distanceToSearchPoint = turf.distance(
                  insideSegment.endPoint,
                  newDrivingPoint
                );
                possibleSegments.push(insideSegment);
              }
            }
          }
          if (possibleSegments.length > 0) {
            const bestSegment = possibleSegments.sort(
              (a, b) => a.distanceToSearchPoint - b.distanceToSearchPoint
            )[0];
            /*const worstSegment = possibleSegments.sort(
              (a, b) => b.distanceToSearchPoint - a.distanceToSearchPoint
            )[0];*/
            subPath = bestSegment.subPath;
            newDrivingPoint = bestSegment.endPoint;
            /*if (bestSegment.corner) {
              newDrivingPoint = bestSegment.corner;
            } else if (worstSegment.corner) {
              newDrivingPoint = worstSegment.corner;
            }*/
          } else {
            isOnRoute = false;
          }
        } else {
          isOnRoute = false;
        }
      } else {
        if (isOnRoute) {
          const insideSegment = this.isDrivingAngleInsideNextSegment(
            this.routePath,
            speedDrivingDistance,
            newDrivingPoint,
            maxCornerDistance
          );
          subPath = insideSegment.subPath;
          if (insideSegment.value) {
            newDrivingPoint = insideSegment.endPoint;
            //if (insideSegment.corner) this.corner = insideSegment.corner;
            //this.pauseCount = 0;
          } else {
            /*const pauseIntervalBeforeRecalculate = 8;
            if (this.movingType === MovingType.free) {
              if (
                this.openAnimationSteps === 0 &&
                this.pauseCount >= pauseIntervalBeforeRecalculate
              ) {
                const point = await this.recalculateRoute(newDrivingPoint);
                if (point) {
                  this.pauseCount = 0;
                  this.noStreet = false;
                  if (
                    this.trackingManager &&
                    this.trackingManager.iterationStep &&
                    this.trackingManager.iterationStep.parameter.drive &&
                    this.trackingManager.iterationStep.parameter.drive
                      .pathCalculationCount
                  )
                    this.trackingManager.iterationStep.parameter.drive
                      .pathCalculationCount++;
                }
                isOnRoute = false;
              } else {
                newDrivingPoint = this.mapDrivingPoint;
                isOnRoute = false;
                if (this.openAnimationSteps === 0) this.pauseCount++;
              }
            } else {
              isOnRoute = false;
              this.pauseCount = 0;
            }*/
            isOnRoute = false;
          }
        }
      }
      this.addDrivingDataToChart(newDrivingPoint);
      if (isOnRoute) {
        this.updateDrivingPoint(newDrivingPoint, subPath, maxGoalDistance);
      } else {
        this.noStreet = true;
        const checkMarkDistance = 0.01;
        this.checkRoutePoint = this.getNewDrivingPoint(checkMarkDistance);
      }

      this.initTrackingData();
      if (this.trackingManager && this.trackingManager.iterationStep) {
        this.trackingManager.iterationStep.parameter.drive.trackingData =
          this.trackingData;
        this.trackingManager.iterationStep.parameter.drive.distanceToGoal =
          turfUtils.distanceToGoal(this.routePath, this.mapDrivingPoint);
        this.trackingManager.iterationStep.parameter.drive.drivenPathLength =
          turf.length(this.drivenPath);
        this.trackingManager.iterationStep.parameter.drive.drivenPath =
          this.drivenPath;
        //this.trackingManager.saveIterationStep();
      }
    }
    this.updateTraceIsRunning = false;
  }
  //#endregion update loop

  //#region animation
  get animationIntermediateSteps(): number {
    return Math.floor(
      this.intervalCalculationTime / this.intervalAnimationTime
    );
  }

  get openAnimationSteps(): number {
    return this.animationPoints.length - this.animationIndex;
  }

  addAnimationSteps(
    newDrivingPoint: [number, number],
    subCoordinates: [number, number][]
  ): void {
    const speedDrivingDistance = this.getDrivingDistance();
    const intermediateSteps = this.animationIntermediateSteps;
    if (subCoordinates.length < 2) {
      if (intermediateSteps > 1) this.animationPoints.push([newDrivingPoint]);
      else this.addAnimationSegment([newDrivingPoint]);
      return;
    }
    let distance = turf.length(turf.lineString(subCoordinates));
    if (distance === 0) return;
    const distanceBuffer = 0.005;
    if (distance > speedDrivingDistance + distanceBuffer) {
      subCoordinates = [this.mapDrivingPoint, newDrivingPoint];
      distance = turf.distance(
        turf.point(this.mapDrivingPoint),
        turf.point(newDrivingPoint)
      );
    }
    for (let i = 0; i < intermediateSteps; i++) {
      const animationSegment = turf.lineSliceAlong(
        turf.lineString(subCoordinates),
        (distance / intermediateSteps) * i,
        (distance / intermediateSteps) * (i + 1)
      );
      if (intermediateSteps > 1) {
        this.animationPoints.push(
          animationSegment.geometry.coordinates.map((p) => [p[0], p[1]])
        );
      } else
        this.addAnimationSegment(
          animationSegment.geometry.coordinates.map((p) => [p[0], p[1]])
        );
    }
  }

  animateVehicle(): void {
    if (this.animationIndex < this.animationPoints.length) {
      this.addAnimationSegment(this.animationPoints[this.animationIndex]);
      this.animationIndex++;
    }
  }

  addAnimationSegment(segmentPath: [number, number][]): void {
    this.mapVehiclePoint = segmentPath[segmentPath.length - 1];
    this.updateDrivingPath(segmentPath);
  }
  //#endregion animation
}
</script>

<style lang="scss" scoped>
.overlay-top-left {
  position: absolute;
  z-index: 100;
  top: 0.5rem;
  left: 0.5rem;

  .el-progress {
    position: absolute;
    top: 1rem;
    left: 1rem;
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
  left: 0.5rem;
  padding: 2rem;
}

.overlay-bottom-right {
  position: absolute;
  z-index: 100;
  bottom: 0.5rem;
  right: 0.5rem;
  padding: 2rem;
}

.overlay-bottom-left {
  position: absolute;
  z-index: 100;
  bottom: 0.5rem;
  left: 0.5rem;
  padding: 0 0 2rem 2rem;

  .overlay-top-right {
    position: absolute;
    z-index: 150;
    top: -0.5rem;
    right: 0.4rem;
    font-size: var(--font-size-large);
    color: var(--pin-color);
  }
}

.chartArea {
  height: 10rem;
  width: 100%;
}

.mapArea {
  //height: calc(100% - 10rem - 10rem);
  height: calc(100% - 10rem);
  width: 100%;
  position: relative;
}

.controlArea {
  height: 10rem;
  width: 100%;
}

.pin {
  --pin-color: var(--color-primary);
  font-size: var(--font-size-xxxlarge);
  color: var(--pin-color);
}

.noEntry {
  font-size: var(--font-size-xlarge);
  color: var(--color-evaluation);
  pointer-events: none;
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
  border: var(--color-evaluating) solid 2px;
}

.rs-control {
  display: inline-block;
}

@keyframes boarding {
  0% {
    font-size: 0;
    right: -100%;
    top: 50%;
  }
  10% {
    font-size: var(--font-size-default);
    right: -100%;
    top: 50%;
  }
  90% {
    font-size: var(--font-size-default);
    right: -20%;
    top: 0;
  }
  100% {
    font-size: 0;
  }
}

.personContainer {
  --persons: 1;
  position: relative;
  text-align: center;

  .person {
    transform-origin: -50% -50%;
    text-align: center;
    position: absolute;
    animation-name: boarding;
    animation-duration: 0.5s;
    animation-iteration-count: var(--persons);
    font-size: 0;
    right: -20%;
    top: 0;
  }
}

.addCount {
  @keyframes move {
    0% {
      font-size: 0;
      right: -100%;
      top: 50%;
    }
    10% {
      font-size: var(--font-size-xxlarge);
      right: -100%;
      top: 50%;
    }
    90% {
      font-size: var(--font-size-xxlarge);
      right: -200%;
      top: -100%;
    }
    100% {
      font-size: 0;
    }
  }

  position: absolute;
  animation-name: move;
  animation-duration: 2s;
  font-size: 0;
}

.mgl-container::v-deep(.joystick) {
  background: rgba(0, 0, 0, 0.1);
  border: 1px solid var(--color-primary);

  .joystick::v-deep(.joystick__stick) {
    background: white;
    border: 1px solid var(--color-primary);
  }
}

.streetMask {
  position: absolute;
  top: 0.5rem;
  right: -22rem;
  width: 30rem;
  height: 30rem;
  background-color: #ffffff55;
}

.miniMap {
  border: 3px solid var(--color-dark-contrast);
}
</style>

<style lang="scss">
.miniMap {
  .maplibregl-ctrl-bottom-right {
    display: none;
  }
}
</style>
