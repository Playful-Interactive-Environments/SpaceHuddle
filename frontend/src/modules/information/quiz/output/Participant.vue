<template>
  <ParticipantModuleDefaultContainer
    :task-id="taskId"
    :module="moduleName"
    :module-theme="theme"
    :class="{ PMDC: hasImage }"
    :showLoadingState="showSavingState || !activeQuestionLoaded"
  >
    <div id="preloader"></div>
    <div id="loadingScreen">
      <span>{{ $t('module.information.quiz.participant.waiting') }}...</span>
      <span
        id="loading"
        v-loading="true"
        element-loading-background="rgba(0, 0, 0, 0)"
      ></span>
    </div>
    <div id="QuizImageBackground" v-if="hasImage">
      <div id="QuizImageContainer">
        <el-image
          :src="getImageSrc"
          alt="quizImage"
          class="QuizImage"
          :preview-src-list="[getImageSrc]"
          :hide-on-click-modal="true"
        />
      </div>
    </div>
    <template #footer>
      <span class="previousNext">
        <el-button
          type="primary"
          class="el-button--submit"
          native-type="submit"
          :disabled="!hasPreviousQuestion"
          v-if="!moderatedQuestionFlow && !submitScreen"
          @click="goToPreviousQuestion"
          :class="{ submitScreenButton: submitScreen }"
        >
          {{ $t('module.information.quiz.participant.previous') }}
        </el-button>
        <el-button
          type="primary"
          class="el-button--submit"
          native-type="submit"
          :disabled="!hasNextQuestion || !questionAnswered"
          v-if="!moderatedQuestionFlow && hasNextQuestion && !submitScreen"
          @click="goToNextQuestion"
        >
          {{ $t('module.information.quiz.participant.next') }}
        </el-button>
        <el-button
          type="primary"
          class="el-button--submit"
          native-type="submit"
          :disabled="hasNextQuestion || !questionAnswered"
          v-if="!moderatedQuestionFlow && !hasNextQuestion && !submitScreen"
          @click="goToSubmitScreen"
        >
          {{ $t('module.information.quiz.participant.submit') }}
        </el-button>
        <el-button
          type="primary"
          class="el-button--submit fullwidth"
          native-type="submit"
          :disabled="!questionAnswered"
          v-if="
            moderatedQuestionFlow &&
            !submitScreen &&
            !activeAnswer.isSaved &&
            activeQuestionType !== QuestionType.INFO &&
            !!activeQuestion &&
            (quizState === QuestionState.ACTIVE_WAIT_FOR_VOTE ||
              quizState === QuestionState.ACTIVE_LAST_CHANGE)
          "
          @click="submitAnswer(true)"
        >
          {{ $t('module.information.quiz.participant.submit') }}
        </el-button>
      </span>
    </template>
    <PublicBase
      :taskId="taskId"
      :authHeaderTyp="EndpointAuthorisationType.PARTICIPANT"
      :usePublicQuestion="false"
      :activeQuestionIndex="activeQuestionIndex"
      :activeQuestionPhase="QuestionPhase.ANSWER"
      v-on:changeQuizState="changeQuizState"
      v-on:changePublicQuestion="(question) => setActiveQuestion(question)"
      v-on:changePublicAnswers="(answers) => (publicAnswerList = answers)"
      v-if="!submitScreen"
      :showData="!initData && !activeAnswer.isSaved"
    >
      <template #answers>
        <el-space
          direction="vertical"
          class="fill"
          v-if="
            activeQuestionType === QuestionType.MULTIPLECHOICE ||
            activeQuestionType === QuestionType.SINGLECHOICE
          "
        >
          <el-button
            v-for="answer in publicAnswerList"
            type="primary"
            :key="answer.answer.id"
            class="link outline-thick"
            :class="{
              correct: answer.answer.parameter.isCorrect && answer.isFinished,
              wrong: !answer.answer.parameter.isCorrect && answer.isFinished,
            }"
            :plain="answer.isHighlightedTemporarily"
            :disabled="!isActive"
            :loading="isSaving(answer.answer.id)"
            v-on:click="changeVote(answer.answer.id)"
          >
            <template #icon>
              <font-awesome-icon
                v-if="isAnswerSelected(answer.answer.id)"
                icon="circle-check"
              />
              <font-awesome-icon v-else :icon="['far', 'circle']" />
            </template>
            {{
              answer.answer.description
                ? answer.answer.description
                : answer.answer.keywords
            }}
            <IdeaMediaViewer :idea="answer.answer" css-class="question-image" />
          </el-button>
        </el-space>
        <el-space
          direction="vertical"
          class="fill"
          v-else-if="activeQuestionType === QuestionType.ORDER"
        >
          <div v-if="quizState === QuestionState.RESULT_ANSWER">
            <div
              v-for="element in publicAnswerList"
              :key="element.answer.id"
              class="media orderDraggable link outline-thick correct"
            >
              <h2 class="media-left">
                {{ publicAnswerList.indexOf(element) + 1 }}
              </h2>
              <p class="media-content">{{ element.answer.keywords }}</p>
              <IdeaMediaViewer
                :idea="element.answer"
                css-class="question-image"
              />
            </div>
          </div>
          <draggable
            v-else
            v-model="orderAnswers"
            tag="ul"
            :component-data="{
              name: 'flip-list',
              type: 'transition',
            }"
            v-bind="dragOptions"
            group="orderAnswers"
            @start="dragging = true"
            @end="handleOrderChange"
            item-key="answer"
          >
            <template #item="{ element }">
              <div class="media orderDraggable link outline-thick">
                <h2 class="media-left">
                  {{ orderAnswers.indexOf(element) + 1 }}
                </h2>
                <p class="media-content">{{ element.answer.keywords }}</p>
                <IdeaMediaViewer
                  :idea="element.answer"
                  css-class="question-image"
                />
              </div>
            </template>
          </draggable>
        </el-space>
        <div v-else-if="activeQuestionType === QuestionType.RATING">
          0 <font-awesome-icon :icon="['far', 'star']" /> =
          {{ $t('module.information.quiz.participant.bad') }}
          <br />
          {{ activeQuestion.parameter.maxValue }}
          <font-awesome-icon icon="star" /> =
          {{ $t('module.information.quiz.participant.good') }}
          <br />
          <el-rate
            v-if="quizState !== QuestionState.RESULT_ANSWER"
            :max="activeQuestion.parameter.maxValue"
            v-model="activeAnswer.numValue"
            size="large"
            v-on:change="inputChanged"
          ></el-rate>
          <el-rate
            v-else
            :max="activeQuestion.parameter.maxValue"
            v-model="animateNumResult"
            :disabled="true"
            size="large"
          ></el-rate>
        </div>
        <el-slider
          v-else-if="
            activeQuestionType === QuestionType.SLIDER &&
            quizState !== QuestionState.RESULT_ANSWER
          "
          :min="activeQuestion.parameter.minValue"
          :max="activeQuestion.parameter.maxValue"
          v-model="activeAnswer.numValue"
          v-on:change="inputChanged"
          :marks="activeQuestionRange"
        ></el-slider>
        <el-slider
          v-else-if="activeQuestionType === QuestionType.SLIDER"
          :min="activeQuestion.parameter.minValue"
          :max="activeQuestion.parameter.maxValue"
          v-model="animateNumResult"
          :marks="activeQuestionRange"
          :disabled="true"
        ></el-slider>
        <div
          v-else-if="activeQuestionType === QuestionType.NUMBER"
          class="media"
        >
          <div class="media-left">
            {{ activeQuestion.parameter.minValue }}
          </div>
          <el-input-number
            v-if="quizState !== QuestionState.RESULT_ANSWER"
            class="media-content"
            :max="activeQuestion.parameter.maxValue"
            :min="activeQuestion.parameter.minValue"
            :value-on-clear="null"
            v-model="activeAnswer.numValue"
            v-on:change="inputChanged"
            v-on:input="inputChanged"
          ></el-input-number>
          <el-input-number
            v-else
            class="media-content"
            :max="activeQuestion.parameter.maxValue"
            :min="activeQuestion.parameter.minValue"
            v-model="animateNumResult"
            :disabled="true"
          ></el-input-number>
          <div class="media-right">
            {{ activeQuestion.parameter.maxValue }}
          </div>
        </div>
        <el-input
          v-else-if="
            activeQuestionType === QuestionType.TEXT &&
            quizState !== QuestionState.RESULT_ANSWER
          "
          type="textarea"
          rows="3"
          v-model="activeAnswer.textValue"
          v-on:change="inputChanged"
          v-on:input="inputChanged"
        ></el-input>
        <el-input
          v-else-if="activeQuestionType === QuestionType.TEXT"
          type="textarea"
          rows="3"
          v-model="textResult"
          :disabled="true"
        ></el-input>
        <div v-else-if="activeQuestionType === QuestionType.IMAGE">
          <ImagePicker
            v-model:link="activeAnswer.link"
            v-model:image="activeAnswer.image"
            v-on:change="inputChanged"
          />
          <br />
          <label>
            {{ $t('module.information.quiz.participant.imageKeywords') }}
          </label>
          <el-input
            v-model="activeAnswer.textValue"
            v-on:change="inputChanged"
            v-on:input="inputChanged"
            :placeholder="
              $t('module.information.quiz.participant.imageKeywords')
            "
            :disabled="quizState === QuestionState.RESULT_ANSWER"
          ></el-input>
        </div>
      </template>
    </PublicBase>
    <div id="submitScreen" v-if="submitScreen">
      <span>{{
        $t('module.information.quiz.participant.thanksIndividual')
      }}</span>
      <span id="ScoreString" v-if="showSummery && quizQuestionCount > 0">{{
        scoreString
      }}</span>
    </div>
    <div id="submitScreen" v-if="activeAnswer.isSaved">
      <span>{{
        $t('module.information.quiz.participant.thanksIndividual')
      }}</span>
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
import { Vote } from '@/types/api/Vote';
import PublicBase, {
  PublicAnswerData,
} from '@/modules/information/quiz/organisms/PublicBase.vue';
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';
import * as timerService from '@/services/timer-service';
import { QuestionnaireType } from '@/modules/information/quiz/types/QuestionnaireType';
import {
  QuestionPhase,
  QuestionState,
} from '@/modules/information/quiz/types/QuestionState';
import * as hierarchyService from '@/services/hierarchy-service';
import * as ideaService from '@/services/idea-service';
import QuizResult from '@/modules/information/quiz/organisms/QuizResult.vue';
import {
  getQuestionResultStorageFromHierarchy,
  getQuestionResultStorageFromQuestionType,
  getQuestionTypeFromHierarchy,
  QuestionResultStorage,
  QuestionType,
} from '@/modules/information/quiz/types/Question';
import { Hierarchy } from '@/types/api/Hierarchy';
import ImagePicker from '@/components/moderator/atoms/ImagePicker.vue';
import * as cashService from '@/services/cash-service';
import draggable from 'vuedraggable';
import TaskParticipantStatesType from '@/types/enum/TaskParticipantStatesType';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import TaskParticipantIterationStatesType from '@/types/enum/TaskParticipantIterationStatesType';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import { delay, until } from '@/utils/wait';
import IdeaMediaViewer from '@/components/moderator/molecules/IdeaMediaViewer.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import MarkdownRender from '@/components/shared/molecules/MarkdownRender.vue';

interface AnswerValue {
  numValue: number | null;
  textValue: string | null;
  link: string | null;
  image: string | null;
  selectionList: string[];
  isSaved: boolean;
}

@Options({
  components: {
    MarkdownRender,
    FontAwesomeIcon,
    IdeaMediaViewer,
    ImagePicker,
    QuizResult,
    ParticipantModuleDefaultContainer,
    PublicBase,
    draggable,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  @Prop({ default: false }) readonly useFullSize!: boolean;
  @Prop({ default: '' }) readonly backgroundClass!: string;
  readonly intervalTime = 100;
  interval!: any;
  publicAnswerList: PublicAnswerData[] = [];
  activeQuestion: Hierarchy | null = null;
  module: Module | null = null;
  task: Task | null = null;
  votes: Vote[] = [];
  EndpointAuthorisationType = EndpointAuthorisationType;
  activeQuestionIndex = -1;
  questionCount = 0;
  quizQuestionCount = 0;
  questionnaireType: QuestionnaireType = QuestionnaireType.QUIZ;
  moderatedQuestionFlow = true;
  score = 0;
  voteResults: boolean[] = [];
  savedQuestions: string[] = [];
  theme = '';

  trackingManager!: TrackingManager;

  activeAnswer: AnswerValue = {
    numValue: null,
    textValue: null,
    link: null,
    image: null,
    selectionList: [],
    isSaved: false,
  };
  ownAnswerList: { [key: string]: string } = {};

  QuestionPhase = QuestionPhase;
  QuestionType = QuestionType;
  QuestionState = QuestionState;
  orderAnswers: PublicAnswerData[] = [];
  dragging = false;
  dragOptions = {
    animation: 200,
    group: 'description',
    disabled: false,
    ghostClass: 'ghost',
  };
  animateNumResult = 0;
  textResult = '';

  submitScreen = false;

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
      this.trackingManager = new TrackingManager(this.taskId, {}, true);
      this.trackingManager.callbackUpdateState = this.updateState;
      this.trackingManager.callbackUpdateFinalStepList =
        this.updateStoredAnswers;
    }
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.PARTICIPANT,
      60 * 60
    );
    hierarchyService.registerGetQuestions(
      this.taskId,
      this.updateQuestions,
      EndpointAuthorisationType.PARTICIPANT,
      60 * 60
    );
    hierarchyService.registerGetList(
      this.taskId,
      'all',
      this.updateHierarchy,
      EndpointAuthorisationType.PARTICIPANT,
      60 * 60
    );
    votingService.registerGetVotes(
      this.taskId,
      this.updateOwnVoteAnswerList,
      EndpointAuthorisationType.PARTICIPANT,
      60 * 60
    );
  }

  hierarchyList: Hierarchy[] = [];
  updateHierarchy(hierarchyList: Hierarchy[]): void {
    this.hierarchyList = hierarchyList;
    const inputList = hierarchyList.filter((item) => item.isOwn);
    for (const input of inputList) {
      const parentId = input.parentId;
      if (parentId) {
        if (this.ownAnswerList[parentId])
          this.ownAnswerList[parentId] += `, ${input.keywords}`;
        else this.ownAnswerList[parentId] = input.keywords;
      }
    }
  }

  updateOwnVoteAnswerList(votes: Vote[]): void {
    for (const vote of votes.sort((a, b) => a.rating - b.rating)) {
      const parentId = this.hierarchyList.find(
        (item) => item.id === vote.ideaId
      )?.parentId;
      if (parentId) {
        if (this.ownAnswerList[parentId])
          this.ownAnswerList[parentId] += `, ${vote.ideaId}`;
        else this.ownAnswerList[parentId] = vote.ideaId;
      }
    }
  }

  async handleOrderChange(): Promise<void> {
    this.dragging = false;
    /*this.publicAnswerList.forEach((answer) => {
      answer.answer.order = this.publicAnswerList.indexOf(answer);
    });*/
    await this.changeOrderVotes();
  }

  get activeQuestionRange(): number[] | { [key: number]: string } {
    if (this.activeQuestion) {
      const end = this.activeQuestion.parameter.maxValue
        ? this.activeQuestion.parameter.maxValue
        : 100;
      const start = this.activeQuestion.parameter.minValue
        ? this.activeQuestion.parameter.minValue
        : 0;
      const rangeCount = end - start + 1;
      if (rangeCount <= 5) {
        return [...Array(rangeCount).keys()].map((x) => (x + start).toString());
      } else {
        const markerValues = [
          start,
          Math.floor(rangeCount / 4 + start),
          Math.floor(rangeCount / 2 + start),
          Math.floor((rangeCount / 4) * 3 + start),
          end,
        ];
        return Object.assign(
          {},
          ...markerValues.map((x) => ({ [x]: x.toString() }))
        );
      }
    }
    return [];
  }

  get activeQuestionId(): string {
    if (this.activeQuestion && this.activeQuestion.id) {
      return this.activeQuestion.id;
    }
    return '';
  }

  get activeQuestionType(): QuestionType {
    return getQuestionTypeFromHierarchy(this.activeQuestion);
  }

  get showSummery(): boolean {
    return this.questionnaireType !== QuestionnaireType.SURVEY;
  }

  get loading(): boolean {
    const element = document.getElementById('loadingScreen');

    if (element != null && !element.classList.contains('zeroOpacity')) {
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

  get scoreString(): string {
    return this.score + '/' + this.quizQuestionCount;
  }

  checkScore(): { isCorrect: boolean; answers: any } {
    let answers: any = null;
    if (
      getQuestionResultStorageFromQuestionType(this.activeQuestionType) ===
      QuestionResultStorage.VOTING
    ) {
      answers = [];
      let voteScore = 0;
      let maxScore = 0;
      if (this.activeQuestionType === QuestionType.ORDER) {
        maxScore = this.orderAnswers.length;
        for (let i = 0; i < this.orderAnswers.length; i++) {
          if (this.orderAnswers[i].answer.order === i) voteScore++;
          else voteScore--;
          answers.push(this.orderAnswers[i].answer.id);
        }
        this.voteResults[this.activeQuestionIndex] = maxScore === voteScore;
      } else if (this.publicAnswerList.length > 0) {
        for (let i = 0; i < this.publicAnswerList.length; i++) {
          for (let j = 0; j < this.votes.length; j++) {
            if (this.publicAnswerList[i].answer.id == this.votes[j].ideaId) {
              if (this.votes[j].rating === 1) {
                answers.push(this.votes[j].ideaId);
              }
              if (
                this.votes[j].rating == 1 &&
                this.publicAnswerList[i].answer.parameter.isCorrect
              ) {
                voteScore++;
              } else if (
                this.votes[j].rating == 0 &&
                this.publicAnswerList[i].answer.parameter.isCorrect
              ) {
                voteScore--;
              } else if (
                this.votes[j].rating == 1 &&
                !this.publicAnswerList[i].answer.parameter.isCorrect
              ) {
                voteScore--;
              } else if (
                this.votes[j].rating == 0 &&
                !this.publicAnswerList[i].answer.parameter.isCorrect
              ) {
                voteScore++;
              }
            }
          }
          if (this.publicAnswerList[i].answer.parameter.isCorrect) {
            maxScore++;
          }
        }
        this.voteResults[this.activeQuestionIndex] = maxScore === voteScore;
      }
    } else {
      if (
        this.activeQuestionType === QuestionType.NUMBER ||
        this.activeQuestionType === QuestionType.RATING ||
        this.activeQuestionType === QuestionType.SLIDER
      ) {
        answers = this.activeAnswer.numValue;
        this.voteResults[this.activeQuestionIndex] =
          this.activeAnswer.numValue ===
          this.activeQuestion?.parameter.correctValue;
      } else if (this.activeQuestionType === QuestionType.TEXT) {
        answers = this.activeAnswer.textValue;
        this.voteResults[this.activeQuestionIndex] = false;
      } else if (this.activeQuestionType === QuestionType.IMAGE) {
        answers = this.activeAnswer.image
          ? this.activeAnswer.image
          : this.activeAnswer.link;
        this.voteResults[this.activeQuestionIndex] = false;
      }
    }
    this.calculateScore();
    return {
      isCorrect: this.voteResults[this.activeQuestionIndex],
      answers: answers,
    };
  }

  calculateScore(): void {
    let score = 0;
    for (let i = 0; i < this.voteResults.length; i++) {
      /*if (
        this.voteResults[i] &&
        this.hasQuestionAnCorrectAnswer(this.questions[i])
      ) {
        score++;
      }*/
      if (
        this.voteResults[i] ||
        !this.hasQuestionAnCorrectAnswer(this.questions[i])
      ) {
        score++;
      }
    }
    this.score = score;
  }

  get getImageSrc(): string {
    if (this.hasImage) {
      //check image src and return. this is just a dummy picture for now
      return require('@/assets/illustrations/Quiz/QuizImage.jpg');
    } else {
      return '';
    }
  }

  get isActive(): boolean {
    if (this.task) return timerService.isActive(this.task);
    return false;
  }

  get hasNextQuestion(): boolean {
    return this.activeQuestionIndex + 1 < this.questionCount;
  }

  async trackState(): Promise<void> {
    const result = this.checkScore();
    if (this.trackingManager) {
      if (!this.savedQuestions.includes(this.activeQuestionId))
        this.savedQuestions.push(this.activeQuestionId);
      if (
        !this.trackingManager.iterationStep ||
        this.trackingManager.iterationStep.ideaId !== this.activeQuestionId ||
        this.trackingManager.iterationStep.iteration !==
          this.trackingManager.iteration?.iteration
      ) {
        await this.trackingManager.createInstanceStepPoints(
          this.activeQuestionId,
          this.hasAnswer()
            ? result.isCorrect
              ? TaskParticipantIterationStepStatesType.CORRECT
              : TaskParticipantIterationStepStatesType.WRONG
            : TaskParticipantIterationStepStatesType.NEUTRAL,
          {
            answer: result.answers,
          },
          !this.hasAnswer() || result.isCorrect ? 10 : 0,
          null,
          true,
          false,
          () => true
        );
      } else {
        await this.trackingManager.saveIterationStep(
          {
            answer: result.answers,
          },
          this.hasAnswer()
            ? result.isCorrect
              ? TaskParticipantIterationStepStatesType.CORRECT
              : TaskParticipantIterationStepStatesType.WRONG
            : TaskParticipantIterationStepStatesType.NEUTRAL,
          null,
          !this.hasAnswer() || result.isCorrect ? 10 : 0,
          true,
          null,
          false,
          () => true
        );
      }
    }
  }

  async logInfo(): Promise<void> {
    if (this.activeQuestionType === QuestionType.INFO) {
      if (
        !this.trackingManager.stepList.find(
          (item) => item.ideaId === this.activeQuestionId
        )
      ) {
        await this.trackingManager.createInstanceStepPoints(
          this.activeQuestionId,
          TaskParticipantIterationStepStatesType.NEUTRAL,
          {},
          0
        );
      }
    }
  }

  async submitAnswer(isSaved = false): Promise<void> {
    let hasChanges = false;
    this.activeAnswer.isSaved = isSaved;
    if (isSaved) {
      this.showSavingState = true;
    }
    if (
      getQuestionResultStorageFromQuestionType(this.activeQuestionType) ===
      QuestionResultStorage.CHILD_HIERARCHY
    ) {
      hasChanges = await this.saveChildHierarchy();
    } else {
      hasChanges = await this.saveVoting();
    }
    if (hasChanges) await this.trackState();
    if (isSaved) {
      this.showSavingState = false;
    }
  }

  async saveChildHierarchy(): Promise<boolean> {
    let hasChanges = false;
    const deleteAnswer = (answerId: string): void => {
      if (answerId) {
        ideaService.deleteIdea(
          answerId,
          EndpointAuthorisationType.PARTICIPANT,
          false
        );
      }
    };
    let answerValue: any = null;
    let answerLink: string | null = null;
    let answerImage: string | null = null;
    if (this.activeQuestionType === QuestionType.TEXT)
      answerValue = this.activeAnswer.textValue;
    if (
      this.activeQuestionType === QuestionType.NUMBER ||
      this.activeQuestionType === QuestionType.RATING ||
      this.activeQuestionType === QuestionType.SLIDER
    )
      answerValue = this.activeAnswer.numValue;
    if (this.activeQuestionType === QuestionType.IMAGE) {
      answerValue = this.activeAnswer.textValue;
      answerLink = this.activeAnswer.link;
      answerImage = this.activeAnswer.image;
      if (!answerValue) answerValue = '...';
    }
    const hasAnswer = answerValue !== null && answerValue.toString() !== '';
    const answer = this.storedActiveAnswer;
    if (answer && answer.id) {
      if (hasAnswer) {
        if (
          answer.keywords !== answerValue?.toString() ||
          answer.description !== answerValue?.toString() ||
          answer.link !== answerLink ||
          answer.image !== answerImage
        ) {
          answer.keywords = answerValue?.toString();
          answer.description = answerValue?.toString();
          answer.link = answerLink;
          answer.image = answerImage;
          await hierarchyService.putHierarchy(
            answer,
            EndpointAuthorisationType.PARTICIPANT
          );
          hierarchyService.refreshCash(this.taskId, this.activeQuestionId);
          hasChanges = true;
        }
      } else {
        deleteAnswer(answer.id);
        answer.id = null;
        this.activeAnswer.isSaved = false;
        this.activeAnswer.numValue = null;
        this.activeAnswer.textValue = null;
        this.activeAnswer.link = null;
        this.activeAnswer.image = null;
        hasChanges = true;
      }
    } else if (hasAnswer) {
      this.storedActiveAnswer = await hierarchyService.postHierarchy(
        this.taskId,
        {
          parentId: this.activeQuestionId,
          keywords: answerValue?.toString(),
          description: answerValue?.toString(),
          order: 0,
        },
        EndpointAuthorisationType.PARTICIPANT
      );
      hierarchyService.refreshCash(this.taskId, this.activeQuestionId);
      hasChanges = true;
    }
    this.ownAnswerList[this.activeQuestionId] = answerValue
      ? answerValue.toString()
      : '';
    return hasChanges;
  }

  async saveVoting(): Promise<boolean> {
    let hasChanges = false;
    for (const vote of this.votes) {
      if (!this.activeAnswer.selectionList.includes(vote.ideaId)) {
        await votingService
          .deleteVote(vote.id, EndpointAuthorisationType.PARTICIPANT)
          .then((result) => {
            if (result) {
              const index = this.votes.findIndex(
                (item) => item.ideaId === vote.ideaId
              );
              if (index > -1) this.votes.splice(index, 1);
            }
          });
        hasChanges = true;
      }
    }
    for (let i = 0; i < this.activeAnswer.selectionList.length; i++) {
      const answerId = this.activeAnswer.selectionList[i];
      const vote = this.votes.find((vote) => vote.ideaId === answerId);
      const rating = this.activeQuestionType === QuestionType.ORDER ? i : 1;
      if (vote) {
        if (vote.rating !== rating) {
          vote.rating = rating;
          await votingService.putVote(vote);
          hasChanges = true;
        }
      } else {
        await votingService
          .postVote(this.taskId, {
            ideaId: answerId,
            rating: rating,
            detailRating: 1,
          })
          .then((vote) => {
            this.votes.push(vote);
          });
        hasChanges = true;
      }
    }
    this.ownAnswerList[this.activeQuestionId] =
      this.activeAnswer.selectionList.join(', ');
    return hasChanges;
  }

  async inputChanged(): Promise<void> {
    await delay(100);
    this.questionAnswered = this.getQuestionAnswered();
  }

  showSavingState = false;
  goForward = true;
  async goToNextQuestion(
    event: PointerEvent | null,
    initData = false
  ): Promise<void> {
    this.goForward = true;
    this.showSavingState = true;
    await this.logInfo();
    this.initData = initData;
    this.checkScore();
    if (this.submitScreen) {
      this.submitScreen = false;
    }
    if (this.hasNextQuestion) {
      await this.submitAnswer();
      this.resetQuestion();
      if (!this.savedQuestions.includes(this.activeQuestionId))
        this.savedQuestions.push(this.activeQuestionId);
      this.activeQuestionIndex++;
      this.reloadAnswers();
    } else await this.goToSubmitScreen();
    this.showSavingState = false;
  }

  async goToSubmitScreen(): Promise<void> {
    this.showSavingState = true;
    await this.logInfo();
    this.checkScore();
    if (!this.savedQuestions.includes(this.activeQuestionId))
      this.savedQuestions.push(this.activeQuestionId);
    this.submitScreen = true;
    await this.submitAnswer();
    this.resetQuestion();
    if (this.trackingManager) {
      await this.trackingManager.saveIteration(
        null,
        this.showSummery
          ? this.score > this.quizQuestionCount / 2
            ? TaskParticipantIterationStatesType.WIN
            : TaskParticipantIterationStatesType.LOOS
          : TaskParticipantIterationStatesType.PARTICIPATED,
        null,
        true
      );
    }
    this.activeQuestionIndex++;
    this.reloadAnswers();
    if (this.trackingManager) {
      await this.trackingManager.saveState(
        {
          score: this.score,
          answeredQuizQuestionCount: this.quizQuestionCount,
          answeredQuestionCount: this.questionCount,
        },
        TaskParticipantStatesType.FINISHED
      );
    }
    this.showSavingState = false;
  }

  get hasPreviousQuestion(): boolean {
    return this.activeQuestionIndex > 0;
  }

  async goToPreviousQuestion(): Promise<void> {
    this.goForward = false;
    this.showSavingState = true;
    this.initData = false;
    this.checkScore();
    if (this.submitScreen) {
      this.submitScreen = false;
    }
    if (this.hasPreviousQuestion) {
      await this.submitAnswer();
      this.resetQuestion();
      this.activeQuestionIndex--;
      this.reloadAnswers();
    }
    this.showSavingState = false;
  }

  isAnswerSelected(answerId: string): boolean {
    return this.activeAnswer.selectionList.includes(answerId);
  }

  isSavingList: string[] = [];
  isSaving(answerId: string): boolean {
    return this.isSavingList.includes(answerId);
  }

  async changeVote(answerId: string): Promise<void> {
    if (this.activeQuestionType === QuestionType.SINGLECHOICE) {
      this.activeAnswer.selectionList = [answerId];
    } else {
      if (this.activeAnswer.selectionList.includes(answerId)) {
        const index = this.activeAnswer.selectionList.indexOf(answerId);
        this.activeAnswer.selectionList.splice(index, 1);
      } else {
        this.activeAnswer.selectionList.push(answerId);
      }
    }
    this.questionAnswered = this.getQuestionAnswered();
  }

  async changeOrderVotes(): Promise<void> {
    this.activeAnswer.selectionList = this.orderAnswers.map(
      (item) => item.answer.id
    ) as string[];
    this.questionAnswered = this.getQuestionAnswered();
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
    this.questionnaireType =
      QuestionnaireType[module.parameter.questionType.toUpperCase()];
    this._setQuizQuestionCount();
    this.moderatedQuestionFlow = module.parameter.moderatedQuestionFlow;
    if (this.moderatedQuestionFlow) this.initData = false;
    if (!this.moderatedQuestionFlow && this.activeQuestionIndex === -1) {
      this.activeQuestionIndex = 0;
    }
  }

  setActiveQuestion(question: Hierarchy | null): void {
    if (this.activeQuestion?.id !== question?.id) {
      const activeQuestionIndex = this.questions.findIndex(
        (item) => question && item.id === question.id
      );
      if (activeQuestionIndex != this.activeQuestionIndex) {
        this.activeQuestionLoaded = false;
        this.resetQuestion(false);
        this.activeQuestionIndex = activeQuestionIndex;
        this.reloadAnswers();
      }
      this.activeQuestion = question;
      this.skipDependenceQuestions();
    }
  }

  activeQuestionLoaded = false;
  @Watch('activeQuestion', { immediate: true })
  async onActiveQuestionChanged(
    newValue: Hierarchy | null,
    oldValue: Hierarchy | null
  ): Promise<void> {
    if (!newValue) {
      this.activeQuestionLoaded = true;
      return;
    }
    this.activeQuestionLoaded = false;
    const newQuestionResultStorage =
      getQuestionResultStorageFromHierarchy(newValue);
    const oldQuestionResultStorage =
      getQuestionResultStorageFromHierarchy(oldValue);
    if (
      newQuestionResultStorage !== oldQuestionResultStorage ||
      newValue?.id !== oldValue?.id
    ) {
      cashService.deregisterAllGet(this.updateAnswers);
      cashService.deregisterAllGet(this.updateVotes);
      if (newValue?.id) {
        if (newQuestionResultStorage === QuestionResultStorage.VOTING) {
          votingService.registerGetHierarchyVotes(
            newValue?.id,
            this.updateVotes,
            EndpointAuthorisationType.PARTICIPANT,
            60 * 60
          );
        } else {
          hierarchyService.registerGetList(
            this.taskId,
            newValue.id,
            this.updateAnswers,
            EndpointAuthorisationType.PARTICIPANT,
            60 * 60
          );
        }
      }
    }
  }

  @Watch('publicAnswerList', { immediate: false })
  async publicAnswerListChanged(): Promise<void> {
    if (this.activeQuestionType === QuestionType.ORDER) {
      if (
        this.orderAnswers.length !== this.publicAnswerList.length ||
        (this.publicAnswerList.length > 0 &&
          !this.orderAnswers.find(
            (answer) => this.publicAnswerList[0].answer.id === answer.answer.id
          ))
      ) {
        if (!this.savedQuestions.includes(this.activeQuestionId))
          this.loadSavedOrder();
      }
    }
  }

  get votesQuestionId(): string | null {
    if (this.hasVotesForActiveQuestion) {
      const votedAnswer = this.publicAnswerList.find(
        (item) =>
          item.answer.id &&
          this.activeAnswer.selectionList.includes(item.answer.id)
      );
      if (votedAnswer) {
        return votedAnswer.answer.parentId;
      }
    }
    return null;
  }

  get hasVotesForActiveQuestion(): boolean {
    return (
      this.activeAnswer.selectionList.length > 0 &&
      this.publicAnswerList.length > 0 &&
      !!this.publicAnswerList.find(
        (answer) =>
          answer.answer.id &&
          this.activeAnswer.selectionList.includes(answer.answer.id)
      )
    );
  }

  async updateVotes(votes: Vote[]): Promise<void> {
    this.votes = votes;
    this.activeAnswer.selectionList = votes
      .sort((a, b) => a.rating - b.rating)
      .map((item) => item.ideaId);
    this.activeAnswer.isSaved = false;
    if (
      getQuestionResultStorageFromQuestionType(this.activeQuestionType) ===
      QuestionResultStorage.VOTING
    ) {
      await until(() => this.publicAnswerList.length > 0);
    }
    if (this.publicAnswerList.length === 0) await delay(500);
    if (
      this.votes.length === votes.length &&
      (votes.length === 0 || votes[0].id === this.votes[0].id)
    ) {
      if (this.hasVotesForActiveQuestion) this.loadSavedOrder();
      await this.skipAnswerQuestions();
      this.activeQuestionLoaded = true;
    }
  }

  async loadSavedOrder(): Promise<void> {
    const isCorrectOrdered = (orderAnswers: PublicAnswerData[]): boolean => {
      for (let i = 0; i < orderAnswers.length; i++) {
        if (orderAnswers[i].answer.order !== i) return false;
      }
      return true;
    };

    const randomSort = (
      orderAnswers: PublicAnswerData[],
      shuffleCount = 0
    ): PublicAnswerData[] => {
      orderAnswers = orderAnswers.sort(() => 0.5 - Math.random());
      if (isCorrectOrdered(orderAnswers) && orderAnswers.length > 1) {
        if (shuffleCount < 10)
          return randomSort(orderAnswers, shuffleCount + 1);
        else {
          const first = orderAnswers[0];
          orderAnswers[0] = orderAnswers[1];
          orderAnswers[1] = first;
        }
      }
      return orderAnswers;
    };

    if (this.activeQuestionType === QuestionType.ORDER) {
      if (this.publicAnswerList.length === 0)
        await until(() => this.publicAnswerList.length > 0, 1000);
      if (this.publicAnswerList.length > 0) {
        const orderAnswers = [...this.publicAnswerList];
        if (this.hasVotesForActiveQuestion) {
          const sortOrder = this.activeAnswer.selectionList;
          const sortedVotes: PublicAnswerData[] = [];
          for (let i = 0; i < sortOrder.length; i++) {
            const option = orderAnswers.find(
              (option) => option.answer.id === sortOrder[i]
            );
            if (option) sortedVotes.push(option);
          }
          this.orderAnswers = sortedVotes;
        } else {
          this.orderAnswers = randomSort(orderAnswers);
          this.handleOrderChange().then(() => {
            this.questionAnswered = this.getQuestionAnswered();
          });
        }
      }
    }
  }

  storedAnswerList: { [key: number]: AnswerValue } = {};
  resetQuestion(resetPublicBaseInput = true): void {
    if (resetPublicBaseInput) {
      this.activeQuestion = null;
      this.publicAnswerList = [];
      this.storedAnswerList[this.activeQuestionIndex] = {
        ...this.activeAnswer,
      };
    }
    this.storedActiveAnswer = undefined;
    this.activeAnswer.isSaved = false;
    this.activeAnswer.textValue = null;
    this.activeAnswer.numValue = null;
    this.activeAnswer.link = null;
    this.activeAnswer.image = null;
    this.activeAnswer.selectionList = [];
    this.activeQuestionLoaded = false;
    this.questionAnswered = false;
    this.votes = [];
  }

  reloadAnswers(): void {
    if (this.storedAnswerList[this.activeQuestionIndex]) {
      const answer = this.storedAnswerList[this.activeQuestionIndex];
      this.activeAnswer.isSaved = false;
      this.activeAnswer.textValue = answer.textValue;
      this.activeAnswer.numValue = answer.numValue;
      this.activeAnswer.link = answer.link;
      this.activeAnswer.image = answer.image;
      this.activeAnswer.selectionList = [];
    }
  }

  storedActiveAnswer: Hierarchy | undefined = undefined;
  async updateAnswers(answers: Hierarchy[]): Promise<void> {
    const answer = answers.find((item) => item.isOwn);
    if (answers.length > 0 && answers[0].parentId !== this.activeQuestionId) {
      return;
    }
    if (answer && !answer.image) answer.image = null;
    if (
      answer &&
      this.storedActiveAnswer &&
      answer.id === this.storedActiveAnswer.id
    ) {
      this.activeQuestionLoaded = true;
      this.questionAnswered = this.getQuestionAnswered();
      return;
    }
    this.storedActiveAnswer = answer;
    if (answer) {
      this.activeAnswer.isSaved = false;
      if (this.activeQuestionType === QuestionType.TEXT) {
        if (answer.description)
          this.activeAnswer.textValue = answer.description;
        else this.activeAnswer.textValue = answer.keywords;
      } else if (
        this.activeQuestionType === QuestionType.NUMBER ||
        this.activeQuestionType === QuestionType.RATING ||
        this.activeQuestionType === QuestionType.SLIDER
      ) {
        if (answer.keywords) {
          const numValue = parseInt(answer.keywords);
          this.activeAnswer.numValue = !isNaN(numValue) ? numValue : null;
        } else this.activeAnswer.numValue = null;
      } else if (this.activeQuestionType === QuestionType.IMAGE) {
        this.activeAnswer.textValue = answer.keywords;
        this.activeAnswer.link = answer.link;
        this.activeAnswer.image = answer.image;
      }
    } else {
      this.activeAnswer.isSaved = false;
      this.activeAnswer.textValue = null;
      this.activeAnswer.numValue = null;
      this.activeAnswer.link = null;
      this.activeAnswer.image = null;
      this.activeAnswer.selectionList = [];
    }
    await this.skipAnswerQuestions();
    this.activeQuestionLoaded = true;
  }

  questions: Hierarchy[] = [];
  updateQuestions(questions: Hierarchy[]): void {
    this.questions = questions;
    this.loadProgress();
    this.questionCount = questions.length;
    this._setQuizQuestionCount();
  }

  _setQuizQuestionCount(): void {
    switch (this.questionnaireType) {
      /*case QuestionnaireType.TALK:
        this.quizQuestionCount = this.questions.filter(
          (item) => item.parameter.questionType !== QuestionType.INFO && item.parameter.hasAnswer
        ).length;
        break;*/
      case QuestionnaireType.TALK:
      case QuestionnaireType.QUIZ:
        this.quizQuestionCount = this.questions.filter(
          (item) => item.parameter.questionType !== QuestionType.INFO
        ).length;
        break;
      case QuestionnaireType.SURVEY:
        this.quizQuestionCount = 0;
        break;
    }
  }

  updateTask(task: Task): void {
    this.task = task;
  }

  updateState(): void {
    if (this.trackingManager.state) {
      if (this.trackingManager.isFinished()) {
        this.questionCount =
          this.trackingManager.state.parameter.answeredQuestionCount;
        this.score = this.trackingManager.state.parameter.score;
        this.submitScreen = true;
      }
    }
  }

  updateStoredAnswers(): void {
    this.loadProgress();
  }

  storedProgressLoaded = false;
  loadProgress(): void {
    if (this.storedProgressLoaded || this.voteResults.length > 0) return;
    const questionCount = this.questions.length;
    const stepCount = this.trackingManager.finalStepList.length;
    if (questionCount > 0 && this.trackingManager && stepCount > 0) {
      let activeIndex =
        stepCount < questionCount ? stepCount : questionCount - 1;
      for (let i = 0; i < questionCount; i++) {
        const trackingData = this.trackingManager.finalStepList.find(
          (item) => item.ideaId === this.questions[i].id
        );
        if (trackingData) {
          this.voteResults[i] =
            trackingData.state ===
            TaskParticipantIterationStepStatesType.CORRECT;
        } else {
          this.voteResults[i] = false;
          if (i < activeIndex) {
            activeIndex = i;
          }
        }
      }
      this.activeQuestionIndex = activeIndex;
      this.calculateScore();
      this.storedProgressLoaded = true;
    }
  }

  quizState: QuestionState = QuestionState.ACTIVE_WAIT_FOR_VOTE;
  async changeQuizState(state: QuestionState): Promise<void> {
    this.quizState = state;
    if (state === QuestionState.RESULT_ANSWER) {
      await until(() => this.activeQuestion);
      this.animateNumResult =
        this.activeAnswer.numValue ??
        this.activeQuestion?.parameter.minValue ??
        0;
      this.textResult =
        this.activeQuestion?.parameter.correctValue ??
        this.activeAnswer.textValue;
      clearInterval(this.interval);
      this.interval = setInterval(this.animateResult, this.intervalTime);
    }
  }

  animateResult(): void {
    if (this.animateNumResult < this.activeQuestion?.parameter.correctValue) {
      this.animateNumResult++;
    } else if (
      this.animateNumResult > this.activeQuestion?.parameter.correctValue
    ) {
      this.animateNumResult--;
    } else {
      clearInterval(this.interval);
    }
  }

  questionAnswered = false;
  getQuestionAnswered(): boolean {
    if (this.activeQuestionType === QuestionType.INFO) return true;
    //if (this.activeQuestionType === QuestionType.ORDER) return true;
    if (
      getQuestionResultStorageFromQuestionType(this.activeQuestionType) ===
      QuestionResultStorage.VOTING
    ) {
      if (this.hasVotesForActiveQuestion) {
        return this.votesQuestionId === this.activeQuestionId;
      }
    } else {
      if (this.activeQuestionType === QuestionType.TEXT) {
        return (
          !!this.activeAnswer.textValue &&
          this.activeAnswer.textValue.length > 0
        );
      } else if (
        this.activeQuestionType === QuestionType.NUMBER ||
        this.activeQuestionType === QuestionType.RATING ||
        this.activeQuestionType === QuestionType.SLIDER
      ) {
        return this.activeAnswer.numValue !== null;
      } else if (this.activeQuestionType === QuestionType.IMAGE) {
        return (
          (!!this.activeAnswer.link || !!this.activeAnswer.image) &&
          !!this.activeAnswer.textValue
        );
      }
    }
    return false;
  }

  async skipAnswerQuestions(): Promise<void> {
    this.questionAnswered = this.getQuestionAnswered();
    if (!this.moderatedQuestionFlow && this.initData) {
      if (
        this.questionAnswered &&
        this.activeQuestionType !== QuestionType.INFO
      )
        await this.goToNextQuestion(null, true);
      else this.initData = false;
    }
  }

  skipDependenceQuestions(): void {
    if (this.activeQuestion) {
      const dependency = this.activeQuestion.parameter.dependency;
      const dependenceValue = this.activeQuestion.parameter.dependenceValue;
      if (dependency) {
        if (this.ownAnswerList[dependency] !== dependenceValue) {
          if (this.goForward) this.goToNextQuestion(null, true);
          else this.goToPreviousQuestion();
        }
      }
    }
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateModule);
    cashService.deregisterAllGet(this.updateAnswers);
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateVotes);
    cashService.deregisterAllGet(this.updateQuestions);
    cashService.deregisterAllGet(this.updateHierarchy);
    cashService.deregisterAllGet(this.updateOwnVoteAnswerList);
  }

  unmounted(): void {
    clearInterval(this.interval);
    this.deregisterAll();
    if (this.trackingManager) this.trackingManager.deregisterAll();
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
  display: inline-flex;
  justify-content: space-between;

  .el-button {
    margin-left: unset;
    margin-right: unset;
  }
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

.orderDraggable {
  background-color: var(--color-background);
  padding: 1rem;
  cursor: move;
  margin: 1rem 0;
  border-radius: 10px;
  display: flex;
  align-items: center;

  img {
    margin: -1rem;
  }
}

.orderDraggable h2 {
  font-weight: bold;
}

.ghost {
  background-color: var(--color-dark-contrast);
  color: white;
}

[module-theme='paper'] {
  background-image: url('@/modules/information/quiz/assets/clipboard.png');
  background-size: cover;
  background-position: center top;

  .el-space .el-button,
  .orderDraggable,
  .el-slider,
  .el-rate,
  .el-input-number,
  .el-textarea::v-deep(.el-textarea__inner) {
    border-radius: 0;
    background: linear-gradient(
        color-mix(in srgb, var(--color-informing) 45%, transparent),
        color-mix(in srgb, var(--color-informing) 45%, transparent)
      ),
      url('@/modules/information/quiz/assets/paper.jpg');
    //filter: drop-shadow(0.3rem 0.3rem 0.5rem var(--color-gray-dark));
    color: var(--color-dark-contrast);
    border: none;
  }

  .el-slider {
    padding: 1.5rem 1.5rem 3rem 1.5rem;
  }

  .el-rate {
    padding: 1.5rem;
  }

  .el-input-number::v-deep(.el-input-number__decrease),
  .el-input-number::v-deep(.el-input-number__increase) {
    background-color: color-mix(
      in srgb,
      var(--el-fill-color-light) 60%,
      transparent
    );
    border-radius: 0;
  }

  .el-input-number::v-deep(.el-input__wrapper) {
    background-color: transparent;
    border-radius: 0;
  }

  .el-rate::v-deep(.el-rate__item) {
    color: var(--color-dark-contrast);
  }

  .el-slider::v-deep(.el-slider__marks-text) {
    color: var(--color-dark-contrast);
  }

  #submitScreen {
    background: linear-gradient(
        color-mix(in srgb, var(--color-informing) 45%, transparent),
        color-mix(in srgb, var(--color-informing) 45%, transparent)
      ),
      url('@/modules/information/quiz/assets/paper.jpg');
    //filter: drop-shadow(0.3rem 0.3rem 0.5rem var(--color-gray-dark));
    padding: 1rem;
  }
}

[module-theme='paper'].module-content::v-deep(.media) {
  --module-color: var(--color-dark-contrast);
}

[module-theme='paper'].module-content::v-deep(.fixed) {
  margin: 0 -2rem -1rem -2rem;
  padding: 1rem 2rem;
  width: calc(100% + 4rem);
}

[module-theme='interview'] {
  background-image: url('@/modules/information/quiz/assets/lectern.png'),
    url('@/modules/information/quiz/assets/stage.png');
  background-position: center bottom, left top;
  background-repeat: no-repeat;
  background-size: contain, cover;

  .el-space .el-button,
  .orderDraggable,
  .el-slider,
  .el-rate,
  .el-input-number,
  .el-textarea::v-deep(.el-textarea__inner) {
    border-radius: var(--border-radius) var(--border-radius) 0
      var(--border-radius);
    background-color: color-mix(
      in srgb,
      var(--color-background) 60%,
      transparent
    );
    border: solid 2px var(--color-gray);
    color: var(--color-dark-contrast);
  }

  .el-slider {
    padding: 1.5rem 1.5rem 3rem 1.5rem;
  }

  .el-rate {
    padding: 1.5rem;
  }

  .el-rate::v-deep(.el-rate__item) {
    color: var(--color-dark-contrast);
  }

  .el-slider::v-deep(.el-slider__marks-text) {
    color: var(--color-dark-contrast);
  }

  .el-input-number::v-deep(.el-input-number__decrease),
  .el-input-number::v-deep(.el-input-number__increase) {
    background-color: color-mix(
      in srgb,
      var(--el-fill-color-light) 60%,
      transparent
    );
  }

  .el-input-number::v-deep(.el-input-number__decrease) {
    border-radius: var(--border-radius) 0 0 var(--border-radius);
  }

  .el-input-number::v-deep(.el-input-number__increase) {
    border-radius: 0 calc(var(--border-radius) - 3px) 0 0;
  }

  .el-input-number::v-deep(.el-input__wrapper) {
    background-color: transparent;
    border-radius: var(--border-radius) var(--border-radius) 0
      var(--border-radius);
  }

  #submitScreen {
    border-radius: var(--border-radius) var(--border-radius)
      var(--border-radius) 0;
    background-color: color-mix(
      in srgb,
      var(--color-informing) 60%,
      transparent
    );
    border: solid 2px var(--color-gray);
    padding: 1rem;
  }
}

[module-theme='interview'].module-content::v-deep(.fixed) {
  margin: 0 -2rem -1rem -2rem;
  padding: 1rem 2rem;
  width: calc(100% + 4rem);
}

.media-left,
.media-right {
  margin: auto 0.5rem;
}

.fullwidth::v-deep(> span) {
  display: inline-block;
  width: 100%;
  text-align: center;
}

.module-content::v-deep(.participant-content) {
  padding-bottom: 1rem;
}
</style>
