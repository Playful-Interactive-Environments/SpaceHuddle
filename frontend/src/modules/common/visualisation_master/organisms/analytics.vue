<template>
  <div id="analytics">
    <el-dropdown
      @command="activeParticipantChanged"
      v-if="participants"
      size="large"
      split-button
      type="primary"
    >
      <font-awesome-icon
        v-if="activeParticipant"
        :icon="activeParticipant.avatar.symbol"
        :style="{ color: activeParticipant.avatar.color }"
      ></font-awesome-icon>
      <p v-else>/</p>
      <template #dropdown>
        <el-dropdown-item
          v-for="participant in participants"
          :key="participant.id"
          :command="participant"
          ><font-awesome-icon
            :icon="participant.avatar.symbol"
            :style="{ color: participant.avatar.color }"
          ></font-awesome-icon
        ></el-dropdown-item>
      </template>
    </el-dropdown>
    <el-button @click="getAllParticipantData">all participant data</el-button>
    <parallel-coordinates />
    <el-button @click="CalculateAxes">calculate Axes</el-button>
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

/* eslint-disable @typescript-eslint/no-explicit-any*/

interface AxisValue {
  id: string;
  range: [number, number];
}

interface ModuleEntry {
  moduleId: string;
  name: string;
  axisValues: AxisValue[];
  categoryActive: string;
  active: boolean;
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
  activeParticipant: ParticipantInfo | null = null;

  steps: {
    module: string;
    moduleId: string;
    steps: TaskParticipantIterationStep[];
  }[] = [];

  activeSteps: {
    module: string;
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

  activeParticipantChanged(selectedParticipant: ParticipantInfo): void {
    this.activeParticipant = selectedParticipant;
    console.log(this.activeParticipant);
    this.activeParticipantStepsChanged(this.activeParticipant.id);
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

  mounted(): void {
    this.CalculateAxes();
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
            task.modules[0].name,
            steps
          );
        },
        EndpointAuthorisationType.MODERATOR,
        15
      );
    }
    console.log(this.steps);
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
    moduleName: string,
    steps: TaskParticipantIterationStep[]
  ): void {
    const filteredSteps = steps.filter((step) => step.parameter.gameplayResult);
    const stepsEntry = {
      moduleId: moduleId,
      module: moduleName,
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

  activeParticipantStepsChanged(participantID: string) {
    const data: {
      moduleId: string;
      module: string;
      steps: TaskParticipantIterationStep[];
    }[] = [];
    for (const stepEntry of this.steps) {
      stepEntry.steps = stepEntry.steps.filter(
        (step) => step.avatar.id === participantID
      );
      data.push(stepEntry);
    }
    this.activeSteps = data;
    console.log(this.activeSteps);
    this.getBestIterationsParticipantSteps(participantID);
  }

  getAllParticipantData() {
    const dataArray: {
      participant: ParticipantInfo;
      data: {
        moduleId: string;
        module: string;
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
    console.log(dataArray);
    return dataArray;
  }

  getBestIterationsParticipantSteps(participantID: string): {
    moduleId: string;
    module: string;
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
        module: stepEntry.module,
        bestStep,
      };
    });
  }

  //Chart prep

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

  @Watch('participantsDataSteps', { immediate: true })
  participantsDataStepsChanged(): void {
    this.CalculateAxes();
    this.CalculateDataEntries();
  }

  CalculateAxes(): void {
    const axes = this.steps.map((step) => {
      const moduleId = step.moduleId;
      const name = step.module;
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
          return maxValue ? { id: value, range: [0, maxValue] } : null;
        })
        .filter(Boolean);

      const active = axisValues.length > 0;
      return {
        moduleId,
        name,
        axisValues,
        categoryActive: active ? (axisValues[0] ? axisValues[0].id : '') : '',
        active,
        available: active,
      };
    });

    console.log(axes);
  }

  CalculateDataEntries(): void {
    console.log('dataEntries');
  }
}
</script>

<style lang="scss" scoped></style>
