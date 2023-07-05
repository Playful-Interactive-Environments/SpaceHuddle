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
                yMin: maxCleanupThreshold,
                yMax: maxCleanupThreshold,
                borderColor: 'rgb(255, 99, 132)',
                borderWidth: 2,
              },
              box1: {
                type: 'box',
                xMin: 0,
                xMax: trackingData.length,
                yMin: maxCleanupThreshold,
                yMax:
                  maxChartValue > maxCleanupThreshold
                    ? maxChartValue + 1
                    : maxCleanupThreshold,
                backgroundColor: 'rgba(255, 99, 132, 0.25)',
                borderColor: 'rgb(255, 99, 132)',
              },
            },
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
              :src="`/assets/games/cleanup/vehicle/${vehicleParameter.imageTop}`"
              alt="car"
              :width="20"
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
            :size="150"
            :stick-size="15"
            @move="move($event)"
            @start="start"
            @stop="stop"
            stickColor="white"
            base-color="rgba(0, 0, 0, 0.1)"
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
      <!--<mgl-marker
        v-for="(searchPoint, index) in [...searchPoints].reverse()"
        :key="index"
        :coordinates="searchPoint"
        :color="searchPointColors[index]"
      ></mgl-marker>
      <CustomMapMarker :coordinates="corner">
        <template v-slot:icon>
          <font-awesome-icon icon="up-down-left-right" />
        </template>
      </CustomMapMarker>
      <mgl-marker
        v-for="(searchPoint, index) in testPoints"
        :key="index"
        :coordinates="searchPoint"
        :color="searchPointColors[index]"
        :scale="1 - index / (testPoints.length * 2)"
      ></mgl-marker>-->
    </mgl-map>
    <div class="overlay-top-left">
      <el-progress
        type="dashboard"
        :percentage="(moveSpeed / vehicleParameter.speed) * 100"
        :color="colors"
      >
        <template #default>
          <div>{{ Math.round(moveSpeed) }} km/h</div>
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
      <el-slider v-model="maxSpeed" :max="vehicleParameter.speed" />
      <!--<div>
        <Joystick
          :size="150"
          :stick-size="50"
          @move="move($event)"
          @start="start"
          @stop="stop"
          stickColor="white"
        />
      </div>-->
    </div>
  </div>
  <!--<div ref="controlArea" class="controlArea level">
    <div class="level-item">
      <el-slider
        v-model="speed"
        vertical
        height="8rem"
        :max="vehicleParameter.speed"
      />
    </div>
    <div class="level-item">
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
    <div class="level-item">
      <round-slider
        v-model="direction"
        max="360"
        start-angle="90"
        end-angle="+360"
        line-cap="round"
        radius="70"
      />
    </div>
  </div>-->
  <!--<Application
    ref="pixi"
    :width="gameWidth"
    :height="controlHeight"
    v-if="ready"
    backgroundColor="#f4f4f4"
  >
    <container backgroundColor="red">
      <sprite
        texture="/assets/games/cleanup/steering-wheel.png"
        :width="controlHeight"
        :height="controlHeight"
      ></sprite>
    </container>
  </Application>-->
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import Joystick from 'vue-joystick-component';
import { Line } from 'vue-chartjs';
import * as gameConfig from '@/modules/information/cleanup/data/gameConfig.json';
import { OsrmCustom as OSRM, OSRMWayPoint } from '@/utils/osrm';
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
import { Application } from 'vue3-pixi';
import RoundSlider from 'vue-three-round-slider/src';
import { TaskParticipantState } from '@/types/api/TaskParticipantState';
import { TaskParticipantIteration } from '@/types/api/TaskParticipantIteration';
import * as constants from '@/modules/information/cleanup/utils/consts';
//import * as tiles from `https://api.maptiler.com/tiles/v3/tiles.json?key=${process.env.VUE_APP_MAPTILER_KEY}`;

MglDefaults.style = `https://api.maptiler.com/maps/streets/style.json?key=${process.env.VUE_APP_MAPTILER_KEY}`;
//MglDefaults.style = 'http://localhost/tileserver-php/osm-2020-02-10-v3/';

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
  emits: [
    'update:useFullSize',
    'goalReached',
    'update:trackingState',
    'update:trackingIteration',
  ],
})
export default class DriveToLocation extends Vue {
  @Prop() readonly trackingState!: TaskParticipantState;
  @Prop() readonly trackingIteration!: TaskParticipantIteration;
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
  animationPoints: [number, number][][] = [];
  map!: Map;
  ready = false;
  gameWidth = 0;
  controlHeight = 0;

  direction = 0;
  speed = 0;
  maxSpeed = 0;

  isMoving = false;
  moveSpeed = 0;
  moveDirection: [number, number] = [0, 0];
  moveAngle = 0;
  checkRoutePoint: [number, number] = [0, 0];

  boardingPersons = 0;

  readonly maxCleanupThreshold = constants.maxCleanupThreshold;

  readonly intervalCalculationTime = 100;
  intervalCalculation = -1;
  readonly intervalAnimationTime = 30;
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

  initTrackingData(): void {
    if (this.trackingIteration && !this.trackingIteration.parameter.drive) {
      this.trackingIteration.parameter.drive = {
        stops: 0,
      };
      this.$emit('update:trackingIteration', this.trackingIteration);
    }
  }

  async mounted(): Promise<void> {
    this.initTrackingData();
    this.gameWidth = this.$el.parentElement.offsetWidth;
    if (this.$refs.controlArea)
      this.controlHeight = (this.$refs.controlArea as HTMLElement).offsetHeight;
    this.ready = true;
    this.maxSpeed = this.vehicleParameter.speed;

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
    if (this.mapEnd[0] === 0 && this.mapEnd[1] === 0) {
      const distanceX = 0.02;
      const distanceY = 0.02;
      this.mapEnd = turf.randomPosition([
        this.mapStart[0] - distanceX,
        this.mapStart[1] - distanceY,
        this.mapStart[0] + distanceX,
        this.mapStart[1] + distanceY,
      ]) as [number, number];
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
      const pathPoints = (this.routePath.features[0].geometry as any)
        .coordinates;
      this.setMapDrivingPoint(pathPoints[0], true);
      this.mapDrivingRotation = turf.bearing(
        turf.point(this.mapDrivingPoint),
        turf.point(pathPoints[1])
      );
      this.routeCalculated = true;

      this.initTrackingData();
      if (this.trackingIteration) {
        this.trackingIteration.parameter.drive.routePath = this.routePath;
        this.trackingIteration.parameter.drive.routePathLenght = turf.length(
          this.routePath
        );
        this.$emit('update:trackingIteration', this.trackingIteration);
      }
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

  start(event: any): void {
    this.boardingPersons = 0;
    this.move(event);
    this.isMoving = true;
    this.intervalCalculation = setInterval(
      this.updateTrace,
      this.intervalCalculationTime
    );
  }

  stop(): void {
    clearInterval(this.intervalCalculation);
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
          this.boardingPersons = addCount;
          this.personCount += addCount;
          busStop.persons -= addCount;
        }
      }
    }

    this.initTrackingData();
    if (this.trackingIteration) {
      this.trackingIteration.parameter.drive.stops++;
      this.$emit('update:trackingIteration', this.trackingIteration);
    }
  }

  move(event: any): void {
    const calcAngle = (point: [number, number]): number => {
      return Math.atan2(point[0], point[1]) * (180 / Math.PI);
    };

    this.moveSpeed = (event.distance / 100.0) * this.maxSpeed;
    this.moveAngle = calcAngle([event.x, event.y]);
    this.moveDirection = [event.x, event.y];
    this.noStreet = false;
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
    distance: number,
    airlinePoint: [number, number]
  ): {
    value: boolean;
    endPoint: [number, number];
    corner: [number, number] | null;
    subPath: [number, number][];
  } {
    const minMax = turfUtils.getMinMaxAngleForPathDistanceSegment(
      this.routePath,
      this.mapDrivingPoint,
      airlinePoint,
      distance,
      minToleratedAngleDeviation
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

  goalReached(): boolean {
    return turfUtils.goalReached(this.routePath, this.mapDrivingPoint);
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
    const consumption = formulas.consumption(
      this.moveSpeed,
      distance,
      vehicleParameter
    );
    const trackingData: TrackingData = {
      speed: this.moveSpeed,
      persons: this.personCount,
      consumption: consumption,
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

  updateDrivingPoint(
    newDrivingPoint: [number, number],
    subPath: [number, number][]
  ): void {
    this.addAnimationSteps(newDrivingPoint, subPath);
    this.setMapDrivingPoint(newDrivingPoint);
    if (this.goalReached()) {
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

  noStreet = false;
  updateTraceIsRunning = false;
  pauseIntervalBeforeRecalculate = 15;
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
      if (isOnRoute) {
        const insideSegment = this.isDrivingAngleInsideNextSegment(
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
          const recalculateRoute = true;
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

          if (recalculateRoute) {
            if (
              this.openAnimationSteps === 0 &&
              this.pauseCount >= this.pauseIntervalBeforeRecalculate
            ) {
              const point = await this.recalculateRoute(newDrivingPoint);
              if (point) {
                this.pauseCount = 0;
                this.noStreet = false;
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
          }
        }
      }
      this.addDrivingDataToChart(newDrivingPoint);
      if (isOnRoute) {
        this.updateDrivingPoint(newDrivingPoint, subPath);
      }

      this.initTrackingData();
      if (this.trackingIteration) {
        this.trackingIteration.parameter.drive.trackingData = this.trackingData;
        this.trackingIteration.parameter.drive.distanceToGoal =
          turfUtils.distanceToGoal(this.routePath, this.mapDrivingPoint);
        this.trackingIteration.parameter.drive.drivenPathLength = turf.length(
          this.drivenPath
        );
        this.trackingIteration.parameter.drive.drivenPath = this.drivenPath;
        this.$emit('update:trackingIteration', this.trackingIteration);
      }
    }
    this.updateTraceIsRunning = false;
  }

  animateVehicle(): void {
    if (this.animationIndex < this.animationPoints.length) {
      const segmentPath = this.animationPoints[this.animationIndex];
      this.mapVehiclePoint = segmentPath[segmentPath.length - 1];
      this.updateDrivingPath(segmentPath);
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
</style>
