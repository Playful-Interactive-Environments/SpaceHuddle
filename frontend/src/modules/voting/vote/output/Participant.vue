<template>
  <ParticipantModuleDefaultContainer
    :task-id="taskId"
    :module="moduleName"
    :module-theme="theme"
    :class="{ PMDC: hasImage }"
  >
    <div id="preloader"></div>
    <div id="loadingScreen">
      <span>{{ $t('module.voting.vote.participant.waiting') }}...</span>
      <span
        id="loading"
        v-loading="true"
        element-loading-background="rgba(0, 0, 0, 0)"
      ></span>
    </div>
    <template #footer>
      <span class="previousNext">
        <el-button
          type="primary"
          class="el-button--submit"
          native-type="submit"
          :disabled="!answerId"
          @click="submit"
          v-if="!submitScreen"
        >
          {{ $t('module.voting.vote.participant.submit') }}
        </el-button>
      </span>
    </template>
    <el-space direction="vertical" class="fill votables" v-if="!submitScreen">
      <div v-for="idea in allIdeas" :key="idea.id" class="votable">
        <el-button
          :disabled="false"
          v-on:click="changeVote(idea.id)"
          class="circleCheck"
        >
          <template #icon>
            <font-awesome-icon
              v-if="isAnswerSelected(idea.id)"
              icon="circle-check"
              class="circleCheckIcon"
            />
            <font-awesome-icon
              v-else
              :icon="['far', 'circle']"
              class="circleCheckIcon"
            />
          </template>
        </el-button>
        <IdeaCard
          class="media-left, IdeaCard"
          :idea="idea"
          :is-selectable="false"
          :is-editable="false"
          :show-state="false"
          :portrait="isMobile"
        />
      </div>
    </el-space>
    <div id="submitScreen" v-if="submitScreen">
      <span>{{ $t('module.voting.vote.participant.thanksIndividual') }}</span>
      <br />
      <el-button
        type="primary"
        class="el-button--submit"
        native-type="submit"
        @click="$router.back()"
      >
        {{ $t('module.voting.vote.participant.back') }}
      </el-button>
    </div>
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/layout/ParticipantModuleDefaultContainer.vue';
import * as moduleService from '@/services/module-service';
import { Module } from '@/types/api/Module';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as votingService from '@/services/voting-service';
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';
import { QuestionnaireType } from '@/modules/information/quiz/types/QuestionnaireType';
import { QuestionType } from '@/modules/information/quiz/types/Question';
import { Hierarchy } from '@/types/api/Hierarchy';
import * as cashService from '@/services/cash-service';
import TaskParticipantStatesType from '@/types/enum/TaskParticipantStatesType';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import TaskParticipantIterationStatesType from '@/types/enum/TaskParticipantIterationStatesType';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import { Idea } from '@/types/api/Idea';
import * as viewService from '@/services/view-service';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import { ElMessageBox } from 'element-plus';
import EndpointType from '@/types/enum/EndpointType';

@Options({
  components: {
    IdeaCard,
    ParticipantModuleDefaultContainer,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  @Prop({ default: false }) readonly useFullSize!: boolean;
  @Prop({ default: '' }) readonly backgroundClass!: string;
  activeQuestion: Hierarchy | null = null;
  module: Module | null = null;
  task: Task | null = null;
  EndpointAuthorisationType = EndpointAuthorisationType;
  questionnaireType: QuestionnaireType = QuestionnaireType.QUIZ;
  moderatedQuestionFlow = true;
  score = 0;
  theme = '';
  answerId = '';

  ideas: Idea[] = [];
  allIdeas: Idea[] = [];

  trackingManager!: TrackingManager;
  inputCash!: cashService.SimplifiedCashEntry<Idea[]>;

  QuestionType = QuestionType;
  submitScreen = false;

  get isMobile(): boolean {
    return window.innerWidth < 600;
  }

  hasAnswer(): boolean {
    return this.hasQuestionAnCorrectAnswer(this.activeQuestion);
  }

  hasQuestionAnCorrectAnswer(question: Hierarchy | null): boolean {
    if (question && Object.hasOwn(question.parameter, 'hasAnswer'))
      return question.parameter.hasAnswer;
    return this.questionnaireType !== QuestionnaireType.SURVEY;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    if (this.taskId) {
      this.trackingManager = new TrackingManager(this.taskId, {}, true, 100);
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
  }

  inputIdeas: Idea[] = [];
  updateInputIdeas(ideas: Idea[]): void {
    this.inputIdeas = ideas;
    this.updateIdeas();
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
    this.ideas = ideas;
  }

  get loading(): boolean {
    const element = document.getElementById('loadingScreen');

    if (element && !element.classList.contains('zeroOpacity')) {
      const preload = document.getElementById('preloader');
      preload?.classList.add('PreloadSprites');

      setTimeout(() => preload?.classList.remove('PreloadSprites'), 1000);
      setTimeout(() => element?.classList.add('zeroOpacity'), 1000);
      setTimeout(() => element?.classList.add('hidden'), 3000);
      return true;
    }
    return false;
  }

  initData = true;
  mounted(): void {
    this.initData = true;
    this.loading;
  }

  get hasImage(): boolean {
    //check if the question has an image and return true or false
    return false;
  }

  isAnswerSelected(answerId: string): boolean {
    return answerId === this.answerId;
  }

  async changeVote(answerId: string): Promise<void> {
    this.answerId = answerId;
  }

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
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
    this.moderatedQuestionFlow = module.parameter.moderatedQuestionFlow;
    if (this.moderatedQuestionFlow) this.initData = false;
  }

  updateTask(task: Task): void {
    this.task = task;
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateModule);
    cashService.deregisterAllGet(this.updateTask);
  }

  async unmounted(): Promise<void> {
    if (!this.submitScreen && !!this.answerId) {
      let submit = false;
      await ElMessageBox.confirm(
        this.$t('module.voting.vote.participant.saveQuestion'),
        'Warning',
        {
          confirmButtonText: this.$t('module.voting.vote.participant.yes'),
          cancelButtonText: this.$t('module.voting.vote.participant.no'),
          type: 'warning',
        }
      )
        .then(() => {
          submit = true;
        })
        .catch(() => {
          submit = false;
        });
      if (submit) await this.submit();
    }
    this.deregisterAll();
    if (this.trackingManager) this.trackingManager.deregisterAll();
  }

  async submit() {
    this.submitScreen = true;
    const vote = await votingService.postVote(this.taskId, {
      ideaId: this.answerId,
      rating: 1,
      detailRating: 1,
    });
    if (this.trackingManager) {
      await this.trackingManager.createInstanceStepPoints(
        vote.ideaId,
        TaskParticipantIterationStepStatesType.NEUTRAL,
        {
          rating: vote.rating,
          detailRating: vote.detailRating,
          parameter: vote.parameter,
        },
        100,
        null,
        true,
        false,
        () => true
      );
      /*await this.trackingManager.saveIterationStep(
        {
          rating: vote.rating,
          detailRating: vote.detailRating,
          parameter: vote.parameter,
        },
        TaskParticipantIterationStepStatesType.NEUTRAL,
        null,
        100,
        true,
        null,
        false,
        () => true
      );*/
      await this.trackingManager.saveIteration(
        null,
        TaskParticipantIterationStatesType.PARTICIPATED,
        null,
        true
      );
      await this.trackingManager.saveState(
        {
          voteCount: 1,
        },
        TaskParticipantStatesType.FINISHED
      );
    }
    if (this.task)
      cashService.refreshCash(
        `/${EndpointType.SESSION}/${this.task.sessionId}/${EndpointType.PARTICIPANT_STATE}`
      );
  }
}
</script>

<style lang="scss" scoped>
.el-space::v-deep(.el-space__item) {
  width: 100%;
}

.el-footer {
  height: auto;
}

.module-content::v-deep(.question) {
  text-transform: none;
  font-weight: var(--font-weight-bold);
  font-size: var(--el-font-size-extra-large);
}

.explanation {
  width: 100%;
  text-align: justify;
  white-space: pre-line;
}

.previousNext {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}

.el-button {
  padding: 1rem 2rem;
  justify-content: left;
}

.PMDC {
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

#PMDC::v-deep(.el-steps) {
  margin-bottom: 3%;
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

.zeroOpacity {
  opacity: 0 !important;
  transition: 2s;
}

.hidden {
  display: none !important;
}

#QuizImageBackground {
  position: absolute;

  max-width: inherit;
  height: 80%;

  left: 0;
  right: 0;
  margin-left: auto;
  margin-right: auto;

  background-image: url('@/assets/illustrations/Voting/StarsSpace.png');
  background-size: contain;

  z-index: 0;
}

#QuizImageContainer {
  position: absolute;

  height: 23%;

  width: 60%;

  top: 3%;
  left: 0;
  right: 0;
  margin-left: auto;
  margin-right: auto;
}

.QuizImage {
  position: absolute;
  height: 100%;

  left: 0;
  right: 0;
  margin-left: auto;
  margin-right: auto;

  border-radius: 20px;
  border: 10px solid var(--color-dark-contrast-light);
}

.el-space::v-deep(.outline-thick):hover {
  background-color: var(--color-dark-contrast);
  border-color: var(--color-dark-contrast-light);
  color: white;
}

.outline-thick {
  border-color: var(--el-button-border-color);
  border-width: 2px;
  border-style: solid;
}

.el-space::v-deep(.link) > span {
  width: 100%;
  white-space: pre-line;
  overflow-wrap: anywhere;
  text-align: left;
  margin-left: 4%;

  img {
    background-color: white;
  }
}

.el-space::v-deep(.link) {
  height: auto;
  padding: 2% 5% 2% 5%;
}

.el-space::v-deep(.fa-circle-check) > path {
  fill: var(--color-informing);
}

.el-space::v-deep(.fa-circle) > path {
  fill: var(--color-dark-contrast-light);
}

#submitScreen {
  margin-top: 10%;
  text-align: center;
}

.el-button.submitScreenButton {
  width: 100%;
  text-align: center;
  display: flex;

  justify-content: center;
  justify-items: center;
  align-items: center;
  align-content: center;
}

#ScoreString {
  display: block;
  font-size: var(--font-size-xxxxlarge);
  font-weight: var(--font-weight-bold);
  margin-top: 2rem;
}

.el-button::v-deep(> span) {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
}

.question-image {
  overflow: unset;
}

.question-image::v-deep(img.el-image__preview) {
  height: 5rem;
  object-fit: contain;
  background-color: var(--color-primary);
  //margin: -0.8rem -2.1rem -0.8rem 0.5rem;
  //border-radius: 0 0.8rem 0.8rem 0;
  border-radius: 0.8rem;
  max-width: unset;
  width: unset;
}

label {
  font-weight: var(--font-weight-semibold);
}

.el-slider::v-deep(.el-slider__stop) {
  width: 0.1px;
}

.media + .media {
  padding-top: 1rem;
}

.ghost {
  background-color: var(--color-dark-contrast);
  color: white;
}

.votable {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: left;
  .IdeaCard {
    width: 100%;
  }
}

.circleCheck {
  font-size: var(--font-size-xxlarge);
  width: fit-content;
  padding: 0;
  background: transparent;
  border: none;
  margin-right: 0.5rem;
  margin-left: 0;
}
</style>
