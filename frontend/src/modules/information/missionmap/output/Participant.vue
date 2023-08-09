<template>
  <ParticipantModuleDefaultContainer :task-id="taskId" :module="moduleName">
    <template #footer>
      <el-scrollbar height="15rem">
        <IdeaCard
          v-for="idea of visibleIdeas"
          :key="idea.id"
          class="ideaCard"
          :idea="idea"
          :is-selectable="true"
          :isSelected="idea.id === selectedIdea?.id"
          :selectionColor="selectionColor"
          :is-editable="false"
          :show-state="false"
          :canChangeState="false"
          :handleEditable="false"
          :portrait="false"
          :authHeaderTyp="EndpointAuthorisationType.PARTICIPANT"
          v-on:click="ideaClicked(idea)"
        >
          <div class="columns is-mobile">
            <div
              class="column"
              v-for="parameter of Object.keys(gameConfig.parameter)"
              :key="parameter"
              :style="{
                color: gameConfig.parameter[parameter].color,
                display: idea.parameter[parameter] > 0 ? 'block' : 'none',
              }"
            >
              <font-awesome-icon :icon="gameConfig.parameter[parameter].icon" />
            </div>
          </div>
        </IdeaCard>
      </el-scrollbar>
    </template>
    <IdeaMap
      v-if="module"
      :ideas="ideas"
      v-model:selected-idea="selectedIdea"
      :parameter="module?.parameter"
      :canChangePosition="false"
      v-on:visibleIdeasChanged="visibleIdeasChanged"
      v-on:selectionColorChanged="selectionColor = $event"
    >
    </IdeaMap>
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
import gameConfig from '@/modules/information/missionmap/data/gameConfig.json';

@Options({
  computed: {
    gameConfig() {
      return gameConfig;
    },
  },
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
  EndpointAuthorisationType = EndpointAuthorisationType;
  selectedIdea: Idea | null = null;
  selectionColor = '#0192d0';
  visibleIdeas: Idea[] = [];

  ideaCash!: cashService.SimplifiedCashEntry<Idea[]>;
  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
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

  @Watch('selectedIdea', { immediate: true })
  onSelectedIdeaChanged(): void {
    if (this.selectedIdea) location.hash = `#${this.selectedIdea.id}`;
  }

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  updateTask(task: Task): void {
    this.task = task;
  }

  updateIdeas(ideas: Idea[]): void {
    this.ideas = ideas;
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
  }

  visibleIdeasChanged(ideas: Idea[]): void {
    this.visibleIdeas = ideas;
  }

  ideaClicked(idea: Idea): void {
    this.selectedIdea = idea;
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
