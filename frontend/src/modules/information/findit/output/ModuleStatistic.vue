<template>
  <Bar
    v-for="(chartData, index) in chartDataList"
    :key="index"
    :data="chartData.data"
    :height="100"
    :options="{
      maintainAspectRatio: true,
      animation: {
        duration: 0,
      },
      scales: {
        x: {
          display: displayLabels,
          ticks: {
            color: '#1d2948',
          },
          grid: {
            display: false,
          },
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
          display: false,
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
import { GameStep } from '@/modules/information/findit/output/Participant.vue';

@Options({
  components: { Bar },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModuleStatistic extends Vue {
  @Prop() readonly taskId!: string;
  ideas: Idea[] = [];
  steps: TaskParticipantIterationStep[] = [];

  chartDataList: { title: string; data: ChartData }[] = [];
  displayLabels = false;

  mounted(): void {
    document.fonts.onloadingdone = () => {
      this.displayLabels = true;
    };
    setTimeout(() => (this.displayLabels = true), 2000);
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      ideaService.registerGetIdeasForTask(
        this.taskId,
        null,
        null,
        this.updateIdeas,
        EndpointAuthorisationType.PARTICIPANT,
        2 * 60
      );
      taskParticipantService.registerGetIterationStepList(
        this.taskId,
        this.updateIterationSteps,
        EndpointAuthorisationType.PARTICIPANT,
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
    this.calculateCharts();
  }

  calculateCharts(): void {
    this.chartDataList = [];
    this.calculateLevelChart();
    this.calculateStarsChart();
    this.calculateAvatarChart(GameStep.Play);
    this.calculateAvatarChart(GameStep.Build);
  }

  calculateLevelChart(): void {
    if (this.ideas && this.steps) {
      const labels: string[] = [];
      const datasets = [
        {
          data: [] as number[],
          borderRadius: 5,
          borderSkipped: false,
          backgroundColor: '#1d2948',
          color: '#1d2948',
        },
      ];
      for (const idea of this.ideas) {
        labels.push(idea.keywords);
        datasets[0].data.push(
          this.steps.filter(
            (item) =>
              item.ideaId === idea.id && item.parameter.step === GameStep.Play
          ).length
        );
      }
      this.chartDataList.push({
        title: this.$t('module.information.findit.statistic.level'),
        data: {
          labels: labels,
          datasets: datasets,
        },
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
      const datasets = [
        {
          data: [] as number[],
          borderRadius: 5,
          borderSkipped: false,
          backgroundColor: '#1d2948',
          color: '#1d2948',
        },
      ];
      for (let stars = 0; stars <= 3; stars++) {
        datasets[0].data.push(
          this.steps.filter(
            (item) =>
              item.parameter.stars === stars &&
              item.parameter.step === GameStep.Play
          ).length
        );
      }
      this.chartDataList.push({
        title: this.$t('module.information.findit.statistic.rating'),
        data: {
          labels: labels,
          datasets: datasets,
        },
      });
    }
  }

  calculateAvatarChart(gameStep: GameStep): void {
    if (this.steps) {
      const labels: string[] = [];
      const datasets = [
        {
          data: [] as number[],
          borderRadius: 5,
          borderSkipped: false,
          backgroundColor: [] as string[],
          color: '#1d2948',
        },
      ];
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
        labels.push(AvatarUnicode[participant.symbol]);
        datasets[0].backgroundColor.push(participant.color);
        datasets[0].data.push(
          this.steps.filter(
            (item) =>
              item.avatar.color === participant.color &&
              item.avatar.symbol === participant.symbol &&
              item.parameter.step === gameStep
          ).length
        );
      }
      this.chartDataList.push({
        title: this.$t(`module.information.findit.statistic.${gameStep}`),
        data: {
          labels: labels,
          datasets: datasets,
        },
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
