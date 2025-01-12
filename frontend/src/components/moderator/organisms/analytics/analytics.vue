<template>
  <div
    id="analytics"
    :style="{ marginTop: '3rem' }"
    v-loading="loadingSteps"
    :element-loading-background="'var(--color-background)'"
    :element-loading-text="$t('moderator.organism.analytics.loading')"
  >
    <div
      class="AnalyticsParallelCoordinates"
      :style="{
        opacity: loadingSteps ? 0 : 1,
      }"
    >
      <parallel-coordinates
        v-if="axes.length > 0 && dataEntries.length > 0 && !loadingSteps"
        :chart-axes="
          JSON.parse(JSON.stringify(axes.filter((axis) => axis.available)))
        "
        :participant-data="JSON.parse(JSON.stringify(dataEntries))"
        :steps="JSON.parse(JSON.stringify(steps))"
        :selected-participant-id="selectedParticipantId"
      />
    </div>

    <div
      class="AnalyticsTables"
      :element-loading-background="'var(--color-background)'"
      :style="{
        opacity: loadingSteps ? 0 : 1,
      }"
    >
      <Tables
        v-if="axes.length > 0 && dataEntries.length > 0 && !loadingSteps"
        :participant-data="JSON.parse(JSON.stringify(dataEntries))"
        :axes="
          JSON.parse(JSON.stringify(axes.filter((axis) => axis.available)))
        "
        @participant-selected="participantSelectionChanged"
        :style="{
          opacity: loadingSteps ? 0 : 1,
        }"
      />
    </div>
<!--    <div class="RadarChartContainer">
    <radar-chart
      :labels="['Strength', 'Speed', 'Stamina', 'Skill', 'Intelligence', 'Agility']"
      :datasets="[
    { data: [65, 59, 90, 81, 56, 55], color: 'rgba(54, 162, 235, 1)' },
    { data: [28, 48, 40, 19, 96, 27], color: 'rgba(255, 99, 132, 1)' }
  ]"
      :size="300"
      :levels="5"
    />
    </div>-->
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Task } from '@/types/api/Task';
import { getEmptyComponent } from '@/modules';
import TaskType from '@/types/enum/TaskType';
import * as taskService from '@/services/task-service';
import * as sessionService from '@/services/session-service';
import * as taskParticipantService from '@/services/task-participant-service';
import * as cashService from '@/services/cash-service';
import * as votingService from '@/services/voting-service';
import * as ideaService from '@/services/idea-service';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import * as pixiUtil from '@/utils/pixi';
import Gallery from '@/modules/common/visualisation_master/organisms/gallery.vue';

import SpriteCanvas from '@/components/shared/atoms/game/SpriteCanvas.vue';
import { Session } from '@/types/api/Session';
import QrcodeVue from 'qrcode.vue';
import { ParticipantInfo } from '@/types/api/Participant';
import ParallelCoordinates from '@/components/moderator/organisms/analytics/subOrganisms/parallelCoordinates.vue';
import Tables from '@/components/moderator/organisms/analytics/subOrganisms/Tables.vue';
import { VoteResult } from '@/types/api/Vote';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import RadarChart from '@/components/moderator/organisms/analytics/subOrganisms/radarChart.vue';

/* eslint-disable @typescript-eslint/no-explicit-any*/

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

@Options({
  components: {
    RadarChart,
    Tables,
    ParallelCoordinates,
    SpriteCanvas,
    IdeaCard,
    Gallery,
    QrcodeVue,
    PublicScreenComponent: getEmptyComponent(),
  },
})
export default class Analytics extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly task!: Task;
  @Prop() readonly receivedTasks!: Task[] | undefined;
  @Prop() readonly sessionId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;

  tasks: Task[] = [];
  gameTasks: Task[] = [];
  otherTasks: Task[] = [];
  votingTasks: Task[] = [];
  brainstormingTasks: Task[] = [];

  ideas: Idea[] = [];
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

  selectedParticipantId = '';

  get topicId(): string | null {
    if (this.task) return this.task.topicId;
    return null;
  }

  get getSessionId(): string | null {
    if (this.sessionId) {
      return this.sessionId;
    } else if (this.task) {
      return this.task.sessionId;
    }
    return null;
  }

  resetData(): void {
    this.axes = [];
    this.steps = [];
    this.tasks = [];
    this.gameTasks = [];
    this.otherTasks = [];
    this.votingTasks = [];
    this.brainstormingTasks = [];
    this.ideas = [];
    this.votes = [];
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
    if (this.receivedTasks != undefined) {
      this.updateTasks(this.receivedTasks);
    }
  }

  unmounted(): void {
    this.deregisterAll();
  }

  participantSelectionChanged(id: string) {
    this.selectedParticipantId = id;
    this.$emit('participantSelected', id);
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

    if (this.topicId && this.receivedTasks == undefined) {
      cashService.deregisterAllGet(this.updateTasks);
      this.taskListService = taskService.registerGetTaskList(
        this.topicId,
        this.updateTasks,
        this.authHeaderTyp,
        30 * 60
      );
    } else {
      cashService.deregisterAllGet(this.updateTasks);
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

  //eslint-disable-next-line @typescript-eslint/no-unused-vars
  updateTasks(tasks: Task[]): void {
    this.resetData();
    this.deregisterSteps();
    this.tasks = tasks.sort((a, b) => a.order - b.order);
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

    for (const task of this.brainstormingTasks) {
      ideaService.registerGetIdeasForTask(
        task.id,
        null,
        null,
        this.updateIdeas,
        this.authHeaderTyp,
        30 * 60
      );
    }
    this.calculateSteps(this.tasks);
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
      } else if ((task.taskType as string) === 'VOTING') {
        votingService.registerGetResult(
          task.id,
          (votes: VoteResult[]) => {
            this.updateVotes(task.id, task, votes);
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
    const filteredSteps = steps.filter((step) => step.parameter.gameplayResult);
    const stepsEntry = {
      taskId: taskId,
      taskData: {
        taskType: task.taskType as TaskType,
        taskName: task.name,
        moduleName: task.modules[0].name,
        initOrder: task.order,
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
    if (this.steps.length === this.tasks.length) {
      this.loadingSteps = false;
    }
    this.axes = this.CalculateAxes();
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
    'points',
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
    console.log(this.steps);
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
}
</style>
