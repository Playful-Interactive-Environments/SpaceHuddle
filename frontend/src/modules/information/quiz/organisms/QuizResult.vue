<template>
  <vue3-chart-js
    ref="chartRef"
    type="bar"
    :data="chartData"
    :height="100"
    :options="{
      maintainAspectRatio: 'false',
      responsive: 'false',
      indexAxis: 'y',
      animation: {
        duration: update ? 0 : 2000,
      },
      scales: {
        x: {
          ticks: {
            color: '#1d2948',
            stepSize: 1,
          },
          grid: {
            display: false,
          },
          stacked: true,
        },
      },
      plugins: {
        legend: {
          display: showLegend,
          position: 'top',
          align: 'end',
          labels: {
            boxWidth: 30,
            boxHeight: 30,
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
  @Prop({ default: true }) readonly showLegend!: true;

  chartData: any = {
    labels: [],
    datasets: [],
  };

  axisID = 'yaxisID';
  labelLineLimit = 2;

  get chartHeight(): number {
    return this.voteResult.length * 13;
  }

  @Watch('chartHeight', { immediate: true })
  onChartHeightChanged(): void {
    this.updateChart();
  }

  async mounted(): Promise<void> {
    if (this.resultData) {
      this.chartData.labels = this.resultData.labels;
      this.chartData.datasets = this.resultData.datasets;
      this.checkLabels();
      this.updateChart();
    }
  }

  @Watch('voteResult', { immediate: true })
  onVoteResultChanged(): void {
    if (this.resultData) {
      this.chartData.labels = this.resultData.labels;
      this.chartData.datasets = this.resultData.datasets;
      this.checkLabels();
      this.updateChart();
    }
  }

  breakString(str: string, limit: number): string[] | string {
    if (str.length > limit) {
      let stringArray = [''];
      let brokenString = '';
      let lineLimit = this.labelLineLimit;
      for (let i = 0, count = 0; i < str.length && lineLimit > 0; i++) {
        if ((count >= limit && str[i] === ' ') || count >= limit + 5) {
          if (str[i] !== ' ') {
            brokenString += '-';
          }
          count = 0;
          stringArray.push(brokenString);
          brokenString = '';
          lineLimit--;
        } else {
          count++;
          brokenString += str[i];
        }
      }
      if (lineLimit == 0) {
        stringArray[stringArray.length - 1] =
          stringArray[stringArray.length - 1] + '...';
      }
      return stringArray;
    } else {
      return str;
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
        labels: this.voteResult.map((vote) =>
          this.breakString(vote.idea.keywords, 34)
        ),
        datasets: [
          {
            label: labelCorrect,
            backgroundColor: '#01cf9e',
            data: this.voteResult.map((vote) =>
              vote.idea.parameter.isCorrect ? vote[this.resultColumn] : 0
            ),
            borderRadius: 5,
            borderSkipped: false,
            yAxisID: 1,
            color: '#1d2948',
          },
          {
            label: labelIncorrect,
            backgroundColor: '#fe6e5d',
            data: this.voteResult.map((vote) =>
              vote.idea.parameter.isCorrect ? 0 : vote[this.resultColumn]
            ),
            borderRadius: 5,
            borderSkipped: false,
            yAxisID: 1,
            color: '#1d2948',
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
            borderRadius: 5,
            borderSkipped: false,
            yAxisID: 1,
            color: '#1d2948',
          },
        ],
      };

    }
  }

  checkLabels(): void {
    const labelCorrect = (this as any).$t(
        'module.information.quiz.publicScreen.chartDataLabelCorrect'
    );
    const labelIncorrect = (this as any).$t(
        'module.information.quiz.publicScreen.chartDataLabelIncorrect'
    );

    let deleteCorrect;
    let deleteIncorrect;

    for (let i = 0; i < this.chartData.datasets.length; i++) {
      if (this.chartData.datasets[i].label == labelCorrect) {
        let count = 0;
        for (let j = 0; j < this.chartData.datasets[i].data.length; j++) {
          if (this.chartData.datasets[i].data[j] > 0) {
            count++;
          }
        }
        if (count <= 0) {
          deleteCorrect = i;
        }
      }
      if (this.chartData.datasets[i].label == labelIncorrect) {
        let count = 0;
        for (let j = 0; j < this.chartData.datasets[i].data.length; j++) {
          if (this.chartData.datasets[i].data[j] > 0) {
            count++;
          }
        }
        if (count <= 0) {
          deleteCorrect = i;
        }
      }
    }
    if (deleteCorrect != undefined) {
      this.chartData.datasets.splice(deleteCorrect, 1);
    }
    if (deleteIncorrect != undefined) {
      this.chartData.datasets.splice(deleteIncorrect, 1);
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
