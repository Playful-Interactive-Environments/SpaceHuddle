<template>
  <Bar
    id="resultChart"
    ref="chartRef"
    :data="chartData"
    :options="{
      animation: {
        duration: 0,
      },
      scales: {
        x: {
          ticks: {
            color: contrastColor,
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
          labels: {
            color: contrastColor,
          },
        },
      },
    }"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Bar } from 'vue-chartjs';
import * as votingService from '@/services/voting-service';
import { VoteResult } from '@/types/api/Vote';
import { EventType } from '@/types/enum/EventType';
import { IModeratorContent } from '@/types/ui/IModeratorContent';
import * as cashService from '@/services/cash-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as themeColors from '@/utils/themeColors';

@Options({
  components: {
    Bar,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue implements IModeratorContent {
  @Prop() readonly taskId!: string;
  votes: VoteResult[] = [];
  chartData: any = {
    labels: [],
    datasets: [],
  };

  get contrastColor(): string {
    return themeColors.getContrastColor();
  }

  voteCashEntry!: cashService.SimplifiedCashEntry<VoteResult[]>;
  @Watch('taskId', { immediate: true })
  reloadTaskSettings(): void {
    this.deregisterAll();
    this.voteCashEntry = votingService.registerGetResult(
      this.taskId,
      this.updateVotes,
      EndpointAuthorisationType.MODERATOR,
      20
    );
  }

  updateVotes(votes: VoteResult[]): void {
    this.votes = votes;
    if (this.resultData) {
      this.chartData.labels = this.resultData.labels;
      this.chartData.datasets = this.resultData.datasets;
    }
    this.updateChart();
  }

  get resultData(): any {
    this.votes.map((vote) => vote.idea.keywords);
    return {
      labels: this.votes.map((vote) => vote.idea.keywords),
      datasets: [
        {
          label: (this as any).$t(
            'module.voting.default.publicScreen.chartDataLabel'
          ),
          backgroundColor: themeColors.getEvaluatingColor(),
          data: this.votes.map((vote) => vote.detailRatingSum),
        },
      ],
    };
  }

  async updateChart(): Promise<void> {
    if (this.$refs.chartRef) {
      const chartRef = this.$refs.chartRef as any;
      if (chartRef.chart) {
        chartRef.chart.data = this.chartData;
        chartRef.chart.update();
      }
    }
  }

  async mounted(): Promise<void> {
    this.eventBus.off(EventType.CHANGE_SETTINGS);
    this.eventBus.on(EventType.CHANGE_SETTINGS, async (taskId) => {
      if (this.taskId === taskId) {
        this.voteCashEntry.refreshData();
      }
    });
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateVotes);
  }

  unmounted(): void {
    this.deregisterAll();
  }
}
</script>

<style scoped></style>
