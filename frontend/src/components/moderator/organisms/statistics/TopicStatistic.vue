<template>
  <Bar
    id="chartRef"
    ref="chartRef"
    :data="resultData"
    :height="100"
    :options="{
      animation: {
        duration: 0,
      },
      scales: {
        x: {
          ticks: {
            color: '#1d2948',
          },
          grid: {
            display: false,
          },
        },
        y: {
          ticks: {
            color: '#1d2948',
            stepSize: 1,
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
import {Options, Vue} from 'vue-class-component';
import {Prop, Watch} from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as taskParticipantService from '@/services/task-participant-service';
import {TaskParticipantStateSum} from '@/types/api/TaskParticipantState';
import {Bar} from 'vue-chartjs';
import * as cashService from '@/services/cash-service';
import TaskType from "@/types/enum/TaskType";

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

  getTypeColor(taskType: TaskType): string {
    switch (TaskType[taskType]) {
      case TaskType.VOTING:
        return '#fe6e5d';
      case TaskType.BRAINSTORMING:
        return '#01cf9e';
      case TaskType.INFORMATION:
        return '#f3a40a';
      case TaskType.CATEGORISATION:
      case TaskType.SELECTION:
        return '#0192d0';
    }
    return '#999999';
  }

  get resultData(): any {
    const datasets = [
      {
        data: this.stateList.map((state) => state.count),
        borderRadius: 5,
        borderSkipped: false,
        backgroundColor: this.stateList.map((state) => this.getTypeColor(state.taskType)),
        color: '#1d2948',
      },
    ];
    return {
      labels: this.stateList.map((state) => state.name),
      datasets: datasets,
    };
  }

  @Watch('topicId', { immediate: true })
  onTaskIdChanged(): void {
    cashService.deregisterAllGet(this.updateState);
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

  unmounted() {
    cashService.deregisterAllGet(this.updateState);
  }
}
</script>

<style scoped lang="scss"></style>
