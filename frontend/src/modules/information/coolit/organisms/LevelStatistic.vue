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
            stepSize: 1,
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
import type { ChartData } from 'chart.js';
import * as ideaService from '@/services/idea-service';
import { Idea } from '@/types/api/Idea';
import { AvatarUnicode } from '@/types/enum/AvatarUnicode';
import { GameStep } from '@/modules/information/coolit/output/Participant.vue';
import * as placeable from '@/modules/information/coolit/types/Placeable';
import * as themeColors from '@/utils/themeColors';
import { getRandomColorList } from '@/utils/colors';
import { calculateChartPerIteration } from '@/utils/statistic';

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
    if (!this.ideaId) this.calculateLevelChart();
    this.calculateStarsChart();
    this.calculateAvatarChart(GameStep.Play);
    if (!this.ideaId) this.calculateAvatarChart(GameStep.Select);
    if (this.ideaId) this.calculateTimeChart();
    this.calculateItemChart(GameStep.Play);
    this.calculateItemChart(GameStep.Select);
    this.calculateItemCountChart(GameStep.Play);
    if (!this.ideaId) this.calculateItemCountChart(GameStep.Select);
  }

  calculateLevelChart(): void {
    if (!this.ideaId && this.ideas && this.steps) {
      const filter = (item) => item.parameter.step === GameStep.Play;
      const labels: string[] = this.ideas.map((idea) => idea.keywords);
      const datasets = calculateChartPerIteration(
        this.steps,
        this.ideas,
        this.replayColors,
        (item) => item.parameter.replayCount,
        (item, idea) => item.ideaId === idea.id,
        filter
      );
      this.barChartDataList.push({
        title: this.$t('module.information.coolit.statistic.level'),
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
      const filter = (item) =>
        item.parameter.step === GameStep.Play &&
        (!this.ideaId || item.ideaId === this.ideaId);
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
        title: this.$t('module.information.coolit.statistic.rating'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
      });
    }
  }

  calculateAvatarChart(gameStep: GameStep): void {
    if (this.steps) {
      const filter = (item) =>
        item.parameter.step === gameStep &&
        (!this.ideaId || item.ideaId === this.ideaId);
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
        title: this.$t(`module.information.coolit.statistic.${gameStep}`),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: labelColors,
      });
    }
  }

  calculateTimeChart(): void {
    if (this.steps) {
      const filter = (item) =>
        item.parameter.step === GameStep.Play &&
        (!this.ideaId || item.ideaId === this.ideaId);
      const labels = this.steps
        .filter((item) => filter(item))
        .map((item) => Math.round(item.parameter.time / 1000))
        .filter((value, index, array) => array.indexOf(value) === index)
        .sort((a, b) => a - b);
      const datasets = calculateChartPerIteration(
        this.steps,
        labels,
        this.replayColors,
        (item) => item.parameter.replayCount,
        (item, time) => Math.round(item.parameter.time / 1000) === time,
        filter
      );
      this.barChartDataList.push({
        title: this.$t('module.information.coolit.statistic.time'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
      });
    }
  }

  calculateItemChart(gameStep: GameStep): void {
    if (this.steps) {
      const itemList: { item: placeable.PlaceableBase; index: number }[] = [];
      for (const step of this.steps.filter(
        (item) =>
          (!this.ideaId || item.ideaId === this.ideaId) &&
          item.parameter.step === gameStep &&
          item.parameter.itemList
      )) {
        itemList.push(
          ...step.parameter.itemList.map((item, index) => {
            return {
              item: item,
              index: index,
            };
          })
        );
      }
      const labels = itemList
        .map((item) => item.item.name)
        .filter((value, index, array) => array.indexOf(value) === index)
        .sort();
      const colorCount = Math.max(...itemList.map((item) => item.index + 1));
      const colors =
        this.colorList.length > colorCount
          ? this.colorList
          : getRandomColorList(colorCount);
      const datasets = calculateChartPerIteration(
        itemList,
        labels,
        colors,
        (item) => item.index,
        (item, label) => item.item.name === label
      );
      this.barChartDataList.push({
        title: this.$t(`module.information.coolit.statistic.item.${gameStep}`),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
      });
    }
  }

  calculateItemCountChart(gameStep: GameStep): void {
    if (this.steps) {
      const filter = (item) =>
        item.parameter.step === gameStep &&
        item.parameter.itemList &&
        (!this.ideaId || item.ideaId === this.ideaId);
      const labels = this.steps
        .filter((item) => filter(item))
        .map((item) => item.parameter.itemList.length)
        .filter((value, index, array) => array.indexOf(value) === index)
        .sort((a, b) => a - b);
      const datasets = calculateChartPerIteration(
        this.steps,
        labels,
        this.replayColors,
        (item) => item.parameter.replayCount,
        (item, count) => item.parameter.itemList.length === count,
        filter
      );
      this.barChartDataList.push({
        title: this.$t(
          `module.information.coolit.statistic.itemCount.${gameStep}`
        ),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
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
