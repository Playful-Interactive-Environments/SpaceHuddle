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
import type { ChartData } from 'chart.js';
import * as themeColors from '@/utils/themeColors';
import { getRandomColorList } from '@/utils/colors';
import { TaskParticipantIteration } from '@/types/api/TaskParticipantIteration';
import { calculateChartPerIteration } from '@/utils/statistic';

@Options({
  components: { Bar },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModuleStatistic extends Vue {
  @Prop() readonly taskId!: string;
  iterations: TaskParticipantIteration[] = [];

  barChartDataList: {
    title: string;
    data: ChartData;
    labelColors: string[] | string;
  }[] = [];
  displayLabels = false;
  replayColors: string[] = [];

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

  calculateCharts(): void {
    this.barChartDataList = [];
    this.calculateStarsChart();
    this.calculateVehicleCategoryChart();
    this.calculateVehicleTypeChart();
    this.calculateStopsChart();
    this.calculatePersonsChart();
    this.calculateMaxSpeedChart();
    this.calculateAverageSpeedChart();
    this.calculateTimeChart('playTime');
    this.calculateTimeChart('selectTime');
    this.calculateTimeChart('driveTime');
    this.calculateTimeChart('cleanupTime');
  }

  calculateStarsChart(): void {
    if (this.iterations) {
      const labels: string[] = [
        '-',
        '\uf005',
        '\uf005\uf005',
        '\uf005\uf005\uf005',
      ];
      const datasets = calculateChartPerIteration(
        this.iterations,
        [...Array(3).keys()],
        this.replayColors,
        (item) => item.iteration - 1,
        (item, stars) => item.parameter.rate === stars
      );
      this.barChartDataList.push({
        title: this.$t('module.information.moveit.statistic.rating'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
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
      });
    }
  }

  calculateVehicleTypeChart(): void {
    if (this.iterations) {
      const vehicleList = this.iterations
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
      const datasets = calculateChartPerIteration(
        this.iterations,
        labels,
        this.replayColors,
        (item) => item.iteration - 1,
        (item, stops) => item.parameter.drive.stops === stops,
        filter
      );
      this.barChartDataList.push({
        title: this.$t('module.information.moveit.statistic.stops'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
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
      });
    }
  }

  calculateMaxSpeedChart(): void {
    if (this.iterations) {
      for (const iteration of this.iterations) {
        if (
          iteration.parameter.drive &&
          iteration.parameter.drive.trackingData
        ) {
          const speedList = iteration.parameter.drive.trackingData
            .map((item) => item.speed)
            .sort((a, b) => b - a);
          if (speedList.length > 0) {
            iteration.parameter.drive.maxSpeed =
              Math.round(speedList[0] / 10) * 10;
          }
        }
      }
      const filter = (item) =>
        item.parameter.drive && item.parameter.drive.maxSpeed;
      const labels: string[] = this.iterations
        .filter((item) => filter(item))
        .map((item) => item.parameter.drive.maxSpeed)
        .filter((value, index, array) => array.indexOf(value) === index)
        .sort((a, b) => a - b);
      const datasets = calculateChartPerIteration(
        this.iterations,
        labels,
        this.replayColors,
        (item) => item.iteration - 1,
        (item, maxSpeed) => item.parameter.drive.maxSpeed === maxSpeed,
        filter
      );
      this.barChartDataList.push({
        title: this.$t('module.information.moveit.statistic.maxSpeed'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
      });
    }
  }

  calculateAverageSpeedChart(): void {
    if (this.iterations) {
      for (const iteration of this.iterations) {
        if (
          iteration.parameter.drive &&
          iteration.parameter.drive.trackingData
        ) {
          const speedList = iteration.parameter.drive.trackingData.map(
            (item) => item.speed
          );
          if (speedList.length > 0) {
            const speed =
              speedList.reduce((prev, curr) => prev + curr, 0) /
              speedList.length;
            iteration.parameter.drive.averageSpeed =
              Math.round(speed / 10) * 10;
          }
        }
      }
      const filter = (item) =>
        item.parameter.drive && item.parameter.drive.averageSpeed;
      const labels: string[] = this.iterations
        .filter((item) => filter(item))
        .map((item) => item.parameter.drive.averageSpeed)
        .filter((value, index, array) => array.indexOf(value) === index)
        .sort((a, b) => a - b);
      const datasets = calculateChartPerIteration(
        this.iterations,
        labels,
        this.replayColors,
        (item) => item.iteration - 1,
        (item, maxSpeed) => item.parameter.drive.averageSpeed === maxSpeed,
        filter
      );
      this.barChartDataList.push({
        title: this.$t('module.information.moveit.statistic.averageSpeed'),
        data: {
          labels: labels,
          datasets: datasets,
        },
        labelColors: themeColors.getContrastColor(),
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
      const datasets = calculateChartPerIteration(
        this.iterations,
        labels,
        this.replayColors,
        (item) => item.iteration - 1,
        (item, time) => Math.round(item.parameter[timeType] / 1000) === time,
        filter
      );
      this.barChartDataList.push({
        title: this.$t(`module.information.moveit.statistic.${timeType}`),
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
