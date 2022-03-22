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
import { QuestionType } from '@/modules/information/quiz/types/QuestionType';

@Options({
  components: {
    Vue3ChartJs,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class QuizResult extends Vue {
  @Prop({ default: [] }) readonly voteResult!: VoteResult[];
  @Prop({ default: false }) readonly update!: boolean;
  @Prop({ default: QuestionType.QUIZ }) readonly questionType!: QuestionType;
  @Prop({ default: 'detailRatingSum' }) readonly resultColumn!: string;
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
    const labelResult = (this as any).$t(
      'module.information.quiz.publicScreen.chartDataLabelResult'
    );
    if (this.questionType === QuestionType.QUIZ) {
      return {
        labels: this.voteResult.map((vote) => vote.idea.keywords),
        datasets: [
          {
            label: labelCorrect,
            backgroundColor: '#01cf9e',
            data: this.voteResult.map((vote) =>
              vote.idea.parameter.isCorrect ? vote[this.resultColumn] : 0
            ),
          },
          {
            label: labelIncorrect,
            backgroundColor: '#fe6e5d',
            data: this.voteResult.map((vote) =>
              vote.idea.parameter.isCorrect ? 0 : vote[this.resultColumn]
            ),
          },
        ],
      };
    } else {
      return {
        labels: this.voteResult.map((vote) => vote.idea.keywords),
        datasets: [
          {
            label: labelResult,
            backgroundColor: '#f3a40a',
            data: this.voteResult.map((vote) => vote[this.resultColumn]),
          },
        ],
      };
    }
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
