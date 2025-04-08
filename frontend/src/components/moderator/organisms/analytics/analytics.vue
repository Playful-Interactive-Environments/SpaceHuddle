<template>
  <div
    id="analytics"
    :style="{ marginTop: '3rem' }"
    v-loading="isLoading"
    :element-loading-background="'var(--color-background)'"
    :element-loading-text="loadingTextNoTasks"
  >
    <el-collapse
      v-model="activeNames"
      v-loading="loadingSteps"
      :element-loading-background="'var(--color-background)'"
      :element-loading-text="loadingText"
    >
      <el-collapse-item name="participantSelection">
        <template #title>
          <span @click.stop>{{
            $t('moderator.organism.analytics.participantSelection.title')
          }}</span>
        </template>
        <div v-if="!loadingSteps" class="participantSelection">
          <participant-selection
            :participants="participants"
            v-model:selectedParticipantIds="selectedParticipantIds"
            @participant-selected="participantSelectionChanged"
            :style="{ borderBottom: '2px solid var(--color-background-dark)' }"
          />
        </div>
      </el-collapse-item>
      <el-collapse-item name="overview">
        <template #title>
          <span @click.stop>{{
            $t('moderator.organism.analytics.parallelCoordinates.title')
          }}</span>
        </template>
        <div
          class="AnalyticsParallelCoordinates"
          @click="console.log(dataEntries)"
        >
          <parallel-coordinates
            v-if="hasAxesAndData"
            :chart-axes="availableAxes"
            :participant-data="dataEntries"
            :steps="steps"
            v-model:selectedParticipantIds="selectedParticipantIds"
            @participant-selected="participantSelectionChanged"
            :style="{ opacity: loadingSteps ? 0 : 1 }"
          />
        </div>
      </el-collapse-item>
      <el-collapse-item
        name="tables"
        class="pdfPageBreakElement dropDownTables"
        :style="{
          width: tableElements.length > 1 ? '100%' : '50%',
        }"
      >
        <template #title>
          <span @click.stop
            >{{ $t('moderator.organism.analytics.tables.title') }}
            <ToolTip
              :text="$t('moderator.organism.analytics.tables.selectTask')"
              ><task-selection-dropdown
                :available-elements="availableAxes"
                v-model:elements="tableElements"
            /></ToolTip>
          </span>
        </template>
        <div class="AnalyticsTables">
          <Tables
            v-if="hasAxesAndData"
            :participant-data="dataEntries"
            :axes="availableAxes"
            :steps="steps"
            v-model:tableElements="tableElements"
            v-model:selectedParticipantIds="selectedParticipantIds"
            :style="{ opacity: loadingSteps ? 0 : 1 }"
          />
        </div>
      </el-collapse-item>
      <el-collapse-item
        v-if="surveyData.length > 0"
        name="surveysQuizzes"
        class="pdfPageBreakElement dropDownSurveysQuizzes"
        :style="{
          width: surveyElements.length > 1 ? '100%' : '50%',
        }"
      >
        <template #title>
          <span @click.stop
            >{{ $t('moderator.organism.analytics.stackedBarCharts.title') }}

            <ToolTip
              :text="
                $t('moderator.organism.analytics.stackedBarCharts.selectTask')
              "
              ><task-selection-dropdown
                :available-elements="surveyData"
                v-model:elements="surveyElements" /></ToolTip
          ></span>
        </template>
        <div class="stackedBarChartContainer">
          <StackedBarChartSelection
            v-if="hasSurveyData"
            :survey-data="surveyData"
            v-model:selectedParticipantIds="selectedParticipantIds"
            v-model:survey-elements="surveyElements"
            :style="{ opacity: loadingSteps ? 0 : 1 }"
          />
        </div>
      </el-collapse-item>
      <el-collapse-item
        v-if="radarDataEntries.length > 0"
        name="radarCharts"
        class="pdfPageBreakElement"
      >
        <template #title>
          <span @click.stop>{{
            $t('moderator.organism.analytics.radarCharts.title')
          }}</span>
        </template>
        <div class="RadarChartContainer">
          <div
            class="radarChart"
            v-for="(entry, index) in radarDataEntries"
            :key="'radarChart' + index"
            :style="{ opacity: loadingSteps ? 0 : 1 }"
          >
            <p v-if="entry.title" class="heading">
              <font-awesome-icon
                class="headingIcon"
                :icon="getIconOfType(TaskType.INFORMATION)"
                :style="{ color: getColorOfType(TaskType.INFORMATION) }"
              />
              {{ entry.title }}
            </p>
            <radar-chart
              :labels="entry.labels"
              :datasets="entry.data"
              :test="entry.test"
              :title="entry.title"
              :size="300"
              :levels="5"
              :defaultColor="'var(--color-dark-contrast-light)'"
              v-model:selectedParticipantIds="selectedParticipantIds"
              @participant-selected="participantSelectionChanged"
            />
          </div>
        </div>
      </el-collapse-item>
    </el-collapse>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Task } from '@/types/api/Task';
import TaskType from '@/types/enum/TaskType';
import * as sessionService from '@/services/session-service';
import * as hierarchyService from '@/services/hierarchy-service';
import * as taskParticipantService from '@/services/task-participant-service';
import * as cashService from '@/services/cash-service';
import * as votingService from '@/services/voting-service';
import * as ideaService from '@/services/idea-service';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import { Session } from '@/types/api/Session';
import { Avatar, ParticipantInfo } from '@/types/api/Participant';
import ParallelCoordinates from '@/components/moderator/organisms/analytics/subOrganisms/parallelCoordinates.vue';
import Tables from '@/components/moderator/organisms/analytics/subOrganisms/Tables.vue';
import { VoteResult } from '@/types/api/Vote';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import RadarChart from '@/components/moderator/organisms/analytics/subOrganisms/radarChart.vue';
import { TaskParticipantState } from '@/types/api/TaskParticipantState';
import StackedBarChart from '@/components/moderator/organisms/analytics/subOrganisms/stackedBarChart.vue';
import { getColorOfType, getIconOfType } from '@/types/enum/TaskCategory';
import StackedBarChartSelection from '@/components/moderator/organisms/analytics/subOrganisms/stackedBarChartSelection.vue';
import ParticipantSelection from '@/components/moderator/organisms/analytics/subOrganisms/participantSelection.vue';
import { Topic } from '@/types/api/Topic';
import TaskSelectionDropdown from '@/components/moderator/organisms/analytics/subOrganisms/taskSelectionDropdown.vue';
import ToolTip from '@/components/shared/atoms/ToolTip.vue';
import { Hierarchy } from '@/types/api/Hierarchy';

interface subAxis {
  id: string;
  range: number;
}

interface Axis {
  taskData: {
    taskId: string;
    taskType: TaskType;
    taskName: string;
    topicName: string;
    topicOrder: number;
    moduleName: string;
    initOrder: number;
  };
  axisValues: (subAxis | null)[];
  categoryActive: string;
  active: boolean;
  available: boolean;
}

interface AxisValue {
  id: string;
  value: number | null;
  ideas?: Idea[] | null;
}

interface DataEntry {
  participant: ParticipantInfo;
  axes: {
    taskId: string;
    axisValues: AxisValue[];
  }[];
}

interface Answer {
  avatar: Avatar;
  answer: string[];
  correct?: boolean[] | null;
}

interface QuestionData {
  question: string;
  questionType: string;
  parameter: {
    minValue?: number;
    maxValue?: number;
  };
  answers: Answer[];
}

@Options({
  computed: {
    TaskType() {
      return TaskType;
    },
  },
  components: {
    ToolTip,
    TaskSelectionDropdown,
    StackedBarChartSelection,
    StackedBarChart,
    RadarChart,
    Tables,
    ParallelCoordinates,
    ParticipantSelection,
  },
})
export default class Analytics extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly task!: Task;
  @Prop() readonly topics!: Topic[];
  @Prop() readonly receivedTasks!: Task[];
  @Prop() readonly sessionId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;

  tasks: Task[] = [];
  gameTasks: Task[] = [];
  otherTasks: Task[] = [];
  votingTasks: Task[] = [];
  brainstormingTasks: Task[] = [];
  typoTasks: Task[] = [];
  surveyTasks: Task[] = [];

  ideas: Idea[] = [];
  surveyIdeas: Map<string, Hierarchy> = new Map();
  votes: VoteResult[] = [];

  session: Session | null = null;

  steps: {
    taskData: {
      taskId: string;
      taskType: TaskType;
      taskName: string;
      topicName: string;
      topicOrder: number;
      moduleName: string;
      initOrder: number;
    };
    steps: TaskParticipantIterationStep[];
  }[] = [];

  participantCash?: cashService.SimplifiedCashEntry<ParticipantInfo[]>;
  participants: ParticipantInfo[] | null = null;
  sessionService?: cashService.SimplifiedCashEntry<Session>;

  axes: Axis[] = [];
  loadingSteps = true;

  selectedParticipantIds: string[] = [];

  surveyData: {
    taskData: {
      moduleName: string;
      taskId: string;
      taskName: string;
      taskType: TaskType;
      topicName: string;
      topicOrder: number;
    };
    questions: QuestionData[];
  }[] = [];

  activeNames = [
    'participantSelection',
    'overview',
    'tables',
    'radarCharts',
    'surveysQuizzes',
  ];

  tableElements: Axis[] = [];
  surveyElements: Axis[] = [];

  get isLoading(): boolean {
    return this.receivedTasks.length <= 0;
  }

  get loadingTextNoTasks(): string {
    return this.$t('moderator.organism.analytics.loadingNoTasks');
  }

  get loadingText(): string {
    return this.$t('moderator.organism.analytics.loading');
  }

  get hasAxesAndData(): boolean {
    return (
      this.axes.length > 0 && this.dataEntries.length > 0 && !this.loadingSteps
    );
  }

  get availableAxes(): Axis[] {
    return this.axes.filter((axis) => axis.available);
  }

  get hasSurveyData(): boolean {
    return this.surveyData.length > 0 && !this.loadingSteps;
  }

  getColorOfType(taskType: TaskType) {
    return getColorOfType(taskType);
  }

  getIconOfType(taskType: TaskType) {
    return getIconOfType(taskType);
  }

  get topicId(): string | null {
    return this.task?.topicId || null;
  }

  get getSessionId(): string | null {
    return this.sessionId || this.task?.sessionId || null;
  }

  resetData(): void {
    this.loadingSteps = true;
    this.axes = [];
    this.steps = [];
    this.tasks = [];
    this.gameTasks = [];
    this.otherTasks = [];
    this.votingTasks = [];
    this.brainstormingTasks = [];
    this.typoTasks = [];
    this.surveyTasks = [];
    this.radarDataEntries = [];
    this.ideas = [];
    this.surveyIdeas = new Map();
    this.votes = [];
    this.surveyData = [];
  }

  get dataEntries(): DataEntry[] {
    return this.CalculateDataEntries();
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTasks);
    cashService.deregisterAllGet(this.updateSession);
    cashService.deregisterAllGet(this.updateParticipants);
    cashService.deregisterAllGet(this.updateVotes);
    cashService.deregisterAllGet(this.updateIdeas);
    cashService.deregisterAllGet(this.updateBrainstormings);
    cashService.deregisterAllGet(this.updatePersonalityTests);
    cashService.deregisterAllGet(this.updateSurveyTasks);
    cashService.deregisterAllGet(this.updateSurveyIdeas);
    cashService.deregisterAllGet(this.updateSurveyQuestions);
    cashService.deregisterAllGet(this.updateIterationSteps);
    this.deregisterSteps();
  }

  deregisterSteps(): void {
    for (const task of this.gameTasks) {
      taskParticipantService.deregisterGetIterationStepList(task.id);
    }
  }

  @Watch('receivedTasks', { immediate: true })
  onReceivedTasksChanged(): void {
    this.resetData();
    if (this.receivedTasks.length > 0) {
      this.updateTasks(this.receivedTasks);
    }
  }

  unmounted(): void {
    this.deregisterAll();
  }

  participantSelectionChanged(ids: string[]) {
    this.selectedParticipantIds = ids;
    this.$emit('participantSelected', ids);
  }

  @Watch('task', { immediate: true })
  async onTaskChanged(): Promise<void> {
    if (this.getSessionId) {
      this.participantCash = sessionService.registerGetParticipants(
        this.getSessionId,
        this.updateParticipants,
        EndpointAuthorisationType.MODERATOR,
        60 * 60
      );
    }

    if (this.getSessionId) {
      cashService.deregisterAllGet(this.updateSession);
      this.sessionService = sessionService.registerGetById(
        this.getSessionId,
        this.updateSession,
        this.authHeaderTyp,
        30 * 60
      );
    }
  }

  updateParticipants(participants: ParticipantInfo[]): void {
    this.participants = participants;
  }

  updateIdeas(ideas: Idea[]): void {
    this.ideas = this.ideas.concat(ideas);
  }

  updateSurveyQuestions(questions: Hierarchy[], taskId: string): void {
    for (const question of questions) {
      hierarchyService.registerGetList(
        taskId,
        question.id,
        this.updateSurveyIdeas,
        this.authHeaderTyp,
        30
      );
    }
    cashService.deregisterAllGet(this.updateSurveyIdeas);
  }

  updateSurveyIdeas(answers: Hierarchy[]): void {
    for (const answer of answers) {
      if (answer.id) {
        this.surveyIdeas.set(answer.id, answer);
      }
    }
  }

  @Watch('surveyIdeas', { immediate: true, deep: true })
  onSurveyIdeasChanged(): void {
    cashService.deregisterAllGet(this.updateSurveyTasks);
    for (const task of this.surveyTasks) {
      taskParticipantService.registerGetIterationStepList(
        task.id,
        (steps: TaskParticipantIterationStep[]) => {
          this.updateSurveyTasks(task.id, task, steps);
        },
        EndpointAuthorisationType.MODERATOR,
        30
      );
    }
  }

  updateTasks(tasks: Task[]): void {
    this.deregisterSteps();
    this.tasks = tasks.sort(
      (a, b) =>
        Number(`${a.topicOrder}000${a.order}`) -
        Number(`${b.topicOrder}000${b.order}`)
    );
    this.gameTasks = this.tasks.filter((task) => task.taskType === 'PLAYING');
    this.votingTasks = this.tasks.filter((task) => task.taskType === 'VOTING');
    this.brainstormingTasks = this.tasks.filter(
      (task) => task.taskType === 'BRAINSTORMING'
    );
    this.otherTasks = this.tasks.filter(
      (task) =>
        task.taskType !== 'PLAYING' &&
        task.taskType !== 'BRAINSTORMING' &&
        task.taskType !== 'VOTING'
    );
    this.typoTasks = this.otherTasks.filter((task) =>
      task.modules.find((module) => module.name === 'personalityTest')
    );
    this.surveyTasks = this.otherTasks.filter((task) =>
      task.modules.find(
        (module) => module.name === 'survey' || module.name === 'quiz'
      )
    );
    this.otherTasks = this.otherTasks.filter((task) =>
      task.modules.find(
        (module) =>
          module.name !== 'personalityTest' && module.name !== 'survey'
      )
    );

    for (const task of this.brainstormingTasks) {
      ideaService.registerGetIdeasForTask(
        task.id,
        null,
        null,
        this.updateIdeas,
        this.authHeaderTyp,
        30
      );
    }

    for (const task of this.surveyTasks) {
      this.surveyIdeas = new Map();
      hierarchyService.registerGetQuestions(
        task.id,
        (questions: Hierarchy[]) => {
          this.updateSurveyIdeas(questions);
          this.updateSurveyQuestions(questions, task.id);
        },
        this.authHeaderTyp,
        30
      );
    }

    this.calculateSteps(
      this.gameTasks
        .concat(this.votingTasks)
        .concat(this.brainstormingTasks)
        .concat(this.otherTasks)
    );
  }

  calculateSteps(tasks: Task[]): void {
    cashService.deregisterAllGet(this.updateVotes);
    cashService.deregisterAllGet(this.updateIdeas);
    cashService.deregisterAllGet(this.updateBrainstormings);
    cashService.deregisterAllGet(this.updatePersonalityTests);
    cashService.deregisterAllGet(this.updateIterationSteps);
    for (const task of tasks) {
      if ((task.taskType as string) === 'PLAYING') {
        taskParticipantService.registerGetIterationStepList(
          task.id,
          (steps: TaskParticipantIterationStep[]) => {
            this.updateIterationSteps(task.id, task, steps);
          },
          EndpointAuthorisationType.MODERATOR,
          30
        );
      } else if ((task.taskType as string) === 'BRAINSTORMING') {
        taskParticipantService.registerGetIterationStepList(
          task.id,
          (steps: TaskParticipantIterationStep[]) => {
            this.updateBrainstormings(task.id, task, steps);
          },
          EndpointAuthorisationType.MODERATOR,
          30
        );
      } else if (
        (task.taskType as string) !== 'PLAYING' &&
        (task.taskType as string) !== 'BRAINSTORMING' &&
        (task.taskType as string) !== 'VOTING'
      ) {
        taskParticipantService.registerGetIterationStepList(
          task.id,
          (steps: TaskParticipantIterationStep[]) => {
            this.updateQuizTasks(task.id, task, steps);
          },
          EndpointAuthorisationType.MODERATOR,
          30
        );
      }
    }
    for (const task of this.votingTasks) {
      votingService.registerGetResult(
        task.id,
        (votes: VoteResult[]) => {
          this.updateVotes(task.id, task, votes);
        },
        EndpointAuthorisationType.MODERATOR,
        30
      );
    }
    for (const task of this.typoTasks) {
      taskParticipantService.registerGetList(
        task.id,
        (result: TaskParticipantState[]) => {
          this.updatePersonalityTests(
            result,
            task.modules[0].parameter.test,
            task.name
          );
        },
        EndpointAuthorisationType.MODERATOR,
        30
      );
    }
  }

  updateSession(session: Session): void {
    this.session = session;
  }

  updateBrainstormings(
    id: string,
    task: Task,
    steps: TaskParticipantIterationStep[]
  ): void {
    for (const step of steps) {
      step.parameter.ideas = [
        this.ideas.find((idea) => idea.id === step.ideaId),
      ];
      step.parameter.ideaCount = 1;
    }

    steps = steps.reduce((acc, current) => {
      const existing = acc.find((step) => step.avatar.id === current.avatar.id);
      if (existing) {
        existing.id += current.id;
        existing.parameter.ideaCount += current.parameter.ideaCount;
        existing.parameter.ideas = [
          ...existing.parameter.ideas,
          ...current.parameter.ideas,
        ].sort(
          (a, b) =>
            b.ratingSum / b.countParticipant - a.ratingSum / a.countParticipant
        );
      } else {
        acc.push(current);
      }
      return acc;
    }, [] as TaskParticipantIterationStep[]);

    this.updateIterationSteps(id, task, steps);
  }

  updateVotes(id: string, task: Task, votes: VoteResult[]): void {
    this.votes = votes;
    const newSteps = this.processVotes();
    newSteps && this.updateIterationSteps(id, task, newSteps);
  }

  processVotes(): TaskParticipantIterationStep[] {
    const brainstormingSteps = this.steps.filter(
      (step) => (step.taskData.taskType as string) === 'BRAINSTORMING'
    );

    const newSteps = this.votes.flatMap((vote) =>
      brainstormingSteps
        .map((step) =>
          step.steps.find((entry) => entry.ideaId === vote.idea.id)
        )
        .filter(Boolean)
        .map((idea) => ({
          id: vote.idea.id + 'voteThing',
          iteration: 0,
          step: 0,
          ideaId: null,
          state: TaskParticipantIterationStepStatesType.NEUTRAL,
          parameter: {
            countParticipant: vote.countParticipant,
            ratingSum: vote.ratingSum,
            averageRating: vote.ratingSum / vote.countParticipant,
            gameplayResult: {},
            ideas: [vote],
            bestIdeaAverageRating: vote.ratingSum / vote.countParticipant,
          },
          avatar: idea ? idea.avatar : vote.avatarList[0],
        }))
    );

    return newSteps.reduce((acc, current) => {
      const existing = acc.find((step) => step.avatar.id === current.avatar.id);
      if (existing) {
        existing.id += current.id;
        existing.parameter.countParticipant +=
          current.parameter.countParticipant;
        existing.parameter.ratingSum += current.parameter.ratingSum;
        existing.parameter.averageRating =
          existing.parameter.ratingSum / existing.parameter.countParticipant;
        existing.parameter.ideas = [
          ...existing.parameter.ideas,
          ...current.parameter.ideas,
        ].sort(
          (a, b) =>
            b.ratingSum / b.countParticipant - a.ratingSum / a.countParticipant
        );
        existing.parameter.bestIdeaAverageRating =
          existing.parameter.ideas[0].ratingSum /
          existing.parameter.ideas[0].countParticipant;
      } else {
        acc.push(current);
      }
      return acc;
    }, [] as TaskParticipantIterationStep[]);
  }

  updateIterationSteps(
    taskId: string,
    task: Task,
    steps: TaskParticipantIterationStep[]
  ): void {
    const filteredSteps = steps.filter((step) => step.parameter.gameplayResult);
    const stepsEntry = {
      taskData: {
        taskId: taskId,
        taskType: task.taskType as TaskType,
        taskName: task.name,
        topicName: this.topics[task.topicOrder]?.title ?? '',
        topicOrder: task.topicOrder,
        moduleName: task.modules[0].name,
        initOrder: Number(`${task.topicOrder}000${task.order}`),
      },
      steps: filteredSteps,
    };
    const index = this.steps.findIndex(
      (step) => step.taskData.taskId === stepsEntry.taskData.taskId
    );

    if (index > -1) {
      this.steps[index] = stepsEntry;
    } else {
      this.steps.push(stepsEntry);
    }
    if (
      this.steps.length ===
      this.gameTasks
        .concat(this.votingTasks)
        .concat(this.brainstormingTasks)
        .concat(this.otherTasks).length
    ) {
      this.loadingSteps = false;
    }
    this.axes = this.CalculateAxes();
  }

  radarDataEntries: {
    taskId: string;
    title: string;
    test: string;
    labels: string[];
    data: { data: number[]; avatar: Avatar }[];
  }[] = [];

  updatePersonalityTests(
    result: TaskParticipantState[],
    test: string,
    title: string
  ): void {
    if (result[0].parameter.resultTypeValues) {
      const radarData = {
        taskId: result[0].taskId,
        title: title,
        test: test,
        labels: Object.keys(result[0].parameter.resultTypeValues),
        data: result
          .map((entry) => ({
            data: entry.parameter.resultTypeValues
              ? (Object.values(entry.parameter.resultTypeValues) as number[])
              : [],
            avatar: entry.avatar,
          }))
          .filter((entry) => entry.data.length > 0),
      };
      if (
        !this.radarDataEntries.some(
          (entry) => entry.taskId === radarData.taskId
        )
      ) {
        this.radarDataEntries.push(radarData);
      }
    }
  }

  updateSurveyTasks(
    id: string,
    task: Task,
    steps: TaskParticipantIterationStep[]
  ): void {
    const taskEntry = {
      taskData: {
        moduleName: task.modules[0].name,
        taskId: task.id,
        taskName: task.name,
        taskType: task.taskType as TaskType,
        topicName: this.topics[task.topicOrder]?.title ?? '',
        topicOrder: task.topicOrder,
      },
      questions: [] as {
        question: string;
        questionType: string;
        parameter: {
          minValue?: number;
          maxValue?: number;
        };
        answers: {
          avatar: Avatar;
          answer: string[];
          correct?: boolean[] | null;
        }[];
      }[],
    };

    const questionData: Record<
      string,
      {
        question: string;
        questionType: string;
        parameter: { minValue?: number; maxValue?: number };
        answers: {
          avatar: Avatar;
          answer: string[];
          correct?: boolean[] | null;
        }[];
      }
    > = {};

    steps.forEach((entry) => {
      const { avatar, ideaId, parameter } = entry;
      const answer = parameter.answer != null ? parameter.answer : [];

      const question = this.surveyIdeas.get(ideaId || '');
      if (!question) return;

      const questionKeywords = question.keywords || '';

      const answers: string[] = [];
      const correct: boolean[] = [];
      if (Array.isArray(answer)) {
        answer.forEach((a) => {
          const answerIdea = this.surveyIdeas.get(a);
          if (answerIdea) {
            answers.push(answerIdea.keywords || '');
            correct.push(answerIdea.parameter.isCorrect);
          }
        });
      } else {
        answers.push(answer || '');
      }

      if (!questionData[questionKeywords]) {
        questionData[questionKeywords] = {
          question: questionKeywords,
          questionType: question.parameter.questionType,
          parameter: {
            minValue:
              question.parameter.minValue != null
                ? question.parameter.minValue
                : undefined,
            maxValue:
              question.parameter.maxValue != null
                ? question.parameter.maxValue
                : undefined,
          },
          answers: [],
        };
      }

      questionData[questionKeywords].answers.push({
        avatar,
        answer: answers,
        correct: correct,
      });
    });

    taskEntry.questions = Object.values(questionData);

    const index = this.surveyData.findIndex(
      (entry) => entry.taskData.taskId === taskEntry.taskData.taskId
    );

    if (index === -1) {
      this.surveyData.push(taskEntry);
      this.surveyData.sort(
        (a, b) => a.taskData.topicOrder - b.taskData.topicOrder
      );
    } else {
      this.surveyData.splice(index, 1, taskEntry);
      this.surveyData.sort(
        (a, b) => a.taskData.topicOrder - b.taskData.topicOrder
      );
    }
  }

  updateQuizTasks(
    id: string,
    task: Task,
    steps: TaskParticipantIterationStep[]
  ): void {
    const newSteps: TaskParticipantIterationStep[] = [...steps];
    let sumCorrect = 0;
    newSteps.forEach((step) => {
      if (step.state === TaskParticipantIterationStepStatesType.CORRECT) {
        sumCorrect += 1;
      }
      step.parameter.sumCorrect = sumCorrect;
    });
    this.updateIterationSteps(id, task, newSteps);
  }

  getAllParticipantData() {
    const dataArray: {
      participant: ParticipantInfo;
      data: {
        taskId: string;
        taskData: {
          taskType: TaskType;
          taskName: string;
          topicName: string;
          topicOrder: number;
          moduleName: string;
          initOrder: number;
        };
        bestStep: TaskParticipantIterationStep | null;
      }[];
    }[] = [];
    if (this.participants) {
      for (const participant of this.participants) {
        const data = this.getBestIterationsParticipantSteps(participant.id);
        if (data) {
          dataArray.push({ participant: participant, data: data });
        }
      }
    }

    return dataArray;
  }

  getBestIterationsParticipantSteps(participantID: string): {
    taskId: string;
    taskData: {
      taskType: TaskType;
      taskName: string;
      topicName: string;
      topicOrder: number;
      moduleName: string;
      initOrder: number;
    };
    bestStep: TaskParticipantIterationStep | null;
  }[] {
    return this.steps.map((stepEntry) => {
      const participantSteps = stepEntry.steps.filter(
        (step) => step.avatar.id === participantID
      );

      let bestStep =
        participantSteps.length > 0
          ? participantSteps.reduce(
              (maxStep, currentStep) =>
                (currentStep.parameter.gameplayResult?.stars || 0) >
                (maxStep.parameter.gameplayResult?.stars || 0)
                  ? currentStep
                  : maxStep,
              participantSteps[0]
            )
          : null;

      if (bestStep && bestStep.parameter.gameplayResult.stars === 0) {
        bestStep = participantSteps[participantSteps.length - 1];
      }

      return {
        taskId: stepEntry.taskData.taskId,
        taskData: stepEntry.taskData,
        bestStep,
      };
    });
  }

  wantedValues = [
    'stars',
    'playtime',
    'playTime',
    'maxSpeed',
    'averageSpeed',
    'cardsPlayed',
    'co2',
    'water',
    'electricity',
    'money',
    'lifetime',
    'pointsSpent',
    'count',
    'ratingSum',
    'averageRating',
    'bestIdeaAverageRating',
    'ideas',
    'sumCorrect',
  ];

  CalculateAxes(): Axis[] {
    return this.steps
      .map((step) => {
        const { taskData } = step;
        const axisValues = this.wantedValues.reduce((acc, value) => {
          if (
            ((taskData.taskType as string) === 'BRAINSTORMING' &&
              value === 'stars') ||
            ((taskData.taskType as string) !== 'BRAINSTORMING' &&
              value === 'ideas')
          ) {
            return acc;
          }

          const maxValue = step.steps.reduce((max, subStep) => {
            const sources = [
              subStep,
              subStep.parameter,
              subStep.parameter?.gameplayResult,
              subStep.parameter?.drive,
              subStep.parameter?.game,
            ];
            for (const source of sources) {
              if (source && value in source) {
                if (Array.isArray(source[value])) {
                  return Math.max(max, source[value].length);
                }
                return Math.max(max, source[value]);
              }
            }
            return max;
          }, 0);

          if (maxValue) {
            acc.push({
              id: value,
              range: value === 'stars' ? 3 : Math.ceil(maxValue),
            });
          }

          return acc;
        }, [] as { id: string; range: number }[]);

        const active = axisValues.length > 0;

        return {
          taskData,
          axisValues,
          categoryActive: active ? axisValues[0]?.id || '' : '',
          active,
          available: active,
        };
      })
      .sort((a, b) => a.taskData.topicOrder - b.taskData.topicOrder);
  }

  CalculateDataEntries(): DataEntry[] {
    const participantData = this.getAllParticipantData();
    const axes = this.CalculateAxes();

    return participantData.map(({ participant, data }) => {
      const formattedAxes = axes
        .map((axis) => {
          const moduleData = data.find(
            (d) => d.taskId === axis.taskData.taskId
          );
          const axisValues = axis.axisValues.map((axisValue) => {
            if (!axisValue) return { id: '', value: null, ideas: null };
            const { id } = axisValue;
            let value = null;
            let ideas = null;

            if (moduleData?.bestStep) {
              const sources = [
                moduleData.bestStep,
                moduleData.bestStep.parameter,
                moduleData.bestStep.parameter?.gameplayResult,
                moduleData.bestStep.parameter?.drive,
                moduleData.bestStep.parameter?.game,
              ];

              for (const source of sources) {
                if (source && id in source) {
                  if (Array.isArray(source[id])) {
                    value = source[id].length;
                    if (id === 'ideas') {
                      ideas = source[id].filter((idea) => idea != undefined);
                    }
                  } else {
                    value = source[id];
                  }
                  break;
                }
              }
            }

            return { id, value, ideas };
          });
          return { taskId: axis.taskData.taskId, axisValues };
        })
        .filter((axis) => axis.axisValues.length > 0);
      return { participant, axes: formattedAxes };
    });
  }
}
</script>

<style lang="scss" scoped>
#analytics {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3rem;
  .el-collapse {
    width: 100%;
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
  }
  .el-collapse-item {
    width: 100%;
  }

  .el-collapse-item::v-deep(.el-collapse-item__content) {
    line-height: unset;
  }
  hr {
    margin: 1.5rem 0 0.5rem 0;
    width: 70%;
    background-color: var(--color-background-dark);
    border-radius: var(--border-radius);
  }
  .AnalyticsParallelCoordinates {
    height: 50vh;
    width: 100%;
    margin: 2rem 0;
  }
  .dropDownTables,
  .dropDownSurveysQuizzes {
    @media (max-width: calc((700px * 2) + 12rem)) {
      width: 100% !important;
    }
  }

  .AnalyticsTables,
  .AnalyticsParallelCoordinates {
    transition: opacity 3s ease;
  }

  .participantSelection {
    position: relative;
    width: 100%;
  }

  .RadarChartContainer {
    width: 100%;
    display: flex;
    justify-content: space-evenly;
    align-items: center;
    gap: 2rem;
    overflow: hidden;
    .radarChart {
      display: flex;
      flex-direction: column;
      align-items: center;
    }
  }

  .smoothAppear {
    transition: opacity 3s ease;
  }

  .stackedBarChartContainer {
    width: 100%;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-evenly;
  }

  .headingIcon {
    font-size: var(--font-size-xlarge);
    cursor: pointer;
  }

  .pdfPageBreakElement {
    break-before: page;
  }
}
</style>
