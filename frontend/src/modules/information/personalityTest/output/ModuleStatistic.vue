<template>
  <ResultTypeResult
    :task-id="taskId"
    :chart-type="ResultChartType.STATISTIC"
    :test="test"
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
import ResultTypeResult from '@/modules/information/personalityTest/organisms/ResultTypeResult.vue';
import * as themeColors from '@/utils/themeColors';
import { Bar } from 'vue-chartjs';
import { ChartData } from 'chart.js/dist/types';
import * as cashService from '@/services/cash-service';
import * as taskParticipantService from '@/services/task-participant-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { TaskParticipantState } from '@/types/api/TaskParticipantState';
import { getResultTypeList } from '@/modules/information/personalityTest/types/ResultType';
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';
import { ResultChartType } from '@/modules/information/personalityTest/types/ResultChartType';

@Options({
  components: { ResultTypeResult, Bar },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModuleStatistic extends Vue {
  @Prop() readonly taskId!: string;

  ResultChartType = ResultChartType;

  task: Task | null = null;
  test = '';

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
    cashService.deregisterAllGet(this.updateTask);
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    cashService.deregisterAllGet(this.updateState);
    cashService.deregisterAllGet(this.updateTask);
    taskParticipantService.registerGetList(
      this.taskId,
      this.updateState,
      EndpointAuthorisationType.MODERATOR,
      120
    );

    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.MODERATOR,
      60 * 60
    );
  }

  updateTask(task: Task): void {
    this.task = task;
    this.test = task.modules[0].parameter.test;
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
            console.log(distribution, item);
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
