<template>
  <section class="container2 container2--centered public-screen__error">
    <vue3-chart-js
      id="resultChart"
      ref="chartRef"
      type="bar"
      :data="chartData"
      :options="{
        scales: {
          y: {
            beginAtZero: true,
          },
        },
        animation: {
          duration: 0,
        },
      }"
    />
  </section>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import Vue3ChartJs from '@j-t-mcc/vue3-chartjs';
import * as votingService from '@/services/voting-service';
import { VoteResult } from '@/types/api/Vote';

@Options({
  components: {
    Vue3ChartJs,
  },
})
export default class PublicScreenComponent extends Vue {
  @Prop() readonly taskId!: string;
  votes: VoteResult[] = [];
  chartData: any = {
    labels: [],
    datasets: [],
  };
  readonly intervalTime = 3000;
  interval!: any;

  get resultData(): any {
    this.votes.map((vote) => vote.idea.keywords);
    const data = {
      labels: this.votes.map((vote) => vote.idea.keywords),
      datasets: [
        {
          label: (this as any).$t('module.voting.default.publicScreen.chartDataLabel'),
          backgroundColor: '#2980b9',
          data: this.votes.map((vote) => vote.detailRatingSum),
        },
      ],
    };
    return data;
  }

  async getVotes(): Promise<void> {
    if (this.taskId) {
      await votingService.getResult(this.taskId).then((votes) => {
        this.votes = votes;
        this.chartData.labels = this.resultData.labels;
        this.chartData.datasets = this.resultData.datasets;
        this.updateChart();
      });
    }
  }

  async updateChart(): Promise<void> {
    if (this.$refs.chartRef) {
      const chartRef = (this.$refs.chartRef as any);
      chartRef.update();
    }
  }

  async mounted(): Promise<void> {
    this.startIdeaInterval();
  }

  startIdeaInterval(): void {
    this.interval = setInterval(this.getVotes, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }
}
</script>
