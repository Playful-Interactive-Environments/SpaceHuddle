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
          duration: 2000,
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
      v-else-if="test"
      id="chartRef"
      ref="chartRef"
      :data="chartData"
      :options="{
        scales: {
          x: {
            min: -0.5,
            max: 4.5,
            title: {
              display: true,
              text: $t(
                `module.information.personalityTest.${test}.participant.secondary`
              ),
            },
            ticks: {
              color: contrastColor,
              precision: 0,
              callback: (value, index) => getResultTypeName(value),
            },
          },
          y: {
            min: -0.5,
            max: 4.5,
            title: {
              display: true,
              text: $t(
                `module.information.personalityTest.${test}.participant.primary`
              ),
            },
            ticks: {
              color: contrastColor,
              precision: 0,
              callback: (value, index) => getResultTypeName(value),
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
                  return `${getResultTypeName(
                    context[0].raw.y
                  )} / ${getResultTypeName(context[0].raw.x)}`;
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
import Color from 'colorjs.io';
import { getResultTypeList } from '@/modules/information/personalityTest/types/ResultType';
import { Big5Value } from '@/modules/information/personalityTest/types/Big5Type';

@Options({
  components: {
    Bar,
    Bubble,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class DetailResult extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: false }) readonly showAll!: boolean;
  @Prop({ default: false }) readonly showDetails!: boolean;

  test = 'big5';

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

  get ResultTypeList(): string[] {
    return getResultTypeList(this.test);
  }

  getResultTypeName(index: number): string {
    if (Number.isInteger(index)) {
      return this.$t(
        `module.information.personalityTest.${this.test}.result.${this.ResultTypeList[index]}.name`
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

  resultTypeCount: { [key: string]: number[] } = {};
  resultTypeCombinedCount: { [key: string]: { [key: string]: number } } = {};
  updateState(result: TaskParticipantState[]): void {
    for (const resultType of this.ResultTypeList) {
      this.resultTypeCount[resultType] = [0, 0, 0, 0, 0];
      this.resultTypeCombinedCount[resultType] = {};
      for (const resultType2 of this.ResultTypeList) {
        this.resultTypeCombinedCount[resultType][resultType2] = 0;
      }
    }
    for (const state of result) {
      const values = state.parameter as Big5Value;
      if (values.resultTypeValues) {
        const rankingList = Object.keys(values.resultTypeValues).sort(
          (a, b) => values.resultTypeValues[b] - values.resultTypeValues[a]
        );
        this.resultTypeCombinedCount[rankingList[0]][rankingList[1]] += 1;
        for (let i = 0; i < this.ResultTypeList.length; i++) {
          this.resultTypeCount[rankingList[i]][i] += 1;
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
      const resultTypeList = this.ResultTypeList;
      const data: { x: number; y: number; r: number }[] = [];
      for (let i = 0; i < resultTypeList.length; i++) {
        const resultType1 = resultTypeList[i];
        for (let j = 0; j < resultTypeList.length; j++) {
          const resultType2 = resultTypeList[j];
          const value = this.resultTypeCombinedCount[resultType1][resultType2];
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
      const max = 4;
      for (let i = min; i <= max; i++) {
        const color = color1.mix(color2, (1 / max) * i, {
          space: 'lch',
          outputSpace: 'srgb',
        }) as any;
        const hexColor = color.toString({ format: 'hex', collapse: false });
        rateColors.push(hexColor);
      }

      for (let i = 0; i < this.ResultTypeList.length; i++) {
        datasets.push({
          label: i + 1,
          data: Object.keys(this.resultTypeCount).map(
            (item) => this.resultTypeCount[item][i]
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
          label: this.$t(
            `module.information.personalityTest.${this.test}.participant.primary`
          ),
          data: Object.keys(this.resultTypeCount).map(
            (item) => this.resultTypeCount[item][0]
          ),
          borderRadius: 5,
          borderSkipped: false,
          backgroundColor: themeColors.getGreenColor(),
          color: themeColors.getContrastColor(),
        },
        {
          label: this.$t(
            `module.information.personalityTest.${this.test}.participant.secondary`
          ),
          data: Object.keys(this.resultTypeCount).map(
            (item) => this.resultTypeCount[item][1]
          ),
          borderRadius: 5,
          borderSkipped: false,
          backgroundColor: themeColors.getYellowColor(),
          color: themeColors.getContrastColor(),
        }
      );
    }
    return {
      labels: Object.keys(this.resultTypeCount).map((item) =>
        this.$t(
          `module.information.personalityTest.${this.test}.result.${item}.name`
        )
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
