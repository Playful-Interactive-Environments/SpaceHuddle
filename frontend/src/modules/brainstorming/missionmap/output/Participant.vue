<template>
  <ParticipantModuleDefaultContainer
    :task-id="taskId"
    :module="moduleName"
    :module-theme="theme"
    :use-scroll-content="true"
  >
    <template v-slot:headerAfterInfo>
      <el-tabs v-model="activeTab">
        <el-tab-pane
          name="measures"
          :label="
            $t('module.brainstorming.missionmap.participant.tabs.measures')
          "
        >
        </el-tab-pane>
        <el-tab-pane
          :name="MissionProgressParameter.influenceAreas"
          :label="
            $t(
              'module.brainstorming.missionmap.participant.tabs.influenceAreas'
            )
          "
        >
        </el-tab-pane>
        <el-tab-pane
          v-if="module && module.parameter.effectElectricity"
          :name="MissionProgressParameter.electricity"
          :label="
            $t('module.brainstorming.missionmap.participant.tabs.electricity')
          "
        >
        </el-tab-pane>
        <el-tab-pane
          name="map"
          :label="$t('module.brainstorming.missionmap.participant.tabs.map')"
        >
        </el-tab-pane>
      </el-tabs>
    </template>
    <IdeaMap
      v-if="module && activeTab === 'map'"
      :ideas="ideas"
      v-model:selected-idea="selectedIdea"
      :parameter="module?.parameter"
      :canChangePosition="
        (idea) => idea.isOwn && inputManager.isCurrentIdea(idea.id)
      "
      :highlightCondition="(idea) => idea.isOwn"
      v-on:visibleIdeasChanged="visibleIdeasChanged"
      v-on:selectionColorChanged="selectionColor = $event"
      v-on:ideaPositionChanged="saveIdea"
    >
    </IdeaMap>
    <div
      v-else-if="
        module &&
        (activeTab === MissionProgressParameter.influenceAreas ||
          activeTab === MissionProgressParameter.electricity)
      "
      class="statistic"
    >
      <MissionProgressChart
        :task-id="taskId"
        :auth-header-typ="EndpointAuthorisationType.PARTICIPANT"
        :mission-progress-parameter="activeTab"
      />
    </div>
    <draggable
      v-else-if="module && activeTab === 'measures'"
      v-model="orderedIdeas"
      item-key="id"
      @end="dragDone"
      class="measureList"
    >
      <template v-slot:item="{ element }">
        <IdeaCard
          :idea="element"
          :isDraggable="true"
          :portrait="false"
          class="ideaCard"
          :is-selectable="true"
          :isSelected="element.id === selectedIdea?.id"
          :selectionColor="selectionColor"
          :is-editable="element.isOwn && inputManager.isCurrentIdea(element.id)"
          :show-state="false"
          :canChangeState="false"
          :handleEditable="false"
          :background-color="getIdeaColor(element)"
          :authHeaderTyp="EndpointAuthorisationType.PARTICIPANT"
          v-on:click="ideaClicked(element)"
          @ideaDeleted="refreshIdeas"
          @ideaStartEdit="editIdea(element)"
        >
          <div class="columns is-mobile" v-if="element.parameter.shareData">
            <div class="column">
              <font-awesome-icon icon="coins" />
              {{ element.parameter.points }}
            </div>
            <div class="column" @click="() => (showDetails = true)">
              <font-awesome-icon icon="person-booth" />
              {{ getVoteResultForIdea(element.id)?.sum }}
            </div>
          </div>
          <div
            v-if="getInfluenceAreasForIdea(element).length > 0"
            class="columns is-mobile"
          >
            <div
              class="column"
              v-for="parameter of getInfluenceAreasForIdea(element)"
              :key="parameter"
              :style="{
                color: gameConfig.parameter[parameter].color,
              }"
            >
              <font-awesome-icon :icon="gameConfig.parameter[parameter].icon" />
            </div>
          </div>
        </IdeaCard>
      </template>
      <template v-slot:footer>
        <AddItem
          :text="$t('module.brainstorming.missionmap.participant.add')"
          :is-column="true"
          @addNew="editNewImage"
        />
      </template>
    </draggable>
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
          {{ $t('module.brainstorming.missionmap.participant.save') }}
        </el-button>
      </template>
      <el-form-item
        :label="$t('module.brainstorming.missionmap.participant.rate')"
        prop="points"
      >
        <el-rate
          v-model="selectedVote.rate"
          :max="
            module.parameter.maxRatingStars
              ? module.parameter.maxRatingStars
              : 3
          "
        ></el-rate>
      </el-form-item>
      <el-form-item
        :label="$t('module.brainstorming.missionmap.participant.points')"
        prop="points"
      >
        <template #label>
          {{ $t('module.brainstorming.missionmap.participant.points') }}
          <font-awesome-icon icon="person-booth" />
          {{ selectedVote.previousSpendPoints + selectedVote.points }}
        </template>
        <el-button
          type="primary"
          :disabled="points < minSpentPoints || maxSpentPoints < minSpentPoints"
          @click="showSpentPoints = true"
        >
          {{ selectedVote.points }}
          {{ $t('module.brainstorming.missionmap.participant.spentHeader') }}
        </el-button>
      </el-form-item>
      <el-form-item
        :label="$t('module.brainstorming.missionmap.participant.explanation')"
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
      {{ $t('module.brainstorming.missionmap.participant.spentHeader') }}
    </template>
    <template #footer>
      <el-button type="primary" @click="showSpentPoints = false">
        {{ $t('module.brainstorming.missionmap.participant.cancel') }}
      </el-button>
      <el-button type="primary" @click="applyPoints">
        {{ $t('module.brainstorming.missionmap.participant.spent') }}
      </el-button>
    </template>
    <el-slider
      v-if="minSpentPoints <= maxSpentPoints"
      v-model="selectedVote.spentPoints"
      :min="minSpentPoints"
      :max="maxSpentPoints"
      :step="100"
      :marks="spentMarks"
      :disabled="minSpentPoints === maxSpentPoints"
    />
  </el-dialog>

  <IdeaSettings
    v-model:show-modal="showIdeaSettings"
    :taskId="taskId"
    :idea="settingsIdea"
    :title="
      $t('module.brainstorming.missionmap.moderatorContent.settingsTitle')
    "
    :authHeaderTyp="EndpointAuthorisationType.PARTICIPANT"
    @updateData="addData"
  />
  <el-dialog
    v-if="activeTab === 'map'"
    v-model="showSelectedIdea"
    :with-header="false"
    :show-close="false"
    class="idea-card-overlay"
  >
    <IdeaCard
      class="ideaCard"
      :idea="selectedIdea"
      :is-selectable="true"
      :selectionColor="selectionColor"
      :is-editable="
        selectedIdea.isOwn && inputManager.isCurrentIdea(selectedIdea.id)
      "
      :show-state="false"
      :canChangeState="false"
      :handleEditable="false"
      :portrait="false"
      :background-color="getIdeaColor(selectedIdea)"
      :authHeaderTyp="EndpointAuthorisationType.PARTICIPANT"
      v-on:click="ideaClicked(selectedIdea)"
      @ideaDeleted="refreshIdeas"
      @ideaStartEdit="editIdea(selectedIdea)"
    >
      <div class="columns is-mobile" v-if="selectedIdea.parameter.shareData">
        <div class="column">
          <font-awesome-icon icon="coins" />
          {{ selectedIdea.parameter.points }}
        </div>
        <div class="column" @click="() => (showDetails = true)">
          <font-awesome-icon icon="person-booth" />
          {{ getVoteResultForIdea(selectedIdea.id)?.sum }}
        </div>
      </div>
      <div
        v-if="getInfluenceAreasForIdea(selectedIdea).length > 0"
        class="columns is-mobile"
      >
        <div
          class="column"
          v-for="parameter of getInfluenceAreasForIdea(selectedIdea)"
          :key="parameter"
          :style="{
            color: gameConfig.parameter[parameter].color,
          }"
        >
          <font-awesome-icon :icon="gameConfig.parameter[parameter].icon" />
        </div>
      </div>
    </IdeaCard>
  </el-dialog>
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
import gameConfig from '@/modules/brainstorming/missionmap/data/gameConfig.json';
import { Vote, VoteParameterResult } from '@/types/api/Vote';
import * as votingService from '@/services/voting-service';
import * as taskParticipantService from '@/services/task-participant-service';
import ValidationForm from '@/components/shared/molecules/ValidationForm.vue';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import draggable from 'vuedraggable';
import * as themeColors from '@/utils/themeColors';
import { setEmptyParameterIfNotExists } from '@/modules/brainstorming/missionmap/utils/parameter';
import { ElMessageBox } from 'element-plus';
import MissionProgressChart, {
  MissionProgressParameter,
} from '@/modules/brainstorming/missionmap/organisms/MissionProgressChart.vue';
import * as progress from '@/modules/brainstorming/missionmap/utils/progress';
import { CombinedInputManager } from '@/types/input/CombinedInputManager';
import AddItem from '@/components/moderator/atoms/AddItem.vue';

interface ProgressValues {
  origin: number;
  general: number;
  own: number;
  ownFuture: number;
}

@Options({
  computed: {
    gameConfig() {
      return gameConfig;
    },
    MissionProgressParameter() {
      return MissionProgressParameter;
    },
  },
  components: {
    MissionProgressChart,
    FontAwesomeIcon,
    ValidationForm,
    IdeaMap,
    IdeaCard,
    IdeaSettings,
    ParticipantModuleDefaultContainer,
    draggable,
    AddItem,
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
  showSelectedIdea = false;
  selectionColor = '#0192d0';
  visibleIdeas: Idea[] = [];
  votes: Vote[] = [];
  voteResults: VoteParameterResult[] = [];
  decidedIdeas: Idea[] = [];
  sessionId!: string;
  points = 0;
  showDetails = false;
  showSpentPoints = false;
  showProgress = false;
  selectedVote = {
    rate: 0,
    order: 0,
    points: 0,
    explanation: '',
    explanationIndex: 3,
    previousSpendPoints: 0,
    previousInputSpendPoints: 0,
    spentPoints: 0,
  };
  trackingManager!: TrackingManager;
  orderedIdeas: Idea[] = [];
  theme = '';
  inputManager!: CombinedInputManager;
  activeTab = 'measures';

  showIdeaSettings = false;
  addIdea: any = {
    keywords: '',
    description: '',
    link: null,
    image: null, // the datebase64 url of created image
  };
  settingsIdea = this.addIdea;

  get minSpentPoints(): number {
    const absoluteMinimum = 100;
    if (this.selectedIdea) {
      return this.selectedVote.previousSpendPoints
        ? absoluteMinimum
        : this.selectedIdea.parameter.minPoints;
    }
    return absoluteMinimum;
  }

  get maxSpentPoints(): number {
    if (this.selectedIdea) {
      let maxPoints =
        this.selectedIdea.parameter.maxPoints -
        this.selectedVote.previousSpendPoints;
      const ideaId = this.selectedIdea.id;
      const result = this.voteResults.find((item) => item.ideaId === ideaId);
      const values = {
        sum: result ? result.sum : 0,
        count: result ? result.count : 0,
      };
      const maxOpenPoints = this.selectedIdea.parameter.points - values.sum;
      if (maxPoints > maxOpenPoints) maxPoints = maxOpenPoints;
      if (this.selectedIdea.parameter.minParticipants > values.count) {
        let openParticipants =
          this.selectedIdea.parameter.minParticipants - values.count;
        if (this.selectedVote.previousSpendPoints === 0) openParticipants -= 1;
        const maxTotalSpentPoints =
          maxOpenPoints -
          this.selectedIdea.parameter.minPoints * openParticipants;
        if (maxPoints > maxTotalSpentPoints) maxPoints = maxTotalSpentPoints;
      }
      if (maxPoints < this.points) return maxPoints;
    }
    return this.points;
  }

  get spentMarks(): { [key: number]: string } {
    const marks: { [key: number]: string } = {};
    for (let i = this.minSpentPoints; i <= this.maxSpentPoints; i += 100) {
      marks[i] = i.toString();
    }
    return marks;
  }

  get progress(): { [key: string]: ProgressValues } {
    const result: { [key: string]: ProgressValues } = {};
    if (this.module) {
      for (const parameterName in gameConfig.parameter) {
        const origin = this.module.parameter[parameterName];
        result[parameterName] = {
          origin: origin,
          general: origin,
          own: origin,
          ownFuture: origin,
        };
        for (const idea of this.ideas) {
          const influence = idea.parameter.influenceAreas[parameterName];
          const vote = this.voteResults.find((vote) => vote.ideaId === idea.id);
          const ownVote = this.votes.find((vote) => vote.ideaId === idea.id);
          if (vote) {
            if (vote.sum >= idea.parameter.points) {
              result[parameterName].general += influence;
              if (ownVote) {
                result[parameterName].own +=
                  influence * (ownVote.parameter.points / vote.sum);
                result[parameterName].ownFuture +=
                  influence * (ownVote.parameter.points / vote.sum);
              }
            } else {
              if (ownVote && vote.sum > 0) {
                result[parameterName].ownFuture +=
                  influence * (ownVote.parameter.points / vote.sum);
              }
            }
          }
        }
      }
    }
    return result;
  }

  getInfluenceAreasForIdea(idea: Idea): string[] {
    const areas: string[] = [];
    for (const parameter of Object.keys(gameConfig.parameter)) {
      if (idea.parameter.influenceAreas[parameter] > 0) areas.push(parameter);
    }
    return areas;
  }

  isDecided(ideaId: string): boolean {
    return !!this.decidedIdeas.find((idea) => idea.id === ideaId);
  }

  getIdeaColor(idea: Idea): string {
    if (idea.isOwn) return themeColors.getInformingColor('-light');
    if (this.isDecided(idea.id))
      return themeColors.getBrainstormingColor('-light');
    return '#ffffff';
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    if (this.taskId) {
      this.trackingManager = new TrackingManager(this.taskId, {});
      this.inputManager = new CombinedInputManager(
        this.taskId,
        IdeaSortOrder.TIMESTAMP,
        EndpointAuthorisationType.PARTICIPANT,
        true,
        'points'
      );
      this.inputManager.callbackUpdateIdeas = this.updateIdeas;
      this.inputManager.callbackUpdateVotes = this.updateVotes;
    }
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.PARTICIPANT,
      60 * 60
    );
  }

  @Watch('selectedIdea', { immediate: true })
  onSelectedIdeaChanged(): void {
    if (this.selectedIdea) {
      this.loadSelectedVote();
      if (this.activeTab === 'map') this.showSelectedIdea = true;
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

  getCurrentVoteForIdea(ideaId: string): Vote | undefined {
    return this.inputManager.currentVotes.find(
      (vote) => vote.ideaId === ideaId
    );
  }

  getInputVoteForIdea(ideaId: string): Vote | undefined {
    return this.inputManager.inputVotes.find((vote) => vote.ideaId === ideaId);
  }

  getVoteResultForIdea(ideaId: string): VoteParameterResult | undefined {
    return this.voteResults.find((vote) => vote.ideaId === ideaId);
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

  updateIdeas(): void {
    const ideas = this.inputManager.ideas;
    this.ideas = ideas.filter((idea) => idea.parameter.shareData || idea.isOwn);
    this.orderedIdeas = [
      ...this.ideas, //.filter((idea) => idea.parameter.shareData),
    ];
    this.sortIdeasByVote();
    this.calculateDecidedIdeas();
  }

  updateVotes(): void {
    this.votes = this.inputManager.votes;
    this.updateVoteResult(this.inputManager.votingResult);
    this.sortIdeasByVote();
  }

  updateVoteResult(votes: VoteParameterResult[]): void {
    this.voteResults = votes;
    this.calculateDecidedIdeas();
  }

  calculateDecidedIdeas(): void {
    this.decidedIdeas = progress.calculateDecidedIdeasFromResult(
      this.voteResults,
      this.ideas
    );
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
      this.orderedIdeas = [
        ...this.ideas, // .filter((idea) => idea.parameter.shareData),
      ];
    }
  }

  async saveVoting(): Promise<void> {
    const trackVote = (vote: Vote, points: number): void => {
      this.trackingManager.createInstanceStepPoints(
        vote.ideaId,
        TaskParticipantIterationStepStatesType.NEUTRAL,
        {
          rating: this.selectedVote.rate,
          order: this.selectedVote.order,
          points: this.selectedVote.points,
          explanation: this.selectedVote.explanation,
          explanationIndex: this.selectedVote.explanationIndex,
        },
        points,
        this.selectedVote.points,
        true
      );
    };

    if (this.selectedIdea) {
      const ownExplanationIndex =
        this.selectedIdea.parameter.explanationList.length;
      if (
        this.selectedVote.explanationIndex < ownExplanationIndex &&
        this.selectedVote.explanation !==
          this.selectedIdea.parameter.explanationList[
            this.selectedVote.explanationIndex
          ]
      )
        this.selectedVote.explanationIndex = ownExplanationIndex;
      const vote = this.getCurrentVoteForIdea(this.selectedIdea.id);
      let points = 0;
      if (!vote) {
        if (this.selectedVote.rate) points += 10;
        if (this.selectedVote.explanation) {
          points += 10;
          if (this.selectedVote.explanationIndex === ownExplanationIndex)
            points += 10;
        }
        votingService
          .postVote(this.taskId, {
            ideaId: this.selectedIdea.id,
            rating: this.selectedVote.rate,
            detailRating: this.selectedVote.order,
            parameter: {
              points:
                this.selectedVote.previousSpendPoints -
                this.selectedVote.previousInputSpendPoints +
                this.selectedVote.points,
              explanation: this.selectedVote.explanation,
              explanationIndex: this.selectedVote.explanationIndex,
            },
          })
          .then(async (vote) => {
            trackVote(vote, points);
            this.votes.push(vote);
            await this.inputManager.refreshVotes();
            this.loadSelectedVote();
          });
      } else {
        if (this.selectedVote.rate && !vote.rating) points += 10;
        const stepsWithExplanation = this.trackingManager.stepList.filter(
          (step) => step.ideaId === vote.ideaId && step.parameter.explanation
        );
        const stepsWithOwnExplanation = stepsWithExplanation.filter(
          (step) => step.parameter.explanationIndex === ownExplanationIndex
        );
        if (
          this.selectedVote.explanation &&
          stepsWithExplanation.length === 0
        ) {
          points += 10;
          if (this.selectedVote.explanationIndex === ownExplanationIndex)
            points += 10;
        } else if (
          this.selectedVote.explanation &&
          this.selectedVote.explanationIndex === ownExplanationIndex &&
          stepsWithOwnExplanation.length === 0
        ) {
          points += 10;
        }
        vote.rating = this.selectedVote.rate;
        vote.detailRating = this.selectedVote.order;
        vote.parameter = {
          points:
            this.selectedVote.previousSpendPoints -
            this.selectedVote.previousInputSpendPoints +
            this.selectedVote.points,
          explanation: this.selectedVote.explanation,
          explanationIndex: this.selectedVote.explanationIndex,
        };
        votingService.putVote(vote).then(async () => {
          trackVote(vote, points);
          await this.inputManager.refreshVotes();
          this.loadSelectedVote();
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
    if (module.parameter.theme) this.theme = module.parameter.theme;
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateModule);
    cashService.deregisterAllGet(this.updateStates);

    if (this.inputManager) this.inputManager.deregisterAll();
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
        const index = this.orderedIdeas.findIndex(
          (item) => item.id === idea.id
        );
        return {
          idea: idea,
          index: index !== -1 ? index : 10000,
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
      previousInputSpendPoints: 0,
      spentPoints: 0,
    };
  }

  loadSelectedVote(): void {
    if (this.selectedIdea) {
      const vote = this.getCurrentVoteForIdea(this.selectedIdea.id);
      const inputVote = this.getInputVoteForIdea(this.selectedIdea.id);
      const voteResult = this.getVoteResultForIdea(this.selectedIdea.id);
      if (vote && voteResult) {
        if (inputVote) {
          this.selectedVote = {
            rate: vote.rating ? vote.rating : inputVote.rating,
            order: vote.detailRating
              ? vote.detailRating
              : inputVote.detailRating,
            points: 0,
            explanation: vote.parameter.explanation
              ? vote.parameter.explanation
              : inputVote.parameter.explanation,
            explanationIndex: vote.parameter.explanationIndex
              ? vote.parameter.explanationIndex
              : inputVote.parameter.explanationIndex,
            previousSpendPoints: voteResult.sum,
            previousInputSpendPoints: inputVote.parameter.points,
            spentPoints: 0,
          };
        } else {
          this.selectedVote = {
            rate: vote.rating,
            order: vote.detailRating,
            points: 0,
            explanation: vote.parameter.explanation,
            explanationIndex: vote.parameter.explanationIndex,
            previousSpendPoints: voteResult.sum,
            previousInputSpendPoints: 0,
            spentPoints: 0,
          };
        }
      } else this.reset();
    }
  }

  /* eslint-disable @typescript-eslint/explicit-module-boundary-types*/
  async dragDone(): Promise<void> {
    for (const [index, idea] of this.orderedIdeas.entries()) {
      const vote = this.getCurrentVoteForIdea(idea.id);
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
          this.inputManager.refreshVotes();
        });
      }
    }
    this.sortVisibleIdeas();
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
    if (!this.settingsIdea.id) {
      this.inputManager.addIdea(newIdea);
      this.updateIdeas();
      this.trackingManager.createInstanceStepPoints(
        newIdea.id,
        TaskParticipantIterationStepStatesType.NEUTRAL,
        {
          participantIdea: true,
        },
        50,
        null,
        true
      );
    }
  }

  saveIdea(idea: Idea): void {
    ideaService
      .putIdea(idea, EndpointAuthorisationType.PARTICIPANT)
      .then(() => {
        this.refreshIdeas();
      });
  }

  refreshIdeas(): void {
    this.inputManager.refreshIdeas();
  }

  finished(): void {
    ElMessageBox.confirm(
      (this as any).$t(
        'module.brainstorming.missionmap.participant.finished.text'
      ),
      (this as any).$t(
        'module.brainstorming.missionmap.participant.finished.header'
      ),
      {
        confirmButtonText: (this as any).$t(
          'module.brainstorming.missionmap.participant.finished.yes'
        ),
        cancelButtonText: (this as any).$t(
          'module.brainstorming.missionmap.participant.finished.no'
        ),
        type: 'warning',
      }
    )
      .then(() => {
        this.trackingManager.setManualFinishedState();
        this.$router.back();
      })
      .catch(() => {
        //
      });
  }
}
</script>

<style lang="scss">
.idea-card-overlay {
  background-color: unset;
  box-shadow: unset;

  .el-dialog__header {
    padding: unset;
  }

  .el-dialog__body {
    padding: 0.5rem;
  }
}
</style>

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

.add {
  --background-color: #ffffff;
}

.add--column {
  min-height: unset;
}

.add::v-deep(.el-card__body) {
  min-height: unset;
}

.measureList {
  padding-bottom: 1rem;
}

.module-content::v-deep(.fixed) {
  background-color: unset;
}

.statistic {
  margin: 0 2rem;
}

[module-theme='preparation'] {
  background-image: url('@/modules/brainstorming/missionmap/assets/preparation.png');
  background-size: cover;
  background-position: center top;

  .el-card {
    margin-right: 2rem;
    margin-left: 2rem;
    border-radius: 0;
    background: linear-gradient(
        color-mix(in srgb, var(--background-color) 45%, transparent),
        color-mix(in srgb, var(--background-color) 45%, transparent)
      ),
      url('@/modules/information/quiz/assets/paper.jpg');
    filter: drop-shadow(0.3rem 0.3rem 0.5rem var(--color-gray-dark));
    border: none;
  }

  .el-card::v-deep(.card__image) {
    background-color: color-mix(in srgb, var(--card-color) 30%, transparent);
    border-radius: 0;
  }

  .el-card::v-deep(.el-card__body) {
    border-radius: 0;
  }

  .statistic {
    background-image: url('@/modules/information/quiz/assets/paper.jpg');
    filter: drop-shadow(0.3rem 0.3rem 0.5rem var(--color-gray-dark));
  }
}

[module-theme='preparation'].module-content::v-deep(.media) {
  --module-color: var(--color-dark-contrast);
}

[module-theme='preparation'].module-content::v-deep(.participant-content) {
  margin-bottom: -1rem;
  margin-left: -2rem;
  margin-right: -2rem;
}

[module-theme='preparation'].module-content::v-deep(.mapSpace) {
  filter: drop-shadow(0.3rem 0.3rem 0.5rem var(--color-gray-dark));
}

[module-theme='preparation'].module-content::v-deep(.task-info) {
  padding: 1rem;
  border-radius: 0;
  background: linear-gradient(
      color-mix(in srgb, var(--color-informing) 45%, transparent),
      color-mix(in srgb, var(--color-informing) 45%, transparent)
    ),
    url('@/modules/information/quiz/assets/paper.jpg');
  filter: drop-shadow(0.3rem 0.3rem 0.5rem var(--color-gray-dark));
  border: none;
}

[module-theme='meeting'] {
  background-image: url('@/modules/brainstorming/missionmap/assets/lectern.png'),
    url('@/modules/brainstorming/missionmap/assets/stage.png');
  background-position: center bottom, left top;
  background-repeat: no-repeat;
  background-size: contain, cover;

  .el-card {
    border-radius: var(--border-radius) var(--border-radius) 0
      var(--border-radius);
    background-color: color-mix(
      in srgb,
      var(--background-color) 60%,
      transparent
    );
  }

  .el-card::v-deep(.card__image) {
    background-color: color-mix(in srgb, var(--card-color) 30%, transparent);
    border-radius: var(--border-radius) 0 0 var(--border-radius);
  }

  .el-card::v-deep(.el-card__body) {
    border-radius: var(--border-radius) var(--border-radius) 0
      var(--border-radius);
  }

  .statistic {
    margin: 0;
    background-color: white;
    border: var(--color-dark-contrast) solid 0.5rem;
  }
}

[module-theme='meeting'].module-content::v-deep(.mapSpace) {
  border: var(--color-dark-contrast) solid 0.5rem;
}

[module-theme='meeting'].module-content::v-deep(.task-info) {
  border-radius: var(--border-radius) var(--border-radius) var(--border-radius)
    0;
  background-color: color-mix(in srgb, var(--color-informing) 60%, transparent);
  border: solid 2px var(--color-gray);
  padding: 0.5rem;
}
</style>
