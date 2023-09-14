<template>
  <div v-for="(chartData, index) in lineChartDataList" :key="index">
    <Line
      :data="chartData.data"
      :height="150"
      :options="{
        maintainAspectRatio: false,
        animation: {
          duration: 0,
        },
        scales: {
          x: {
            ticks: {
              color: chartData.labelColors,
            },
            grid: {
              display: false,
            },
          },
          y: {
            ticks: {
              stepSize: 1,
            },
          },
        },
        plugins: {
          legend: {
            display: chartData.data.datasets.length > 1 && displayLabels,
            position: 'right',
            labels: {
              color: colorPrimary,
              usePointStyle: true,
            },
          },
          title: {
            display: lineChartDataList.length > 1,
            text: chartData.title,
          },
          annotation: {
            annotations: chartAnnotations(chartData),
          },
        },
      }"
    />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import gameConfig from '@/modules/brainstorming/missionmap/data/gameConfig.json';
import gameConfigMoveIt from '@/modules/playing/moveit/data/gameConfig.json';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import * as themeColors from '@/utils/themeColors';
import { Bar, Line } from 'vue-chartjs';
import { ChartData } from 'chart.js/dist/types';
import { Idea } from '@/types/api/Idea';
import { Vote, VoteParameterResult } from '@/types/api/Vote';
import {
  calculateChartPerParameter,
  CalculationType,
  getCalculationForType,
} from '@/utils/statistic';
import * as progress from '@/modules/brainstorming/missionmap/utils/progress';
import { CombinedInputManager } from '@/types/input/CombinedInputManager';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
import * as cashService from '@/services/cash-service';
import { Module } from '@/types/api/Module';

export enum MissionProgressParameter {
  influenceAreas = 'influenceAreas',
  electricity = 'electricity',
}

interface LineChartData {
  title: string;
  data: ChartData;
  labelColors: string[] | string;
}

@Options({
  computed: {
    gameConfig() {
      return gameConfig;
    },
  },
  components: {
    FontAwesomeIcon,
    Bar,
    Line,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class MissionProgressChart extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: MissionProgressParameter.influenceAreas })
  readonly missionProgressParameter!: MissionProgressParameter;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  readonly authHeaderTyp!: EndpointAuthorisationType;
  lineChartDataList: LineChartData[] = [];
  module!: Module;
  ideas: Idea[] = [];
  inputManager!: CombinedInputManager;
  decidedIdeas: Idea[] = [];
  displayLabels = false;

  chartAnnotations(chartData: LineChartData): any {
    if (
      this.missionProgressParameter === MissionProgressParameter.influenceAreas
    ) {
      const xMax = chartData.data.labels ? chartData.data.labels.length : 0;
      return {
        box1: {
          type: 'box',
          xMin: 0,
          xMax: xMax,
          yMin: 10,
          yMax: 3,
          backgroundColor: this.positiveColorTransparent,
          borderColor: 'transparent',
        },
        box2: {
          type: 'box',
          xMin: 0,
          xMax: xMax,
          yMin: 3,
          yMax: -2,
          backgroundColor: this.naturalColorTransparent,
          borderColor: 'transparent',
        },
        box3: {
          type: 'box',
          xMin: 0,
          xMax: xMax,
          yMin: -2,
          yMax: -10,
          backgroundColor: this.negativeColorTransparent,
          borderColor: 'transparent',
        },
      };
    }
    return {};
  }

  mounted(): void {
    this.displayLabels = true;
    setTimeout(() => (this.displayLabels = false), 10);
    document.fonts.onloadingdone = () => {
      this.displayLabels = true;
    };
    setTimeout(() => (this.displayLabels = true), 2000);
  }

  get colorPrimary(): string {
    return themeColors.convertToRGBA(themeColors.getContrastColor());
  }

  get negativeColorTransparent(): string {
    return themeColors.convertToRGBA(themeColors.getRedColor(), 0.25);
  }

  get naturalColorTransparent(): string {
    return themeColors.convertToRGBA(themeColors.getYellowColor(), 0.25);
  }

  get positiveColorTransparent(): string {
    return themeColors.convertToRGBA(themeColors.getGreenColor(), 0.25);
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      this.inputManager = new CombinedInputManager(
        this.taskId,
        IdeaSortOrder.TIMESTAMP,
        this.authHeaderTyp,
        true,
        'points'
      );
      this.inputManager.callbackUpdateIdeas = this.updateIdeas;
      this.inputManager.callbackUpdateVotes = this.updateVotes;
      taskService.registerGetTaskById(this.taskId, this.updateTask);
    }
  }

  updateTask(task: Task): void {
    const module = task.modules.find((module) => module.name === 'missionmap');
    if (module) this.module = module;
    this.calculateCharts();
  }

  @Watch('missionProgressParameter', { immediate: true })
  onMissionProgressParameterChanged(): void {
    this.calculateCharts();
  }

  calculateCharts(): void {
    this.lineChartDataList = [];
    if (this.module) {
      this.calculateLineChartByProgression(this.decidedIdeas);
      if (this.authHeaderTyp === EndpointAuthorisationType.PARTICIPANT) {
        this.calculateLineChartByProgression(this.decidedIdeas, 'own', true);
        this.calculateLineChartByProgression(this.ideas, 'ownFuture', true);
      }
    }
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    if (this.inputManager) this.inputManager.deregisterAll();
  }

  unmounted(): void {
    this.deregisterAll();
  }

  updateIdeas(): void {
    const ideas = this.inputManager.ideas;
    this.ideas = ideas.filter((idea) => idea.parameter.shareData);
    this.calculateDecidedIdeas();
    this.calculateCharts();
  }

  updateVotes(): void {
    this.calculateDecidedIdeas();
    this.calculateCharts();
  }

  calculateDecidedIdeas(): void {
    if (this.inputManager) {
      this.decidedIdeas = progress.calculateDecidedIdeasFromResult(
        this.inputManager.votingResult,
        this.ideas
      );
    }
  }

  calculateLineChartByProgression(
    decidedIdeas: Idea[],
    title = 'general',
    mapOnlyOwnInfluence = false
  ): void {
    const displayParameter =
      this.missionProgressParameter === MissionProgressParameter.influenceAreas
        ? gameConfig.parameter
        : gameConfigMoveIt.electricity;
    let ideaVotes = decidedIdeas.map((idea) => {
      return {
        idea: idea,
        ownVotes: [] as Vote[],
        vote: undefined as VoteParameterResult | undefined,
      };
    });
    let mapToValue = (list, parameter) =>
      list.map((item) =>
        item.idea
          ? item.idea.parameter[this.missionProgressParameter][parameter]
          : 0
      );
    if (mapOnlyOwnInfluence) {
      ideaVotes = decidedIdeas
        .map((idea) => {
          return {
            idea: idea,
            ownVotes: this.inputManager.votes.filter(
              (vote) => vote.ideaId === idea.id && vote.parameter.points
            ),
            vote: this.inputManager.votingResult.find(
              (vote) => vote.ideaId === idea.id
            ),
          };
        })
        .filter((item) => item.ownVotes.length > 0)
        .sort((a, b) =>
          (a.ownVotes[0] as Vote).timestamp.localeCompare(
            (b.ownVotes[0] as Vote).timestamp
          )
        );
      mapToValue = (list, parameter) =>
        list.map((item) => {
          if (
            item.idea &&
            item.vote &&
            item.ownVotes &&
            item.ownVotes.length > 0
          ) {
            const ownSum = item.ownVotes.reduce(
              (sum, item) => sum + item.parameter.points,
              0
            );
            return (
              item.idea.parameter[this.missionProgressParameter][parameter] *
              (ownSum / item.vote.sum)
            );
          }
          return 0;
        });
    }
    const ideaProgress: {
      index: number;
      idea: Idea | null;
      ownVotes: Vote[];
      vote: VoteParameterResult | undefined;
    }[] = [{ index: -1, idea: null, vote: undefined, ownVotes: [] }];
    for (let i = 0; i < ideaVotes.length; i++) {
      ideaProgress.push({
        index: i,
        idea: ideaVotes[i].idea,
        ownVotes: ideaVotes[i].ownVotes,
        vote: ideaVotes[i].vote,
      });
    }
    const labels = ideaProgress.map((item) =>
      item.idea
        ? item.idea.keywords
        : this.$t('module.brainstorming.missionmap.enum.progress.origin')
    );
    const datasets = calculateChartPerParameter(
      ideaProgress,
      Object.keys(displayParameter),
      ideaProgress,
      Object.values(displayParameter).map((item) => item.color),
      () => true,
      (item, progress) => item.index <= progress.index,
      null,
      (list, loopItem, parameter) => {
        const sum = getCalculationForType(CalculationType.Sum)(
          mapToValue(list, parameter)
        );
        if (
          this.missionProgressParameter ===
          MissionProgressParameter.influenceAreas
        )
          return this.module.parameter[parameter] + sum;
        else return displayParameter[parameter].value + sum;
      },
      (parameter) => displayParameter[parameter].iconCode
    );
    for (const dataset of datasets) {
      (dataset as any).pointStyle = 'circle';
    }
    this.lineChartDataList.push({
      title: this.$t(
        `module.brainstorming.missionmap.enum.progressLong.${title}`
      ),
      data: {
        labels: labels,
        datasets: datasets,
      },
      labelColors: themeColors.getContrastColor(),
    });
  }
}
</script>

<style lang="scss" scoped>
.el-form-item::v-deep(.el-form-item__label) {
  color: var(--parameter-color);
}

.el-form-item .el-slider {
  --el-slider-runway-bg-color: color-mix(
    in srgb,
    var(--state-color) 30%,
    transparent
  );
  --el-slider-disabled-color: var(--state-color);
}

.el-tabs::v-deep(.el-tabs__nav-next),
.el-tabs::v-deep(.el-tabs__nav-prev) {
  line-height: unset;
}
</style>
