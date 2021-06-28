<template>
  <router-view />
  <snackbar
    v-bind:showSnackbar="showSnackbar"
    v-bind:snackbarMessage="snackbarMessage"
    v-bind:snackbarType="snackbarType"
  ></snackbar>
</template>

<script lang="ts">
import { Vue, Options } from 'vue-class-component';
import Snackbar from '@/components/shared/atoms/Snackbar.vue';
import SnackbarType from '@/types/SnackbarType';

@Options({
  components: {
    Snackbar,
  },
})
export default class Sidebar extends Vue {
  showSnackbar = false;
  snackbarMessage = '';
  snackbarType = SnackbarType.INFO;

  mounted(): void {
    this.eventBus.off('showSnackbar');
    this.eventBus.on('showSnackbar', async (data) => {
      this.showSnackbar = true;
      this.snackbarMessage = (
        data as { type: SnackbarType; message: string }
      ).message;
      this.snackbarType = (
        data as { type: SnackbarType; message: string }
      ).type;
      setTimeout(() => (this.showSnackbar = false), 2500);
    });
  }
}
</script>
