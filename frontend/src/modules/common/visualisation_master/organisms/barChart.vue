<template>
  <Bar
    id="resultChart"
    ref="chartRef"
    :data="chartData"
    :options="{
      animation: {
        duration: 0,
      },
      elements: {
        bar: {
          borderRadius: 10,
        },
      },
      scales: {
        x: {
          ticks: {
            display: timerEnded,
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
          grid: {
            display: false,
          },
        },
      },
      plugins: {
        legend: {
          display: false,
          labels: {
            color: contrastColor,
          },
        },
      },
    }"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Bar } from 'vue-chartjs';
import { VoteResult } from '@/types/api/Vote';
import * as themeColors from '@/utils/themeColors';

@Options({
  components: {
    Bar,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: 0 }) readonly timeModifier!: number;
  @Prop({ default: false }) readonly timerEnded!: boolean;
  @Prop({ default: [] }) readonly votes!: VoteResult[];
  chartData: any = {
    labels: [],
    datasets: [],
  };

  get contrastColor(): string {
    return themeColors.getContrastColor();
  }

  @Watch('votes', { immediate: true })
  onVotesChanged(): void {
    if (this.resultData) {
      this.chartData.labels = this.resultData.labels;
      this.chartData.datasets = this.resultData.datasets;
    }
    this.updateChart();
  }

  get resultData(): any {
    this.votes.map((vote) => vote.idea.keywords);
    return {
      labels: this.votes.map((vote) => vote.idea.keywords),
      datasets: [
        {
          label: (this as any).$t(
            'module.voting.default.publicScreen.chartDataLabel'
          ),
          backgroundColor: themeColors.getEvaluatingColor(),
          data: this.votes.map((vote) => vote.ratingSum),
        },
      ],
    };
  }

  async updateChart(): Promise<void> {
    if (this.$refs.chartRef) {
      const chartRef = this.$refs.chartRef as any;
      if (chartRef.chart) {
        chartRef.chart.data = this.chartData;
        chartRef.chart.update();
      }
    }
  }
}
</script>

<style scoped lang="scss">
#resultChart {
  max-height: 100%;
}
</style>
