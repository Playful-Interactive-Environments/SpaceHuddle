<template>
  <vue3-chart-js
    id="resultChart"
    ref="chartRef"
    type="bar"
    :data="chartData"
    :options="{
      animation: {
        duration: update ? 0 : 2000,
      },
      scales: {
        x: {
          ticks: {
            color: '#1d2948',
          },
          grid: {
            display: false,
          },
          stacked: true,
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
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import Vue3ChartJs from '@j-t-mcc/vue3-chartjs';
import { VoteResult } from '@/types/api/Vote';

@Options({
  components: {
    Vue3ChartJs,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class QuizResult extends Vue {
  @Prop({ default: [] }) readonly voteResult!: VoteResult[];
  @Prop({ default: false }) readonly update!: boolean;
  chartData: any = {
    labels: [],
    datasets: [],
  };

  @Watch('voteResult', { immediate: true })
  onVoteResultChanged(): void {
    if (this.resultData) {
      this.chartData.labels = this.resultData.labels;
      this.chartData.datasets = this.resultData.datasets;
      this.updateChart();
    }
  }

  get resultData(): any {
    const labelCorrect = (this as any).$t(
      'module.information.quiz.publicScreen.chartDataLabelCorrect'
    );
    const labelIncorrect = (this as any).$t(
      'module.information.quiz.publicScreen.chartDataLabelIncorrect'
    );
    return {
      labels: this.voteResult.map((vote) => vote.idea.keywords),
      datasets: [
        {
          label: labelCorrect,
          backgroundColor: '#f1be3a',
          data: this.voteResult.map((vote) =>
            vote.idea.parameter.isCorrect ? vote.detailRatingSum : 0
          ),
        },
        {
          label: labelIncorrect,
          backgroundColor: '#fe6e5d',
          data: this.voteResult.map((vote) =>
            vote.idea.parameter.isCorrect ? 0 : vote.detailRatingSum
          ),
        },
      ],
    };
    /*return {
      labels: this.voteResult.map((vote) => vote.idea.keywords),
      datasets: [
        {
          label: this.voteResult[0].idea.parameter.isCorrect
            ? labelCorrect
            : labelIncorrect,
          backgroundColor: this.voteResult.map((vote) =>
            vote.idea.parameter.isCorrect ? '#f1be3a' : '#fe6e5d'
          ),
          data: this.vote_result.map((vote) => vote.detailRatingSum),
        },
      ],
    };*/
  }

  async updateChart(): Promise<void> {
    if (this.$refs.chartRef) {
      const chartRef = this.$refs.chartRef as any;
      chartRef.update();
    }
  }
}
</script>

<style lang="scss" scoped></style>
