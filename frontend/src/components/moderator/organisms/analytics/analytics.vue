<template>
  <div
    id="analytics"
    :style="{ marginTop: '3rem' }"
    v-loading="receivedTasks.length <= 0"
    :element-loading-background="'var(--color-background)'"
    :element-loading-text="$t('moderator.organism.analytics.loadingNoTasks')"
  >
    <div
      class="AnalyticsParallelCoordinates"
      v-loading="loadingSteps"
      :element-loading-background="'var(--color-background)'"
      :element-loading-text="$t('moderator.organism.analytics.loading')"
    >
      <parallel-coordinates
        v-if="axes.length > 0 && dataEntries.length > 0 && !loadingSteps"
        :chart-axes="axes.filter((axis) => axis.available)"
        :participant-data="dataEntries"
        :steps="steps"
        v-model:selectedParticipantIds="selectedParticipantIds"
        @participant-selected="participantSelectionChanged"
        :style="{ opacity: loadingSteps ? 0 : 1 }"
      />
    </div>

    <div
      class="AnalyticsTables"
      v-loading="loadingSteps"
      :element-loading-background="'var(--color-background)'"
      :element-loading-text="$t('moderator.organism.analytics.loading')"
    >
      <Tables
        v-if="axes.length > 0 && dataEntries.length > 0 && !loadingSteps"
        :participant-data="dataEntries"
        :axes="axes.filter((axis) => axis.available)"
        v-model:selectedParticipantIds="selectedParticipantIds"
        :style="{ opacity: loadingSteps ? 0 : 1 }"
      />
    </div>

    <div
      class="RadarChartContainer"
      v-loading="loadingSteps"
      :element-loading-background="'var(--color-background)'"
      :element-loading-text="$t('moderator.organism.analytics.loading')"
    >
      <div
        class="radarChart"
        v-for="(entry, index) of radarDataEntries"
        :key="'radarChart' + index"
        :style="{ opacity: loadingSteps ? 0 : 1 }"
      >
        <p v-if="entry.title !== ''" class="heading">
          <font-awesome-icon
            class="headingIcon"
            :icon="getIconOfType(TaskType.INFORMATION)"
            :style="{
              color: getColorOfType(TaskType.INFORMATION),
            }"
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
    <div
      class="stackedBarChartContainer"
      v-loading="loadingSteps"
      :element-loading-background="'var(--color-background)'"
      :element-loading-text="$t('moderator.organism.analytics.loading')"
    >
      <StackedBarChartSelection
        v-if="surveyData.length > 0 && !loadingSteps"
        :survey-data="surveyData"
        v-model:selectedParticipantIds="selectedParticipantIds"
        :style="{ opacity: loadingSteps ? 0 : 1 }"
      />
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

interface subAxis {
  id: string;
  range: number;
}

interface Axis {
  taskId: string;
  taskData: {
    taskType: TaskType;
    taskName: string;
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
}

interface QuestionData {
  question: string;
  questionType: string;
  parameter: any;
  answers: Answer[];
}

@Options({
  computed: {
    TaskType() {
      return TaskType;
    },
  },
  components: {
    StackedBarChartSelection,
    StackedBarChart,
    RadarChart,
    Tables,
    ParallelCoordinates,
  },
})
export default class Analytics extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly task!: Task;
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
  surveyIdeas: Idea[] = [];
  votes: VoteResult[] = [];

  session: Session | null = null;

  steps: {
    taskId: string;
    taskData: {
      taskType: TaskType;
      taskName: string;
      moduleName: string;
      initOrder: number;
    };
    steps: TaskParticipantIterationStep[];
  }[] = [];

  participantCash?: cashService.SimplifiedCashEntry<ParticipantInfo[]>;
  participants: ParticipantInfo[] | null = null;
  taskListService?: cashService.SimplifiedCashEntry<Task[]>;
  sessionService?: cashService.SimplifiedCashEntry<Session>;

  axes: Axis[] = [];
  loadingSteps = true;

  selectedParticipantIds: string[] = [];

  surveyData: {
    taskId: string;
    title: string;
    questions: QuestionData[];
  }[] = [];

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
    this.surveyIdeas = [];
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
    for (const participant of this.participants) {
      (participant as any).selected = false;
    }
  }

  updateIdeas(ideas: Idea[]): void {
    this.ideas = this.ideas.concat(ideas);
  }

  updateSurveyIdeas(ideas: Idea[]): void {
    this.surveyIdeas = this.surveyIdeas.concat(ideas);
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
      task.modules.find((module) => module.name === 'survey')
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
      ideaService.registerGetIdeasForTask(
        task.id,
        null,
        null,
        this.updateSurveyIdeas,
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
        task.taskType !== 'PLAYING' &&
        task.taskType !== 'BRAINSTORMING' &&
        task.taskType !== 'VOTING'
      ) {
        taskParticipantService.registerGetIterationList(
          task.id,
          (steps: TaskParticipantIterationStep[]) => {
            this.updateIterationSteps(task.id, task, steps);
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
      (step) => String(step.taskData.taskType) === 'BRAINSTORMING'
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
    const filteredSteps = (steps || []).filter(
      (step) => step.parameter.gameplayResult
    );
    const stepsEntry = {
      taskId: taskId,
      taskData: {
        taskType: task.taskType as TaskType,
        taskName: task.name,
        moduleName: task.modules[0].name,
        initOrder: Number(`${task.topicOrder}000${task.order}`),
      },
      steps: filteredSteps,
    };
    const index = this.steps.findIndex(
      (step) => step.taskId === stepsEntry.taskId
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
    // Create a task entry with taskId and title
    const taskEntry = {
      taskId: task.id,
      title: task.name,
      questions: [] as {
        question: string;
        questionType: string;
        parameter: {
          minValue?: number;
          maxValue?: number;
        };
        answers: { avatar: Avatar; answer: string[] }[];
      }[],
    };

    const questionData: Record<
      string,
      {
        question: string;
        questionType: string;
        parameter: { minValue?: number; maxValue?: number };
        answers: { avatar: Avatar; answer: string[] }[];
      }
    > = {};

    steps.forEach((entry) => {
      const { avatar, ideaId, parameter } = entry;
      const answer = parameter.answer != null ? parameter.answer : [];

      const question = this.surveyIdeas.find((idea) => idea.id === ideaId);
      if (!question) return;

      const questionKeywords = question.keywords || '';

      const answers: string[] = [];
      if (Array.isArray(answer)) {
        answer.forEach((a) => {
          const answerIdea = this.surveyIdeas.find((idea) => idea.id === a);
          if (answerIdea) {
            answers.push(answerIdea.keywords || '');
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
      });
    });

    // Add the question data to the task entry
    taskEntry.questions = Object.values(questionData);

    // Push the task entry into surveyData
    this.surveyData.push(taskEntry);
  }

  getAllParticipantData() {
    const dataArray: {
      participant: ParticipantInfo;
      data: {
        taskId: string;
        taskData: {
          taskType: TaskType;
          taskName: string;
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
        taskId: stepEntry.taskId,
        taskData: stepEntry.taskData,
        bestStep,
      };
    });
  }

  wantedValues = [
    'stars',
    //'points',
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
    'ideaCount',
    'ratingSum',
    'averageRating',
    'bestIdeaAverageRating',
  ];

  CalculateAxes(): Axis[] {
    return this.steps.map((step) => {
      const { taskId, taskData } = step;
      const axisValues = this.wantedValues.reduce((acc, value) => {
        if (
          (taskData.taskType as string) === 'BRAINSTORMING' &&
          value === 'stars'
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
        taskId,
        taskData,
        axisValues,
        categoryActive: active ? axisValues[0]?.id || '' : '',
        active,
        available: active,
      };
    });
  }

  CalculateDataEntries(): DataEntry[] {
    const participantData = this.getAllParticipantData();
    const axes = this.CalculateAxes();

    return participantData.map(({ participant, data }) => {
      const formattedAxes = axes
        .map((axis) => {
          const moduleData = data.find((d) => d.taskId === axis.taskId);
          const axisValues = axis.axisValues.map((axisValue) => {
            if (!axisValue) return { id: '', value: null };

            const { id } = axisValue;
            let value = null;

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
                  } else {
                    value = source[id];
                  }
                  break;
                }
              }
            }

            return { id, value };
          });
          return { taskId: axis.taskId, axisValues };
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
  hr {
    margin: 1.5rem 0 0.5rem 0;
    width: 70%;
    background-color: var(--color-background-dark);
    border-radius: var(--border-radius);
  }
  .AnalyticsParallelCoordinates {
    height: 50vh;
    width: 100%;
  }
  .AnalyticsTables {
    width: 100%;
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
  }

  .smoothAppear {
    transition: opacity 3s ease;
  }

  .stackedBarChartContainer {
    width: 100%;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-evenly;
    .stackedBarChart {
      display: flex;
      flex-direction: column;
      align-items: center;
      position: relative;
      min-width: 47.5%;
      max-width: 65%;
    }
  }

  .headingIcon {
    font-size: var(--font-size-xlarge);
    cursor: pointer;
  }
}
</style>
