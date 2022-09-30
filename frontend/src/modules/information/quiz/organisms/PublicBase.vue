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
  <div v-if="showQuestion" class="fill">
    <el-space
      direction="vertical"
      class="fill"
      v-if="
        publicQuestion &&
        publicQuestion.question &&
        (statePointer >= 0 || !isActive)
      "
    >
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
      :questionType="questionType"
      :update="true"
    />
  </div>
  <div v-if="showStatistics && !publicQuestion">
    <QuizResult
      :voteResult="voteResult"
      resultColumn="countParticipant"
      :change="false"
      :questionType="QuestionType.SURVEY"
      :update="true"
    />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import Vue3ChartJs from '@j-t-mcc/vue3-chartjs';
import { Task } from '@/types/api/Task';
import {
  getQuestionResultStorageFromHierarchy,
  getQuestionTypeFromHierarchy,
  Question,
  QuestionResultStorage,
} from '@/modules/information/quiz/types/Question';
import { VoteResult } from '@/types/api/Vote';
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

export interface PublicAnswerData {
  answer: Hierarchy;
  isHighlighted: boolean;
  isHighlightedTemporarily: boolean;
  isFinished: boolean;
}

@Options({
  components: {
    Vue3ChartJs,
    QuizResult,
  },
  emits: ['changePublicAnswers', 'changePublicQuestion', 'changeQuizState'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicBase extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: true }) readonly usePublicQuestion!: boolean;
  @Prop({ default: -1 }) readonly activeQuestionIndex!: number;
  @Prop({ default: QuestionPhase.RESULT })
  readonly activeQuestionPhase!: QuestionPhase;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  readonly intervalTime = 1000;
  interval!: any;
  task: Task | null = null;
  questions: Question[] = [];
  publicQuestion: Question | null = null;
  voteResult: VoteResult[] = [];
  questionType: QuestionnaireType = QuestionnaireType.QUIZ;
  moderatedQuestionFlow = true;

  questionState: QuestionState = QuestionState.ACTIVE_CREATE_QUESTION;
  statePointer = 0;

  QuestionType = QuestionnaireType;

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
      (this.questionState === QuestionState.RESULT_ANSWER && this.hasVotes)
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
    return 0;
  }

  getActiveQuestionId(): string | null {
    if (
      this.task &&
      this.task.parameter &&
      (this.moderatedQuestionFlow || this.usePublicQuestion)
    ) {
      return this.task.parameter.activeQuestion;
    } else {
      const activeQuestion = this.getActiveQuestion();
      if (activeQuestion) return activeQuestion.id;
    }
    return '';
  }

  getActiveQuestion(): Hierarchy | null {
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

  get publicAnswers(): Hierarchy[] {
    if (this.publicQuestion) {
      if (this.questionState == QuestionState.ACTIVE_CREATE_QUESTION)
        if (this.statePointer > 0) {
          //return this.publicQuestion.answers.slice(0, this.statePointer - 1);
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

  finishedAnswer(answer: Hierarchy): boolean {
    if (this.publicQuestion && this.questionType === QuestionnaireType.QUIZ) {
      if (this.questionState == QuestionState.RESULT_ANSWER) {
        return true;
      }
    }
    return false;
  }

  highlightAnswer(answer: Hierarchy): boolean {
    if (this.publicQuestion && this.questionType === QuestionnaireType.QUIZ) {
      if (this.questionState == QuestionState.RESULT_ANSWER) {
        return answer.parameter.isCorrect;
      }
    }
    return false;
  }

  highlightAnswerTemporarily(answer: Hierarchy): boolean {
    if (this.publicQuestion && this.questionType === QuestionnaireType.QUIZ) {
      if (this.questionState == QuestionState.ACTIVE_LAST_CHANGE) {
        const index = this.publicQuestion.answers.indexOf(answer);
        return index === this.statePointer;
      }
    }
    return false;
  }

  @Watch('taskId', { immediate: true })
  async onTaskIdChanged(): Promise<void> {
    await this.getTask().then(() => {
      this.initQuestionState();
    });
    this.getHierarchies();
  }

  private initQuestionState(): void {
    if (this.isActive) {
      this.questionState = QuestionState.ACTIVE_CREATE_QUESTION;
    } else {
      this.questionState = this.moderatedQuestionFlow
        ? QuestionState.RESULT_ANSWER
        : QuestionState.RESULT_STATISTICS;
    }
  }

  async getTask(): Promise<void> {
    await taskService
      .getTaskById(this.taskId, this.authHeaderTyp)
      .then((task) => {
        this.task = task;
        const module = task.modules.find((module) =>
          moduleNameValid(module.name)
        );
        if (module) {
          this.questionType =
            QuestionnaireType[module.parameter.questionType.toUpperCase()];
          this.moderatedQuestionFlow = module.parameter.moderatedQuestionFlow;
        }
      });
  }

  async getHierarchies(): Promise<void> {
    if (this.taskId) {
      const activeQuestionId = this.getActiveQuestionId();
      await hierarchyService
        .getList(this.taskId, '{parentHierarchyId}', this.authHeaderTyp)
        .then(async (questions) => {
          const result: Question[] = [];
          let publicQuestion: Question | null = null;
          for (const index in questions) {
            const question = questions[index];
            const questionResultStorage: QuestionResultStorage =
              getQuestionResultStorageFromHierarchy(question);
            if (questionResultStorage === QuestionResultStorage.VOTING) {
              await hierarchyService
                .getList(this.taskId, question.id, this.authHeaderTyp)
                .then((answers) => {
                  const item: Question = {
                    questionType: getQuestionTypeFromHierarchy(question),
                    question: question,
                    answers: answers,
                  };
                  result.push(item);
                  if (question.id === activeQuestionId) {
                    publicQuestion = item;
                  }
                });
            } else {
              const item: Question = {
                questionType: getQuestionTypeFromHierarchy(question),
                question: question,
                answers: [],
              };
              result.push(item);
              if (question.id === activeQuestionId) {
                publicQuestion = item;
              }
            }
          }
          this.questions = result;
          if (this.moderatedQuestionFlow || this.usePublicQuestion) {
            this.publicQuestion = publicQuestion;
          } else if (
            this.activeQuestionIndex >= 0 &&
            this.activeQuestionIndex < this.questions.length
          ) {
            this.publicQuestion = this.questions[this.activeQuestionIndex];
          }
          await this.getVotes();
        });
    }
  }

  async getVotes(): Promise<void> {
    const activeQuestionId = this.getActiveQuestionId();
    if (activeQuestionId) {
      const questionResultStorage: QuestionResultStorage =
        getQuestionResultStorageFromHierarchy(this.getActiveQuestion());
      if (questionResultStorage === QuestionResultStorage.VOTING) {
        await votingService
          .getHierarchyResult(activeQuestionId, this.authHeaderTyp)
          .then((votes) => {
            this.voteResult = votes;
          });
      } else {
        await hierarchyService
          .getHierarchyResult(this.taskId, activeQuestionId, this.authHeaderTyp)
          .then((votes) => {
            this.voteResult = votes;
          });
      }
    } else {
      await hierarchyService
        .getParentResult(this.taskId, this.authHeaderTyp)
        .then((votes) => {
          this.voteResult = votes;
        });
      //this.voteResult = [];
    }
  }

  @Watch('activeQuestionIndex', { immediate: true })
  onActiveQuestionIndexChanged(): void {
    this.checkState(true);
  }

  async checkState(staticUpdate = false): Promise<void> {
    const oldQuizState = this.questionState;
    const oldState = this.isActive;
    const oldQuestionId = this.getActiveQuestionId();
    await this.getTask();
    if (
      staticUpdate ||
      oldState !== this.isActive ||
      oldQuestionId !== this.getActiveQuestionId() ||
      this.showStatistics
    ) {
      this.statePointer = -1;
      this.initQuestionState();
      await this.getHierarchies();
    }

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
        break;
    }

    if (oldQuizState !== this.questionState) {
      this.$emit('changeQuizState', this.questionState);
    }

    if (staticUpdate || oldQuestionId !== this.getActiveQuestionId()) {
      this.$emit('changePublicQuestion', this.getActiveQuestion());
    }

    if (this.publicQuestion) {
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
    }
  }

  async mounted(): Promise<void> {
    this.startInterval();
  }

  startInterval(): void {
    this.interval = setInterval(this.checkState, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }
}
</script>

<style lang="scss" scoped>
.el-space::v-deep {
  .el-space__item {
    width: 100%;

    button {
      width: 100%;
    }
  }
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
</style>
