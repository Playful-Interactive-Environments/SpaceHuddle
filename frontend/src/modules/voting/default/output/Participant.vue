<template>
  <div id="loadingScreen">
    <span>{{ $t('module.voting.default.participant.waiting') }}...</span>
    <span
      id="loading"
      v-loading="true"
      element-loading-background="rgba(0, 0, 0, 0)"
    ></span>
  </div>
  <div id="starImageBackground">
    <div id="starImageContainer">
      <img
        src="../../../../assets/illustrations/Voting/starDesat.png"
        alt="star"
        class="starImage"
      />
      <img
        src="../../../../assets/illustrations/Voting/starGlow.png"
        alt="star"
        class="starImage"
        id="glowingStar"
        :style="{ '--star-opacity': getOpacity() }"
      />
    </div>
  </div>

  <ParticipantModuleDefaultContainer
    :task-id="taskId"
    :module="moduleName"
    id="PMDC"
  >
    <div id="preloader"></div>
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

    <div v-if="finished">
      {{ $t('module.voting.default.participant.thanks') }}
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

    <div id="ideaAndSkipOverlay" v-if="showIdeaOverlay">
      <div id="backgroundOfOverlay" v-on:click="showIdeaOverlay = false"></div>
      <span class="ideaCardSpan">
        <font-awesome-icon
          icon="plus"
          class="x"
          v-on:click="showIdeaOverlay = false"
        />

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
        <span id="clickOut" v-on:click="showIdeaOverlay = false"></span>
      </span>
    </div>
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
  task: Task | null = null;
  module: Module | null = null;
  ideas: Idea[] = [];
  allIdeas: Idea[] = [];
  votes: Vote[] = [];
  ideaPointer = 0;
  rate = 0;
  maxRate = 5;
  readonly intervalTime = 10000;
  interval!: any;
  starOpacity = 0;
  initIdeaNumber = 0;

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  get finished(): boolean {
    return this.validVotes.length > 0 && this.ideaPointer >= this.ideas.length;
  }

  get waiting(): boolean {
    if (this.allIdeas.length != 0) {
      let element = document.getElementById('loadingScreen');

      if (element != null && !element.classList.contains('zeroOpacity')) {
        var preload = document.getElementById('preloader');
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
    this.startInterval();
    this.waiting;
  }

  startInterval(): void {
    this.interval = setInterval(this.reloadIdeas, this.intervalTime);
  }

  voteIdea(ideaId: string | null): Idea | undefined {
    return this.allIdeas.find((idea) => idea.id === ideaId);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }

  reloadIdeas(): void {
    if (this.finished) this.getIdeas();
  }

  initConfig(count: number): void {
    this.maxRate = count;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getIdeas();
  }

  async getTask(): Promise<void> {
    if (this.taskId) {
      await taskService
        .getTaskById(this.taskId, EndpointAuthorisationType.PARTICIPANT)
        .then((task) => {
          this.task = task;
        });
    }
  }

  @Watch('moduleId', { immediate: true })
  onModuleIdChanged(): void {
    this.getModule();
  }

  async getModule(): Promise<void> {
    if (this.moduleId) {
      await moduleService
        .getModuleById(this.moduleId, EndpointAuthorisationType.PARTICIPANT)
        .then((module) => {
          this.module = module;
          if (this.maxRate != parseInt(this.module.parameter.maxRate))
            this.getVotes();
        });
    }
  }

  async getVotes(): Promise<void> {
    if (this.taskId) {
      if (this.module) this.initConfig(parseInt(this.module.parameter.maxRate));
      else this.initConfig(5);
      await votingService
        .getVotes(this.taskId, EndpointAuthorisationType.PARTICIPANT)
        .then((votes) => {
          this.votes = votes;
          votes.forEach((vote) => {
            const ideaIndex = this.ideas.findIndex(
              (idea) => idea.id == vote.ideaId
            );
            if (ideaIndex >= 0) this.ideas.splice(ideaIndex, 1);
          });
          this.ideaPointer = 0;
        });
    }
  }

  async getIdeas(): Promise<void> {
    if (this.taskId) {
      if (!this.task) await this.getTask();
      if (!this.module) await this.getModule();
      if (this.task && this.task.parameter.input) {
        await viewService
          .getViewIdeas(
            this.task.topicId,
            this.task.parameter.input,
            null,
            null,
            EndpointAuthorisationType.PARTICIPANT,
            (this as any).$t
          )
          .then((ideas) => {
            this.allIdeas = [...ideas];
            this.ideaPointer = ideas.length;
            this.ideas = ideas;
            this.initIdeaNumber = ideas.length;
            this.getVotes();
          });
      }
    }
  }

  scrollToTop(delay = 100): void {
    setTimeout(() => {
      window.scroll(0, 0);
    }, delay);
  }

  async saveVoting(rate: number, vote: Vote | null = null): Promise<void> {
    setTimeout(() => {
      if (!vote) {
        if (this.ideaPointer < this.ideas.length) {
          const idea = this.ideas[this.ideaPointer];
          votingService
            .postVote(this.taskId, {
              ideaId: idea.id,
              rating: rate,
              detailRating: rate,
            })
            .then((vote) => {
              this.votes.push(vote);
            });
          this.rate = 0;
          this.ideaPointer++;
          this.scrollToTop(0);
        }
      } else {
        vote.rating = rate;
        vote.detailRating = rate;
        votingService.putVote(vote);
      }
    }, 1000);
  }

  showIdeaOverlay = false;
  voteIdOverlay: Idea | null = null;
  currentRateIdea = false;

  getOpacity(): number {
    let number = this.ideaPointer + (this.initIdeaNumber - this.ideas.length);
    let opacityNum = (1 / this.initIdeaNumber) * number;
    if (opacityNum == 1 && this.ideaPointer == 0) {
      return opacityNum;
    } else if (opacityNum != 1) {
      console.log(this.initIdeaNumber + ', ' + number + ', ' + opacityNum);
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

#PMDC {
  border-radius: 30px 30px 0 0;
  position: absolute;
  top: 30%;
  min-height: 70%;

  left: 0;
  right: 0;
  margin-left: auto;
  margin-right: auto;

  z-index: 1;
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

  background-color: var(--color-darkblue);

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

div#loadingScreen > span#loading::v-deep .path {
  stroke: white;
  stroke-width: 4;
}

@keyframes preloadSprites {
  /*Sprite changes (couldn't find a way to loop keyframes within animation)*/
  0% {
    background-image: url('../../../../assets/illustrations/Voting/starDesat.png');
  }
  10% {
    background-image: url('../../../../assets/illustrations/Voting/starGlow.png');
  }
  20% {
    background-image: url('../../../../assets/illustrations/Voting/StarsSpace.png');
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
  position: absolute;

  max-width: inherit;
  height: 100%;

  left: 0;
  right: 0;
  margin-left: auto;
  margin-right: auto;

  background-image: url('../../../../assets/illustrations/Voting/StarsSpace.png');
  background-size: contain;

  z-index: 0;
}

#starImageContainer {
  position: absolute;

  height: 23%;

  top: 0;
  left: 0;
  right: 0;
  margin-left: auto;
  margin-right: auto;
}

.starImage {
  position: absolute;
  height: 100%;

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
  background-color: var(--color-darkblue);
  border: 3px solid var(--color-darkblue-light);
  padding: 3%;
}

.IdeaCard::v-deep .el-card__body {
  display: flex;
  flex-direction: row;

  align-items: center;

  height: 10%;

  border-radius: 10px;
  color: white;
}

.IdeaCard::v-deep .card__image {
  height: 26%;
  width: 26%;
  border-radius: 20px;

  background-color: var(--color-gray);
}

.IdeaCard::v-deep .card__text {
  padding: 1% 1% 1% 3%;
  height: 98%;
  /* Hide scrollbar for IE, Edge and Firefox */
  -ms-overflow-style: none; /* IE and Edge */
  scrollbar-width: none; /* Firefox */
}

/* Hide scrollbar for Chrome, Safari and Opera */
.IdeaCard::v-deep .card__text::-webkit-scrollbar {
  display: none;
}

/* Hide scrollbar for Chrome, Safari and Opera */
.IdeaCard::-webkit-scrollbar {
  display: none;
}

div#ideaAndSkipOverlay {
  position: fixed;

  overflow: hidden;

  top: 0;
  left: 0;
  right: 0;
  margin-left: auto;
  margin-right: auto;

  height: 100%;
  max-width: 760px;
}

div#ideaAndSkipOverlay #backgroundOfOverlay {
  position: absolute;

  top: 0;
  left: 0;
  right: 0;
  margin-left: auto;
  margin-right: auto;

  height: 100%;
  width: 100%;

  background-color: #00000050;
  z-index: 7;
}

div#ideaAndSkipOverlay .ideaCardSpan {
  position: absolute;

  width: 95%;
  max-height: 70%;
  min-height: 40%;

  z-index: 7;
  margin: auto;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;

  background-color: transparent;
}

div#ideaAndSkipOverlay .ideaCardOverlay {
  position: absolute;

  overflow-y: scroll;

  width: 100%;
  height: auto;
  min-height: 40%;
  max-height: 100%;

  padding: 2%;
  margin-top: 0;

  box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.25);
  border: 3px solid var(--color-darkblue);
}
#ideaAndSkipOverlay::v-deep .card__image {
  border-radius: 20px;
}

div#ideaAndSkipOverlay .x {
  position: absolute;
  width: 10%;
  height: 10%;

  top: -8%;
  right: 0;

  color: white;

  transform: rotate(45deg);

  z-index: 8;
  background-color: transparent;
}

span#clickOut {
  display: block;
  width: 100%;
  height: 100%;
}

.el-rate.ratingStars {
  display: flex;

  justify-content: center;

  width: 100%;
}

.el-rate.ratingStars::v-deep path {
  color: var(--color-red);
}
</style>
