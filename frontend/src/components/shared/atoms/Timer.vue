<template>
  <div class="timer">
    <span v-if="timeLeft !== null">{{
      showTime ? formattedTime : $t('shared.atom.timer.inactive')
    }}</span>
    <font-awesome-icon v-else icon="infinity" />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';

@Options({
  components: {},
  emits: ['timerEnds'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Timer extends Vue {
  @Prop() task!: Task;
  timeLeft: number | null = 0;
  interval!: any;
  readonly intervalTime = 1000;

  mounted(): void {
    document.addEventListener('visibilitychange', this.syncTimeWithBackend);
  }

  get showTime(): boolean {
    return taskService.isActive(this.task);
  }

  @Watch('task', { immediate: true, deep: true })
  onTaskChanged(val: Task): void {
    if (this.showTime) {
      if (val) this.timeLeft = val.remainingTime;
      if (val && val.remainingTime) {
        this.startTimer();
      } else if (this.interval) {
        clearInterval(this.interval);
        if (this.timeLeft !== null) this.$emit('timerEnds');
      }
    }
  }

  syncTimeWithBackend(): void {
    if (this.task) {
      taskService.getTaskById(this.task.id).then((task) => {
        this.task.remainingTime = task.remainingTime;
      });
    }
  }

  get formattedTime(): string {
    if (this.timeLeft !== null) {
      let minutes = Math.floor(this.timeLeft / 60);
      let seconds = this.timeLeft - minutes * 60;
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
        clearInterval(this.interval);
        this.$emit('timerEnds');
      }
      if (this.task) {
        this.task.remainingTime = this.timeLeft;
      }
    }
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
