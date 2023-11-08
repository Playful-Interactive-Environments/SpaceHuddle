<template>
  <div class="chartArea">
    <!--<Line
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
                yMin: maxCleanupThreshold,
                yMax: maxCleanupThreshold,
                borderColor: highlightColor,
                borderWidth: 2,
              },
              box1: {
                type: 'box',
                xMin: 0,
                xMax: trackingData.length,
                yMin: maxCleanupThreshold,
                yMax: calcChartHeight(maxChartValue),
                backgroundColor: highlightColorTransparent,
                borderColor: highlightColor,
              },
            },
          },
        },
      }"
    />-->
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
import { OsrmCustom as OSRM, OSRMWayPoint } from '@/utils/osrm';
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
import * as constants from '@/modules/playing/moveit/utils/consts';
import Color from 'colorjs.io';
import * as mapStyle from '@/utils/mapStyle';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import * as themeColors from '@/utils/themeColors';
import * as vehicleCalculation from '@/modules/playing/moveit/types/Vehicle';
import {
  MovingType,
  NavigationType,
} from '@/modules/playing/moveit/organisms/SelectChallenge.vue';
import { createCanvas } from 'canvas';

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
  osrmProfile = 'car';
  mapZoomDefault = 15;
  mapCenter: [number, number] = [0, 0];
  mapStart: [number, number] = [0, 0];
  mapEnd: [number, number] = [0, 0];
  mapDrivingPoint: [number, number] = [0, 0];
  mapVehiclePoint: [number, number] = [0, 0];
  mapDrivingRotation = 0;
  mapZoom = this.mapZoomDefault;
  readonly maxDrivingDistance = 2;
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
  animationPoints: [number, number][][] = [];
  map!: Map;
  ready = false;
  gameWidth = 0;
  controlHeight = 0;

  direction = 0;
  speed = 0;
  maxSpeed = 0;
  //combinedJoystick = true;

  isMoving = false;
  moveSpeed = 0;
  moveDirection: [number, number] = [0, 0];
  moveAngle = 0;
  checkRoutePoint: [number, number] = [0, 0];

  boardingPersons = 0;

  readonly maxCleanupThreshold = constants.maxCleanupThreshold;
  readonly calcChartHeight = constants.calcChartHeight;

  readonly intervalCalculationTime = 100;
  intervalCalculation = -1;
  readonly intervalAnimationTime = 30;
  intervalAnimation = -1;
  readonly busStopIntervalTime = 10000;
  busStopInterval = -1;
  NavigationType = NavigationType;
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

  chartData: ChartData = {
    labels: [],
    datasets: [],
  };

  get vehicleParameter(): any {
    return configCalculation.getVehicleParameter(this.vehicle);
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
          (layer.id.includes('highway') ||
            //layer.id.includes('primary') ||
            layer.id.includes('bridge') ||
            layer.id.includes('tunnel')) &&
          !layer.id.includes('casing') &&
          !layer.id.includes('path') &&
          !layer.id.includes('area') &&
          !layer.id.includes('waterway') &&
          !layer.id.includes('railway') &&
          layer.type === 'line' &&
          !notNeededLayers.includes(layer)
        );
      })
      .map((layer) => layer.id)
      .filter((value, index, array) => array.indexOf(value) === index);
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
    const mapCanvas = this.map.getCanvas();
    const bounds = this.map.getBounds();
    const delta = 0;
    const streets = this.map
      .queryRenderedFeatures(
        [
          [-delta, -delta],
          [mapCanvas.width + delta * 2, mapCanvas.height + delta * 2],
        ],
        { layers: this.streetLayers }
      )
      .filter((f) => f.properties.class !== 'service')
      //.filter((f) => f.properties.surface !== 'unpaved')
      .filter(
        (f) =>
          f.properties.subclass !== 'pedestrian' &&
          f.properties.subclass !== 'footway' &&
          f.properties.subclass !== 'cycleway'
      );

    const canvas = createCanvas(mapCanvas.width, mapCanvas.height);
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    ctx.strokeStyle = 'black';
    ctx.lineWidth = 10;
    for (const street of streets) {
      const coordinates: [number, number][] = (street.geometry as any)
        .coordinates;
      if (coordinates.length >= 2) {
        ctx.beginPath();
        const lngLatStart: [number, number] = coordinates[0];
        if (Array.isArray(lngLatStart)) {
          const start = turfUtils.lngLatToPixel(
            lngLatStart,
            bounds,
            mapCanvas.width,
            mapCanvas.height
          );
          ctx.moveTo(start[0], start[1]);
          for (let i = 1; i < coordinates.length; i++) {
            const lngLatEnd: [number, number] = coordinates[i];
            if (Array.isArray(lngLatStart) && Array.isArray(lngLatEnd)) {
              const end = turfUtils.lngLatToPixel(
                lngLatEnd,
                bounds,
                mapCanvas.width,
                mapCanvas.height
              );
              ctx.lineTo(end[0], end[1]);
            }
          }
          ctx.stroke();
        }
      }
    }
    this.streetMask = canvas.toDataURL();
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

    this.intervalAnimation = setInterval(
      this.animateVehicle,
      this.intervalAnimationTime
    );
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
      this.routePath = this.getRouteObject(pathPoints);
      this.setMapDrivingPoint(pathPoints[0], true);
      this.mapDrivingRotation = turf.bearing(
        turf.point(this.mapDrivingPoint),
        turf.point(pathPoints[1])
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

  @Watch('direction', { immediate: true })
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
  }

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
  //#endregion watcher

  //#region route
  searchPoints: [number, number][] = [];
  searchPointColors = [
    'red',
    'green',
    'blue',
    'yellow',
    'magenta',
    'orange',
    'gray',
    'white',
    'black',
    'cyan',
    'violet',
  ];
  async isOnPossibleRoute(point: [number, number]): Promise<{
    distance: number;
    location: [number, number];
    delta: number;
    pathDelta: number;
    isOnRoutePath: boolean;
  }> {
    const getPoint = (path: OSRMWayPoint): [number, number] => {
      return [path.location[0], path.location[1]];
    };
    //eslint-disable-next-line @typescript-eslint/no-unused-vars
    const checkWay = (waypoints: OSRMWayPoint[], name: string): void => {
      let minDelta = pathDelta;
      let minPoint = waypoints[0];
      for (let i = 1; i < waypoints.length; i++) {
        const waypointDelta = turfUtils.getPathDeviation(
          [this.mapDrivingPoint, point],
          [getPoint(waypoints[i - 1]), getPoint(waypoints[i])]
        );
        if (waypointDelta < pathDelta) {
          minDelta = waypointDelta;
        }
        if (
          (minPoint.distance as number) > (waypoints[i - 1].distance as number)
        ) {
          minPoint = waypoints[i - 1];
        }
      }
      if (minDelta < pathDelta) {
        pathDelta = minDelta;
        path = minPoint;
      }
    };
    const osrm = new OSRM(this.osrmProfile, {
      userAgent: '',
    });
    this.checkRoutePoint = point;
    const nearest = await osrm.nearest(
      [point[1], point[0]],
      this.osrmProfile,
      10
    );
    let path = nearest.waypoints[0];
    this.searchPoints = nearest.waypoints.map((wp) => getPoint(wp));
    let pathDelta = 180;
    if (this.searchPoints.length > 1) {
      pathDelta = turfUtils.getPathDeviation(
        [this.mapDrivingPoint, point],
        [this.searchPoints[0], this.searchPoints[1]]
      );
      const streets = [
        ...new Set(nearest.waypoints.map((wp) => wp.name)),
      ] as string[];
      for (const street of streets) {
        const waypoints = nearest.waypoints.filter((wp) => wp.name === street);
        const hints = [
          ...new Set(waypoints.map((wp) => wp.hint.substring(0, 10))),
        ] as string[];
        checkWay(waypoints, street);
        for (const hint of hints) {
          checkWay(
            waypoints.filter((wp) => wp.hint.startsWith(hint)),
            hint
          );
        }
      }
    }
    const location = getPoint(path);
    const delta = turfUtils.getAngleDeviation(
      this.mapDrivingPoint,
      point,
      location
    );
    return {
      distance: path.distance,
      location: location,
      delta: delta,
      pathDelta: pathDelta,
      isOnRoutePath: turfUtils.isPointOnPath(this.routePath, location),
    };
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
    wayPoints.push(
      ...intermediateGoals.map((p) => [p[1], p[0]] as [number, number])
    );
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
    this.drivenPath = this.getRouteObject(coordinates);
  }

  getPossibleStreets(): [number, number][][] {
    const connectIfOverlaps = (
      street01: [number, number][],
      street02: [number, number][]
    ): [number, number][] => {
      if (
        street01[street01.length - 1][0] === street02[0][0] &&
        street01[street01.length - 1][1] === street02[0][1] &&
        street01[street01.length - 2][0] !== street02[1][0] &&
        street01[street01.length - 2][1] !== street02[1][1]
      ) {
        return [...street01.slice(0, -1), ...street02];
      }
      /*for (let i = street01.length - 1; i > 0; i--) {
        const checkCount = street01.length - i;
        if (street02.length < checkCount) return [];
        let match = true;
        for (let j = i, k = 0; j < street01.length; j++, k++) {
          if (
            street02.length <= k ||
            street01[j][0] !== street02[k][0] ||
            street01[j][1] !== street02[k][1]
          ) {
            match = false;
            break;
          }
        }
        if (match) {
          const j = street01.length - 1 - i;
          if (
            i === street01.length - 1 &&
            street01[i - 1][0] === street02[1][0] &&
            street01[i - 1][1] === street02[1][1]
          )
            return [];
          if (
            street02.length > j + 1 &&
            street01[i - 1][0] === street02[j + 1][0] &&
            street01[i - 1][1] === street02[j + 1][1]
          )
            return [];
          return [...street01.slice(0, i), ...street02];
        }
      }*/
      return [];
    };

    const checkTotalOverlay = (
      street01: [number, number][],
      street02: [number, number][]
    ): boolean => {
      for (let i = street01.length - street02.length; i >= 0; i--) {
        let match = true;
        for (let j = i, k = 0; k < street02.length; j++, k++) {
          if (
            street01[j][0] !== street02[k][0] ||
            street01[j][1] !== street02[k][1]
          ) {
            match = false;
            break;
          }
        }
        if (match) return true;
      }
      return false;
    };

    const removeTotalOverlay = (
      roads: [number, number][][]
    ): [number, number][][] => {
      const streets: [number, number][][] = [];
      for (let i = 0; i < roads.length; i++) {
        const street01 = roads[i];
        let overlay = false;
        for (let j = 0; j < roads.length; j++) {
          if (i === j) continue;
          const street02 = roads[j];
          overlay = checkTotalOverlay(street02, street01);
          if (overlay) {
            if (street01.length !== street02.length || i > j) break;
            else overlay = false;
          }
          overlay = checkTotalOverlay(street02, [...street01].reverse());
          if (overlay) {
            if (street01.length !== street02.length || i > j) break;
            else overlay = false;
          }
        }
        if (!overlay) streets.push(street01);
      }
      return streets;
    };

    const connectStreets = (
      roads: [number, number][][],
      baseRoads: [number, number][][] | null = null,
      loopCount = 0
    ): [number, number][][] => {
      roads = removeTotalOverlay(roads);
      const roadList = roads.map((road) => {
        return {
          coordinates: road,
          findConnection: false,
        };
      });
      const streets: [number, number][][] = [];
      let findConnection = false;
      for (let i = 0; i < roadList.length; i++) {
        const street01 = roadList[i].coordinates;
        if (!baseRoads) {
          for (let j = i + 1; j < roadList.length; j++) {
            const street02 = roadList[j].coordinates;
            const tryConnection = (
              road01: [number, number][],
              road02: [number, number][]
            ): boolean => {
              const street = connectIfOverlaps(road01, road02);
              if (street.length > 0) {
                findConnection = true;
                roadList[i].findConnection = true;
                roadList[j].findConnection = true;
                streets.push(street);
                return true;
              }
              return false;
            };
            if (!tryConnection(street01, street02))
              if (!tryConnection([...street01].reverse(), street02))
                if (!tryConnection(street01, [...street02].reverse()))
                  tryConnection(
                    [...street01].reverse(),
                    [...street02].reverse()
                  );
          }
        } else {
          for (let j = 0; j < baseRoads.length; j++) {
            const street02 = baseRoads[j];
            if (checkTotalOverlay(street01, street02)) continue;
            if (checkTotalOverlay(street01, [...street02].reverse())) continue;
            const tryConnection = (
              road01: [number, number][],
              road02: [number, number][]
            ): boolean => {
              const street = connectIfOverlaps(road01, road02);
              if (street.length > 0) {
                findConnection = true;
                roadList[i].findConnection = true;
                streets.push(street);
                return true;
              }
              return false;
            };
            if (!tryConnection(street01, street02))
              if (!tryConnection([...street01].reverse(), street02))
                if (!tryConnection(street01, [...street02].reverse()))
                  tryConnection(
                    [...street01].reverse(),
                    [...street02].reverse()
                  );
          }
        }
        if (!roadList[i].findConnection) {
          streets.push(street01);
        }
      }
      if (findConnection && loopCount < 6) {
        return connectStreets(streets, baseRoads ?? roads, loopCount + 1);
      }
      return streets;
    };

    /*return connectStreets([
      [[14.285917282104492, 48.30691550235801],
        [14.286566376686096, 48.30715813191841],
        [14.286877512931824, 48.30728658239519]],


      [[14.285863637924194, 48.306983296027795],
        [14.285670518875122, 48.30725090173962]],


      [[14.285917282104492, 48.30691550235801],
        [14.285863637924194, 48.306983296027795]],


      [[14.285697340965271, 48.30692977471702],
        [14.285756349563599, 48.30685127669307]],


      [[14.285504221916199, 48.30719738070954],
        [14.285697340965271, 48.30692977471702]],


      [[14.285756349563599, 48.30685127669307],
        [14.285917282104492, 48.30691550235801]],


      [[14.286502003669739, 48.30613051646574],
        [14.28646981716156, 48.306205447458495],
        [14.286324977874756, 48.30640883102723],
        [14.286180138587952, 48.30658010077221],
        [14.286110401153564, 48.30665146299637],
        [14.285917282104492, 48.30691550235801]],


      [[14.285756349563599, 48.30685127669307],
        [14.285799264907837, 48.30679418714524]],


      [[14.285799264907837, 48.30679418714524],
        [14.285944104194641, 48.30660150944993],
        [14.28596556186676, 48.30656226020062]],


      [[14.28596556186676, 48.30656226020062],
        [14.286169409751892, 48.30619831117821],
        [14.286190867424011, 48.30610910759043]],


      [[14.285756349563599, 48.30685127669307],
        [14.285686612129211, 48.30682630002377]]
    ]);*/

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
        const pixelDelta = 2;
        const roadList = this.map
          .queryRenderedFeatures(
            [
              [pixelPos01[0] - pixelDelta, pixelPos01[1] - pixelDelta],
              [pixelPos02[0] + pixelDelta, pixelPos02[1] + pixelDelta],
            ],
            { layers: this.streetLayers }
          )
          //.filter((f) => f.properties.class !== 'service')
          .filter((f) => f.properties.surface !== 'unpaved')
          .filter(
            (f) =>
              f.properties.subclass !== 'pedestrian' &&
              f.properties.subclass !== 'footway' &&
              f.properties.subclass !== 'cycleway'
          )
          .map(
            (road) => (road.geometry as any).coordinates as [number, number][]
          );
        return connectStreets(roadList);
      }
    }
    return [];
  }

  noStreet = false;
  updateTraceIsRunning = false;
  pauseIntervalBeforeRecalculate = 8;
  pauseCount = 0;
  corner: [number, number] = [0, 0];
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
      let subPath: [number, number][] = [];
      const maxGoalDistance =
        this.movingType === MovingType.free ? speedDrivingDistance / 3 : 0.001;
      if (this.movingType === MovingType.free) {
        const possibleStreets = this.getPossibleStreets();
        if (possibleStreets.length > 0) {
          const streets = possibleStreets.filter((street) =>
            turfUtils.isPointCloseToRoute(
              this.getRouteObject(street),
              this.mapDrivingPoint,
              0.003
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
              this.getRouteObject(street),
              speedDrivingDistance,
              newDrivingPoint,
              speedDrivingDistance / 4
            ) as any;
            if (insideSegment.value) {
              insideSegment.distanceToSearchPoint = turf.distance(
                insideSegment.endPoint,
                newDrivingPoint
              );
              possibleSegments.push(insideSegment);
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
              this.pauseCount = 5;
            } else if (worstSegment.corner) {
              newDrivingPoint = worstSegment.corner;
              this.pauseCount = 5;
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
            newDrivingPoint
          );
          subPath = insideSegment.subPath;
          if (insideSegment.value) {
            newDrivingPoint = insideSegment.endPoint;
            if (insideSegment.corner) this.corner = insideSegment.corner;
            this.pauseCount = 0;
          } else {
            /*if (this.goalReached()) {
              this.$emit('goalReached', this.trackingData);
              clearInterval(this.intervalCalculation);
              clearInterval(this.busStopInterval);
            }*/
            //const recalculateRoute = this.movingType === MovingType.free;
            /*const corner = turfUtils.isCornerPointOnSegment(
              this.routePath,
              this.mapDrivingPoint,
              newDrivingPoint,
              minToleratedAngleDeviation
            );
            if (corner.value) {
              this.corner = corner.location;
              newDrivingPoint = corner.location;
              recalculateRoute = false;
            } else if (
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
            }*/

            /*if (recalculateRoute) {
              if (
                this.openAnimationSteps === 0 &&
                this.pauseCount >= this.pauseIntervalBeforeRecalculate
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
      this.animationPoints.push([newDrivingPoint]);
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
      this.animationPoints.push(
        animationSegment.geometry.coordinates.map((p) => [p[0], p[1]])
      );
    }
  }

  animateVehicle(): void {
    if (this.animationIndex < this.animationPoints.length) {
      const segmentPath = this.animationPoints[this.animationIndex];
      this.mapVehiclePoint = segmentPath[segmentPath.length - 1];
      this.updateDrivingPath(segmentPath);
      this.animationIndex++;
    }
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
  padding: 2rem;
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
</style>
