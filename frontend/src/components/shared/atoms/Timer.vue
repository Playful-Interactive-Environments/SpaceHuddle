<template>
  <div class="timer" v-if="showTime">
    <span v-if="timeLeft !== null">{{
      showTime ? formattedTime : $t('shared.atom.timer.inactive')
    }}</span>
    <font-awesome-icon v-else icon="infinity" />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as timerService from '@/services/timer-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { TimerEntity } from '@/types/enum/TimerEntity';

@Options({
  components: {},
  emits: ['timerEnds'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Timer extends Vue {
  @Prop({ default: TimerEntity.TASK }) entityName!: string;
  @Prop() entity!: any;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  timeLeft: number | null = 0;
  interval!: any;
  readonly intervalTime = 1000;

  mounted(): void {
    document.addEventListener('visibilitychange', this.syncTimeWithBackend);
    this.startTimer();
  }

  get showTime(): boolean {
    return timerService.isActive(this.entity);
  }

  @Watch('entity', { immediate: true, deep: true })
  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  onEntityChanged(val: any): void {
    if (this.showTime) {
      if (val) this.timeLeft = timerService.getRemainingTime(val);
    }
  }

  syncTimeWithBackend(): void {
    if (this.entity) {
      let authHeaderTyp: EndpointAuthorisationType =
        EndpointAuthorisationType.MODERATOR;
      if (this.authHeaderTyp) authHeaderTyp = this.authHeaderTyp;
      timerService
        .get(this.entityName, this.entity.id, authHeaderTyp)
        .then((item) => {
          const remainingTime = timerService.getRemainingTime(item);
          const state = timerService.getState(item);
          this.timeLeft = remainingTime;
          timerService.setRemainingTime(this.entity, remainingTime);

          if (
            timerService.getState(item) !== timerService.getState(this.entity)
          ) {
            timerService.setState(this.entity, state);
            if (!timerService.isActive(this.entity)) this.$emit('timerEnds');
          }
        });
    }
  }

  get formattedTime(): string {
    if (this.timeLeft !== null) {
      const minutes = Math.floor(this.timeLeft / 60);
      const seconds = this.timeLeft - minutes * 60;
      return `${minutes}:${seconds < 10 ? '0' + seconds : seconds}`;
    }
    return '';
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
    this.syncTimeWithBackend();
  }

  unmounted(): void {
    document.removeEventListener('visibilitychange', this.syncTimeWithBackend);
    clearInterval(this.interval);
  }
}
</script>

<style lang="scss" scoped>
.timer {
  background-color: var(--module-color);
  color: white;
  font-weight: var(--font-weight-bold);
  min-width: 60px;
  padding: 0.5rem 0.8rem;
  border-radius: var(--border-radius-xs);
  text-align: center;
}
</style>
