<template>
  <div>
    <el-button @click="getAllParticipantData">all participant data</el-button>
    <el-button @click="console.log(axes)">calculate Axes</el-button>
    <el-button @click="console.log(dataEntries)"
    >calculate DataEntries</el-button
    >
  </div>
  <div id="analytics">
    <parallel-coordinates v-if="axes.length > 0 && dataEntries.length > 0" :chart-axes="axes.filter((axis) => axis.available)" :participant-data="dataEntries" />
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
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import * as pixiUtil from '@/utils/pixi';
import Gallery from '@/modules/common/visualisation_master/organisms/gallery.vue';

import coolItHighScore from '@/modules/playing/coolit/organisms/Highscore.vue';
import moveItHighScore from '@/modules/playing/moveit/organisms/Highscore.vue';
import shopItHighScore from '@/modules/playing/shopit/organisms/Highscore.vue';
import findItHighScore from '@/modules/playing/findit/organisms/Highscore.vue';

import SpriteCanvas from '@/components/shared/atoms/game/SpriteCanvas.vue';
import { Session } from '@/types/api/Session';
import QrcodeVue from 'qrcode.vue';
import { ParticipantInfo } from '@/types/api/Participant';
import ParallelCoordinates from '@/modules/common/visualisation_master/organisms/subOrganisms/parallelCoordinates.vue';
import {Module} from "@/types/api/Module";

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
    ParallelCoordinates,
    SpriteCanvas,
    IdeaCard,
    coolItHighScore,
    moveItHighScore,
    shopItHighScore,
    findItHighScore,
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
  @Prop({ default: [] }) readonly ideas!: Idea[];
  @Prop({ default: false }) readonly paused!: boolean;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  filter: FilterData = { ...defaultFilterData };

  textureToken = pixiUtil.createLoadingToken();

  tasks: Task[] = [];
  gameTasks: Task[] = [];
  otherTasks: Task[] = [];
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

  get topicId(): string | null {
    if (this.task) return this.task.topicId;
    return null;
  }

  get sessionId(): string | null {
    if (this.task) return this.task.sessionId;
    return null;
  }

  get axes(): Axis[] {
    return this.CalculateAxes();
  }

  get dataEntries(): DataEntry[] {
    return this.CalculateDataEntries();
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTasks);
    cashService.deregisterAllGet(this.updateSession);
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
      sessionService.registerGetById(
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
    for (const task of this.gameTasks) {
      taskParticipantService.registerGetIterationStepList(
        task.id,
        (steps: TaskParticipantIterationStep[]) => {
          this.updateIterationSteps(
            task.modules[0].id,
            task,
            steps
          );
        },
        EndpointAuthorisationType.MODERATOR,
        15
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

    this.gameTasks = this.tasks;
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

          const maxValue = range.length ? Math.ceil(Math.max(...range)) : null;
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
        available: active,
      };
    });
  }

  CalculateDataEntries(): DataEntry[] {
    const participantData = this.getAllParticipantData();
    return participantData.map(({ participant, data }) => {
      const axes = this.CalculateAxes();
      const formattedAxes = axes.map((axis) => {
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
      });
      return { participant, axes: formattedAxes };
    });
  }
}
</script>

<style lang="scss" scoped>
#analytics {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;

}
</style>
