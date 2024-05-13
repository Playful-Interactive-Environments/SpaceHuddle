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
            precision: 0,
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
import type { ChartData } from 'chart.js';
import * as themeColors from '@/utils/themeColors';
import { getRandomColorList } from '@/utils/colors';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import {
  calculateChartPerIteration,
  calculateChartPerParameter,
  mapArrayToConstantSize,
  getCalculationForType,
  CalculationType,
} from '@/utils/statistic';
import * as gameConfig from '@/modules/playing/moveit/data/gameConfig.json';
import * as vehicleCalculation from '@/modules/playing/moveit/types/Vehicle';
import {
  NavigationType,
  MovingType,
} from '@/modules/playing/moveit/organisms/SelectChallenge.vue';
import { AvatarUnicode } from '@/types/enum/AvatarUnicode';
import Highscore from '@/modules/playing/moveit/organisms/Highscore.vue';

@Options({
  components: { Highscore, Bar, Line },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModuleStatistic extends Vue {
  @Prop() readonly taskId!: string;
  steps: TaskParticipantIterationStep[] = [];

  barChartDataList: {
    title: string;
    data: ChartData;
    labelColors: string[] | string;
    stacked: boolean;
  }[] = [];
  lineChartDataList: {
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
      const maxReplays = steps.sort((a, b) => b.iteration - a.iteration)[0]
        .iteration;
      this.replayColors.push(...getRandomColorList(maxReplays));
      this.calculateCharts();
    }
  }

  getVehicleList(
    filter: ((item) => boolean) | null = null
  ): vehicleCalculation.Vehicle[] {
    const subset = filter ? this.steps.filter(filter) : this.steps;
    return subset
      .map((item) => item.parameter.vehicle)
      .filter(
        (value, index, array) =>
          array.findIndex((item) =>
            vehicleCalculation.isSameVehicle(item, value)
          ) === index
      )
      .sort((a, b) => vehicleCalculation.vehicleCompare(a, b));
  }

  calculateCharts(): void {
    this.barChartDataList = [];
    this.lineChartDataList = [];
    this.calculateStarsChart();
    this.calculateVehicleCategoryChart();
    this.calculateVehicleTypeChart();
    this.calculateNavigationTypeChart();
    this.calculateMovingTypeChart();
    this.calculateDrivingParameterChart('stops');
    this.calculatePersonsChart();
    this.calculateSpeedChart('max');
    this.calculateSpeedChart('average');
    this.calculateTimeChart('playTime');
    this.calculateTimeChart('selectTime');
    this.calculateTimeChart('driveTime');
    this.calculateTimeChart('cleanupTime');
    this.calculateParticleChart('totalCount', CalculationType.Average);
    this.calculateParticleChart('totalCount', CalculationType.Min);
    this.calculateParticleChart('totalCount', CalculationType.Max);
    this.calculateParticleChart('collectedCount', CalculationType.Average);
    this.calculateParticleChart('collectedCount', CalculationType.Min);
    this.calculateParticleChart('collectedCount', CalculationType.Max);
    this.calculateLineChart(CalculationType.Average, 'speed');
    this.calculateLineChart(CalculationType.Min, 'speed');
    this.calculateLineChart(CalculationType.Max, 'speed');
    this.calculateLineChartByCalculationType('persons');
  }

  calculateStarsChart(): void {
    if (this.steps) {
      const labels: string[] = [
        '-',
        '\uf005',
        '\uf005\uf005',
        '\uf005\uf005\uf005',
      ];
      const vehicleList = this.getVehicleList(null);
      const datasets = calculateChartPerParameter(
        this.steps,
        vehicleList,
        [...Array(4).keys()],
        this.colorList,
        (item, parameter) =>
          vehicleCalculation.isSameVehicle(item.parameter.vehicle, parameter),
        (item, stars) =>
          item.parameter.rate === stars && item.parameter.rate != -1,
        null,
        (list) => list.length,
        (vehicle) => vehicleCalculation.vehicleToString(vehicle)
      );
      this.barChartDataList.push({
        title: this.$t('module.playing.moveit.statistic.rating'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
        stacked: true,
      });
    }
  }

  calculateVehicleCategoryChart(): void {
    if (this.steps) {
      const labels: string[] = this.steps
        .map((item) => item.parameter.vehicle.category)
        .filter((value, index, array) => array.indexOf(value) === index)
        .sort();
      const datasets = calculateChartPerIteration(
        this.steps,
        labels,
        this.replayColors,
        (item) => item.iteration - 1,
        (item, category) => item.parameter.vehicle.category === category
      );
      this.barChartDataList.push({
        title: this.$t('module.playing.moveit.statistic.vehicleCategory'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
        stacked: true,
      });
    }
  }

  calculateVehicleTypeChart(): void {
    if (this.steps) {
      const vehicleList = this.getVehicleList();
      const labels: string[] = vehicleList.map((item) =>
        vehicleCalculation.vehicleToString(item)
      );
      const datasets = calculateChartPerIteration(
        this.steps,
        vehicleList,
        this.replayColors,
        (item) => item.iteration - 1,
        (item, vehicle) =>
          vehicleCalculation.isSameVehicle(item.parameter.vehicle, vehicle)
      );
      this.barChartDataList.push({
        title: this.$t('module.playing.moveit.statistic.vehicleType'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
        stacked: true,
      });
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

  calculateNavigationTypeChart(): void {
    if (this.steps) {
      const navigationList = Object.keys(NavigationType);
      const avatarList = this.getAvatarList(this.steps);
      const datasets = calculateChartPerParameter(
        this.steps,
        avatarList,
        navigationList,
        avatarList.map((avatar) => avatar.color),
        (item, avatar) =>
          item.avatar.color === avatar.color &&
          item.avatar.symbol === avatar.symbol,
        (item, navigation) => navigation === item.parameter.navigation,
        null,
        (list) => list.length,
        (avatar) => AvatarUnicode[avatar.symbol]
      );
      this.barChartDataList.push({
        title: this.$t('module.playing.moveit.statistic.navigationType'),
        data: {
          labels: navigationList,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
        stacked: true,
      });
    }
  }

  calculateMovingTypeChart(): void {
    if (this.steps) {
      const movingTypeList = Object.keys(MovingType);
      const avatarList = this.getAvatarList(this.steps);
      const datasets = calculateChartPerParameter(
        this.steps,
        avatarList,
        movingTypeList,
        avatarList.map((avatar) => avatar.color),
        (item, avatar) =>
          item.avatar.color === avatar.color &&
          item.avatar.symbol === avatar.symbol,
        (item, movingType) => movingType === item.parameter.movingType,
        null,
        (list) => list.length,
        (avatar) => AvatarUnicode[avatar.symbol]
      );
      this.barChartDataList.push({
        title: this.$t('module.playing.moveit.statistic.movingType'),
        data: {
          labels: movingTypeList,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
        stacked: true,
      });
    }
  }

  calculateDrivingParameterChart(parameterName: string): void {
    if (this.steps) {
      const filter = (item) => item.parameter.drive;
      const labels: string[] = this.steps
        .filter((item) => filter(item))
        .map((item) => item.parameter.drive[parameterName])
        .filter((value, index, array) => array.indexOf(value) === index)
        .sort((a, b) => a - b);
      const vehicleList = this.getVehicleList(filter);
      const datasets = calculateChartPerParameter(
        this.steps,
        vehicleList,
        labels,
        this.colorList,
        (item, parameter) =>
          vehicleCalculation.isSameVehicle(item.parameter.vehicle, parameter),
        (item, parameterValue) =>
          item.parameter.drive[parameterName] === parameterValue,
        filter,
        (list) => list.length,
        (vehicle) => vehicleCalculation.vehicleToString(vehicle)
      );
      this.barChartDataList.push({
        title: this.$t(`module.playing.moveit.statistic.${parameterName}`),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
        stacked: true,
      });
    }
  }

  calculatePersonsChart(): void {
    if (this.steps) {
      for (const iteration of this.steps) {
        if (
          iteration.parameter.drive &&
          iteration.parameter.drive.trackingData &&
          !iteration.parameter.drive.persons
        ) {
          const trackingData = iteration.parameter.drive.trackingData;
          if (trackingData.length > 0) {
            const lastTrackingData = trackingData[trackingData.length - 1];
            iteration.parameter.drive.persons = lastTrackingData.persons;
          }
        }
      }
      const filter = (item) =>
        item.parameter.drive &&
        item.parameter.vehicle.category === 'bus' &&
        item.parameter.drive.persons;
      const labels: string[] = this.steps
        .filter((item) => filter(item))
        .map((item) => item.parameter.drive.persons)
        .filter((value, index, array) => array.indexOf(value) === index)
        .sort((a, b) => a - b);
      const datasets = calculateChartPerIteration(
        this.steps,
        labels,
        this.replayColors,
        (item) => item.iteration - 1,
        (item, persons) => item.parameter.drive.persons === persons,
        filter
      );
      this.barChartDataList.push({
        title: this.$t('module.playing.moveit.statistic.persons'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
        stacked: true,
      });
    }
  }

  calculateSpeedChart(type: string): void {
    if (this.steps) {
      for (const iteration of this.steps) {
        if (
          iteration.parameter.drive &&
          iteration.parameter.drive.trackingData
        ) {
          if (type === 'max') {
            const speedList = iteration.parameter.drive.trackingData
              .map((item) => item.speed)
              .sort((a, b) => b - a);
            if (speedList.length > 0) {
              iteration.parameter.drive.calcSpeed =
                Math.round(speedList[0] / 10) * 10;
            }
          } else if (type === 'average') {
            const speedList = iteration.parameter.drive.trackingData.map(
              (item) => item.speed
            );
            if (speedList.length > 0) {
              const speed =
                speedList.reduce((prev, curr) => prev + curr, 0) /
                speedList.length;
              iteration.parameter.drive.calcSpeed = Math.round(speed / 10) * 10;
            }
          }
        }
      }
      const filter = (item) =>
        item.parameter.drive && item.parameter.drive.calcSpeed;
      const labels: string[] = this.steps
        .filter((item) => filter(item))
        .map((item) => item.parameter.drive.calcSpeed)
        .filter((value, index, array) => array.indexOf(value) === index)
        .sort((a, b) => a - b);
      const vehicleList = this.getVehicleList(filter);
      const datasets = calculateChartPerParameter(
        this.steps,
        vehicleList,
        labels,
        this.colorList,
        (item, parameter) =>
          vehicleCalculation.isSameVehicle(item.parameter.vehicle, parameter),
        (item, calcSpeed) => item.parameter.drive.calcSpeed === calcSpeed,
        filter,
        (list) => list.length,
        (vehicle) => vehicleCalculation.vehicleToString(vehicle)
      );
      this.barChartDataList.push({
        title: this.$t(`module.playing.moveit.statistic.speed.${type}`),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
        stacked: true,
      });
    }
  }

  calculateTimeChart(timeType: string): void {
    if (this.steps) {
      const filter = (item) => item.parameter[timeType];
      const labels: number[] = this.steps
        .filter((item) => filter(item))
        .map((item) => Math.round(item.parameter[timeType] / 1000))
        .filter((value, index, array) => array.indexOf(value) === index)
        .sort((a, b) => a - b);
      const vehicleList = this.getVehicleList(filter);
      const datasets = calculateChartPerParameter(
        this.steps,
        vehicleList,
        labels,
        this.colorList,
        (item, parameter) =>
          vehicleCalculation.isSameVehicle(item.parameter.vehicle, parameter),
        (item, time) => Math.round(item.parameter[timeType] / 1000) === time,
        filter,
        (list) => list.length,
        (vehicle) => vehicleCalculation.vehicleToString(vehicle)
      );
      this.barChartDataList.push({
        title: this.$t(`module.playing.moveit.statistic.${timeType}`),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
        stacked: true,
      });
    }
  }

  calculateParticleChart(
    value: string,
    calculationType: CalculationType
  ): void {
    if (this.steps) {
      const mapToValue = (list, label) =>
        list
          .filter((item) => item.parameter.particleState[label])
          .map((item) => item.parameter.particleState[label][value]);
      const filter = (item) =>
        item.parameter.particleState &&
        Object.keys(item.parameter.particleState).length > 0;
      const vehicleList = this.getVehicleList(filter);
      const labels: string[] = Object.keys(gameConfig.particles);
      const datasets = calculateChartPerParameter(
        this.steps,
        vehicleList,
        labels,
        this.colorList,
        (item, parameter) =>
          vehicleCalculation.isSameVehicle(item.parameter.vehicle, parameter),
        null,
        filter,
        (list, label) =>
          getCalculationForType(calculationType)(mapToValue(list, label)),
        (vehicle) => vehicleCalculation.vehicleToString(vehicle)
      );
      this.barChartDataList.push({
        title: this.$t(
          `module.playing.moveit.statistic.particleState.${value}.${calculationType}`
        ),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
        stacked: false,
      });
    }
  }

  calculateLineChart(
    calculationType: CalculationType,
    parameterName: string
  ): void {
    if (this.steps) {
      const mappingLength = 100;
      const mapToValue = (list, label) =>
        list
          .filter((item) => item.parameter.drive.trackingData)
          .map((item) => {
            return mapArrayToConstantSize(
              item.parameter.drive.trackingData,
              (item) => item[parameterName],
              label,
              mappingLength
            );
          });
      const filter = (item) =>
        item.parameter.drive.trackingData &&
        item.parameter.drive.trackingData.length > 0;
      const vehicleList = this.getVehicleList(filter);
      const labels: number[] = [...Array(mappingLength).keys()];
      const datasets = calculateChartPerParameter(
        this.steps,
        vehicleList,
        labels,
        this.colorList,
        (item, parameter) =>
          vehicleCalculation.isSameVehicle(item.parameter.vehicle, parameter),
        null,
        filter,
        (list, label) =>
          getCalculationForType(calculationType)(mapToValue(list, label)),
        (vehicle) => vehicleCalculation.vehicleToString(vehicle)
      );
      this.lineChartDataList.push({
        title: this.$t(
          `module.playing.moveit.statistic.line.${parameterName}.${calculationType}`
        ),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
      });
    }
  }

  calculateLineChartByCalculationType(parameterName: string): void {
    if (this.steps) {
      const mappingLength = 100;
      const mapToValue = (list, label) =>
        list
          .filter((item) => item.parameter.drive.trackingData)
          .map((item) => {
            return mapArrayToConstantSize(
              item.parameter.drive.trackingData,
              (item) => item[parameterName],
              label,
              mappingLength
            );
          });
      const filter = (item) =>
        item.parameter.drive.trackingData &&
        item.parameter.drive.trackingData.length > 0 &&
        item.parameter.vehicle.category === 'bus';
      const compareList = [
        CalculationType.Average,
        CalculationType.Min,
        CalculationType.Max,
      ];
      const labels: number[] = [...Array(mappingLength).keys()];
      const datasets = calculateChartPerParameter(
        this.steps,
        compareList,
        labels,
        this.colorList,
        () => true,
        null,
        filter,
        (list, label, parameter) =>
          getCalculationForType(parameter)(mapToValue(list, label))
      );
      this.lineChartDataList.push({
        title: this.$t(
          `module.playing.moveit.statistic.line.${parameterName}.mix`
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
    cashService.deregisterAllGet(this.updateIterationSteps);
  }

  unmounted(): void {
    this.deregisterAll();
  }
}
</script>
