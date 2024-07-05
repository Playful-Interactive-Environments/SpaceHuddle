<template>
  <div v-if="!hasVehicleHistory">
    {{ $t('module.playing.moveit.participant.historyEmpty') }}
  </div>
  <div v-else>
    <el-carousel
      :autoplay="false"
      indicator-position="none"
      arrow="always"
      class="history-carousel"
      @change="historyPageChanged"
    >
      <el-carousel-item v-for="(step, index) of activeStepsList" :key="step.id">
        <h1 class="history-vehicle">
          {{
            $t(
              `module.playing.moveit.enums.vehicles.${step.parameter.vehicle.category}.${step.parameter.vehicle.type}`
            )
          }}
          {{ index + 1 }} / {{ activeStepsList.length }}
        </h1>
        <h2 class="heading heading--regular">
          {{ $t('module.playing.moveit.participant.drivingStats.time') }} :
          {{ Math.round((calculateSpeed('driveTime') / 60000) * 100) / 100 }}
          {{ $t('module.playing.moveit.enums.units.min') }}
        </h2>
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
          {{ $t('module.playing.moveit.participant.drivingStats.consumption') }}
          :
          {{ Math.round(calculateSpeed('consumption') * 1000) / 1000 }}
          <span v-if="vehicle.vehicle.fuel === 'electricity'">
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
      </el-carousel-item>
    </el-carousel>
    <h2>
      {{ $t('module.playing.moveit.participant.info.distance.title') }}
    </h2>
    <div class="chartArea">
      <Line
        ref="distanceSpeed"
        :data="chartDataDistance"
        :options="{
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: {
              title: {
                text: $t(
                  'module.playing.moveit.participant.info.distance.scale.x'
                ),
                display: true,
              },
            },
            y: {
              stacked: true,
              title: {
                text: $t(
                  'module.playing.moveit.participant.info.distance.scale.y'
                ),
                display: true,
              },
            },
          },
        }"
      />
    </div>
    <h2>
      {{ $t('module.playing.moveit.participant.info.speed.title') }}
    </h2>
    <div class="chartArea">
      <Line
        ref="chartSpeed"
        :data="chartDataSpeed"
        :options="{
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: {
              title: {
                text: $t(
                  'module.playing.moveit.participant.info.speed.scale.x'
                ),
                display: true,
              },
            },
            y: {
              stacked: true,
              title: {
                text: $t(
                  'module.playing.moveit.participant.info.speed.scale.y'
                ),
                display: true,
              },
            },
          },
        }"
      />
    </div>
    <h2
      v-if="
        activeHistoryStep &&
        activeHistoryStep.parameter.vehicle.category === 'bus'
      "
    >
      {{ $t('module.playing.moveit.participant.info.persons.title') }}
    </h2>
    <div
      class="chartArea"
      v-if="
        activeHistoryStep &&
        activeHistoryStep.parameter.vehicle.category === 'bus'
      "
    >
      <Line
        ref="chartPersons"
        :data="chartDataPersons"
        :options="{
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: {
              title: {
                text: $t(
                  'module.playing.moveit.participant.info.persons.scale.x'
                ),
                display: true,
              },
            },
            y: {
              stacked: true,
              title: {
                text: $t(
                  'module.playing.moveit.participant.info.persons.scale.y'
                ),
                display: true,
              },
            },
          },
        }"
      />
    </div>
    <h2>
      {{ $t('module.playing.moveit.participant.info.emissions.input') }}
    </h2>
    <div class="chartArea">
      <Line
        ref="chartInput"
        :data="chartDataInput"
        :options="{
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: {
              title: {
                text: $t(
                  'module.playing.moveit.participant.info.emissions.scale.x'
                ),
                display: true,
              },
            },
            y: {
              stacked: true,
              title: {
                text: $t(
                  'module.playing.moveit.participant.info.emissions.scale.y'
                ),
                display: true,
              },
            },
          },
          plugins: {
            annotation: {
              annotations: {
                box1: {
                  type: 'box',
                  xMin: 0,
                  xMax: chartDataInput.labels.length,
                  yMin: maxCleanupThreshold,
                  yMax: calcChartHeight(maxChartValue),
                  backgroundColor: highlightColorTransparent,
                  borderColor: highlightColor,
                },
              },
            },
          },
        }"
      />
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as taskParticipantService from '@/services/task-participant-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import { Line } from 'vue-chartjs';
import {
  maxSpeed,
  averageSpeed,
  consumption,
  distance,
  normalizedTrackingData,
  persons,
} from '@/modules/playing/moveit/utils/trackingData';
import * as gameConfig from '@/modules/playing/moveit/data/gameConfig.json';
import * as configCalculation from '@/modules/playing/moveit/utils/configCalculation';
import * as constants from '@/modules/playing/moveit/utils/consts';
import * as themeColors from '@/utils/themeColors';

/* eslint-disable @typescript-eslint/no-explicit-any*/
interface DatasetData {
  name: string;
  label: string;
  backgroundColor: string;
  borderColor: string;
  data: number[];
  fill: any;
}

interface VehicleData {
  vehicle: any;
  category: string;
}

@Options({
  components: {
    Line,
  },
  emits: [],
})
export default class ParticipantHistory extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly vehicle!: VehicleData;
  steps: TaskParticipantIterationStep[] = [];

  chartDataInput: {
    labels: string[];
    datasets: DatasetData[];
  } = {
    labels: [],
    datasets: [],
  };
  chartDataPersons: {
    labels: string[];
    datasets: any[];
  } = {
    labels: [],
    datasets: [],
  };
  chartDataDistance: {
    labels: string[];
    datasets: any[];
  } = {
    labels: [],
    datasets: [],
  };
  chartDataSpeed: {
    labels: string[];
    datasets: any[];
  } = {
    labels: [],
    datasets: [],
  };

  maxChartValue = 0;
  readonly maxCleanupThreshold = constants.maxCleanupThreshold;
  readonly calcChartHeight = constants.calcChartHeight;
  gameConfig = gameConfig;

  get highlightColorTransparent(): string {
    return themeColors.convertToRGBA(themeColors.getHighlightColor(), 0.25);
  }

  get highlightColor(): string {
    return themeColors.convertToRGBA(themeColors.getHighlightColor());
  }

  get activeHistoryStep(): TaskParticipantIterationStep | null {
    if (this.activeStepsList.length > this.activeHistoryIndex)
      return this.activeStepsList[this.activeHistoryIndex];
    return null;
  }

  get vehicleList(): VehicleData[] {
    const list: VehicleData[] = [];
    for (const category of Object.keys(gameConfig.vehicles)) {
      list.push(
        ...gameConfig.vehicles[category].types.map((vehicle) => {
          return {
            vehicle: vehicle,
            category: category,
          };
        })
      );
    }
    return list;
  }

  get activeStepsList(): TaskParticipantIterationStep[] {
    return this.steps.filter(
      (item) =>
        item.parameter.vehicle.category === this.vehicle.category &&
        item.parameter.vehicle.type === this.vehicle.vehicle.name
    );
  }

  get hasVehicleHistory(): boolean {
    return (
      this.steps.filter(
        (item) =>
          item.parameter.vehicle.category === this.vehicle.category &&
          item.parameter.vehicle.type === this.vehicle.vehicle.name
      ).length > 0
    );
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      taskParticipantService.registerGetIterationStepList(
        this.taskId,
        this.updateIterationSteps,
        EndpointAuthorisationType.MODERATOR,
        2 * 60
      );
    }
  }

  updateIterationSteps(steps: TaskParticipantIterationStep[]): void {
    this.steps = steps.filter(
      (item) =>
        item.parameter.drive.trackingData &&
        item.parameter.drive.trackingData.length > 0
    );
    this.historyPageChanged(this.activeHistoryIndex);
  }

  activeHistoryIndex = 0;
  historyPageChanged(index: number): void {
    if (this.activeStepsList.length <= index) return;
    this.activeHistoryIndex = index;
    let distanceTraveled = 0;
    for (const data of this.activeStepsList[index].parameter.drive
      .trackingData) {
      distanceTraveled += data.distance;
      data.distanceTraveled = distanceTraveled;
    }
    const normalizedData = normalizedTrackingData(
      this.activeStepsList[index].parameter.drive.trackingData
    );

    const driveTime = this.activeStepsList[index].parameter.driveTime;
    this.chartDataInput.labels =
      this.chartDataPersons.labels =
      this.chartDataDistance.labels =
      this.chartDataSpeed.labels =
        normalizedData.map((data, index) =>
          Math.round(
            (driveTime / 1000 / normalizedData.length) * index
          ).toString()
        );

    this.chartDataPersons.datasets = [];
    this.chartDataInput.datasets = [];
    this.chartDataDistance.datasets = [];
    this.chartDataSpeed.datasets = [];

    this.chartDataSpeed.datasets.push({
      name: 'speed',
      label: 'speed',
      data: normalizedData.map((data) => Math.round(data.speed)),
    });

    this.chartDataDistance.datasets.push({
      name: 'distance',
      label: 'distance',
      data: normalizedData.map(
        (data) => Math.round(data.distanceTraveled * 100) / 100
      ),
    });

    let chartRef = this.$refs.chartSpeed as any;
    if (chartRef && chartRef.chart) {
      chartRef.chart.data = this.chartDataSpeed;
      chartRef.chart.update();
    }

    chartRef = this.$refs.distanceSpeed as any;
    if (chartRef && chartRef.chart) {
      chartRef.chart.data = this.chartDataDistance;
      chartRef.chart.update();
    }

    this.chartDataPersons.datasets.push({
      name: 'persons',
      label: 'persons',
      data: normalizedData.map((data) => Math.round(data.persons)),
    });

    chartRef = this.$refs.chartPersons as any;
    if (chartRef && chartRef.chart) {
      chartRef.chart.data = this.chartDataPersons;
      chartRef.chart.update();
    }

    for (const particleName of Object.keys(
      this.activeStepsList[index].parameter.particleState
    )) {
      const particle = gameConfig.particles[particleName];
      const timelineInput = normalizedData.map((data) => {
        return configCalculation.statisticsValue(
          particleName,
          data,
          this.getVehicleParameter(
            this.activeStepsList[index].parameter.vehicle
          )
        );
      });
      this.chartDataInput.datasets.push({
        name: particleName,
        label: this.getParticleDisplayName(particleName),
        data: timelineInput,
        backgroundColor: particle.color,
        borderColor: particle.color,
        fill: {
          target: 'stack',
          above: `${particle.color}77`,
        },
      });
    }

    chartRef = this.$refs.chartInput as any;
    if (chartRef && chartRef.chart) {
      chartRef.chart.data = this.chartDataInput;
      chartRef.chart.update();
    }
  }

  getParticleDisplayName(particleName: string): string {
    return this.$t(`module.playing.moveit.enums.particle.${particleName}`);
  }

  getVehicleParameter(vehicle: any): any {
    return configCalculation.getVehicleParameter(vehicle);
  }

  calculateSpeed(type: string): number {
    const trackingData =
      this.activeStepsList[this.activeHistoryIndex].parameter.drive
        .trackingData;
    switch (type) {
      case 'max':
        return maxSpeed(trackingData);
      case 'average':
        return averageSpeed(trackingData);
      case 'consumption':
        return consumption(trackingData);
      case 'distance':
        return distance(trackingData);
      case 'persons':
        return persons(trackingData);
      case 'driveTime':
        return this.activeStepsList[this.activeHistoryIndex].parameter
          .driveTime;
    }
    return 0;
  }
}
</script>

<style lang="scss" scoped>
.el-carousel::v-deep(.el-carousel__container) {
  height: 12rem;
}

.history-carousel {
  //height: 15rem;
}

.history-vehicle {
  font-weight: var(--font-weight-semibold);
  font-size: 1.2rem;
  margin-bottom: 0.5rem;
  text-transform: uppercase;
}

.chartArea {
  height: 20%;
  min-height: 10rem;
}

.category-selection {
  .el-button {
    margin-right: 0.5rem;
  }

  .el-button.active {
    --el-button-bg-color: var(--color-structuring);
  }
}
</style>
