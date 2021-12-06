<template>
  <div>
    <ProcessTimeline
      v-model="questions"
      v-model:publicScreen="publicQuestion"
      v-model:activeItem="editQuestion"
      :entityName="TimerEntity.TASK"
      :canDisablePublicTimeline="false"
      :isLinkedToDetails="true"
      :startParticipantOnPublicChange="true"
      keyPropertyName="question"
      :getTitle="(item) => item.question.keywords"
      :getKey="(item) => item.question.id"
      :getTimerEntity="(item) => task"
      :hasParticipantOption="hasParticipantOption"
      :contentListIcon="(item) => null"
      :defaultTimerSeconds="defaultQuestionTime"
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
          :label="$t('module.information.quiz.moderatorContent.question')"
          prop="question.keywords"
          :rules="[defaultFormRules.ruleRequired, defaultFormRules.ruleToLong(400)]"
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
              $t('module.information.quiz.moderatorContent.explanationExample')
            "
          />
        </el-form-item>
        <el-form-item
          v-for="(answer, index) in formData.answers"
          :key="index"
          :label="
            $t('module.information.quiz.moderatorContent.answer') +
            ' ' +
            (index + 1)
          "
          :prop="`answers[${index}].keywords`"
          :rules="[defaultFormRules.ruleRequired, defaultFormRules.ruleToLong(400)]"
        >
          <div class="media" v-if="index < formData.answers.length">
            <el-checkbox
              class="media-left"
              v-model="answer.parameter.isCorrect"
            ></el-checkbox>
            <el-input
              class="media-content"
              v-model="answer.keywords"
              :placeholder="
                $t('module.information.quiz.moderatorContent.answerExample')
              "
            />
          </div>
        </el-form-item>
      </ValidationForm>
      <vue3-chart-js
        id="resultChart"
        ref="chartRef"
        type="bar"
        :data="chartData"
        :options="{
          animation: {
            duration: 0,
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
      <el-pagination
        layout="prev, pager, next"
        :page-size="1"
        :total="questions.length + 1"
        v-model:current-page="editQuestionIndex"
      ></el-pagination>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import * as taskService from '@/services/task-service';
import * as hierarchyService from '@/services/hierarchy-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import IdeaSettings from '@/components/moderator/organisms/settings/IdeaSettings.vue';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import draggable from 'vuedraggable';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import ProcessTimeline from '@/components/moderator/organisms/Timeline/ProcessTimeline.vue';
import ValidationForm from '@/components/shared/molecules/ValidationForm.vue';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import { Hierarchy } from '@/types/api/Hierarchy';
import Vue3ChartJs from '@j-t-mcc/vue3-chartjs';
import { VoteResult } from '@/types/api/Vote';
import * as votingService from '@/services/voting-service';
import { TimerEntity } from '@/types/enum/TimerEntity';
import { convertToSaveVersion, Task } from '@/types/api/Task';

interface Question {
  question: Hierarchy;
  answers: Hierarchy[];
}

@Options({
  components: {
    ValidationForm,
    ProcessTimeline,
    AddItem,
    IdeaSettings,
    IdeaCard,
    draggable,
    Vue3ChartJs,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  @Prop() readonly taskId!: string;
  task: Task | null = null;
  questions: Question[] = [];
  publicQuestion: Question | null = null;
  editQuestion: Question | null = null;
  ideas: Idea[] = [];
  readonly intervalTime = 10000;
  interval!: any;
  answerCount = 4;
  defaultQuestionTime: number | null = null;

  @Watch('publicQuestion', { immediate: true })
  async onPublicQuestionChanged(): Promise<void> {
    if (this.publicQuestion) this.editQuestion = this.publicQuestion;
    if (this.task) {
      this.task.parameter['activeQuestion'] = this.publicQuestion?.question.id;
      await taskService.updateTask(convertToSaveVersion(this.task));
    }
  }

  //@Watch('editQuestion', { immediate: true })
  async onEditQuestionChanged(): Promise<void> {
    if (this.editQuestion) this.formData = this.editQuestion;
  }

  @Watch('formData', { immediate: true })
  async onFormDataChanged(): Promise<void> {
    await this.getVotes();
  }

  hasParticipantOption(item: Question): boolean {
    if (this.publicQuestion)
      return item.question.id === this.publicQuestion.question.id;
    return false;
  }

  formData: Question = this.getEmptyQuestion();
  votes: VoteResult[] = [];
  chartData: any = {
    labels: [],
    datasets: [],
  };

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
    if (index < this.questions.length) this.formData = this.questions[index];
    else this.setupEmptyQuestion();
  }

  setupEmptyQuestion(): void {
    this.formData = this.getEmptyQuestion();
  }

  saveQuestion(): void {
    if (this.formData.question.id) {
      hierarchyService.putHierarchy(
        this.formData.question.id,
        this.formData.question
      );
      this.formData.answers.forEach((answer) => {
        if (answer.id) hierarchyService.putHierarchy(answer.id, answer);
      });
    } else {
      hierarchyService
        .postHierarchy(this.taskId, {
          keywords: this.formData.question.keywords,
          description: this.formData.question.description,
          link: this.formData.question.link,
          image: this.formData.question.image,
          parameter: this.formData.question.parameter,
          order: this.formData.question.order,
        })
        .then((question) => {
          this.formData.answers.forEach((answer) => {
            answer.parentId = question.id;
            hierarchyService.postHierarchy(this.taskId, {
              parentId: answer.parentId,
              keywords: answer.keywords,
              description: answer.description,
              link: answer.link,
              image: answer.image,
              parameter: answer.parameter,
              order: answer.order,
            });
          });
          this.setupEmptyQuestion();
          this.getHierarchies();
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
    taskService.getTaskById(this.taskId).then((task) => {
      this.task = task;
      const module = task.modules.find((module) => module.name == 'quiz');
      if (module) {
        this.answerCount = module.parameter.answerCount;
        this.defaultQuestionTime = module.parameter.defaultQuestionTime;
      }
      this.setupEmptyQuestion();
      this.getHierarchies();
    });
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
      parameter: forAnswer ? { isCorrect: false } : {},
      order: order,
    };
  }

  getEmptyQuestion(): Question {
    const answers: Hierarchy[] = [];
    for (let index = 0; index < this.answerCount; index++) {
      answers.push(this.getEmptyHierarchy(index, true));
    }
    return {
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
            await hierarchyService
              .getList(this.taskId, question.id)
              .then((answers) => {
                const item: Question = {
                  question: question,
                  answers: answers,
                };
                result.push(item);
                if (question.id == this.task?.parameter.activeQuestion) {
                  publicQuestion = item;
                }
              });
          }
          this.questions = result;
          if (publicQuestion) this.publicQuestion = publicQuestion;
          this.getVotes();
        });
    }
  }

  get resultData(): any {
    this.votes.map((vote) => vote.idea.keywords);
    return {
      labels: this.votes.map((vote) => vote.idea.keywords),
      datasets: [
        {
          label: (this as any).$t(
            'module.voting.default.publicScreen.chartDataLabel'
          ),
          backgroundColor: this.votes.map((vote) =>
            vote.idea.parameter.isCorrect ? '#f1be3a' : '#fe6e5d'
          ),
          data: this.votes.map((vote) => vote.detailRatingSum),
        },
      ],
    };
  }

  async getVotes(): Promise<void> {
    if (this.formData.question.id) {
      await votingService
        .getHierarchyResult(this.formData.question.id)
        .then((votes) => {
          this.votes = votes;
          this.chartData.labels = this.resultData.labels;
          this.chartData.datasets = this.resultData.datasets;
          this.updateChart();
        });
    } else {
      this.votes = [];
      this.chartData.labels = this.resultData.labels;
      this.chartData.datasets = this.resultData.datasets;
      this.updateChart();
    }
  }

  async updateChart(): Promise<void> {
    if (this.$refs.chartRef) {
      const chartRef = this.$refs.chartRef as any;
      chartRef.update();
    }
  }

  async mounted(): Promise<void> {
    this.startIdeaInterval();
  }

  startIdeaInterval(): void {
    this.interval = setInterval(this.getVotes, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }

  /* eslint-disable @typescript-eslint/explicit-module-boundary-types*/
  async dragDone(list: any[]): Promise<void> {
    list.forEach((question, index) => {
      if (question.question.id) {
        question.question.order = index;
        hierarchyService.putHierarchy(question.question.id, question.question);
      }
    });
  }
}
</script>

<style scoped>
.el-input {
  --el-input-background-color: white;
}
</style>
