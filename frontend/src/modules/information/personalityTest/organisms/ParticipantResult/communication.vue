<template>
  <div id="submitScreen" class="markdown">
    <div class="chart">
      <Bar
        id="chartRef"
        ref="chartRef"
        :data="chartData"
        :options="{
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              ticks: {
                precision: 0,
              },
            },
          },
          plugins: {
            title: {
              display: false,
            },
            legend: {
              display: false,
            },
          },
        }"
      />
    </div>
    <div>
      {{ $t(`module.information.personalityTest.${test}.participant.score`) }}
    </div>
    <div>
      <div
        v-for="item of Object.keys(resultValue.resultTypeValues)"
        :key="item"
      >
        <el-link
          @click="
            () => {
              dialogResultType = item;
              showResultType = true;
            }
          "
        >
          {{
            $t(
              `module.information.personalityTest.${test}.result.${item}.name`
            )
          }}:
        </el-link>
        {{ resultValue.resultTypeValues[item] }}
      </div>
    </div>
  </div>
  <el-dialog v-model="showResultType">
    <template #header>
      <h1>
        {{
          $t(
            `module.information.personalityTest.${test}.result.${dialogResultType}.name`
          )
        }}
      </h1>
    </template>
    <div>
      {{
        $t(
          `module.information.personalityTest.${test}.result.${dialogResultType}.description`
        )
      }}
    </div>
  </el-dialog>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Bar } from 'vue-chartjs';
import { ChartData } from 'chart.js';
import { v4 as uuidv4 } from 'uuid';
import { delay } from '@/utils/wait';
import * as themeColors from '@/utils/themeColors';
import { ThinkingHatsValue } from '@/modules/information/personalityTest/types/ThinkingHatsType';
import surveyConfig from '@/modules/information/personalityTest/data/survey.json';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

@Options({
  components: {
    FontAwesomeIcon,
    Bar,
  },
  emits: [],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ParticipantResult extends Vue {
  @Prop() readonly test!: string;
  @Prop() readonly resultValue!: ThinkingHatsValue;

  showResultType = false;
  dialogResultType = '';

  chartData: ChartData = {
    labels: [],
    datasets: [],
  };

  labelColors = surveyConfig.communication.result;

  @Watch('resultValue', { immediate: true })
  onResultChanged(): void {
    if (this.resultData) {
      this.chartData.labels = this.resultData.labels;
      this.chartData.datasets = this.resultData.datasets;
      this.updateChart();
    }
  }

  get resultData(): ChartData {
    const labels: string[] = [];
    const datasets: any[] = [];
    for (const resultType of Object.keys(this.resultValue.resultTypeValues)) {
      labels.push(
        this.$t(
          `module.information.personalityTest.${this.test}.result.${resultType}.name`
        )
      );
    }
    datasets.push({
      label: '',
      data: Object.values(this.resultValue.resultTypeValues),
      borderColor: themeColors.getInformingColor(),
      backgroundColor: themeColors.getInformingColor(),
    });
    return {
      labels: labels,
      datasets: datasets,
    };
  }

  get characteristics(): string[] {
    return this.$t(
      `module.information.personalityTest.${this.test}.result.${this.dialogResultType}.characteristics`
    )
      .split('#')
      .map((item) => item.trim())
      .filter((item) => item.length > 0);
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
#submitScreen {
  text-align: center;
}

#submitScreen > div {
  padding-bottom: 1rem;
  text-align: left;
}

.markdown .el-image::v-deep(img) {
  max-height: unset;
  padding: unset;
}

h1 {
  font-size: var(--font-size-large);
  font-weight: var(--font-weight-bold);
  padding-bottom: 1rem;
}

.chart {
  height: 40vh;
}

ul {
  margin-block-start: 1em;
  margin-block-end: 1em;
  margin-inline-start: 0;
  margin-inline-end: 0;
  padding-inline-start: 40px;
  list-style: disc;
}

.hat {
  font-size: 10rem;
  text-align: center;
}
</style>
