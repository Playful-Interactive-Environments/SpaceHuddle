<template>
  <div class="chartArea">
    <Line
      ref="chartRef"
      :data="chartData"
      :options="{
        responsive: true,
        maintainAspectRatio: false,
        animation: {
          duration: 0,
        },
        scales: {
          x: {
            title: {
              text: $t(
                'module.playing.moveit.participant.info.driving.scale.x'
              ),
              display: true,
            },
          },
          y: {
            stacked: true,
            title: {
              text: $t(
                'module.playing.moveit.participant.info.driving.scale.y'
              ),
              display: true,
            },
          },
        },
      }"
    />
  </div>
  <div class="mapArea">
    <mgl-map
      :center="mapVehiclePoint"
      :zoom="mapZoom"
      :double-click-zoom="false"
      :keyboard="false"
      @map:load="onLoad"
    >
      <mgl-geo-json-source
        v-if="routeCalculated && movingType === MovingType.path"
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
      <!--<mgl-geo-json-source source-id="drivenPath" :data="drivenPath">
        <mgl-line-layer
          layer-id="drivenPath"
          :layout="routeLayout"
          :paint="drivenPaint"
        />
      </mgl-geo-json-source>-->
      <!--<mgl-geo-json-source
        v-for="(street, index) of possibleStreets"
        :key="index"
        :source-id="`street${index}`"
        :data="turfUtils.getRouteObject(street)"
        :lineMetrics="true"
      >
        <mgl-line-layer
          :layer-id="`street${index}`"
          :layout="routeLayout"
          :paint="routePaint"
        />
      </mgl-geo-json-source>-->
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
            <font-awesome-icon
              v-if="carLoading"
              icon="car"
              class="previewImage"
            />
            <img
              class="divingVehicle"
              :src="`/assets/games/moveit/vehicle/${vehicleParameter.imageTop}`"
              alt="car"
              :width="30"
              @load="() => (carLoading = false)"
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
      <CustomMapMarker anchor="top-left" :coordinates="mapEnd">
        <template v-slot:icon>
          <font-awesome-icon icon="flag-checkered" class="pin" />
        </template>
      </CustomMapMarker>
      <!--<CustomMapMarker anchor="bottom" v-for="(corner, index) of searchPoints" :key="index" :coordinates="corner.point">
        <template v-slot:icon>
          <font-awesome-icon
            icon="location-pin"
            class="pin"
            :style="{
              color: corner.color,
              display: corner.active ? 'block' : 'none',
            }"
          />
        </template>
      </CustomMapMarker>
      <CustomMapMarker
        anchor="bottom"
        v-for="(corner, index) of corners"
        :key="index"
        :coordinates="corner.point"
      >
        <template v-slot:icon>
          <font-awesome-icon
            icon="location-dot"
            class="pin"
            :style="{ color: 'red', display: corner.active ? 'block' : 'none' }"
          />
        </template>
      </CustomMapMarker>-->
      <CustomMapMarker
        anchor="center"
        :coordinates="mapEndInfo"
        :rotation="infoRotation"
      >
        <template v-slot:icon>
          <font-awesome-icon
            icon="arrow-right"
            class="pin"
            :style="{ display: showDirectionInfo ? 'block' : 'none' }"
          />
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
    <!--<div class="overlay-top-left">
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
    </div>-->
    <div class="overlay-top-right">
      <p
        class="overlay-info"
        :style="{ backgroundColor: getSpeedColor(moveSpeed, 0.6) }"
      >
        {{ Math.round(moveSpeed) }} km/h
      </p>
      <div v-if="vehicle.category === 'bus'" class="overlay-info">
        <font-awesome-icon icon="users" />
        {{ personCount }} / {{ vehicleParameter.persons }}
      </div>
    </div>
    <div class="overlay-bottom-right">
      <div>
        <Joystick
          v-if="
            zoomReady &&
            navigation !== NavigationType.speed &&
            navigation !== NavigationType.acceleration
          "
          :size="120"
          :stick-size="40"
          @move="move($event)"
          @start="startJoystick"
          @stop="stopJoystick"
          @mousedown="disableMapPan"
          v-on:touchstart="disableMapPan"
          stickColor="radial-gradient(circle at 60% 55%, #ffffff, #aaaaaa)"
          :base-color="`radial-gradient(circle, #757d76ff 20%, #26352799 59%, ${getSpeedColor(
            moveSpeed,
            0.3
          )} 60%)`"
        />
        <!--<el-button @click="createVisibleStreetMask">test</el-button>-->
      </div>
    </div>
    <div
      :class="
        navigation === NavigationType.speed ||
        navigation === NavigationType.acceleration
          ? 'overlay-bottom-right'
          : 'overlay-bottom-left'
      "
    >
      <el-button
        v-if="
          zoomReady &&
          (navigation === NavigationType.speed ||
            navigation === NavigationType.speedDirection)
        "
        class="brake"
        @pointerdown="startDecreaseSpeed"
        @pointerup="stopDecreaseSpeed"
        @contextmenu="(e) => e.preventDefault()"
        :style="{ backgroundColor: getSpeedColor(100 - moveSpeed, 0.6) }"
      >
        B
      </el-button>
      <el-button
        v-if="zoomReady && hasAccelerationButtons"
        class="gas"
        @pointerdown="startIncreaseSpeed"
        @pointerup="stopIncreaseSpeed"
        @contextmenu="(e) => e.preventDefault()"
        :style="{ backgroundColor: getSpeedColor(moveSpeed, 0.6) }"
      >
        G
      </el-button>
      <el-slider
        v-if="zoomReady && hasAccelerationSlider"
        v-model="accelerationFactor"
        :max="maxAccelerationFactor"
        :min="minAccelerationFactor"
        :show-tooltip="false"
        :marks="{
          '-10': {
            style: {
              color: getRedColor(),
            },
            label: $t('module.playing.moveit.participant.break'),
          },
          0: $t('module.playing.moveit.participant.hold'),
          10: {
            style: {
              color: getGreenColor(),
            },
            label: $t('module.playing.moveit.participant.gas'),
          },
        }"
        vertical
        height="10rem"
        @pointerdown="startIncreaseSpeed"
        @pointerup="stopIncreaseSpeed"
        class="accelerationFactor"
        :style="{
          '--acceleration-color': getAccelerationColor(),
        }"
      />
      <el-slider
        v-if="zoomReady && navigation === NavigationType.joystick"
        v-model="maxSpeed"
        :max="vehicleParameter.speed"
        :format-tooltip="
          (value) =>
            `${$t('module.playing.moveit.participant.maxSpeed')}: ${value} ${$t(
              'module.playing.moveit.enums.units.km/h'
            )}`
        "
        vertical
        height="6rem"
      />
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
    <div class="overlay-top-left">
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
        :double-click-zoom="false"
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

    <div id="overlayEndGoal">
      <div class="overlayEndGoalBackground"></div>
      <h1 class="heading heading--big">
        {{ $t('module.playing.moveit.participant.drivingStats.goal') }}
      </h1>
      <h2 class="heading heading--regular">
        {{ $t('module.playing.moveit.participant.drivingStats.avgSpeed') }} :
        {{ Math.round(calculateSpeed('average')) }}
        {{ $t('module.playing.moveit.enums.units.km/h') }}
      </h2>
      <h2 class="heading heading--regular">
        {{ $t('module.playing.moveit.participant.drivingStats.maxSpeed') }} :
        {{ Math.round(calculateSpeed('max')) }}
        {{ $t('module.playing.moveit.enums.units.km/h') }}
      </h2>
      <h2 class="heading heading--regular">
        {{ $t('module.playing.moveit.participant.drivingStats.consumption') }} :
        {{ Math.round(calculateSpeed('consumption') * 1000) / 1000 }}
        <span v-if="activeVehicle.fuel === 'electricity'">
          {{ $t('module.playing.moveit.enums.units.kw') }}
        </span>
        <span v-else>
          {{ $t('module.playing.moveit.enums.units.liters') }}
        </span>
      </h2>
      <h2 class="heading heading--regular">
        {{ $t('module.playing.moveit.participant.drivingStats.distance') }} :
        {{ Math.round(calculateSpeed('distance') * 100) / 100 }}
        {{ $t('module.playing.moveit.enums.units.km') }}
      </h2>
      <h2 class="heading heading--regular">
        {{ $t('module.playing.moveit.participant.drivingStats.persons') }} :
        {{ Math.round(calculateSpeed('persons')) }}
      </h2>
      <h2 class="heading heading--regular">
        {{ $t('module.playing.moveit.participant.drivingStats.time') }} :
        {{ this.driveTime }}s
      </h2>
      <el-button type="primary" native-type="submit" @click="endGame">
        {{ $t('module.playing.moveit.participant.confirm') }}
      </el-button>
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
import gameConfig from '@/modules/playing/moveit/data/gameConfig.json';
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
import { getGreenColor, getRedColor } from '@/utils/themeColors';
import * as vehicleCalculation from '@/modules/playing/moveit/types/Vehicle';
import {
  MovingType,
  NavigationType,
} from '@/modules/playing/moveit/organisms/SelectChallenge.vue';
import * as mapUtils from '@/modules/playing/moveit/utils/map';
import {
  TrackingData,
  normalizedTrackingData,
  trackingDataToChartData,
  averageSpeed,
  maxSpeed,
  consumption,
  distance,
  persons,
} from '@/modules/playing/moveit/utils/trackingData';

mapStyle.setMapStyleStreets();

/* eslint-disable @typescript-eslint/no-explicit-any*/
interface BusStop {
  coordinates: [number, number];
  name: string;
  id: number;
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

interface ColorProp {
  color: string;
  percentage: number;
}

const minToleratedAngleDeviation = 22.5;
export const timeScaleFactor = 10;
const intervalCalculationTime = 50;
export const drivingStepTime =
  (intervalCalculationTime / 1000) * timeScaleFactor;

@Options({
  methods: { getRedColor, getGreenColor },
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
  showMiniMap = false;
  mapZoomDefault = 18;
  mapCenter: [number, number] = [0, 0];
  mapStart: [number, number] = [0, 0];
  mapEnd: [number, number] = [0, 0];
  mapDrivingPoint: [number, number] = [0, 0];
  mapVehiclePoint: [number, number] = [0, 0];
  mapZoom = this.mapZoomDefault;
  readonly maxDrivingDistance = 2;
  routeCalculated = true;
  routePath: FeatureCollection = turfUtils.getRouteObject([]);
  drivenPath: FeatureCollection = turfUtils.getRouteObject([]);
  routeLayout: LineLayerSpecification['layout'] = {
    'line-join': 'round',
    'line-cap': 'round',
  };
  routePaint: any = {
    'line-color': '#0000FF',
    'line-width': 4,
    /*'line-gradient': [
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
    ],*/
  };
  drivenPaint: LineLayerSpecification['paint'] = {
    'line-color': '#FF0000',
    'line-width': 8,
  };
  busStopList: BusStop[] = [];
  personCount = 1;
  stops = 0;
  trackingData: TrackingData[] = [];
  normalizedTrackingData: TrackingData[] = [];
  animationIndex = 0;
  animationPoints: [number, number][][] = [];
  map!: Map;
  ready = false;
  carLoading = true;

  speed = 0;
  maxSpeed = 0;

  isMoving = false;
  moveSpeed = 0;
  moveDirection: [number, number] = [0, 0];
  moveAngle = 0;
  checkRoutePoint: [number, number] = [0, 0];
  accelerationFactor = 5;
  minAccelerationFactor = -10;
  maxAccelerationFactor = 10;

  boardingPersons = 0;

  readonly intervalCalculationTime = intervalCalculationTime;
  intervalCalculation = -1;
  readonly intervalAnimationTime = 50;
  intervalAnimation = -1;
  readonly intervalChartTime = 1000;
  intervalChart = -1;
  stepsPerSecond = 5;
  intervalGas = -1;
  intervalBreak = -1;
  decreaseSpeedInterval = -1;
  NavigationType = NavigationType;
  MovingType = MovingType;

  endGoalReached = false;
  startTime = 0;

  chartData: ChartData = {
    labels: [],
    datasets: [],
  };

  turfUtils = turfUtils;
  streetPaint: LineLayerSpecification['paint'] = {
    'line-color': '#FFFF00',
    'line-width': 8,
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

  get hasAccelerationSlider(): boolean {
    return (
      this.navigation === NavigationType.acceleration ||
      this.navigation === NavigationType.accelerationDirection
    );
  }

  get hasAccelerationButtons(): boolean {
    return (
      this.navigation === NavigationType.speed ||
      this.navigation === NavigationType.speedDirection
    );
  }

  get controlDirection(): boolean {
    return (
      this.navigation !== NavigationType.speed &&
      this.navigation !== NavigationType.acceleration
    );
  }

  get activeVehicle(): any {
    return gameConfig.vehicles[this.vehicle.category].types.find(
      (item) => item.name === this.vehicle.type
    );
  }

  calculateColor(
    colorPropList: ColorProp[],
    percentage: number,
    transparent = 1
  ): string {
    const convertColor = (colorProp: ColorProp): Color => {
      return new Color(colorProp.color);
    };
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

  getAccelerationColor(): string {
    const percentage =
      ((this.accelerationFactor - this.minAccelerationFactor) /
        (this.maxAccelerationFactor - this.minAccelerationFactor)) *
      100;
    const colorPropList = [
      {
        color: themeColors.getRedColor(),
        percentage: 40,
      },
      {
        color: themeColors.getGreenColor(),
        percentage: 60,
      },
    ];
    return this.calculateColor(colorPropList, percentage);
  }

  getSpeedColor(speed: number, transparent = 1): string {
    const percentage = this.convertSpeedToColorPercentage(speed);
    const colorPropList = this.colors;
    return this.calculateColor(colorPropList, percentage, transparent);
  }

  get vehicleParameter(): any {
    return configCalculation.getVehicleParameter(this.vehicle);
  }

  get mapDrivingRotation(): number {
    if (this.moveAngle < 0) return this.moveAngle + 360;
    return this.moveAngle;
  }

  get driveTime(): number {
    return drivingStepTime * this.trackingData.length;
  }

  getRoute(): turf.Feature<turf.LineString> | turf.LineString {
    return turfUtils.getRoute(this.routePath);
  }

  getDrivenRoute(): turf.Feature<turf.LineString> | turf.LineString {
    return turfUtils.getRoute(this.drivenPath);
  }

  getDrivingDistance(moveSpeed: number | null = null): number {
    if (moveSpeed === null) moveSpeed = this.moveSpeed;
    return (moveSpeed * drivingStepTime) / 3600;
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
    allowedCornerDistance = 0.001,
    moveAngle: number | null = null
  ): {
    value: boolean;
    endPoint: [number, number];
    corner: [number, number] | null;
    subPath: [number, number][];
  } {
    if (!moveAngle) moveAngle = this.moveAngle;
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
    console.log(minMax, moveAngle);*/
    if (moveAngle >= minMax.min && moveAngle <= minMax.max)
      return {
        value: true,
        endPoint: minMax.endPoint,
        corner: minMax.corner,
        subPath: minMax.subPath,
      };
    if (moveAngle - 360 >= minMax.min && moveAngle - 360 <= minMax.max)
      return {
        value: true,
        endPoint: minMax.endPoint,
        corner: minMax.corner,
        subPath: minMax.subPath,
      };
    if (moveAngle + 360 >= minMax.min && moveAngle + 360 <= minMax.max)
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
  zoomReady = false;
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
      setTimeout(() => {
        this.zoomReady = true;
        this.calculateRouteInfoPoint();
      }, 1000);
      /*setTimeout(() => {
        this.createVisibleStreetMask();
      }, 2000);*/
    }, 1000);
  }

  miniMap!: Map;
  miniMapLoaded(e: MglEvent): void {
    this.miniMap = e.map;
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
    const mapSize = mapUtils.getMapSize(this.map);
    const features = this.map.queryRenderedFeatures([[0, 0], mapSize], {
      layers: [busLayerName],
    });
    this.busStopList = features
      .filter((f) => f.properties.class === 'bus')
      .map((f) => {
        let persons = Math.ceil(Math.random() * 10);
        if (persons < 1) persons = 1;
        return {
          coordinates: (f.geometry as any).coordinates,
          name: f.properties.name,
          id: f.id,
          persons: persons,
        } as BusStop;
      });
  }

  streetMask = '';
  createVisibleStreetMask(): void {
    this.streetMask = mapUtils.calculateStreetMask(this.map, this.streetLayers);
  }

  async mounted(): Promise<void> {
    this.startTime = Date.now();
    this.ready = true;
    this.maxSpeed = this.vehicleParameter.speed;
    if (this.navigation === NavigationType.joystick && this.maxSpeed > 100)
      this.maxSpeed = 100;

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
    }
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
    this.intervalChart = setInterval(this.updateChart, this.intervalChartTime);

    window.addEventListener('mouseup', this.enableMapPan);
    window.addEventListener('touchend', this.enableMapPan);
    window.addEventListener('keydown', this.handleKeyDown);
    window.addEventListener('keyup', this.handleKeyUp);
    window.addEventListener('touchend', this.stopTouchAction);
    window.addEventListener('mouseup', this.stopTouchAction);
    window.addEventListener('pointerup', this.stopTouchAction);
    window.addEventListener('pointerdown', this.syncTouchPoint);
    window.addEventListener('touchstart', this.syncTouchPoint);
  }

  KeyPressedAccelerate = false;
  KeyPressedDecelerate = false;
  handleKeyDown(event: KeyboardEvent): void {
    if (this.navigation !== NavigationType.joystick) {
      if (
        (event.key === 'g' || event.key === 'ArrowUp') &&
        !this.KeyPressedAccelerate &&
        this.zoomReady
      ) {
        this.KeyPressedAccelerate = true;
        this.startIncreaseSpeed();
      }
      if (
        (event.key === 'b' || event.key === 'ArrowDown') &&
        !this.KeyPressedDecelerate &&
        this.zoomReady
      ) {
        this.KeyPressedDecelerate = true;
        this.startDecreaseSpeed();
      }
    }
  }

  handleKeyUp(event: KeyboardEvent): void {
    if (this.navigation !== NavigationType.joystick) {
      if (event.key === 'g' || event.key === 'ArrowUp') {
        this.KeyPressedAccelerate = false;
        this.stopIncreaseSpeed();
      }
      if (event.key === 'b' || event.key === 'ArrowDown') {
        this.KeyPressedDecelerate = false;
        this.stopDecreaseSpeed();
      }
    }
  }

  unmounted(): void {
    clearInterval(this.intervalCalculation);
    clearInterval(this.intervalAnimation);
    clearInterval(this.intervalChart);
    clearInterval(this.decreaseSpeedInterval);
    clearInterval(this.intervalGas);
    clearInterval(this.intervalBreak);
    window.removeEventListener('mouseup', this.enableMapPan);
    window.removeEventListener('touchend', this.enableMapPan);
    window.removeEventListener('keydown', this.handleKeyDown);
    window.removeEventListener('keyup', this.handleKeyUp);
    window.removeEventListener('mouseup', this.stopTouchAction);
    window.removeEventListener('touchend', this.stopTouchAction);
    window.removeEventListener('pointerup', this.stopTouchAction);
  }
  //#endregion load / unload

  //#region navigation
  startJoystick(event: any): void {
    if (this.navigation === NavigationType.joystick) this.start(event);
  }

  start(event: any | null = null): void {
    this.disableMapPan();
    this.boardingPersons = 0;
    if (event) this.move(event);
    this.isMoving = true;
    clearInterval(this.intervalCalculation);
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

  snapToCorner(speedDrivingDistance: number): void {
    const possibleCorners = this.getPossibleCorners(speedDrivingDistance, true);
    if (possibleCorners.length > 0) {
      let minCorner = possibleCorners[0];
      let minDistance = turf.distance(minCorner, this.mapDrivingPoint);
      for (let i = 1; i < possibleCorners.length; i++) {
        const distance = turf.distance(
          possibleCorners[i],
          this.mapDrivingPoint
        );
        if (distance < minDistance) {
          minDistance = distance;
          minCorner = possibleCorners[i];
        }
      }
      if (minDistance < 0.015) {
        this.updateDrivingPoint(minCorner, [], 0.015);
      }
    }
  }

  corners: { point: [number, number]; active: boolean }[] = [];
  searchPoints: { point: [number, number]; active: boolean; color: string }[] =
    [];
  stopJoystick(): void {
    if (this.navigation === NavigationType.joystick) this.stop();
  }

  addedBusList: number[] = [];
  stop(): void {
    this.enableMapPan();
    clearInterval(this.intervalCalculation);
    if (!this.isMoving) return;
    this.isMoving = false;
    this.moveSpeed = 0;
    this.stops++;

    if (this.vehicle.category === 'bus') {
      this.updateVisibleBusStops();
      for (const busStop of this.busStopList) {
        if (this.addedBusList.find((item) => item === busStop.id)) continue;
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
          this.addedBusList.push(busStop.id);
          break;
        }
      }
    }

    const speedDrivingDistance = 0.04;
    //const maxStreetDistance = 0.0001;
    const maxCornerDistance = 0;
    if (this.movingType === MovingType.free) {
      this.snapToCorner(speedDrivingDistance);
    } else if (this.controlDirection) {
      const corner = turfUtils.isCornerPoint(
        this.routePath,
        this.mapDrivingPoint
      );
      if (corner.value) {
        if (
          corner.location[0] !== this.mapDrivingPoint[0] ||
          corner.location[1] !== this.mapDrivingPoint[1]
        )
          this.updateDrivingPoint(corner.location, [], 0.005);
      } else {
        const newDrivingPoint = this.getNewDrivingPoint(speedDrivingDistance);
        const insideSegment = this.isDrivingAngleInsideNextSegment(
          this.routePath,
          speedDrivingDistance,
          newDrivingPoint,
          maxCornerDistance
        );
        if (insideSegment.corner) {
          this.updateDrivingPoint(insideSegment.corner, [], 0.005);
        }
      }
    }
  }

  move(event: any): void {
    if (event.distance === undefined) return;
    const calcAngle = (point: [number, number]): number => {
      return Math.atan2(point[0], point[1]) * (180 / Math.PI);
    };
    if (this.navigation === NavigationType.joystick) {
      this.moveSpeed = (event.distance / 100.0) * this.maxSpeed;
    }
    this.moveAngle = calcAngle([event.x, event.y]);
    this.moveDirection = [event.x, event.y];
    this.noStreet = false;
  }

  isIncreaseSpeedStarted = false;
  clickPointerId = -1;
  clickTouchId = -1;
  //eslint-disable-next-line @typescript-eslint/no-unused-vars
  startIncreaseSpeed(event: PointerEvent | null = null): void {
    this.clickPointerId = event ? event.pointerId : -1;
    this.isIncreaseSpeedStarted = true;
    if (event && this.hasAccelerationButtons) event.preventDefault();
    clearInterval(this.decreaseSpeedInterval);
    this.decreaseSpeedInterval = -1;
    this.increaseSpeed();
    clearInterval(this.intervalGas);
    this.intervalGas = setInterval(
      this.increaseSpeed,
      1000 / this.stepsPerSecond
    );
  }

  //eslint-disable-next-line @typescript-eslint/no-unused-vars
  stopIncreaseSpeed(event: PointerEvent | null = null): void {
    const pointerId = this.getPointerIdForEvent(event);
    if (pointerId === this.clickPointerId) {
      if (event && this.hasAccelerationButtons) event.preventDefault();
      clearInterval(this.intervalGas);
      if (this.decreaseSpeedInterval === -1) {
        clearInterval(this.decreaseSpeedInterval);
        this.decreaseSpeedInterval = setInterval(
          () => this.decreaseSpeed(5),
          1000 / this.stepsPerSecond
        );
      }
      this.isIncreaseSpeedStarted = false;
    }
  }

  pointerDict: { [key: number]: number } = {};
  lastTouchId = -1;
  syncTouchPoint(event: TouchEvent | PointerEvent): void {
    if (event instanceof TouchEvent) {
      for (const key of Object.keys(this.pointerDict)) {
        if (this.pointerDict[key] === -1) {
          this.pointerDict[key] = event.changedTouches[0].identifier;
          return;
        }
      }
      this.lastTouchId = event.changedTouches[0].identifier;
    } else {
      this.pointerDict[event.pointerId] = this.lastTouchId;
      this.lastTouchId = -1;
    }
  }

  getPointerIdForEvent(
    event: PointerEvent | TouchEvent | MouseEvent | null
  ): number {
    if (event instanceof PointerEvent) {
      return event.pointerId;
    } else if (event instanceof TouchEvent) {
      for (const key of Object.keys(this.pointerDict).reverse()) {
        if (this.pointerDict[key] === event.changedTouches[0].identifier) {
          return parseInt(key);
        }
      }
    } else if (event instanceof MouseEvent) {
      return parseInt(Object.keys(this.pointerDict).reverse()[0]);
    }
    return -1;
  }

  stopTouchAction(
    event: PointerEvent | TouchEvent | MouseEvent | null = null
  ): void {
    if (this.navigation !== NavigationType.joystick) {
      if (this.isIncreaseSpeedStarted) this.stopIncreaseSpeed(event as any);
      if (this.isDecreaseSpeedStarted) this.stopDecreaseSpeed(event as any);
    }
    const pointerId = this.getPointerIdForEvent(event);
    if (pointerId) delete this.pointerDict[pointerId];
  }

  increaseValue = 0;
  increaseSpeed(): void {
    if (this.accelerationFactor > 0 && this.speed < this.maxSpeed) {
      let speed = this.speed;
      if (this.speed < this.maxSpeed && !this.noStreet) {
        if (this.speed + this.accelerationFactor < this.maxSpeed) {
          speed += this.accelerationFactor;
        } else {
          speed += this.maxSpeed - this.speed;
        }
      }
      if (speed < 0) speed = 0;
      if (speed !== this.speed) {
        if (this.speed !== 0 || this.checkPossibility(speed)) {
          this.speed = speed;
          this.increaseValue = this.speed;
        }
      }
    } else if (this.accelerationFactor < 0 && this.speed > 0) {
      this.decreaseSpeed(
        this.accelerationFactor - this.minAccelerationFactor + 0.3,
        this.increaseValue
      );
    }
  }

  isDecreaseSpeedStarted = false;
  //eslint-disable-next-line @typescript-eslint/no-unused-vars
  startDecreaseSpeed(event: PointerEvent | null = null): void {
    this.clickPointerId = event ? event.pointerId : -1;
    this.isDecreaseSpeedStarted = true;
    if (event && this.hasAccelerationButtons) event.preventDefault();
    clearInterval(this.decreaseSpeedInterval);
    this.decreaseSpeedInterval = -1;
    const speed = this.speed;
    this.decreaseSpeed(1, speed);
    clearInterval(this.intervalBreak);
    this.intervalBreak = setInterval(
      () => this.decreaseSpeed(1, speed),
      1000 / this.stepsPerSecond
    );
  }

  //eslint-disable-next-line @typescript-eslint/no-unused-vars
  stopDecreaseSpeed(event: PointerEvent | null = null): void {
    const pointerId = this.getPointerIdForEvent(event);
    if (pointerId === this.clickPointerId) {
      if (event && this.hasAccelerationButtons) event.preventDefault();
      clearInterval(this.intervalBreak);
      if (this.decreaseSpeedInterval === -1) {
        clearInterval(this.decreaseSpeedInterval);
        this.decreaseSpeedInterval = setInterval(
          () => this.decreaseSpeed(5),
          1000 / this.stepsPerSecond
        );
      }
      this.isDecreaseSpeedStarted = false;
    }
  }

  decreaseSpeed(
    decreaseTimeSeconds: number,
    speed: number | null = null
  ): void {
    if (this.speed > 0) {
      const totalSteps = decreaseTimeSeconds * this.stepsPerSecond;
      let stepSize = (speed ?? this.speed) / totalSteps;
      const minStepSize = 2;
      if (stepSize < minStepSize) stepSize = minStepSize;
      if (this.speed - stepSize > 0) {
        this.speed -= stepSize;
      } else {
        this.speed = 0;
      }
    }
  }
  //#endregion navigation

  //#region watcher
  @Watch('showMiniMap', { immediate: true })
  onShowMiniMapChanged(): void {
    if (this.showMiniMap) {
      setTimeout(() => {
        this.updateMiniMap();
      }, 500);
    }
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
    });
  }

  @Watch('moveAngle', { immediate: true })
  onMoveAngleChanged(): void {
    this.noStreet = false;
  }

  @Watch('speed', { immediate: true })
  onSpeedChanged(): void {
    if (this.moveSpeed === 0 && this.speed > 0) {
      this.start();
    } else if (this.moveSpeed > 0 && this.speed === 0) {
      this.stop();
    }
    this.moveSpeed = this.speed;
  }

  @Watch('moveSpeed', { immediate: true })
  onMoveSpeedChanged(): void {
    if (this.speed !== this.moveSpeed) this.speed = this.moveSpeed;
  }

  showDirectionInfo = false;
  mapEndInfo: [number, number] = [0, 0];
  infoRotation = 0;
  @Watch('mapDrivingPoint', { immediate: true })
  onMapDrivingPointChanged(): void {
    if (this.showMiniMap) this.updateMiniMap();
    setTimeout(() => this.calculateRouteInfoPoint(), 100);
  }
  //#endregion watcher

  //#region route
  calculateRouteInfoPoint(): void {
    if (this.movingType === MovingType.free && this.map) {
      const bounds = this.map.getBounds();
      const lineBorder = turf.lineString([
        [bounds.getWest(), bounds.getNorth()],
        [bounds.getEast(), bounds.getNorth()],
        [bounds.getEast(), bounds.getSouth()],
        [bounds.getWest(), bounds.getSouth()],
        [bounds.getWest(), bounds.getNorth()],
      ]);
      const lineGoal = turf.lineString([this.mapDrivingPoint, this.mapEnd]);
      const intersectionPoint = turf.lineIntersect(lineBorder, lineGoal);
      const coordinates =
        intersectionPoint.features.length > 0
          ? intersectionPoint.features[0].geometry.coordinates
          : [...this.mapEnd];
      this.showDirectionInfo = true; //intersectionPoint.features.length > 0;
      if (this.showDirectionInfo) {
        //const coordinates = intersectionPoint.features[0].geometry.coordinates;
        this.infoRotation =
          turf.bearing(this.mapDrivingPoint, this.mapEnd) - 90;
        if (intersectionPoint.features.length > 0) {
          const boundsWidth = bounds.getEast() - bounds.getWest();
          const boundsHeight = bounds.getNorth() - bounds.getSouth();
          const mapSize = mapUtils.getMapSize(this.map);
          const deltaHorizontal = (boundsWidth / mapSize[0]) * 40;
          const deltaVertical = (boundsHeight / mapSize[1]) * 30;
          const lineNorth = turf.lineString([
            [bounds.getWest(), bounds.getNorth() - deltaVertical],
            [bounds.getEast(), bounds.getNorth() - deltaVertical],
          ]);
          const lineEast = turf.lineString([
            [bounds.getEast() - deltaHorizontal, bounds.getNorth()],
            [bounds.getEast() - deltaHorizontal, bounds.getSouth()],
          ]);
          const lineSouth = turf.lineString([
            [bounds.getEast(), bounds.getSouth() + deltaVertical],
            [bounds.getWest(), bounds.getSouth() + deltaVertical],
          ]);
          const lineWest = turf.lineString([
            [bounds.getWest() + deltaHorizontal, bounds.getSouth()],
            [bounds.getWest() + deltaHorizontal, bounds.getNorth()],
          ]);
          if (turf.lineIntersect(lineNorth, lineGoal).features.length > 0) {
            coordinates[1] -= deltaVertical;
          } else if (
            turf.lineIntersect(lineSouth, lineGoal).features.length > 0
          ) {
            coordinates[1] += deltaVertical;
          }
          if (turf.lineIntersect(lineEast, lineGoal).features.length > 0) {
            coordinates[0] -= deltaHorizontal;
          } else if (
            turf.lineIntersect(lineWest, lineGoal).features.length > 0
          ) {
            coordinates[0] += deltaHorizontal;
          }
        }
        this.mapEndInfo = coordinates as [number, number];
      }
    }
  }

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
      setTimeout(() => {
        if (this.noStreet) {
          this.speed = 0;
        }
      }, 2000);
      return null;
    }
  }
  //#endregion route

  //#region chart
  lastChartLength = 0;
  async updateChart(): Promise<void> {
    if (this.$refs.chartRef) {
      const chartRef = this.$refs.chartRef as any;
      if (chartRef.chart && this.lastChartLength !== this.trackingData.length) {
        this.lastChartLength = this.trackingData.length;
        let period = this.distanceTraveled / this.maxDrivingDistance;
        if (period < 0.2) period = 0.2;
        this.normalizedTrackingData = normalizedTrackingData(
          this.trackingData,
          period
        );
        trackingDataToChartData(
          this.normalizedTrackingData,
          this.chartData,
          this.vehicleParameter
        );
        chartRef.chart.data = this.chartData;
        chartRef.chart.update();
      }
    }
  }

  maxChartValue = 0;
  distanceTraveled = 0;
  addDrivingDataToChart(newDrivingPoint: [number, number]): void {
    const distance = turf.distance(
      turf.point(this.mapDrivingPoint),
      turf.point(newDrivingPoint)
    );
    if (distance === 0) {
      return;
    }
    this.distanceTraveled += distance;
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
      distanceTraveled: this.distanceTraveled,
      tireWareRate: formulas.tireWareRate(
        this.moveSpeed,
        distance,
        vehicleParameter
      ),
    };
    this.trackingData.push(trackingData);
    const totalValue = configCalculation.getTotalValue(
      trackingData,
      this.vehicleParameter
    );
    if (this.maxChartValue < totalValue) this.maxChartValue = totalValue;
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
      const endOverlay = document.getElementById('overlayEndGoal');
      if (endOverlay) {
        endOverlay.style.pointerEvents = 'all';
        endOverlay.style.opacity = '1';
      }
      this.endGoalReached = true;
      clearInterval(this.intervalCalculation);
      clearInterval(this.intervalAnimation);
      clearInterval(this.intervalChart);
      clearInterval(this.decreaseSpeedInterval);
      clearInterval(this.intervalGas);
      clearInterval(this.intervalBreak);
    }
  }

  endGame() {
    this.$emit(
      'goalReached',
      this.trackingData,
      this.routePath,
      this.drivenPath,
      this.personCount,
      this.stops
    );
  }

  updateDrivingPath(path: [number, number][]): void {
    const coordinates = (this.drivenPath.features[0].geometry as any)
      .coordinates;
    coordinates.push(...path);
    this.drivenPath = turfUtils.getRouteObject(coordinates);
    /*(this.drivenPath.features[0].geometry as any).coordinates.push(...path);
    const source = this.map.getSource('drivenPath') as any;
    if (source) source.setData(this.drivenPath);*/
  }

  getSearchArea(
    speedDrivingDistance: number,
    lookBackward = false
  ): [[number, number], [number, number]] | null {
    if (this.map) {
      const mapSize = mapUtils.getMapSize(this.map);
      const bounds = this.map.getBounds();
      if (speedDrivingDistance > 0) {
        const features: turf.helpers.Feature[] = [
          turf.point(this.mapDrivingPoint),
        ];
        features.push(
          turf.destination(
            this.mapDrivingPoint,
            speedDrivingDistance,
            this.moveAngle - minToleratedAngleDeviation
          )
        );
        features.push(
          turf.destination(
            this.mapDrivingPoint,
            speedDrivingDistance,
            this.moveAngle + minToleratedAngleDeviation
          )
        );
        if (lookBackward) {
          features.push(
            turf.destination(
              this.mapDrivingPoint,
              speedDrivingDistance,
              this.moveAngle - minToleratedAngleDeviation - 180
            )
          );
          features.push(
            turf.destination(
              this.mapDrivingPoint,
              speedDrivingDistance,
              this.moveAngle + minToleratedAngleDeviation - 180
            )
          );
        }
        const envelope = turf.envelope(turf.featureCollection(features));
        const searchRegion = envelope.bbox as number[];
        const min: [number, number] = [
          searchRegion[0] < searchRegion[2] ? searchRegion[0] : searchRegion[2],
          searchRegion[1] > searchRegion[3] ? searchRegion[1] : searchRegion[3],
        ];
        const max: [number, number] = [
          searchRegion[0] > searchRegion[2] ? searchRegion[0] : searchRegion[2],
          searchRegion[1] < searchRegion[3] ? searchRegion[1] : searchRegion[3],
        ];
        const pixelPos01 = turfUtils.lngLatToPixel(
          min,
          bounds,
          mapSize[0],
          mapSize[1]
        );
        const pixelPos02 = turfUtils.lngLatToPixel(
          max,
          bounds,
          mapSize[0],
          mapSize[1]
        );
        return [pixelPos01, pixelPos02];
      }
    }
    return null;
  }

  getPossibleStreets(
    speedDrivingDistance: number,
    lookBackward = false
  ): [number, number][][] {
    const searchArea = this.getSearchArea(speedDrivingDistance, lookBackward);
    if (searchArea) {
      const pixelDelta = 0; // 2;
      return mapUtils.getStreetsInRegion(
        this.map,
        this.streetLayers,
        searchArea[0],
        searchArea[1],
        pixelDelta
      );
    }
    return [];
  }

  getPossibleCorners(
    speedDrivingDistance: number,
    lookBackward = false
  ): [number, number][] {
    const searchArea = this.getSearchArea(speedDrivingDistance, lookBackward);
    if (searchArea) {
      const pixelDelta = 0; // 2;
      return mapUtils.getCornersInRegion(
        this.map,
        this.streetLayers,
        searchArea[0],
        searchArea[1],
        pixelDelta
      );
    }
    return [];
  }

  possibleStreets: [number, number][][] = [];
  getPossibleSegments(
    //newDrivingPoint: [number, number],
    speedDrivingDistance: number,
    maxCornerDistance: number,
    maxStreetDistance: number,
    lookBackward = false
  ): {
    value: boolean;
    endPoint: [number, number];
    corner: [number, number] | null;
    subPath: [number, number][];
    distanceToSearchPoint: number;
    distanceToCorner: number | null;
  }[] {
    const possibleStreets = this.getPossibleStreets(
      speedDrivingDistance,
      lookBackward
    );
    /*this.possibleStreets.length = 0;
    this.possibleStreets.push(...possibleStreets);*/
    //console.log(possibleStreets);
    if (possibleStreets.length > 0) {
      const streets = possibleStreets.filter((street) =>
        turfUtils.isPointCloseToRoute(
          turfUtils.getRouteObject(street),
          this.mapDrivingPoint,
          maxStreetDistance
        )
      );
      /*if (lookBackward) {
        for (const point of this.searchPoints) {
          point.active = false;
        }
        const points = streets.map((item) => {
          return {
            point: item[item.length - 1],
            active: true,
            color: 'green'
          }
        });
        points.push(...streets.map((item) => {
          return {
            point: item[0],
            active: true,
            color: 'blue'
          }
        }));
        points.push({
          point: this.mapDrivingPoint,
          active: true,
          color: 'yellow'
        });
        this.searchPoints.push(...points);
      }*/
      const possibleSegments: {
        value: boolean;
        endPoint: [number, number];
        corner: [number, number] | null;
        subPath: [number, number][];
        distanceToSearchPoint: number;
        distanceToCorner: number | null;
      }[] = [];
      if (streets.length === 0) return [];
      const newDrivingPoint01 = this.getNewDrivingPoint(speedDrivingDistance);
      const newDrivingPoint02 = this.getNewDrivingPoint(-speedDrivingDistance);
      for (const street of streets) {
        let newDrivingPoint = newDrivingPoint01;
        let insideSegment = this.isDrivingAngleInsideNextSegment(
          turfUtils.getRouteObject(street),
          speedDrivingDistance,
          newDrivingPoint,
          maxCornerDistance
        ) as any;
        if (!insideSegment.value && lookBackward) {
          newDrivingPoint = newDrivingPoint02;
          insideSegment = this.isDrivingAngleInsideNextSegment(
            turfUtils.getRouteObject(street),
            speedDrivingDistance,
            newDrivingPoint,
            maxCornerDistance,
            this.moveAngle + 180
          ) as any;
        }
        if (insideSegment.value && insideSegment.subPath.length > 1) {
          const pathLength = turf.length(
            turf.lineString(insideSegment.subPath)
          );
          if (pathLength > 0) {
            insideSegment.distanceToSearchPoint = turf.distance(
              insideSegment.endPoint,
              newDrivingPoint
            );
            if (insideSegment.corner) {
              insideSegment.distanceToCorner = turf.distance(
                insideSegment.corner,
                this.mapDrivingPoint
              );
            } else insideSegment.distanceToCorner = null;
            possibleSegments.push(insideSegment);
          }
        }
      }
      return possibleSegments;
    }
    return [];
  }

  checkPossibility(speed: number): boolean {
    const speedDrivingDistance = this.getDrivingDistance(speed);
    const maxCornerDistance =
      this.movingType === MovingType.free ? 0.03 : 0.002;
    if (this.movingType === MovingType.free) {
      const possibleSegments = this.getPossibleSegments(
        speedDrivingDistance,
        maxCornerDistance,
        maxCornerDistance
      );
      return possibleSegments.length > 0;
    } else {
      const newDrivingPoint = this.getNewDrivingPoint(speedDrivingDistance);
      const insideSegment = this.isDrivingAngleInsideNextSegment(
        this.routePath,
        speedDrivingDistance,
        newDrivingPoint,
        maxCornerDistance
      );
      return insideSegment.value;
    }
  }

  noStreet = false;
  updateTraceIsRunning = false;
  accelerateEnabled = true;
  findNewPoint = true;
  async updateTrace(): Promise<void> {
    if (this.updateTraceIsRunning) return;
    if (this.openAnimationSteps > this.animationIntermediateSteps / 2) return;
    this.updateTraceIsRunning = true;

    const speedDrivingDistance = this.getDrivingDistance();

    if (this.isMoving && this.moveSpeed && this.controlDirection) {
      let newDrivingPoint = this.getNewDrivingPoint(speedDrivingDistance);
      let isOnRoute = !this.noStreet;
      let subPath: [number, number][] = [];
      const maxGoalDistance =
        this.movingType === MovingType.free ? 0.015 : 0.005;
      const maxCornerDistance =
        this.movingType === MovingType.free ? 0.03 : 0.002;
      if (this.movingType === MovingType.free) {
        const possibleSegments = this.getPossibleSegments(
          speedDrivingDistance,
          maxCornerDistance,
          maxCornerDistance
        );
        if (possibleSegments.length > 0) {
          const bestSegment = possibleSegments.sort(
            (a, b) => a.distanceToSearchPoint - b.distanceToSearchPoint
          )[0];
          subPath = bestSegment.subPath;
          newDrivingPoint = bestSegment.endPoint;
          this.findNewPoint = true;
        } else {
          if (this.findNewPoint) {
            this.snapToCorner(0.01);
          }
          isOnRoute = false;
          this.findNewPoint = false;
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
            this.findNewPoint = true;
          } else {
            isOnRoute = false;
            this.findNewPoint = false;
          }
        }
      }
      this.addDrivingDataToChart(newDrivingPoint);
      if (isOnRoute) {
        this.updateDrivingPoint(newDrivingPoint, subPath, maxGoalDistance);
      } else {
        this.noStreet = true;
        setTimeout(() => {
          if (this.noStreet) {
            this.speed = 0;
          }
        }, 2000);
        const checkMarkDistance = 0.01;
        this.checkRoutePoint = this.getNewDrivingPoint(checkMarkDistance);
      }
    } else if (this.isMoving && this.moveSpeed && !this.controlDirection) {
      const newDrivingPoint = turfUtils.moveAlongPath(
        this.routePath,
        this.mapDrivingPoint,
        speedDrivingDistance
      ).location;
      this.moveAngle = turfUtils.getRotation(
        this.mapDrivingPoint,
        newDrivingPoint
      );
      const maxGoalDistance = 0.005;
      const subPath = turfUtils.getSubRouteCoordinates(
        this.routePath,
        this.mapDrivingPoint,
        newDrivingPoint
      );
      this.addDrivingDataToChart(newDrivingPoint);
      this.updateDrivingPoint(newDrivingPoint, subPath, maxGoalDistance);
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
    this.updateDrivingPath(segmentPath);
    this.mapVehiclePoint = segmentPath[segmentPath.length - 1];
  }
  //#endregion animation
  calculateSpeed(type: string): number {
    switch (type) {
      case 'max':
        return maxSpeed(this.trackingData);
      case 'average':
        return averageSpeed(this.trackingData);
      case 'consumption':
        return consumption(this.trackingData);
      case 'distance':
        return distance(this.trackingData);
      case 'persons':
        return persons(this.trackingData);
    }
    return 0;
  }

  updateTime = true;
  timeDriven = 0;
  calculateTime(): number {
    if (this.updateTime) {
      this.timeDriven = Math.round((Date.now() - this.startTime) / 1000);
      if (this.endGoalReached) {
        this.updateTime = false;
      }
    }
    return this.timeDriven;
  }
}
</script>

<style lang="scss" scoped>
.overlay-top-left {
  position: absolute;
  z-index: 100;
  top: 1rem;
  left: 1rem;

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
  top: 1rem;
  right: 1rem;
  font-size: var(--font-size-xxxlarge);
  color: var(--pin-color);
  text-align: right;
}

.overlay-bottom {
  position: absolute;
  z-index: 100;
  bottom: 0.5rem;
  right: 1rem;
  left: 1rem;
  //padding: 1rem;
}

.overlay-info {
  text-align: center;
  position: relative;
  font-size: var(--font-size-large);
  font-weight: var(--font-weight-semibold);
  right: 0;
  display: block;
  padding: 0.4rem 0.5rem;
  border-radius: var(--border-radius-small);
  border: 4px solid var(--color-dark-contrast);
  margin-top: 0.3rem;
  background-color: var(--color-background);
}

.overlay-bottom-left {
  position: absolute;
  z-index: 100;
  bottom: 1rem;
  left: 1rem;
  //padding: 1rem;
}

.overlay-bottom-right {
  position: absolute;
  z-index: 100;
  bottom: 1rem;
  right: 1rem;
  display: flex;
  justify-content: flex-end;
  align-content: flex-end;
  flex-direction: row;
  //padding: 0 0 1rem 1rem;

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
  --pin-color: var(--color-red);
  font-size: var(--font-size-xxxlarge);
  color: var(--pin-color);
}

.noEntry {
  font-size: var(--font-size-xlarge);
  color: var(--color-red);
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
  //border: var(--color-evaluating) solid 2px;
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

  .previewImage {
    font-size: var(--font-size-xxxlarge);
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
    background-image: radial-gradient(#ffffff, #dddddd);
    //background: white;
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

.gas {
  border: 4px solid var(--color-dark-contrast);
  width: 4rem;
  height: 7rem;
}
.brake {
  border: 4px solid var(--color-dark-contrast);
  width: 3rem;
  height: 4rem;
  margin-right: 0.3rem;
  align-self: flex-end;
  justify-self: flex-end;
}

.accelerationFactor {
  font-weight: var(--font-weight-bold);
}

.accelerationFactor::v-deep(.el-slider__button) {
  border-radius: 20% 20% 50% 50%;
  border: 4px solid var(--color-dark-contrast);
  background-color: var(--acceleration-color);
  width: 1.5rem;
  height: 2rem;
}

#overlayEndGoal {
  display: flex;
  position: absolute;
  height: 100%;
  width: 100%;
  top: 0;

  opacity: 0;
  pointer-events: none;
  transition: all 0.5s ease-in-out;

  flex-direction: column;
  align-items: center;
  padding: 4rem 2rem 2rem;

  z-index: 1000;
  .overlayEndGoalBackground {
    background-color: var(--color-background);
    top: 0;
    position: absolute;
    opacity: 85%;
    width: 100%;
    height: 100%;
    z-index: 0;
  }
  h1 {
    z-index: 1;
  }
  h2 {
    z-index: 1;
    text-align: center;
  }
  .el-button {
    position: absolute;
    z-index: 1;
    bottom: 3rem;
  }
}
</style>

<style lang="scss">
.miniMap {
  .maplibregl-ctrl-bottom-right {
    display: none;
  }
}
</style>
