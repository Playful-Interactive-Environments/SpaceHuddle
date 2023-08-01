<template>
  <Bar
    id="chartRef"
    ref="chartRef"
    :data="resultData"
    :height="100"
    :options="{
      maintainAspectRatio: true,
      animation: {
        duration: 0,
      },
      scales: {
        x: {
          display: displayLabels,
          ticks: {
            color: labelColors,
          },
          grid: {
            display: false,
          },
        },
        y: {
          ticks: {
            color: contrastColor,
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
import { AvatarUnicode } from '@/types/enum/AvatarUnicode';
import * as themeColors from '@/utils/themeColors';

@Options({
  components: {
    Bar,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TaskStatistic extends Vue {
  @Prop() taskId!: string;
  stateList: TaskParticipantState[] = [];
  displayLabels = false;
  labelColors: string[] = [];

  get contrastColor(): string {
    return themeColors.getContrastColor();
  }

  mounted(): void {
    document.fonts.onloadingdone = () => {
      this.displayLabels = true;
    };
    setTimeout(() => (this.displayLabels = true), 5000);
  }

  get resultData(): any {
    const datasets = [
      {
        data: this.stateList.map((state) => state.count),
        borderRadius: 5,
        borderSkipped: false,
        backgroundColor: this.stateList.map((state) => state.avatar.color),
        color: themeColors.getContrastColor(),
      },
    ];
    return {
      labels: this.stateList.map((state) => {
        return AvatarUnicode[state.avatar.symbol];
      }),
      datasets: datasets,
    };
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
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
    this.labelColors = this.stateList.map((state) => state.avatar.color);
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
