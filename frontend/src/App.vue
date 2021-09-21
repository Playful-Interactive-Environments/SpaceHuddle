<template>
  <!--<router-view class="pre-formatted" />-->
  <router-view />
  <snackbar
    class="pre-formatted"
    v-bind:showSnackbar="showSnackbar"
    v-bind:snackbarMessage="snackbarMessage"
    v-bind:snackbarMessageContent="snackbarMessageContent"
    v-bind:snackbarType="snackbarType"
  ></snackbar>
</template>

<script lang="ts">
import { Vue, Options } from 'vue-class-component';
import Snackbar from '@/components/shared/atoms/Snackbar.vue';
import SnackbarType from '@/types/enum/SnackbarType';
import { EventType } from '@/types/enum/EventType';

@Options({
  components: {
    Snackbar,
  },
})
export default class Sidebar extends Vue {
  showSnackbar = false;
  snackbarMessage = '';
  snackbarMessageContent: [] | null = null;
  snackbarType = SnackbarType.INFO;
  modal = {
    show: false,
    title: '',
    message: '',
    callback: '',
    callbackData: {},
  };

  mounted(): void {
    this.eventBus.off(EventType.SHOW_SNACKBAR);
    this.eventBus.on(EventType.SHOW_SNACKBAR, async (data) => {
      this.showSnackbar = true;
      const input = data as {
        type: SnackbarType;
        message: string;
        messageContent: [] | null;
      };
      this.snackbarMessage = input.message;
      this.snackbarType = input.type;
      this.snackbarMessageContent = input.messageContent;
      setTimeout(() => (this.showSnackbar = false), 4000);
    });
  }
}
</script>
