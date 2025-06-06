<template>
  <ParticipantDefaultContainer
    :key="componentLoadIndex"
    :useFullSize="useFullSize"
    :backgroundClass="backgroundClass"
    :drawNavigation="drawNavigation"
  >
    <template #header>
      <el-page-header
        :content="taskType"
        :title="$t('general.back')"
        @back="back"
      />
      <el-tabs
        :stretch="false"
        v-if="task && modules.length > 1"
        v-model="moduleName"
        class="white"
        @tab-click="(tab) => moduleNameClick(tab.paneName)"
      >
        <el-tab-pane
          v-for="module in modules"
          :key="module.name"
          :name="module.name"
        >
          <template #label>
            <span class="awesome-icon" v-if="module.icon">
              <font-awesome-icon :icon="module.icon" />
            </span>
            <span class="text">
              {{ $t(`module.${taskType}.${module.name}.description.title`) }}
            </span>
          </template>
        </el-tab-pane>
      </el-tabs>
      <div class="right" v-if="task && timeLeft">
        <div class="uppercase">
          {{ $t('participant.organism.modelDefaultContainer.timeLeft') }}
        </div>
        <Timer
          class="timer"
          :auth-header-typ="EndpointAuthorisationType.PARTICIPANT"
          :entity="task"
          v-on:timerEnds="goToOverview"
        ></Timer>
      </div>
      <div class="right" v-if="points && showCoins">
        <div class="uppercase">
          <font-awesome-icon icon="coins" />
        </div>
        {{ points }}
      </div>
    </template>
    <participant-tutorial
      v-if="
        moduleDisplayState === ModuleDisplayState.tutorial && task?.description
      "
      :module-info-entry-data-list="['description']"
      @infoRead="moduleDisplayState = ModuleDisplayState.content"
      :info-type="moduleId"
      :showTutorialOnlyOnce="module.parameter.showTutorialOnlyOnce"
    >
      <div class="info-text">
        <h1>
          {{ task.name }}
        </h1>
        <p>
          <markdown-render class="markdown" :source="task.description" />
        </p>
      </div>
    </participant-tutorial>
    <ParticipantFinishedModuleComponent
      v-else-if="
        task &&
        state &&
        state.state === TaskParticipantStatesType.FINISHED &&
        !task.parameter.displayFinished
      "
      :task-id="taskId"
      :module-id="moduleId"
      v-model:useFullSize="useFullSize"
      v-model:backgroundClass="backgroundClass"
    />
    <ParticipantModuleComponent
      v-else-if="task"
      :task-id="taskId"
      :module-id="moduleId"
      v-model:useFullSize="useFullSize"
      v-model:backgroundClass="backgroundClass"
    />
  </ParticipantDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';

import { Task } from '@/types/api/Task';
import TaskType from '@/types/enum/TaskType';

import {
  getAsyncDefaultModule,
  getAsyncModule,
  getEmptyComponent,
  getModuleConfig,
  hasModule,
} from '@/modules';
import ModuleComponentType from '@/modules/ModuleComponentType';
import * as taskService from '@/services/task-service';
import * as sessionService from '@/services/session-service';
import * as timerService from '@/services/timer-service';
import * as taskParticipantService from '@/services/task-participant-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Module } from '@/types/api/Module';
import { ComponentLoadingState } from '@/types/enum/ComponentLoadingState';
import ParticipantDefaultContainer from '@/components/participant/organisms/layout/ParticipantDefaultContainer.vue';
import Timer from '@/components/shared/atoms/Timer.vue';
import * as cashService from '@/services/cash-service';
import { TaskParticipantState } from '@/types/api/TaskParticipantState';
import { RouteName } from '@/types/enum/RouteName';
import { Session } from '@/types/api/Session';
import ParticipantTutorial from '@/components/participant/molecules/ParticipantTutorial.vue';
import MarkdownRender from '@/components/shared/molecules/MarkdownRender.vue';
import TaskParticipantStatesType from '@/types/enum/TaskParticipantStatesType';

enum ModuleDisplayState {
  tutorial = 'tutorial',
  content = 'content',
}

@Options({
  components: {
    MarkdownRender,
    ParticipantTutorial,
    Timer,
    ParticipantDefaultContainer,
    ParticipantModuleComponent: getEmptyComponent(),
    ParticipantFinishedModuleComponent: getEmptyComponent(),
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ParticipantModuleContent extends Vue {
  @Prop({ default: '' }) readonly taskId!: string;

  // eslint-disable-next-line no-undef
  task: Task | null = null;
  state: TaskParticipantState | null = null;

  TaskType = TaskType;
  TaskParticipantStatesType = TaskParticipantStatesType;
  moduleName = '';
  componentLoadIndex = 0;
  componentLoadingState: ComponentLoadingState = ComponentLoadingState.NONE;
  useFullSize = false;
  backgroundClass = '';
  drawNavigation = true;
  points = 0;
  backClicked = false;
  moduleDisplayState = ModuleDisplayState.tutorial;

  EndpointAuthorisationType = EndpointAuthorisationType;
  ModuleDisplayState = ModuleDisplayState;

  get showCoins(): boolean {
    return JSON.parse(process.env.VUE_APP_SHOW_COINS);
  }

  stateCash!: cashService.SimplifiedCashEntry<TaskParticipantState[]>;
  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    this.stateCash = taskParticipantService.registerGetList(
      this.taskId,
      this.updateState,
      EndpointAuthorisationType.PARTICIPANT,
      2 * 60
    );
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.PARTICIPANT,
      2 * 60
    );
  }

  back(): void {
    if (!this.backClicked) this.$router.go(-1);
    this.backClicked = true;
  }

  goBack(): void {
    if (
      !this.isSyncedWithPublicScreen &&
      this.$router.currentRoute.value.name === 'participant-module-content'
    )
      this.$router.go(-1);
  }

  goToOverview(): void {
    if (
      !this.isSyncedWithPublicScreen &&
      this.$router.currentRoute.value.name === 'participant-module-content'
    )
      this.$router.push({
        name: RouteName.PARTICIPANT_OVERVIEW,
      });
  }

  modules: Module[] = [];
  loadUsedModules(): void {
    this.modules = [];
    if (this.task && this.task.modules) {
      this.task.modules.forEach((module) => {
        hasModule(
          ModuleComponentType.PARTICIPANT,
          this.taskType,
          module.name,
          false
        ).then((result) => {
          if (result) this.modules.push(module);
          if (this.modules.length == 1) {
            this.moduleNameClick(this.moduleNames[0]);
          }
        });
      });
    }
  }

  mounted(): void {
    this.loadDefaultModule();
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updatePublicTask);
    cashService.deregisterAllGet(this.updateState);
    cashService.deregisterAllGet(this.updatePoints);
    cashService.deregisterAllGet(this.updateSession);
  }

  unmounted(): void {
    this.deregisterAll();
    this.loadDefaultModule();
    this.task = null;
  }

  get isSyncedWithPublicScreen(): boolean {
    if (this.task && this.task.modules) {
      return !!this.task.modules.find((module) => module.syncPublicParticipant);
    }
    return false;
  }

  loadDefaultModule(): void {
    getAsyncDefaultModule(ModuleComponentType.PARTICIPANT).then((component) => {
      if (
        this.$options.components &&
        this.componentLoadIndex == 0 &&
        this.componentLoadingState == ComponentLoadingState.NONE
      ) {
        this.componentLoadingState = ComponentLoadingState.DEFAULT;
        this.$options.components['ParticipantModuleComponent'] = component;
        this.componentLoadIndex++;
      }
    });
  }

  get module(): Module | null {
    if (this.task && this.task.modules) {
      const module = this.task.modules.find(
        (module) => module.name == this.moduleName
      );
      if (module) return module;
    }
    return null;
  }

  get moduleId(): string | null {
    if (this.module) return this.module.id;
    return null;
  }

  // eslint-disable-next-line no-undef
  get taskType(): TaskType | null {
    if (this.task) return TaskType[this.task.taskType];
    return null;
  }

  get moduleNames(): string[] {
    if (this.modules && this.modules.length > 0) {
      return this.modules.map((module) => module.name);
    }

    if (this.task && this.task.modules && this.task.modules.length > 0) {
      const modules: string[] = [];
      if (this.task?.modules)
        this.task.modules.forEach((module) => {
          modules.push(module.name);
        });
      return modules;
    }

    return ['default'];
  }

  timeLeft: number | null = 0;
  updateTask(task: Task): void {
    if (!this.task) {
      this.task = task;
      if (task?.modules) task.modules.forEach((module) => this.setIcon(module));
      this.loadUsedModules();
      this.moduleNameClick(this.moduleNames[0]);
      sessionService.registerGetPublicScreen(
        this.task.sessionId,
        this.updatePublicTask,
        EndpointAuthorisationType.PARTICIPANT,
        2 * 60
      );
    }
    if (task) this.timeLeft = timerService.getRemainingTime(task);

    if (
      !this.isSyncedWithPublicScreen &&
      !timerService.isActive(task) &&
      this.$router.currentRoute.value.name === 'participant-module-content'
    )
      this.$router.push({
        name: RouteName.PARTICIPANT_OVERVIEW,
      });

    cashService.deregisterAllGet(this.updateSession);
    if (this.task.sessionId) {
      sessionService.registerGetById(
        this.task.sessionId,
        this.updateSession,
        EndpointAuthorisationType.PARTICIPANT,
        60 * 60
      );
    }
  }

  updateState(stateList: TaskParticipantState[]): void {
    if (stateList.length > 0) {
      const state = stateList[0];
      if (!this.state && state.state !== TaskParticipantStatesType.FINISHED) {
        state.count++;
        taskParticipantService.putParticipantState(this.taskId, state);
      }
      this.state = state;
    }
  }

  updateSession(session: Session): void {
    cashService.deregisterAllGet(this.updatePoints);
    taskParticipantService.registerGetPoints(
      session.id,
      this.updatePoints,
      EndpointAuthorisationType.PARTICIPANT,
      60 * 60,
      session.parameter?.startingPoints ?? 0
    );
  }

  updatePoints(points: number): void {
    this.points = points;
  }

  updatePublicTask(task: Task): void {
    if (
      this.isSyncedWithPublicScreen &&
      task?.id !== this.taskId &&
      this.$router.currentRoute.value.name === 'participant-module-content'
    )
      this.$router.push({
        name: RouteName.PARTICIPANT_OVERVIEW,
      });
  }

  async setIcon(module: Module): Promise<void> {
    await getModuleConfig('icon', this.taskType, module.name).then((icon) => {
      getModuleConfig('iconPrefix', this.taskType, module.name).then(
        (iconPrefix) => {
          (module as any).icon = [iconPrefix, icon];
        }
      );
    });
  }

  moduleNameClick(moduleName: string | null = null): void {
    this.useFullSize = false;
    this.backgroundClass = '';
    this.drawNavigation = true;
    if (!moduleName) {
      moduleName = this.moduleName;
    }
    if (moduleName) this.moduleName = moduleName;
    const taskType = this.taskType;
    if (this.$options.components) {
      getAsyncModule(
        ModuleComponentType.PARTICIPANT,
        taskType,
        moduleName
      ).then((component) => {
        if (this.$options.components) {
          this.componentLoadingState = ComponentLoadingState.SELECTED;
          this.$options.components['ParticipantModuleComponent'] = component;
          this.componentLoadIndex++;
        }
      });
      getAsyncModule(
        ModuleComponentType.PARTICIPANT_FINISHED,
        taskType,
        moduleName
      ).then((component) => {
        if (this.$options.components && component) {
          this.$options.components['ParticipantFinishedModuleComponent'] =
            component;
          this.componentLoadIndex++;
        }
      });
    }
  }
}
</script>

<style lang="scss" scoped>
.sh-content {
  height: var(--app-height);
  width: 100%;
  position: relative;
  display: flex;
  box-sizing: border-box;
}

.sh-overlay {
  position: fixed;
  top: 0;
  flex: 1;
  z-index: 20;
  width: inherit;
  max-width: inherit;
  padding: 10px 20px 0 20px;
}

.el-page-header {
  color: white;
}

.el-page-header::v-deep(.el-page-header__content) {
  color: white;
  text-transform: uppercase;
}

.right {
  position: absolute;
  top: 1rem;
  right: 2rem;
}

.right:not(:last-child) {
  top: 1rem;
  right: 4.2rem;
}

.timer {
  text-transform: uppercase;
  color: white;
  font-size: 1.2rem;
  padding: 0;
  background-color: transparent;
}

.uppercase {
  text-transform: uppercase;
  color: white;
  font-size: 0.75rem;
  text-align: center;
}

.el-tabs::v-deep(.el-tabs__header) {
  margin: 0;
}

.el-tabs::v-deep(.el-tabs__nav-wrap)::after {
  background-color: var(--color-gray-inactive);
}

.el-tabs {
  pointer-events: auto;
}

.info-text {
  padding: 2rem;
  margin: 2vh 1vh 1vh 1vh;

  h1 {
    font-weight: var(--font-weight-bold);
    padding-bottom: 0.5rem;
  }
}
</style>
