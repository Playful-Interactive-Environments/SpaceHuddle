<template>
  <Bar
    id="chartRef"
    ref="chartRef"
    :data="resultData"
    :height="100"
    :options="{
      maintainAspectRatio: false,
      animation: {
        duration: 0,
      },
      scales: {
        x: {
          ticks: {
            color: contrastColor,
          },
          grid: {
            display: false,
          },
        },
        y: {
          ticks: {
            color: contrastColor,
            precision: 0,
          },
        },
      },
      plugins: {
        legend: {
          display: false,
        },
      },
    }"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as taskParticipantService from '@/services/task-participant-service';
import { TaskParticipantStateSum } from '@/types/api/TaskParticipantState';
import { Bar } from 'vue-chartjs';
import * as cashService from '@/services/cash-service';
import TaskType from '@/types/enum/TaskType';
import * as themeColors from '@/utils/themeColors';

@Options({
  components: {
    Bar,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TaskStatistic extends Vue {
  @Prop() topicId!: string;
  stateList: TaskParticipantStateSum[] = [];

  chartData: any = {
    labels: [],
    datasets: [],
  };

  get contrastColor(): string {
    return themeColors.getContrastColor();
  }

  getTypeColor(taskType: TaskType): string {
    switch (TaskType[taskType]) {
      case TaskType.VOTING:
        return themeColors.getEvaluatingColor();
      case TaskType.BRAINSTORMING:
        return themeColors.getBrainstormingColor();
      case TaskType.INFORMATION:
        return themeColors.getInformingColor();
      case TaskType.PLAYING:
        return themeColors.getPlayingColor();
      case TaskType.CATEGORISATION:
      case TaskType.SELECTION:
        return themeColors.getStructuringColor();
    }
    return themeColors.getInactiveColor();
  }

  get resultData(): any {
    const datasets = [
      {
        data: this.stateList.map((state) => state.count),
        borderRadius: 5,
        borderSkipped: false,
        backgroundColor: this.stateList.map((state) =>
          this.getTypeColor(state.taskType)
        ),
        color: themeColors.getContrastColor(),
      },
    ];
    return {
      labels: this.stateList.map((state) => state.name),
      datasets: datasets,
    };
  }

  @Watch('topicId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    taskParticipantService.registerGetListFromTopic(
      this.topicId,
      this.updateState,
      EndpointAuthorisationType.MODERATOR,
      2 * 60
    );
  }

  updateState(stateList: TaskParticipantStateSum[]): void {
    this.stateList = stateList;
    this.updateChart();
  }

  async updateChart(): Promise<void> {
    if (this.$refs.chartRef) {
      const chartRef = this.$refs.chartRef as any;
      if (chartRef.chart) {
        chartRef.chart.data = this.resultData;
        chartRef.chart.update();
      }
    }
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateState);
  }

  unmounted(): void {
    this.deregisterAll();
  }
}
</script>

<style scoped lang="scss"></style>
