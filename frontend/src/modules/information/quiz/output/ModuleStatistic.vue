<template>
  <QuizResult
    :voteResult="detailVotingResult"
    resultColumn="countParticipant"
    :change="false"
    :questionnaireType="
      trackingResult.length > 0
        ? moduleQuestionnaireType
        : QuestionnaireType.SURVEY
    "
    :update="true"
  />
  <Highscore
    v-if="moduleQuestionnaireType !== QuestionnaireType.SURVEY"
    :tracking-result="trackingResult"
  />
  <div
    v-for="(question, index) in questions.filter(
      (item) => item.questionType !== QuestionType.INFO
    )"
    :key="question.question.id"
  >
    <h2>
      {{ index + 1 }}.
      {{ question.question.description ?? question.question.keywords }}
    </h2>
    <QuizResult
      :voteResult="getQuestionResult(question.question.id)"
      :update="true"
      :questionnaireType="
        question.question.parameter.hasAnswer
          ? QuestionnaireType.QUIZ
          : QuestionnaireType.SURVEY
      "
      :questionType="question.questionType"
      :show-legend="true"
    />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as cashService from '@/services/cash-service';
import * as taskParticipantService from '@/services/task-participant-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { VoteResult, VoteResultDetail } from '@/types/api/Vote';
import * as hierarchyService from '@/services/hierarchy-service';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import { Hierarchy } from '@/types/api/Hierarchy';
import {
  Question,
  QuestionType,
} from '@/modules/information/quiz/types/Question';
import QuizResult from '@/modules/information/quiz/organisms/QuizResult.vue';
import {
  moduleNameValid,
  QuestionnaireType,
} from '@/modules/information/quiz/types/QuestionnaireType';
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';
import Highscore from '@/modules/information/quiz/organisms/Highscore.vue';
import * as ideaService from '@/services/idea-service';
import { Idea } from '@/types/api/Idea';

@Options({
  components: { QuizResult, Highscore },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModuleStatistic extends Vue {
  @Prop() readonly taskId!: string;
  votes: VoteResult[] = [];
  moduleQuestionnaireType: QuestionnaireType = QuestionnaireType.QUIZ;
  QuestionnaireType = QuestionnaireType;
  QuestionType = QuestionType;
  task: Task | null = null;

  get detailVotingResult(): VoteResult[] {
    if (this.trackingResult.length > 0 && this.questions.length > 0) {
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
              avatarList: [],
            });
          }
        }
      }
      return result;
    }
    return [];
  }

  getQuestionResult(id: string): VoteResult[] {
    const trackingResult = this.trackingResult.filter(
      (data) => data.ideaId === id
    );
    if (trackingResult.length > 0 && this.questions.length > 0) {
      const question = this.questions.find((q) => q.question.id === id);
      if (question) {
        const result: VoteResultDetail[] = [];
        for (const step of trackingResult) {
          if (step.parameter.answer) {
            if (Array.isArray(step.parameter.answer)) {
              const answerList = step.parameter.answer
                .map((itemId) =>
                  this.possibleAnswers.find((idea) => idea.id === itemId)
                )
                .filter((item) => item);
              for (
                let answerIndex = 0;
                answerIndex < answerList.length;
                answerIndex++
              ) {
                const answer = answerList[answerIndex];
                const exists = result.find(
                  (exist) =>
                    exist.idea.id === answer.id &&
                    (question.questionType !== QuestionType.ORDER ||
                      answerIndex === exist.rating)
                );
                if (exists) {
                  exists.countParticipant++;
                  exists.detailRatingSum++;
                  exists.ratingSum++;
                } else {
                  result.push({
                    idea: answer,
                    ratingSum: 1,
                    rating: answerIndex,
                    detailRatingSum: 1,
                    detailRating: 1,
                    countParticipant: 1,
                    avatarList: [],
                  });
                }
              }
            } else {
              const answer = this.possibleAnswers.find(
                (item) =>
                  item.parentId === id &&
                  (item.description ?? item.keywords) ===
                    step.parameter.answer.toString()
              );
              if (answer) {
                answer.parameter.isCorrect =
                  question.question.parameter.correctValue?.toString() ===
                  step.parameter.answer.toString();
                const exists = result.find(
                  (exist) => exist.idea.id === answer.id
                );
                if (exists) {
                  exists.countParticipant++;
                  exists.detailRatingSum++;
                  exists.ratingSum++;
                } else {
                  result.push({
                    idea: answer,
                    ratingSum: 1,
                    rating: 1,
                    detailRatingSum: 1,
                    detailRating: 1,
                    countParticipant: 1,
                    avatarList: [],
                  });
                }
              }
            }
          }
        }
        return result;
      }
    }
    return [];
  }

  unmounted(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateFinalResult);
    cashService.deregisterAllGet(this.updateQuestions);
    cashService.deregisterAllGet(this.updateIdeas);
    cashService.deregisterAllGet(this.updateHierarchy);
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateFinalResult);
    cashService.deregisterAllGet(this.updateQuestions);
    cashService.deregisterAllGet(this.updateIdeas);
    cashService.deregisterAllGet(this.updateHierarchy);
    taskService.registerGetTaskById(this.taskId, this.updateTask);
    hierarchyService.registerGetQuestions(
      this.taskId,
      this.updateQuestions,
      EndpointAuthorisationType.MODERATOR,
      20
    );

    taskParticipantService.registerGetIterationStepFinalList(
      this.taskId,
      this.updateFinalResult,
      EndpointAuthorisationType.MODERATOR,
      20
    );

    ideaService.registerGetIdeasForTask(
      this.taskId,
      null,
      null,
      this.updateIdeas
    );

    hierarchyService.registerGetList(this.taskId, 'all', this.updateHierarchy);
  }

  updateTask(task: Task): void {
    this.task = task;
    const module = task.modules.find((module) => moduleNameValid(module.name));
    if (module && module.parameter) {
      if (module.parameter?.questionType) {
        this.moduleQuestionnaireType =
          QuestionnaireType[module.parameter.questionType.toUpperCase()];
      } else {
        this.moduleQuestionnaireType = QuestionnaireType.QUIZ;
      }
    }
  }

  questionHierarchy: Hierarchy[] = [];
  questions: Question[] = [];
  updateQuestions(items: Hierarchy[]): void {
    this.questionHierarchy = items;
    this.questions = hierarchyService.convertToQuestions(items);
  }

  trackingResult: TaskParticipantIterationStep[] = [];
  updateFinalResult(result: TaskParticipantIterationStep[]): void {
    this.trackingResult = result;
  }

  ideas: Idea[] = [];
  updateIdeas(ideas: Idea[]): void {
    this.ideas = ideas;
  }

  possibleAnswers: Hierarchy[] = [];
  updateHierarchy(hierarchyList: Hierarchy[]): void {
    this.possibleAnswers = hierarchyList;
  }
}
</script>

<style lang="scss" scoped>
h2 {
  font-size: var(--font-size-large);
  padding-top: 1rem;
}
</style>
