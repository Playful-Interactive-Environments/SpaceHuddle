<template>
  <transition name="snackbar">
    <div
      class="snackbar"
      :class="'snackbar__' + snackbarType"
      v-if="showSnackbar"
    >
      <ul>
        <li v-for="error in errors" :key="error">
          {{ $i18n.translateWithFallback(error, snackbarMessageContent) }}
        </li>
      </ul>
    </div>
  </transition>
</template>

<script lang="ts">
import { Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import SnackbarType from '@/types/enum/SnackbarType';

// use via eventbus
export default class Snackbar extends Vue {
  @Prop({ default: 'Oops! Something went wrong' }) snackbarMessage!:
    | string
    | string[];
  @Prop({ default: null }) snackbarMessageContent!: [] | null;
  @Prop({ default: false }) showSnackbar!: boolean;
  @Prop({ default: SnackbarType.SUCCESS }) snackbarType?: SnackbarType;

  get errors(): string[] {
    if (Array.isArray(this.snackbarMessage)) return this.snackbarMessage;
    return [this.snackbarMessage];
  }
}
</script>

<style lang="scss" scoped>
.snackbar {
  position: fixed;
  left: 50%;
  transform: translateX(-50%);
  top: 2rem;
  padding: 0.6rem 2rem;
  color: white;
  border-radius: var(--border-radius-xs);
  box-shadow: 1px 3px 20px rgba(0, 0, 0, 0.2);
  max-width: 400px;
  margin: 0 auto;

  &__error {
    background: var(--color-red);
  }
  &__success {
    background: var(--color-mint);
  }
  &__info {
    background: var(--color-yellow);
  }
}

/* enter transitions */
.snackbar-enter-from {
  opacity: 0;
  transform: translateX(-50%) translateY(-60px);
}
.snackbar-enter-to {
  opacity: 1;
  transform: translateX(-50%) translateY(0);
}
.snackbar-enter-active {
  transition: all 0.5s ease;
}
/* leave transitions */
.snackbar-leave-from {
  opacity: 1;
  transform: translateX(-50%) translateY(0);
}
.snackbar-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-60px);
}
.snackbar-leave-active {
  transition: all 0.3s ease;
}
</style>
