<template>
  <h1 v-if="!publicQuestion" class="fade-down anim-delay-md anim-slow">{{ task?.name }}</h1>

  <div class="question-container fade-down anim-slow">
    <h1 v-if="publicQuestion">{{ publicQuestion.question.order + 1 }}. {{ publicQuestion.question.keywords }}</h1>
    <div v-if="publicQuestion" class="countdown" v-bind:key="publicQuestion.question.id"></div>
  </div>

  <div v-if="!publicQuestion" class="timer fade-right anim-delay-xl anim-slow">
    <p v-if="task?.remainingTime && task.remainingTime > 0">Answer on your device!</p>
    <p v-else>Time's up!<br><span>Waiting for the moderator</span></p>
    <div v-if="task?.remainingTime && task.remainingTime > 0" class="timer__container">
      <div class="timer__container__icon">
        <img src="@/assets/icons/svg/clock.svg" alt="timer" >
      </div>
      <TimerProgress class="timer__container__text" :entity="task" :isQuiz="true"/>
    </div>
  </div>
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
      <img
        v-if="publicQuestion.question.image"
        :src="publicQuestion.question.image"
        class="question-image"
        alt=""
      />
      <img
        v-if="publicQuestion.question.link && !publicQuestion.question.image"
        :src="publicQuestion.question.link"
        class="question-image"
        alt=""
      />
      <div class="question">
        {{ publicQuestion.question.keywords }}
      </div>
      <slot name="answers"></slot>
    </el-space>
  </div>
  <div  v-if="showStatistics && publicQuestion" class="fade-right anim-slow">
    <!-- <div
      v-if="
        publicQuestion &&
        publicQuestion.question &&
        (showExplanation || showStatistics)
      "
      class="explanation"
    >
      {{ publicQuestion.question.description }}
    </div> -->
    <div class="answers">
      <p v-for="answer in publicQuestion.answers" v-bind:key="answer.id" 
      :class="{
          correct: answer.parameter.isCorrect,
          wrong: !answer.parameter.isCorrect,
      }">
        {{ answer.keywords }}
      </p>
    </div>
  </div>
  <p class="participants fade-up anim-delay-2xl anim-slow">
    {{ task?.participantCount }} participants</p>
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
  QuestionType,
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
  questionnaireType: QuestionnaireType = QuestionnaireType.QUIZ;
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
    return QuizStateProperty[QuestionState.ACTIVE_WAIT_FOR_VOTE].time;
  }

  get activeQuestionId(): string | null {
    if (
      this.task &&
      this.task.parameter &&
      (this.moderatedQuestionFlow || this.usePublicQuestion)
    ) {
      return this.task.parameter.activeQuestion;
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
      this.questionState =
        this.moderatedQuestionFlow &&
        this.questionState !== QuestionState.RESULT_STATISTICS
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
          this.questionnaireType =
            QuestionnaireType[module.parameter.questionType.toUpperCase()];
          this.moderatedQuestionFlow = module.parameter.moderatedQuestionFlow;
        }
      });
  }

  async getHierarchies(): Promise<void> {
    if (this.taskId) {
      const activeQuestionId = this.activeQuestionId;
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
    const activeQuestionId = this.activeQuestionId;
    if (activeQuestionId) {
      const questionResultStorage: QuestionResultStorage =
        getQuestionResultStorageFromHierarchy(this.activeQuestion);
      if (questionResultStorage === QuestionResultStorage.VOTING) {
        await votingService
          .getHierarchyResult(activeQuestionId, this.authHeaderTyp)
          .then((votes) => {
            this.voteResult = votes;
          });
      } else {
        await hierarchyService
          .getHierarchyResult(
            this.taskId,
            activeQuestionId,
            this.activeQuestion?.parameter.correctValue,
            this.authHeaderTyp
          )
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
    const oldQuestionId = this.activeQuestionId;
    await this.getTask();
    if (
      staticUpdate ||
      oldState !== this.isActive ||
      oldQuestionId !== this.activeQuestionId ||
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

    if (staticUpdate || oldQuestionId !== this.activeQuestionId) {
      this.$emit('changePublicQuestion', this.activeQuestion);
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
  text-align: center;
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

h1{
  font-weight: bold;
  font-size: 1.5rem;
}

.timer{
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;

  p{
    font-size: 1.2rem;
    font-weight: 600;
    text-align: center;

    span{
      font-size: 1rem;
      opacity: 0.7;
      font-weight: normal;
    }
  }

  &__container{
    display: flex;
    align-items: center;
    gap: 1rem;

    &__icon{
      background-color: white;
      width: fit-content;
      height: fit-content;
      display: flex;
      justify-content: center;
      align-items: center;
      border-radius: 50%;
      padding: 0.8rem;

      img{
        height: 1.8rem;
      }
    }

    &__text{
      font-weight: bold;
      font-style: italic;
      font-size: 2.5rem;
      width: 7rem;
    }
  }
}

.participants{
  font-weight: 500;
  color: rgba(255,255,255,0.7);
}

.answers{
  max-width: 30vw;
  display: flex;
  justify-content: space-evenly;
  gap: 1vw;
  flex-flow: wrap;
  
  p{
    width: 14vw;
    min-height: 5rem;
    font-size: 1.2rem;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.1);
  }

  .correct{
    animation: rightAnswer 4s 5s ease forwards;
  }

  .wrong{
    opacity: 1;
    animation: wrongAnswer 4s 5s ease forwards;
  }
}

@keyframes rightAnswer {
  0%{
    border-color: rgba(255,255,255,0.1);
    background-color: rgba(0,0,0,0.5);
  }
  100%{
    border-color: rgb(0, 222, 0);
    background-color: rgba(1, 94, 1, 0.393);
  }
}

@keyframes wrongAnswer {
  0%{
    opacity: 1;
  }
  100%{
    opacity: 0.6;
  }
}

.question-container{
  display: flex;
  flex-direction: column;
  align-items: center;

  .countdown{
    width: 30vw;
    height: 0.3rem;
    background-color: #01cf9e;
    margin-top: 1rem;
    border-radius: 10px;
    animation: countdown 5s 0.5s forwards ease-in;
  }
}

@keyframes countdown {
  0%{
    width: 30vw;
  }
  100%{
    width: 0;
  }
}
</style>
