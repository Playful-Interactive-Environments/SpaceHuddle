<template>
  <va-modal
    v-model="modal.show"
    :message="modal.message.length > 0 ? $t(modal.message) : ''"
    :title="modal.title.length > 0 ? $t(modal.title) : ''"
    @ok="eventBus.emit(modal.callback, modal.callbackData)"
  />
  <router-view class="pre-formatted" />
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

    this.eventBus.off(EventType.SHOW_CONFIRM);
    this.eventBus.on(EventType.SHOW_CONFIRM, async (data) => {
      const input = data as {
        message: string;
        title: string;
        callback: string;
        callbackData: any;
      };
      this.modal.message = input.message;
      this.modal.title = input.title;
      this.modal.callback = input.callback;
      this.modal.callbackData = input.callbackData;
      this.modal.show = true;
    });
  }
}
</script>
