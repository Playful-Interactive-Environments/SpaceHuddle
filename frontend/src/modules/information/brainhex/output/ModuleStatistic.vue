<template>
  <PlayerTypeResult :task-id="taskId" :show-exception="true" />
  <PlayerTypeResult :task-id="taskId" :show-all="true" />
  <PlayerTypeResult :task-id="taskId" :show-details="true" />
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
import PlayerTypeResult from '@/modules/information/brainhex/organisms/PlayerTypeResult.vue';
import * as themeColors from '@/utils/themeColors';
import { Bar } from 'vue-chartjs';
import { ChartData } from 'chart.js/dist/types';
import * as cashService from '@/services/cash-service';
import * as taskParticipantService from '@/services/task-participant-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { TaskParticipantState } from '@/types/api/TaskParticipantState';
import { PlayerType } from '@/modules/information/brainhex/types/PlayerType';

@Options({
  components: { PlayerTypeResult, Bar },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModuleStatistic extends Vue {
  @Prop() readonly taskId!: string;

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

  updateState(result: TaskParticipantState[]): void {
    const distribution: { [key: string]: number[] } = {};
    for (const playerType of Object.values(PlayerType)) {
      distribution[playerType] = [];
    }
    for (const state of result) {
      if (state.parameter.playerTypeValues) {
        for (const item of Object.keys(state.parameter.playerTypeValues)) {
          distribution[item].push(state.parameter.playerTypeValues[item]);
        }
      }
    }
    for (const playerType of Object.values(PlayerType)) {
      distribution[playerType].sort((a, b) => b - a);
      this.barChartDataList.push({
        title: this.$t(
          `module.information.brainhex.enum.playerType.${playerType}`
        ),
        data: {
          labels: distribution[playerType],
          datasets: [
            {
              data: distribution[playerType],
              borderRadius: 5,
              borderSkipped: false,
              backgroundColor: distribution[playerType].map((item) => {
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
