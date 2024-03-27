<template>
  <nav class="level is-mobile">
    <div class="level-left category-selection">
      <el-button
        v-for="vehicle of Object.keys(gameConfig.vehicles)"
        :key="vehicle"
        type="primary"
        size="large"
        @click="vehicleTypeClicked(vehicle)"
        class="level-item"
        :class="{ active: vehicle === activeVehicle?.category }"
      >
        <font-awesome-icon
          v-if="!gameConfig.vehicles[vehicle].iconPrefix"
          :icon="gameConfig.vehicles[vehicle].icon"
        />
        <font-awesome-icon
          v-else
          :icon="[
            gameConfig.vehicles[vehicle].iconPrefix,
            gameConfig.vehicles[vehicle].icon,
          ]"
        />
      </el-button>
    </div>
    <div class="level-right">
      <el-button
        type="primary"
        size="large"
        @click="showInfo = true"
        class="level-item"
      >
        <font-awesome-icon icon="circle-info" />
      </el-button>
    </div>
  </nav>
  <div ref="gameContainer" class="gameContainer">
    <el-carousel
      v-if="ready"
      ref="carousel"
      :autoplay="false"
      indicator-position="none"
      arrow="always"
      :height="`${targetHeight}px`"
      @change="activeVehicleChanged"
    >
      <el-carousel-item
        v-for="vehicle of vehicleList"
        :key="vehicle.vehicle.name"
      >
      </el-carousel-item>
    </el-carousel>
    <div class="vehicle-info">
      <el-card class="vehicle-card">
        <template #header>
          <div class="card-header media is-mobile">
            <div class="media-content">
              {{
                $t(
                  `module.playing.moveit.enums.vehicles.${activeVehicle.category}.${activeVehicle.vehicle.name}`
                )
              }}
            </div>
            <div class="media-right">
              {{ $t('module.playing.moveit.participant.earned') + ': ' }}
              <font-awesome-icon icon="coins" />
              {{
                getPointsForVehicle({
                  category: activeVehicle.category,
                  type: activeVehicle.vehicle.name,
                })
              }}
              / {{ maxVehiclePoints }}
            </div>
            <el-button
              type="primary"
              size="large"
              @click="showHistory = true"
              class="level-item"
            >
              <font-awesome-icon icon="chart-line" />
            </el-button>
          </div>
        </template>
        <div class="vehicle-image">
          <div ref="vehicleSpace" class="spritesheet-container">
            <SpriteCanvas
              v-if="vehicleWidth && activeVehicle.animation.length > 0"
              :texture="activeVehicle.animation"
              :width="vehicleWidth"
              :height="vehicleHeight"
              background-color="#ffffff"
            >
            </SpriteCanvas>
          </div>
          <div>
            <el-rate
              :max="3"
              :model-value="
                getStarsForVehicle({
                  category: activeVehicle.category,
                  type: activeVehicle.vehicle.name,
                })
              "
              disabled
            />
          </div>
        </div>
        <!--          <img
          class="vehicle-image"
          :src="`/assets/games/moveit/vehicle/${activeVehicle.vehicle.image}`"
          :alt="activeVehicle.vehicle.name"
        />-->
        <div v-if="activeVehicle.vehicle.fuel">
          {{ $t('module.playing.moveit.participant.vehicle-parameter.fuel') }}:
          {{
            $t(`module.playing.moveit.enums.fuel.${activeVehicle.vehicle.fuel}`)
          }}
        </div>
        <div v-if="activeVehicle.vehicle.mpg">
          {{ $t('module.playing.moveit.participant.vehicle-parameter.mpg') }}:
          {{ activeVehicle.vehicle.mpg }}
          <span v-if="activeVehicle.vehicle.fuel === 'electricity'">
            {{ $t('module.playing.moveit.enums.units.kw') }}
          </span>
          <span v-else>
            {{ $t('module.playing.moveit.enums.units.liters') }}
          </span>
        </div>
        <div v-if="activeVehicle.vehicle.power">
          {{ $t('module.playing.moveit.participant.vehicle-parameter.power') }}:
          {{ activeVehicle.vehicle.power }}
          {{ $t('module.playing.moveit.enums.units.ps') }}
        </div>
        <div v-if="activeVehicle.vehicle.speed">
          {{ $t('module.playing.moveit.participant.vehicle-parameter.speed') }}:
          {{ activeVehicle.vehicle.speed }}
          {{ $t('module.playing.moveit.enums.units.km/h') }}
        </div>
        <div v-if="activeVehicle.vehicle.acceleration">
          {{
            $t(
              'module.playing.moveit.participant.vehicle-parameter.acceleration'
            )
          }}:
          {{ activeVehicle.vehicle.acceleration }}
          {{ $t('module.playing.moveit.enums.units.seconds') }}
        </div>
        <div v-if="activeVehicle.vehicle.persons">
          {{
            $t('module.playing.moveit.participant.vehicle-parameter.persons')
          }}:
          {{ activeVehicle.vehicle.persons }}
        </div>
        <div>
          <el-button
            type="primary"
            @click="
              selectVehicle(activeVehicle.category, activeVehicle.vehicle.name)
            "
          >
            {{ $t('module.playing.moveit.participant.select') }}
          </el-button>
        </div>
      </el-card>
    </div>
  </div>
  <DrawerBottomOverlay
    v-model="showInfo"
    :title="$t(`module.playing.moveit.participant.info.${activeTabName}.title`)"
  >
    <div class="chartArea">
      <el-carousel
        height="12rem"
        :interval="30000"
        trigger="click"
        arrow="always"
        indicator-position="outside"
        @change="carouselChanged"
      >
        <el-carousel-item>
          <Doughnut
            ref="chartElectricityMixRef"
            :data="chartDataElectricityMix"
            :options="{
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'right',
                  labels: {
                    color: '#000000',
                  },
                },
                title: {
                  display: true,
                  text: $t(
                    'module.playing.moveit.participant.info.electricity.scale'
                  ),
                },
              },
            }"
          />
        </el-carousel-item>
        <el-carousel-item>
          <Bar
            ref="chartElectricityRef"
            :data="chartDataElectricity"
            :options="{
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  display: false,
                  position: 'right',
                  labels: {
                    color: '#000000',
                  },
                },
              },
              scales: {
                x: {
                  ticks: {
                    color: '#000000',
                  },
                  stacked: true,
                  title: {
                    text: $t(
                      'module.playing.moveit.participant.info.emissionsPerElectricitySource.scale.x'
                    ),
                    display: true,
                  },
                },
                y: {
                  ticks: {
                    color: '#000000',
                  },
                  stacked: true,
                  title: {
                    text: $t(
                      'module.playing.moveit.participant.info.emissionsPerElectricitySource.scale.y'
                    ),
                    display: true,
                  },
                },
              },
            }"
          />
        </el-carousel-item>
        <el-carousel-item>
          <Bar
            ref="chartFuelRef"
            :data="chartDataFuel"
            :options="{
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'right',
                  labels: {
                    color: '#000000',
                  },
                },
              },
              scales: {
                x: {
                  ticks: {
                    color: '#000000',
                  },
                  stacked: true,
                  title: {
                    text: $t(
                      'module.playing.moveit.participant.info.emissionsPerFuel.scale.x'
                    ),
                    display: true,
                  },
                },
                y: {
                  ticks: {
                    color: '#000000',
                  },
                  stacked: true,
                  title: {
                    text: $t(
                      'module.playing.moveit.participant.info.emissionsPerFuel.scale.y'
                    ),
                    display: true,
                  },
                },
              },
            }"
          />
        </el-carousel-item>
      </el-carousel>
      <div>
        {{
          $t(
            `module.playing.moveit.participant.info.${activeTabName}.description`
          )
        }}
      </div>
    </div>
  </DrawerBottomOverlay>
  <DrawerBottomOverlay
    v-if="activeVehicle"
    v-model="showHistory"
    :title="$t(`module.playing.moveit.participant.history`)"
  >
    <ParticipantHistory
      :task-id="taskId"
      :vehicle="activeVehicle"
    />
  </DrawerBottomOverlay>
  <el-dialog v-model="showPlayDialog">
    <template #header>
      <h1 v-if="selectedVehicle">
        {{
          $t(
            `module.playing.moveit.enums.vehicles.${selectedVehicle.category}.${selectedVehicle.type}`
          )
        }}
      </h1>
    </template>
    <div>
      <el-radio-group v-model="navigationType">
        <!-- :disabled="hasNavigationType(level)" -->
        <el-radio-button
          v-for="level in NavigationTypeList"
          :key="level"
          :label="level"
        >
          <span class="icon">
            <font-awesome-icon
              v-for="icon in getNavigationIcon(level)"
              :key="icon"
              :icon="['fac', icon]"
            />
          </span>
          <!--{{
            $t(
              `module.playing.moveit.participant.navigationType.${level}.title`
            )
          }}-->
        </el-radio-button>
      </el-radio-group>
    </div>
    <div class="info">
      {{
        $t(
          `module.playing.moveit.participant.navigationType.${navigationType}.description`
        )
      }}
    </div>
    <div v-if="freeStylePossible">
      <el-radio-group v-model="movingType">
        <el-radio-button
          v-for="level in MovingType"
          :key="level"
          :label="level"
        >
          <span class="icon">
            <font-awesome-icon :icon="['fac', getMovingIcon(level)]" />
          </span>
          <!--{{
            $t(`module.playing.moveit.participant.movingType.${level}.title`)
          }}-->
        </el-radio-button>
      </el-radio-group>
    </div>
    <div v-if="freeStylePossible" class="info">
      {{
        $t(
          `module.playing.moveit.participant.movingType.${movingType}.description`
        )
      }}
    </div>
    <template #footer>
      <el-button @click="cancelGame" class="dialog-button">
        {{ $t('module.playing.moveit.participant.playDialog.cancel') }}
      </el-button>
      <el-button @click="startGame" class="dialog-button">
        {{ $t('module.playing.moveit.participant.playDialog.play') }}
      </el-button>
    </template>
  </el-dialog>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Doughnut, Bar } from 'vue-chartjs';
import * as gameConfig from '@/modules/playing/moveit/data/gameConfig.json';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import * as configCalculation from '@/modules/playing/moveit/utils/configCalculation';
import { Prop } from 'vue-property-decorator';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import * as vehicleCalculation from '@/modules/playing/moveit/types/Vehicle';
import DrawerBottomOverlay from '@/components/participant/molecules/DrawerBottomOverlay.vue';
import SpriteCanvas from '@/components/shared/atoms/game/SpriteCanvas.vue';
import * as pixiUtil from '@/utils/pixi';
import * as PIXI from 'pixi.js';
import { registerDomElement, unregisterDomElement } from '@/vunit';
import { delay } from '@/utils/wait';
import { remToPx } from '@/modules/playing/moveit/utils/consts';
import ParticipantHistory from '@/modules/playing/moveit/organisms/ParticipantHistory.vue';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export enum NavigationType {
  speed = 'speed',
  acceleration = 'acceleration',
  speedDirection = 'speedDirection',
  accelerationDirection = 'accelerationDirection',
  joystick = 'joystick',
}

export enum MovingType {
  path = 'path',
  free = 'free',
}

export interface ChartData {
  labels: string[];
  datasets: {
    label: string;
    backgroundColor: string | string[];
    data: number[];
  }[];
}

interface VehicleData {
  vehicle: any;
  category: string;
  animation: PIXI.Texture[];
}

@Options({
  components: {
    ParticipantHistory,
    SpriteCanvas,
    FontAwesomeIcon,
    Doughnut,
    Bar,
    DrawerBottomOverlay,
  },
  emits: ['play'],
})
export default class SelectChallenge extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly trackingManager!: TrackingManager;
  chartDataElectricityMix: ChartData = {
    labels: [],
    datasets: [],
  };
  chartDataElectricity: ChartData = {
    labels: [],
    datasets: [],
  };
  chartDataFuel: ChartData = {
    labels: [],
    datasets: [],
  };
  activeVehicleType: 'motorcycle' | 'bus' | 'car' | 'scooter' | 'bike' = 'car';
  activeVehicleIndex = 0;
  gameConfig = gameConfig;
  selectedVehicle: vehicleCalculation.Vehicle = {
    category: 'car',
    type: 'sport',
  };
  showInfo = false;
  showHistory = false;
  showPlayDialog = false;
  navigationType = NavigationType.speed;
  NavigationTypeList = [
    NavigationType.speed,
    NavigationType.speedDirection,
    NavigationType.joystick,
  ];
  movingType = MovingType.path;
  MovingType = MovingType;
  textureToken = pixiUtil.createLoadingToken();

  get activeVehicle(): VehicleData | null {
    if (this.vehicleList.length > this.activeVehicleIndex)
      return this.vehicleList[this.activeVehicleIndex];
    return null;
  }

  get selectedVehicleParameter(): any {
    return configCalculation.getVehicleParameter(this.selectedVehicle);
  }

  get maxVehiclePoints(): number {
    return this.trackingManager.getStarPoints(3);
  }

  getMovingIcon(movingType: MovingType): string {
    switch (movingType) {
      case MovingType.free:
        return 'freestyle';
      case MovingType.path:
        return 'path';
    }
    return 'path';
  }

  getNavigationIcon(navigationType: NavigationType): string[] {
    switch (navigationType) {
      case NavigationType.speed:
        return ['speed'];
      case NavigationType.acceleration:
        return ['acceleration'];
      case NavigationType.speedDirection:
        return ['speed', 'joystick'];
      case NavigationType.accelerationDirection:
        return ['acceleration', 'joystick'];
      case NavigationType.joystick:
        return ['joystick'];
    }
    return ['joystick'];
  }

  hasNavigationType(navigationType: NavigationType): boolean {
    switch (navigationType) {
      case NavigationType.speed:
      case NavigationType.acceleration:
        if (
          this.navigationType === navigationType &&
          this.movingType === MovingType.free
        )
          this.navigationType = NavigationType.speedDirection;
        return this.movingType === MovingType.free;
      case NavigationType.speedDirection:
      case NavigationType.accelerationDirection:
      case NavigationType.joystick:
        return false;
    }
    return false;
  }

  get freeStylePossible(): boolean {
    return (
      this.navigationType !== NavigationType.speed &&
      this.navigationType !== NavigationType.acceleration
    );
  }

  getStarsForVehicle(vehicle: vehicleCalculation.Vehicle): number {
    const vehicleSteps = this.trackingManager.stepList
      .filter((item) =>
        vehicleCalculation.isSameVehicle(item.parameter.vehicle, vehicle)
      )
      .sort((a, b) => b.parameter.rate - a.parameter.rate);
    if (vehicleSteps.length > 0) {
      return vehicleSteps[0].parameter.rate;
    }
    return 0;
  }

  getPointsForVehicle(vehicle: vehicleCalculation.Vehicle): number {
    return this.trackingManager.getStarPoints(this.getStarsForVehicle(vehicle));
  }

  activeTabName = 'electricity';
  carouselChanged(index: number): void {
    switch (index) {
      case 0:
        this.activeTabName = 'electricity';
        break;
      case 1:
        this.activeTabName = 'emissionsPerElectricitySource';
        break;
      case 2:
        this.activeTabName = 'emissionsPerFuel';
        break;
    }
  }

  vehicleWidth = 0;
  vehicleHeight = 0;
  getAnimationForVehicle(vehicle: any): PIXI.Texture[] {
    if (this.vehicleSpritesheet) {
      const animationName = vehicle.image.slice(0, -4);
      return this.vehicleSpritesheet.animations[animationName];
    }
    return [];
    /*const result: PIXI.Texture[] = [];
    if (this.vehicleSpritesheet) {
      const keys = Object.keys(this.vehicleSpritesheet.textures)
        .filter((key) => key.includes(vehicle.image.slice(0, -4)))
        .sort((a, b) => a.localeCompare(b));
      for (const key of keys) {
        result.push(this.vehicleSpritesheet.textures[key]);
      }
    }
    return result;*/
  }

  get vehicleList(): VehicleData[] {
    const list: VehicleData[] = [];
    for (const category of Object.keys(gameConfig.vehicles)) {
      list.push(
        ...gameConfig.vehicles[category].types.map((vehicle) => {
          return {
            vehicle: vehicle,
            category: category,
            animation: this.getAnimationForVehicle(vehicle),
          };
        })
      );
    }
    return list;
  }

  targetWidth = 100;
  targetHeight = 100;
  vehicleSpritesheet: PIXI.Spritesheet | null = null;
  ready = false;
  domKeys: string[] = [];
  mounted(): void {
    pixiUtil
      .loadTexture(
        '/assets/games/moveit/vehicle/vehicle_animation.json',
        this.textureToken
      )
      .then(async (sheet) => {
        this.vehicleSpritesheet = sheet;
        for (const vehicle of this.vehicleList) {
          if (vehicle.animation.length === 0)
            vehicle.animation = this.getAnimationForVehicle(vehicle.vehicle);
        }
        this.ready = true;

        await delay(100);
        const dom = this.$refs.vehicleSpace as HTMLElement;
        if (dom) {
          this.domKeys.push(
            registerDomElement(
              dom,
              () => {
                this.vehicleWidth = dom.offsetWidth - 20;
                this.vehicleHeight = dom.offsetHeight;
              },
              100,
              false,
              () => {
                this.vehicleWidth = 100;
                this.vehicleHeight = 100;
              }
            )
          );
        }
      });

    this.chartDataElectricityMix.datasets.push({
      label: this.$t('module.playing.moveit.participant.electricity'),
      backgroundColor: [],
      data: [],
    });
    for (const energySource of Object.keys(gameConfig.electricity)) {
      this.chartDataElectricityMix.labels.push(
        this.$t(`module.playing.moveit.enums.electricity.${energySource}`)
      );
      this.chartDataElectricityMix.datasets[0].data.push(
        configCalculation.getElectricityValue(energySource)
      );
      (
        this.chartDataElectricityMix.datasets[0].backgroundColor as string[]
      ).push(gameConfig.electricity[energySource].color);
    }
    this.chartDataFuel.datasets.push({
      backgroundColor: '#ffffff',
      label: this.$t(
        'module.playing.moveit.participant.vehicle-parameter.fuel'
      ),
      data: [],
    });
    for (const fuelSource of Object.keys(gameConfig.fuel)) {
      if ('carbonDioxideEquivalent' in gameConfig.fuel[fuelSource].perUnit) {
        this.chartDataFuel.labels.push(
          this.$t(`module.playing.moveit.enums.fuel.${fuelSource}`)
        );
        this.chartDataFuel.datasets[0].data.push(
          (gameConfig.fuel[fuelSource].perUnit['carbonDioxideEquivalent'] /
            gameConfig.fuel[fuelSource].perUnit.kwh) *
            (1 / gameConfig.fuel[fuelSource].perUnit.efficiency)
        );
      }
    }
    this.chartDataFuel.labels.push(
      this.$t('module.playing.moveit.enums.fuel.electricity')
    );
    for (const particleSource of ['carbonDioxideEquivalent']) {
      //Object.keys(gameConfig.particles)) {
      const dsElectricity = {
        label: this.$t(
          `module.playing.moveit.enums.particle.${particleSource}`
        ),
        backgroundColor: [] as string[], //gameConfig.particles.carbonDioxide.color,
        //backgroundColor: gameConfig.particles[particleSource].color,
        data: [] as number[],
      };

      for (const energySource of Object.keys(gameConfig.electricity)) {
        dsElectricity.data.push(
          gameConfig.electricity[energySource].perUnit[particleSource]
        );
        dsElectricity.backgroundColor.push(
          gameConfig.electricity[energySource].color
        );
      }
      this.chartDataElectricity.datasets.push(dsElectricity);
    }
    for (const energySource of Object.keys(gameConfig.electricity)) {
      this.chartDataElectricity.labels.push(
        this.$t(`module.playing.moveit.enums.electricity.${energySource}`)
      );
      const ds = {
        label: this.$t(
          `module.playing.moveit.enums.electricity.${energySource}`
        ),
        backgroundColor: gameConfig.electricity[energySource].color,
        data: [] as number[],
      };
      for (let i = 1; i < this.chartDataFuel.labels.length; i++)
        ds.data.push(0);
      ds.data.push(
        gameConfig.electricity[energySource].perUnit[
          'carbonDioxideEquivalent'
        ] *
          (configCalculation.getElectricityValue(energySource) / 100) *
          (1 / gameConfig.fuel.electricity.perUnit.efficiency)
      );
      this.chartDataFuel.datasets.push(ds);
    }
    this.chartDataFuel.datasets[0].data.push(0);

    this.domKeys.push(
      registerDomElement(
        this.$refs.gameContainer as HTMLElement,
        (targetWidth, targetHeight) => {
          this.targetWidth = targetWidth;
          let cardWidth = targetWidth - remToPx(2);
          if (cardWidth > remToPx(30)) cardWidth = remToPx(30);
          if (cardWidth < 300) cardWidth = 300;
          if (targetHeight > cardWidth / 0.7) this.targetHeight = targetHeight;
          else this.targetHeight = cardWidth / 0.7;
        },
        100,
        false,
        () => {
          this.targetWidth = 100;
          this.targetHeight = 100;
        }
      )
    );
    setTimeout(() => {
      this.updateChart();
    }, 1000);
  }

  unmounted(): void {
    pixiUtil.cleanupToken(this.textureToken);
    for (const key of this.domKeys) {
      unregisterDomElement(key);
    }
  }

  activeVehicleChanged(index: number): void {
    this.activeVehicleIndex = index;
  }

  async updateChart(): Promise<void> {
    if (this.$refs.chartElectricityMixRef) {
      const chartRef = this.$refs.chartElectricityMixRef as any;
      if (chartRef.chart) {
        chartRef.chart.data = this.chartDataElectricityMix;
        chartRef.chart.update();
      }
    }
    if (this.$refs.chartElectricityRef) {
      const chartRef = this.$refs.chartElectricityRef as any;
      if (chartRef.chart) {
        chartRef.chart.data = this.chartDataElectricity;
        chartRef.chart.update();
      }
    }
    if (this.$refs.chartFuelRef) {
      const chartRef = this.$refs.chartFuelRef as any;
      if (chartRef.chart) {
        chartRef.chart.data = this.chartDataFuel;
        chartRef.chart.update();
      }
    }
  }

  vehicleTypeClicked(
    vehicle: 'motorcycle' | 'bus' | 'car' | 'scooter' | 'bike'
  ): void {
    const index = this.vehicleList.findIndex(
      (value) => value.category === vehicle
    );
    if (index > -1 && this.$refs.carousel) {
      (this.$refs.carousel as any).setActiveItem(index);
    }
    this.activeVehicleType = vehicle;
  }

  selectVehicle(
    category: 'motorcycle' | 'bus' | 'car' | 'scooter' | 'bike',
    type: string
  ): void {
    this.selectedVehicle.category = category;
    this.selectedVehicle.type = type;
    this.showPlayDialog = true;
  }

  startGame(): void {
    this.$emit(
      'play',
      this.selectedVehicle,
      this.navigationType,
      this.movingType
    );
  }

  cancelGame(): void {
    this.showPlayDialog = false;
  }
}
</script>

<style scoped lang="scss">
.chartArea {
  padding: 1rem 0;
  height: 15rem;
  width: 100%;
}

.category-selection {
  //padding: 1rem;

  .el-button.active {
    --el-button-bg-color: var(--color-structuring);
  }
}

.vehicle-info {
  position: absolute;
  top: 0;
  z-index: 0;
  width: 100%;
  height: 100%;
}

.vehicle-card {
  margin: 0 auto;
  width: calc(100% - 2rem);
  max-width: 30rem;
  text-align: center;
  border: solid var(--color-dark-contrast) 5px;
  border-radius: var(--border-radius);
  //aspect-ratio: 0.7;
  height: calc(100% - 0.5rem);
  max-height: calc(30rem / 0.7);

  .el-button {
    margin-top: 1rem;
  }

  .card-header {
    font-weight: var(--font-weight-bold);
    font-size: var(--font-size-large);
    padding: 0.5rem;
    background-color: var(--color-dark-contrast);
    color: #ffffff;
    border-radius: 0;
    //text-align: center;
    //display: block;

    .media-content {
      text-align: left;
    }

    .el-button {
      margin: 0;
      padding: 0;
      background-color: transparent;
      height: unset;
      min-height: unset;
      font-weight: var(--font-weight-bold);
      font-size: var(--font-size-large);
    }
  }
}

.el-card::v-deep(.el-card__body) {
  padding: 0;
  background-color: var(--color-gray);
}

.media-right {
  padding-right: 0.5rem;
}

/*.vehicle-image {
  height: 10rem;
}*/

/*spritesheet functionality:*/

.vehicle-image {
  height: calc(100% - 17rem);
  min-height: 7rem;
  background-color: #ffffff;
  display: grid;
  grid-template-rows: auto 3rem;
  align-items: baseline;
  margin-bottom: 2rem;
}

.spritesheet-container {
  width: 100%;
  height: 100%;
  //height: 7rem;
  //aspect-ratio: 3.07 / 1;
  overflow: hidden;
  margin: auto;
  //max-width: 100%;
}

.vehicle-spritesheet {
  height: 200%;
  width: 200%;
  object-position: 0 0;
  animation: spritesheet-animation 2s steps(1) infinite;
}

@keyframes spritesheet-animation {
  0% {
    object-position: left top;
  }
  25% {
    object-position: right top;
  }
  50% {
    object-position: left -7rem;
  }
  75% {
    object-position: right -7rem;
  }
}

.el-carousel::v-deep(.el-carousel__button) {
  background-color: var(--color-primary);
}

.play {
  position: fixed;
  bottom: 0;
  left: 0;
  text-align: center;
  width: 100vw;
}

.level {
  padding: 1rem;
}

.level:not(:last-child) {
  margin-bottom: 0;
}

.gameContainer {
  //height: 100%;
  position: relative;
}

.dialog-button {
  margin-left: 0.5rem;
}

.info {
  padding-top: 1rem;
  padding-bottom: 2rem;
  text-align: left;
  font-size: var(--font-size-small);
  max-width: 30rem;
}
</style>

<style lang="scss">
.levelInfo .el-dialog__body div {
  margin-bottom: 0.5rem;
}

.icon {
  font-size: var(--font-size-xxxlarge);
  margin-right: 0.5rem;
  width: unset;
  min-width: 1.5rem;
}

.el-radio-button > .el-radio-button__inner {
  padding-top: 1rem;
}
</style>
