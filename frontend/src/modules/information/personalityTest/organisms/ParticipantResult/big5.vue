<template>
  <div id="submitScreen" class="markdown">
    <div class="chart">
      <Radar
        id="chartRef"
        ref="chartRef"
        :data="chartData"
        :options="{
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            r: {
              angleLines: {
                display: true,
              },
              suggestedMin: 0,
              suggestedMax: 50,
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
      <ul>
        <li v-for="characteristic of characteristics" :key="characteristic">
          {{ characteristic }}
        </li>
      </ul>
    </div>
  </el-dialog>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Big5Value } from '@/modules/information/personalityTest/types/Big5Type';
import { Radar } from 'vue-chartjs';
import { ChartData } from 'chart.js';
import { v4 as uuidv4 } from 'uuid';
import { delay } from '@/utils/wait';
import * as themeColors from '@/utils/themeColors';

@Options({
  components: {
    Radar,
  },
  emits: [],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ParticipantResult extends Vue {
  @Prop() readonly test!: string;
  @Prop() readonly resultValue!: Big5Value;

  showResultType = false;
  dialogResultType = '';

  chartData: ChartData = {
    labels: [],
    datasets: [],
  };

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
      backgroundColor: themeColors.convertToRGBA(
        themeColors.getInformingColor(),
        0.5
      ),
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
  margin-inline-start: 0px;
  margin-inline-end: 0px;
  padding-inline-start: 40px;
  list-style: disc;
}
</style>
