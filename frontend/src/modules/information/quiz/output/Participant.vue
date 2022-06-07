<template>
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
      <img :src="getImageSrc" alt="quizImage" class="QuizImage" />
    </div>
  </div>
  <ParticipantModuleDefaultContainer
    :task-id="taskId"
    :module="moduleName"
    :class="{ PMDC: hasImage }"
  >
    <div id="preloader"></div>
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
          :disabled="!hasNextQuestion"
          v-if="!moderatedQuestionFlow && hasNextQuestion"
          @click="goToNextQuestion"
        >
          {{ $t('module.information.quiz.participant.next') }}
        </el-button>
        <el-button
          type="primary"
          class="el-button--submit"
          native-type="submit"
          :disabled="hasNextQuestion"
          v-if="!moderatedQuestionFlow && !hasNextQuestion && !submitScreen"
          @click="goToSubmitScreen"
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
      v-on:changeQuizState="getTask"
      v-on:changePublicQuestion="(id) => (activeQuestionId = id)"
      v-on:changePublicAnswers="(answers) => (publicAnswerList = answers)"
      v-if="!submitScreen"
    >
      <template #answers>
        <el-space direction="vertical" class="fill">
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
            {{ answer.answer.keywords }}
          </el-button>
        </el-space>
      </template>
    </PublicBase>
    <div id="submitScreen" v-if="submitScreen">
      <span>{{
        $t('module.information.quiz.participant.thanksIndividual')
      }}</span>
      <span id="ScoreString" v-if="isQuiz">{{ getScoreString }}</span>
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
import {
  moduleNameValid,
  QuestionType,
} from '@/modules/information/quiz/types/QuestionType';
import { QuestionPhase } from '@/modules/information/quiz/types/QuestionState';
import * as hierarchyService from '@/services/hierarchy-service';
import QuizResult from '@/modules/information/quiz/organisms/QuizResult.vue';

@Options({
  components: {
    QuizResult,
    ParticipantModuleDefaultContainer,
    PublicBase,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  publicAnswerList: PublicAnswerData[] = [];
  activeQuestionId = '';
  module: Module | null = null;
  task: Task | null = null;
  votes: Vote[] = [];
  EndpointAuthorisationType = EndpointAuthorisationType;
  activeQuestionIndex = -1;
  questionCount = 0;
  questionType: QuestionType = QuestionType.QUIZ;
  moderatedQuestionFlow = true;
  score = 0;
  voteResults: boolean[] = [];

  QuestionPhase = QuestionPhase;

  submitScreen = false;

  get isQuiz(): boolean {
    return this.questionType === QuestionType.QUIZ;
  }

  get loading(): boolean {
    let element = document.getElementById('loadingScreen');

    if (element != null && !element.classList.contains('zeroOpacity')) {
      var preload = document.getElementById('preloader');
      preload?.classList.add('PreloadSprites');

      setTimeout(() => preload?.classList.remove('PreloadSprites'), 1000);
      setTimeout(() => element?.classList.add('zeroOpacity'), 1000);
      setTimeout(() => element?.classList.add('hidden'), 3000);
      return true;
    }
    return false;
  }

  mounted(): void {
    this.loading;
  }

  get hasImage(): boolean {
    //check if the question has an image and return true or false
    return false;
  }

  get getScoreString(): string {
    let score = 0;
    for (let i = 0; i < this.voteResults.length; i++) {
      if (this.voteResults[i]) {
        score++;
      }
    }
    return score + '/' + this.questionCount;
  }

  checkScore(): void {
    let voteScore = 0;
    let maxScore = 0;
    for (let i = 0; i < this.publicAnswerList.length; i++) {
      for (let j = 0; j < this.votes.length; j++) {
        if (this.publicAnswerList[i].answer.id == this.votes[j].ideaId) {
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
    this.voteResults[this.activeQuestionIndex] = maxScore == voteScore;
  }

  get getImageSrc(): string {
    if (this.hasImage) {
      //check image src and return. this is just a dummy picture for now
      return require('../../../../assets/illustrations/Quiz/QuizImage.jpg');
    } else {
      return '';
    }
  }

  get isActive(): boolean {
    if (this.task) return timerService.isActive(this.task);
    return false;
  }

  loadQuestionCount(): void {
    hierarchyService
      .getList(
        this.taskId,
        '{parentHierarchyId}',
        EndpointAuthorisationType.PARTICIPANT
      )
      .then(async (questions) => {
        this.questionCount = questions.length;
      });
  }

  get hasNextQuestion(): boolean {
    return this.activeQuestionIndex + 1 < this.questionCount;
  }

  goToNextQuestion(): void {
    this.checkScore();
    if (this.submitScreen) {
      this.submitScreen = false;
    }
    if (this.hasNextQuestion) this.activeQuestionIndex++;
  }

  goToSubmitScreen(): void {
    this.checkScore();
    this.activeQuestionIndex++;
    this.submitScreen = true;
  }

  get hasPreviousQuestion(): boolean {
    return this.activeQuestionIndex > 0;
  }

  goToPreviousQuestion(): void {
    this.checkScore();
    if (this.submitScreen) {
      this.submitScreen = false;
    }
    if (this.hasPreviousQuestion) this.activeQuestionIndex--;
  }

  isAnswerSelected(answerId: string): boolean {
    return !!this.votes.find((vote) => vote.ideaId == answerId);
  }

  isSavingList: string[] = [];
  isSaving(answerId: string): boolean {
    return this.isSavingList.includes(answerId);
  }

  async changeVote(answerId: string): Promise<void> {
    if (!this.isSaving(answerId)) {
      this.isSavingList.push(answerId);
      const vote = this.votes.find((vote) => vote.ideaId == answerId);
      if (vote) {
        await votingService
          .deleteVote(vote.id, EndpointAuthorisationType.PARTICIPANT)
          .then((result) => {
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

  @Watch('moduleId', { immediate: true })
  onModuleIdChanged(): void {
    this.getModule();
  }

  @Watch('activeQuestionId', { immediate: true })
  onActiveQuestionIdChanged(): void {
    this.getTask();
    this.getVotes();
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getTask();
  }

  async getTask(): Promise<void> {
    await taskService
      .getTaskById(this.taskId, EndpointAuthorisationType.PARTICIPANT)
      .then((task) => {
        this.task = task;
        const module = this.task.modules.find((module) =>
          moduleNameValid(module.name)
        );
        if (module) {
          this.questionType =
            QuestionType[module.parameter.questionType.toUpperCase()];
          this.moderatedQuestionFlow = module.parameter.moderatedQuestionFlow;
          if (!this.moderatedQuestionFlow && this.activeQuestionIndex === -1) {
            this.activeQuestionIndex = 0;
          }
        }
        this.loadQuestionCount();
      });
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

  async getVotes(): Promise<void> {
    if (this.activeQuestionId) {
      await votingService
        .getHierarchyVotes(
          this.activeQuestionId,
          EndpointAuthorisationType.PARTICIPANT
        )
        .then((votes) => {
          this.votes = votes;
        });
    }
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

.el-footer {
  height: auto;
}

/*.question {
  border: 1px solid var(--color-primary);
  border-radius: var(--border-radius);
  padding: 1rem;
  font-weight: var(--font-weight-semibold);
  text-transform: uppercase;
  text-align: center;
  color: var(--color-primary);
  margin: 1em 0;
}*/

.module-content::v-deep div.question {
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

#PMDC::v-deep .el-steps {
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

  background-color: var(--color-darkblue);

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

div#loadingScreen > span#loading::v-deep .path {
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

  background-image: url('../../../../assets/illustrations/Voting/StarsSpace.png');
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
  border: 10px solid var(--color-darkblue-light);
}

.el-space::v-deep
  .el-button.el-button--primary.el-button--default.link.outline-thick:hover {
  background-color: var(--color-darkblue);
  border-color: var(--color-darkblue-light);
}

.el-space::v-deep .el-button.el-button--primary.el-button--default.link > span {
  width: 100%;
  white-space: pre-line;
  overflow-wrap: anywhere;
  text-align: left;
  margin-left: 4%;
}

.el-space::v-deep .el-button.el-button--primary.el-button--default.link {
  height: auto;
  padding: 2% 5% 2% 5%;
}

.el-space::v-deep .fa-circle-check > path {
  fill: var(--color-yellow);
}

.el-space::v-deep .fa-circle > path {
  fill: var(--color-darkblue-light);
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
</style>
