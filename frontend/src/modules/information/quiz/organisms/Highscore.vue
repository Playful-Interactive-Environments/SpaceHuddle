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
            display: displayLabels,
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
import { Bar } from 'vue-chartjs';
import { v4 as uuidv4 } from 'uuid';
import { delay } from '@/utils/wait';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';

import { Avatar } from '@/types/api/Participant';
import { AvatarUnicode } from '@/types/enum/AvatarUnicode';
import * as themeColors from '@/utils/themeColors';

interface AvatarResult {
  avatar: Avatar;
  count: number;
}

@Options({
  components: {
    Bar,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class QuizResult extends Vue {
  @Prop({ default: [] })
  readonly trackingResult!: TaskParticipantIterationStep[];
  @Prop({ default: false }) readonly update!: boolean;

  chartData: any = {
    labels: [],
    datasets: [],
  };
  labelLineLimit = 2;
  displayLabels = false;

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

  get result(): AvatarResult[] {
    const result: AvatarResult[] = [];
    const correctAnswers = this.trackingResult.filter(
      (step) => step.state === TaskParticipantIterationStepStatesType.CORRECT
    );
    for (const answer of correctAnswers) {
      const resultItem = result.find(
        (item) =>
          item.avatar.color === answer.avatar.color &&
          item.avatar.symbol === answer.avatar.symbol
      );
      if (resultItem) resultItem.count++;
      else {
        result.push({
          avatar: answer.avatar,
          count: 1,
        });
      }
    }
    return result.sort((a, b) => b.count - a.count);
  }

  async mounted(): Promise<void> {
    if (this.resultData) {
      this.chartData.labels = this.resultData.labels;
      this.chartData.datasets = this.resultData.datasets;
      this.updateChart();
    }
    document.fonts.onloadingdone = () => {
      this.displayLabels = true;
    };
    setTimeout(() => (this.displayLabels = true), 5000);
  }

  @Watch('trackingResult', { immediate: true })
  onTrackingResultChanged(): void {
    if (this.resultData) {
      this.chartData.labels = this.resultData.labels;
      this.chartData.datasets = this.resultData.datasets;
      this.updateChart();
    }
  }

  get resultData(): any {
    const result = this.result;
    const datasets = [
      {
        data: result.map((item) => item.count),
        borderRadius: 5,
        borderSkipped: false,
        backgroundColor: result.map((item) => item.avatar.color),
        color: themeColors.getContrastColor(),
      },
    ];
    return {
      labels: result.map((item) => AvatarUnicode[item.avatar.symbol]),
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
