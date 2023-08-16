<template>
  <ParticipantModuleDefaultContainer :task-id="taskId" :module="moduleName">
    <template #headerOverlay>
      <div class="header-overlay-right columns is-mobile">
        <div class="column" @click="showSort = true">
          <font-awesome-icon icon="sort" />
        </div>
        <div class="column" @click="editNewImage">
          <font-awesome-icon icon="circle-plus" />
        </div>
        <div class="column" @click="finshed">
          <font-awesome-icon icon="file-circle-check" />
        </div>
      </div>
    </template>
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
          :is-editable="idea.isOwn"
          :show-state="false"
          :canChangeState="false"
          :handleEditable="false"
          :portrait="false"
          :background-color="getIdeaColor(idea)"
          :authHeaderTyp="EndpointAuthorisationType.PARTICIPANT"
          v-on:click="ideaClicked(idea)"
          @ideaDeleted="refreshIdeas"
          @ideaStartEdit="editIdea(idea)"
        >
          <div class="columns is-mobile" v-if="idea.parameter.shareData">
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
      :canChangePosition="(idea) => idea.isOwn"
      v-on:visibleIdeasChanged="visibleIdeasChanged"
      v-on:selectionColorChanged="selectionColor = $event"
      v-on:ideaPositionChanged="saveIdea"
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
        <template #label>
          {{ $t('module.information.missionmap.participant.points') }}
          <font-awesome-icon icon="person-booth" />
          {{ selectedVote.previousSpendPoints + selectedVote.points }}
        </template>
        <el-button
          type="primary"
          :disabled="this.points < selectedIdea?.parameter.minPoints"
          @click="showSpentPoints = true"
        >
          {{ selectedVote.points }}
          {{ $t('module.information.missionmap.participant.spentHeader') }}
        </el-button>
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
  <el-dialog v-model="showSpentPoints">
    <template #header>
      {{ $t('module.information.missionmap.participant.spentHeader') }}
    </template>
    <template #footer>
      <el-button type="primary" @click="showSpentPoints = false">
        {{ $t('module.information.missionmap.participant.cancel') }}
      </el-button>
      <el-button type="primary" @click="applyPoints">
        {{ $t('module.information.missionmap.participant.spent') }}
      </el-button>
    </template>
    <el-slider
      v-model="selectedVote.spentPoints"
      :min="selectedIdea?.parameter.minPoints"
      :max="maxSpentPoints"
      :step="100"
      :marks="spentMarks"
      :disabled="selectedIdea?.parameter.minPoints === maxSpentPoints"
    />
  </el-dialog>
  <el-dialog v-model="showSort">
    <div>
      <draggable v-model="orderedIdeas" item-key="id" @end="dragDone">
        <template v-slot:item="{ element }">
          <IdeaCard
            :idea="element"
            :isDraggable="true"
            :portrait="false"
            :is-editable="false"
          ></IdeaCard>
        </template>
      </draggable>
    </div>
  </el-dialog>

  <IdeaSettings
    v-model:show-modal="showIdeaSettings"
    :taskId="taskId"
    :idea="settingsIdea"
    :title="$t('module.brainstorming.map.moderatorContent.settingsTitle')"
    :authHeaderTyp="EndpointAuthorisationType.PARTICIPANT"
    @updateData="addData"
  />
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
import { TrackingManager } from '@/types/tracking/TrackingManager';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import draggable from 'vuedraggable';
import { setHash } from '@/utils/url';
import * as themeColors from '@/utils/themeColors';
import { setEmptyParameterIfNotExists } from '@/modules/information/missionmap/utils/parameter';

@Options({
  computed: {
    gameConfig() {
      return gameConfig;
    },
  },
  components: {
    FontAwesomeIcon,
    ValidationForm,
    IdeaMap,
    IdeaCard,
    IdeaSettings,
    ParticipantModuleDefaultContainer,
    draggable,
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
  showSpentPoints = false;
  showSort = false;
  selectedVote = {
    rate: 0,
    order: 0,
    points: 0,
    explanation: '',
    explanationIndex: 3,
    previousSpendPoints: 0,
    spentPoints: 0,
  };
  trackingManager!: TrackingManager;
  orderedIdeas: Idea[] = [];

  showIdeaSettings = false;
  addIdea: any = {
    keywords: '',
    description: '',
    link: null,
    image: null, // the datebase64 url of created image
  };
  settingsIdea = this.addIdea;

  get maxSpentPoints(): number {
    if (this.selectedIdea) {
      const maxPoints =
        this.selectedIdea.parameter.maxPoints -
        this.selectedVote.previousSpendPoints;
      if (maxPoints < this.points) return maxPoints;
    }
    return this.points;
  }

  get spentMarks(): { [key: number]: string } {
    const marks: { [key: number]: string } = {};
    for (
      let i = this.selectedIdea?.parameter.minPoints;
      i <= this.maxSpentPoints;
      i += 100
    ) {
      marks[i] = i.toString();
    }
    return marks;
  }

  getIdeaColor(idea: Idea): string {
    if (idea.isOwn) return themeColors.getInformingColor('-light');
    return '#ffffff';
  }

  votingCash!: cashService.SimplifiedCashEntry<Vote[]>;
  ideaCash!: cashService.SimplifiedCashEntry<Idea[]>;
  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    if (this.taskId) {
      this.trackingManager = new TrackingManager(this.taskId, {});
    }
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
      setHash(this.selectedIdea.id);
      const vote = this.getVoteForIdea(this.selectedIdea.id);
      if (vote) {
        this.selectedVote = {
          rate: vote.rating,
          order: vote.detailRating,
          points: 0,
          explanation: vote.parameter.explanation,
          explanationIndex: vote.parameter.explanationIndex,
          previousSpendPoints: vote.parameter.points,
          spentPoints: 0,
        };
      } else this.reset();
    }
  }

  @Watch('showSpentPoints', { immediate: true })
  onShowSpentPoints(): void {
    if (this.showSpentPoints)
      this.selectedVote.spentPoints = this.selectedVote.points;
  }

  applyPoints(): void {
    this.selectedVote.points = this.selectedVote.spentPoints;
    this.showSpentPoints = false;
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
    this.ideas = ideas.filter((idea) => idea.parameter.shareData || idea.isOwn);
    this.orderedIdeas = [...this.ideas];
    this.sortIdeasByVote();
  }

  updateVotes(votes: Vote[]): void {
    this.votes = votes;
    this.sortIdeasByVote();
  }

  sortIdeasByVote(): void {
    if (this.ideas && this.votes) {
      this.ideas = this.ideas
        .map((idea) => {
          const vote = this.votes.find((vote) => vote.ideaId === idea.id);
          return {
            idea: idea,
            order: vote ? vote.detailRating : 0,
          };
        })
        .sort((a, b) => a.order - b.order)
        .map((item) => item.idea);
      this.orderedIdeas = [...this.ideas];
    }
  }

  async saveVoting(): Promise<void> {
    const trackVote = (vote: Vote): void => {
      this.trackingManager.createInstanceStep(
        vote.ideaId,
        TaskParticipantIterationStepStatesType.NEUTRAL,
        {
          rating: this.selectedVote.rate,
          order: this.selectedVote.order,
          points: this.selectedVote.points,
          explanation: this.selectedVote.explanation,
          explanationIndex: this.selectedVote.explanationIndex,
        },
        null,
        this.selectedVote.points,
        true
      );
    };

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
              points:
                this.selectedVote.previousSpendPoints +
                this.selectedVote.points,
              explanation: this.selectedVote.explanation,
              explanationIndex: this.selectedVote.explanationIndex,
            },
          })
          .then((vote) => {
            this.votes.push(vote);
            trackVote(vote);
          });
      } else {
        vote.rating = this.selectedVote.rate;
        vote.detailRating = this.selectedVote.order;
        vote.parameter = {
          points:
            this.selectedVote.previousSpendPoints + this.selectedVote.points,
          explanation: this.selectedVote.explanation,
          explanationIndex: this.selectedVote.explanationIndex,
        };
        votingService.putVote(vote).then(() => {
          this.votingCash.refreshData();
          trackVote(vote);
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
    if (this.trackingManager) this.trackingManager.deregisterAll();
  }

  visibleIdeasChanged(ideas: Idea[]): void {
    this.visibleIdeas = ideas;
    this.sortVisibleIdeas();
  }

  sortVisibleIdeas(): void {
    this.visibleIdeas = this.visibleIdeas
      .map((idea) => {
        return {
          idea: idea,
          index: this.orderedIdeas.findIndex((item) => item.id === idea.id),
        };
      })
      .sort((a, b) => a.index - b.index)
      .map((item) => item.idea);
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
      previousSpendPoints: 0,
      spentPoints: 0,
    };
  }

  /* eslint-disable @typescript-eslint/explicit-module-boundary-types*/
  async dragDone(): Promise<void> {
    for (const [index, idea] of this.orderedIdeas.entries()) {
      const vote = this.votes.find((vote) => vote.ideaId === idea.id);
      if (!vote) {
        votingService
          .postVote(this.taskId, {
            ideaId: idea.id,
            rating: 0,
            detailRating: index,
            parameter: {
              points: 0,
              explanation: '',
              explanationIndex: -1,
            },
          })
          .then((vote) => {
            this.votes.push(vote);
          });
      } else {
        vote.detailRating = index;
        votingService.putVote(vote).then(() => {
          this.votingCash.refreshData();
        });
      }
    }
    this.sortVisibleIdeas();
    //this.ideas = [...this.orderedIdeas];
  }

  editNewImage(): void {
    this.settingsIdea = this.addIdea;
    this.addIdea.keywords = '';
    this.addIdea.description = '';
    this.addIdea.image = null;
    this.addIdea.link = null;
    this.addIdea.order = this.ideas.length;
    this.addIdea.parameter = {};
    setEmptyParameterIfNotExists(this.addIdea, () => this.module, false);
    this.showIdeaSettings = true;
  }

  editIdea(idea: Idea): void {
    this.settingsIdea = idea;
    this.showIdeaSettings = true;
  }

  addData(newIdea: Idea): void {
    if (!this.settingsIdea.id) this.ideas.push(newIdea);
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

.el-slider {
  height: 3rem;
}

.header-overlay-right {
  position: absolute;
  top: 2rem;
  right: 0;
}
</style>
