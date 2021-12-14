<template>
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
  <div v-if="showStatistics">
    <QuizResult :voteResult="voteResult" :change="false" />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import Vue3ChartJs from '@j-t-mcc/vue3-chartjs';
import { Task } from '@/types/api/Task';
import { Question } from '@/modules/information/quiz/types/Question';
import { VoteResult } from '@/types/api/Vote';
import {
  QuizState,
  QuizStateProperty,
} from '@/modules/information/quiz/types/QuizState';
import * as timerService from '@/services/timer-service';
import { Hierarchy } from '@/types/api/Hierarchy';
import * as taskService from '@/services/task-service';
import * as hierarchyService from '@/services/hierarchy-service';
import * as votingService from '@/services/voting-service';
import QuizResult from '@/modules/information/quiz/organisms/QuizResult.vue';

export interface PublicAnswerData {
  answer: Hierarchy;
  isHighlighted: boolean;
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
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  readonly intervalTime = 1000;
  interval!: any;
  task: Task | null = null;
  questions: Question[] = [];
  publicQuestion: Question | null = null;
  voteResult: VoteResult[] = [];

  quizState: QuizState = QuizState.ACTIVE_CREATE_QUESTION;
  statePointer = 0;

  get isActive(): boolean {
    if (this.task) return timerService.isActive(this.task);
    return false;
  }

  get hasVotes(): boolean {
    return !!this.voteResult.find((vote) => vote.ratingSum > 0);
  }

  get showQuestion(): boolean {
    return (
      this.isActive ||
      (this.quizState === QuizState.RESULT_ANSWER && this.hasVotes)
    );
  }

  get showExplanation(): boolean {
    return this.quizState === QuizState.RESULT_EXPLANATION && this.hasVotes;
  }

  get showStatistics(): boolean {
    return this.quizState === QuizState.RESULT_STATISTICS && this.hasVotes;
  }

  get remainingTime(): number {
    const time = timerService.getRemainingTime(this.task);
    if (time) return time;
    return 0;
  }

  get activeQuestionId(): string | null {
    if (this.task && this.task.parameter) {
      return this.task.parameter.activeQuestion;
    }
    return '';
  }

  get publicAnswers(): Hierarchy[] {
    if (this.publicQuestion) {
      if (this.quizState == QuizState.ACTIVE_CREATE_QUESTION)
        if (this.statePointer > 0)
          return this.publicQuestion.answers.slice(0, this.statePointer - 1);
        else return [];
      return this.publicQuestion.answers;
    }
    return [];
  }

  highlightAnswer(answer: Hierarchy): boolean {
    if (this.publicQuestion) {
      if (this.quizState == QuizState.ACTIVE_LAST_CHANGE) {
        const index = this.publicQuestion.answers.indexOf(answer);
        return index === this.statePointer;
      } else if (this.quizState == QuizState.RESULT_ANSWER) {
        return answer.parameter.isCorrect;
      }
    }
    return false;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getTask().then(() => {
      if (this.isActive) {
        this.quizState = QuizState.ACTIVE_CREATE_QUESTION;
      } else {
        this.quizState = QuizState.RESULT_ANSWER;
      }
    });
    this.getHierarchies();
  }

  async getTask(): Promise<void> {
    await taskService
      .getTaskById(this.taskId, this.authHeaderTyp)
      .then((task) => {
        this.task = task;
      });
  }

  async getHierarchies(): Promise<void> {
    if (this.taskId) {
      await hierarchyService
        .getList(this.taskId, '{parentHierarchyId}', this.authHeaderTyp)
        .then(async (questions) => {
          const result: Question[] = [];
          let publicQuestion: Question | null = null;
          for (const index in questions) {
            const question = questions[index];
            await hierarchyService
              .getList(this.taskId, question.id, this.authHeaderTyp)
              .then((answers) => {
                const item: Question = {
                  question: question,
                  answers: answers,
                };
                result.push(item);
                if (question.id == this.activeQuestionId) {
                  publicQuestion = item;
                }
              });
          }
          this.questions = result;
          if (publicQuestion) this.publicQuestion = publicQuestion;
          await this.getVotes();
        });
    }
  }

  async getVotes(): Promise<void> {
    if (this.activeQuestionId) {
      await votingService
        .getHierarchyResult(this.activeQuestionId, this.authHeaderTyp)
        .then((votes) => {
          this.voteResult = votes;
        });
    } else {
      this.voteResult = [];
    }
  }

  async checkState(): Promise<void> {
    const oldQuizState = this.quizState;
    const oldState = this.isActive;
    const oldQuestionId = this.activeQuestionId;
    await this.getTask();
    if (oldState !== this.isActive || oldQuestionId !== this.activeQuestionId) {
      this.statePointer = -1;
      if (this.isActive) {
        this.quizState = QuizState.ACTIVE_CREATE_QUESTION;
      } else {
        this.quizState = QuizState.RESULT_ANSWER;
      }
      await this.getHierarchies();
    }

    switch (this.quizState) {
      case QuizState.ACTIVE_CREATE_QUESTION:
        this.statePointer++;
        if (
          this.publicQuestion &&
          this.statePointer > this.publicQuestion.answers.length
        ) {
          this.quizState = QuizState.ACTIVE_WAIT_FOR_VOTE;
        }
        break;
      case QuizState.ACTIVE_WAIT_FOR_VOTE:
        if (
          this.remainingTime <
          QuizStateProperty[QuizState.ACTIVE_WAIT_FOR_VOTE].time
        ) {
          this.quizState = QuizState.ACTIVE_LAST_CHANGE;
          this.statePointer = 0;
        }
        break;
      case QuizState.ACTIVE_LAST_CHANGE:
        if (this.publicQuestion) {
          this.statePointer =
            (this.statePointer + 1) % this.publicQuestion.answers.length;
        }
        break;
      case QuizState.DONE_THANKS:
        this.statePointer++;
        if (this.statePointer > QuizStateProperty[QuizState.DONE_THANKS].time) {
          this.quizState = QuizState.DONE_WAIT;
        }
        break;
      case QuizState.DONE_WAIT:
        break;
      case QuizState.RESULT_ANSWER:
        this.statePointer++;
        if (
          this.statePointer > QuizStateProperty[QuizState.RESULT_ANSWER].time
        ) {
          this.statePointer = 0;
          this.quizState = this.publicQuestion?.question.description
            ? QuizState.RESULT_EXPLANATION
            : QuizState.RESULT_STATISTICS;
        }
        break;
      case QuizState.RESULT_EXPLANATION:
        this.statePointer++;
        if (
          this.statePointer >
          QuizStateProperty[QuizState.RESULT_EXPLANATION].time
        ) {
          this.statePointer = 0;
          this.quizState = QuizState.RESULT_STATISTICS;
        }
        break;
      case QuizState.RESULT_STATISTICS:
        break;
    }

    if (oldQuizState !== this.quizState) {
      this.$emit('changeQuizState', this.quizState);
    }

    if (oldQuestionId !== this.activeQuestionId) {
      this.$emit('changePublicQuestion', this.activeQuestionId);
    }

    if (this.publicQuestion) {
      this.$emit(
        'changePublicAnswers',
        this.publicAnswers.map((answer) => {
          return {
            answer: answer,
            isHighlighted: this.highlightAnswer(answer),
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
  border: 1px solid var(--color-primary);
  border-radius: var(--border-radius);
  padding: 1rem;
  font-weight: var(--font-weight-semibold);
  text-transform: uppercase;
  text-align: center;
  color: var(--color-primary);
  margin: 1em 0;
}

.explanation {
  width: 100%;
  text-align: justify;
  white-space: pre-line;
}
</style>