<template>
  <Bar
    v-for="(chartData, index) in barChartDataList"
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
import { AvatarUnicode } from '@/types/enum/AvatarUnicode';
import { GameStep } from '@/modules/information/findit/output/Participant.vue';

@Options({
  components: { Bar },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModuleStatistic extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly ideaId!: string;
  steps: TaskParticipantIterationStep[] = [];

  barChartDataList: { title: string; data: ChartData }[] = [];
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
      taskParticipantService.registerGetIterationStepList(
        this.taskId,
        this.updateIterationSteps,
        EndpointAuthorisationType.PARTICIPANT,
        2 * 60
      );
    }
  }

  updateIterationSteps(steps: TaskParticipantIterationStep[]): void {
    this.steps = steps;
    this.calculateCharts();
  }

  calculateCharts(): void {
    this.barChartDataList = [];
    this.calculateStarsChart();
    this.calculateAvatarChart();
    this.calculateTimeChart();
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
              item.ideaId === this.ideaId &&
              item.parameter.step === GameStep.Play
          ).length
        );
      }
      this.barChartDataList.push({
        title: this.$t('module.information.findit.statistic.rating'),
        data: {
          labels: labels,
          datasets: datasets,
        },
      });
    }
  }

  calculateAvatarChart(): void {
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
              item.ideaId === this.ideaId &&
              item.parameter.step === GameStep.Play
          ).length
        );
      }
      this.barChartDataList.push({
        title: this.$t(`module.information.findit.statistic.play`),
        data: {
          labels: labels,
          datasets: datasets,
        },
      });
    }
  }

  calculateTimeChart(): void {
    if (this.steps) {
      const labels = this.steps
        .map((item) => Math.round(item.parameter.time / 1000))
        .filter((value, index, array) => array.indexOf(value) === index)
        .sort((a, b) => a - b);
      const datasets = [
        {
          data: [] as number[],
          borderRadius: 5,
          borderSkipped: false,
          backgroundColor: '#1d2948',
          color: '#1d2948',
        },
      ];
      for (const time of labels) {
        datasets[0].data.push(
          this.steps.filter(
            (item) =>
              Math.round(item.parameter.time / 1000) === time &&
              item.ideaId === this.ideaId &&
              item.parameter.step === GameStep.Play
          ).length
        );
      }
      this.barChartDataList.push({
        title: this.$t('module.information.findit.statistic.time'),
        data: {
          labels: labels,
          datasets: datasets,
        },
      });
    }
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateIterationSteps);
  }

  unmounted(): void {
    this.deregisterAll();
  }
}
</script>
