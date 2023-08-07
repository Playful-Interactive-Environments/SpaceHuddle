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
import type { ChartData } from 'chart.js';
import * as themeColors from '@/utils/themeColors';
import { getRandomColorList } from '@/utils/colors';
import { TaskParticipantIteration } from '@/types/api/TaskParticipantIteration';
import {
  calculateChartPerIteration,
  calculateChartPerParameter,
  mapArrayToConstantSize,
  CalculationType,
} from '@/utils/statistic';
import * as gameConfig from '@/modules/information/moveit/data/gameConfig.json';

@Options({
  components: { Bar, Line },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModuleStatistic extends Vue {
  @Prop() readonly taskId!: string;
  iterations: TaskParticipantIteration[] = [];

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
      taskParticipantService.registerGetIterationList(
        this.taskId,
        this.updateIterations,
        EndpointAuthorisationType.MODERATOR,
        2 * 60
      );
    }
  }

  updateIterations(iterations: TaskParticipantIteration[]): void {
    this.iterations = iterations;
    if (iterations.length > 0) {
      const maxReplays = iterations.sort((a, b) => b.iteration - a.iteration)[0]
        .iteration;
      this.replayColors.push(...getRandomColorList(maxReplays));
      this.calculateCharts();
    }
  }

  getVehicleList(filter: ((item) => boolean) | null = null): any[] {
    const subset = filter ? this.iterations.filter(filter) : this.iterations;
    return subset
      .map((item) => item.parameter.vehicle)
      .filter(
        (value, index, array) =>
          array.findIndex(
            (item) =>
              item.category === value.category && item.type === value.type
          ) === index
      )
      .sort((a, b) => {
        const x = `${a.category} - ${a.type}`;
        const y = `${b.category} - ${b.type}`;
        if (x < y) return -1;
        if (x > y) return 1;
        return 0;
      });
  }

  getCalculationForType(calculationType: CalculationType): (list) => number {
    let calculation = (list) => list.length;
    if (calculationType === CalculationType.Max)
      calculation = (list) => Math.max(...list);
    if (calculationType === CalculationType.Min)
      calculation = (list) => Math.min(...list);
    if (calculationType === CalculationType.Average)
      calculation = (list) =>
        list.reduce((prev, curr) => prev + curr, 0) / list.length;
    if (calculationType === CalculationType.Sum)
      calculation = (list) => list.reduce((prev, curr) => prev + curr, 0);

    return calculation;
  }

  calculateCharts(): void {
    this.barChartDataList = [];
    this.lineChartDataList = [];
    this.calculateStarsChart();
    this.calculateVehicleCategoryChart();
    this.calculateVehicleTypeChart();
    this.calculateStopsChart();
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
    if (this.iterations) {
      const labels: string[] = [
        '-',
        '\uf005',
        '\uf005\uf005',
        '\uf005\uf005\uf005',
      ];
      const vehicleList = this.getVehicleList(null);
      const datasets = calculateChartPerParameter(
        this.iterations,
        vehicleList,
        [...Array(3).keys()],
        this.colorList,
        (item, parameter) =>
          item.parameter.vehicle.category === parameter.category &&
          item.parameter.vehicle.type === parameter.type,
        (item, stars) => item.parameter.rate === stars,
        null,
        (list) => list.length,
        (vehicle) => `${vehicle.category} - ${vehicle.type}`
      );
      this.barChartDataList.push({
        title: this.$t('module.information.moveit.statistic.rating'),
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
    if (this.iterations) {
      const labels: string[] = this.iterations
        .map((item) => item.parameter.vehicle.category)
        .filter((value, index, array) => array.indexOf(value) === index)
        .sort();
      const datasets = calculateChartPerIteration(
        this.iterations,
        labels,
        this.replayColors,
        (item) => item.iteration - 1,
        (item, category) => item.parameter.vehicle.category === category
      );
      this.barChartDataList.push({
        title: this.$t('module.information.moveit.statistic.vehicleCategory'),
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
    if (this.iterations) {
      const vehicleList = this.getVehicleList();
      const labels: string[] = vehicleList.map(
        (item) => `${item.category} - ${item.type}`
      );
      const datasets = calculateChartPerIteration(
        this.iterations,
        vehicleList,
        this.replayColors,
        (item) => item.iteration - 1,
        (item, vehicle) =>
          item.parameter.vehicle.category === vehicle.category &&
          item.parameter.vehicle.type === vehicle.type
      );
      this.barChartDataList.push({
        title: this.$t('module.information.moveit.statistic.vehicleType'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
        stacked: true,
      });
    }
  }

  calculateStopsChart(): void {
    if (this.iterations) {
      const filter = (item) => item.parameter.drive;
      const labels: string[] = this.iterations
        .filter((item) => filter(item))
        .map((item) => item.parameter.drive.stops)
        .filter((value, index, array) => array.indexOf(value) === index)
        .sort((a, b) => a - b);
      const vehicleList = this.getVehicleList(filter);
      const datasets = calculateChartPerParameter(
        this.iterations,
        vehicleList,
        labels,
        this.colorList,
        (item, parameter) =>
          item.parameter.vehicle.category === parameter.category &&
          item.parameter.vehicle.type === parameter.type,
        (item, stops) => item.parameter.drive.stops === stops,
        filter,
        (list) => list.length,
        (vehicle) => `${vehicle.category} - ${vehicle.type}`
      );
      this.barChartDataList.push({
        title: this.$t('module.information.moveit.statistic.stops'),
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
    if (this.iterations) {
      for (const iteration of this.iterations) {
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
      const labels: string[] = this.iterations
        .filter((item) => filter(item))
        .map((item) => item.parameter.drive.persons)
        .filter((value, index, array) => array.indexOf(value) === index)
        .sort((a, b) => a - b);
      const datasets = calculateChartPerIteration(
        this.iterations,
        labels,
        this.replayColors,
        (item) => item.iteration - 1,
        (item, persons) => item.parameter.drive.persons === persons,
        filter
      );
      this.barChartDataList.push({
        title: this.$t('module.information.moveit.statistic.persons'),
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
    if (this.iterations) {
      for (const iteration of this.iterations) {
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
      const labels: string[] = this.iterations
        .filter((item) => filter(item))
        .map((item) => item.parameter.drive.calcSpeed)
        .filter((value, index, array) => array.indexOf(value) === index)
        .sort((a, b) => a - b);
      const vehicleList = this.getVehicleList(filter);
      const datasets = calculateChartPerParameter(
        this.iterations,
        vehicleList,
        labels,
        this.colorList,
        (item, parameter) =>
          item.parameter.vehicle.category === parameter.category &&
          item.parameter.vehicle.type === parameter.type,
        (item, calcSpeed) => item.parameter.drive.calcSpeed === calcSpeed,
        filter,
        (list) => list.length,
        (vehicle) => `${vehicle.category} - ${vehicle.type}`
      );
      this.barChartDataList.push({
        title: this.$t(`module.information.moveit.statistic.speed.${type}`),
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
    if (this.iterations) {
      const filter = (item) => item.parameter[timeType];
      const labels: number[] = this.iterations
        .filter((item) => filter(item))
        .map((item) => Math.round(item.parameter[timeType] / 1000))
        .filter((value, index, array) => array.indexOf(value) === index)
        .sort((a, b) => a - b);
      const vehicleList = this.getVehicleList(filter);
      const datasets = calculateChartPerParameter(
        this.iterations,
        vehicleList,
        labels,
        this.colorList,
        (item, parameter) =>
          item.parameter.vehicle.category === parameter.category &&
          item.parameter.vehicle.type === parameter.type,
        (item, time) => Math.round(item.parameter[timeType] / 1000) === time,
        filter,
        (list) => list.length,
        (vehicle) => `${vehicle.category} - ${vehicle.type}`
      );
      this.barChartDataList.push({
        title: this.$t(`module.information.moveit.statistic.${timeType}`),
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
    if (this.iterations) {
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
        this.iterations,
        vehicleList,
        labels,
        this.colorList,
        (item, parameter) =>
          item.parameter.vehicle.category === parameter.category &&
          item.parameter.vehicle.type === parameter.type,
        null,
        filter,
        (list, label) =>
          this.getCalculationForType(calculationType)(mapToValue(list, label)),
        (vehicle) => `${vehicle.category} - ${vehicle.type}`
      );
      this.barChartDataList.push({
        title: this.$t(
          `module.information.moveit.statistic.particleState.${value}.${calculationType}`
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
    if (this.iterations) {
      const mappingLength = 100;
      const mapToValue = (list, label) =>
        list
          .filter((item) => item.parameter.trackingData)
          .map((item) => {
            return mapArrayToConstantSize(
              item.parameter.trackingData,
              (item) => item[parameterName],
              label,
              mappingLength
            );
          });
      const filter = (item) =>
        item.parameter.trackingData && item.parameter.trackingData.length > 0;
      const vehicleList = this.getVehicleList(filter);
      const labels: number[] = [...Array(mappingLength).keys()];
      const datasets = calculateChartPerParameter(
        this.iterations,
        vehicleList,
        labels,
        this.colorList,
        (item, parameter) =>
          item.parameter.vehicle.category === parameter.category &&
          item.parameter.vehicle.type === parameter.type,
        null,
        filter,
        (list, label) =>
          this.getCalculationForType(calculationType)(mapToValue(list, label)),
        (vehicle) => `${vehicle.category} - ${vehicle.type}`
      );
      this.lineChartDataList.push({
        title: this.$t(
          `module.information.moveit.statistic.line.${parameterName}.${calculationType}`
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
    if (this.iterations) {
      const mappingLength = 100;
      const mapToValue = (list, label) =>
        list
          .filter((item) => item.parameter.trackingData)
          .map((item) => {
            return mapArrayToConstantSize(
              item.parameter.trackingData,
              (item) => item[parameterName],
              label,
              mappingLength
            );
          });
      const filter = (item) =>
        item.parameter.trackingData &&
        item.parameter.trackingData.length > 0 &&
        item.parameter.vehicle.category === 'bus';
      const compareList = [
        CalculationType.Average,
        CalculationType.Min,
        CalculationType.Max,
      ];
      const labels: number[] = [...Array(mappingLength).keys()];
      const datasets = calculateChartPerParameter(
        this.iterations,
        compareList,
        labels,
        this.colorList,
        () => true,
        null,
        filter,
        (list, label, parameter) =>
          this.getCalculationForType(parameter)(mapToValue(list, label))
      );
      this.lineChartDataList.push({
        title: this.$t(
          `module.information.moveit.statistic.line.${parameterName}.mix`
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
    cashService.deregisterAllGet(this.updateIterations);
  }

  unmounted(): void {
    this.deregisterAll();
  }
}
</script>
