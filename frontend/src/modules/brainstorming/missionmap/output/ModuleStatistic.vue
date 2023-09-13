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
            stepSize: chartData.stepSize,
          },
          stacked: chartData.stacked,
        },
      },
      plugins: {
        legend: {
          display:
            chartData.legendAlwaysVisible || chartData.data.datasets.length > 1,
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
  <Line
    v-for="(chartData, index) in lineChartDataList"
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
        },
        y: {
          ticks: {
            color: contrastColor,
            stepSize: 1,
          },
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
      },
    }"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import { Bar, Line } from 'vue-chartjs';
import * as taskParticipantService from '@/services/task-participant-service';
import * as taskService from '@/services/task-service';
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
import gameConfig from '@/modules/brainstorming/missionmap/data/gameConfig.json';
import { Task } from '@/types/api/Task';
import { Module } from '@/types/api/Module';
import * as progress from '@/modules/brainstorming/missionmap/utils/progress';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import { CombinedInputManager } from '@/types/input/CombinedInputManager';

Chart.register(annotationPlugin);

@Options({
  components: { Bar, Line },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModuleStatistic extends Vue {
  @Prop() readonly taskId!: string;
  module!: Module;
  steps: TaskParticipantIterationStep[] = [];
  ideas: Idea[] = [];
  votes: Vote[] = [];
  decidedIdeas: Idea[] = [];
  decidedInputIdeas: Idea[] = [];
  barChartDataList: {
    title: string;
    data: ChartData;
    labelColors: string[] | string;
    stacked: boolean;
    annotations: { [key: string]: any };
    stepSize: number;
    legendAlwaysVisible: boolean;
  }[] = [];
  lineChartDataList: {
    title: string;
    data: ChartData;
    labelColors: string[] | string;
  }[] = [];
  displayLabels = false;
  replayColors: string[] = [];
  colorList: string[] = getRandomColorList(20);
  inputManager!: CombinedInputManager;

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
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateIterationSteps);
    if (this.inputManager) this.inputManager.deregisterAll();
  }

  unmounted(): void {
    this.deregisterAll();
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      this.inputManager = new CombinedInputManager(
        this.taskId,
        IdeaSortOrder.TIMESTAMP,
        EndpointAuthorisationType.MODERATOR,
        true,
        'points'
      );
      this.inputManager.callbackUpdateIdeas = this.updateIdeas;
      this.inputManager.callbackUpdateVotes = this.updateVotes;
      taskService.registerGetTaskById(this.taskId, this.updateTask);
      taskParticipantService.registerGetIterationStepList(
        this.taskId,
        this.updateIterationSteps,
        EndpointAuthorisationType.MODERATOR,
        2 * 60
      );
    }
  }

  updateTask(task: Task): void {
    const module = task.modules.find((module) => module.name === 'missionmap');
    if (module) this.module = module;
    this.calculateCharts();
  }

  updateIdeas(): void {
    const ideas = this.inputManager.ideas;
    this.ideas = ideas.filter((idea) => idea.parameter.shareData);
    this.calculateDecidedIdeas();
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

  updateVotes(): void {
    this.votes = this.inputManager.votes;
    this.calculateDecidedIdeas();
    this.sortIdeasByVote();
    this.calculateCharts();
  }

  calculateDecidedIdeas(): void {
    if (this.inputManager) {
      this.decidedIdeas = progress.calculateDecidedIdeasFromVotesSorted(
        this.inputManager.votes,
        this.ideas
      );
      this.decidedInputIdeas = progress.calculateDecidedIdeasFromVotesSorted(
        this.inputManager.inputVotes,
        this.ideas
      );
    }
  }

  sortIdeasByVote(): void {
    if (this.inputManager && this.ideas && this.inputManager.votes) {
      this.votes = this.inputManager.votes.filter((vote) =>
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

  getAvatarList(list: any[], filter: ((item) => boolean) | null = null): any[] {
    const subset = filter ? list.filter(filter) : list;
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
    this.lineChartDataList = [];
    if (!this.module) return;
    if (this.inputManager.hasInput())
      this.calculateRatingChart(this.inputManager.inputVotes, 'input');
    this.calculateRatingChart(this.inputManager.currentVotes, 'current');
    if (this.inputManager.hasInput())
      this.calculateOrderChart(this.inputManager.inputVotes, 'input');
    this.calculateOrderChart(this.inputManager.currentVotes, 'current');
    if (this.inputManager.hasInput())
      this.calculatePointSpentChart(this.inputManager.inputVotes, 'input');
    this.calculatePointSpentChart(this.inputManager.votes, 'current');
    if (this.inputManager.hasInput())
      this.calculateExplanationChart(this.inputManager.inputVotes, 'input');
    this.calculateExplanationChart(this.inputManager.currentVotes, 'current');
    this.calculatePointCollectChart();
    this.calculateOwnIdeasChart();
    if (this.inputManager.hasInput())
      this.calculateLineChartByProgression(this.decidedInputIdeas, 'input');
    this.calculateLineChartByProgression(this.decidedIdeas, 'current');
  }

  calculateRatingChart(votes: Vote[], type: string): void {
    const parameter: number[] = [0, 1, 2, 3];
    const displayParameter: string[] = [
      '-',
      '\uf005',
      '\uf005\uf005',
      '\uf005\uf005\uf005',
    ];
    const labels: string[] = this.ideas.map((idea) => idea.keywords);
    const datasets = calculateChartPerParameter(
      votes,
      parameter,
      this.ideas,
      this.colorList,
      (item, parameter) => item.rating === parameter,
      (item, idea) => item.ideaId === idea.id,
      null,
      (list) => list.length,
      (parameter) => displayParameter[parameter]
    );
    const typeName = this.$t(
      `module.brainstorming.missionmap.statistic.${type}`
    );
    const title = this.$t('module.brainstorming.missionmap.statistic.rating');
    this.barChartDataList.push({
      title: `${typeName}: ${title}`,
      data: {
        labels: labels,
        datasets: datasets,
      },
      labelColors: themeColors.getContrastColor(),
      stacked: false,
      annotations: {},
      stepSize: 1,
      legendAlwaysVisible: false,
    });
  }

  calculateOrderChart(votes: Vote[], type: string): void {
    const maxOrder =
      votes.length > 0
        ? votes.sort((a, b) => b.detailRating - a.detailRating)[0].detailRating
        : 0;
    const colorList = getRainbowColorList(maxOrder + 1);
    const parameter: number[] = [...Array(maxOrder + 1).keys()];
    const labels: string[] = this.ideas.map((idea) => idea.keywords);
    const datasets = calculateChartPerParameter(
      votes,
      parameter,
      this.ideas,
      colorList,
      (item, parameter) => item.detailRating === parameter,
      (item, idea) => item.ideaId === idea.id,
      null,
      (list) => list.length,
      (parameter) => parameter + 1
    );
    const typeName = this.$t(
      `module.brainstorming.missionmap.statistic.${type}`
    );
    const title = this.$t('module.brainstorming.missionmap.statistic.order');
    this.barChartDataList.push({
      title: `${typeName}: ${title}`,
      data: {
        labels: labels,
        datasets: datasets,
      },
      labelColors: themeColors.getContrastColor(),
      stacked: true,
      annotations: {},
      stepSize: 1,
      legendAlwaysVisible: false,
    });
  }

  calculatePointSpentChart(votes: Vote[], type: string): void {
    const avatarList = this.getAvatarList(votes);
    const labels: string[] = this.ideas.map((idea) => idea.keywords);
    const mapToValue = (list) => list.map((item) => item.parameter.points);
    const datasets = calculateChartPerParameter(
      votes,
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
      const votePoints = this.votes
        .filter((vote) => vote.ideaId === idea.id)
        .map((vote) => vote.parameter.points)
        .reduce((a, b) => a + b, 0);
      let annotationColor = themeColors.getRedColor();
      if (votePoints >= idea.parameter.points)
        annotationColor = themeColors.getGreenColor();
      if (votePoints >= (idea.parameter.points / 3) * 2)
        annotationColor = themeColors.getYellowColor();
      annotations[idea.id] = {
        type: 'box',
        xMin: i - 0.4,
        xMax: i + 0.4,
        yMin: 0,
        yMax: idea.parameter.points,
        backgroundColor: themeColors.convertToRGBA(annotationColor, 0.25),
        borderColor: themeColors.convertToRGBA(annotationColor),
        label: {
          content: idea.parameter.points,
          display: true,
          position: { x: '50%', y: '0%' },
          color: themeColors.convertToRGBA(themeColors.getContrastColor()),
        },
      };
    }
    const typeName = this.$t(
      `module.brainstorming.missionmap.statistic.${type}`
    );
    const title = this.$t(
      'module.brainstorming.missionmap.statistic.pointsSpent'
    );
    this.barChartDataList.push({
      title: `${typeName}: ${title}`,
      data: {
        labels: labels,
        datasets: datasets,
      },
      labelColors: themeColors.getContrastColor(),
      stacked: true,
      annotations: annotations,
      stepSize: 500,
      legendAlwaysVisible: false,
    });
  }

  calculatePointSpentChart_(): void {
    const avatarList = this.getAvatarList(this.steps);
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
      const votePoints = this.votes
        .filter((vote) => vote.ideaId === idea.id)
        .map((vote) => vote.parameter.points)
        .reduce((a, b) => a + b, 0);
      let annotationColor = themeColors.getRedColor();
      if (votePoints >= idea.parameter.points)
        annotationColor = themeColors.getGreenColor();
      if (votePoints >= (idea.parameter.points / 3) * 2)
        annotationColor = themeColors.getYellowColor();
      annotations[idea.id] = {
        type: 'box',
        xMin: i - 0.4,
        xMax: i + 0.4,
        yMin: 0,
        yMax: idea.parameter.points,
        backgroundColor: themeColors.convertToRGBA(annotationColor, 0.25),
        borderColor: themeColors.convertToRGBA(annotationColor),
        label: {
          content: idea.parameter.points,
          display: true,
          position: { x: '50%', y: '0%' },
          color: themeColors.convertToRGBA(themeColors.getContrastColor()),
        },
      };
    }
    this.barChartDataList.push({
      title: this.$t('module.brainstorming.missionmap.statistic.pointsSpent'),
      data: {
        labels: labels,
        datasets: datasets,
      },
      labelColors: themeColors.getContrastColor(),
      stacked: true,
      annotations: annotations,
      stepSize: 500,
      legendAlwaysVisible: false,
    });
  }

  calculateExplanationChart(votes: Vote[], type: string): void {
    const filter = (item) => item.parameter.explanation;
    const parameter = votes
      .filter(filter)
      .map((item) => item.parameter.explanation)
      .filter(
        (value, index, array) =>
          array.findIndex((item) => item === value) === index
      )
      .sort((a, b) => {
        if (a < b) return -1;
        if (a > b) return 1;
        return 0;
      });
    const labels: string[] = this.ideas.map((idea) => idea.keywords);
    const datasets = calculateChartPerParameter(
      votes,
      parameter,
      this.ideas,
      this.colorList,
      (item, parameter) => item.parameter.explanation === parameter,
      (item, idea) => item.ideaId === idea.id,
      filter
    );
    const typeName = this.$t(
      `module.brainstorming.missionmap.statistic.${type}`
    );
    const title = this.$t(
      'module.brainstorming.missionmap.statistic.explanation'
    );
    this.barChartDataList.push({
      title: `${typeName}: ${title}`,
      data: {
        labels: labels,
        datasets: datasets,
      },
      labelColors: themeColors.getContrastColor(),
      stacked: true,
      annotations: {},
      stepSize: 1,
      legendAlwaysVisible: true,
    });
  }

  calculatePointCollectChart(): void {
    const avatarList = this.getAvatarList(this.steps);
    const labels: string[] = avatarList.map(
      (participant) => AvatarUnicode[participant.symbol]
    );
    const labelColors: string[] = avatarList.map(
      (participant) => participant.color
    );
    const mapToValue = (list) =>
      list.map((item) => item.parameter.gameplayResult.points);
    const datasets = calculateChartPerParameter(
      this.steps,
      [''],
      avatarList,
      this.replayColors,
      () => true,
      (item, avatar) =>
        item.avatar.color === avatar.color &&
        item.avatar.symbol === avatar.symbol,
      null,
      (list) => getCalculationForType(CalculationType.Sum)(mapToValue(list))
    );
    this.barChartDataList.push({
      title: this.$t(
        'module.brainstorming.missionmap.statistic.pointsCollected'
      ),
      data: {
        labels: labels,
        datasets: datasets,
      },
      labelColors: labelColors,
      stacked: true,
      annotations: {},
      stepSize: 50,
      legendAlwaysVisible: false,
    });
  }

  calculateOwnIdeasChart(): void {
    const filter = (item) => item.parameter.participantIdea;
    const avatarList = this.getAvatarList(this.steps);
    const labels: string[] = avatarList.map(
      (participant) => AvatarUnicode[participant.symbol]
    );
    const labelColors: string[] = avatarList.map(
      (participant) => participant.color
    );
    const datasets = calculateChartPerParameter(
      this.steps,
      [''],
      avatarList,
      this.replayColors,
      () => true,
      (item, avatar) =>
        item.avatar.color === avatar.color &&
        item.avatar.symbol === avatar.symbol,
      filter
    );
    this.barChartDataList.push({
      title: this.$t('module.brainstorming.missionmap.statistic.ownIdeas'),
      data: {
        labels: labels,
        datasets: datasets,
      },
      labelColors: labelColors,
      stacked: true,
      annotations: {},
      stepSize: 1,
      legendAlwaysVisible: false,
    });
  }

  calculateLineChartByProgression(decidedIdeas: Idea[], type: string): void {
    const mapToValue = (list, parameter) =>
      list.map((item) =>
        item.idea ? item.idea.parameter.influenceAreas[parameter] : 0
      );
    const ideaProgress: { index: number; idea: Idea | null }[] = [
      { index: -1, idea: null },
    ];
    for (let i = 0; i < decidedIdeas.length; i++) {
      ideaProgress.push({ index: i, idea: decidedIdeas[i] });
    }
    const labels = ideaProgress.map((item) =>
      item.idea
        ? item.idea.keywords
        : this.$t('module.brainstorming.missionmap.enum.progress.origin')
    );
    const datasets = calculateChartPerParameter(
      ideaProgress,
      Object.keys(gameConfig.parameter),
      ideaProgress,
      Object.values(gameConfig.parameter).map((item) => item.color),
      () => true,
      (item, progress) => item.index <= progress.index,
      null,
      (list, loopItem, parameter) => {
        const sum = getCalculationForType(CalculationType.Sum)(
          mapToValue(list, parameter)
        );
        return this.module.parameter[parameter] + sum;
      }
    );
    const typeName = this.$t(
      `module.brainstorming.missionmap.statistic.${type}`
    );
    const title = this.$t('module.brainstorming.missionmap.statistic.progress');
    this.lineChartDataList.push({
      title: `${typeName}: ${title}`,
      data: {
        labels: labels,
        datasets: datasets,
      },
      labelColors: themeColors.getContrastColor(),
    });
  }
}
</script>
