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
          stacked: true,
        },
        y: {
          ticks: {
            color: contrastColor,
            precision: 0,
          },
          stacked: true,
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
import { Bar } from 'vue-chartjs';
import * as taskParticipantService from '@/services/task-participant-service';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import type { ChartData, ChartDataset } from 'chart.js';
import * as ideaService from '@/services/idea-service';
import { Idea } from '@/types/api/Idea';
import { AvatarUnicode } from '@/types/enum/AvatarUnicode';
import * as themeColors from '@/utils/themeColors';
import { getRandomColorList } from '@/utils/colors';
import {
  calculateChartPerIteration,
  calculateChartPerParameter,
} from '@/utils/statistic';
import { LevelWorkflowType } from '@/types/game/LevelWorkflowType';

@Options({
  components: { Bar },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class LevelStatistic extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: null }) readonly ideaId!: string | null;
  steps: TaskParticipantIterationStep[] = [];
  ideas: Idea[] = [];

  barChartDataList: {
    title: string;
    data: ChartData;
    labelColors: string[] | string;
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

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      if (!this.ideaId) {
        ideaService.registerGetIdeasForTask(
          this.taskId,
          null,
          null,
          this.updateIdeas,
          EndpointAuthorisationType.MODERATOR,
          2 * 60
        );
      }
      taskParticipantService.registerGetIterationStepList(
        this.taskId,
        this.updateIterationSteps,
        EndpointAuthorisationType.MODERATOR,
        2 * 60
      );
    }
  }

  updateIdeas(ideas: Idea[]): void {
    this.ideas = ideas;
    this.calculateCharts();
  }

  updateIterationSteps(steps: TaskParticipantIterationStep[]): void {
    this.steps = steps;
    const maxReplays = taskParticipantService.addReplayCountToSteps(
      this.steps,
      (param) => param.step
    );
    /*this.replayColors.push(
      ...randomColor({ luminosity: 'dark', count: maxReplays })
    );*/

    this.replayColors.push(...getRandomColorList(maxReplays + 1));
    this.calculateCharts();
  }

  calculateCharts(): void {
    this.barChartDataList = [];
    if (!this.ideaId) {
      this.calculateLevelChartPerState();
      this.calculateLevelChartIteration();
    }
    this.calculateStarsChart();
    this.calculateAvatarChart();
    this.calculateStateParameterChartPerLevel(
      'normalisedTime',
      (value) => Math.round(value / 1000),
      'time'
    );
    this.calculateStateParameterChartPerLevel(
      'normalisedTime',
      (value) => Math.round((value / 60000) * 100),
      'winPoints'
    );
    this.calculateStateParameterChartPerLevel('temperatureRise');
    this.calculateStateParameterChartPerTemperatureRise('moleculeHitCount');
    this.calculateStateParameterChartPerTemperatureRise(
      'moleculeState',
      (value) =>
        Object.values(value).reduce(
          (sum, item: any) => sum + item.movedCount,
          0
        ),
      'moleculeMovedCount'
    );
    this.calculateStateParameterChartPerTemperatureRise(
      'moleculeState',
      (value) =>
        Object.values(value).reduce(
          (sum, item: any) => sum + item.decreaseCount,
          0
        ),
      'moleculeDecreaseCount'
    );
    this.calculateStateParameterChartPerLevel('obstacleHitCount');
    this.calculateStateParameterChartPerLevel('regionHitCount');
    this.calculateStateParameterChartPerLevel('rayCount');
    this.calculateStateParameterChartPerLevel('temperature', (value) =>
      Math.round(value)
    );
  }

  calculateLevelChartPerState(): void {
    if (!this.ideaId && this.ideas && this.steps) {
      const labels: string[] = this.ideas.map((idea) => idea.keywords);
      const datasets = calculateChartPerParameter(
        this.steps,
        Object.values(LevelWorkflowType),
        this.ideas,
        this.replayColors,
        (item, parameter) =>
          (item.parameter.state ?? LevelWorkflowType.approved) === parameter,
        (item, idea) => item.ideaId === idea.id
      );
      this.barChartDataList.push({
        title: this.$t('module.playing.coolit.statistic.level'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
      });
    }
  }

  calculateStateParameterChartPerLevel(
    parameter: string,
    convert: ((value: any) => any) | null = null,
    title: string | null = null
  ): void {
    if (this.ideas && this.steps) {
      const filter = (item) =>
        (!this.ideaId || item.ideaId === this.ideaId) && item.parameter.state;
      const labels: number[] = this.steps
        .filter(
          (item) =>
            filter(item) && item.parameter.state[parameter] !== undefined
        )
        .map((idea) =>
          convert
            ? convert(idea.parameter.state[parameter])
            : idea.parameter.state[parameter]
        )
        .filter((value, index, array) => array.indexOf(value) === index)
        .sort((a, b) => a - b);
      let datasets: ChartDataset[] = [];
      if (this.ideaId) {
        datasets = calculateChartPerIteration(
          this.steps,
          labels,
          this.replayColors,
          (item) => item.parameter.replayCount,
          (item, value) =>
            (convert
              ? convert(item.parameter.state[parameter])
              : item.parameter.state[parameter]) === value,
          filter
        );
      } else {
        datasets = calculateChartPerParameter(
          this.steps,
          this.ideas,
          labels,
          this.replayColors,
          (item, value) => item.ideaId === value.id,
          (item, value) =>
            (convert
              ? convert(item.parameter.state[parameter])
              : item.parameter.state[parameter]) === value,
          filter,
          (list) => list.length,
          (value) => value.keywords
        );
      }
      this.barChartDataList.push({
        title: this.$t(`module.playing.coolit.statistic.${title ?? parameter}`),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
      });
    }
  }

  calculateStateParameterChartPerTemperatureRise(
    parameter: string,
    convert: ((value: any) => any) | null = null,
    title: string | null = null
  ): void {
    if (this.ideas && this.steps) {
      const filter = (item) =>
        (!this.ideaId || item.ideaId === this.ideaId) && item.parameter.state;
      const labels: number[] = this.steps
        .filter(
          (item) =>
            filter(item) && item.parameter.state[parameter] !== undefined
        )
        .map((idea) =>
          convert
            ? convert(idea.parameter.state[parameter])
            : idea.parameter.state[parameter]
        )
        .filter((value, index, array) => array.indexOf(value) === index)
        .sort((a, b) => a - b);
      const legend = this.steps
        .filter(
          (item) =>
            filter(item) && item.parameter.state[parameter] !== undefined
        )
        .map((idea) => idea.parameter.state.temperatureRise)
        .filter((value, index, array) => array.indexOf(value) === index)
        .sort((a, b) => a - b);
      const datasets = calculateChartPerParameter(
        this.steps,
        legend,
        labels,
        this.replayColors,
        (item, value) => item.parameter.state.temperatureRise === value,
        (item, value) =>
          (convert
            ? convert(item.parameter.state[parameter])
            : item.parameter.state[parameter]) === value,
        filter
      );
      this.barChartDataList.push({
        title: this.$t(`module.playing.coolit.statistic.${title ?? parameter}`),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
      });
    }
  }

  calculateLevelChartIteration(): void {
    if (!this.ideaId && this.ideas && this.steps) {
      const labels: string[] = this.ideas.map((idea) => idea.keywords);
      const datasets = calculateChartPerIteration(
        this.steps,
        this.ideas,
        this.replayColors,
        (item) => item.parameter.replayCount,
        (item, idea) => item.ideaId === idea.id
      );
      this.barChartDataList.push({
        title: this.$t('module.playing.coolit.statistic.level'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
      });
    }
  }

  calculateStarsChart(): void {
    if (this.steps) {
      const filter = (item) => !this.ideaId || item.ideaId === this.ideaId;
      const labels: string[] = [
        '-',
        '\uf005',
        '\uf005\uf005',
        '\uf005\uf005\uf005',
      ];
      const datasets = calculateChartPerIteration(
        this.steps,
        [...Array(4).keys()],
        this.replayColors,
        (item) => item.parameter.replayCount,
        (item, stars) => item.parameter.stars === stars,
        filter
      );
      this.barChartDataList.push({
        title: this.$t('module.playing.coolit.statistic.rating'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
      });
    }
  }

  calculateAvatarChart(): void {
    if (this.steps) {
      const filter = (item) => !this.ideaId || item.ideaId === this.ideaId;
      const participants = this.steps
        .filter((item) => filter(item))
        .map((item) => item.avatar)
        .filter(
          (value, index, array) =>
            array.findIndex(
              (item) =>
                item.color === value.color && item.symbol === value.symbol
            ) === index
        );
      const labels: string[] = participants.map(
        (participant) => AvatarUnicode[participant.symbol]
      );
      const labelColors: string[] = participants.map(
        (participant) => participant.color
      );
      const datasets = calculateChartPerIteration(
        this.steps,
        participants,
        this.replayColors,
        (item) => item.parameter.replayCount,
        (item, participant) =>
          item.avatar.color === participant.color &&
          item.avatar.symbol === participant.symbol,
        filter
      );
      this.barChartDataList.push({
        title: this.$t(`module.playing.coolit.statistic.avatar`),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: labelColors,
      });
    }
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateIdeas);
    cashService.deregisterAllGet(this.updateIterationSteps);
  }

  unmounted(): void {
    this.deregisterAll();
  }
}
</script>
