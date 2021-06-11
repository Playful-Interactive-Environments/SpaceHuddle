<template>
  <div class="timer">{{ active ? time : 'inactive ' }}</div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';

@Options({
  components: {},
})
export default class Timer extends Vue {
  private active = true;
  private time = '5:22';
}
</script>

<style lang="scss" scoped>
.timer {
  background-color: var(--module-color);
  color: white;
  font-weight: var(--font-weight-bold);
  padding: 0.5rem 1rem;
  border-radius: var(--border-radius-xs);
}
</style>
