<template>
  <DetailResult
    :task-id="taskId"
    :show-exception="chartType !== ResultChartType.PUBLIC"
  />
  <DetailResult
    v-if="chartType === ResultChartType.STATISTIC"
    :task-id="taskId"
    :show-all="true"
  />
  <DetailResult
    v-if="chartType === ResultChartType.STATISTIC"
    :task-id="taskId"
    :show-details="true"
  />
  <Bar
    v-for="(chartData, index) in barChartDataList"
    :key="index"
    :data="chartData.data"
    :height="80"
    :options="{
      maintainAspectRatio: true,
      animation: {
        duration: 0,
      },
      scales: {
        x: {
          display: false,
          ticks: {
            color: chartData.labelColors,
          },
          grid: {
            display: false,
          },
          stacked: true,
        },
        y: {
          ticks: {
            color: contrastColor,
            precision: 0,
          },
          stacked: true,
          grid: {
            color: (context) => {
              if (context.tick.value < 0) {
                return redColor;
              }
              if (context.tick.value > 5) {
                return greenColor;
              }
              if (context.tick.value > 0) {
                return yellowColor;
              }

              return '#000000';
            },
          },
        },
      },
      plugins: {
        legend: {
          display: chartData.data.datasets.length > 1,
        },
        title: {
          display: true,
          text: chartData.title,
        },
      },
    }"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import DetailResult from '@/modules/information/personalityTest/organisms/ResultChart/brainhex/DetailResult.vue';
import { ResultChartType } from '@/modules/information/personalityTest/types/ResultChartType';
import { Bar } from 'vue-chartjs';
import { ChartData } from 'chart.js';
import { TaskParticipantState } from '@/types/api/TaskParticipantState';
import * as themeColors from '@/utils/themeColors';
import * as cashService from '@/services/cash-service';
import * as taskParticipantService from '@/services/task-participant-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { getResultTypeList } from '@/modules/information/personalityTest/types/ResultType';

@Options({
  components: { DetailResult, Bar },
  emits: [],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ResultChart extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: ResultChartType.MODERATOR })
  readonly chartType!: ResultChartType;

  ResultChartType = ResultChartType;
  test = 'brainhex';

  barChartDataList: {
    title: string;
    data: ChartData;
    labelColors: string[] | string;
  }[] = [];

  get contrastColor(): string {
    return themeColors.getContrastColor();
  }

  get greenColor(): string {
    return themeColors.getGreenColor();
  }

  get redColor(): string {
    return themeColors.getRedColor();
  }

  get yellowColor(): string {
    return themeColors.getYellowColor();
  }

  get ResultTypeList(): string[] {
    return getResultTypeList(this.test);
  }

  unmounted(): void {
    cashService.deregisterAllGet(this.updateState);
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.chartType === ResultChartType.STATISTIC) {
      cashService.deregisterAllGet(this.updateState);
      taskParticipantService.registerGetList(
        this.taskId,
        this.updateState,
        EndpointAuthorisationType.MODERATOR,
        120
      );
    }
  }

  updateState(result: TaskParticipantState[]): void {
    const distribution: { [key: string]: number[] } = {};
    for (const resultType of this.ResultTypeList) {
      distribution[resultType] = [];
    }
    for (const state of result) {
      if (state.parameter.resultTypeValues) {
        for (const item of Object.keys(state.parameter.resultTypeValues)) {
          if (distribution[item]) {
            distribution[item].push(state.parameter.resultTypeValues[item]);
          } else {
            distribution[item] = [state.parameter.resultTypeValues[item]];
          }
        }
      }
    }
    for (const resultType of this.ResultTypeList) {
      distribution[resultType].sort((a, b) => b - a);
      this.barChartDataList.push({
        title: this.$t(
          `module.information.personalityTest.${this.test}.result.${resultType}.name`
        ),
        data: {
          labels: distribution[resultType],
          datasets: [
            {
              data: distribution[resultType],
              borderRadius: 5,
              borderSkipped: false,
              backgroundColor: distribution[resultType].map((item) => {
                if (item < 0) return themeColors.getRedColor();
                if (item > 5) return themeColors.getGreenColor();
                return themeColors.getYellowColor();
              }),
            },
          ],
        },
        labelColors: themeColors.getContrastColor(),
      });
    }
  }
}
</script>

<style lang="scss" scoped></style>
