<template>
  <section class="centered public-screen__content chart">
    <Bar
      id="resultChart"
      ref="chartRef"
      :data="chartData"
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
            labels: {
              color: contrastColor,
            },
          },
        },
      }"
    />
  </section>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Bar } from 'vue-chartjs';
import * as votingService from '@/services/voting-service';
import { VoteResult } from '@/types/api/Vote';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import * as themeColors from '@/utils/themeColors';

@Options({
  components: {
    Bar,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  votes: VoteResult[] = [];
  chartData: any = {
    labels: [],
    datasets: [],
  };

  get contrastColor(): string {
    return themeColors.getContrastColor();
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    votingService.registerGetResult(
      this.taskId,
      this.updateVotes,
      this.authHeaderTyp
    );
  }

  updateVotes(votes: VoteResult[]): void {
    this.votes = votes;
    this.chartData.labels = this.resultData.labels;
    this.chartData.datasets = this.resultData.datasets;
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
          data: this.votes.map((vote) => vote.detailRatingSum),
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

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateVotes);
  }

  unmounted(): void {
    this.deregisterAll();
  }
}
</script>

<style lang="scss" scoped>
.chart {
  max-width: calc(var(--app-height) * 1.2);
  height: calc(100% - 5rem);
}
</style>
