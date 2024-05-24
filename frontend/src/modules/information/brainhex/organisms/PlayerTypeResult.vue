<template>
  <div class="chartContainer" :style="{ height: `${chartHeight}rem` }">
    <Bar
      id="chartRef"
      ref="chartRef"
      :data="chartData"
      :options="{
        maintainAspectRatio: false,
        responsive: true,
        indexAxis: 'y',
        animation: {
          duration: update ? 0 : 2000,
        },
        scales: {
          y: {
            ticks: {
              color: contrastColor,
              precision: 0,
            },
            grid: {
              display: false,
            },
            stacked: true,
          },
          x: {
            ticks: {
              precision: 0,
            },
          },
        },
        plugins: {
          legend: {
            display: true,
            position: 'top',
            align: 'end',
            labels: {
              boxWidth: 30,
              boxHeight: 30,
              color: contrastColor,
            },
          },
        },
      }"
    />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Bar } from 'vue-chartjs';
import { v4 as uuidv4 } from 'uuid';
import { delay } from '@/utils/wait';
import * as themeColors from '@/utils/themeColors';
import * as cashService from '@/services/cash-service';
import * as taskParticipantService from '@/services/task-participant-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { TaskParticipantState } from '@/types/api/TaskParticipantState';
import { PlayerType } from '@/modules/information/brainhex/types/PlayerType';

@Options({
  components: {
    Bar,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PlayerTypeResult extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: false }) readonly update!: boolean;

  chartData: any = {
    labels: [],
    datasets: [],
  };

  get contrastColor(): string {
    return themeColors.getContrastColor();
  }

  get chartHeight(): number {
    const headHeight = 5;
    const itemHeight = 3;
    const calcHeight = this.chartData.labels.length * itemHeight + headHeight;
    const minHeight = headHeight + itemHeight * 2;
    if (calcHeight < minHeight) return minHeight;
    return calcHeight;
  }

  unmounted(): void {
    cashService.deregisterAllGet(this.updateState);
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    cashService.deregisterAllGet(this.updateState);
    taskParticipantService.registerGetList(
      this.taskId,
      this.updateState,
      EndpointAuthorisationType.MODERATOR,
      120
    );
  }

  playerTypeCount: { [key: string]: number } = {};
  playerTypeCountSecondary: { [key: string]: number } = {};
  updateState(result: TaskParticipantState[]): void {
    for (const playerType of Object.values(PlayerType)) {
      this.playerTypeCount[playerType] = 0;
      this.playerTypeCountSecondary[playerType] = 0;
    }
    for (const state of result) {
      if (state.parameter.playerTypes) {
        this.playerTypeCount[state.parameter.playerTypes[0]] += 1;
        this.playerTypeCountSecondary[state.parameter.playerTypes[1]] += 1;
      }
    }
    if (this.resultData) {
      this.chartData.labels = this.resultData.labels;
      this.chartData.datasets = this.resultData.datasets;
      this.updateChart();
    }
  }

  get resultData(): any {
    const datasets = [
      {
        label: this.$t('module.information.brainhex.participant.primary'),
        data: Object.values(this.playerTypeCount),
        borderRadius: 5,
        borderSkipped: false,
        backgroundColor: themeColors.getGreenColor(),
        color: themeColors.getContrastColor(),
      },
      {
        label: this.$t('module.information.brainhex.participant.secondary'),
        data: Object.values(this.playerTypeCountSecondary),
        borderRadius: 5,
        borderSkipped: false,
        backgroundColor: themeColors.getYellowColor(),
        color: themeColors.getContrastColor(),
      },
    ];
    return {
      labels: Object.keys(this.playerTypeCount).map((item) =>
        this.$t(`module.information.brainhex.enum.playerType.${item}`)
      ),
      datasets: datasets,
    };
  }

  lastUpdateCall = '';
  async updateChart(): Promise<void> {
    const uuid = uuidv4();
    this.lastUpdateCall = uuid;
    await delay(100);
    if (uuid === this.lastUpdateCall) {
      if (this.$refs.chartRef) {
        const chartRef = this.$refs.chartRef as any;
        if (chartRef.chart) {
          chartRef.chart.data = this.chartData;
          chartRef.chart.update();
        }
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.chartContainer {
  width: 100%;
}
</style>
