<template>
  <Bar
    v-for="(chartData, index) in barChartDataList"
    :key="index"
    :data="chartData.data"
    :height="80"
    :options="{
      maintainAspectRatio: true,
      animation: {
        duration: 0,
      },
      scales: {
        x: {
          display: displayLabels,
          ticks: {
            color: chartData.labelColors,
          },
          grid: {
            display: false,
          },
          stacked: chartData.stacked,
        },
        y: {
          ticks: {
            color: contrastColor,
            stepSize: 1,
          },
          stacked: chartData.stacked,
        },
      },
      plugins: {
        legend: {
          display: chartData.data.datasets.length > 1,
        },
        title: {
          display: true,
          text: chartData.title,
        },
        annotation: {
          annotations: chartData.annotations,
        },
      },
    }"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import { Bar } from 'vue-chartjs';
import * as taskParticipantService from '@/services/task-participant-service';
import * as ideaService from '@/services/idea-service';
import * as votingService from '@/services/voting-service';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import type { ChartData } from 'chart.js';
import { Chart } from 'chart.js';
import { Idea } from '@/types/api/Idea';
import { getRainbowColorList, getRandomColorList } from '@/utils/colors';
import * as themeColors from '@/utils/themeColors';
import {
  getCalculationForType,
  calculateChartPerParameter,
  CalculationType,
} from '@/utils/statistic';
import { Vote } from '@/types/api/Vote';
import { AvatarUnicode } from '@/types/enum/AvatarUnicode';
import annotationPlugin from 'chartjs-plugin-annotation';

Chart.register(annotationPlugin);

@Options({
  components: { Bar },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModuleStatistic extends Vue {
  @Prop() readonly taskId!: string;
  steps: TaskParticipantIterationStep[] = [];
  ideas: Idea[] = [];
  votes: Vote[] = [];
  barChartDataList: {
    title: string;
    data: ChartData;
    labelColors: string[] | string;
    stacked: boolean;
    annotations: { [key: string]: any };
  }[] = [];
  displayLabels = false;
  replayColors: string[] = [];
  colorList: string[] = getRandomColorList(20);

  get contrastColor(): string {
    return themeColors.getContrastColor();
  }

  mounted(): void {
    document.fonts.onloadingdone = () => {
      this.displayLabels = true;
    };
    setTimeout(() => (this.displayLabels = true), 2000);
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateIdeas);
    cashService.deregisterAllGet(this.updateIterationSteps);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      ideaService.registerGetIdeasForTask(
        this.taskId,
        null,
        null,
        this.updateIdeas,
        EndpointAuthorisationType.MODERATOR,
        2 * 60
      );
      taskParticipantService.registerGetIterationStepList(
        this.taskId,
        this.updateIterationSteps,
        EndpointAuthorisationType.MODERATOR,
        2 * 60
      );
      votingService.registerGetVotes(
        this.taskId,
        this.updateVotes,
        EndpointAuthorisationType.MODERATOR,
        2 * 60
      );
    }
  }

  updateIdeas(ideas: Idea[]): void {
    this.ideas = ideas.filter((idea) => idea.parameter.shareData);
    this.sortIdeasByVote();
    this.calculateCharts();
  }

  updateIterationSteps(steps: TaskParticipantIterationStep[]): void {
    this.steps = steps;
    const maxReplays = taskParticipantService.addReplayCountToSteps(
      this.steps,
      (param) => param.step
    );

    this.replayColors.push(...getRandomColorList(maxReplays + 1));
    this.calculateCharts();
  }

  updateVotes(votes: Vote[]): void {
    this.votes = votes;
    this.sortIdeasByVote();
    this.calculateCharts();
  }

  sortIdeasByVote(): void {
    if (this.ideas && this.votes) {
      this.votes = this.votes.filter((vote) =>
        this.ideas.find((idea) => idea.id === vote.ideaId)
      );
      this.ideas = this.ideas
        .map((idea) => {
          const votes = this.votes.filter((vote) => vote.ideaId === idea.id);
          let order = 0;
          for (const vote of votes) {
            order += vote.detailRating;
          }
          order /= votes.length;
          return {
            idea: idea,
            order: order,
          };
        })
        .sort((a, b) => a.order - b.order)
        .map((item) => item.idea);
    }
  }

  getAvatarList(filter: ((item) => boolean) | null = null): any[] {
    const subset = filter ? this.steps.filter(filter) : this.steps;
    return subset
      .map((item) => item.avatar)
      .filter(
        (value, index, array) =>
          array.findIndex(
            (item) => item.color === value.color && item.symbol === value.symbol
          ) === index
      )
      .sort((a, b) => {
        const x = `${a.symbol}#${a.color}`;
        const y = `${b.symbol}#${b.color}`;
        if (x < y) return -1;
        if (x > y) return 1;
        return 0;
      });
  }

  calculateCharts(): void {
    this.barChartDataList = [];
    this.calculateRatingChart();
    this.calculateOrderChart();
    this.calculatePointChart();
  }

  calculateRatingChart(): void {
    const parameter: number[] = [0, 1, 2, 3];
    const displayParameter: string[] = [
      '-',
      '\uf005',
      '\uf005\uf005',
      '\uf005\uf005\uf005',
    ];
    const labels: string[] = this.ideas.map((idea) => idea.keywords);
    const datasets = calculateChartPerParameter(
      this.votes,
      parameter,
      this.ideas,
      this.colorList,
      (item, parameter) => item.rating === parameter,
      (item, idea) => item.ideaId === idea.id,
      null,
      (list) => list.length,
      (parameter) => displayParameter[parameter]
    );
    this.barChartDataList.push({
      title: this.$t('module.information.missionmap.statistic.rating'),
      data: {
        labels: labels,
        datasets: datasets,
      },
      labelColors: themeColors.getContrastColor(),
      stacked: false,
      annotations: {},
    });
  }

  calculateOrderChart(): void {
    const maxOrder =
      this.votes.length > 0
        ? this.votes.sort((a, b) => b.detailRating - a.detailRating)[0]
            .detailRating
        : 0;
    const colorList = getRainbowColorList(maxOrder + 1);
    const parameter: number[] = [...Array(maxOrder + 1).keys()];
    const labels: string[] = this.ideas.map((idea) => idea.keywords);
    const datasets = calculateChartPerParameter(
      this.votes,
      parameter,
      this.ideas,
      colorList,
      (item, parameter) => item.detailRating === parameter,
      (item, idea) => item.ideaId === idea.id,
      null,
      (list) => list.length,
      (parameter) => parameter + 1
    );
    this.barChartDataList.push({
      title: this.$t('module.information.missionmap.statistic.order'),
      data: {
        labels: labels,
        datasets: datasets,
      },
      labelColors: themeColors.getContrastColor(),
      stacked: true,
      annotations: {},
    });
  }

  calculatePointChart(): void {
    const avatarList = this.getAvatarList();
    const labels: string[] = this.ideas.map((idea) => idea.keywords);
    const mapToValue = (list) => list.map((item) => item.parameter.points);
    const datasets = calculateChartPerParameter(
      this.steps,
      avatarList,
      this.ideas,
      avatarList.map((avatar) => avatar.color),
      (item, avatar) =>
        item.avatar.color === avatar.color &&
        item.avatar.symbol === avatar.symbol,
      (item, idea) => item.ideaId === idea.id,
      null,
      (list) => getCalculationForType(CalculationType.Sum)(mapToValue(list)),
      (avatar) => AvatarUnicode[avatar.symbol]
    );
    const annotations: { [key: string]: any } = {};
    for (let i = 0; i < this.ideas.length; i++) {
      const idea = this.ideas[i];
      annotations[idea.id] = {
        type: 'box',
        xMin: i - 0.4,
        xMax: i + 0.4,
        yMin: 0,
        yMax: idea.parameter.points,
        backgroundColor: themeColors.convertToRGBA(
          themeColors.getHighlightColor(),
          0.25
        ),
        borderColor: themeColors.convertToRGBA(themeColors.getHighlightColor()),
        label: {
          content: idea.parameter.points,
          display: true,
          position: { x: '50%', y: '0%' },
          color: themeColors.convertToRGBA(themeColors.getContrastColor()),
        },
      };
    }
    this.barChartDataList.push({
      title: this.$t('module.information.missionmap.statistic.points'),
      data: {
        labels: labels,
        datasets: datasets,
      },
      labelColors: themeColors.getContrastColor(),
      stacked: true,
      annotations: annotations,
    });
  }
}
</script>
