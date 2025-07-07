<template>
  <ParticipantModuleDefaultContainer
    :task-id="taskId"
    :module="moduleName"
    :showLoadingState="showSavingState || !activeQuestionLoaded"
    :key="resultKey"
  >
    <div id="loadingScreen" v-if="loading">
      <span
        >{{
          $t('module.information.personalityTest.participant.waiting')
        }}...</span
      >
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
          :disabled="!hasPreviousQuestion"
          v-if="!submitScreen"
          @click="goToPreviousQuestion"
          :class="{ submitScreenButton: submitScreen }"
        >
          {{ $t('module.information.personalityTest.participant.previous') }}
        </el-button>
        <el-button
          type="primary"
          class="el-button--submit"
          native-type="submit"
          :disabled="!hasNextQuestion || !questionAnswered"
          v-if="hasNextQuestion && !submitScreen"
          @click="goToNextQuestion"
        >
          {{ $t('module.information.personalityTest.participant.next') }}
        </el-button>
        <el-button
          type="primary"
          class="el-button--submit"
          native-type="submit"
          :disabled="hasNextQuestion || !questionAnswered"
          v-if="!hasNextQuestion && !submitScreen"
          @click="goToSubmitScreen"
        >
          {{ $t('module.information.personalityTest.participant.submit') }}
        </el-button>
      </span>
    </template>
    <div class="question" v-if="activeQuestion">
      {{ activeQuestion.question.order + 1 }}.
      {{
        $t(
          `module.information.personalityTest.${test}.questions.${convertToI18nKey(
            activeQuestion.question.description
          )}.text`
        )
      }}
    </div>
    <el-space
      direction="vertical"
      class="fill"
      v-if="activeQuestion && activeQuestionType === QuestionType.ORDER"
    >
      <draggable
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
            <p class="media-content">
              {{
                $t(
                  `module.information.personalityTest.${test}.answers.${convertToI18nKey(
                    element.description
                  )}`
                )
              }}
            </p>
          </div>
        </template>
      </draggable>
    </el-space>
    <el-space
      direction="vertical"
      class="fill"
      v-else-if="
        activeQuestion && activeQuestionType === QuestionType.SINGLECHOICE
      "
    >
      <el-radio-group
        v-model="activeAnswer.textValue"
        v-on:change="choiceChanged"
      >
        <el-radio
          v-for="answer in activeQuestion.answers"
          :key="answer.id"
          :label="answer.id"
          border
        >
          {{
            $t(
              `module.information.personalityTest.${test}.answers.${convertToI18nKey(
                answer.description
              )}`
            )
          }}
        </el-radio>
      </el-radio-group>
    </el-space>
    <div
      v-else-if="activeQuestion && activeQuestionType === QuestionType.RATING"
    >
      1 <font-awesome-icon icon="star" /> =
      {{ $t(`module.information.personalityTest.${test}.enum.rating.1`) }}
      <br />
      5 <font-awesome-icon icon="star" /> =
      {{ $t(`module.information.personalityTest.${test}.enum.rating.5`) }}
      <br />
      <el-rate
        :max="5"
        v-model="activeAnswer.numValue"
        size="large"
        v-on:change="inputChanged"
      ></el-rate>
    </div>
    <div
      v-else-if="activeQuestion && activeQuestionType === QuestionType.YESNO"
    >
      <el-radio-group
        v-model="activeAnswer.numValue"
        size="large"
        v-on:change="inputChanged"
      >
        <el-radio-button :label="1">{{
          $t(`module.information.personalityTest.participant.yes`)
        }}</el-radio-button>
        <el-radio-button :label="0">{{
          $t(`module.information.personalityTest.participant.no`)
        }}</el-radio-button>
      </el-radio-group>
    </div>
    <div id="submitScreen" v-if="activeAnswer.isSaved">
      <span>{{
        $t(
          `module.information.personalityTest.${test}.participant.thanksIndividual`
        )
      }}</span>
    </div>
    <ParticipantResult
      v-if="submitScreen && resultValue"
      :test="test"
      :result-value="resultValue"
    />
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
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';
import * as timerService from '@/services/timer-service';
import * as hierarchyService from '@/services/hierarchy-service';
import * as ideaService from '@/services/idea-service';
import {
  getQuestionResultStorageFromHierarchy,
  getQuestionResultStorageFromQuestionType,
  Question,
  QuestionResultStorage,
  QuestionType,
} from '@/modules/information/quiz/types/Question';
import { Hierarchy } from '@/types/api/Hierarchy';
import * as cashService from '@/services/cash-service';
import draggable from 'vuedraggable';
import TaskParticipantStatesType from '@/types/enum/TaskParticipantStatesType';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import TaskParticipantIterationStatesType from '@/types/enum/TaskParticipantIterationStatesType';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import { delay, until } from '@/utils/wait';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
  convertToI18nKey,
  ResultCalculation,
  ResultValue,
} from '@/modules/information/personalityTest/types/ResultType';
import ParticipantResult from '@/modules/information/personalityTest/organisms/ParticipantResult/default.vue';
import * as importService from '@/modules/information/personalityTest/utils/import';

interface AnswerValue {
  numValue: number | null;
  textValue: string | null;
  selectionList: string[];
  isSaved: boolean;
}

@Options({
  methods: { convertToI18nKey },
  components: {
    FontAwesomeIcon,
    ParticipantModuleDefaultContainer,
    draggable,
    ParticipantResult: ParticipantResult,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  @Prop({ default: false }) readonly useFullSize!: boolean;
  @Prop({ default: '' }) readonly backgroundClass!: string;
  activeQuestion: Question | null = null;
  module: Module | null = null;
  task: Task | null = null;
  test = '';
  votes: Vote[] = [];
  EndpointAuthorisationType = EndpointAuthorisationType;
  activeQuestionIndex = -1;
  questionCount = 0;
  voteResults: boolean[] = [];
  savedQuestions: string[] = [];

  trackingManager!: TrackingManager;

  activeAnswer: AnswerValue = {
    numValue: null,
    textValue: null,
    selectionList: [],
    isSaved: false,
  };
  ownAnswerList: { [key: string]: string } = {};
  resultValue: ResultValue | null = null;
  calculation: ResultCalculation = new ResultCalculation();
  storedActiveAnswer: Hierarchy | undefined = undefined;

  QuestionType = QuestionType;
  orderAnswers: Hierarchy[] = [];
  dragging = false;
  dragOptions = {
    animation: 200,
    group: 'description',
    disabled: false,
    ghostClass: 'ghost',
  };

  submitScreen = false;
  resultKey = 0;

  get loading(): boolean {
    return this.questions.length === 0 || this.hierarchyList.length === 0;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    if (this.taskId) {
      this.trackingManager = new TrackingManager(this.taskId, {});
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
    this.assignAnswers();
    const inputList = hierarchyList.filter((item) => item.isOwn);
    for (const input of inputList) {
      const parentId = input.parentId;
      if (parentId) {
        if (this.ownAnswerList[parentId])
          this.ownAnswerList[parentId] += `, ${input.keywords}`;
        else this.ownAnswerList[parentId] = input.keywords;
      }
    }
    this.loadStoredAnswersForActiveQuestion();
  }

  assignAnswers(): void {
    for (const question of this.questions) {
      question.answers = this.hierarchyList.filter(
        (item) => item.parentId === question.question.id
      );
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
    await this.changeOrderVotes();
  }

  get activeQuestionId(): string {
    if (this.activeQuestion && this.activeQuestion.question.id) {
      return this.activeQuestion.question.id;
    }
    return '';
  }

  get activeQuestionType(): QuestionType {
    if (this.activeQuestion) return this.activeQuestion.questionType;
    return QuestionType.RATING;
  }

  initData = true;
  mounted(): void {
    this.initData = true;
  }

  get isActive(): boolean {
    if (this.task) return timerService.isActive(this.task);
    return false;
  }

  get hasNextQuestion(): boolean {
    return this.activeQuestionIndex + 1 < this.questionCount;
  }

  async trackState(): Promise<void> {
    let answers: any = null;
    if (
      getQuestionResultStorageFromQuestionType(this.activeQuestionType) ===
      QuestionResultStorage.VOTING
    ) {
      answers = [];
      if (this.activeQuestionType === QuestionType.ORDER) {
        for (let i = 0; i < this.orderAnswers.length; i++) {
          answers.push(this.orderAnswers[i].id);
        }
      } else if (this.activeQuestion) {
        answers.push(this.activeAnswer.textValue);
      }
    } else {
      answers = this.activeAnswer.numValue;
    }
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
          TaskParticipantIterationStepStatesType.NEUTRAL,
          {
            answer: answers,
          },
          10,
          null,
          true,
          false,
          () => true
        );
      } else {
        await this.trackingManager.saveIterationStep(
          {
            answer: answers,
          },
          TaskParticipantIterationStepStatesType.NEUTRAL,
          null,
          10,
          true,
          null,
          false,
          () => true
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
    if (
      this.activeQuestionType === QuestionType.RATING ||
      this.activeQuestionType === QuestionType.YESNO
    )
      answerValue = this.activeAnswer.numValue;
    const hasAnswer = answerValue !== null && answerValue.toString() !== '';
    const answer = this.storedActiveAnswer;
    if (answer && answer.id) {
      if (hasAnswer) {
        if (
          answer.keywords !== answerValue?.toString() ||
          answer.description !== answerValue?.toString()
        ) {
          answer.keywords = answerValue?.toString();
          answer.description = answerValue?.toString();
          answer.link = null;
          answer.image = null;
          await hierarchyService.putHierarchy(
            answer,
            EndpointAuthorisationType.PARTICIPANT
          );
          hierarchyService.refreshCash(this.taskId, 'all');
          hasChanges = true;
        }
      } else {
        deleteAnswer(answer.id);
        answer.id = null;
        this.activeAnswer.isSaved = false;
        this.activeAnswer.numValue = null;
        this.activeAnswer.textValue = null;
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
      hierarchyService.refreshCash(this.taskId, 'all');
      hasChanges = true;
    }
    this.ownAnswerList[this.activeQuestionId] = answerValue
      ? answerValue.toString()
      : '';
    return hasChanges;
  }

  async saveVoting(): Promise<boolean> {
    if (
      this.activeAnswer.selectionList.length === 0 ||
      !this.activeQuestion?.answers.find(
        (item) => item.id === this.activeAnswer.selectionList[0]
      )
    ) {
      return false;
    }
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

  async choiceChanged(): Promise<void> {
    if (this.activeAnswer.textValue) {
      this.activeAnswer.selectionList = [this.activeAnswer.textValue];
    }
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
    this.initData = initData;
    if (this.submitScreen) {
      this.submitScreen = false;
    }
    if (this.hasNextQuestion) {
      await this.submitAnswer();
      this.resetQuestion();
      if (!this.savedQuestions.includes(this.activeQuestionId))
        this.savedQuestions.push(this.activeQuestionId);
      this.activeQuestionIndex++;
      this.setActiveQuestion();
      this.reloadAnswers();
    } else await this.goToSubmitScreen();
    this.showSavingState = false;
  }

  async goToSubmitScreen(): Promise<void> {
    this.showSavingState = true;
    if (!this.savedQuestions.includes(this.activeQuestionId))
      this.savedQuestions.push(this.activeQuestionId);
    this.submitScreen = true;
    await this.submitAnswer();
    const answerList: { [key: string]: number | { [key: string]: number } } =
      {};
    for (const question of this.questions) {
      if (question.question.id && question.question.description) {
        if (this.ownAnswerList[question.question.id] === undefined) {
          await delay(100);
        }
        if (
          question.questionType === QuestionType.RATING ||
          this.activeQuestionType === QuestionType.YESNO
        ) {
          answerList[question.question.description] = parseInt(
            this.ownAnswerList[question.question.id]
          );
        } else {
          const orderList = this.ownAnswerList[question.question.id]
            .split(', ')
            .reverse();
          answerList[question.question.description] = {};
          for (const answer of question.answers) {
            if (answer.description) {
              answerList[question.question.description][answer.description] =
                orderList.findIndex((item) => item === answer.id) + 1;
            }
          }
        }
      }
    }
    this.resultValue = this.calculation.calculateResult(answerList);
    this.resetQuestion();
    if (this.trackingManager) {
      await this.trackingManager.saveIteration(
        this.resultValue,
        TaskParticipantIterationStatesType.PARTICIPATED,
        null,
        true
      );
    }
    this.activeQuestionIndex++;
    this.setActiveQuestion();
    this.reloadAnswers();
    if (this.trackingManager) {
      await this.trackingManager.saveState(
        {
          answeredQuestionCount: this.questionCount,
          ...this.resultValue,
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
    if (this.submitScreen) {
      this.submitScreen = false;
    }
    if (this.hasPreviousQuestion) {
      await this.submitAnswer();
      this.resetQuestion();
      this.activeQuestionIndex--;
      this.setActiveQuestion();
      this.reloadAnswers();
    }
    this.showSavingState = false;
  }

  isSavingList: string[] = [];
  isSaving(answerId: string): boolean {
    return this.isSavingList.includes(answerId);
  }

  async changeOrderVotes(): Promise<void> {
    this.activeAnswer.selectionList = this.orderAnswers.map(
      (item) => item.id
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
    this.test = module.parameter.test;
    importService.importCalculation(this.test).then((input) => {
      this.calculation = input;
    });
    if (this.activeQuestionIndex === -1) {
      this.activeQuestionIndex = 0;
      this.setActiveQuestion();
    }
    importService
      .importComponent('ParticipantResult', this.test)
      .then((component) => {
        if (this.$options.components) {
          this.$options.components['ParticipantResult'] = component as any;
          this.resultKey++;
        }
      });
  }

  setActiveQuestion(): void {
    const oldActiveQuestionIndex = this.questions.findIndex(
      (item) =>
        this.activeQuestionId && item.question.id === this.activeQuestionId
    );
    if (oldActiveQuestionIndex !== this.activeQuestionIndex) {
      this.activeQuestionLoaded = false;
      this.resetQuestion(false);
      this.reloadAnswers();
      this.activeQuestion = this.questions[this.activeQuestionIndex];
    }
  }

  activeQuestionLoaded = false;
  @Watch('activeQuestion', { immediate: true })
  async onActiveQuestionChanged(
    newValue: Question | null,
    oldValue: Question | null
  ): Promise<void> {
    if (!newValue) {
      this.activeQuestionLoaded = true;
      return;
    }
    this.activeQuestionLoaded = false;
    const newQuestionResultStorage = getQuestionResultStorageFromHierarchy(
      newValue.question
    );
    const oldQuestionResultStorage = getQuestionResultStorageFromHierarchy(
      oldValue ? oldValue.question : null
    );
    if (
      newQuestionResultStorage !== oldQuestionResultStorage ||
      newValue.question.id !== oldValue?.question.id
    ) {
      cashService.deregisterAllGet(this.updateVotes);
      if (newValue.question.id) {
        if (this.activeQuestionType === QuestionType.ORDER) {
          this.loadSavedOrder();
        }

        if (newQuestionResultStorage === QuestionResultStorage.VOTING) {
          votingService.registerGetHierarchyVotes(
            newValue.question.id,
            this.updateVotes,
            EndpointAuthorisationType.PARTICIPANT,
            60 * 60
          );
        } else {
          this.loadStoredAnswersForActiveQuestion();
        }
      }
    }
  }

  get votesQuestionId(): string | null {
    if (this.hasVotesForActiveQuestion) {
      const votedAnswer = this.activeQuestion?.answers.find(
        (item) => item.id && this.activeAnswer.selectionList.includes(item.id)
      );
      if (votedAnswer) {
        return votedAnswer.parentId;
      }
    }
    return null;
  }

  get hasVotesForActiveQuestion(): boolean {
    return (
      this.activeAnswer.selectionList.length > 0 &&
      !!this.activeQuestion &&
      this.activeQuestion.answers.length > 0 &&
      !!this.activeQuestion.answers.find(
        (answer) =>
          answer.id && this.activeAnswer.selectionList.includes(answer.id)
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
      await until(
        () => this.activeQuestion && this.activeQuestion.answers.length > 0
      );

      if (
        this.activeQuestionType === QuestionType.SINGLECHOICE &&
        votes.length > 0
      ) {
        this.activeAnswer.textValue = votes[0].ideaId;
      } else this.activeAnswer.textValue = null;
    }
    if (this.activeQuestion && this.activeQuestion.answers.length === 0)
      await delay(500);
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
    const isCorrectOrdered = (orderAnswers: Hierarchy[]): boolean => {
      for (let i = 0; i < orderAnswers.length; i++) {
        if (orderAnswers[i].order !== i) return false;
      }
      return true;
    };

    const randomSort = (
      orderAnswers: Hierarchy[],
      shuffleCount = 0
    ): Hierarchy[] => {
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
      if (!this.activeQuestion || this.activeQuestion.answers.length === 0)
        await until(
          () => this.activeQuestion && this.activeQuestion.answers.length > 0,
          1000
        );
      if (this.activeQuestion && this.activeQuestion.answers.length > 0) {
        const orderAnswers = [...this.activeQuestion.answers];
        if (this.hasVotesForActiveQuestion) {
          const sortOrder = this.activeAnswer.selectionList;
          const sortedVotes: Hierarchy[] = [];
          for (let i = 0; i < sortOrder.length; i++) {
            const option = orderAnswers.find(
              (option) => option.id === sortOrder[i]
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
      this.storedAnswerList[this.activeQuestionIndex] = {
        ...this.activeAnswer,
      };
    }
    this.storedActiveAnswer = undefined;
    this.activeAnswer.isSaved = false;
    this.activeAnswer.numValue = null;
    this.activeAnswer.textValue = null;
    this.activeAnswer.selectionList = [];
    this.activeQuestionLoaded = false;
    this.questionAnswered = false;
    this.votes = [];
  }

  reloadAnswers(): void {
    if (this.storedAnswerList[this.activeQuestionIndex]) {
      const answer = this.storedAnswerList[this.activeQuestionIndex];
      this.activeAnswer.isSaved = false;
      this.activeAnswer.numValue = answer.numValue;
      this.activeAnswer.textValue = answer.textValue;
      this.activeAnswer.selectionList = [];
    }
  }

  async loadStoredAnswersForActiveQuestion(): Promise<void> {
    if (!this.activeQuestion) return;
    const questionId = this.activeQuestion.question.id;
    if (
      this.activeQuestionType === QuestionType.RATING ||
      this.activeQuestionType === QuestionType.YESNO
    ) {
      const answer = this.hierarchyList.find(
        (item) => item.isOwn && item.parentId === questionId
      );
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
        if (answer.keywords) {
          const numValue = parseInt(answer.keywords);
          this.activeAnswer.numValue = !isNaN(numValue) ? numValue : null;
        } else this.activeAnswer.numValue = null;
      } else {
        this.activeAnswer.isSaved = false;
        this.activeAnswer.numValue = null;
        this.activeAnswer.selectionList = [];
      }
      await this.skipAnswerQuestions();
    }
    this.activeQuestionLoaded = true;
  }

  questions: Question[] = [];
  updateQuestions(questions: Hierarchy[]): void {
    this.questions = hierarchyService.convertToQuestions(questions);
    this.assignAnswers();
    this.loadProgress();
    this.questionCount = questions.length;
  }

  updateTask(task: Task): void {
    this.task = task;
  }

  updateState(): void {
    if (this.trackingManager.state) {
      if (this.trackingManager.isFinished()) {
        this.questionCount =
          this.trackingManager.state.parameter.answeredQuestionCount;
        this.submitScreen = true;
      }
    }
  }

  updateStoredAnswers(): void {
    this.loadProgress(true);
  }

  storedProgressLoaded = false;
  loadProgress(changeState = false): void {
    if (this.storedProgressLoaded || this.voteResults.length > 0) return;
    const questionCount = this.questions.length;
    const stepCount = this.trackingManager.finalStepList.length;
    if (questionCount > 0 && this.trackingManager && stepCount > 0) {
      let activeIndex =
        stepCount < questionCount ? stepCount : questionCount - 1;
      for (let i = 0; i < questionCount; i++) {
        const trackingData = this.trackingManager.finalStepList.find(
          (item) => item.ideaId === this.questions[i].question.id
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
      this.setActiveQuestion();
      this.storedProgressLoaded = true;
    } else if (changeState && questionCount > 0 && this.trackingManager) {
      this.storedProgressLoaded = true;
    }
  }

  questionAnswered = false;
  getQuestionAnswered(): boolean {
    if (this.activeQuestionType === QuestionType.ORDER) {
      if (this.hasVotesForActiveQuestion) {
        return this.votesQuestionId === this.activeQuestionId;
      }
    } else if (this.activeQuestionType === QuestionType.SINGLECHOICE) {
      return this.activeAnswer.textValue !== null;
    } else if (
      this.activeQuestionType === QuestionType.RATING ||
      this.activeQuestionType === QuestionType.YESNO
    ) {
      return this.activeAnswer.numValue !== null;
    }
    return false;
  }

  async skipAnswerQuestions(): Promise<void> {
    this.questionAnswered = this.getQuestionAnswered();
    if (this.initData) {
      if (this.questionAnswered) await this.goToNextQuestion(null, true);
      else this.initData = false;
    }
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateModule);
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateVotes);
    cashService.deregisterAllGet(this.updateQuestions);
    cashService.deregisterAllGet(this.updateHierarchy);
    cashService.deregisterAllGet(this.updateOwnVoteAnswerList);
  }

  unmounted(): void {
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

.el-rate {
  //margin-top: 1rem;
  flex-wrap: wrap;
  height: unset;
}

.el-rate::v-deep(.el-icon) {
  height: 2.5em;
  width: 2.5em;
  margin-top: 1rem;
  margin-right: 0.8rem;

  svg {
    height: 2.5em;
    width: 2.5em;
  }
}

#submitScreen {
  text-align: center;
}

#submitScreen > div {
  padding-bottom: 1rem;
  text-align: left;
}

.markdown .el-image::v-deep(img) {
  max-height: unset;
  padding: unset;
}

h1 {
  font-size: var(--font-size-large);
  font-weight: var(--font-weight-bold);
}

.el-radio {
  --color: var(--color-informing-dark);
  white-space: unset;
  height: unset;
  width: 100%;
  margin-top: 0.5rem;
  background-color: var(--color);
  --el-color-primary: white;
  --el-radio-text-color: white;
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;

  &.is-checked {
    --color: var(--color-informing);
  }
}

.el-radio::v-deep(.el-radio__inner) {
  font-family: 'Font Awesome';
  --el-radio-input-width: 1.2rem;
  --el-radio-input-height: var(--el-radio-input-width);

  &:after {
    width: var(--el-radio-input-width);
    height: var(--el-radio-input-height);
    background-color: unset;
    content: '\f00c';
    color: var(--color-informing);
    text-align: center;
  }
}
</style>
