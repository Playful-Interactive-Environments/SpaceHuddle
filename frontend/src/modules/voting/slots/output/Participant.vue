<template>
  <ParticipantModuleDefaultContainer
    :task-id="taskId"
    :module="moduleName"
    id="Container"
  >
    <div id="preloader"></div>
    <div id="loadingScreen" :class="{ hidden: loadingScreenEnd }">
      <span>{{ $t('module.voting.slots.participant.waiting') }}...</span>
      <span
        id="loading"
        v-loading="true"
        element-loading-background="rgba(0, 0, 0, 0)"
      ></span>
    </div>
    <div id="backgroundImage"></div>
    <div
      id="rocketMask"
      :class="{ rocketMaskSmall: heightCheck() || smallHeight }"
    >
      <div
        id="rocketColumn"
        v-on:scroll="getHeightPercentage()"
        :class="{
          rocketColBig: seats.length <= 3 && enoughHeight,
          blockScrolling: !enoughHeight || smallHeight,
          hidden: ElementHidden,
        }"
        v-on:animationend="ElementHidden = true"
      >
        <div
          v-for="(idea, index) in seats"
          :key="index"
          class="rocketDiv"
          :class="{ rocketDivBig: seats.length <= 3 && enoughHeight }"
        >
          <div
            :class="{
              rocketBotImg: index === 0,
              rocketMidImg: index > 0 && index < seats.length - 1,
              rocketTopImg: index === seats.length - 1,
            }"
            class="rocketImg"
          ></div>
          <el-button
            @click.prevent="vote(index + 1)"
            class="rocketWindow"
            :class="{
              rocketBotWindow: index === 0,
              rocketMidWindow: index > 0 && index < seats.length - 1,
              rocketTopWindow: index === seats.length - 1,
              rocketWindowAstronaut: idea,
            }"
          >
            <span
              v-if="idea"
              class="idea"
              :class="{ ideaBig: seats.length <= 3 && enoughHeight }"
            >
              <span style="color: var(--color-evaluating)">{{
                $t('module.voting.slots.participant.replace')
              }}</span>
              "{{ idea.keywords }}"
            </span>
            <span
              v-if="!idea"
              class="idea, emptyIdea"
              :class="{ ideaBig: seats.length <= 3 && enoughHeight }"
            >
              <font-awesome-icon icon="angle-double-left" />
              {{ $t('module.voting.slots.participant.emptySlot') }}
              <font-awesome-icon icon="angle-double-right" />
            </span>
          </el-button>
        </div>
        <img
          id="fire"
          src=""
          alt="fire"
          v-on:animationend="ElementHidden = true"
          :class="{
            hidden: ElementHidden,
            FireBig: seats.length <= 3 && enoughHeight,
          }"
        />
      </div>

      <div
        id="Platform"
        :class="{ PlatformBig: seats.length <= 3 && enoughHeight }"
      >
        <img id="backPlatform" src="" alt="platform" />
        <img id="frontPlatform" src="" alt="platform" />
        <img id="extendPlatform" src="" alt="platform" />
      </div>
    </div>

    <div id="ideaAndSkip">
      <el-button
        type="warning"
        nativeType="submit"
        class="skipButton"
        @click.prevent="vote(0)"
        v-if="ideaPointer < ideas.length"
      >
        {{ $t('module.voting.slots.participant.skip') }}
      </el-button>
      <span v-if="ideaPointer < ideas.length">
        <IdeaCard
          :idea="ideas[ideaPointer]"
          :is-selectable="false"
          :is-editable="false"
          :show-state="false"
          class="ideaCard"
          v-on:click="showIdeaOverlay = true"
        />
      </span>
      <span id="endOfIdeas" v-if="ideaPointer >= ideas.length">
        <span v-if="allSlotsFilled()">
          {{ $t('module.voting.slots.participant.again') }}
        </span>
        <span v-if="!allSlotsFilled()">
          {{ $t('module.voting.slots.participant.emptySlop') }}
        </span>
        <span id="endOfIdeasButtons">
          <el-button
            class="endButtons"
            id="yes"
            v-on:click="replaceIdeaArray()"
            v-if="allSlotsFilled()"
          >
            {{ $t('module.voting.slots.participant.yes') }}
          </el-button>
          <el-button
            class="endButtons"
            id="launch"
            v-on:click="scrollRocketToBottom()"
            v-if="allSlotsFilled()"
          >
            {{ $t('module.voting.slots.participant.no') }}
          </el-button>

          <el-button
            class="endButtons"
            id="notAllSlotsButton"
            v-on:click="replaceIdeaArray()"
            v-if="!allSlotsFilled()"
          >
            {{ $t('module.voting.slots.participant.cycle') }}
          </el-button>
        </span>
      </span>
    </div>

    <div id="ideaAndSkipOverlay" v-if="showIdeaOverlay">
      <div id="backgroundOfOverlay" v-on:click="showIdeaOverlay = false"></div>
      <span v-if="ideaPointer < ideas.length" class="ideaCardSpan">
        <font-awesome-icon
          icon="plus"
          class="x"
          v-on:click="showIdeaOverlay = false"
        />
        <IdeaCard
          :idea="ideas[ideaPointer]"
          :is-selectable="false"
          :is-editable="false"
          :show-state="false"
          class="ideaCard"
        />
        <span id="clickOut" v-on:click="showIdeaOverlay = false"></span>
      </span>
    </div>
    <div id="thanksText">
      <span>{{ $t('module.voting.slots.participant.thanks') }}</span>
    </div>
    <div v-if="waiting" style="display: none">
      <span>{{ $t('module.voting.slots.participant.waiting') }}</span>
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
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import * as cashService from '@/services/cash-service';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
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
  votes: Vote[] = [];
  seats: (Idea | null)[] = [];
  ideaPointer = 0;
  trackingManager!: TrackingManager;

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  get finished(): boolean {
    return (
      this.votes.length > 0 &&
      this.ideaPointer >= this.ideas.length &&
      this.finishedAndLaunched
    );
  }

  get waiting(): boolean {
    //Changes Paul Start
    if (this.ideas.length > 0 || this.votes.length > 0) {
      const element = document.getElementById('loadingScreen');
      if (element !== null && !element.classList.contains('zeroOpacity')) {
        this.replaceIdeaArray();

        const preload = document.getElementById('preloader');
        preload?.classList.add('PreloadSprites');

        setTimeout(() => preload?.classList.remove('PreloadSprites'), 1000);
        setTimeout(() => element?.classList.add('zeroOpacity'), 1000);
        setTimeout(() => element?.classList.add('hidden'), 3000);
      }
    }
    //Changes Paul End
    return this.ideas.length === 0 && this.votes.length === 0;
  }

  inputCash!: cashService.SimplifiedCashEntry<Idea[]>;
  votingCash!: cashService.SimplifiedCashEntry<Vote[]>;
  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    if (this.taskId) {
      this.trackingManager = new TrackingManager(this.taskId, {}, 100);
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

  mounted(): void {
    if (this.seats.length == 0) {
      this.initSeats(3);
    }
    //Changes Paul Start
    this.waiting;
    window.addEventListener('resize', this.heightCheck);
    //Changes Paul End
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateVotes);
    cashService.deregisterAllGet(this.updateModule);
    cashService.deregisterAllGet(this.updateInputIdeas);
  }

  unmounted(): void {
    this.deregisterAll();
    window.removeEventListener('resize', this.heightCheck);
  }

  initSeats(count: number): void {
    this.seats = [];
    for (let i = 0; i < count; i++) {
      this.seats.push(null);
    }
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
      if (vote.rating > 0) {
        this.seats[vote.rating - 1] = this.ideas.filter(
          (idea) => idea.id == vote.ideaId
        )[0];
      }
      const ideaIndex = this.ideas.findIndex((idea) => idea.id == vote.ideaId);
      if (ideaIndex >= 0) this.ideas.splice(ideaIndex, 1);
    });
    if (this.ideas.length === 0) {
      this.replaceIdeaArray();
    }
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
    this.ideaPointer = ideas.length;
    this.ideas = ideas;
    for (let i = 0; i < ideas.length; i++) {
      this.secondIdeaArray[i] = ideas[i];
    }
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
    if (this.seats.length !== this.module.parameter.slotCount) this.getVotes();
  }

  async getVotes(): Promise<void> {
    if (this.taskId) {
      if (this.module) this.initSeats(this.module.parameter.slotCount);
      else this.initSeats(3);
    }
  }

  async vote(slot: number): Promise<void> {
    if (this.ideaPointer < this.ideas.length) {
      const idea = this.ideas[this.ideaPointer];
      let vote = this.votes.find((vote) => vote.ideaId === idea.id);
      if (!vote) {
        vote = await votingService.postVote(this.taskId, {
          ideaId: idea.id,
          rating: slot,
          detailRating: slot > 0 ? 1 : 0,
        });
        this.votes.push(vote);
      } else {
        vote.rating = slot;
        vote.detailRating = slot > 0 ? 1 : 0;
        await votingService.putVote(vote);
      }
      if (slot > 0) {
        const seatIdea = this.seats[slot - 1];
        if (seatIdea && seatIdea.id !== idea.id) {
          const slotIdea = this.seats[slot - 1];
          if (slotIdea) {
            const slotVote = this.votes.filter(
              (vote) => vote.ideaId == slotIdea?.id
            )[0];
            await votingService.putVote({
              id: slotVote.id,
              ideaId: slotIdea.id,
              rating: 0,
              detailRating: 0,
            });
          }
        }
        this.seats[slot - 1] = idea;
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
      }
      this.ideaPointer++;
    }
  }

  //Changes Paul Start
  ElementHidden = false;

  showIdeaOverlay = false;

  enoughHeight = true;
  smallHeight = false;

  loadingScreenEnd = false;
  finishedAndLaunched = false;

  getHeightPercentage(): void {
    const viewportHeight = window.innerHeight;
    const ideas = document.getElementsByClassName('idea');

    if (this.enoughHeight) {
      for (let i = 0; i < ideas.length; i++) {
        const top = ideas[i].getBoundingClientRect().top;
        if (
          100 - (top / viewportHeight) * 100 < 22 ||
          (top / viewportHeight) * 100 < 27
        ) {
          ideas[i].classList.add('ideaShrink');
          ideas[i].classList.remove('ideaGrow');
        } else {
          ideas[i].classList.add('ideaGrow');
          ideas[i].classList.remove('ideaShrink');
        }
      }
    }
  }

  async scrollRocketToBottom(): Promise<void> {
    const rocket = document.getElementById('rocketColumn');
    const fire = document.getElementById('fire');
    this.finishedAndLaunched = true;
    if (rocket !== null && fire !== null && this.finished) {
      rocket.scrollTo({ top: rocket.scrollHeight, behavior: 'smooth' });
      rocket.classList.add('rocketAnimateMove');
      fire.classList.add('rocketAnimateSprite');
      const endCard = document.getElementById('endOfIdeas');
      if (endCard !== null) {
        endCard.classList.add('hidden');
      }
      const thanksText = document.getElementById('thanksText');
      if (thanksText !== null) {
        thanksText.classList.add('notHidden');
        setTimeout(() => thanksText?.classList.add('fullOpacity'), 2000);
      }
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

  heightCheck(): boolean {
    const element = document.getElementById('backgroundImage');

    if (element !== null) {
      this.smallHeight = element.scrollHeight * 0.9 < element.scrollWidth;
      this.enoughHeight = element.scrollHeight * 0.7 > element.scrollWidth;
    }
    return this.smallHeight;
  }

  secondIdeaArray: Idea[] = [];

  replaceIdeaArray(): void {
    this.ideas = [];
    this.ideaPointer = 0;

    if (!this.finishedAndLaunched) {
      let subtractIndex = 0;
      for (let i = 0; i < this.secondIdeaArray.length; i++) {
        let replace = true;
        for (let j = 0; j < this.seats.length; j++) {
          if (
            this.seats[j]?.keywords == this.secondIdeaArray[i].keywords &&
            this.seats[j]?.description == this.secondIdeaArray[i].description
          ) {
            replace = false;
            subtractIndex++;
          }
        }
        if (replace) {
          this.ideas[i - subtractIndex] = this.secondIdeaArray[i];
        }
      }
    }
  }

  allSlotsFilled(): boolean {
    for (let i = 0; i < this.seats.length; i++) {
      if (this.seats[i] == null) {
        return false;
      }
    }
    return true;
  }
  //Changes Paul End
}
</script>

<style lang="scss" scoped>
ParticipantModuleDefaultContainer {
  overflow: hidden;
  color: var(--color-gray);
}

div#loadingScreen {
  position: absolute;
  width: 100%;
  height: 100%;

  bottom: 0;
  left: 0;
  right: 0;
  margin-left: auto;
  margin-right: auto;

  background-color: var(--color-dark-contrast);
  z-index: 100;

  display: flex;
  justify-items: center;
  align-items: center;
  flex-direction: column;

  opacity: 1;
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

div#cache {
  position: absolute;
  z-index: -1000;
  opacity: 0;
}

div#cache image {
  position: absolute;
}

.zeroOpacity {
  opacity: 0 !important;
  transition: 2s;
}

.fullOpacity {
  opacity: 1 !important;
  transition: 2s;
}

.notHidden {
  display: inline !important;
}

#backgroundImage {
  position: absolute;
  width: 100%;
  height: 100%;

  bottom: 0;
  left: 0;
  right: 0;
  margin-left: auto;
  margin-right: auto;

  background-image: url('@/assets/illustrations/Slots/Background.png');
  background-size: 100%;
  background-position: bottom;
  background-repeat: no-repeat;

  mask-image: url('@/assets/illustrations/Slots/Mask.png');
  mask-size: contain;
  mask-repeat: repeat-x;
}

div#rocketMask {
  position: absolute;
  width: 100%;
  height: 100%;

  top: 0;
  left: 0;
  right: 0;
  margin-left: auto;
  margin-right: auto;

  mask-image: url('@/assets/illustrations/Slots/Mask.png');
  mask-size: contain;
  mask-repeat: repeat;

  overflow: hidden;

  transition: 1s;
}

div#rocketMask.rocketMaskSmall {
  width: 70%;
  left: 0;

  transition: 1s;
}

@keyframes takeoffSprite {
  /*Sprite changes (couldn't find a way to loop keyframes within animation)*/
  0% {
    content: url('@/assets/illustrations/Slots/fire/fire-none.png');
  }
  6% {
    content: url('@/assets/illustrations/Slots/fire/fire-launch-3.png');
  }
  7% {
    content: url('@/assets/illustrations/Slots/fire/fire-launch-3.png');
  }
  12% {
    content: url('@/assets/illustrations/Slots/fire/fire-launch-1.png');
  }
  18% {
    content: url('@/assets/illustrations/Slots/fire/fire-launch-1.png');
  }
  19% {
    content: url('@/assets/illustrations/Slots/fire/fire-launch-2.png');
  }
  24% {
    content: url('@/assets/illustrations/Slots/fire/fire-launch-2.png');
  }
  25% {
    content: url('@/assets/illustrations/Slots/fire/fire-launch-3.png');
  }
  30% {
    content: url('@/assets/illustrations/Slots/fire/fire-launch-3.png');
  }
  31% {
    content: url('@/assets/illustrations/Slots/fire/fire-3.png');
  }
  36% {
    content: url('@/assets/illustrations/Slots/fire/fire-3.png');
  }

  37% {
    content: url('@/assets/illustrations/Slots/fire/fire-1.png');
  }
  42% {
    content: url('@/assets/illustrations/Slots/fire/fire-1.png');
  }
  43% {
    content: url('@/assets/illustrations/Slots/fire/fire-2.png');
  }
  48% {
    content: url('@/assets/illustrations/Slots/fire/fire-2.png');
  }
  49% {
    content: url('@/assets/illustrations/Slots/fire/fire-3.png');
  }
  54% {
    content: url('@/assets/illustrations/Slots/fire/fire-3.png');
  }

  55% {
    content: url('@/assets/illustrations/Slots/fire/fire-1.png');
  }
  60% {
    content: url('@/assets/illustrations/Slots/fire/fire-1.png');
  }
  61% {
    content: url('@/assets/illustrations/Slots/fire/fire-2.png');
  }
  66% {
    content: url('@/assets/illustrations/Slots/fire/fire-2.png');
  }
  72% {
    content: url('@/assets/illustrations/Slots/fire/fire-3.png');
  }
  73% {
    content: url('@/assets/illustrations/Slots/fire/fire-3.png');
  }

  78% {
    content: url('@/assets/illustrations/Slots/fire/fire-1.png');
  }
  79% {
    content: url('@/assets/illustrations/Slots/fire/fire-1.png');
  }
  84% {
    content: url('@/assets/illustrations/Slots/fire/fire-2.png');
  }
  85% {
    content: url('@/assets/illustrations/Slots/fire/fire-2.png');
  }
  90% {
    content: url('@/assets/illustrations/Slots/fire/fire-3.png');
  }
  91% {
    content: url('@/assets/illustrations/Slots/fire/fire-3.png');
  }

  96% {
    content: url('@/assets/illustrations/Slots/fire/fire-1.png');
  }
  97% {
    content: url('@/assets/illustrations/Slots/fire/fire-1.png');
  }
}
@keyframes takeoffMove {
  0% {
    bottom: 12%;
    left: 0.1%;
    right: 0;
  }
  2% {
    bottom: 12%;
    right: 0.2%;
    left: 0;
  }
  4% {
    bottom: 12%;
    left: 0.4%;
    right: 0;
  }
  6% {
    bottom: 12%;
    right: 0.6%;
    left: 0;
  }
  8% {
    bottom: 12%;
    left: 0.9%;
    right: 0;
  }
  10% {
    bottom: 12%;
    right: 1%;
    left: 0;
  }
  12% {
    bottom: 12%;
    left: 1%;
    right: 0;
  }
  14% {
    bottom: 12%;
    right: 1%;
    left: 0;
  }
  16% {
    bottom: 12%;
    left: 1%;
    right: 0;
  }
  18% {
    bottom: 12%;
    right: 1%;
    left: 0;
  }

  20% {
    left: 1%;
    right: 0;
  }
  22% {
    right: 1%;
    left: 0;
  }
  24% {
    left: 0.9%;
    right: 0;
  }
  26% {
    right: 0.8%;
    left: 0;
  }
  28% {
    left: 0.7%;
    right: 0;
  }
  30% {
    right: 0.6%;
    left: 0;
    overflow: visible;
  }
  32% {
    left: 0.5%;
    right: 0;
  }
  34% {
    right: 0.5%;
    left: 0;
  }
  36% {
    left: 0.4%;
    right: 0;
  }
  38% {
    right: 0.4%;
    left: 0;
  }
  40% {
    left: 0.3%;
    right: 0;
  }
  42% {
    right: 0.3%;
    left: 0;
  }
  44% {
    left: 0.3%;
    right: 0;
  }
  46% {
    right: 0.2%;
    left: 0;
  }
  48% {
    left: 0.2%;
    right: 0;
  }
  50% {
    right: 0.2%;
    left: 0;
  }
  52% {
    left: 0.1%;
    right: 0;
  }
  54% {
    right: 0.1%;
    left: 0;
  }
  56% {
    left: 0;
    right: 0;
  }
  100% {
    bottom: 110%;
    overflow: visible;
  }
}

@keyframes preloadSprites {
  /*Sprite changes (couldn't find a way to loop keyframes within animation)*/
  0% {
    background-image: url('@/assets/illustrations/Slots/fire/fire-none.png');
  }
  10% {
    background-image: url('@/assets/illustrations/Slots/fire/fire-launch-1.png');
  }
  20% {
    background-image: url('@/assets/illustrations/Slots/fire/fire-launch-2.png');
  }
  30% {
    background-image: url('@/assets/illustrations/Slots/fire/fire-launch-3.png');
  }
  40% {
    background-image: url('@/assets/illustrations/Slots/fire/fire-1.png');
  }
  50% {
    background-image: url('@/assets/illustrations/Slots/fire/fire-2.png');
  }
  60% {
    background-image: url('@/assets/illustrations/Slots/fire/fire-3.png');
  }
  70% {
    background-image: url('@/assets/illustrations/Slots/Rocket parts/Window Astronaut.png');
  }
}

.PreloadSprites {
  animation-name: preloadSprites;
  animation-duration: 0.5s;
  animation-iteration-count: 1;
}

.rocketAnimateSprite {
  animation-name: takeoffSprite;
  animation-duration: 5s;
  animation-timing-function: ease-in-out;
  animation-iteration-count: 1;
  animation-direction: normal;
}

.hidden {
  display: none !important;
}

.rocketAnimateMove {
  animation-name: takeoffMove;
  animation-duration: 5s;
  animation-timing-function: ease-in-out;
  animation-iteration-count: 1;
  animation-direction: normal;
}

div#rocketColumn {
  overflow-y: scroll;
  position: absolute;
  width: 100%;
  height: 100%;

  bottom: 12%;
  left: 0;
  right: 0;
  margin-left: auto;
  margin-right: auto;

  display: flex;
  flex-direction: column-reverse;

  align-items: flex-start;

  padding: 80% 0 10% 3%;

  /* Hide scrollbar for IE, Edge and Firefox */
  -ms-overflow-style: none; /* IE and Edge */
  scrollbar-width: none; /* Firefox */

  transition: 1s;
}

/* Hide scrollbar for Chrome, Safari and Opera */
div#rocketColumn::-webkit-scrollbar {
  display: none;
}

img#fire {
  position: absolute;
  bottom: -4.5%;

  left: 8%;
  margin-left: auto;
  margin-right: auto;

  height: auto;
  width: 28%;
  content: url('@/assets/illustrations/Slots/fire/fire-none.png');
  z-index: 1;

  transition: 1s;
}

img#fire.FireBig {
  bottom: -7%;
  width: 35%;

  transition: 1s;
}

.rocketDiv {
  width: 40%;
  height: auto;
  position: relative;

  transition: 1s;
}

.rocketDivBig {
  width: 50%;

  transition: 1s;
}

div#rocketColumn.rocketColBig {
  overflow: hidden;
  padding: 80% 0 8% 0.5%;
  transition: 1s;
}

div#rocketColumn.blockScrolling {
  overflow: hidden;
  transition: 1s;
}

.rocketImg {
  left: 0;
  right: 0;
  margin-left: auto;
  margin-right: auto;

  height: 100%;
  width: 100%;

  position: relative;

  z-index: 2;
}

.rocketTopImg {
  content: url('@/assets/illustrations/Slots/Rocket parts/Top.png');
}
.rocketMidImg {
  content: url('@/assets/illustrations/Slots/Rocket parts/Mid.png');
}
.rocketBotImg {
  content: url('@/assets/illustrations/Slots/Rocket parts/Bot.png');
}

.el-button.rocketWindow {
  position: absolute;
  left: 0;
  right: 0;

  width: 35%;

  margin-left: auto;
  margin-right: auto;

  background-color: transparent;
  background-size: contain;
  background-position: center;
  background-repeat: no-repeat;
  background-image: url('@/assets/illustrations/Slots/Rocket parts/Window.png');

  z-index: 3;
}

.el-button.rocketWindowAstronaut {
  background-image: url('@/assets/illustrations/Slots/Rocket parts/Window Astronaut.png');
  transition: 0.5s;
}

.el-button.rocketMidWindow {
  bottom: 3%;
  height: 70%;
}
.el-button.rocketTopWindow {
  bottom: 2%;
  height: 50%;
}
.el-button.rocketBotWindow {
  top: 12%;
  height: 41%;
}

div#Platform {
  position: absolute;
  left: -5%;
  bottom: 12%;
  width: 55%;
  height: auto;

  transition: 1s;
}

div#Platform.PlatformBig {
  left: -7%;
  width: 62%;

  transition: 1s;
}

img#frontPlatform {
  position: absolute;
  bottom: 1.5%;
  content: url('@/assets/illustrations/Slots/PlatformFront.png');
  z-index: 4;
}
img#backPlatform {
  position: absolute;
  bottom: 1.4%;
  content: url('@/assets/illustrations/Slots/PlatformBack.png');

  z-index: 0;
}
img#extendPlatform {
  position: absolute;
  content: url('@/assets/illustrations/Slots/PlatformExtension.png');

  z-index: 0;
}

span.idea,
span.emptyIdea {
  display: table;
  position: absolute;

  width: 480%;
  height: auto;
  text-align: left;
  white-space: normal;
  //word-break: break-all;

  left: 80%;
  right: 0;
  margin-left: auto;
  margin-right: auto;

  padding: 15% 15%;

  background-color: white;
  border-radius: 20px;
}

span.idea.ideaBig,
span.emptyIdea.ideaBig {
  width: 385% !important;
}

span.ideaShrink {
  transform: scale(0);
  opacity: 0;
  transition: 0.1s;
}

span.ideaGrow {
  transform: scale(1);
  opacity: 100;
  transition: 0.1s;
}

span#endOfIdeas {
  position: relative;
  display: flex;
  flex-direction: column;

  width: 100%;
  height: 70%;

  bottom: 0;

  color: white;
  text-align: center;

  justify-items: center;
  align-items: center;
  align-content: center;
  justify-content: center;

  z-index: 5;
  margin-top: 0;
  margin-bottom: 0;
  padding: 2%;

  box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.25);
  border: 3px solid white;
  background-color: var(--color-dark-contrast);

  border-radius: 10px;
}

span#endOfIdeas > span#endOfIdeasButtons {
  display: flex;
  flex-direction: row;

  text-align: center;

  width: 100%;
}

span#endOfIdeas > span {
  display: block;
  text-align: center;
  width: 100%;
}

.el-button.endButtons {
  width: 40%;
}

.el-button.endButtons#notAllSlotsButton {
  width: 95%;
}

.ideaCard {
  position: relative;
  width: 100%;
  max-height: 75%;

  bottom: 0;

  z-index: 5;
  margin-top: 0;
  margin-bottom: 0;
  padding: 2%;

  box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.25);
  border: 3px solid var(--color-dark-contrast);

  /* Hide scrollbar for IE, Edge and Firefox */
  -ms-overflow-style: none; /* IE and Edge */
  scrollbar-width: none; /* Firefox */
}

#ideaAndSkip::v-deep(.el-card__body) {
  display: flex;
  flex-direction: row;

  justify-items: center;

  height: 20%;
}

#ideaAndSkip::v-deep(.card__image) {
  height: 26%;
  width: 26%;
  border-radius: 20px;
  padding: 1% 1% 1% 0;
}

#ideaAndSkip::v-deep(.card__text) {
  padding: 1% 1% 1% 3%;
  height: 98%;
  /* Hide scrollbar for IE, Edge and Firefox */
  -ms-overflow-style: none; /* IE and Edge */
  scrollbar-width: none; /* Firefox */
}

/* Hide scrollbar for Chrome, Safari and Opera */
#ideaAndSkip::v-deep(.card__text)::-webkit-scrollbar {
  display: none;
}

/* Hide scrollbar for Chrome, Safari and Opera */
.ideaCard::-webkit-scrollbar {
  display: none;
}

.el-button.skipButton {
  width: 30%;
  font-size: var(--font-size-small);

  box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.25);
  padding: 0;
  z-index: 5;
}

div#ideaAndSkip {
  position: absolute;
  width: 94%;
  height: 22%;

  bottom: 2%;

  left: 0;
  right: 0;
  margin-left: auto;
  margin-right: auto;
}

div#ideaAndSkipOverlay {
  position: absolute;

  overflow: hidden;

  top: 0;
  left: 0;
  right: 0;
  margin-left: auto;
  margin-right: auto;

  height: 100%;
  width: 100%;
  z-index: 7;
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

div#ideaAndSkipOverlay .ideaCard {
  position: absolute;

  overflow-y: scroll;

  width: 100%;
  height: auto;
  min-height: 40%;
  max-height: 100%;

  padding: 2%;
  margin-top: 0;

  box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.25);
  border: 3px solid var(--color-dark-contrast);
}
#ideaAndSkipOverlay::v-deep(.card__image) {
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

div#thanksText {
  position: absolute;

  width: 50%;
  height: 100%;

  margin: auto;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;

  opacity: 0;
  display: none;
}

div#thanksText > span {
  position: absolute;

  text-align: center;

  font-size: var(--font-size-large);
  color: white;

  width: 100%;
  height: 10%;

  margin: auto;
  top: 0;
  bottom: 0;

  left: 0;
  right: 0;
}
</style>
