<template>
  <router-view class="pre-formatted" />
  <snackbar
    class="pre-formatted"
    v-bind:showSnackbar="showSnackbar"
    v-bind:snackbarMessage="snackbarMessage"
    v-bind:snackbarType="snackbarType"
  ></snackbar>
</template>

<script lang="ts">
import { Vue, Options } from 'vue-class-component';
import Snackbar from '@/components/shared/atoms/Snackbar.vue';
import SnackbarType from '@/types/SnackbarType';
import { EventType } from '@/types/EventType';

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
    this.eventBus.off(EventType.SHOW_SNACKBAR);
    this.eventBus.on(EventType.SHOW_SNACKBAR, async (data) => {
      this.showSnackbar = true;
      this.snackbarMessage = (
        data as { type: SnackbarType; message: string }
      ).message;
      this.snackbarType = (
        data as { type: SnackbarType; message: string }
      ).type;
      setTimeout(() => (this.showSnackbar = false), 4000);
    });
  }
}
</script>
