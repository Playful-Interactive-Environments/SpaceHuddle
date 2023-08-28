<template>
  <el-steps
    v-if="publicQuestion"
    :active="publicQuestionIndex"
    finish-status="success"
  >
    <el-step
      v-for="question in questions"
      :key="question.question.id"
    ></el-step>
  </el-steps>
  <div v-if="showQuestion && showData" class="fill">
    <el-space
      direction="vertical"
      class="fill"
      v-if="
        publicQuestion &&
        publicQuestion.question &&
        (statePointer >= 0 || !isActive)
      "
    >
      <img
        v-if="hasPublicImage"
        :src="publicImage"
        class="question-image"
        alt=""
      />
      <div class="question">
        {{ publicQuestion.question.keywords }}
      </div>
      <slot name="answers"></slot>
    </el-space>
  </div>
  <div v-if="(showExplanation || showStatistics) && publicQuestion">
    <span class="explanation result">
      {{ publicQuestion.question.order + 1 }}.
      {{ publicQuestion.question.keywords }}
    </span>
  </div>
  <div
    v-if="
      publicQuestion &&
      publicQuestion.question &&
      (showExplanation || showStatistics)
    "
    class="explanation"
  >
    {{ publicQuestion.question.description }}
  </div>
  <div v-if="showStatistics && publicQuestion">
    <QuizResult
      :voteResult="voteResult"
      :change="false"
      :questionnaireType="questionnaireType"
      :questionType="activeQuestionType"
      :update="true"
    />
  </div>
  <div
    v-else-if="
      showStatistics && !publicQuestion && activeArea !== TimelineArea.right
    "
  >
    <QuizResult
      :voteResult="detailVotingResult"
      resultColumn="countParticipant"
      :change="false"
      :questionnaireType="
        trackingResult.length > 0 && showQuestionWinStatistic
          ? questionnaireType
          : QuestionnaireType.SURVEY
      "
      :questionType="activeQuestionType"
      :update="true"
    />
  </div>
  <div
    v-else-if="
      showStatistics && !publicQuestion && activeArea === TimelineArea.right
    "
  >
    <Highscore :tracking-result="trackingResult" />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Task } from '@/types/api/Task';
import {
  getQuestionResultStorageFromHierarchy,
  getQuestionResultStorageFromQuestionType,
  Question,
  QuestionResultStorage,
  QuestionType,
} from '@/modules/information/quiz/types/Question';
import { VoteResult, VoteResultDetail } from '@/types/api/Vote';
import {
  QuestionPhase,
  QuestionState,
  QuizStateProperty,
} from '@/modules/information/quiz/types/QuestionState';
import * as timerService from '@/services/timer-service';
import { Hierarchy } from '@/types/api/Hierarchy';
import * as taskService from '@/services/task-service';
import * as hierarchyService from '@/services/hierarchy-service';
import * as votingService from '@/services/voting-service';
import QuizResult from '@/modules/information/quiz/organisms/QuizResult.vue';
import {
  moduleNameValid,
  QuestionnaireType,
} from '@/modules/information/quiz/types/QuestionnaireType';
import { until } from '@/utils/wait';
import * as cashService from '@/services/cash-service';
import * as taskParticipantService from '@/services/task-participant-service';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import Highscore from '@/modules/information/quiz/organisms/Highscore.vue';
import { TimelineArea } from '@/components/moderator/organisms/Timeline/ProcessTimeline.vue';

export interface PublicAnswerData {
  answer: Hierarchy;
  isHighlighted: boolean;
  isHighlightedTemporarily: boolean;
  isFinished: boolean;
}

@Options({
  components: {
    Highscore,
    QuizResult,
  },
  emits: ['changePublicAnswers', 'changePublicQuestion', 'changeQuizState'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicBase extends Vue {
  //#region Veriables
  @Prop() readonly taskId!: string;
  @Prop({ default: true }) readonly usePublicQuestion!: boolean;
  @Prop({ default: -1 }) readonly activeQuestionIndex!: number;
  @Prop({ default: QuestionPhase.RESULT })
  readonly activeQuestionPhase!: QuestionPhase;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  @Prop({ default: true }) readonly showData!: boolean;
  @Prop({ default: QuestionState.ACTIVE_CREATE_QUESTION })
  readonly defaultQuestionState!: QuestionState;
  @Prop({ default: false }) readonly showQuestionWinStatistic!: boolean;
  readonly intervalTime = 1000;
  interval!: any;
  task: Task | null = null;
  questions: Question[] = [];
  publicQuestion: Question | null = null;
  voteResult: VoteResult[] = [];
  moduleQuestionnaireType: QuestionnaireType = QuestionnaireType.QUIZ;
  moderatedQuestionFlow = true;

  questionState: QuestionState = QuestionState.ACTIVE_CREATE_QUESTION;
  statePointer = 0;
  QuestionnaireType = QuestionnaireType;
  TimelineArea = TimelineArea;
  //#endregion properties

  //#region get / set
  get hasPublicImage(): boolean {
    return (
      !!this.publicQuestion?.question.imageTimestamp ||
      !!this.publicQuestion?.question.link
    );
  }

  get isActive(): boolean {
    if (this.moderatedQuestionFlow) {
      if (this.task) return timerService.isActive(this.task);
    } else {
      return this.activeQuestionPhase === QuestionPhase.ANSWER;
    }
    return false;
  }

  get hasVotes(): boolean {
    return !!this.voteResult.find((vote) => vote.ratingSum > 0);
  }

  get showQuestion(): boolean {
    return (
      this.isActive ||
      (this.questionState === QuestionState.RESULT_ANSWER && this.hasVotes) ||
      (this.questionState === QuestionState.RESULT_ANSWER &&
        this.moderatedQuestionFlow)
    );
  }

  get showExplanation(): boolean {
    return (
      this.questionState === QuestionState.RESULT_EXPLANATION && this.hasVotes
    );
  }

  get showStatistics(): boolean {
    return this.questionState === QuestionState.RESULT_STATISTICS;
  }

  get remainingTime(): number {
    const time = timerService.getRemainingTime(this.task);
    if (time) return time;
    return QuizStateProperty[QuestionState.ACTIVE_WAIT_FOR_VOTE].time;
  }

  get activeQuestionId(): string | null {
    if (
      this.task &&
      this.task.parameter &&
      (this.moderatedQuestionFlow || this.usePublicQuestion)
    ) {
      const id = this.task.parameter.activeQuestion;
      if (id) return id;
    } else {
      const activeQuestion = this.activeQuestion;
      if (activeQuestion) return activeQuestion.id;
    }
    return '';
  }

  get activeQuestionType(): QuestionType {
    if (this.activeQuestion && this.activeQuestion.parameter.questionType) {
      return this.activeQuestion.parameter.questionType;
    }
    return QuestionType.MULTIPLECHOICE;
  }

  get activeQuestion(): Hierarchy | null {
    if (
      this.task &&
      this.task.parameter &&
      (this.moderatedQuestionFlow || this.usePublicQuestion)
    ) {
      const activeQuestionId = this.task.parameter.activeQuestion;
      const question = this.questions.find(
        (item) => item.question.id === activeQuestionId
      );
      if (question) return question.question;
    } else if (
      this.questions.length > this.activeQuestionIndex &&
      this.activeQuestionIndex >= 0
    ) {
      return this.questions[this.activeQuestionIndex].question;
    }
    return null;
  }

  get activeArea(): TimelineArea {
    if (
      this.task &&
      this.task.parameter &&
      (this.moderatedQuestionFlow || this.usePublicQuestion)
    ) {
      return this.task.parameter.activeArea;
    }
    return TimelineArea.content;
  }

  get publicAnswers(): Hierarchy[] {
    if (this.publicQuestion) {
      if (this.questionState == QuestionState.ACTIVE_CREATE_QUESTION)
        if (this.statePointer > 0) {
          return this.publicQuestion.answers;
        } else return [];
      return this.publicQuestion.answers;
    }
    return [];
  }

  get publicQuestionIndex(): number {
    if (this.publicQuestion)
      return this.questions.findIndex(
        (question) => question.question.id === this.publicQuestion?.question.id
      );
    return -1;
  }

  get hasAnswer(): boolean {
    if (
      this.activeQuestion &&
      Object.hasOwn(this.activeQuestion.parameter, 'hasAnswer')
    )
      return this.activeQuestion.parameter.hasAnswer;
    return this.moduleQuestionnaireType !== QuestionnaireType.SURVEY;
  }

  get questionnaireType(): QuestionnaireType {
    if (this.hasAnswer) return QuestionnaireType.QUIZ;
    return QuestionnaireType.SURVEY;
  }
  //#endregion get / set

  taskCash!: cashService.SimplifiedCashEntry<Task>;
  @Watch('taskId', { immediate: true })
  async onTaskIdChanged(): Promise<void> {
    this.deregisterAll();
    this.task = null;
    this.taskCash = taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      this.authHeaderTyp,
      5
    );
    hierarchyService.registerGetQuestions(
      this.taskId,
      this.updateQuestions,
      this.authHeaderTyp,
      20
    );
  }

  publicImage: string | null = null;
  @Watch('publicQuestion.question.image', { immediate: true })
  @Watch('publicQuestion.question.link', { immediate: true })
  publicImageChanged(): void {
    if (this.publicQuestion?.question.imageTimestamp) {
      until(() => !!this.publicQuestion?.question.image).then(() => {
        if (this.publicQuestion?.question.image)
          this.publicImage = this.publicQuestion.question.image;
      });
    } else if (this.publicQuestion?.question.link)
      this.publicImage = this.publicQuestion?.question.link;
    else this.publicImage = null;
  }

  //eslint-disable-next-line @typescript-eslint/no-unused-vars
  finishedAnswer(answer: Hierarchy): boolean {
    if (
      this.publicQuestion &&
      this.questionnaireType === QuestionnaireType.QUIZ
    ) {
      if (this.questionState == QuestionState.RESULT_ANSWER) {
        return true;
      }
    }
    return false;
  }

  highlightAnswer(answer: Hierarchy): boolean {
    if (
      this.publicQuestion &&
      this.questionnaireType === QuestionnaireType.QUIZ
    ) {
      if (this.questionState == QuestionState.RESULT_ANSWER) {
        return answer.parameter.isCorrect;
      }
    }
    return false;
  }

  highlightAnswerTemporarily(answer: Hierarchy): boolean {
    if (
      this.publicQuestion &&
      this.questionnaireType === QuestionnaireType.QUIZ
    ) {
      if (this.questionState == QuestionState.ACTIVE_LAST_CHANGE) {
        const index = this.publicQuestion.answers.indexOf(answer);
        return index === this.statePointer;
      }
    }
    return false;
  }

  resultCash: cashService.SimplifiedCashEntry<VoteResultDetail[]> | null = null;
  resultParentCash: cashService.SimplifiedCashEntry<VoteResult[]> | null = null;
  resultHierarchyCash: cashService.SimplifiedCashEntry<Hierarchy[]> | null =
    null;
  @Watch('activeQuestion', { immediate: true })
  async onActiveQuestionChanged(
    newValue: Hierarchy | null,
    oldValue: Hierarchy | null
  ): Promise<void> {
    const newQuestionResultStorage =
      getQuestionResultStorageFromHierarchy(newValue);
    const oldQuestionResultStorage =
      getQuestionResultStorageFromHierarchy(oldValue);
    if (
      newQuestionResultStorage !== oldQuestionResultStorage ||
      newValue?.id !== oldValue?.id ||
      (newValue === null && oldValue === undefined)
    ) {
      cashService.deregisterAllGet(this.updateParentVotes);
      cashService.deregisterAllGet(this.updateVotes);
      cashService.deregisterAllGet(this.updateHierarchyResult);
      this.resultParentCash = null;
      this.resultCash = null;
      this.resultHierarchyCash = null;
      if (!newValue?.id) {
        this.resultParentCash = votingService.registerGetParentResult(
          this.taskId,
          this.updateParentVotes,
          this.authHeaderTyp,
          20
        );
      } else {
        if (newQuestionResultStorage === QuestionResultStorage.VOTING) {
          this.resultCash = votingService.registerGetHierarchyResultDetail(
            newValue?.id,
            this.updateVotes,
            this.authHeaderTyp,
            20
          );
        } else {
          this.resultHierarchyCash = hierarchyService.registerGetList(
            this.taskId,
            newValue?.id,
            this.updateHierarchyResult,
            this.authHeaderTyp,
            20
          );
        }
      }
    }
  }

  updateVotes(votes: VoteResult[]): void {
    if (
      this.activeQuestionId &&
      getQuestionResultStorageFromQuestionType(this.activeQuestionType) ===
        QuestionResultStorage.VOTING
    ) {
      this.voteResult = votes;
    }
  }

  updateParentVotes(votes: VoteResult[]): void {
    if (!this.activeQuestionId) {
      this.voteResult = hierarchyService.getParentResult(
        votes,
        this.questionHierarchy
      );
    }
  }

  trackingResult: TaskParticipantIterationStep[] = [];
  updateFinalResult(result: TaskParticipantIterationStep[]): void {
    this.trackingResult = result;
  }

  get detailVotingResult(): VoteResult[] {
    if (
      this.showQuestionWinStatistic &&
      this.trackingResult.length > 0 &&
      this.questions.length > 0
    ) {
      const result: VoteResult[] = [];
      for (const step of this.trackingResult) {
        const question = this.questions.find(
          (q) => q.question.id === step.ideaId
        );
        if (question) {
          const idea = { ...question.question };
          idea.parameter = { ...idea.parameter };
          idea.parameter.isCorrect =
            step.state === TaskParticipantIterationStepStatesType.CORRECT;
          const exists = result.find(
            (exist) =>
              exist.idea.id === idea.id &&
              exist.idea.parameter.isCorrect === idea.parameter.isCorrect
          );
          if (exists) exists.countParticipant++;
          else {
            result.push({
              idea: idea,
              ratingSum: 1,
              detailRatingSum: 1,
              countParticipant: 1,
            });
          }
        }
      }
      return result;
    }
    return this.voteResult;
  }

  questionHierarchy: Hierarchy[] = [];
  updateQuestions(questions: Hierarchy[]): void {
    this.questionHierarchy = questions;
    if (!this.activeQuestion?.id) {
      this.voteResult = [
        ...hierarchyService.getParentResult(
          this.voteResult,
          this.questionHierarchy
        ),
      ];
    }
    this.questions = hierarchyService.convertToQuestions(questions);
    this.updatePublicQuestion();
  }

  updatePublicQuestion(): void {
    let newPublicQuestion: Question | null = null;
    const activeQuestionId = this.activeQuestionId;
    if (this.moderatedQuestionFlow || this.usePublicQuestion) {
      const publicQuestion: Question | undefined = this.questions.find(
        (question) => question.question.id === activeQuestionId
      );
      newPublicQuestion = publicQuestion ? publicQuestion : null;
    } else if (
      this.activeQuestionIndex >= 0 &&
      this.activeQuestionIndex < this.questions.length
    ) {
      newPublicQuestion = this.questions[this.activeQuestionIndex];
    }
    const hasNewQuestion =
      newPublicQuestion?.question.id !== this.publicQuestion?.question.id;
    if (hasNewQuestion) {
      if (this.publicQuestion?.question.id) {
        this.deregisterGetAnswers([this.publicQuestion?.question.id]);
      }
      this.voteResult = [];
      this.publicQuestion = newPublicQuestion;
      if (newPublicQuestion?.question.id) {
        hierarchyService.registerGetList(
          this.taskId,
          newPublicQuestion?.question.id,
          this.updateAnswers,
          this.authHeaderTyp,
          60
        );
      }
      setTimeout(() => {
        this.initQuestionState();
      }, 5 * 1000);
    }
    this.$emit('changePublicQuestion', this.activeQuestion);
  }

  deregisterGetAnswers(questions: string[] | null = null): void {
    if (!questions)
      questions = this.questions
        .filter((q) => q.question.id)
        .map((q) => q.question.id as string);
    questions.forEach(async (question) => {
      hierarchyService.deregisterGetList(
        this.taskId,
        question,
        this.updateAnswers
      );
    });
  }

  updateAnswers(answers: Hierarchy[]): void {
    if (answers.length > 0) {
      const question = this.questions.find(
        (question) => question.question.id === answers[0].parentId
      );
      if (question) {
        const questionResultStorage: QuestionResultStorage =
          getQuestionResultStorageFromQuestionType(question.questionType);
        if (questionResultStorage === QuestionResultStorage.VOTING) {
          question.answers = answers;
        }
      }
    }
  }

  updateHierarchyResult(answers: Hierarchy[]): void {
    if (
      this.activeQuestionId &&
      getQuestionResultStorageFromQuestionType(this.activeQuestionType) ===
        QuestionResultStorage.CHILD_HIERARCHY
    ) {
      this.voteResult = hierarchyService.getHierarchyResult(
        answers,
        this.activeQuestion?.parameter.correctValue
      );
    }
  }

  updateTask(task: Task): void {
    const init = !this.task;
    if (
      this.task?.parameter?.activeQuestion !== task?.parameter?.activeQuestion
    ) {
      const oldQuizState = this.questionState;
      this.questionState = this.moderatedQuestionFlow
        ? QuestionState.ACTIVE_CREATE_QUESTION
        : this.defaultQuestionState;
      if (oldQuizState !== this.questionState) {
        this.$emit('changeQuizState', this.questionState);
      }
    }
    this.task = task;
    const module = task.modules.find((module) => moduleNameValid(module.name));
    if (module) {
      this.moduleQuestionnaireType =
        QuestionnaireType[module.parameter.questionType.toUpperCase()];
      this.moderatedQuestionFlow = module.parameter.moderatedQuestionFlow;
      if (this.moderatedQuestionFlow && this.taskCash) {
        this.taskCash.updateCallback(this.updateTask, 1);
      } else {
        this.taskCash.updateCallback(this.updateTask, 5);
      }
    }
    if (init) {
      this.initQuestionState();
      cashService.deregisterAllGet(this.updateFinalResult);
      if (this.moduleQuestionnaireType !== QuestionnaireType.SURVEY) {
        taskParticipantService.registerGetIterationStepFinalList(
          this.taskId,
          this.updateFinalResult,
          this.authHeaderTyp,
          20
        );
      }
    }
    this.updatePublicQuestion();
  }

  private initQuestionState(): void {
    const oldQuizState = this.questionState;
    if (this.isActive) {
      this.questionState = QuestionState.ACTIVE_CREATE_QUESTION;
    } else {
      this.questionState =
        this.moderatedQuestionFlow &&
        this.questionState !== QuestionState.RESULT_STATISTICS &&
        this.activeQuestionId
          ? QuestionState.RESULT_ANSWER
          : QuestionState.RESULT_STATISTICS;
    }
    if (oldQuizState !== this.questionState) {
      this.$emit('changeQuizState', this.questionState);
    }
  }

  @Watch('activeQuestionIndex', { immediate: true })
  onActiveQuestionIndexChanged(): void {
    this.updatePublicQuestion();
    this.checkState(true);
  }

  oldIsActive = false;
  emitedPublicQuestionId: string | null = null;
  async checkState(staticUpdate = false): Promise<void> {
    const oldQuizState = this.questionState;
    const oldQuestionId = this.activeQuestionId;
    await until(() => !!this.task);
    const newIsActive = this.isActive;
    if (
      staticUpdate ||
      this.oldIsActive !== newIsActive ||
      oldQuestionId !== this.activeQuestionId
      //|| this.showStatistics
    ) {
      this.statePointer = -1;
      this.initQuestionState();
    }
    this.oldIsActive = newIsActive;

    switch (this.questionState) {
      case QuestionState.ACTIVE_CREATE_QUESTION:
        this.statePointer++;
        if (
          this.publicQuestion &&
          this.statePointer > this.publicQuestion.answers.length
        ) {
          this.questionState = QuestionState.ACTIVE_WAIT_FOR_VOTE;
        }
        break;
      case QuestionState.ACTIVE_WAIT_FOR_VOTE:
        if (
          this.moderatedQuestionFlow &&
          this.remainingTime <
            QuizStateProperty[QuestionState.ACTIVE_WAIT_FOR_VOTE].time
        ) {
          this.questionState = QuestionState.ACTIVE_LAST_CHANGE;
          this.statePointer = 0;
        }
        break;
      case QuestionState.ACTIVE_LAST_CHANGE:
        if (this.publicQuestion) {
          const answerCount = this.publicQuestion.answers.length;
          if (answerCount > 0) {
            this.statePointer = (this.statePointer + 1) % answerCount;
          }
        }
        break;
      case QuestionState.DONE_THANKS:
        this.statePointer++;
        if (
          this.statePointer > QuizStateProperty[QuestionState.DONE_THANKS].time
        ) {
          this.questionState = QuestionState.DONE_WAIT;
        }
        break;
      case QuestionState.DONE_WAIT:
        break;
      case QuestionState.RESULT_ANSWER:
        this.statePointer++;
        if (
          this.statePointer >
          QuizStateProperty[QuestionState.RESULT_ANSWER].time
        ) {
          this.statePointer = 0;
          this.questionState = this.publicQuestion?.question.description
            ? QuestionState.RESULT_EXPLANATION
            : QuestionState.RESULT_STATISTICS;
        }
        break;
      case QuestionState.RESULT_EXPLANATION:
        this.statePointer++;
        if (
          this.statePointer >
          QuizStateProperty[QuestionState.RESULT_EXPLANATION].time
        ) {
          this.statePointer = 0;
          this.questionState = QuestionState.RESULT_STATISTICS;
        }
        break;
      case QuestionState.RESULT_STATISTICS:
        if (this.statePointer === 0) {
          if (this.resultCash) this.resultCash.refreshData();
          if (this.resultHierarchyCash) this.resultHierarchyCash.refreshData();
          if (this.resultParentCash) this.resultParentCash.refreshData();
        }
        this.statePointer++;
        break;
    }

    if (oldQuizState !== this.questionState) {
      this.$emit('changeQuizState', this.questionState);
    }

    if (staticUpdate || oldQuestionId !== this.activeQuestionId) {
      this.$emit('changePublicQuestion', this.activeQuestion);
    }

    const isVotingQuestion =
      getQuestionResultStorageFromQuestionType(this.activeQuestionType) ===
      QuestionResultStorage.VOTING;
    if (
      this.publicQuestion &&
      (!isVotingQuestion || this.publicAnswers.length > 0)
    ) {
      this.emitedPublicQuestionId = this.publicQuestion.question.id;
      if (isVotingQuestion) {
        this.$emit(
          'changePublicAnswers',
          this.publicAnswers.map((answer) => {
            return {
              answer: answer,
              isHighlightedTemporarily: this.highlightAnswerTemporarily(answer),
              isHighlighted: this.highlightAnswer(answer),
              isFinished: this.finishedAnswer(answer),
            };
          })
        );
      } else {
        this.$emit('changePublicAnswers', []);
      }
    }
  }

  async mounted(): Promise<void> {
    this.startInterval();
  }

  startInterval(): void {
    this.interval = setInterval(this.checkState, this.intervalTime);
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateVotes);
    cashService.deregisterAllGet(this.updateHierarchyResult);
    cashService.deregisterAllGet(this.updateParentVotes);
    cashService.deregisterAllGet(this.updateFinalResult);
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateQuestions);
    cashService.deregisterAllGet(this.updateAnswers);
  }

  unmounted(): void {
    this.deregisterAll();
    clearInterval(this.interval);
  }
}
</script>

<style lang="scss" scoped>
.el-space::v-deep(.el-space__item) {
  width: 100%;
}

.question {
  //border: 1px solid var(--color-primary);
  //border-radius: var(--border-radius);
  //padding: 1rem;
  font-weight: var(--font-weight-semibold);
  font-size: var(--font-size-xlarge);
  text-transform: uppercase;
  //text-align: center;
  color: var(--color-primary);
  margin: 1em 0;
}

.explanation {
  width: 100%;
  text-align: justify;
  white-space: pre-line;
}

.result {
  font-size: 1.5rem;
  margin-bottom: 1rem;
}

.el-steps {
  margin-bottom: 2rem;
}

.question-image {
  max-height: 30vh;
  object-fit: contain;
}
</style>
