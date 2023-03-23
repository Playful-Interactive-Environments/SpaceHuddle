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
          display: false,
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
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as taskParticipantService from '@/services/task-participant-service';
import { TaskParticipantState } from '@/types/api/TaskParticipantState';
import { Bar } from 'vue-chartjs';
import * as cashService from '@/services/cash-service';

@Options({
  components: {
    Bar,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TaskStatistic extends Vue {
  @Prop() taskId!: string;
  stateList: TaskParticipantState[] = [];

  chartData: any = {
    labels: [],
    datasets: [],
  };

  get resultData(): any {
    const datasets = [
      {
        data: this.stateList.map((state) => state.count),
        borderRadius: 5,
        borderSkipped: false,
        backgroundColor: this.stateList.map((state) => state.avatar.color),
        color: '#1d2948',
      },
    ];
    return {
      labels: this.stateList.map((state) => state.avatar.symbol),
      datasets: datasets,
    };
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    cashService.deregisterAllGet(this.updateState);
    taskParticipantService.registerGetList(
      this.taskId,
      this.updateState,
      EndpointAuthorisationType.MODERATOR,
      2 * 60
    );
  }

  updateState(stateList: TaskParticipantState[]): void {
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
