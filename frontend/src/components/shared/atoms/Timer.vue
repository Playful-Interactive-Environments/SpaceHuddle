<template>
  <div class="timer">{{ isActive ? formattedTime : 'inactive' }}</div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';

@Options({
  components: {},
})
export default class Timer extends Vue {
  @Prop({ default: false }) isActive!: boolean;
  timeLeft = 360;
  timerInterval!: number;
  readonly interval = 1000;

  @Watch('isActive')
  onIsActiveChanged(val: boolean) {
    if (val === true) {
      this.startTimer();
    } else {
      clearInterval(this.timerInterval);
    }
  }

  get formattedTime(): string {
    let minutes = Math.floor(this.timeLeft / 60);
    let seconds = this.timeLeft - minutes * 60;
    return `${minutes}:${seconds < 10 ? '0' + seconds : seconds}`;
  }

  mounted() {
    if (this.isActive) {
      this.startTimer();
    }
  }

  startTimer(): void {
    this.timerInterval = setInterval(() => {
      this.timeLeft -= 1;
      if (this.timeLeft <= 0) {
        clearInterval(this.timerInterval);
      }
    }, this.interval);
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
