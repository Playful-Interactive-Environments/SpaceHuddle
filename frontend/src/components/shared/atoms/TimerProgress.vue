<template>
  <div v-if="showProgress">
    <el-progress
      :text-inside="true"
      :stroke-width="26"
      :percentage="percentage"
      :format="formatProcess"
    />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as timerService from '@/services/timer-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { TimerEntity } from '@/types/enum/TimerEntity';
import * as cashService from '@/services/cash-service';

@Options({
  components: {},
  emits: ['timerEnds'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TimerProgress extends Vue {
  @Prop({ default: TimerEntity.TASK }) entityName!: string;
  @Prop() entity!: any;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  timeLeft: number | null = 0;
  totalTime: number | null = null;
  interval!: any;
  readonly intervalTime = 1000;

  mounted(): void {
    document.addEventListener('visibilitychange', this.reloadTimer);
    this.startTimer();
  }

  get showTime(): boolean {
    return timerService.isActive(this.entity);
  }

  get showProgress(): boolean {
    return this.showTime && !!this.totalTime && this.timeLeft !== null;
  }

  timerCash!: cashService.SimplifiedCashEntry<any>;
  @Watch('entity.id', { immediate: true })
  onEntityIdChanged(): void {
    let authHeaderTyp: EndpointAuthorisationType =
      EndpointAuthorisationType.MODERATOR;
    if (this.authHeaderTyp) authHeaderTyp = this.authHeaderTyp;
    this.timerCash = timerService.registerGet(
      this.entityName,
      this.entity.id,
      this.updateTimer,
      authHeaderTyp,
      20
    );
  }

  updateTimer(item: any): void {
    const remainingTime = timerService.getRemainingTime(item);
    const state = timerService.getState(item);
    const totalTime = timerService.getTotalTime(item);
    this.timeLeft = remainingTime;
    this.totalTime = totalTime;
    timerService.setTotalTime(this.entity, totalTime);
    timerService.setRemainingTime(this.entity, remainingTime);

    if (timerService.getState(item) !== timerService.getState(this.entity)) {
      timerService.setState(this.entity, state);
      if (!timerService.isActive(this.entity)) this.$emit('timerEnds');
    }
  }

  reloadTimer(): void {
    this.timerCash.refreshData();
  }

  @Watch('entity', { immediate: true, deep: true })
  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  onEntityChanged(val: any): void {
    if (this.showTime) {
      if (val) {
        this.timeLeft = timerService.getRemainingTime(val);
        this.totalTime = timerService.getTotalTime(val);
      }
    }
  }

  formatProcess(): string {
    return this.formattedTimeLeft;
  }

  get formattedTimeLeft(): string {
    if (this.timeLeft !== null) {
      return this.formatTime(this.timeLeft);
    }
    return '';
  }

  get formattedTotalTime(): string {
    if (this.totalTime !== null) {
      return this.formatTime(this.totalTime);
    }
    return '';
  }

  formatTime(time: number | null): string {
    if (time !== null) {
      const minutes = Math.floor(time / 60);
      const seconds = time - minutes * 60;
      return `${minutes}:${seconds < 10 ? '0' + seconds : seconds}`;
    }
    return '';
  }

  get percentage(): number {
    if (this.timeLeft !== null && this.totalTime !== null) {
      return ((this.totalTime - this.timeLeft) / this.totalTime) * 100;
    }
    return 100;
  }

  startTimer(): void {
    clearInterval(this.interval);
    this.interval = setInterval(() => this.refreshTimer(), this.intervalTime);
  }

  refreshTimer(): void {
    if (this.timeLeft !== null) {
      this.timeLeft -= 1;
      if (this.timeLeft <= 0) {
        this.timeLeft = 0;
        this.$emit('timerEnds');
      }
      if (this.entity) {
        timerService.setRemainingTime(this.entity, this.timeLeft);
      }
    }
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTimer);
  }

  unmounted(): void {
    this.deregisterAll();
    document.removeEventListener('visibilitychange', this.reloadTimer);
    clearInterval(this.interval);
  }
}
</script>

<style lang="scss" scoped>
.el-progress::v-deep(.el-progress-bar__outer) {
  border: 1px var(--color-primary) solid;
}
</style>
