<template>
  <ParticipantModuleDefaultContainer :task-id="taskId" :module="moduleName">
    <template #header>
      <div id="starImageBackground">
        <div id="starImageContainer">
          <img
            src="@/assets/illustrations/Voting/starDesat.png"
            alt="star"
            class="starImage"
          />
          <img
            src="@/assets/illustrations/Voting/starGlow.png"
            alt="star"
            class="starImage"
            id="glowingStar"
            :style="{ '--star-opacity': getOpacity() }"
          />
        </div>
      </div>
    </template>
    <div id="preloader"></div>
    <div id="loadingScreen">
      <span>{{ $t('module.voting.default.participant.waiting') }}...</span>
      <span
        id="loading"
        v-loading="true"
        element-loading-background="rgba(0, 0, 0, 0)"
      ></span>
    </div>
    <div class="media" v-if="ideaPointer < ideas.length">
      <IdeaCard
        class="media-left, IdeaCard"
        :idea="ideas[ideaPointer]"
        :is-selectable="false"
        :is-editable="false"
        :show-state="false"
        v-on:click="
          showIdeaOverlay = true;
          currentRateIdea = true;
        "
      />
      <el-rate
        class="media-content, ratingStars"
        v-model:model-value="rate"
        :max="maxRate"
        v-on:change="saveVoting($event)"
      ></el-rate>
    </div>

    <div class="finished" v-if="finished">
      {{ $t('module.voting.default.participant.thanks') }}
      <br />
      <el-button
        type="primary"
        class="el-button--submit"
        native-type="submit"
        @click="$router.back()"
      >
        {{ $t('module.voting.default.participant.back') }}
      </el-button>
    </div>
    <div v-if="waiting">
      {{ $t('module.voting.default.participant.waiting') }}
    </div>
    <br />
    <el-divider v-if="validVotes.length > 0"></el-divider>
    <div
      class="media"
      v-for="vote in validVotes.sort((a, b) =>
        b.timestamp.localeCompare(a.timestamp)
      )"
      :key="vote.id"
    >
      <IdeaCard
        v-if="voteIdea(vote.ideaId)"
        class="IdeaCard"
        :idea="voteIdea(vote.ideaId)"
        :is-selectable="false"
        :is-editable="false"
        :cutLongTexts="true"
        :show-state="false"
        v-on:click="
          showIdeaOverlay = true;
          voteIdOverlay = voteIdea(vote.ideaId);
          currentRateIdea = false;
        "
      />
      <el-rate
        class="media-content, ratingStars"
        v-model:model-value="vote.rating"
        :max="maxRate"
        v-on:change="saveVoting($event, vote)"
      ></el-rate>
    </div>

    <el-dialog v-model="showIdeaOverlay" class="big">
      <IdeaCard
        v-if="ideaPointer < ideas.length && currentRateIdea"
        :idea="ideas[ideaPointer]"
        :is-selectable="false"
        :is-editable="false"
        :show-state="false"
        class="ideaCardOverlay"
      />
      <IdeaCard
        v-if="voteIdOverlay && !currentRateIdea"
        class="ideaCardOverlay"
        :idea="voteIdOverlay"
        :is-selectable="false"
        :is-editable="false"
        :cutLongTexts="true"
        :show-state="false"
      />
    </el-dialog>
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/layout/ParticipantModuleDefaultContainer.vue';
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';
import * as moduleService from '@/services/module-service';
import * as viewService from '@/services/view-service';
import * as votingService from '@/services/voting-service';
import { Idea } from '@/types/api/Idea';
import { Vote } from '@/types/api/Vote';
import { Module } from '@/types/api/Module';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as cashService from '@/services/cash-service';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import { delay } from '@/utils/wait';
import TaskParticipantIterationStatesType from '@/types/enum/TaskParticipantIterationStatesType';
import TaskParticipantStatesType from '@/types/enum/TaskParticipantStatesType';

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
  task: Task | null = null;
  module: Module | null = null;
  ideas: Idea[] = [];
  allIdeas: Idea[] = [];
  votes: Vote[] = [];
  ideaPointer = 0;
  rate = 0;
  maxRate = 5;
  starOpacity = 0;
  initIdeaNumber = 0;
  trackingManager!: TrackingManager;

  inputCash!: cashService.SimplifiedCashEntry<Idea[]>;
  votingCash!: cashService.SimplifiedCashEntry<Vote[]>;
  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    if (this.taskId) {
      this.trackingManager = new TrackingManager(this.taskId, {}, true);
    }
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.PARTICIPANT,
      60 * 60
    );
    this.inputCash = viewService.registerGetInputIdeas(
      this.taskId,
      IdeaSortOrder.TIMESTAMP,
      null,
      this.updateInputIdeas,
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

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  get finished(): boolean {
    return this.validVotes.length > 0 && this.ideaPointer >= this.ideas.length;
  }

  get waiting(): boolean {
    if (this.allIdeas.length != 0) {
      const element = document.getElementById('loadingScreen');

      if (element != null && !element.classList.contains('zeroOpacity')) {
        const preload = document.getElementById('preloader');
        preload?.classList.add('PreloadSprites');

        setTimeout(() => preload?.classList.remove('PreloadSprites'), 1000);
        setTimeout(() => element?.classList.add('zeroOpacity'), 1000);
        setTimeout(() => element?.classList.add('hidden'), 3000);
      }
    }
    return this.allIdeas.length === 0;
  }

  get validVotes(): Vote[] {
    return this.votes.filter((vote) =>
      this.allIdeas.find((idea) => idea.id === vote.ideaId)
    );
  }

  mounted(): void {
    this.initConfig(5);
    this.waiting;
  }

  voteIdea(ideaId: string | null): Idea | undefined {
    return this.allIdeas.find((idea) => idea.id === ideaId);
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateVotes);
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateModule);
    cashService.deregisterAllGet(this.updateInputIdeas);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  initConfig(count: number): void {
    this.maxRate = count;
  }

  updateTask(task: Task): void {
    this.task = task;
  }

  inputIdeas: Idea[] = [];
  updateInputIdeas(ideas: Idea[]): void {
    this.inputIdeas = ideas;
    this.updateIdeas();
  }

  updateVotes(votes: Vote[]): void {
    this.votes = votes;
    votes.forEach((vote) => {
      const ideaIndex = this.ideas.findIndex((idea) => idea.id == vote.ideaId);
      if (ideaIndex >= 0) this.ideas.splice(ideaIndex, 1);
    });
    this.ideaPointer = 0;
  }

  updateIdeas(): void {
    const ideas = viewService.customizeView(
      this.inputIdeas,
      null,
      (this as any).$t,
      [],
      '',
      this.task ? this.task.parameter.input.length : 1
    );
    this.allIdeas = [...ideas];
    this.ideaPointer = ideas.length;
    this.ideas = ideas;
    this.initIdeaNumber = ideas.length;
    this.getVotes();
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
    if (this.maxRate != parseInt(this.module.parameter.maxRate))
      this.getVotes();
  }

  async getVotes(): Promise<void> {
    if (this.taskId) {
      if (this.module) this.initConfig(parseInt(this.module.parameter.maxRate));
      else this.initConfig(5);
    }
  }

  scrollToTop(delay = 100): void {
    setTimeout(() => {
      window.scroll(0, 0);
    }, delay);
  }

  async saveVoting(rate: number, vote: Vote | null = null): Promise<void> {
    await delay(1000);
    if (!vote) {
      if (this.ideaPointer < this.ideas.length) {
        const idea = this.ideas[this.ideaPointer];
        vote = await votingService.postVote(this.taskId, {
          ideaId: idea.id,
          rating: rate,
          detailRating: rate,
        });
        this.votes.push(vote);
        this.rate = 0;
        this.ideaPointer++;
        this.scrollToTop(0);
      }
    } else {
      vote.rating = rate;
      vote.detailRating = rate;
      await votingService.putVote(vote);
    }
    if (this.trackingManager && vote) {
      if (
        !this.trackingManager.iterationStep ||
        this.trackingManager.iterationStep.ideaId !== vote.ideaId ||
        this.trackingManager.iterationStep.iteration !==
          this.trackingManager.iteration?.iteration
      ) {
        await this.trackingManager.createInstanceStepPoints(
          vote.ideaId,
          TaskParticipantIterationStepStatesType.NEUTRAL,
          {
            rating: vote.rating,
            detailRating: vote.detailRating,
            parameter: vote.parameter,
          },
          5,
          null,
          true,
          false,
          () => true
        );
      } else {
        await this.trackingManager.saveIterationStep(
          {
            rating: vote.rating,
            detailRating: vote.detailRating,
            parameter: vote.parameter,
          },
          TaskParticipantIterationStepStatesType.NEUTRAL,
          null,
          5,
          true,
          null,
          false,
          () => true
        );
      }
      if (this.finished) {
        await this.trackingManager.saveIteration(
          null,
          TaskParticipantIterationStatesType.PARTICIPATED,
          null,
          true
        );
        await this.trackingManager.saveState(
          {
            voteCount: this.votes.length,
          },
          TaskParticipantStatesType.FINISHED
        );
      }
    }
  }

  showIdeaOverlay = false;
  voteIdOverlay: Idea | null = null;
  currentRateIdea = false;

  getOpacity(): number {
    const number = this.ideaPointer + (this.initIdeaNumber - this.ideas.length);
    const opacityNum = (1 / this.initIdeaNumber) * number;
    if (opacityNum == 1 && this.ideaPointer == 0) {
      return opacityNum;
    } else if (opacityNum != 1) {
      return opacityNum;
    } else {
      return 0;
    }
  }
}
</script>

<style lang="scss" scoped>
.media-left {
  width: 50%;
}

.media-content {
  align-self: center;
  text-align: center;
}

.el-divider {
  background-color: var(--color-primary);
  height: 2px;
}

.el-rate {
  --el-rate-font-size: 2rem;
  --el-rate-icon-size: 2rem;
  height: unset;
}

@media only screen and (max-width: 400px) {
  .media {
    display: block;
  }
  .media-left {
    width: unset;
  }
}

.media {
  display: flex;
  flex-direction: column;
}

div#loadingScreen {
  position: absolute;
  width: 100%;
  height: 100%;

  max-width: 760px;

  bottom: 0;
  left: 0;
  right: 0;
  margin-left: auto;
  margin-right: auto;

  background-color: var(--color-dark-contrast);

  display: flex;
  justify-items: center;
  align-items: center;
  flex-direction: column;

  opacity: 1;
  z-index: 2;
}

div#loadingScreen > span {
  width: 70%;
  text-align: center;
  color: white;
  font-size: var(--font-size-large);
  position: relative;
  margin: auto auto 0;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
}

div#loadingScreen > span#loading {
  margin-top: 50px;
  margin-bottom: auto;
}

div#loadingScreen > span#loading::v-deep(.path) {
  stroke: white;
  stroke-width: 4;
}

@keyframes preloadSprites {
  /*Sprite changes (couldn't find a way to loop keyframes within animation)*/
  0% {
    background-image: url('@/assets/illustrations/Voting/starDesat.png');
  }
  10% {
    background-image: url('@/assets/illustrations/Voting/starGlow.png');
  }
  20% {
    background-image: url('@/assets/illustrations/Voting/StarsSpace.png');
  }
}

.PreloadSprites {
  animation-name: preloadSprites;
  animation-duration: 0.5s;
  animation-iteration-count: 1;
}

.zeroOpacity {
  opacity: 0 !important;
  transition: 2s;
}

.hidden {
  display: none !important;
}

#starImageBackground {
  max-width: inherit;
  padding-bottom: 2rem;

  left: 0;
  right: 0;

  background-image: url('@/assets/illustrations/Voting/StarsSpace.png');
  background-size: contain;

  z-index: 0;
}

#starImageContainer {
  position: relative;
  height: 30vh;

  top: 0;
  left: 0;
  right: 0;
  margin-left: auto;
  margin-right: auto;
}

.starImage {
  position: absolute;
  height: 30vh;
  align-self: center;

  left: 0;
  right: 0;
  margin-left: auto;
  margin-right: auto;
}

.starImage#glowingStar {
  transition: 2s;
  opacity: var(--star-opacity);
}

#glowingStar.lightUp {
  animation-name: takeoffSprite;
  animation-duration: 5s;
  animation-timing-function: ease-in-out;
  animation-iteration-count: 1;
  animation-direction: normal;
}

@keyframes lightUpKeyframes {
  0% {
    opacity: 0;
  }
  100% {
    opacity: var(--star-opacity);
  }
}

.IdeaCard {
  width: 100%;
  background-color: var(--color-dark-contrast);
  border: 3px solid var(--color-dark-contrast-light);
  padding: 3%;
}

.IdeaCard::v-deep(.el-card__body) {
  display: flex;
  flex-direction: row;

  align-items: center;

  height: 10%;

  border-radius: 10px;
  color: white;
}

.IdeaCard::v-deep(.card__image) {
  height: 26%;
  width: 26%;
  border-radius: 20px;

  background-color: var(--color-gray);
}

.IdeaCard::v-deep(.card__text) {
  padding: 1% 1% 1% 3%;
  height: 98%;
  /* Hide scrollbar for IE, Edge and Firefox */
  -ms-overflow-style: none; /* IE and Edge */
  scrollbar-width: none; /* Firefox */
}

/* Hide scrollbar for Chrome, Safari and Opera */
.IdeaCard::v-deep(.card__text)::-webkit-scrollbar {
  display: none;
}

/* Hide scrollbar for Chrome, Safari and Opera */
.IdeaCard::-webkit-scrollbar {
  display: none;
}

.el-rate.ratingStars {
  display: flex;

  justify-content: center;

  width: 100%;
}

.el-rate.ratingStars::v-deep(path) {
  color: var(--color-evaluating);
}

.finished {
  text-align: center;
}
</style>
