<template>
  <vue3-chart-js
    id="resultChart"
    ref="chartRef"
    type="bar"
    :data="chartData"
    :options="{
      animation: {
        duration: 0,
      },
      scales: {
        x: {
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
          labels: {
            color: '#1d2948',
          },
        },
      },
    }"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import Vue3ChartJs from '@j-t-mcc/vue3-chartjs';
import * as votingService from '@/services/voting-service';
import { VoteResult } from '@/types/api/Vote';
import { EventType } from '@/types/enum/EventType';

@Options({
  components: {
    Vue3ChartJs,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue {
  @Prop() readonly taskId!: string;
  votes: VoteResult[] = [];
  chartData: any = {
    labels: [],
    datasets: [],
  };
  readonly intervalTime = 10000;
  interval!: any;

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getVotes();
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
          backgroundColor: '#fe6e5d',
          data: this.votes.map((vote) => vote.detailRatingSum),
        },
      ],
    };
  }

  async getVotes(): Promise<void> {
    if (this.taskId) {
      await votingService.getResult(this.taskId).then((votes) => {
        this.votes = votes;
        if (this.resultData) {
          this.chartData.labels = this.resultData.labels;
          this.chartData.datasets = this.resultData.datasets;
        }
        this.updateChart();
      });
    }
  }

  async updateChart(): Promise<void> {
    if (this.$refs.chartRef) {
      const chartRef = this.$refs.chartRef as any;
      chartRef.update();
    }
  }

  async mounted(): Promise<void> {
    this.startInterval();

    this.eventBus.off(EventType.CHANGE_SETTINGS);
    this.eventBus.on(EventType.CHANGE_SETTINGS, async () => {
      await this.getVotes();
    });
  }

  startInterval(): void {
    this.interval = setInterval(this.getVotes, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }
}
</script>

<style scoped></style>
