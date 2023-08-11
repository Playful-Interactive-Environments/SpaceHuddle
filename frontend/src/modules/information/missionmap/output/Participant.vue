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
            <div class="column">
              <font-awesome-icon icon="coins" />
              {{ idea.parameter.points }}
            </div>
            <div class="column" @click="() => (showDetails = true)">
              <font-awesome-icon icon="person-booth" />
              {{ getVoteForIdea(idea.id)?.parameter.points }}
            </div>
          </div>
          <div class="columns is-mobile">
            <div
              class="column"
              v-for="parameter of Object.keys(gameConfig.parameter)"
              :key="parameter"
              :style="{
                color: gameConfig.parameter[parameter].color,
                display:
                  idea.parameter.influenceAreas[parameter] > 0
                    ? 'block'
                    : 'none',
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
  <ValidationForm
    :form-data="selectedVote"
    :use-default-submit="false"
    v-on:submitDataValid="saveVoting"
    v-on:reset="reset"
  >
    <el-dialog v-model="showDetails">
      <template #header>{{ selectedIdea?.keywords }}</template>
      <template #footer>
        <el-button type="primary" native-type="submit">
          {{ $t('module.information.missionmap.participant.save') }}
        </el-button>
      </template>
      <el-form-item
        :label="$t('module.information.missionmap.participant.rate')"
        prop="points"
      >
        <el-rate v-model="selectedVote.rate" :max="3"></el-rate>
      </el-form-item>
      <el-form-item
        :label="$t('module.information.missionmap.participant.points')"
        prop="points"
      >
        <el-slider
          v-model="selectedVote.points"
          :min="selectedIdea?.parameter.minPoints"
          :max="maxSpentPoints"
          :step="100"
        />
      </el-form-item>
      <el-form-item
        :label="$t('module.information.missionmap.participant.explanation')"
        prop="explanation"
      >
        <el-button
          style="width: 100%; justify-content: left"
          v-for="(explanation, index) of selectedIdea?.parameter
            .explanationList"
          :key="index"
          @click="
            () => {
              selectedVote.explanation = explanation;
              selectedVote.explanationIndex = index;
            }
          "
        >
          <template #icon>
            <span style="width: 1.5rem; text-align: left">
              {{ index + 1 }}.
            </span>
          </template>
          {{ explanation }}
        </el-button>
        <el-input v-model="selectedVote.explanation">
          <template #prefix>
            <span style="width: 1.5rem">
              {{ selectedIdea?.parameter.explanationList.length + 1 }}.
            </span>
          </template>
        </el-input>
      </el-form-item>
    </el-dialog>
  </ValidationForm>
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
import { Vote } from '@/types/api/Vote';
import * as votingService from '@/services/voting-service';
import * as taskParticipantService from '@/services/task-participant-service';
import ValidationForm from '@/components/shared/molecules/ValidationForm.vue';

@Options({
  computed: {
    gameConfig() {
      return gameConfig;
    },
  },
  components: {
    ValidationForm,
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
  votes: Vote[] = [];
  sessionId!: string;
  points = 0;
  showDetails = false;
  selectedVote = {
    rate: 0,
    order: 0,
    points: 0,
    explanation: '',
    explanationIndex: 3,
  };

  get maxSpentPoints(): number {
    if (
      this.selectedIdea &&
      this.selectedIdea.parameter.maxPoints < this.points
    )
      return this.selectedIdea.parameter.maxPoints;
    return this.points;
  }

  votingCash!: cashService.SimplifiedCashEntry<Vote[]>;
  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.PARTICIPANT,
      60 * 60
    );
    ideaService.registerGetIdeasForTask(
      this.taskId,
      IdeaSortOrder.TIMESTAMP,
      null,
      this.updateIdeas,
      EndpointAuthorisationType.PARTICIPANT,
      30
    );
    this.votingCash = votingService.registerGetVotes(
      this.taskId,
      this.updateVotes,
      EndpointAuthorisationType.PARTICIPANT,
      60 * 60
    );
  }

  @Watch('selectedIdea', { immediate: true })
  onSelectedIdeaChanged(): void {
    if (this.selectedIdea) {
      location.hash = `#${this.selectedIdea.id}`;
      const vote = this.getVoteForIdea(this.selectedIdea.id);
      if (vote) {
        this.selectedVote = {
          rate: vote.rating,
          order: vote.detailRating,
          points: vote.parameter.points,
          explanation: vote.parameter.explanation,
          explanationIndex: vote.parameter.explanationIndex,
        };
      } else this.reset();
    }
  }

  getVoteForIdea(ideaId: string): Vote | undefined {
    return this.votes.find((vote) => vote.ideaId === ideaId);
  }

  updateStates(points: number): void {
    this.points = points;
  }

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  updateTask(task: Task): void {
    this.sessionId = task.sessionId;
    this.task = task;
    cashService.deregisterAllGet(this.updateStates);
    if (this.sessionId) {
      taskParticipantService.registerGetPoints(
        this.sessionId,
        this.updateStates,
        EndpointAuthorisationType.PARTICIPANT,
        60 * 60
      );
    }
  }

  updateIdeas(ideas: Idea[]): void {
    this.ideas = ideas;
  }

  updateVotes(votes: Vote[]): void {
    this.votes = votes;
  }

  async saveVoting(): Promise<void> {
    if (this.selectedIdea) {
      if (
        this.selectedVote.explanationIndex <
          this.selectedIdea.parameter.explanationList.length &&
        this.selectedVote.explanation !==
          this.selectedIdea.parameter.explanationList[
            this.selectedVote.explanationIndex
          ]
      )
        this.selectedVote.explanationIndex =
          this.selectedIdea.parameter.explanationList.length;
      const vote = this.getVoteForIdea(this.selectedIdea.id);
      if (!vote) {
        votingService
          .postVote(this.taskId, {
            ideaId: this.selectedIdea.id,
            rating: this.selectedVote.rate,
            detailRating: this.selectedVote.order,
            parameter: {
              points: this.selectedVote.points,
              explanation: this.selectedVote.explanation,
              explanationIndex: this.selectedVote.explanationIndex,
            },
          })
          .then((vote) => {
            this.votes.push(vote);
          });
      } else {
        vote.rating = this.selectedVote.rate;
        vote.detailRating = this.selectedVote.order;
        vote.parameter = {
          points: this.selectedVote.points,
          explanation: this.selectedVote.explanation,
          explanationIndex: this.selectedVote.explanationIndex,
        };
        votingService.putVote(vote).then(() => {
          this.votingCash.refreshData();
        });
      }
    }
    this.showDetails = false;
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
    cashService.deregisterAllGet(this.updateStates);
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

  reset(): void {
    this.selectedVote = {
      rate: 0,
      order: 0,
      points: 0,
      explanation: '',
      explanationIndex: 3,
    };
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

.el-button::v-deep(.el-icon) {
  width: unset;
}
</style>
