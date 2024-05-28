<template>
  <Highscore :task-id="taskId" />
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
            precision: 0,
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
      },
    }"
  />
</template>

<script lang="ts">
import { ChartData } from 'chart.js';
import { Options, Vue } from 'vue-class-component';
import { Bar, Line } from 'vue-chartjs';
import { Prop, Watch } from 'vue-property-decorator';
import * as taskParticipantService from '@/services/task-participant-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import { getRandomColorList } from '@/utils/colors';
import { calculateChartPerParameter } from '@/utils/statistic';
import * as themeColors from '@/utils/themeColors';
import { AvatarUnicode } from '@/types/enum/AvatarUnicode';
import * as cashService from '@/services/cash-service';
import Highscore from '@/modules/playing/shopit/organisms/Highscore.vue';

@Options({
  components: { Bar, Line, Highscore },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModuleStatistic extends Vue {
  @Prop() readonly taskId!: string;
  steps: TaskParticipantIterationStep[] = [];

  colorList: string[] = getRandomColorList(20);

  barChartDataList: {
    title: string;
    data: ChartData;
    labelColors: string[] | string;
    stacked: boolean;
  }[] = [];

  displayLabels = true;

  get contrastColor(): string {
    return themeColors.getContrastColor();
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      taskParticipantService.registerGetIterationStepList(
        this.taskId,
        this.updateIterationSteps,
        EndpointAuthorisationType.MODERATOR,
        2 * 60
      );
    }
  }

  updateIterationSteps(steps: TaskParticipantIterationStep[]): void {
    this.steps = steps;
    if (steps.length > 0) {
      //todo iteration + step
      //const maxReplays = steps.sort((a, b) => b.iteration - a.iteration)[0].iteration;
      //this.replayColors.push(...getRandomColorList(maxReplays));
      this.calculateCharts();
    }
  }
  calculateCharts(): void {
    this.filterSteps();
    this.barChartDataList = [];
    this.calculateStarsChart();
    this.calculatePointsSpentChart();
    this.calculateCO2Chart();
    this.calculateElectricityChart();
    this.calculateLifetimeChart();
    this.calculateWaterChart();
    this.calculateMoneyChart();
    this.calculateWinReasonChart();
    this.calculateCardsPlayedLengthChart();
  }

  filterSteps(): void {
    this.steps = this.steps.filter((item) => item.parameter.rate != null);
  }

  calculateStarsChart(): void {
    if (this.steps) {
      const labels: string[] = [
        '-',
        '\uf005',
        '\uf005\uf005',
        '\uf005\uf005\uf005',
      ];
      const avatarList = this.getAvatarList(this.steps);
      const datasets = calculateChartPerParameter(
        this.steps,
        avatarList,
        [...Array(4).keys()],
        avatarList.map((avatar) => avatar.color),
        (item, avatar) =>
          item.avatar.color === avatar.color &&
          item.avatar.symbol === avatar.symbol,
        (item, stars) => item.parameter.rate === stars,
        null,
        (list) => list.length,
        (avatar) => AvatarUnicode[avatar.symbol]
      );
      this.barChartDataList.push({
        title: this.$t('module.playing.shopit.statistic.rating'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
        stacked: true,
      });
    }
  }

  //TODO Object bug in statistic
  calculatePointsSpentChart(): void {
    if (this.steps) {
      const avatarList = this.getAvatarList(this.steps);
      const labels: number[] = this.getPointsSpent();
      const pointsSpent = this.getPointsSpent();
      const datasets = calculateChartPerParameter(
        this.steps,
        avatarList,
        pointsSpent,
        avatarList.map((avatar) => avatar.color),
        (item, avatar) =>
          item.avatar.color === avatar.color &&
          item.avatar.symbol === avatar.symbol,
        (item, pointsSpent) =>
          this.convert(item.parameter.game.pointsSpent) === pointsSpent,
        null,
        (list) => list.length,
        (avatar) => AvatarUnicode[avatar.symbol]
      );
      this.barChartDataList.push({
        title: this.$t('module.playing.shopit.statistic.pointsSpent'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
        stacked: true,
      });
    }
  }

  calculateCO2Chart(): void {
    if (this.steps) {
      const avatarList = this.getAvatarList(this.steps);
      const labels: number[] = this.getCO2();
      const co2 = this.getCO2();
      const datasets = calculateChartPerParameter(
        this.steps,
        avatarList,
        co2,
        avatarList.map((avatar) => avatar.color),
        (item, avatar) =>
          item.avatar.color === avatar.color &&
          item.avatar.symbol === avatar.symbol,
        (item, co2) => this.convert(item.parameter.game.co2) === co2,
        null,
        (list) => list.length,
        (avatar) => AvatarUnicode[avatar.symbol]
      );
      this.barChartDataList.push({
        title: this.$t('module.playing.shopit.statistic.co2'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
        stacked: true,
      });
    }
  }

  calculateElectricityChart(): void {
    if (this.steps) {
      const avatarList = this.getAvatarList(this.steps);
      const labels: number[] = this.getElectricity();
      const electricity = this.getElectricity();
      const datasets = calculateChartPerParameter(
        this.steps,
        avatarList,
        electricity,
        avatarList.map((avatar) => avatar.color),
        (item, avatar) =>
          item.avatar.color === avatar.color &&
          item.avatar.symbol === avatar.symbol,
        (item, electricity) =>
          this.convert(item.parameter.game.electricity) === electricity,
        null,
        (list) => list.length,
        (avatar) => AvatarUnicode[avatar.symbol]
      );
      this.barChartDataList.push({
        title: this.$t('module.playing.shopit.statistic.electricity'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
        stacked: true,
      });
    }
  }

  calculateLifetimeChart(): void {
    if (this.steps) {
      const avatarList = this.getAvatarList(this.steps);
      const labels: number[] = this.getLifetime();
      const lifetime = this.getLifetime();
      const datasets = calculateChartPerParameter(
        this.steps,
        avatarList,
        lifetime,
        avatarList.map((avatar) => avatar.color),
        (item, avatar) =>
          item.avatar.color === avatar.color &&
          item.avatar.symbol === avatar.symbol,
        (item, lifetime) =>
          this.convert(item.parameter.game.lifetime) === lifetime,
        null,
        (list) => list.length,
        (avatar) => AvatarUnicode[avatar.symbol]
      );
      this.barChartDataList.push({
        title: this.$t('module.playing.shopit.statistic.lifetime'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
        stacked: true,
      });
    }
  }

  calculateWaterChart(): void {
    if (this.steps) {
      const avatarList = this.getAvatarList(this.steps);
      const labels: number[] = this.getWater();
      const water = this.getWater();
      const datasets = calculateChartPerParameter(
        this.steps,
        avatarList,
        water,
        avatarList.map((avatar) => avatar.color),
        (item, avatar) =>
          item.avatar.color === avatar.color &&
          item.avatar.symbol === avatar.symbol,
        (item, water) => this.convert(item.parameter.game.water) === water,
        null,
        (list) => list.length,
        (avatar) => AvatarUnicode[avatar.symbol]
      );
      this.barChartDataList.push({
        title: this.$t('module.playing.shopit.statistic.water'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
        stacked: true,
      });
    }
  }

  calculateMoneyChart(): void {
    if (this.steps) {
      const avatarList = this.getAvatarList(this.steps);
      const labels: number[] = this.getMoney();
      const money = this.getMoney();
      const datasets = calculateChartPerParameter(
        this.steps,
        avatarList,
        money,
        avatarList.map((avatar) => avatar.color),
        (item, avatar) =>
          item.avatar.color === avatar.color &&
          item.avatar.symbol === avatar.symbol,
        (item, money) => this.convert(item.parameter.game.money) === money,
        null,
        (list) => list.length,
        (avatar) => AvatarUnicode[avatar.symbol]
      );
      this.barChartDataList.push({
        title: this.$t('module.playing.shopit.statistic.money'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
        stacked: true,
      });
    }
  }

  calculateWinReasonChart(): void {
    if (this.steps) {
      const avatarList = this.getAvatarList(this.steps);
      const labels: string[] = this.getWinReason();
      const winReason = this.getWinReason();
      const datasets = calculateChartPerParameter(
        this.steps,
        avatarList,
        winReason,
        avatarList.map((avatar) => avatar.color),
        (item, avatar) =>
          item.avatar.color === avatar.color &&
          item.avatar.symbol === avatar.symbol,
        (item, winReason) => {
          return item.parameter.game.winReason === winReason;
        },
        null,
        (list) => list.length,
        (avatar) => AvatarUnicode[avatar.symbol]
      );
      this.barChartDataList.push({
        title: this.$t('module.playing.shopit.statistic.winReason'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
        stacked: true,
      });
    }
  }

  calculateCardsPlayedLengthChart(): void {
    if (this.steps) {
      const avatarList = this.getAvatarList(this.steps);
      const labels: number[] = this.getCardsPlayedLength();
      const cardsPlayed = this.getCardsPlayedLength();
      const datasets = calculateChartPerParameter(
        this.steps,
        avatarList,
        cardsPlayed,
        avatarList.map((avatar) => avatar.color),
        (item, avatar) =>
          item.avatar.color === avatar.color &&
          item.avatar.symbol === avatar.symbol,
        (item, cardsPlayed) =>
          item.parameter.game.cardsPlayed.length === cardsPlayed,
        null,
        (list) => list.length,
        (avatar) => AvatarUnicode[avatar.symbol]
      );
      this.barChartDataList.push({
        title: this.$t('module.playing.shopit.statistic.cardsPlayed'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
        stacked: true,
      });
    }
  }

  convert(value: number, accuracy = 10): number {
    return Math.round(value / accuracy) * accuracy;
  }

  getPointsSpent() {
    return this.steps
      .map((item) => this.convert(item.parameter.game.pointsSpent))
      .filter((value, index, array) => array.indexOf(value) === index)
      .sort((a, b) => a - b);
  }

  getRatings() {
    return this.steps
      .map((item) => item.parameter.rate)
      .filter((value, index, array) => array.indexOf(value) === index);
  }

  getCO2() {
    return this.steps
      .map((item) => this.convert(item.parameter.game.co2))
      .filter((value, index, array) => array.indexOf(value) === index)
      .sort((a, b) => a - b);
  }

  getElectricity() {
    return this.steps
      .map((item) => this.convert(item.parameter.game.electricity))
      .filter((value, index, array) => array.indexOf(value) === index)
      .sort((a, b) => a - b);
  }

  getLifetime() {
    return this.steps
      .map((item) => this.convert(item.parameter.game.lifetime))
      .filter((value, index, array) => array.indexOf(value) === index)
      .sort((a, b) => a - b);
  }

  getWater() {
    return this.steps
      .map((item) => this.convert(item.parameter.game.water))
      .filter((value, index, array) => array.indexOf(value) === index)
      .sort((a, b) => a - b);
  }

  getMoney() {
    return this.steps
      .map((item) => this.convert(item.parameter.game.money))
      .filter((value, index, array) => array.indexOf(value) === index)
      .sort((a, b) => a - b);
  }

  getWinReason() {
    return this.steps
      .map((item) => item.parameter.game.winReason)
      .filter((value, index, array) => array.indexOf(value) === index)
      .filter((item) => item != 'lost');
  }

  getCardsPlayedLength() {
    return this.steps
      .map((item) => item.parameter.game.cardsPlayed.length)
      .filter((value, index, array) => array.indexOf(value) === index)
      .sort((a, b) => a - b);
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

  unmounted() {
    this.deregisterAll();
  }

  deregisterAll() {
    cashService.deregisterAllGet(this.updateIterationSteps);
  }

  mounted() {
    document.fonts.onloadingdone = () => {
      this.displayLabels = true;
    };
    setTimeout(() => (this.displayLabels = true), 2000);
  }
}
</script>

<style scoped lang="scss">
.statisticsHeading {
  text-align: center;
  margin-top: 2rem;
}
</style>
