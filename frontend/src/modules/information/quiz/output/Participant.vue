<template>
  <ParticipantModuleDefaultContainer :task-id="taskId" :module="moduleName">
    <template v-slot:planet>
      <img
        src="@/assets/illustrations/planets/information.png"
        alt="planet"
        class="module-container__planet"
      />
    </template>
    <div v-if="showQuestion" class="fill">
      <el-space
        direction="vertical"
        class="fill"
        v-if="publicQuestion && (statePointer >= 0 || !isActive)"
      >
        <div class="question">
          {{ publicQuestion.question.keywords }}
        </div>
        <el-button
          v-for="answer in publicAnswers"
          type="primary"
          :key="answer.id"
          class="link"
          :plain="!highlightAnswer(answer)"
          :disabled="!isActive"
          :loading="isSaving(answer.id)"
          v-on:click="changeVote(answer.id)"
        >
          <font-awesome-icon v-if="isAnswerSelected(answer.id)" icon="check" />
          {{ answer.keywords }}
        </el-button>
      </el-space>
    </div>
    <div v-if="showExplanation || showStatistics" class="explanation">
      {{ publicQuestion.question.description }}
    </div>
    <div v-if="showStatistics">
      <vue3-chart-js
        id="resultChart"
        ref="chartRef"
        type="bar"
        :data="chartData"
        :options="{
          animation: {
            duration: 2000,
          },
          scales: {
            x: {
              ticks: {
                color: '#1d2948',
              },
              grid: {
                display: false,
              },
            },
            y: {
              ticks: {
                color: '#1d2948',
                stepSize: 1,
              },
            },
          },
          plugins: {
            legend: {
              labels: {
                color: '#1d2948',
              },
            },
          },
        }"
      />
    </div>
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/ParticipantModuleDefaultContainer.vue';
import * as moduleService from '@/services/module-service';
import { Module } from '@/types/api/Module';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';
import * as hierarchyService from '@/services/hierarchy-service';
import * as votingService from '@/services/voting-service';
import * as timerService from '@/services/timer-service';
import { Question } from '@/modules/information/quiz/types/Question';
import { VoteResult, Vote } from '@/types/api/Vote';
import Vue3ChartJs from '@j-t-mcc/vue3-chartjs';
import { Hierarchy } from '@/types/api/Hierarchy';
import {
  QuizState,
  QuizStateProperty,
} from '@/modules/information/quiz/types/QuizState';

@Options({
  components: {
    ParticipantModuleDefaultContainer,
    Vue3ChartJs,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  module: Module | null = null;
  readonly intervalTime = 1000;
  interval!: any;
  task: Task | null = null;
  questions: Question[] = [];
  publicQuestion: Question | null = null;
  votes: Vote[] = [];
  vote_result: VoteResult[] = [];
  chartData: any = {
    labels: [],
    datasets: [],
  };

  quizState: QuizState = QuizState.ACTIVE_CREATE_QUESTION;
  statePointer = 0;

  isAnswerSelected(answerId: string): boolean {
    return !!this.votes.find((vote) => vote.ideaId == answerId);
  }

  isSavingList: string[] = []; //: { [name: string]: boolean } = {};
  isSaving(answerId: string): boolean {
    return this.isSavingList.includes(answerId);
  }

  async changeVote(answerId: string): Promise<void> {
    if (!this.isSaving(answerId)) {
      this.isSavingList.push(answerId);
      const vote = this.votes.find((vote) => vote.ideaId == answerId);
      if (vote) {
        await votingService.deleteVote(vote.id).then((result) => {
          if (result) {
            const index = this.votes.findIndex(
              (vote) => vote.ideaId == answerId
            );
            this.votes.splice(index, 1);
          }
        });
      } else {
        await votingService
          .postVote(this.taskId, {
            ideaId: answerId,
            rating: 1,
            detailRating: 1,
          })
          .then((vote) => {
            this.votes.push(vote);
          });
      }
      const index = this.isSavingList.indexOf(answerId);
      this.isSavingList.splice(index, 1);
    }
  }

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  get isActive(): boolean {
    if (this.task) return timerService.isActive(this.task);
    return false;
  }

  get showQuestion(): boolean {
    return this.isActive || this.quizState === QuizState.RESULT_ANSWER;
  }

  get showExplanation(): boolean {
    return this.quizState === QuizState.RESULT_EXPLANATION;
  }

  get showStatistics(): boolean {
    return this.quizState === QuizState.RESULT_STATISTICS;
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
        });
    }
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getTask();
    if (this.isActive) {
      this.quizState = QuizState.ACTIVE_CREATE_QUESTION;
    } else {
      this.quizState = QuizState.RESULT_ANSWER;
    }
    this.getHierarchies();
  }

  async getTask(): Promise<void> {
    await taskService.getTaskById(this.taskId).then((task) => {
      this.task = task;
    });
  }

  async getHierarchies(): Promise<void> {
    if (this.taskId) {
      await hierarchyService
        .getList(
          this.taskId,
          '{parentHierarchyId}',
          EndpointAuthorisationType.MODERATOR
        )
        .then(async (questions) => {
          const result: Question[] = [];
          let publicQuestion: Question | null = null;
          for (const index in questions) {
            const question = questions[index];
            await hierarchyService
              .getList(this.taskId, question.id)
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

  get resultData(): any {
    return {
      labels: this.vote_result.map((vote) => vote.idea.keywords),
      datasets: [
        {
          label: (this as any).$t(
            'module.voting.default.publicScreen.chartDataLabel'
          ),
          backgroundColor: this.vote_result.map((vote) =>
            vote.idea.parameter.isCorrect ? '#f1be3a' : '#fe6e5d'
          ),
          data: this.vote_result.map((vote) => vote.detailRatingSum),
        },
      ],
    };
  }

  async getVotes(): Promise<void> {
    if (this.activeQuestionId) {
      await votingService
        .getHierarchyResult(this.activeQuestionId)
        .then((votes) => {
          this.vote_result = votes;
          this.chartData.labels = this.resultData.labels;
          this.chartData.datasets = this.resultData.datasets;
          this.updateChart();
        });
      await votingService
        .getHierarchyVotes(this.activeQuestionId)
        .then((votes) => {
          this.votes = votes;
        });
    } else {
      this.vote_result = [];
      if (this.resultData) {
        this.chartData.labels = this.resultData.labels;
        this.chartData.datasets = this.resultData.datasets;
        await this.updateChart();
      }
    }
  }

  async updateChart(): Promise<void> {
    if (this.$refs.chartRef) {
      const chartRef = this.$refs.chartRef as any;
      chartRef.update();
    }
  }

  async checkState(): Promise<void> {
    const oldState = this.isActive;
    const oldQuestionId = this.activeQuestionId;
    await this.getTask();
    const newState = this.isActive;
    const newQuestionId = this.activeQuestionId;
    if (oldState !== newState || oldQuestionId !== newQuestionId) {
      this.statePointer = -1;
      if (newState) {
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
  }

  async mounted(): Promise<void> {
    this.startIdeaInterval();
  }

  startIdeaInterval(): void {
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
