<template>
  <ParticipantModuleDefaultContainer :task-id="taskId" :module="moduleName">
    <template #footer>
      <el-scrollbar height="15rem">
        <IdeaCard
          v-for="idea of ideas"
          :key="idea.id"
          class="ideaCard"
          :idea="idea"
          :is-selectable="false"
          :is-editable="true"
          :show-state="false"
          :canChangeState="false"
          :handleEditable="false"
          :portrait="false"
          :authHeaderTyp="EndpointAuthorisationType.PARTICIPANT"
          @ideaDeleted="refreshIdeas"
          @ideaStartEdit="editIdea(idea)"
        />
      </el-scrollbar>
      <div v-on:click="editNewImage" class="button">
        <font-awesome-icon icon="circle-plus" />
      </div>
    </template>
    <IdeaMap
      v-if="module"
      :ideas="ideas"
      :canChangePosition="() => true"
      v-model:selected-idea="selectedIdea"
      :parameter="module?.parameter"
      v-on:ideaPositionChanged="saveIdea"
    >
    </IdeaMap>

    <IdeaSettings
      v-model:show-modal="showIdeaSettings"
      :taskId="taskId"
      :idea="settingsIdea"
      :title="$t('module.brainstorming.map.moderatorContent.settingsTitle')"
      :authHeaderTyp="EndpointAuthorisationType.PARTICIPANT"
      @updateData="addData"
    />
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/layout/ParticipantModuleDefaultContainer.vue';
import * as ideaService from '@/services/idea-service';
import * as taskService from '@/services/task-service';
import * as moduleService from '@/services/module-service';
import { Idea } from '@/types/api/Idea';
import { Module } from '@/types/api/Module';
import { Task } from '@/types/api/Task';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import IdeaSettings from '@/components/moderator/organisms/settings/IdeaSettings.vue';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import { defaultFormRules, ValidationRuleDefinition } from '@/utils/formRules';
import * as cashService from '@/services/cash-service';
import IdeaMap from '@/components/shared/organisms/IdeaMap.vue';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import TaskParticipantIterationStatesType from '@/types/enum/TaskParticipantIterationStatesType';
import { TrackingManager } from '@/types/tracking/TrackingManager';

@Options({
  components: {
    IdeaMap,
    IdeaCard,
    IdeaSettings,
    ParticipantModuleDefaultContainer,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  @Prop({ default: false }) readonly useFullSize!: boolean;
  @Prop({ default: '' }) readonly backgroundClass!: string;
  module: Module | null = null;
  task: Task | null = null;
  ideas: Idea[] = [];
  showIdeaSettings = false;
  EndpointAuthorisationType = EndpointAuthorisationType;
  selectedIdea: Idea | null = null;
  trackingManager!: TrackingManager;

  addIdea: any = {
    keywords: '',
    description: '',
    link: null,
    image: null, // the datebase64 url of created image
  };
  settingsIdea = this.addIdea;

  ideaCash!: cashService.SimplifiedCashEntry<Idea[]>;
  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      this.trackingManager = new TrackingManager(this.taskId, {}, 100);
    }
    this.deregisterAll();
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.PARTICIPANT,
      60 * 60
    );
    this.ideaCash = ideaService.registerGetIdeasForTask(
      this.taskId,
      IdeaSortOrder.TIMESTAMP,
      null,
      this.updateIdeas,
      EndpointAuthorisationType.PARTICIPANT,
      30
    );
  }

  editNewImage(): void {
    this.settingsIdea = this.addIdea;
    this.addIdea.keywords = '';
    this.addIdea.description = '';
    this.addIdea.image = null;
    this.addIdea.link = null;
    this.addIdea.order = this.ideas.length;
    if (this.module && this.module.parameter.mapCenter) {
      this.addIdea.parameter = {
        position: this.module.parameter.mapCenter,
      };
    }
    this.showIdeaSettings = true;
  }

  @Watch('selectedIdea', { immediate: true })
  onSelectedIdeaChanged(): void {
    if (this.selectedIdea) {
      this.settingsIdea = this.selectedIdea;
      this.showIdeaSettings = true;
    }
  }

  editIdea(idea: Idea): void {
    this.settingsIdea = idea;
    this.showIdeaSettings = true;
  }

  async addData(newIdea: Idea): Promise<void> {
    if (!this.settingsIdea.id) this.ideas.push(newIdea);
    if (this.trackingManager) {
      await this.trackingManager.createInstanceStepPoints(
        newIdea.id,
        TaskParticipantIterationStepStatesType.NEUTRAL,
        {
          keywords: newIdea.keywords,
        },
        10,
        null,
        true,
        false,
        () => true
      );
      await this.trackingManager.saveIteration(
        null,
        TaskParticipantIterationStatesType.PARTICIPATED,
        null,
        true
      );
    }
  }

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  updateTask(task: Task): void {
    this.task = task;
  }

  updateIdeas(ideas: Idea[]): void {
    this.ideas = ideas.filter((idea) => idea.isOwn).reverse();
  }

  refreshIdeas(): void {
    this.ideaCash.refreshData();
  }

  saveIdea(idea: Idea): void {
    ideaService
      .putIdea(idea, EndpointAuthorisationType.PARTICIPANT)
      .then(() => {
        this.refreshIdeas();
      });
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
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateModule);
    cashService.deregisterAllGet(this.updateIdeas);
  }

  unmounted(): void {
    this.deregisterAll();
    if (this.trackingManager) this.trackingManager.deregisterAll();
  }
}
</script>

<style lang="scss" scoped>
.button {
  border: unset;
  background-color: unset;
  padding: unset;
  font-size: var(--font-size-xxxlarge);
  color: var(--color-primary);
  position: absolute;
  bottom: 0.5rem;
  right: 1rem;
}

.ideaCard {
  //max-width: 12rem;
  //margin: auto;
}
</style>
