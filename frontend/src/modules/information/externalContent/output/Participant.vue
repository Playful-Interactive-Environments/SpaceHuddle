<template>
  <ParticipantModuleDefaultContainer :task-id="taskId" :module="moduleName">
    <div class="iframe-container" v-if="isValidSourceLink && participantView">
      <iframe
        id="iframe"
        title="ExternalSource"
        width="100%"
        height="100%"
        :src="sourceLink"
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
import { Idea } from '@/types/api/Idea';
import * as ideaService from '@/services/idea-service';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as cashService from '@/services/cash-service';
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';

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
  ideas: Idea[] = [];

  task!: Task;

  sourceLink = '';
  participantView = false;

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    ideaService.registerGetIdeasForTask(
      this.taskId,
      IdeaSortOrder.ORDER,
      null,
      this.updateIdeas,
      EndpointAuthorisationType.PARTICIPANT,
      10
    );
  }

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

  updateIdeas(ideas: Idea[]): void {
    this.ideas = ideas;
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateIdeas);
    cashService.deregisterAllGet(this.updateModule);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  get isValidSourceLink(): boolean {
    const base64Pattern =
      /^(data:application\/pdf;base64,[A-Za-z0-9+/]+={0,2})$/;

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
