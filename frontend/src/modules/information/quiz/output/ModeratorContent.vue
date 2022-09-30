<template>
  <div>
    <h1 class="heading heading--medium">
      {{ $t(`module.information.quiz.enum.questionType.${questionType}`) }}
    </h1>

    <div id="QuizDiv">
      <QuizResult
        :voteResult="votes"
        :update="true"
        :questionType="questionType"
        :show-legend="true"
      />
      <span id="noQuestionSelectedSpan" v-if="!editQuestion">{{
        $t('module.information.quiz.moderatorContent.noQuestionSelected')
      }}</span>
    </div>

    <ProcessTimeline
      v-model="questions"
      v-model:publicScreen="publicQuestion"
      v-model:activeItem="editQuestion"
      :entityName="TimerEntity.TASK"
      :canDisablePublicTimeline="!moderatedQuestionFlow"
      :isLinkedToDetails="true"
      :startParticipantOnPublicChange="moderatedQuestionFlow"
      keyPropertyName="id"
      :defaultTimerSeconds="defaultQuestionTime"
      :hasParticipantToggle="moderatedQuestionFlow"
      :hasParticipantOption="hasParticipantOption"
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
                {{ $t('module.information.quiz.moderatorContent.question') }}
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
              v-for="questionType in Object.values(QuestionType)"
              :key="questionType"
              :value="questionType"
              :label="$t(`enum.questionType.${questionType}`)"
            >
            </el-option>
          </el-select>
        </el-form-item>
        <el-form-item
          :label="$t('module.information.quiz.moderatorContent.question')"
          prop="question.keywords"
          :rules="[
            defaultFormRules.ruleRequired,
            defaultFormRules.ruleToLong(400),
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
          :label="$t('module.information.quiz.moderatorContent.explanation')"
          :prop="`question.description`"
          :rules="[defaultFormRules.ruleToLong(1000)]"
        >
          <el-input
            v-model="formData.question.description"
            type="textarea"
            rows="3"
            :placeholder="
              $t('module.information.quiz.moderatorContent.explanationExample')
            "
          />
        </el-form-item>
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
            defaultFormRules.ruleToLong(400),
          ]"
        >
          <div class="media" v-if="index < formData.answers.length">
            <el-checkbox
              v-if="questionType === QuestionnaireType.QUIZ"
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
            <span
              class="icons"
              v-if="formData.answers.length > minAnswerCount"
              v-on:click="deleteAnswer(answer)"
            >
              <font-awesome-icon icon="trash" class="link" />
            </span>
          </div>
        </el-form-item>
        <el-form-item
          v-if="
            formData.questionType === QuestionType.MULTICHOICE ||
            formData.questionType === QuestionType.SINGLECHOICE
          "
        >
          <AddItem
            :text="$t('module.information.quiz.moderatorContent.addAnswer')"
            @addNew="addAnswer"
          />
        </el-form-item>
        <el-form-item
          v-if="formData.questionType === QuestionType.NUMBER"
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
import ProcessTimeline from '@/components/moderator/organisms/Timeline/ProcessTimeline.vue';
import ValidationForm from '@/components/shared/molecules/ValidationForm.vue';
import { defaultFormRules, ValidationRuleDefinition } from '@/utils/formRules';
import { Hierarchy } from '@/types/api/Hierarchy';
import Vue3ChartJs from '@j-t-mcc/vue3-chartjs';
import { VoteResult } from '@/types/api/Vote';
import * as votingService from '@/services/voting-service';
import { TimerEntity } from '@/types/enum/TimerEntity';
import { convertToSaveVersion, Task } from '@/types/api/Task';
import {
  getQuestionResultStorageFromQuestionType,
  getQuestionTypeFromHierarchy,
  Question,
  QuestionResultStorage,
  QuestionType,
} from '@/modules/information/quiz/types/Question';
import QuizResult from '@/modules/information/quiz/organisms/QuizResult.vue';
import {
  moduleNameValid,
  QuestionnaireType,
} from '@/modules/information/quiz/types/QuestionnaireType';
import { IModeratorContent } from '@/types/ui/IModeratorContent';

@Options({
  components: {
    ValidationForm,
    ProcessTimeline,
    AddItem,
    IdeaSettings,
    IdeaCard,
    draggable,
    Vue3ChartJs,
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
  readonly intervalTime = 10000;
  interval!: any;
  minAnswerCount = 2;
  answerCount = this.minAnswerCount;
  questionType: QuestionnaireType = QuestionnaireType.QUIZ;
  moderatedQuestionFlow = true;
  defaultQuestionTime: number | null = null;

  QuestionnaireType = QuestionnaireType;
  QuestionType = QuestionType;

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
      this.formData.questionType === QuestionType.MULTICHOICE ||
      this.formData.questionType === QuestionType.SINGLECHOICE
    )
      return this.formData.answers;
    return [];
  }

  @Watch('publicQuestion', { immediate: true })
  async onPublicQuestionChanged(): Promise<void> {
    if (this.publicQuestion && !this.editQuestion)
      if (this.publicQuestion) {
        this.editQuestion = this.publicQuestion;
      }
    if (this.task) {
      this.task.parameter['activeQuestion'] = this.publicQuestion?.question.id;
      await taskService.putTask(convertToSaveVersion(this.task));
    }
  }

  setFormData(question: Question): void {
    this.formData = {
      questionType: getQuestionTypeFromHierarchy(question.question),
      question: Object.assign({}, question.question),
      answers: question.answers.map((answer) => Object.assign({}, answer)),
    };
  }

  @Watch('editQuestion', { immediate: true })
  async onEditQuestionChanged(): Promise<void> {
    if (this.editQuestion) this.setFormData(this.editQuestion);
  }

  @Watch('formData', { immediate: true })
  async onFormDataChanged(): Promise<void> {
    await this.getVotes();
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

    if (this.formData.questionType === QuestionType.NUMBER) {
      if (!this.formData.question.parameter.minValue)
        this.formData.question.parameter.minValue = 0;
      if (!this.formData.question.parameter.maxValue)
        this.formData.question.parameter.maxValue = 100;
    } else if (
      this.formData.questionType === QuestionType.SLIDER ||
      this.formData.questionType === QuestionType.RATING
    ) {
      delete this.formData.question.parameter.minValue;
      if (!this.formData.question.parameter.maxValue)
        this.formData.question.parameter.maxValue = 10;
    } else {
      delete this.formData.question.parameter.maxValue;
      delete this.formData.question.parameter.minValue;
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

  get hasPublicSlider(): boolean {
    return !!this.publicTask && this.publicTask.id === this.taskId;
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
  }

  saveQuestion(): void {
    this.formData.question.parameter.questionType = this.formData.questionType;
    if (this.formData.question.id) {
      hierarchyService.putHierarchy(this.formData.question).then(() => {
        if (
          getQuestionResultStorageFromQuestionType(
            this.formData.questionType
          ) === QuestionResultStorage.VOTING
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
          this.getHierarchies();
        }
      });
    } else {
      if (this.formData.question.order === null)
        this.formData.question.order = this.questions.length;
      hierarchyService
        .postHierarchy(this.taskId, {
          keywords: this.formData.question.keywords,
          description: this.formData.question.description,
          link: this.formData.question.link,
          image: this.formData.question.image,
          parameter: this.formData.question.parameter,
          order: this.formData.question.order,
        })
        .then(async (question) => {
          this.formData.question.id = question.id;
          if (
            getQuestionResultStorageFromQuestionType(
              this.formData.questionType
            ) === QuestionResultStorage.VOTING
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
            this.getHierarchies().then(() => {
              this.setupEmptyQuestion();
            });
          } else {
            this.getHierarchies().then(() => {
              this.setupEmptyQuestion();
            });
          }
        });
    }
  }

  deleteQuestion(): void {
    if (this.formData.question.id) {
      hierarchyService
        .deleteHierarchy(this.formData.question.id)
        .then((result) => {
          if (result) {
            this.getHierarchies();
            this.setupEmptyQuestion();
          }
        });
    }
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.reloadTaskSettings().then(() => {
      this.setupEmptyQuestion();
    });
  }

  async reloadTaskSettings(): Promise<void> {
    await taskService.getTaskById(this.taskId).then((task) => {
      this.task = task;
      topicService.getTopicById(task.topicId).then((topic) => {
        this.sessionId = topic.sessionId;
      });
      const module = task.modules.find((module) =>
        moduleNameValid(module.name)
      );
      if (module) {
        this.answerCount = module.parameter.answerCount;
        if (module.parameter.questionType) {
          this.questionType =
            QuestionnaireType[module.parameter.questionType.toUpperCase()];
        } else {
          this.questionType = QuestionnaireType.QUIZ;
        }
        this.moderatedQuestionFlow = module.parameter.moderatedQuestionFlow;
        this.defaultQuestionTime = module.parameter.defaultQuestionTime;
      }
      this.getHierarchies();
    });
  }

  @Watch('sessionId', { immediate: true })
  onSessionIdChanged(): void {
    if (this.sessionId) {
      sessionService.getPublicScreen(this.sessionId).then((task) => {
        this.publicTask = task;
      });
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
      parameter: forAnswer
        ? { isCorrect: false }
        : { questionType: QuestionType.MULTICHOICE },
      order: order,
      isOwn: false,
    };
  }

  getEmptyQuestion(): Question {
    const answers: Hierarchy[] = [];
    for (let index = 0; index < this.answerCount; index++) {
      answers.push(this.getEmptyHierarchy(index, true));
    }
    return {
      questionType: QuestionType.MULTICHOICE,
      question: this.getEmptyHierarchy(),
      answers: answers,
    };
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
            const questionType: QuestionType =
              getQuestionTypeFromHierarchy(question);
            const questionResultStorage: QuestionResultStorage =
              getQuestionResultStorageFromQuestionType(questionType);
            if (questionResultStorage === QuestionResultStorage.VOTING) {
              await hierarchyService
                .getList(this.taskId, question.id)
                .then((answers) => {
                  const item: Question = {
                    questionType: questionType,
                    question: question,
                    answers: answers,
                  };
                  result.push(item);
                  if (question.id == this.task?.parameter.activeQuestion) {
                    publicQuestion = item;
                  }
                });
            } else {
              const item: Question = {
                questionType: questionType,
                question: question,
                answers: [],
              };
              result.push(item);
              if (question.id == this.task?.parameter.activeQuestion) {
                publicQuestion = item;
              }
            }
          }
          this.questions = result;
          if (publicQuestion) this.publicQuestion = publicQuestion;
          await this.getVotes();
        });
    }
  }

  async getVotes(): Promise<void> {
    if (this.formData.question.id) {
      if (
        getQuestionResultStorageFromQuestionType(this.formData.questionType) ===
        QuestionResultStorage.VOTING
      ) {
        await votingService
          .getHierarchyResult(this.formData.question.id)
          .then((votes) => {
            this.votes = votes;
          });
      } else {
        await hierarchyService
          .getHierarchyResult(this.taskId, this.formData.question.id)
          .then((votes) => {
            this.votes = votes;
          });
      }
    } else {
      this.votes = [];
    }
  }

  async mounted(): Promise<void> {
    this.startInterval();
  }

  startInterval(): void {
    this.interval = setInterval(this.reloadData, this.intervalTime);
  }

  reloadData(): void {
    this.getVotes();
    taskService.getTaskById(this.taskId).then((task) => {
      this.task = task;
      const module = task.modules.find((module) =>
        moduleNameValid(module.name)
      );
      if (module) {
        this.answerCount = module.parameter.answerCount;
        this.defaultQuestionTime = module.parameter.defaultQuestionTime;
      }
      this.getHierarchies();
    });
    this.onSessionIdChanged();
  }

  unmounted(): void {
    clearInterval(this.interval);
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

<style scoped>
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
</style>
