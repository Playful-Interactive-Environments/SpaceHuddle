<template>
  <ParticipantModuleDefaultContainer :task-id="taskId" :module="module?.name">
    <div class="iframe-container" v-if="isValidSourceLink && participantView">
      <iframe
        id="iframe"
        title="ExternalSource"
        width="100%"
        height="100%"
        :src="getIframeSrc(sourceLink)"
      >
      </iframe>
    </div>
    <p v-else-if="sourceLink && participantView">
      {{ $t('module.information.externalContent.moderatorConfig.invalid') }}
    </p>
    <p v-else>
      {{
        $t(
          'module.information.externalContent.participant.participantViewFalse'
        )
      }}
    </p>
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/layout/ParticipantModuleDefaultContainer.vue';
import * as moduleService from '@/services/module-service';
import { Module } from '@/types/api/Module';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as cashService from '@/services/cash-service';
import { Task } from '@/types/api/Task';

@Options({
  components: {
    ParticipantModuleDefaultContainer,
    IdeaCard,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  @Prop({ default: false }) readonly useFullSize!: boolean;
  @Prop({ default: '' }) readonly backgroundClass!: string;
  module: Module | null = null;
  task!: Task;
  sourceLink = '';
  participantView = false;
  private currentBlobUrl: string | null = null;

  @Watch('moduleId', { immediate: true })
  onModuleIdChanged(): void {
    if (this.moduleId) {
      moduleService.registerGetModuleById(
        this.moduleId,
        this.updateModule,
        EndpointAuthorisationType.PARTICIPANT,
        60 * 60
      );
    }
  }

  updateModule(module: Module): void {
    this.module = module;
    if (module) {
      this.sourceLink = module.parameter.sourceLink;
      this.participantView = module.parameter.participantView;
    }
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateModule);
  }

  unmounted(): void {
    this.deregisterAll();
    // Revoke the Blob URL when the component is unmounted
    if (this.currentBlobUrl) {
      URL.revokeObjectURL(this.currentBlobUrl);
    }
  }

  get isValidSourceLink(): boolean {
    const base64Pattern = /^data:application\/pdf;base64,[A-Za-z0-9+/=]+$/;
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
      base64Pattern.test(this.sourceLink) ||
      urlPattern.test(this.sourceLink) ||
      this.sourceLink.includes('<iframe')
    );
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

  getIframeSrc(sourceLink: string | null): string | null {
    if (!sourceLink) return null;

    const base64Pattern = /^data:application\/pdf;base64,[A-Za-z0-9+/=]+$/;
    if (base64Pattern.test(sourceLink)) {
      try {
        const pdfBlob = this.base64ToBlob(sourceLink);
        // Revoke the old Blob URL if it exists
        if (this.currentBlobUrl) {
          URL.revokeObjectURL(this.currentBlobUrl);
        }
        this.currentBlobUrl = URL.createObjectURL(pdfBlob);
        return this.currentBlobUrl;
      } catch (error) {
        console.error('Error creating Blob URL:', error);
        return null;
      }
    }

    return sourceLink;
  }
}
</script>

<style scoped>
.iframe-container {
  position: relative;
  width: 100%;
  height: 100%;
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
