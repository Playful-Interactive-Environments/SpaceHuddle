<template>
  <div
    v-if="
      questionType === QuestionType.TEXT || questionType === QuestionType.IMAGE
    "
    class="layout__columns"
  >
    <IdeaCard
      v-for="(item, index) in voteResult"
      :key="index"
      :idea="item.idea"
      :is-editable="false"
    />
  </div>
  <div v-else class="chartContainer" :style="{ height: `${chartHeight}rem` }">
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
          x: {
            ticks: {
              color: contrastColor,
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
            display: showLegend && questionnaireType === QuestionnaireType.QUIZ,
            position: 'top',
            align: 'end',
            labels: {
              boxWidth: 30,
              boxHeight: 30,
              color: contrastColor,
            },
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
import { VoteResult, VoteResultDetail } from '@/types/api/Vote';
import { QuestionnaireType } from '@/modules/information/quiz/types/QuestionnaireType';
import { QuestionType } from '@/modules/information/quiz/types/Question';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import Color from 'colorjs.io';
import { v4 as uuidv4 } from 'uuid';
import { delay } from '@/utils/wait';
import * as themeColors from '@/utils/themeColors';

interface ChartLegend {
  color: string;
  name: string;
  condition: (vote: VoteResult) => boolean;
}

@Options({
  components: {
    Bar,
    IdeaCard,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class QuizResult extends Vue {
  @Prop({ default: [] }) readonly voteResult!: VoteResult[];
  @Prop({ default: false }) readonly update!: boolean;
  @Prop({ default: QuestionnaireType.QUIZ })
  readonly questionnaireType!: QuestionnaireType;
  @Prop({ default: QuestionType.MULTIPLECHOICE })
  readonly questionType!: QuestionType;
  @Prop({ default: 'detailRatingSum' }) readonly resultColumn!: string;
  @Prop({ default: true }) readonly showLegend!: true;

  QuestionType = QuestionType;
  QuestionnaireType = QuestionnaireType;

  chartData: any = {
    labels: [],
    datasets: [],
  };
  labelLineLimit = 2;

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

  get legend(): ChartLegend[] {
    if (this.questionnaireType === QuestionnaireType.SURVEY) {
      const labelResult = (this as any).$t(
        'module.information.quiz.publicScreen.chartDataLabelResult'
      );
      return [
        {
          color: themeColors.getInformingColor(),
          name: labelResult,
          condition: () => true,
        },
      ];
    } else {
      if (this.questionType !== QuestionType.ORDER) {
        const labelCorrect = (this as any).$t(
          'module.information.quiz.publicScreen.chartDataLabelCorrect'
        );
        const labelIncorrect = (this as any).$t(
          'module.information.quiz.publicScreen.chartDataLabelIncorrect'
        );
        return [
          {
            color: themeColors.getBrainstormingColor(),
            name: labelCorrect,
            condition: (vote) => vote.idea.parameter.isCorrect,
          },
          {
            color: themeColors.getEvaluatingColor(),
            name: labelIncorrect,
            condition: (vote) => !vote.idea.parameter.isCorrect,
          },
        ];
      } else {
        const color1 = new Color(themeColors.getBrainstormingColor());
        const color2 = new Color(themeColors.getEvaluatingColor());
        const minVote = this.voteResult.sort((a, b) => {
          if (a.idea && b.idea)
            return (a.idea.order as number) - (b.idea.order as number);
          else if (b.idea) return 1;
          return 0;
        })[0];
        const min = minVote ? (minVote.idea.order as number) : 0;
        const maxVote = this.voteResult.sort((a, b) => {
          if (a.idea && b.idea)
            return (b.idea.order as number) - (a.idea.order as number);
          else if (b.idea) return 0;
          return 1;
        })[0];
        const max = maxVote
          ? (maxVote.idea.order as number)
          : this.voteResult.length - 1;
        const legend: ChartLegend[] = [];
        for (let i = min; i <= max; i++) {
          const color = (
            color1.mix(color2, (1 / (max + 1)) * (i + 1), {
              space: 'lch',
              outputSpace: 'srgb',
            }) as any
          ).coords as number[];
          let hexColor = '#';
          color.forEach(
            (coord) => (hexColor += Math.round(coord * 255).toString(16))
          );
          legend.push({
            color: hexColor,
            name: (i + 1).toString(),
            condition: (vote) => (vote as VoteResultDetail).rating === i,
          });
        }
        return legend;
      }
    }
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
      const stringArray: string[] = [];
      let lineLimit = this.labelLineLimit;
      let i = 0;
      while (i <= str.length && lineLimit > 0) {
        let end = str.indexOf(' ', i + limit);
        if (end === -1) end = str.length;
        if (end > i + limit + 10) {
          end = str.lastIndexOf(' ', i + limit);
          if (end < i + limit - 10) end = i + limit;
        }
        let brokenString = str.substring(i, end);
        if (end < str.length && ![' ', ',', '.', '?', '!'].includes(str[end])) {
          brokenString += '-';
        }
        stringArray.push(brokenString);
        lineLimit--;
        i = end;
      }
      if (lineLimit == 0 && i + limit < str.length) {
        stringArray[stringArray.length - 1] =
          stringArray[stringArray.length - 1] + '...';
      }
      if (stringArray.length === 0) stringArray.push('');
      return stringArray;
    } else {
      return str;
    }
  }

  get resultData(): any {
    const ideas = this.voteResult
      .map((vote) => vote.idea)
      .filter(
        (value, index, self) =>
          self.findIndex((item) => item.id === value.id) === index
      )
      .sort((a, b) => (a.order as number) - (b.order as number));
    const legend = this.legend;
    const datasets = legend.map((l) => {
      return {
        label: l.name,
        backgroundColor: l.color,
        data: ideas.map((idea) => {
          const votes = this.voteResult.filter(
            (item) => item.idea.id === idea.id
          );
          for (const vote of votes) {
            if (l.condition(vote)) return vote[this.resultColumn];
          }
          return 0;
        }),
        borderRadius: {
          topRight: 5,
          bottomRight: 5,
          topLeft: 5,
          bottomLeft: 5,
        },
        borderSkipped: false,
        yAxisID: 1,
        color: themeColors.getContrastColor(),
      };
    });
    return {
      labels: ideas.map((idea) => this.breakString(idea.keywords, 34)),
      datasets: datasets,
    };
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
