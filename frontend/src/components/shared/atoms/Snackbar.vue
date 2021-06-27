<template>
  <transition name="snackbar">
    <div v-if="showSnackbar" class="snackbar-wrapper">
      <div class="snackbar snackbar__info">
        {{ message }}
      </div>
    </div>
  </transition>
</template>

<script lang="ts">
import { Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

export default class Snackbar extends Vue {
  @Prop({ default: 'something went wrong' }) snackbarMessage!: string;
  @Prop({ default: false }) showSnackbar?: boolean;
  @Prop({ default: 'snackbar__error' }) snackbarType?: string;
}
/*
// import snackbar component from import Snackbar from '@/components/shared/atoms/Snackbar.vue';
// and use these properties in parent view to display individual snackbar info
  <snackbar
    v-bind:showSnackbar="showSnackbar"
    v-bind:snackbarMessage="errorMessage"
    v-bind:snackbarType="errorMessage"
  ></snackbar>

showSnackbar = false;
snackbarMessage = 'This is an example message';
snackbarType = 'error';

triggerSnackbar = () => {
  this.showSnackbar = true;
  setTimeout(() => (this.showSnackbar = false), 3000);
};
 */
</script>

<style lang="scss" scoped>
.snackbar-wrapper {
  position: fixed;
  width: 100%;
  top: 20px;
}
.snackbar {
  padding: 20px;
  color: white;
  border-radius: 10px;
  box-shadow: 1px 3px 5px rgba(0, 0, 0, 0.2);
  max-width: 400px;
  margin: 0 auto;

  &__error {
    background: var(--color-red);
  }
  &__success {
    background: var(--color-mint);
  }
  &__info {
    background: var(--color-blue);
  }
}

/* enter transitions */
.snackbar-enter-from {
  opacity: 0;
  transform: translateY(-60px);
}
.snackbar-enter-to {
  opacity: 1;
  transform: translateY(0);
}
.snackbar-enter-active {
  transition: all 0.5s ease;
}
/* leave transitions */
.snackbar-leave-from {
  opacity: 1;
  transform: translateY(0);
}
.snackbar-leave-to {
  opacity: 0;
  transform: translateY(-60px);
}
.snackbar-leave-active {
  transition: all 0.3s ease;
}
</style>
