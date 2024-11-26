<template>
  <div id="analytics" :style="{ marginTop: '3rem' }">
    <div class="AnalyticsParallelCoordinates">
      <parallel-coordinates
        v-if="axes.length > 0 && dataEntries.length > 0"
        :chart-axes="
          JSON.parse(JSON.stringify(axes.filter((axis) => axis.available)))
        "
        :participant-data="JSON.parse(JSON.stringify(dataEntries))"
      />
    </div>

    <div class="AnalyticsTables">
      <Tables
        v-if="axes.length > 0 && dataEntries.length > 0"
        :participant-data="JSON.parse(JSON.stringify(dataEntries))"
        :axes="
          JSON.parse(JSON.stringify(axes.filter((axis) => axis.available)))
        "
      />
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import {
  defaultFilterData,
  FilterData,
} from '@/components/moderator/molecules/IdeaFilterBase.vue';
import { Task } from '@/types/api/Task';
import { getAsyncModule, getEmptyComponent } from '@/modules';
import ModuleComponentType from '@/modules/ModuleComponentType';
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
import ParallelCoordinates from '@/modules/common/visualisation_master/organisms/subOrganisms/parallelCoordinates.vue';
import { Module } from '@/types/api/Module';
import Tables from '@/modules/common/visualisation_master/organisms/subOrganisms/Tables.vue';
import { Vote, VoteResult } from '@/types/api/Vote';

/* eslint-disable @typescript-eslint/no-explicit-any*/

interface subAxis {
  id: string;
  range: number;
}

interface Axis {
  moduleId: string;
  taskData: {
    taskType: TaskType;
    taskName: string;
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
    moduleId: string;
    axisValues: AxisValue[];
  }[];
}

@Options({
  components: {
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
  @Prop({ default: 0 }) readonly timeModifier!: number;
  @Prop({ default: null }) readonly taskParameter!: any;
  @Prop({ default: false }) readonly paused!: boolean;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  filter: FilterData = { ...defaultFilterData };

  textureToken = pixiUtil.createLoadingToken();

  tasks: Task[] = [];
  gameTasks: Task[] = [];
  otherTasks: Task[] = [];
  votingTasks: Task[] = [];
  brainstormingTasks: Task[] = [];
  ideas: Idea[] = [];

  session: Session | null = null;

  componentLoadIndex = 0;

  steps: {
    moduleId: string;
    taskData: {
      taskType: TaskType;
      taskName: string;
    };
    steps: TaskParticipantIterationStep[];
  }[] = [];

  participantCash?: cashService.SimplifiedCashEntry<ParticipantInfo[]>;
  participants: ParticipantInfo[] | null = null;
  taskListService?: cashService.SimplifiedCashEntry<Task[]>;
  sessionService?: cashService.SimplifiedCashEntry<Session>;

  axes: Axis[] = [];

  get topicId(): string | null {
    if (this.task) return this.task.topicId;
    return null;
  }

  get sessionId(): string | null {
    if (this.task) return this.task.sessionId;
    return null;
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

  unmounted(): void {
    this.deregisterAll();
    pixiUtil.cleanupToken(this.textureToken);
  }

  getModuleName(task: Task): string[] {
    if (task && task.modules && task.modules.length > 0)
      return task.modules.map((module) => module.name);
    return ['default'];
  }

  @Watch('task', { immediate: true })
  async onTaskChanged(): Promise<void> {
    if (this.$options.components) {
      const taskType = TaskType[this.task.taskType];
      await getAsyncModule(
        ModuleComponentType.PUBLIC_SCREEN,
        taskType,
        this.getModuleName(this.task),
        false
      ).then((component) => {
        if (this.$options.components) {
          this.$options.components['PublicScreenComponent'] = component;
          this.componentLoadIndex++;
        }
      });
    }

    if (this.sessionId) {
      this.participantCash = sessionService.registerGetParticipants(
        this.sessionId,
        this.updateParticipants,
        EndpointAuthorisationType.MODERATOR,
        60 * 60
      );
    }

    if (this.topicId) {
      cashService.deregisterAllGet(this.updateTasks);
      this.taskListService = taskService.registerGetTaskList(
        this.topicId,
        this.updateTasks,
        this.authHeaderTyp,
        30 * 60
      );
    }

    if (this.sessionId) {
      cashService.deregisterAllGet(this.updateSession);
      this.sessionService = sessionService.registerGetById(
        this.sessionId,
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

  @Watch('gameTasks', { immediate: true })
  onGameTasksChanged(): void {
    cashService.deregisterAllGet(this.updateIterationSteps);
    for (const task of this.gameTasks) {
      taskParticipantService.registerGetIterationStepList(
        task.id,
        (steps: TaskParticipantIterationStep[]) => {
          this.updateIterationSteps(task.modules[0].id, task, steps);
        },
        EndpointAuthorisationType.MODERATOR,
        30
      );
    }
    for (const task of this.otherTasks) {
      taskParticipantService.registerGetIterationList(
        task.id,
        (steps: TaskParticipantIterationStep[]) => {
          this.updateIterationSteps(task.modules[0].id, task, steps);
        },
        EndpointAuthorisationType.MODERATOR,
        30
      );
    }
  }

  //eslint-disable-next-line @typescript-eslint/no-unused-vars
  updateTasks(tasks: Task[]): void {
    this.deregisterSteps();
    this.tasks = tasks;
    this.gameTasks = this.tasks
      .filter((task) => task.taskType === 'PLAYING')
      .sort();
    this.otherTasks = this.tasks
      .filter((task) => task.taskType !== 'PLAYING')
      .sort();
    this.votingTasks = this.tasks
      .filter((task) => task.taskType === 'VOTING')
      .sort();
    this.brainstormingTasks = this.tasks
      .filter((task) => task.taskType === 'BRAINSTORMING')
      .sort();
  }

  updateSession(session: Session): void {
    this.session = session;
  }

  updateIterationSteps(
    moduleId: string,
    task: Task,
    steps: TaskParticipantIterationStep[]
  ): void {
    const filteredSteps = steps.filter((step) => step.parameter.gameplayResult);
    const stepsEntry = {
      moduleId: moduleId,
      taskData: {
        taskType: task.taskType as TaskType,
        taskName: task.name,
      },
      steps: filteredSteps,
    };
    const index = this.steps.findIndex(
      (step) => step.moduleId === stepsEntry.moduleId
    );

    if (index > -1) {
      this.steps[index] = stepsEntry;
    } else {
      this.steps.push(stepsEntry);
    }
    this.axes = this.CalculateAxes();
  }

  getAllParticipantData() {
    const dataArray: {
      participant: ParticipantInfo;
      data: {
        moduleId: string;
        taskData: {
          taskType: TaskType;
          taskName: string;
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
    moduleId: string;
    taskData: {
      taskType: TaskType;
      taskName: string;
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
        moduleId: stepEntry.moduleId,
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
    'co2',
    'water',
    'electricity',
    'money',
    'lifetime',
    'pointsSpent',
  ];

  CalculateAxes(): Axis[] {
    return this.steps.map((step) => {
      const moduleId = step.moduleId;
      const taskData = step.taskData;
      const axisValues = this.wantedValues
        .map((value) => {
          const range = step.steps.flatMap((subStep) => {
            const sources = [
              subStep,
              subStep.parameter,
              subStep.parameter?.gameplayResult,
              subStep.parameter?.drive,
              subStep.parameter?.game,
            ];
            for (const source of sources) {
              if (source && value in source) return source[value];
            }
            return [];
          });
          let maxValue = range.length ? Math.ceil(Math.max(...range)) : null;
          if (maxValue != null && value === 'stars') {
            maxValue = 3;
          }
          return maxValue ? { id: value, range: maxValue } : null;
        })
        .filter(Boolean);

      const active = axisValues.length > 0;
      return {
        moduleId,
        taskData,
        axisValues,
        categoryActive: active ? (axisValues[0] ? axisValues[0].id : '') : '',
        active,
        available: axisValues.length > 0,
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
          const moduleData = data.find((d) => d.moduleId === axis.moduleId);
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
                  value = source[id];
                  break;
                }
              }
            }

            return { id, value };
          });
          return { moduleId: axis.moduleId, axisValues };
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
  height: 95%;
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
    height: calc(60% - 3rem);
    width: 100%;
  }
  .AnalyticsTables {
    height: 40%;
    width: 100%;
  }
}
</style>
