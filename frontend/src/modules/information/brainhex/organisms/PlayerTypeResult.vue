<template>
  <div class="chartContainer" :style="{ height: `${chartHeight}rem` }">
    <Bar
      v-if="!showDetails"
      id="chartRef"
      ref="chartRef"
      :data="chartData"
      :options="{
        maintainAspectRatio: false,
        responsive: true,
        indexAxis: 'y',
        animation: {
          duration: update ? 0 : 2000,
        },
        scales: {
          y: {
            ticks: {
              color: contrastColor,
              precision: 0,
            },
            grid: {
              display: false,
            },
            stacked: true,
          },
          x: {
            ticks: {
              precision: 0,
            },
            stacked: true,
          },
        },
        plugins: {
          legend: {
            display: true,
            position: 'top',
            align: 'end',
            labels: {
              boxWidth: 30,
              boxHeight: 30,
              color: contrastColor,
            },
          },
        },
      }"
    />
    <Bubble
      v-else
      id="chartRef"
      ref="chartRef"
      :data="chartData"
      :options="{
        scales: {
          x: {
            min: -0.5,
            max: 6.5,
            title: {
              display: true,
              text: $t('module.information.brainhex.participant.secondary'),
            },
            ticks: {
              color: contrastColor,
              precision: 0,
              callback: (value, index) => getPlayTypeName(value),
            },
          },
          y: {
            min: -0.5,
            max: 6.5,
            title: {
              display: true,
              text: $t('module.information.brainhex.participant.primary'),
            },
            ticks: {
              color: contrastColor,
              precision: 0,
              callback: (value, index) => getPlayTypeName(value),
            },
          },
        },
        plugins: {
          legend: {
            display: false,
          },
          tooltip: {
            callbacks: {
              label: (context) => {
                context.formattedValue = (
                  context.raw.r / zoomFactor
                ).toString();
                return context.formattedValue;
              },
              title: (context) => {
                if (context[0].raw) {
                  return `${getPlayTypeName(
                    context[0].raw.y
                  )} / ${getPlayTypeName(context[0].raw.x)}`;
                }
                return '';
              },
            },
          },
        },
      }"
    />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Bar, Bubble } from 'vue-chartjs';
import { v4 as uuidv4 } from 'uuid';
import { delay } from '@/utils/wait';
import * as themeColors from '@/utils/themeColors';
import * as cashService from '@/services/cash-service';
import * as taskParticipantService from '@/services/task-participant-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { TaskParticipantState } from '@/types/api/TaskParticipantState';
import { PlayerType } from '@/modules/information/brainhex/types/PlayerType';
import Color from 'colorjs.io';

@Options({
  components: {
    Bar,
    Bubble,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PlayerTypeResult extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: false }) readonly update!: boolean;
  @Prop({ default: false }) readonly showException!: boolean;
  @Prop({ default: false }) readonly showAll!: boolean;
  @Prop({ default: false }) readonly showDetails!: boolean;

  readonly zoomFactor = 5;
  chartData: any = {
    labels: [],
    datasets: [],
  };

  get contrastColor(): string {
    return themeColors.getContrastColor();
  }

  get chartHeight(): number {
    const headHeight = 5;
    const itemHeight = 3;
    const calcHeight = this.chartData.labels.length * itemHeight + headHeight;
    const minHeight = headHeight + itemHeight * 2;
    if (calcHeight < minHeight) return minHeight;
    return calcHeight;
  }

  getPlayTypeName(index: number): string {
    if (Number.isInteger(index)) {
      const playerTypeList = Object.values(PlayerType);
      return this.$t(
        `module.information.brainhex.enum.playerType.${playerTypeList[index]}`
      );
    }
    return '';
  }

  unmounted(): void {
    cashService.deregisterAllGet(this.updateState);
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    cashService.deregisterAllGet(this.updateState);
    taskParticipantService.registerGetList(
      this.taskId,
      this.updateState,
      EndpointAuthorisationType.MODERATOR,
      120
    );
  }

  playerTypeCount: { [key: string]: number[] } = {};
  playerTypeCombinedCount: { [key: string]: { [key: string]: number } } = {};
  playerTypeCountHate: { [key: string]: number } = {};
  updateState(result: TaskParticipantState[]): void {
    for (const playerType of Object.values(PlayerType)) {
      this.playerTypeCount[playerType] = [0, 0, 0, 0, 0, 0, 0];
      this.playerTypeCountHate[playerType] = 0;
      this.playerTypeCombinedCount[playerType] = {};
      for (const playerType2 of Object.values(PlayerType)) {
        this.playerTypeCombinedCount[playerType][playerType2] = 0;
      }
    }
    for (const state of result) {
      if (state.parameter.playerTypes) {
        this.playerTypeCombinedCount[state.parameter.playerTypes[0]][
          state.parameter.playerTypes[1]
        ] += 1;
        for (let i = 0; i < Object.keys(PlayerType).length; i++) {
          this.playerTypeCount[state.parameter.playerTypes[i]][i] += 1;
        }
        for (const item of Object.keys(state.parameter.playerTypeValues)) {
          if (state.parameter.playerTypeValues[item] < 0) {
            this.playerTypeCountHate[item] += 1;
          }
        }
      }
    }
    if (this.resultData) {
      this.chartData.labels = this.resultData.labels;
      this.chartData.datasets = this.resultData.datasets;
      this.updateChart();
    }
  }

  get resultData(): any {
    const datasets: any[] = [];
    if (this.showDetails) {
      /*const randomColors = getRandomColorList(7);
      let index = 0;
      for (const playerType of Object.values(PlayerType)) {
        datasets.push({
          label: this.$t(
            `module.information.brainhex.enum.playerType.${playerType}`
          ),
          data: Object.keys(this.playerTypeCombinedCount).map(
            (item) => this.playerTypeCombinedCount[item][playerType]
          ),
          borderRadius: 5,
          borderSkipped: false,
          backgroundColor: randomColors[index],
          color: themeColors.getContrastColor(),
        });
        index++;
      }*/
      const playerTypeList = Object.values(PlayerType);
      const data: { x: number; y: number; r: number }[] = [];
      for (let i = 0; i < playerTypeList.length; i++) {
        const playerType1 = playerTypeList[i];
        for (let j = 0; j < playerTypeList.length; j++) {
          const playerType2 = playerTypeList[j];
          const value = this.playerTypeCombinedCount[playerType1][playerType2];
          if (value) {
            data.push({
              x: j,
              y: i,
              r: value * this.zoomFactor,
            });
          }
        }
      }
      datasets.push({
        data: data,
        borderRadius: 5,
        borderColor: themeColors.getYellowColor(),
        backgroundColor: themeColors.convertToRGBA(
          themeColors.getYellowColor(),
          0.5
        ),
        color: themeColors.getContrastColor(),
      });
    } else if (this.showAll) {
      const rateColors: string[] = [];
      const color1 = new Color(themeColors.getGreenColor());
      const color2 = new Color(themeColors.getRedColor());
      const min = 0;
      const max = 6;
      for (let i = min; i <= max; i++) {
        const color = color1.mix(color2, (1 / max) * i, {
          space: 'lch',
          outputSpace: 'srgb',
        }) as any;
        const hexColor = color.toString({ format: 'hex', collapse: false });
        rateColors.push(hexColor);
      }

      for (let i = 0; i < Object.keys(PlayerType).length; i++) {
        datasets.push({
          label: i + 1,
          data: Object.keys(this.playerTypeCount).map(
            (item) => this.playerTypeCount[item][i]
          ),
          borderRadius: 5,
          borderSkipped: false,
          backgroundColor: rateColors[i],
          color: themeColors.getContrastColor(),
        });
      }
    } else {
      datasets.push(
        {
          label: this.$t('module.information.brainhex.participant.primary'),
          data: Object.keys(this.playerTypeCount).map(
            (item) => this.playerTypeCount[item][0]
          ),
          borderRadius: 5,
          borderSkipped: false,
          backgroundColor: themeColors.getGreenColor(),
          color: themeColors.getContrastColor(),
        },
        {
          label: this.$t('module.information.brainhex.participant.secondary'),
          data: Object.keys(this.playerTypeCount).map(
            (item) => this.playerTypeCount[item][1]
          ),
          borderRadius: 5,
          borderSkipped: false,
          backgroundColor: themeColors.getYellowColor(),
          color: themeColors.getContrastColor(),
        }
      );
      if (this.showException) {
        datasets.push({
          label: this.$t('module.information.brainhex.participant.exception'),
          data: Object.keys(this.playerTypeCount).map(
            (item) => this.playerTypeCountHate[item]
          ),
          borderRadius: 5,
          borderSkipped: false,
          backgroundColor: themeColors.getRedColor(),
          color: themeColors.getContrastColor(),
        });
      }
    }
    return {
      labels: Object.keys(this.playerTypeCount).map((item) =>
        this.$t(`module.information.brainhex.enum.playerType.${item}`)
      ),
      datasets: datasets,
    };
  }

  lastUpdateCall = '';
  async updateChart(): Promise<void> {
    const uuid = uuidv4();
    this.lastUpdateCall = uuid;
    await delay(100);
    if (uuid === this.lastUpdateCall) {
      if (this.$refs.chartRef) {
        const chartRef = this.$refs.chartRef as any;
        if (chartRef.chart) {
          chartRef.chart.data = this.chartData;
          chartRef.chart.update();
        }
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.chartContainer {
  width: 100%;
}
</style>
