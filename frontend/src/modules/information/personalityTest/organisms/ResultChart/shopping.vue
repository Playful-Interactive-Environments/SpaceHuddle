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
  <div class="chartContainer" :style="{ height: `${pointChartHeight}rem` }">
    <Bar
      v-if="chartType === ResultChartType.STATISTIC"
      id="pointChartRef"
      ref="pointChartRef"
      :data="pointChartData"
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
import { ShoppingType } from '@/modules/information/personalityTest/types/ShoppingType';

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
  ResultChartType = ResultChartType;

  chartData: any = {
    labels: [],
    datasets: [],
  };

  pointChartData: any = {
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

  get pointChartHeight(): number {
    const headHeight = 5;
    const itemHeight = 3;
    const calcHeight =
      this.pointChartData.labels.length * itemHeight + headHeight;
    const minHeight = headHeight + itemHeight * 2;
    if (calcHeight < minHeight) return minHeight;
    return calcHeight;
  }

  get ResultTypeList(): string[] {
    return getResultTypeList(this.test);
  }

  getTypeColor(resultType: ShoppingType): string {
    switch (resultType) {
      case ShoppingType.BARGAIN:
        return themeColors.getPlayingColor();
      case ShoppingType.DELIBERATE:
        return themeColors.getGreenColor();
      case ShoppingType.IMPULSIVE:
        return themeColors.getYellowColor();
      case ShoppingType.BRANDS:
        return themeColors.getRedColor();
    }
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

  resultTypeCount: { [key: string]: number } = {};
  pointCount: { [key: string]: { [key: number]: number } } = {};
  possiblePoints: number[] = [];
  updateState(result: TaskParticipantState[]): void {
    for (const resultType of this.ResultTypeList) {
      this.resultTypeCount[resultType] = 0;
      this.pointCount[resultType] = {};
    }
    for (const state of result) {
      const resultType = state.parameter.resultType;
      if (resultType) {
        this.resultTypeCount[resultType] += 1;
        const points = parseInt(state.parameter.points);
        if (points) {
          if (!this.possiblePoints.includes(points))
            this.possiblePoints.push(points);
          if (this.pointCount[resultType][points])
            this.pointCount[resultType][points] += 1;
          else this.pointCount[resultType][points] = 1;
        }
      }
    }
    this.possiblePoints.sort((a, b) => a - b);
    if (this.resultData) {
      this.chartData.labels = this.resultData.labels;
      this.chartData.datasets = this.resultData.datasets;
      this.updateChart();
    }
    if (this.pointResultData) {
      this.pointChartData.labels = this.pointResultData.labels;
      this.pointChartData.datasets = this.pointResultData.datasets;
      this.updateChart();
    }
  }

  get resultData(): any {
    const datasets: any[] = [];
    datasets.push({
      label: '',
      data: Object.values(this.resultTypeCount),
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

  get pointResultData(): any {
    const datasets: any[] = [];
    for (const resultType of this.ResultTypeList) {
      datasets.push({
        label: this.$t(
          `module.information.personalityTest.${this.test}.result.${resultType}.name`
        ),
        data: this.possiblePoints.map(
          (points) => this.pointCount[resultType][points] ?? 0
        ),
        borderRadius: 5,
        borderSkipped: false,
        backgroundColor: this.getTypeColor(resultType as ShoppingType),
        color: themeColors.getContrastColor(),
      });
    }
    return {
      labels: this.possiblePoints,
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
      if (this.$refs.pointChartRef) {
        const chartRef = this.$refs.pointChartRef as any;
        if (chartRef.chart) {
          chartRef.chart.data = this.pointChartData;
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
