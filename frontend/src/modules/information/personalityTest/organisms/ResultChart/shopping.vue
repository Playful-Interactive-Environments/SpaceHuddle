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
          duration: 2000,
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
            stacked: true,
          },
        },
        plugins: {
          legend: {
            display: false,
          },
        },
      }"
    />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Bar, Bubble } from 'vue-chartjs';
import { v4 as uuidv4 } from 'uuid';
import { delay } from '@/utils/wait';
import * as themeColors from '@/utils/themeColors';
import * as cashService from '@/services/cash-service';
import * as taskParticipantService from '@/services/task-participant-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { TaskParticipantState } from '@/types/api/TaskParticipantState';
import { getResultTypeList } from '@/modules/information/personalityTest/types/ResultType';
import { ResultChartType } from '@/modules/information/personalityTest/types/ResultChartType';

@Options({
  components: {
    Bar,
    Bubble,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ResultChart extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: ResultChartType.MODERATOR })
  readonly chartType!: ResultChartType;

  test = 'shopping';

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

  get ResultTypeList(): string[] {
    return getResultTypeList(this.test);
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

  resultTypeCount: { [key: string]: number[] } = {};
  updateState(result: TaskParticipantState[]): void {
    for (const resultType of this.ResultTypeList) {
      this.resultTypeCount[resultType] = [0, 0, 0, 0, 0, 0, 0];
    }
    for (const state of result) {
      if (state.parameter.resultType) {
        for (let i = 0; i < this.ResultTypeList.length; i++) {
          this.resultTypeCount[state.parameter.resultType][i] += 1;
        }
      }
    }
    if (this.resultData) {
      this.chartData.labels = this.resultData.labels;
      this.chartData.datasets = this.resultData.datasets;
      this.updateChart();
    }
  }

  get resultData(): any {
    const datasets: any[] = [];
    datasets.push({
      label: '',
      data: Object.keys(this.resultTypeCount).map(
        (item) => this.resultTypeCount[item][0]
      ),
      borderRadius: 5,
      borderSkipped: false,
      backgroundColor: themeColors.getYellowColor(),
      color: themeColors.getContrastColor(),
    });
    return {
      labels: Object.keys(this.resultTypeCount).map((item) =>
        this.$t(
          `module.information.personalityTest.${this.test}.result.${item}.name`
        )
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
