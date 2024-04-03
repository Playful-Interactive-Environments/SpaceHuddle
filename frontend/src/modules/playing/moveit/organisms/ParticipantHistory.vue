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
        <h1>
          {{
            $t(
              `module.playing.moveit.enums.vehicles.${step.parameter.vehicle.category}.${step.parameter.vehicle.type}`
            )
          }}
          {{ index + 1 }} / {{ activeStepsList.length }}
        </h1>
      </el-carousel-item>
    </el-carousel>
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
import { normalizedTrackingData } from '@/modules/playing/moveit/utils/trackingData';
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
  chartDataSpeed: {
    labels: string[];
    datasets: any[];
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
    const labels = normalizedData.map((data) =>
      (Math.round(data.distanceTraveled * 100) / 100).toString()
    );
    this.chartDataInput.labels =
      this.chartDataSpeed.labels =
      this.chartDataPersons.labels =
        labels;

    this.chartDataSpeed.datasets = [];
    this.chartDataPersons.datasets = [];
    this.chartDataInput.datasets = [];

    this.chartDataSpeed.datasets.push({
      name: 'speed',
      label: 'speed',
      data: normalizedData.map((data) => Math.round(data.speed)),
    });

    let chartRef = this.$refs.chartSpeed as any;
    if (chartRef && chartRef.chart) {
      chartRef.chart.data = this.chartDataSpeed;
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
}
</script>

<style lang="scss" scoped>
.el-carousel::v-deep(.el-carousel__container) {
  height: 5rem;
}
.history-carousel {
  height: 5rem;
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
