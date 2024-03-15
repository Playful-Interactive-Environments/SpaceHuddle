<template>
  <div
    v-if="
      questionType === QuestionType.TEXT || questionType === QuestionType.IMAGE
    "
  >
    <IdeaFilterBase
      :sort-order-options="ideaSortOrderOptions"
      v-model="filter"
    />
    <div class="layout__columns">
      <IdeaCard
        v-for="(item, index) in filteredVoteResult"
        :key="index"
        :idea="item"
        :canBeChanged="false"
        :show-keyword="false"
        :isSharable="false"
        v-model:collapseIdeas="filter.collapseIdeas"
      >
        <div
          class="participant-info"
          v-if="filter.orderType === IdeaSortOrder.PARTICIPANT"
        >
          <span
            v-for="avatar in item.avatar"
            :key="avatar.symbol"
            :style="{ color: avatar.color }"
          >
            <font-awesome-icon :icon="avatar.symbol" />&nbsp;
          </span>
        </div>
      </IdeaCard>
    </div>
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
            display:
              showLegend && questionnaireType !== QuestionnaireType.SURVEY,
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
import { Hierarchy } from '@/types/api/Hierarchy';
import { Idea } from '@/types/api/Idea';
import IdeaFilterBase, {
  defaultFilterData,
  FilterData,
} from '@/components/moderator/molecules/IdeaFilterBase.vue';
import * as ideaService from '@/services/idea-service';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import { SortOrderOption } from '@/types/api/OrderGroup';
import { convertAvatarToString } from '@/types/api/Participant';

interface ChartLegend {
  color: string;
  name: string;
  conditionAnswer: (vote: VoteResult) => boolean;
  conditionQuestion: (idea: Hierarchy | Idea) => boolean;
}

@Options({
  components: {
    IdeaFilterBase,
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
  IdeaSortOrder = IdeaSortOrder;

  chartData: any = {
    labels: [],
    datasets: [],
  };
  labelLineLimit = 2;
  filter: FilterData = { ...defaultFilterData };

  get ideaSortOrderOptions(): SortOrderOption[] {
    return ['TIMESTAMP', 'ALPHABETICAL', 'PARTICIPANT', 'COUNT', 'STATE'].map(
      (orderType) => {
        return { orderType: orderType.toLowerCase(), ref: null };
      }
    );
  }

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
    const legend: ChartLegend[] = [];
    if (this.questionnaireType !== QuestionnaireType.QUIZ) {
      const labelResult = (this as any).$t(
        'module.information.quiz.publicScreen.chartDataLabelResult'
      );
      legend.push({
        color: themeColors.getYellowColor(),
        name: labelResult,
        conditionAnswer: () => true,
        conditionQuestion: (idea) =>
          this.questionnaireType === QuestionnaireType.SURVEY ||
          !idea.parameter.hasAnswer,
      });
    }

    if (this.questionnaireType !== QuestionnaireType.SURVEY) {
      if (this.questionType !== QuestionType.ORDER) {
        const labelCorrect = (this as any).$t(
          'module.information.quiz.publicScreen.chartDataLabelCorrect'
        );
        const labelIncorrect = (this as any).$t(
          'module.information.quiz.publicScreen.chartDataLabelIncorrect'
        );
        legend.push({
          color: themeColors.getGreenColor(),
          name: labelCorrect,
          conditionAnswer: (vote) => vote.idea.parameter.isCorrect,
          conditionQuestion: (idea) =>
            this.questionnaireType === QuestionnaireType.QUIZ ||
            idea.parameter.hasAnswer,
        });
        legend.push({
          color: themeColors.getRedColor(),
          name: labelIncorrect,
          conditionAnswer: (vote) => !vote.idea.parameter.isCorrect,
          conditionQuestion: (idea) =>
            this.questionnaireType === QuestionnaireType.QUIZ ||
            idea.parameter.hasAnswer,
        });
      } else {
        const color1 = new Color(themeColors.getGreenColor());
        const color2 = new Color(themeColors.getRedColor());
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
            conditionAnswer: (vote) => (vote as VoteResultDetail).rating === i,
            conditionQuestion: (idea) =>
              this.questionnaireType === QuestionnaireType.QUIZ ||
              idea.parameter.hasAnswer,
          });
        }
      }
    }
    return legend;
  }

  get filteredVoteResult(): Idea[] {
    let list = ideaService.filterIdeas(
      this.voteResult.map((item) => {
        const idea = item.idea as Idea;
        idea.count = item.countParticipant;
        idea.avatar = item.avatarList;
        return idea;
      }),
      this.filter.stateFilter,
      this.filter.textFilter
    );
    switch (this.filter.orderType) {
      case IdeaSortOrder.TIMESTAMP:
        list = list.sort((a, b) => a.timestamp.localeCompare(b.timestamp));
        break;
      case IdeaSortOrder.ALPHABETICAL:
        list = list.sort((a, b) => a.keywords.localeCompare(b.keywords));
        break;
      case IdeaSortOrder.PARTICIPANT:
        list = list.sort((a, b) =>
          convertAvatarToString(a.avatar[0]).localeCompare(
            convertAvatarToString(b.avatar[0])
          )
        );
        break;
      case IdeaSortOrder.COUNT:
        list = list.sort((a, b) => a.count - b.count);
        break;
      case IdeaSortOrder.STATE:
        list = list.sort((a, b) => a.state.localeCompare(b.state));
        break;
    }
    if (!this.filter.orderAsc) {
      return list.reverse();
    }
    return list;
  }

  async mounted(): Promise<void> {
    this.filter.orderType = IdeaSortOrder.ALPHABETICAL;
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
            if (l.conditionQuestion(idea) && l.conditionAnswer(vote))
              return vote[this.resultColumn];
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

.participant-info {
  margin-top: 1rem;
}
</style>
