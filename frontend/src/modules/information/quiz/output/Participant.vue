<template>
  <ParticipantModuleDefaultContainer
    :task-id="taskId"
    :module="moduleName"
    :class="{ PMDC: hasImage }"
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
        <img :src="getImageSrc" alt="quizImage" class="QuizImage" />
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
          v-if="!moderatedQuestionFlow && hasNextQuestion"
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
      </span>
    </template>
    <PublicBase
      :taskId="taskId"
      :authHeaderTyp="EndpointAuthorisationType.PARTICIPANT"
      :usePublicQuestion="false"
      :activeQuestionIndex="activeQuestionIndex"
      :activeQuestionPhase="QuestionPhase.ANSWER"
      v-on:changeQuizState="changeQuizState"
      v-on:changePublicQuestion="(question) => (activeQuestion = question)"
      v-on:changePublicAnswers="(answers) => (publicAnswerList = answers)"
      v-if="!submitScreen"
      :showData="!initData"
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
            {{ answer.answer.keywords }}
            <img
              v-if="answer.answer.image"
              :src="answer.answer.image"
              class="question-image"
              alt=""
            />
            <img
              v-if="answer.answer.link && !answer.answer.image"
              :src="answer.answer.link"
              class="question-image"
              alt=""
            />
          </el-button>
        </el-space>
        <el-rate
          v-else-if="activeQuestionType === QuestionType.RATING"
          :max="activeQuestion.parameter.maxValue"
          v-model="activeAnswer.numValue"
          v-on:change="onAnswerValueChanged"
        ></el-rate>
        <el-slider
          v-else-if="activeQuestionType === QuestionType.SLIDER"
          :min="activeQuestion.parameter.minValue"
          :max="activeQuestion.parameter.maxValue"
          v-model="activeAnswer.numValue"
          v-on:change="onAnswerValueChanged"
          :marks="activeQuestionRange"
        ></el-slider>
        <el-input-number
          v-else-if="activeQuestionType === QuestionType.NUMBER"
          :max="activeQuestion.parameter.maxValue"
          :min="activeQuestion.parameter.minValue"
          :value-on-clear="null"
          v-model="activeAnswer.numValue"
          v-on:change="onAnswerValueChanged"
        ></el-input-number>
        <el-input
          v-else-if="activeQuestionType === QuestionType.TEXT"
          type="textarea"
          rows="3"
          v-model="activeAnswer.textValue"
          v-on:change="onAnswerValueChanged"
        ></el-input>
        <div v-else-if="activeQuestionType === QuestionType.IMAGE">
          <ImagePicker
            v-model:link="activeAnswer.link"
            v-model:image="activeAnswer.image"
            v-on:change="onAnswerValueChanged"
          />
          <br />
          <label>
            {{ $t('module.information.quiz.participant.imageKeywords') }}
          </label>
          <el-input
            v-model="activeAnswer.textValue"
            v-on:change="onAnswerValueChanged"
            :placeholder="
              $t('module.information.quiz.participant.imageKeywords')
            "
          ></el-input>
        </div>
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
  QuestionnaireType,
} from '@/modules/information/quiz/types/QuestionnaireType';
import { QuestionPhase } from '@/modules/information/quiz/types/QuestionState';
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

@Options({
  components: {
    ImagePicker,
    QuizResult,
    ParticipantModuleDefaultContainer,
    PublicBase,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  @Prop({ default: false }) readonly useFullSize!: boolean;
  @Prop({ default: '' }) readonly backgroundClass!: string;
  publicAnswerList: PublicAnswerData[] = [];
  activeQuestion: Hierarchy | null = null;
  module: Module | null = null;
  task: Task | null = null;
  votes: Vote[] = [];
  EndpointAuthorisationType = EndpointAuthorisationType;
  activeQuestionIndex = -1;
  questionCount = 0;
  questionnaireType: QuestionnaireType = QuestionnaireType.QUIZ;
  moderatedQuestionFlow = true;
  score = 0;
  voteResults: boolean[] = [];

  activeAnswer: {
    numValue: number | null;
    textValue: string | null;
    link: string | null;
    image: string | null;
  } = {
    numValue: null,
    textValue: null,
    link: null,
    image: null,
  };

  QuestionPhase = QuestionPhase;
  QuestionType = QuestionType;

  submitScreen = false;

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

  get isQuiz(): boolean {
    return this.questionnaireType === QuestionnaireType.QUIZ;
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
    if (
      getQuestionResultStorageFromQuestionType(this.activeQuestionType) ===
      QuestionResultStorage.VOTING
    ) {
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
      this.voteResults[this.activeQuestionIndex] = maxScore === voteScore;
    } else {
      if (
        this.activeQuestionType === QuestionType.NUMBER ||
        this.activeQuestionType === QuestionType.RATING ||
        this.activeQuestionType === QuestionType.SLIDER
      ) {
        this.voteResults[this.activeQuestionIndex] =
          this.activeAnswer.numValue ===
          this.activeQuestion?.parameter.correctValue;
      }
    }
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

  goToNextQuestion(event: PointerEvent | null, initData = false): void {
    this.initData = initData;
    this.checkScore();
    if (this.submitScreen) {
      this.submitScreen = false;
    }
    if (this.hasNextQuestion) this.activeQuestionIndex++;
    else this.goToSubmitScreen();
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
    this.initData = false;
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

  async onAnswerValueChanged(): Promise<void> {
    if (
      getQuestionResultStorageFromQuestionType(this.activeQuestionType) ===
      QuestionResultStorage.CHILD_HIERARCHY
    ) {
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
      const answer = this.storedActiveAnswer;
      if (answer && answer.id) {
        if (answerValue) {
          answer.keywords = answerValue.toString();
          answer.link = answerLink;
          answer.image = answerImage;
          await hierarchyService.putHierarchy(
            answer,
            EndpointAuthorisationType.PARTICIPANT
          );
        } else {
          deleteAnswer(answer.id);
          answer.id = null;
          this.activeAnswer.numValue = null;
          this.activeAnswer.textValue = null;
          this.activeAnswer.link = null;
          this.activeAnswer.image = null;
        }
      } else if (answerValue) {
        await hierarchyService.postHierarchy(
          this.taskId,
          {
            parentId: this.activeQuestionId,
            keywords: answerValue.toString(),
            order: 0,
          },
          EndpointAuthorisationType.PARTICIPANT
        );
      }
      this.questionAnswered = this.getQuestionAnswered();
    }
  }

  async changeVote(answerId: string): Promise<void> {
    if (!this.isSaving(answerId)) {
      this.isSavingList.push(answerId);
      const vote = this.votes.find((vote) => vote.ideaId === answerId);
      if (vote) {
        await votingService
          .deleteVote(vote.id, EndpointAuthorisationType.PARTICIPANT)
          .then((result) => {
            if (result) {
              const index = this.votes.findIndex(
                (vote) => vote.ideaId === answerId
              );
              if (index > -1) this.votes.splice(index, 1);
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
    if (this.activeQuestionType === QuestionType.SINGLECHOICE) {
      const deleteVotes = this.votes.filter((vote) => vote.ideaId !== answerId);
      for (const deleteVote of deleteVotes) {
        await votingService
          .deleteVote(deleteVote.id, EndpointAuthorisationType.PARTICIPANT)
          .then((result) => {
            if (result) {
              const index = this.votes.findIndex(
                (vote) => vote.ideaId === deleteVote.ideaId
              );
              if (index > -1) this.votes.splice(index, 1);
            }
          });
      }
    }
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
  }

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

  updateVotes(votes: Vote[]): void {
    this.votes = votes;
    this.skipAnswerQuestions();
  }

  storedActiveAnswer!: Hierarchy | undefined;
  updateAnswers(answers: Hierarchy[]): void {
    const answer = answers.find((item) => item.isOwn);
    this.storedActiveAnswer = answer;
    if (answer) {
      if (this.activeQuestionType === QuestionType.TEXT) {
        this.activeAnswer.textValue = answer.keywords;
      } else if (
        this.activeQuestionType === QuestionType.NUMBER ||
        this.activeQuestionType === QuestionType.RATING ||
        this.activeQuestionType === QuestionType.SLIDER
      ) {
        this.activeAnswer.numValue = parseInt(answer.keywords);
      } else if (this.activeQuestionType === QuestionType.IMAGE) {
        this.activeAnswer.textValue = answer.keywords;
        this.activeAnswer.link = answer.link;
        this.activeAnswer.image = answer.image;
      }
    } else {
      this.activeAnswer.textValue = null;
      this.activeAnswer.numValue = null;
    }
    this.skipAnswerQuestions();
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
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
  }

  updateQuestions(questions: Hierarchy[]): void {
    this.questionCount = questions.length;
  }

  updateTask(task: Task): void {
    this.task = task;
    const module = this.task.modules.find((module) =>
      moduleNameValid(module.name)
    );
    if (module) {
      this.questionnaireType =
        QuestionnaireType[module.parameter.questionType.toUpperCase()];
      this.moderatedQuestionFlow = module.parameter.moderatedQuestionFlow;
      if (!this.moderatedQuestionFlow && this.activeQuestionIndex === -1) {
        this.activeQuestionIndex = 0;
      }
    }
  }

  changeQuizState(): void {
    //todo
  }

  questionAnswered = false;
  getQuestionAnswered(): boolean {
    if (
      getQuestionResultStorageFromQuestionType(this.activeQuestionType) ===
      QuestionResultStorage.VOTING
    ) {
      return this.votes.length > 0;
    } else {
      if (this.activeQuestionType === QuestionType.TEXT) {
        return !!this.activeAnswer.textValue;
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

  skipAnswerQuestions(): void {
    this.questionAnswered = this.getQuestionAnswered();
    if (!this.moderatedQuestionFlow && this.initData) {
      if (this.questionAnswered) this.goToNextQuestion(null, true);
      else this.initData = false;
    }
  }

  unmounted(): void {
    cashService.deregisterAllGet(this.updateModule);
    cashService.deregisterAllGet(this.updateAnswers);
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateVotes);
    cashService.deregisterAllGet(this.updateQuestions);
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
  border: 10px solid var(--color-darkblue-light);
}

.el-space::v-deep(.outline-thick):hover {
  background-color: var(--color-darkblue);
  border-color: var(--color-darkblue-light);
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
  fill: var(--color-yellow);
}

.el-space::v-deep(.fa-circle) > path {
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

.el-button::v-deep(> span) {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
}

.question-image {
  height: 5rem;
  object-fit: contain;
  background-color: var(--color-primary);
  //margin: -0.8rem -2.1rem -0.8rem 0.5rem;
  //border-radius: 0 0.8rem 0.8rem 0;
  border-radius: 0.8rem;
}

label {
  font-weight: var(--font-weight-semibold);
}

.el-slider::v-deep(.el-slider__stop) {
  width: 0.1px;
}
</style>
