<template>
  <div class="chartArea">
    <el-carousel
      height="12rem"
      :interval="30000"
      trigger="click"
      indicator-position="outside"
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
                  color: '#ffffff',
                },
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
                position: 'right',
                labels: {
                  color: '#ffffff',
                },
              },
            },
            scales: {
              x: {
                ticks: {
                  color: '#ffffff',
                },
                stacked: true,
              },
              y: {
                ticks: {
                  color: '#ffffff',
                },
                stacked: true,
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
                  color: '#ffffff',
                },
              },
            },
            scales: {
              x: {
                ticks: {
                  color: '#ffffff',
                },
                stacked: true,
              },
              y: {
                ticks: {
                  color: '#ffffff',
                },
                stacked: true,
              },
            },
          }"
        />
      </el-carousel-item>
    </el-carousel>
  </div>
  <el-space wrap>
    <el-button
      v-for="vehicle of Object.keys(gameConfig.vehicles)"
      :key="vehicle"
      type="primary"
      size="large"
      @click="vehicleTypeClicked(vehicle)"
      :class="{ active: vehicle === activeVehicleType }"
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
  </el-space>
  <div>
    <el-carousel :autoplay="false" arrow="always" height="30rem">
      <el-carousel-item
        v-for="vehicle of gameConfig.vehicles[activeVehicleType].types"
        :key="vehicle.name"
      >
        <el-card class="vehicle-card">
          <template #header>
            <div class="card-header media is-mobile">
              <div class="media-content">
                {{
                  $t(
                    `module.playing.moveit.enums.vehicles.${activeVehicleType}.${vehicle.name}`
                  )
                }}
              </div>
              <div class="media-right">
                <font-awesome-icon icon="coins" />
                {{
                  getPointsForVehicle({
                    category: activeVehicleType,
                    type: vehicle.name,
                  })
                }}
                / {{ maxVehiclePoints }}
              </div>
            </div>
          </template>
          <div class="spritesheet-container">
            <img
              class="vehicle-spritesheet"
              :src="`/assets/games/moveit/vehicle/spritesheets/${vehicle.image}`"
              :alt="vehicle.name"
            />
          </div>
          <!--          <img
            class="vehicle-image"
            :src="`/assets/games/moveit/vehicle/${vehicle.image}`"
            :alt="vehicle.name"
          />-->
          <div>
            <el-rate
              :max="3"
              :model-value="
                getStarsForVehicle({
                  category: activeVehicleType,
                  type: vehicle.name,
                })
              "
              disabled
            />
          </div>
          <div v-if="vehicle.fuel">
            {{
              $t('module.playing.moveit.participant.vehicle-parameter.fuel')
            }}:
            {{ $t(`module.playing.moveit.enums.fuel.${vehicle.fuel}`) }}
          </div>
          <div v-if="vehicle.mpg">
            {{ $t('module.playing.moveit.participant.vehicle-parameter.mpg') }}:
            {{ vehicle.mpg }}
            <span v-if="vehicle.fuel === 'electricity'">
              {{ $t('module.playing.moveit.enums.units.kw') }}
            </span>
            <span v-else>
              {{ $t('module.playing.moveit.enums.units.liters') }}
            </span>
          </div>
          <div v-if="vehicle.power">
            {{
              $t('module.playing.moveit.participant.vehicle-parameter.power')
            }}:
            {{ vehicle.power }}
            {{ $t('module.playing.moveit.enums.units.ps') }}
          </div>
          <div v-if="vehicle.speed">
            {{
              $t('module.playing.moveit.participant.vehicle-parameter.speed')
            }}:
            {{ vehicle.speed }}
            {{ $t('module.playing.moveit.enums.units.km/h') }}
          </div>
          <div v-if="vehicle.acceleration">
            {{
              $t(
                'module.playing.moveit.participant.vehicle-parameter.acceleration'
              )
            }}:
            {{ vehicle.acceleration }}
            {{ $t('module.playing.moveit.enums.units.seconds') }}
          </div>
          <div v-if="vehicle.persons">
            {{
              $t('module.playing.moveit.participant.vehicle-parameter.persons')
            }}:
            {{ vehicle.persons }}
          </div>
          <div>
            <el-button
              type="primary"
              @click="selectVehicle(activeVehicleType, vehicle.name)"
            >
              {{ $t('module.playing.moveit.participant.select') }}
            </el-button>
          </div>
        </el-card>
      </el-carousel-item>
    </el-carousel>
  </div>
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

export interface ChartData {
  labels: string[];
  datasets: {
    label: string;
    backgroundColor: string | string[];
    data: number[];
  }[];
}

@Options({
  components: {
    FontAwesomeIcon,
    Doughnut,
    Bar,
  },
  emits: ['play'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class SelectChallenge extends Vue {
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
  gameConfig = gameConfig;
  selectedVehicle: vehicleCalculation.Vehicle = {
    category: 'car',
    type: 'sport',
  };

  get selectedVehicleParameter(): any {
    return configCalculation.getVehicleParameter(this.selectedVehicle);
  }

  get maxVehiclePoints(): number {
    return this.trackingManager.getStarPoints(3);
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

  mounted(): void {
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
    for (const particleSource of Object.keys(gameConfig.particles)) {
      const dsElectricity = {
        label: this.$t(
          `module.playing.moveit.enums.particle.${particleSource}`
        ),
        backgroundColor: gameConfig.particles[particleSource].color,
        data: [] as number[],
      };

      for (const energySource of Object.keys(gameConfig.electricity)) {
        dsElectricity.data.push(
          gameConfig.electricity[energySource].perUnit[particleSource]
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
    setTimeout(() => {
      this.updateChart();
    }, 1000);
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
    this.activeVehicleType = vehicle;
  }

  selectVehicle(
    category: 'motorcycle' | 'bus' | 'car' | 'scooter' | 'bike',
    type: string
  ): void {
    this.selectedVehicle.category = category;
    this.selectedVehicle.type = type;
    this.$emit('play', this.selectedVehicle);
  }
}
</script>

<style scoped lang="scss">
.chartArea {
  padding: 1rem;
  height: 15rem;
  width: 100%;

  background-image: url('~@/modules/playing/moveit/assets/chartsbg.png');
  background-size: cover; //contain;
  background-position: 50%;
  background-repeat: no-repeat;
  background-color: var(--color-dark-contrast);
  background-blend-mode: overlay;
}

.el-space {
  padding: 1rem;

  .el-button.active {
    --el-button-bg-color: var(--color-structuring);
  }
}

.vehicle-card {
  width: calc(100%);
  text-align: center;

  .card-header {
    font-weight: var(--font-weight-bold);
    font-size: var(--font-size-large);
    //text-align: center;
    //display: block;
  }
}

.media-right {
  padding-right: 0.5rem;
}

/*.vehicle-image {
  height: 10rem;
}*/

/*spritesheet functionality:*/

.spritesheet-container {
  height: 6rem;
  aspect-ratio: 3.07 / 1;
  overflow: hidden;
  margin: auto auto 1rem;
  max-width: 100%;
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
    object-position: left -6rem;
  }
  75% {
    object-position: right -6rem;
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
</style>
