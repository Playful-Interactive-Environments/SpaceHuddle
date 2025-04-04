<template>
  <div class="iframe-container" v-if="isValidSourceLink">
    <iframe
      id="iframe"
      title="ExternalSource"
      width="100%"
      height="100%"
      :src="getPdfBlobUrl(sourceLink)"
    >
    </iframe>
  </div>
  <p v-else-if="sourceLink">
    {{ $t('module.information.externalContent.moderatorConfig.invalid') }}
  </p>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import IdeaSettings from '@/components/moderator/organisms/settings/IdeaSettings.vue';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import draggable from 'vuedraggable';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import { IModeratorContent } from '@/types/ui/IModeratorContent';
import * as cashService from '@/services/cash-service';
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';

@Options({
  components: {
    AddItem,
    IdeaSettings,
    IdeaCard,
    draggable,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue implements IModeratorContent {
  @Prop() readonly taskId!: string;
  task!: Task;
  sourceLink = '';

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  @Watch('taskId', { immediate: true })
  reloadTaskSettings(): void {
    this.deregisterAll();
    taskService.registerGetTaskById(this.taskId, this.updateTask);
  }

  updateTask(task: Task): void {
    this.task = task;
    const module = this.task.modules.find(
      (module) => module.parameter.sourceLink
    );
    if (module) {
      this.sourceLink = module.parameter.sourceLink;
    }
  }

  get isValidSourceLink(): boolean {
    const base64Pattern = /^data:application\/pdf;base64,[A-Za-z0-9+/=]+$/;

    return (
      base64Pattern.test(this.sourceLink) ||
      new RegExp(
        '^(https?:\\/\\/)' +
          '((([a-z0-9\\-]+\\.)+[a-z]{2,})|' +
          'localhost|' +
          '\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}|' +
          '\\[([0-9a-f]{1,4}:){7}[0-9a-f]{1,4}\\])' +
          '(\\:\\d+)?(\\/[-a-z0-9%_.~+&:]*)*' +
          '(\\?[;&a-z0-9%_.~+=-]*)?' +
          '(\\#[-a-z0-9_]*)?$',
        'i'
      ).test(this.sourceLink)
    );
  }

  base64ToBlob(base64: string, contentType = "application/pdf"): Blob {
    const base64Data = base64.includes(",") ? base64.split(",")[1] : base64;

    const byteCharacters = atob(base64Data);
    const byteNumbers = new Array(byteCharacters.length);
    for (let i = 0; i < byteCharacters.length; i++) {
      byteNumbers[i] = byteCharacters.charCodeAt(i);
    }
    const byteArray = new Uint8Array(byteNumbers);
    return new Blob([byteArray], { type: contentType });
  }

  getPdfBlobUrl(base64: string | null): string | null {
    if (!base64) return null;

    try {
      const pdfBlob = this.base64ToBlob(base64);
      return URL.createObjectURL(pdfBlob);
    } catch (error) {
      console.error("Error creating Blob URL:", error);
      return null;
    }
  }
}
</script>

<style scoped>
.iframe-container {
  position: relative;
  width: 100%;
  height: 75%;
  display: flex;
  justify-content: center;
  align-items: center;
}

iframe {
  width: 100%;
  height: 100%;
  border: none;
}
</style>
