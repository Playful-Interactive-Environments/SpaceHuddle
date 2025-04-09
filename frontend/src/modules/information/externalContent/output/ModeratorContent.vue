<template>
  <div class="iframe-container" v-if="isValidSourceLink">
    <iframe
      id="iframe"
      title="ExternalSource"
      width="100%"
      height="100%"
      :src="cachedBlobUrl"
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
  private currentBlobUrl: string | null = null;
  private cachedBlobUrl: string | null = null;

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

  @Watch('sourceLink')
  onSourceLinkChanged(newSourceLink: string) {
    if (this.isBase64PDF(newSourceLink)) {
      this.cachedBlobUrl = this.convertBase64ToBlobUrl(newSourceLink);
    } else {
      this.cachedBlobUrl = newSourceLink;
    }
  }

  isBase64PDF(sourceLink: string): boolean {
    const base64Pattern = /^data:application\/pdf;base64,[A-Za-z0-9+/=]+$/;
    return base64Pattern.test(sourceLink);
  }

  convertBase64ToBlobUrl(base64: string): string {
    const pdfBlob = this.base64ToBlob(base64);
    if (this.currentBlobUrl) {
      URL.revokeObjectURL(this.currentBlobUrl);
    }
    this.currentBlobUrl = URL.createObjectURL(pdfBlob);
    return this.currentBlobUrl;
  }

  base64ToBlob(base64: string, contentType = 'application/pdf'): Blob {
    const base64Data = base64.includes(',') ? base64.split(',')[1] : base64;
    const byteCharacters = atob(base64Data);
    const byteNumbers = new Array(byteCharacters.length);
    for (let i = 0; i < byteCharacters.length; i++) {
      byteNumbers[i] = byteCharacters.charCodeAt(i);
    }
    const byteArray = new Uint8Array(byteNumbers);
    return new Blob([byteArray], { type: contentType });
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
    const urlPattern = new RegExp(
      '^(https?:\\/\\/)' +
        '((([a-z0-9\\-]+\\.)+[a-z]{2,})|' +
        'localhost|' +
        '\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}|' +
        '\\[([0-9a-f]{1,4}:){7}[0-9a-f]{1,4}\\])' +
        '(\\:\\d+)?(\\/[-a-z0-9%_.~+&:]*)*' +
        '(\\?[;&a-z0-9%_.~+=-]*)?' +
        '(\\#[-a-z0-9_]*)?$',
      'i'
    );

    return (
      this.isBase64PDF(this.sourceLink) ||
      urlPattern.test(this.sourceLink) ||
      this.sourceLink.includes('<iframe')
    );
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
