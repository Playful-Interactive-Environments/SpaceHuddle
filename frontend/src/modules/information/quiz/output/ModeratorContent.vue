<template>
  <div>
    <h1 class="heading heading--medium">
      {{ $t(`module.information.quiz.enum.questionType.${questionnaireType}`) }}
    </h1>

    <div id="QuizDiv">
      <QuizResult
        v-if="editQuestion"
        :voteResult="votes"
        :update="true"
        :questionnaireType="questionnaireType"
        :questionType="formData.questionType"
        :show-legend="true"
      />
      <QuizResult
        v-else-if="activeArea === TimelineArea.left"
        :voteResult="detailVotingResult"
        resultColumn="countParticipant"
        :change="false"
        :questionnaireType="
          trackingResult.length > 0
            ? questionnaireType
            : QuestionnaireType.SURVEY
        "
        :questionType="formData.questionType"
        :update="true"
      />
      <Highscore v-else :tracking-result="trackingResult" />
    </div>

    <ProcessTimeline
      v-model="questions"
      v-model:publicScreen="publicQuestion"
      v-model:activeItem="editQuestion"
      :entityName="TimerEntity.TASK"
      :canDisablePublicTimeline="true"
      :isLinkedToDetails="true"
      :startParticipantOnPublicChange="moderatedQuestionFlow"
      keyPropertyName="id"
      :defaultTimerSeconds="defaultQuestionTime"
      :hasParticipantToggle="moderatedQuestionFlow"
      :hasParticipantOption="hasParticipantOption"
      :canClickHome="true"
      :canDisableResult="questionnaireType === QuestionnaireType.QUIZ"
      :contentListIcon="(item) => null"
      :getKey="(item) => item.id"
      :getTitle="(item) => item.keywords"
      :getTimerEntity="(item) => task"
      :itemIsEquals="
        (a, b) => (!a && !b) || (a && b && a.question.id === b.question.id)
      "
      :displayItem="(item) => item.question"
      :hasPublicSlider="hasPublicSlider || !moderatedQuestionFlow"
      accentColor="var(--color-yellow)"
      @changeOrder="dragDone"
      @changeActiveElement="onEditQuestionChanged"
      @homeClicked="homeClicked"
      @resultClicked="resultClicked"
      @changePublicScreen="changePublicScreen"
    >
    </ProcessTimeline>
    <div>
      <ValidationForm
        :form-data="formData"
        submit-label-key="module.information.quiz.moderatorContent.submit"
        v-on:submitDataValid="saveQuestion"
      >
        <el-form-item
          :label="$t('module.information.quiz.moderatorContent.questionType')"
          prop="questionType"
          :rules="[defaultFormRules.ruleRequired]"
        >
          <template #label>
            <div class="media">
              <span class="media-content">
                {{
                  $t('module.information.quiz.moderatorContent.questionType')
                }}
              </span>
              <span class="media-right" v-if="formData.question.id">
                <font-awesome-icon
                  icon="clone"
                  class="link"
                  @click="cloneQuestion"
                ></font-awesome-icon>
              </span>
              <span class="media-right" v-if="formData.question.id">
                <font-awesome-icon
                  icon="trash"
                  class="link"
                  @click="deleteQuestion"
                ></font-awesome-icon>
              </span>
              <span class="media-right" v-if="formData.question.id">
                <font-awesome-icon
                  icon="plus"
                  class="link"
                  @click="setupEmptyQuestion"
                ></font-awesome-icon>
              </span>
            </div>
          </template>
          <el-select v-model="formData.questionType" class="select--fullwidth">
            <el-option
              v-for="questionType in QuestionTypeList"
              :key="questionType"
              :value="questionType"
              :label="$t(`enum.questionType.${questionType}`)"
            >
            </el-option>
          </el-select>
        </el-form-item>
        <div class="image-layout">
          <div class="image-layout--form-data">
            <el-form-item
              :label="$t('module.information.quiz.moderatorContent.question')"
              prop="question.keywords"
              :rules="[
                defaultFormRules.ruleRequired,
                defaultFormRules.ruleToLong(255),
              ]"
            >
              <el-input
                v-model="formData.question.keywords"
                :placeholder="
                  $t('module.information.quiz.moderatorContent.questionExample')
                "
              />
            </el-form-item>
            <el-form-item
              :label="
                $t('module.information.quiz.moderatorContent.explanation')
              "
              :prop="`question.description`"
              :rules="[defaultFormRules.ruleToLong(1000)]"
            >
              <el-input
                v-model="formData.question.description"
                type="textarea"
                rows="3"
                :placeholder="
                  $t(
                    'module.information.quiz.moderatorContent.explanationExample'
                  )
                "
              />
            </el-form-item>
          </div>
          <div class="image-layout--image">
            <ImagePicker
              v-model:link="formData.question.link"
              v-model:image="formData.question.image"
            />
          </div>
        </div>
        <el-form-item
          v-for="(answer, index) in formAnswers"
          :key="index"
          :label="
            $t('module.information.quiz.moderatorContent.answer') +
            ' ' +
            (index + 1)
          "
          :prop="`answers[${index}].keywords`"
          :rules="[
            defaultFormRules.ruleRequired,
            defaultFormRules.ruleToLong(255),
          ]"
        >
          <div class="media" v-if="index < formData.answers.length">
            <el-checkbox
              v-if="questionnaireType === QuestionnaireType.QUIZ"
              class="media-left"
              v-model="answer.parameter.isCorrect"
              v-on:change="correctCheckboxChanged(answer)"
            ></el-checkbox>
            <el-input
              class="media-content"
              v-model="answer.keywords"
              :placeholder="
                $t('module.information.quiz.moderatorContent.answerExample')
              "
            />
            <ImagePicker
              class="answerImage"
              v-model:link="answer.link"
              v-model:image="answer.image"
            />
            <span
              class="icons"
              v-if="formData.answers.length > minAnswerCount"
              v-on:click="deleteAnswer(answer)"
            >
              <font-awesome-icon icon="trash" class="link" />
            </span>
          </div>
        </el-form-item>
        <el-form-item v-if="formData.questionType === QuestionType.ORDER">
          <draggable
            v-model="formData.answers"
            tag="ul"
            :component-data="{
              name: 'flip-list',
              type: 'transition',
            }"
            v-bind="dragOptions"
            group="orderAnswers"
            @start="dragging = true"
            @end="handleOrderChange"
            item-key="id"
          >
            <template #item="{ element }">
              <div class="orderDraggable">
                <h2 class="media-left">{{ element.order + 1 }}</h2>
                <el-input
                  v-model="element.keywords"
                  :placeholder="
                    $t('module.information.quiz.moderatorContent.answerExample')
                  "
                />
                <ImagePicker
                  class="answerImage"
                  v-model:link="element.link"
                  v-model:image="element.image"
                />
                <span
                  class="icons"
                  v-if="formData.answers.length > minAnswerCount"
                  v-on:click="deleteAnswer(element)"
                >
                  <font-awesome-icon icon="trash" class="link" />
                </span>
              </div>
            </template>
          </draggable>
        </el-form-item>
        <el-form-item
          v-if="
            formData.questionType === QuestionType.MULTIPLECHOICE ||
            formData.questionType === QuestionType.SINGLECHOICE ||
            formData.questionType === QuestionType.ORDER
          "
        >
          <AddItem
            :text="$t('module.information.quiz.moderatorContent.addAnswer')"
            @addNew="addAnswer"
          />
        </el-form-item>
        <el-form-item
          v-if="
            formData.questionType === QuestionType.NUMBER ||
            formData.questionType === QuestionType.SLIDER
          "
          :label="$t('module.information.quiz.moderatorContent.minValue')"
          prop="question.parameter.minValue"
          :rules="[defaultFormRules.ruleRequired, defaultFormRules.ruleNumber]"
        >
          <el-input-number
            v-model="formData.question.parameter.minValue"
            :min="0"
            :max="formData.question.parameter.maxValue"
          />
        </el-form-item>
        <el-form-item
          v-if="
            formData.questionType === QuestionType.NUMBER ||
            formData.questionType === QuestionType.RATING ||
            formData.questionType === QuestionType.SLIDER
          "
          :label="$t('module.information.quiz.moderatorContent.maxValue')"
          prop="question.parameter.maxValue"
          :rules="[defaultFormRules.ruleRequired, defaultFormRules.ruleNumber]"
        >
          <el-input-number
            v-model="formData.question.parameter.maxValue"
            :min="
              formData.question.parameter.minValue
                ? formData.question.parameter.minValue
                : 0
            "
            :max="formData.questionType === QuestionType.RATING ? 15 : 10000"
          />
        </el-form-item>
        <el-form-item
          v-if="
            (formData.questionType === QuestionType.NUMBER ||
              formData.questionType === QuestionType.RATING ||
              formData.questionType === QuestionType.SLIDER) &&
            questionnaireType === QuestionnaireType.QUIZ
          "
          :label="$t('module.information.quiz.moderatorContent.correctValue')"
          prop="question.parameter.correctValue"
          :rules="[defaultFormRules.ruleRequired, defaultFormRules.ruleNumber]"
        >
          <el-input-number
            v-model="formData.question.parameter.correctValue"
            :min="
              formData.question.parameter.minValue
                ? formData.question.parameter.minValue
                : 0
            "
            :max="formData.question.parameter.maxValue"
          />
        </el-form-item>
      </ValidationForm>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import * as taskService from '@/services/task-service';
import * as topicService from '@/services/topic-service';
import * as sessionService from '@/services/session-service';
import * as hierarchyService from '@/services/hierarchy-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import IdeaSettings from '@/components/moderator/organisms/settings/IdeaSettings.vue';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import draggable from 'vuedraggable';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import ProcessTimeline, {
  TimelineArea,
} from '@/components/moderator/organisms/Timeline/ProcessTimeline.vue';
import ValidationForm from '@/components/shared/molecules/ValidationForm.vue';
import { defaultFormRules, ValidationRuleDefinition } from '@/utils/formRules';
import { Hierarchy } from '@/types/api/Hierarchy';
import { VoteResult } from '@/types/api/Vote';
import * as votingService from '@/services/voting-service';
import * as cashService from '@/services/cash-service';
import { TimerEntity } from '@/types/enum/TimerEntity';
import { convertToSaveVersion, Task } from '@/types/api/Task';
import {
  getQuestionResultStorageFromQuestionType,
  getQuestionTypeFromHierarchy,
  Question,
  QuestionResultStorage,
  QuestionType,
  QuizQuestionType,
  SurveyQuestionType,
} from '@/modules/information/quiz/types/Question';
import QuizResult from '@/modules/information/quiz/organisms/QuizResult.vue';
import {
  moduleNameValid,
  QuestionnaireType,
} from '@/modules/information/quiz/types/QuestionnaireType';
import { IModeratorContent } from '@/types/ui/IModeratorContent';
import ImagePicker from '@/components/moderator/atoms/ImagePicker.vue';
import { Topic } from '@/types/api/Topic';
import * as taskParticipantService from '@/services/task-participant-service';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import Highscore from '@/modules/information/quiz/organisms/Highscore.vue';

@Options({
  components: {
    Highscore,
    ImagePicker,
    ValidationForm,
    ProcessTimeline,
    AddItem,
    IdeaSettings,
    IdeaCard,
    draggable,
    QuizResult,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue implements IModeratorContent {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  @Prop() readonly taskId!: string;
  task: Task | null = null;
  sessionId!: string;
  questions: Question[] = [];
  publicQuestion: Question | null = null;
  publicTask: Task | null = null;
  editQuestion: Question | null = null;
  ideas: Idea[] = [];
  minAnswerCount = 2;
  answerCount = this.minAnswerCount;
  questionnaireType: QuestionnaireType = QuestionnaireType.QUIZ;
  moderatedQuestionFlow = false;
  defaultQuestionTime: number | null = null;

  QuestionnaireType = QuestionnaireType;
  QuestionType = QuestionType;
  activeArea: TimelineArea = TimelineArea.content;
  publicArea: TimelineArea = TimelineArea.content;
  TimelineArea = TimelineArea;

  dragging = false;
  dragOptions = {
    animation: 200,
    group: 'description',
    disabled: false,
    ghostClass: 'ghost',
  };

  get QuestionTypeList(): QuestionType[] {
    return this.questionnaireType === QuestionnaireType.QUIZ
      ? QuizQuestionType
      : SurveyQuestionType;
  }

  get correctFormAnswer(): Hierarchy | undefined {
    return this.formData.answers.find((item) => item.parameter.isCorrect);
  }

  set correctFormAnswer(value: Hierarchy | undefined) {
    this.formData.answers.forEach((item) => (item.parameter.isCorrect = false));
    if (value) {
      value.parameter.isCorrect = true;
    }
  }

  get formAnswers(): Hierarchy[] {
    if (
      this.formData.questionType === QuestionType.MULTIPLECHOICE ||
      this.formData.questionType === QuestionType.SINGLECHOICE
    )
      return this.formData.answers;
    return [];
  }

  questionCash!: cashService.SimplifiedCashEntry<Hierarchy[]>;
  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    this.task = null;
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateQuestions);
    cashService.deregisterAllGet(this.updateFinalResult);
    taskService.registerGetTaskById(this.taskId, this.updateTask);
    this.questionCash = hierarchyService.registerGetQuestions(
      this.taskId,
      this.updateQuestions,
      EndpointAuthorisationType.MODERATOR,
      20
    );
  }

  @Watch('publicQuestion', { immediate: true })
  async onPublicQuestionChanged(): Promise<void> {
    /*if (this.publicQuestion && !this.editQuestion)
      this.editQuestion = this.publicQuestion;*/
  }

  async changePublicScreen(
    publicQuestion: Question | null,
    area: TimelineArea
  ): Promise<void> {
    if (this.task) {
      const task = this.task;
      const activeQuestion = publicQuestion?.question.id;
      task.parameter['activeQuestion'] = activeQuestion ? activeQuestion : null;
      task.parameter['activeArea'] = area;
      const updateData = { ...convertToSaveVersion(task) };
      await taskService.putTask(updateData);
    }
  }

  setFormData(question: Question): void {
    this.formData = {
      questionType: getQuestionTypeFromHierarchy(question.question),
      question: Object.assign({}, question.question),
      answers: question.answers.map((answer) => Object.assign({}, answer)),
    };
  }

  handleOrderChange(): void {
    this.dragging = false;
    this.formData.answers.forEach((answer) => {
      answer.order = this.formData.answers.indexOf(answer);
    });
  }

  @Watch('editQuestion', { immediate: true })
  async onEditQuestionChanged(): Promise<void> {
    this.votes = [];
    if (this.editQuestion) this.setFormData(this.editQuestion);
  }

  @Watch('formData', { immediate: true })
  async onFormDataChanged(
    newValue: Question,
    oldValue: Question
  ): Promise<void> {
    const oldQuestionResultStorage = oldValue
      ? getQuestionResultStorageFromQuestionType(oldValue.questionType)
      : null;
    const newQuestionResultStorage = getQuestionResultStorageFromQuestionType(
      newValue.questionType
    );
    if (
      newQuestionResultStorage !== oldQuestionResultStorage ||
      newValue?.question?.id !== oldValue?.question?.id
    ) {
      cashService.deregisterAllGet(this.updateParentVotes);
      cashService.deregisterAllGet(this.updateVotes);
      cashService.deregisterAllGet(this.updateHierarchyResult);
      if (newValue?.question?.id) {
        if (newQuestionResultStorage === QuestionResultStorage.VOTING) {
          votingService.registerGetHierarchyResultDetail(
            newValue.question.id,
            this.updateVotes,
            EndpointAuthorisationType.MODERATOR,
            20
          );
        } else {
          hierarchyService.registerGetList(
            this.taskId,
            newValue.question.id,
            this.updateHierarchyResult,
            EndpointAuthorisationType.MODERATOR,
            20
          );
        }
      } else {
        votingService.registerGetParentResult(
          this.taskId,
          this.updateParentVotes,
          EndpointAuthorisationType.MODERATOR,
          20
        );
      }
    }
  }

  updateHierarchyResult(answers: Hierarchy[]): void {
    this.votes = hierarchyService.getHierarchyResult(
      answers,
      this.formData.question.parameter?.correctValue
    );
  }

  updateVotes(votes: VoteResult[]): void {
    this.votes = votes;
  }

  @Watch('formData.questionType', { immediate: true })
  async onQuestionTypeChanged(): Promise<void> {
    if (this.formData.questionType === QuestionType.SINGLECHOICE) {
      const correctAnswers = this.formData.answers.filter(
        (item) => item.parameter.isCorrect
      );
      for (let i = 1; i < correctAnswers.length; i++) {
        correctAnswers[i].parameter.isCorrect = false;
      }
    }

    if (
      this.formData.questionType === QuestionType.NUMBER ||
      this.formData.questionType === QuestionType.SLIDER
    ) {
      if (!this.formData.question.parameter.minValue)
        this.formData.question.parameter.minValue = 0;
      if (!this.formData.question.parameter.maxValue)
        this.formData.question.parameter.maxValue = 100;
    } else if (this.formData.questionType === QuestionType.RATING) {
      delete this.formData.question.parameter.minValue;
      if (
        !this.formData.question.parameter.maxValue ||
        this.formData.question.parameter.maxValue > 10
      )
        this.formData.question.parameter.maxValue = 10;
    } else {
      delete this.formData.question.parameter.maxValue;
      delete this.formData.question.parameter.minValue;
    }

    if (
      getQuestionResultStorageFromQuestionType(this.formData.questionType) ===
        QuestionResultStorage.VOTING &&
      this.formData.answers.length == 0
    ) {
      this.addAnswer();
      this.addAnswer();
    }
  }

  correctCheckboxChanged(answer: Hierarchy): void {
    if (
      this.formData.questionType === QuestionType.SINGLECHOICE &&
      answer.parameter.isCorrect
    ) {
      this.correctFormAnswer = answer;
    }
  }

  get chartHeight(): number {
    return this.votes.length * 13;
  }

  hasParticipantOption(item: Hierarchy): boolean {
    if (!this.publicTask || this.publicTask.id !== this.taskId) return false;
    if (this.publicQuestion && this.moderatedQuestionFlow)
      return item.id === this.publicQuestion.question.id;
    return false;
  }

  formData: Question = this.getEmptyQuestion();
  votes: VoteResult[] = [];

  TimerEntity = TimerEntity;

  get editQuestionIndex(): number {
    const index = this.questions.findIndex(
      (question) => question.question.id === this.formData.question.id
    );
    if (index >= 0) return index + 1;
    return this.questions.length + 1;
  }

  set editQuestionIndex(index: number) {
    index = index - 1;
    if (index < this.questions.length) this.setFormData(this.questions[index]);
    else this.setupEmptyQuestion();
  }

  setupEmptyQuestion(): void {
    this.formData = this.getEmptyQuestion();
    this.editQuestion = null;
  }

  homeClicked(): void {
    this.activeArea = TimelineArea.left;
    this.setupEmptyQuestion();
  }

  resultClicked(): void {
    this.activeArea = TimelineArea.right;
    this.setupEmptyQuestion();
  }

  addAnswer(): void {
    this.formData.answers.push(
      this.getEmptyHierarchy(this.formData.answers.length, true)
    );
  }

  deleteAnswer(answer: Hierarchy): void {
    if (answer.id) {
      hierarchyService.deleteHierarchy(answer.id);
    }
    const index = this.formData.answers.indexOf(answer);
    this.formData.answers.splice(index, 1);
    this.handleOrderChange();
  }

  async saveQuestion(): Promise<void> {
    this.formData.question.parameter.questionType = this.formData.questionType;
    if (this.formData.question.id) {
      await hierarchyService.putHierarchy(this.formData.question);
      if (
        getQuestionResultStorageFromQuestionType(this.formData.questionType) ===
        QuestionResultStorage.VOTING
      ) {
        this.formData.answers.forEach((answer) => {
          if (answer.id) hierarchyService.putHierarchy(answer);
          else {
            answer.parentId = this.formData.question.id;
            hierarchyService
              .postHierarchy(this.taskId, {
                parentId: answer.parentId,
                keywords: answer.keywords,
                description: answer.description,
                link: answer.link,
                image: answer.image,
                parameter: answer.parameter,
                order: answer.order,
              })
              .then((hierarchy) => {
                answer.id = hierarchy.id;
              });
          }
        });
        this.questionCash.refreshData();
      } else if (
        this.editQuestion &&
        getQuestionResultStorageFromQuestionType(
          this.editQuestion.questionType
        ) === QuestionResultStorage.VOTING
      ) {
        for (const answer of this.editQuestion.answers) {
          if (answer.id)
            await hierarchyService.deleteHierarchy(
              answer.id,
              EndpointAuthorisationType.MODERATOR,
              false
            );
        }
      }
      this.editQuestion = {
        questionType: this.formData.questionType,
        question: Object.assign({}, this.formData.question),
        answers: this.formData.answers.map((answer) =>
          Object.assign({}, answer)
        ),
      };
    } else {
      if (this.formData.question.order === null)
        this.formData.question.order = this.questions.length;
      const question = await hierarchyService.postHierarchy(this.taskId, {
        keywords: this.formData.question.keywords,
        description: this.formData.question.description,
        link: this.formData.question.link,
        image: this.formData.question.image,
        parameter: this.formData.question.parameter,
        order: this.formData.question.order,
      });
      this.formData.question.id = question.id;
      if (
        getQuestionResultStorageFromQuestionType(this.formData.questionType) ===
        QuestionResultStorage.VOTING
      ) {
        for (const answer of this.formData.answers) {
          answer.parentId = question.id;
          await hierarchyService.postHierarchy(this.taskId, {
            parentId: answer.parentId,
            keywords: answer.keywords,
            description: answer.description,
            link: answer.link,
            image: answer.image,
            parameter: answer.parameter,
            order: answer.order,
          });
        }
        this.questionCash.refreshData().then(() => {
          this.setupEmptyQuestion();
        });
      } else {
        this.questionCash.refreshData().then(() => {
          this.setupEmptyQuestion();
        });
      }
    }
  }

  deleteQuestion(): void {
    if (this.formData.question.id) {
      hierarchyService
        .deleteHierarchy(this.formData.question.id)
        .then((result) => {
          if (result) {
            this.questionCash.refreshData();
            this.setupEmptyQuestion();
          }
        });
    }
  }

  cloneQuestion(): void {
    if (this.formData.question.id) {
      hierarchyService.clone(this.formData.question.id).then(() => {
        this.questionCash.refreshData();
      });
    }
  }

  initQuestion = false;
  updateTask(task: Task): void {
    const init = !this.task;
    this.initQuestion = init;
    this.task = task;
    const module = task.modules.find((module) => moduleNameValid(module.name));
    if (module && module.parameter) {
      this.answerCount = module.parameter.answerCount;
      if (module.parameter?.questionType) {
        this.questionnaireType =
          QuestionnaireType[module.parameter.questionType.toUpperCase()];
      } else {
        this.questionnaireType = QuestionnaireType.QUIZ;
      }
      const questionFlowChanged =
        this.moderatedQuestionFlow !== module.parameter.moderatedQuestionFlow;
      this.moderatedQuestionFlow = module.parameter.moderatedQuestionFlow;
      if (init || questionFlowChanged) {
        this.registerPublicScreen();
      }
      this.defaultQuestionTime = module.parameter.defaultQuestionTime;
    } else if (init) {
      this.answerCount = 1;
      this.questionnaireType = QuestionnaireType.QUIZ;
      this.moderatedQuestionFlow = false;
      this.defaultQuestionTime = null;
    }
    if (init) {
      cashService.deregisterAllGet(this.updateFinalResult);
      if (this.questionnaireType === QuestionnaireType.QUIZ) {
        taskParticipantService.registerGetIterationStepFinalList(
          this.taskId,
          this.updateFinalResult,
          EndpointAuthorisationType.MODERATOR,
          20
        );
      }
    }
    this.initPublicQuestion();
  }

  questionHierarchy: Hierarchy[] = [];
  updateQuestions(items: Hierarchy[]): void {
    this.questionHierarchy = items;
    const questions = hierarchyService.convertToQuestions(items);
    const deletedQuestions = this.questions
      .filter((qOld) => {
        return !questions.find((qNew) => qNew.question.id === qOld.question.id);
      })
      .map((q) => q.question.id as string);
    this.deregisterGetAnswers(deletedQuestions);
    const newQuestions = questions.filter((qNew) => {
      return !this.questions.find(
        (qOld) => qNew.question.id === qOld.question.id
      );
    });
    const oldQuestions = questions.filter((qNew) => {
      return this.questions.find(
        (qOld) => qNew.question.id === qOld.question.id
      );
    });
    for (const oldQuestion of oldQuestions) {
      const savedQuestion = this.questions.find(
        (item) => item.question.id === oldQuestion.question.id
      );
      if (savedQuestion) oldQuestion.answers = savedQuestion.answers;
    }
    this.questions = questions;
    newQuestions.forEach(async (question) => {
      hierarchyService.registerGetList(
        this.taskId,
        question.question.id,
        this.updateAnswers,
        EndpointAuthorisationType.MODERATOR,
        60
      );
    });
    if (this.initQuestion) {
      this.initPublicQuestion();
      if (this.questions.length === 0) this.setupEmptyQuestion();
      else this.editQuestion = this.questions[0];
    }
    this.initQuestion = false;
  }

  trackingResult: TaskParticipantIterationStep[] = [];
  updateFinalResult(result: TaskParticipantIterationStep[]): void {
    this.trackingResult = result;
  }

  updateParentVotes(votes: VoteResult[]): void {
    if (!this.formData?.question?.id) {
      this.votes = hierarchyService.getParentResult(
        votes,
        this.questionHierarchy
      );
    }
  }

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
            });
          }
        }
      }
      return result;
    }
    return this.votes;
  }

  initPublicQuestion(): void {
    const activeQuestionId = this.task?.parameter?.activeQuestion;
    const publicQuestion: Question | undefined = this.questions.find(
      (question) => question.question.id === activeQuestionId
    );
    if (publicQuestion) this.publicQuestion = publicQuestion;
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

  updateAnswers(answers: Hierarchy[], questionId: string | null): void {
    if (questionId) {
      const question = this.questions.find(
        (question) => question.question.id === questionId
      );
      if (question) {
        const questionResultStorage: QuestionResultStorage =
          getQuestionResultStorageFromQuestionType(question.questionType);
        if (questionResultStorage === QuestionResultStorage.VOTING) {
          const changes = question.answers.length !== answers.length;
          question.answers = answers;
          if (changes && question === this.editQuestion) {
            this.setFormData(this.editQuestion);
          }
        } else {
          //
        }
      }
    }
  }

  @Watch('task.topicId', { immediate: true })
  onTopicIdChanged(newValue: string | null, oldValue: string | null): void {
    if (newValue)
      topicService.registerGetTopicById(
        newValue,
        this.updateTopic,
        EndpointAuthorisationType.MODERATOR,
        60 * 60
      );
    if (oldValue)
      topicService.deregisterGetTopicById(oldValue, this.updateTopic);
  }

  updateTopic(topic: Topic): void {
    this.sessionId = topic.sessionId;
    this.registerPublicScreen();
  }

  publicScreenRegistered = false;
  registerPublicScreen(): void {
    if (this.sessionId) {
      if (this.publicScreenRegistered && !this.moderatedQuestionFlow) {
        sessionService.deregisterGetPublicScreen(
          this.sessionId,
          this.updatePublicTask
        );
        this.publicTask = null;
        this.hasPublicSlider = false;
      } else if (this.moderatedQuestionFlow && !this.publicScreenRegistered) {
        this.publicScreenRegistered = true;
        sessionService.registerGetPublicScreen(
          this.sessionId,
          this.updatePublicTask,
          EndpointAuthorisationType.MODERATOR,
          30
        );
      }
      this.publicScreenRegistered = this.moderatedQuestionFlow;
    }
  }

  async reloadTaskSettings(): Promise<void> {
    //todo
  }

  hasPublicSlider = false;
  updatePublicTask(task: Task): void {
    if (task) {
      this.publicTask = task;
      this.hasPublicSlider = this.publicTask.id === this.taskId;
    } else {
      this.publicTask = null;
      this.hasPublicSlider = false;
    }
  }

  getEmptyHierarchy(
    order = this.questions.length,
    forAnswer = false
  ): Hierarchy {
    return {
      id: null,
      parentId: null,
      keywords: '',
      description: null,
      link: null,
      image: null,
      timestamp: null,
      imageTimestamp: null,
      parameter: forAnswer
        ? { isCorrect: false }
        : { questionType: QuestionType.MULTIPLECHOICE },
      order: order,
      isOwn: false,
      childCount: 0,
    };
  }

  getEmptyQuestion(): Question {
    const answers: Hierarchy[] = [];
    for (let index = 0; index < this.answerCount; index++) {
      answers.push(this.getEmptyHierarchy(index, true));
    }
    return {
      questionType: QuestionType.MULTIPLECHOICE,
      question: this.getEmptyHierarchy(),
      answers: answers,
    };
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateTopic);
    cashService.deregisterAllGet(this.updateVotes);
    cashService.deregisterAllGet(this.updateAnswers);
    cashService.deregisterAllGet(this.updateHierarchyResult);
    cashService.deregisterAllGet(this.updatePublicTask);
    cashService.deregisterAllGet(this.updateQuestions);
    cashService.deregisterAllGet(this.updateFinalResult);
    cashService.deregisterAllGet(this.updateParentVotes);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  /* eslint-disable @typescript-eslint/explicit-module-boundary-types*/
  async dragDone(list: any[]): Promise<void> {
    list.forEach((question, index) => {
      if (question.question.id) {
        question.question.order = index;
        hierarchyService.putHierarchy(question.question);
      }
    });
  }
}
</script>

<style scoped lang="scss">
.el-input {
  --el-input-background-color: white;
}

.select--fullwidth {
  width: 100%;
}

.icons {
  height: 100%;
  align-items: center;
  margin: auto;
  padding-left: 0.5rem;
}

#QuizDiv {
  position: relative;

  width: auto;
  height: auto;
}

#noQuestionSelectedSpan {
  position: absolute;
  height: auto;
  width: auto;

  text-align: center;
  font-size: var(--font-size-large);

  left: 0;
  right: 0;
  top: 50%;
  margin-left: auto;
  margin-right: auto;
}

.image-layout {
  display: flex;
  flex-direction: row;
  justify-content: space-between;

  &--form-data {
    width: 100%;
  }
  &--image {
    width: 12rem;
    margin-top: 2rem;
    margin-left: 1rem;
  }
}

.answerImage.stack {
  min-width: 4.5rem;
  max-height: 2.5rem;
  margin-left: 0.5rem;
  font-size: 0.7rem;
  position: relative;
  top: -2rem;
}

.answerImage.stack::v-deep(.stack__action) {
  font-size: 1rem;
  gap: 0.3rem;
}

.orderDraggable {
  background-color: var(--color-dark-contrast-light);
  border-right: 2rem solid var(--color-primary);
  padding: 0.5rem 1rem;
  cursor: move;
  margin: 0.5rem 0;
  border-radius: 10px;
  display: flex;
  align-items: center;

  .answerImage.stack {
    max-height: unset;
    position: inherit;
    margin: 0 0.5rem;
  }
}

.orderDraggable h2 {
  font-weight: bold;
}

.ghost {
  background-color: var(--color-dark-contrast);
  color: white;
}
</style>
