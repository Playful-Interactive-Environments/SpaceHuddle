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
  duration = 360;
  timer!: number;
  readonly countdownInterval = 1000;

  @Watch('isActive')
  onIsActiveChanged(val: boolean) {
    if (val === true) {
      this.startTimer();
    } else {
      clearInterval(this.timer);
    }
  }

  get formattedTime(): string {
    let minutes = Math.floor(this.duration / 60);
    let seconds = this.duration - minutes * 60;
    return `${minutes}:${seconds < 10 ? '0' + seconds : seconds}`;
  }

  mounted() {
    if (this.isActive) {
      this.startTimer();
    }
  }

  startTimer(): void {
    this.timer = setInterval(() => {
      this.duration -= 1;
      if (this.duration <= 0) {
        clearInterval(this.timer);
      }
    }, this.countdownInterval);
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
