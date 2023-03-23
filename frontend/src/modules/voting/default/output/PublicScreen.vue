<template>
  <section class="centered public-screen__content chart">
    <Bar
      id="resultChart"
      ref="chartRef"
      :data="chartData"
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
            labels: {
              color: '#1d2948',
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

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
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
          backgroundColor: '#fe6e5d',
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

  unmounted(): void {
    cashService.deregisterAllGet(this.updateVotes);
  }
}
</script>

<style lang="scss" scoped>
.chart {
  max-width: calc(var(--app-height) * 1.2);
}
</style>
