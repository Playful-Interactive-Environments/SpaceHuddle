<template>
  <div class="AnalyticsWrapper" v-if="hasParticipantData && !isLoading">
    <div
      class="participantSelection"
      :style="{
        height: participantSelectionCollapsed ? '1.5rem' : 'auto',
      }"
    >
      <participant-selection
        class="participantSelectionModule"
        :participants="participants"
        v-model:selectedParticipantIds="selectedParticipantIds"
        @participant-selected="participantSelectionChanged"
      />
      <font-awesome-icon
        :icon="['fas', 'question']"
        class="tutorialIcon"
        @click="showParticipantSelectionTutorial = true"
      />
      <ToolTip
        :text="
          !participantSelectionCollapsed
            ? $t('moderator.organism.analytics.collapse')
            : $t('moderator.organism.analytics.expand')
        "
        :placement="'bottom'"
      >
        <div
          class="collapseParticipantSelection"
          @click="
            participantSelectionCollapsed = !participantSelectionCollapsed
          "
        >
          <font-awesome-icon
            v-if="!participantSelectionCollapsed"
            :icon="['fas', 'chevron-up']"
            class="collapseIcon"
          />
          <font-awesome-icon
            v-else
            :icon="['fas', 'chevron-down']"
            class="collapseIcon"
          />
        </div>
      </ToolTip>
    </div>
    <div id="analytics" :style="{ marginTop: '3rem' }">
      <el-collapse v-model="activeNames">
        <el-collapse-item name="overview" v-if="hasAxesAndData">
          <template #title>
            <span class="titleSpan" @click.stop
              >{{ $t('moderator.organism.analytics.parallelCoordinates.title')
              }}<font-awesome-icon
                :icon="['fas', 'question']"
                class="tutorialIcon"
                @click="showOverviewTutorial = true"
            /></span>
          </template>
          <div class="AnalyticsParallelCoordinates">
            <parallel-coordinates
              :chart-axes="availableAxes"
              :participant-data="dataEntries"
              :steps="steps"
              v-model:selectedParticipantIds="selectedParticipantIds"
              @participant-selected="participantSelectionChanged"
            />
          </div>
        </el-collapse-item>
        <el-collapse-item
          name="tables"
          class="pdfPageBreakElement dropDownTables"
          :style="{
            width: tableElements.length > 1 || tablesExpanded ? '100%' : '50%',
          }"
          v-if="hasAxesAndData && hasSteps"
        >
          <template #title>
            <span class="titleSpan" @click.stop>
              <ToolTip
                :text="$t('moderator.organism.analytics.tables.selectTask')"
                :show-after="300"
                class="titleToolTip"
                ><task-selection-dropdown
                  :available-elements="availableAxes"
                  v-model:elements="tableElements"
                  class="taskSelectionDropdown"
                >
                  {{ $t('moderator.organism.analytics.tables.title') }}
                  <font-awesome-icon
                    :icon="['fas', 'plus']"
                    class="plus-icon"
                  />
                </task-selection-dropdown> </ToolTip
              ><font-awesome-icon
                :icon="['fas', 'question']"
                class="tutorialIcon"
                @click="showTableTutorial = true"
              />
            </span>
          </template>
          <div class="AnalyticsTables">
            <DraggableCardList
              :chart-data="axes"
              v-model:selectedElements="tableElements"
              v-model:selectedParticipantIds="selectedParticipantIds"
              v-model:expanded="tablesExpanded"
              :dropdownItems="axes"
              :getParticipantCount="getTablesParticipantCount"
              @hasExpandedElement="handleHasExpandedElement"
            >
              <template #card-header-icon="{ element }">
                <font-awesome-icon
                  v-if="element"
                  class="highscoreModuleIcon"
                  :icon="getIconOfAxis(element)"
                  :style="{ color: getColorOfAxis(element) }"
                />
              </template>
              <template>
                <font-awesome-icon
                  class="axisIcon"
                  :icon="getIconOfAxis(item)"
                  :style="{ color: getColorOfAxis(item) }"
                />
              </template>
              <template
                #item-content="{
                  element,
                  selectedParticipantIds,
                  updateSelectedParticipantIds,
                }"
              >
                <Highscore
                  v-if="element"
                  class="highscore"
                  :task-id="element.taskData.taskId"
                  :table-data="filterParticipantData(element.taskData.taskId)"
                  :selected-participant-ids="selectedParticipantIds"
                  @update:selected-participant-ids="
                    updateSelectedParticipantIds
                  "
                  :translation-path="getTranslationPath(element)"
                />
              </template>
            </DraggableCardList>
          </div>
        </el-collapse-item>
        <el-collapse-item
          v-if="hasSurveyData"
          name="surveysQuizzes"
          class="pdfPageBreakElement dropDownSurveysQuizzes"
          :style="{
            width:
              surveyElements.length > 1 || surveysExpanded ? '100%' : '50%',
          }"
        >
          <template #title>
            <span class="titleSpan" @click.stop>
              <ToolTip
                :text="$t('moderator.organism.analytics.tables.selectTask')"
                :show-after="300"
                ><task-selection-dropdown
                  :available-elements="surveyData"
                  v-model:elements="surveyElements"
                  class="taskSelectionDropdown"
                >
                  {{
                    $t('moderator.organism.analytics.stackedBarCharts.title')
                  }}
                  <font-awesome-icon
                    :icon="['fas', 'plus']"
                    class="plus-icon"
                  />
                </task-selection-dropdown>
              </ToolTip>
              <font-awesome-icon
                :icon="['fas', 'question']"
                class="tutorialIcon"
                @click="showSurveyTutorial = true"
              />
            </span>
          </template>
          <div class="stackedBarChartContainer">
            <DraggableCardList
              :chart-data="surveyData"
              v-model:selectedElements="surveyElements"
              v-model:selectedParticipantIds="selectedParticipantIds"
              v-model:expanded="surveysExpanded"
              :dropdownItems="surveyData"
              :getParticipantCount="getSurveyParticipantCount"
              @hasExpandedElement="handleHasExpandedElement"
            >
              <template #card-header-icon="{ element }">
                <font-awesome-icon
                  v-if="element"
                  class="highscoreModuleIcon"
                  :icon="getTaskIconOfType(TaskType.INFORMATION)"
                  :style="{ color: getTaskColorOfType(TaskType.INFORMATION) }"
                />
              </template>
              <template>
                <font-awesome-icon
                  class="axisIcon"
                  :icon="getTaskIconOfType(TaskType.INFORMATION)"
                  :style="{ color: getTaskColorOfType(TaskType.INFORMATION) }"
                />
              </template>
              <template
                #item-content="{
                  element,
                  selectedParticipantIds,
                  updateSelectedParticipantIds,
                }"
              >
                <stacked-bar-chart
                  v-if="element"
                  :task-id="element.taskData.taskId"
                  :has-correct="isQuizOrTalk(element)"
                  :chart-data="element.questions"
                  :color-theme="colorTheme"
                  :color-correct="'var(--color-brainstorming)'"
                  :color-incorrect="'var(--color-evaluating)'"
                  :selected-participant-ids="selectedParticipantIds"
                  @update:selected-participant-ids="
                    updateSelectedParticipantIds
                  "
                />
              </template>
            </DraggableCardList>
          </div>
        </el-collapse-item>
        <el-collapse-item
          v-if="hasRadarData"
          name="radarCharts"
          class="pdfPageBreakElement"
          :style="{
            width: radarElements.length > 1 || radarExpanded ? '100%' : '50%',
          }"
        >
          <template #title>
            <span class="titleSpan" @click.stop>
              <ToolTip
                :text="$t('moderator.organism.analytics.tables.selectTask')"
                :show-after="300"
                ><task-selection-dropdown
                  :available-elements="radarDataEntries"
                  v-model:elements="radarElements"
                  class="taskSelectionDropdown"
                >
                  {{ $t('moderator.organism.analytics.radarCharts.title') }}
                  <font-awesome-icon
                    :icon="['fas', 'plus']"
                    class="plus-icon"
                  />
                </task-selection-dropdown>
              </ToolTip>
              <font-awesome-icon
                :icon="['fas', 'question']"
                class="tutorialIcon"
                @click="showSurveyTutorial = true"
              />
            </span>
          </template>
          <div class="stackedBarChartContainer">
            <DraggableCardList
              :chart-data="radarDataEntries"
              v-model:selectedElements="radarElements"
              v-model:selectedParticipantIds="selectedParticipantIds"
              v-model:expanded="radarExpanded"
              :dropdownItems="radarDataEntries"
              :getParticipantCount="getRadarParticipantCount"
              @hasExpandedElement="handleHasExpandedElement"
            >
              <template #card-header-icon="{ element }">
                <font-awesome-icon
                  v-if="element"
                  class="highscoreModuleIcon"
                  :icon="getTaskIconOfType(TaskType.INFORMATION)"
                  :style="{ color: getTaskColorOfType(TaskType.INFORMATION) }"
                />
              </template>
              <template>
                <font-awesome-icon
                  class="axisIcon"
                  :icon="getTaskIconOfType(TaskType.INFORMATION)"
                  :style="{ color: getTaskColorOfType(TaskType.INFORMATION) }"
                />
              </template>
              <template
                #item-content="{
                  element,
                  selectedParticipantIds,
                  updateSelectedParticipantIds,
                }"
              >
                <radar-chart-container
                  :labels="element.labels"
                  :datasets="element.data"
                  :test="element.test"
                  :title="element.title"
                  :size="300"
                  :levels="5"
                  :defaultColor="'var(--color-dark-contrast-light)'"
                  :selected-participant-ids="selectedParticipantIds"
                  @update:selected-participant-ids="
                    updateSelectedParticipantIds
                  "
                />
              </template>
            </DraggableCardList>
          </div>
        </el-collapse-item>
      </el-collapse>
      <el-dialog
        v-model="showParticipantSelectionTutorial"
        :title="
          $t('moderator.organism.analytics.tutorial.participantSelection.title')
        "
      >
        {{
          $t('moderator.organism.analytics.tutorial.participantSelection.info')
        }}
      </el-dialog>
      <el-dialog
        v-model="showOverviewTutorial"
        :title="$t('moderator.organism.analytics.tutorial.overview.title')"
        >{{
          $t('moderator.organism.analytics.tutorial.overview.info')
        }}</el-dialog
      >
      <el-dialog
        v-model="showTableTutorial"
        :title="$t('moderator.organism.analytics.tutorial.table.title')"
        >{{ $t('moderator.organism.analytics.tutorial.table.info') }}</el-dialog
      >
      <el-dialog
        v-model="showSurveyTutorial"
        :title="
          $t('moderator.organism.analytics.tutorial.stackedBarChart.title')
        "
      >
        {{ $t('moderator.organism.analytics.tutorial.stackedBarChart.info') }}
      </el-dialog>
      <el-dialog
        v-model="showRadarTutorial"
        :title="$t('moderator.organism.analytics.tutorial.radarChart.title')"
        >{{
          $t('moderator.organism.analytics.tutorial.radarChart.info')
        }}</el-dialog
      >
    </div>
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
import { VoteResult } from '@/types/api/Vote';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import RadarChart from '@/components/moderator/organisms/analytics/subOrganisms/radarChart.vue';
import { TaskParticipantState } from '@/types/api/TaskParticipantState';
import StackedBarChart from '@/components/moderator/organisms/analytics/subOrganisms/stackedBarChart.vue';
import { getColorOfType, getIconOfType } from '@/types/enum/TaskCategory';
import ParticipantSelection from '@/components/moderator/organisms/analytics/subOrganisms/participantSelection.vue';
import { Topic } from '@/types/api/Topic';
import TaskSelectionDropdown from '@/components/moderator/organisms/analytics/subOrganisms/taskSelectionDropdown.vue';
import ToolTip from '@/components/shared/atoms/ToolTip.vue';
import { Hierarchy } from '@/types/api/Hierarchy';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import DraggableCardList from '@/components/moderator/organisms/analytics/subOrganisms/draggableCardList.vue';
import Highscore, {
  HighScoreEntry,
} from '@/components/moderator/organisms/analytics/Highscore.vue';
import RadarChartContainer from '@/components/moderator/organisms/analytics/subOrganisms/radarChartContainer.vue';

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

interface SurveyData {
  taskData: {
    moduleName: string;
    taskId: string;
    taskName: string;
    taskType: TaskType;
    topicName: string;
    topicOrder: number;
  };
  questions: QuestionData[];
}

interface RadarData {
  taskData: {
    moduleName: string;
    taskId: string;
    taskName: string;
    taskType: TaskType;
    topicName: string;
    topicOrder: number;
  };
  test: string;
  labels: string[];
  data: { data: number[]; avatar: Avatar }[];
}

@Options({
  computed: {
    TaskType() {
      return TaskType;
    },
  },
  components: {
    RadarChartContainer,
    Highscore,
    DraggableCardList,
    FontAwesomeIcon,
    ToolTip,
    TaskSelectionDropdown,
    StackedBarChart,
    RadarChart,
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

  participantSelectionCollapsed = false;
  surveysExpanded = false;
  tablesExpanded = false;
  radarExpanded = false;

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
  participants: ParticipantInfo[] = [];
  sessionService?: cashService.SimplifiedCashEntry<Session>;

  selectedParticipantIds: string[] = [];

  axes: Axis[] = [];
  surveyData: SurveyData[] = [];
  radarDataEntries: RadarData[] = [];

  activeNames = [
    'participantSelection',
    'overview',
    'tables',
    'radarCharts',
    'surveysQuizzes',
  ];

  tableElements: Axis[] = [];
  surveyElements: SurveyData[] = [];
  radarElements: SurveyData[] = [];

  showParticipantSelectionTutorial = false;
  showOverviewTutorial = false;
  showTableTutorial = false;
  showSurveyTutorial = false;
  showRadarTutorial = false;

  get isLoading(): boolean {
    return this.receivedTasks.length <= 0;
  }

  get hasSteps(): boolean {
    return this.steps.length > 0;
  }

  get hasAxesAndData(): boolean {
    return this.axes.length > 0 && this.dataEntries.length > 0;
  }

  get availableAxes(): Axis[] {
    return this.axes.filter((axis) => axis.available);
  }

  get hasSurveyData(): boolean {
    return this.surveyData.length > 0;
  }

  get hasRadarData(): boolean {
    return this.radarDataEntries.length > 0;
  }

  get hasParticipantData(): boolean {
    return this.participants.length > 0;
  }

  get topicId(): string | null {
    return this.task?.topicId || null;
  }

  get getSessionId(): string | null {
    return this.sessionId || this.task?.sessionId || null;
  }

  resetData(): void {
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
    cashService.deregisterAllGet(this.updateQuizTasks);
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
  }

  getIconOfAxis(axis: Axis): string | undefined {
    return axis.taskData.taskType
      ? getIconOfType(TaskType[axis.taskData.taskType.toUpperCase()])
      : undefined;
  }

  getColorOfAxis(axis: Axis): string | undefined {
    return axis.taskData.taskType
      ? getColorOfType(TaskType[axis.taskData.taskType.toUpperCase()])
      : undefined;
  }

  handleHasExpandedElement(expanded: boolean): void {
    console.log('DraggableCardList expanded state changed:', expanded);
  }

  get tableChartData(): DataEntry[] {
    return this.dataEntries.filter((entry) =>
      entry.axes.some((axis) =>
        axis.axisValues.some((value) => value.value != null)
      )
    );
  }

  filterParticipantData(taskId: string): HighScoreEntry[] {
    return this.tableChartData
      .filter((entry) =>
        entry.axes.some(
          (a) =>
            a.taskId === taskId &&
            a.axisValues.some((value) => value.value != null)
        )
      )
      .map((entry) => ({
        values:
          entry.axes
            .find((axis) => axis.taskId === taskId)
            ?.axisValues.sort(
              (a, b) =>
                (['stars', 'rate'].includes(a.id) ? 1 : -1) -
                (['stars', 'rate'].includes(b.id) ? 1 : -1)
            ) || [],
        participant: entry.participant,
      }));
  }

  getTranslationPath(axis: Axis): string {
    return `module.${axis.taskData.taskType.toLowerCase()}.${
      axis.taskData.moduleName
    }.analytics.highscore.`;
  }

  isQuizOrTalk(survey: SurveyData): boolean {
    return ['quiz', 'talk'].includes(survey.taskData.moduleName);
  }

  getTablesParticipantCount(axis: Axis): number {
    const taskId = axis.taskData.taskId;
    const category = axis.categoryActive;

    return this.tableChartData.reduce((counter, partData) => {
      const partAxis = partData.axes.find(
        (partAxis) => partAxis.taskId === taskId
      );
      if (partAxis) {
        counter += partAxis.axisValues.reduce((count, value) => {
          return (
            count + (value.id === category && value.value !== null ? 1 : 0)
          );
        }, 0);
      }
      return counter;
    }, 0);
  }

  getSurveyParticipantCount(survey: SurveyData): number {
    const uniqueAvatarIds = new Set<string>();
    survey.questions.forEach((questionData) => {
      questionData.answers.forEach((answer) => {
        uniqueAvatarIds.add(answer.avatar.id);
      });
    });
    return uniqueAvatarIds.size;
  }

  getRadarParticipantCount(radar: RadarData): number {
    return radar.data.length;
  }

  getTaskColorOfType(taskType: TaskType) {
    return getColorOfType(taskType);
  }

  getTaskIconOfType(taskType: TaskType) {
    return getIconOfType(taskType);
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
          this.updatePersonalityTests(result, task);
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
    this.axes = this.CalculateAxes();
  }

  updatePersonalityTests(result: TaskParticipantState[], task: Task): void {
    const resultFiltered = result.filter(
      (entry) => entry.parameter.resultTypeValues
    );
    if (resultFiltered[0].parameter.resultTypeValues) {
      const radarData = {
        taskData: {
          moduleName: task.modules[0].name,
          taskId: task.id,
          taskName: task.name,
          taskType: task.taskType as TaskType,
          topicName: this.topics[task.topicOrder]?.title ?? '',
          topicOrder: task.topicOrder,
        },
        test: task.modules[0].parameter.test,
        labels: Object.keys(resultFiltered[0].parameter.resultTypeValues),
        data: resultFiltered
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
          (entry) => entry.taskData.taskId === radarData.taskData.taskId
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
    const participantSumCorrect: Map<string, number> = new Map();

    newSteps.forEach((step) => {
      const participantId = step.avatar.id;
      if (!participantSumCorrect.has(participantId)) {
        participantSumCorrect.set(participantId, 0);
      }
      if (step.state === 'CORRECT') {
        const currentSumCorrect = participantSumCorrect.get(participantId) || 0;
        participantSumCorrect.set(participantId, currentSumCorrect + 1);
      }
      step.parameter.sumCorrect = participantSumCorrect.get(participantId) || 0;
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
    'maxSpeed',
    'averageSpeed',
    'consumption',
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
          active:
            (taskData.taskType as string) === 'BRAINSTORMING' ? false : active,
          available: active,
        };
      })
      .sort((a, b) => a.taskData.topicOrder - b.taskData.topicOrder);
  }

  CalculateDataEntries(): DataEntry[] {
    const participantData = this.getAllParticipantData();
    const axes = this.CalculateAxes();

    if (axes.length <= 0 || participantData.length <= 0) {
      return [];
    }

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
.AnalyticsWrapper {
  width: 100%;
  position: relative;
}
.participantSelection {
  position: sticky;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: height 0.3s ease;
  top: 0;
  width: 100%;
  background-color: var(--color-background);
  border-bottom: 2px solid var(--color-background-dark);
  z-index: 500;
  .tutorialIcon {
    position: absolute;
    right: 1rem;
    font-size: var(--font-size-small);
    cursor: pointer;
  }
  .participantSelectionModule {
    height: 100%;
    overflow: hidden;
  }
  .collapseParticipantSelection {
    position: absolute;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.2rem;
    height: 1.2rem;
    bottom: -1.2rem;
    font-size: 0.7rem;
    transition: background-color 0.4s ease;
    color: var(--color-background);
    background-color: var(--color-background-darker);
    border-radius: 0 0 var(--border-radius-small) var(--border-radius-small);
    cursor: pointer;
    .collapseIcon {
      display: flex;
      align-items: center;
      justify-content: center;
    }
  }
  .collapseParticipantSelection:hover {
    background-color: var(--color-evaluating);
  }
}

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
    transition: width 0.4s ease;
    width: 100%;
    .taskSelectionDropdown,
    .tutorialIcon {
      cursor: pointer;
    }
    .titleSpan {
      height: 100%;
      width: calc(100% - 2rem);
      display: flex;
      align-items: center;
      justify-content: space-between;
      cursor: default;
      .tutorialIcon {
        margin-right: 0.3rem;
        font-size: var(--font-size-small);
      }
    }
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
    .radarChartHeading {
      display: flex;
      flex-direction: row;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      .RadarModuleName {
        font-size: var(--font-size-default);
        font-weight: var(--font-weight-bold);
        margin: 0;
      }
      .participant-count {
        margin-left: 1rem;
      }
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
