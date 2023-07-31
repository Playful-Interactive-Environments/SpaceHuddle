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
            color: '#1d2948',
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
import type { ChartData, ChartDataset } from 'chart.js';
import * as ideaService from '@/services/idea-service';
import { Idea } from '@/types/api/Idea';
import { AvatarUnicode } from '@/types/enum/AvatarUnicode';
import { GameStep } from '@/modules/information/findit/output/Participant.vue';
import { convertAvatarToString } from '@/types/api/Participant';
import randomColor from 'randomcolor';
import Color from 'colorjs.io';
import DeltaE from 'delta-e';
import * as placeable from '@/modules/information/findit/types/Placeable';

@Options({
  components: { Bar },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModuleStatistic extends Vue {
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

  getRandomColorList(count: number): string[] {
    const uniqueColors: { L: number; A: number; B: number }[] = [];
    const returnColors: string[] = [];
    for (let i = 0; i < count; i++) {
      let tryCount = 0;
      while (i === returnColors.length) {
        tryCount++;
        const colorHex = randomColor({ luminosity: 'dark' });
        const colorLab = new Color(colorHex).to('lab') as any;
        const color = { L: colorLab.l, A: colorLab.a, B: colorLab.b };
        let isUnique = true;
        for (const uniqueColor of uniqueColors) {
          isUnique = DeltaE.getDeltaE00(uniqueColor, color) > 35;
          if (!isUnique) break;
        }
        if (isUnique || tryCount > 50) {
          uniqueColors.push(color);
          returnColors.push(colorHex);
        }
      }
    }
    return returnColors;
  }

  updateIterationSteps(steps: TaskParticipantIterationStep[]): void {
    this.steps = steps;
    const participantLevelCount: { [key: string]: number } = {};
    let maxReplays = 0;
    for (const step of steps) {
      if (step.parameter.step === GameStep.Play) {
        const avatarLevel = `${convertAvatarToString(step.avatar)}-${
          step.ideaId
        }`;
        if (!(avatarLevel in participantLevelCount))
          participantLevelCount[avatarLevel] = 0;
        else participantLevelCount[avatarLevel]++;
        step.parameter.replayCount = participantLevelCount[avatarLevel];
      } else step.parameter.replayCount = 0;
      if (maxReplays < step.parameter.replayCount)
        maxReplays = step.parameter.replayCount;
    }
    /*this.replayColors.push(
      ...randomColor({ luminosity: 'dark', count: maxReplays })
    );*/

    this.replayColors.push(...this.getRandomColorList(maxReplays));
    this.calculateCharts();
  }

  calculateCharts(): void {
    this.barChartDataList = [];
    if (!this.ideaId) this.calculateLevelChart();
    this.calculateStarsChart();
    this.calculateAvatarChart(GameStep.Play);
    if (!this.ideaId) this.calculateAvatarChart(GameStep.Build);
    if (this.ideaId) this.calculateTimeChart();
    this.calculateItemChart(GameStep.Play);
    this.calculateItemChart(GameStep.Build);
  }

  calculateLevelChart(): void {
    if (this.ideas && this.steps) {
      const labels: string[] = [];
      const datasets: ChartDataset[] = [];
      for (
        let i = 0;
        this.steps.find(
          (item) =>
            item.parameter.step === GameStep.Play &&
            item.parameter.replayCount === i
        );
        i++
      ) {
        const dataset = {
          data: [] as number[],
          borderRadius: {
            topRight: 5,
            bottomRight: 5,
            topLeft: 5,
            bottomLeft: 5,
          },
          borderSkipped: false,
          label: (i + 1).toString(),
          backgroundColor: this.replayColors[i],
          color: '#1d2948',
        };
        for (const idea of this.ideas) {
          if (i === 0) labels.push(idea.keywords);
          dataset.data.push(
            this.steps.filter(
              (item) =>
                item.ideaId === idea.id &&
                item.parameter.step === GameStep.Play &&
                item.parameter.replayCount === i
            ).length
          );
        }
        datasets.push(dataset as ChartDataset);
      }
      this.barChartDataList.push({
        title: this.$t('module.information.findit.statistic.level'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: '#1d2948',
      });
    }
  }

  calculateStarsChart(): void {
    if (this.steps) {
      const labels: string[] = [
        '-',
        '\uf005',
        '\uf005\uf005',
        '\uf005\uf005\uf005',
      ];
      const datasets: ChartDataset[] = [];
      for (
        let i = 0;
        this.steps.find(
          (item) =>
            item.parameter.step === GameStep.Play &&
            item.parameter.replayCount === i
        );
        i++
      ) {
        const dataset = {
          label: (i + 1).toString(),
          backgroundColor: this.replayColors[i],
          borderRadius: {
            topRight: 5,
            bottomRight: 5,
            topLeft: 5,
            bottomLeft: 5,
          },
          data: [] as number[],
          borderSkipped: false,
          color: '#1d2948',
        };
        for (let stars = 0; stars <= 3; stars++) {
          dataset.data.push(
            this.steps.filter(
              (item) =>
                item.parameter.stars === stars &&
                (!this.ideaId || item.ideaId === this.ideaId) &&
                item.parameter.step === GameStep.Play &&
                item.parameter.replayCount === i
            ).length
          );
        }
        datasets.push(dataset as ChartDataset);
      }
      this.barChartDataList.push({
        title: this.$t('module.information.findit.statistic.rating'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: '#1d2948',
      });
    }
  }

  calculateAvatarChart(gameStep: GameStep): void {
    if (this.steps) {
      const labels: string[] = [];
      const labelColors: string[] = [];
      const datasets: ChartDataset[] = [];
      for (
        let i = 0;
        this.steps.find(
          (item) =>
            item.parameter.step === gameStep && item.parameter.replayCount === i
        );
        i++
      ) {
        const dataset = {
          label: (i + 1).toString(),
          data: [] as number[],
          borderRadius: {
            topRight: 5,
            bottomRight: 5,
            topLeft: 5,
            bottomLeft: 5,
          },
          borderSkipped: false,
          backgroundColor: this.replayColors[i],
          color: '#1d2948',
        };
        const participants = this.steps
          .map((item) => item.avatar)
          .filter(
            (value, index, array) =>
              array.findIndex(
                (item) =>
                  item.color === value.color && item.symbol === value.symbol
              ) === index
          );
        for (const participant of participants) {
          if (i === 0) {
            labels.push(AvatarUnicode[participant.symbol]);
            labelColors.push(participant.color);
          }
          dataset.data.push(
            this.steps.filter(
              (item) =>
                item.avatar.color === participant.color &&
                item.avatar.symbol === participant.symbol &&
                (!this.ideaId || item.ideaId === this.ideaId) &&
                item.parameter.step === gameStep &&
                item.parameter.replayCount === i
            ).length
          );
        }
        datasets.push(dataset as ChartDataset);
      }
      this.barChartDataList.push({
        title: this.$t(`module.information.findit.statistic.${gameStep}`),
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
      const labels = this.steps
        .map((item) => Math.round(item.parameter.time / 1000))
        .filter((value, index, array) => array.indexOf(value) === index)
        .sort((a, b) => a - b);
      const datasets: ChartDataset[] = [];
      for (
        let i = 0;
        this.steps.find(
          (item) =>
            item.parameter.step === GameStep.Play &&
            item.parameter.replayCount === i
        );
        i++
      ) {
        const dataset = {
          data: [] as number[],
          borderSkipped: false,
          label: (i + 1).toString(),
          backgroundColor: this.replayColors[i],
          borderRadius: {
            topRight: 5,
            bottomRight: 5,
            topLeft: 5,
            bottomLeft: 5,
          },
          color: '#1d2948',
        };
        for (const time of labels) {
          dataset.data.push(
            this.steps.filter(
              (item) =>
                Math.round(item.parameter.time / 1000) === time &&
                (!this.ideaId || item.ideaId === this.ideaId) &&
                item.parameter.step === GameStep.Play &&
                item.parameter.replayCount === i
            ).length
          );
        }
        datasets.push(dataset as ChartDataset);
      }
      this.barChartDataList.push({
        title: this.$t('module.information.findit.statistic.time'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: '#1d2948',
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
      const colors = this.getRandomColorList(
        Math.max(...itemList.map((item) => item.index))
      );
      const datasets: ChartDataset[] = [];
      for (let i = 0; itemList.find((item) => item.index === i); i++) {
        const dataset = {
          label: (i + 1).toString(),
          backgroundColor: colors[i],
          borderRadius: {
            topRight: 5,
            bottomRight: 5,
            topLeft: 5,
            bottomLeft: 5,
          },
          data: [] as number[],
          borderSkipped: false,
          color: '#1d2948',
        };
        for (const label of labels) {
          dataset.data.push(
            itemList.filter(
              (item) => item.item.name === label && item.index === i
            ).length
          );
        }
        datasets.push(dataset as ChartDataset);
      }
      this.barChartDataList.push({
        title: this.$t(`module.information.findit.statistic.item.${gameStep}`),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: '#1d2948',
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
