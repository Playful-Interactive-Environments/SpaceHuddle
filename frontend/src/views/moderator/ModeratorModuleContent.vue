<template>
  <ModeratorNavigationLayout
    v-if="task"
    :current-route-title="task.taskType"
    :key="componentLoadIndex"
  >
    <template v-slot:sidebar>
      <Sidebar
        :session="session"
        :title="task.name"
        :pretitle="task.taskType"
        :description="task.description"
        taskType="TaskType[task.taskType]"
        :is-on-public-screen="task.id === publicScreenTaskId"
        :task="task"
        v-on:openSettings="editTask"
        v-on:delete="deleteTask"
      />
    </template>
    <template v-slot:header></template>
    <template v-slot:content>
      <ModuleContentComponent :task-id="taskId" />
      <TaskSettings
        v-model:show-modal="showSettings"
        :task-id="taskId"
        :key="componentLoadIndex"
      />
    </template>
  </ModeratorNavigationLayout>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import {
  getAsyncModule,
  getAsyncDefaultModule,
  getEmptyComponent,
} from '@/modules';

import ModuleComponentType from '@/modules/ModuleComponentType';
import TaskType from '@/types/enum/TaskType';
import TaskStates from '@/types/enum/TaskStates';
import { Task } from '@/types/api/Task';
import { Session } from '@/types/api/Session';
import { setModuleStyles } from '@/utils/moduleStyles';

import * as sessionService from '@/services/session-service';
import * as taskService from '@/services/task-service';

import Sidebar from '@/components/moderator/organisms/Sidebar.vue';
import ModeratorNavigationLayout from '@/components/moderator/organisms/layout/ModeratorNavigationLayout.vue';
import TaskSettings from '@/components/moderator/organisms/settings/TaskSettings.vue';
import { EventType } from '@/types/enum/EventType';

@Options({
  components: {
    Sidebar,
    ModeratorNavigationLayout,
    TaskSettings,
    ModuleContentComponent: getEmptyComponent(),
  },
})
export default class ModeratorModuleContent extends Vue {
  @Prop({ default: '' }) readonly sessionId!: string;
  @Prop({ default: '' }) readonly taskId!: string;
  componentLoadIndex = 0;

  task: Task | null = null;
  session: Session | null = null;
  publicScreenTaskId = '';
  TaskType = TaskType;
  TaskStates = TaskStates;
  showSettings = false;

  mounted(): void {
    this.loadDefaultModule();
    this.eventBus.off(EventType.CHANGE_PUBLIC_SCREEN);
    this.eventBus.on(EventType.CHANGE_PUBLIC_SCREEN, async (id) => {
      await this.changePublicScreen(id as string);
    });
  }

  async changePublicScreen(id: string | null): Promise<void> {
    this.publicScreenTaskId = id as string;
  }

  unmounted(): void {
    this.loadDefaultModule();
  }

  loadDefaultModule(): void {
    getAsyncDefaultModule(ModuleComponentType.MODERATOR_CONTENT).then(
      (component) => {
        if (this.$options.components)
          this.$options.components['ModuleContentComponent'] = component;
        this.componentLoadIndex++;
      }
    );
  }

  get taskType(): TaskType | null {
    if (this.task) return TaskType[this.task.taskType];
    return null;
  }

  get moduleName(): string[] {
    if (this.task && this.task.modules && this.task.modules.length > 0)
      return this.task.modules.map((module) => module.name);
    return ['default'];
  }

  @Watch('sessionId', { immediate: true })
  onSessionIdChanged(id: string): void {
    sessionService.getById(id).then((queryResult) => {
      this.session = queryResult;
    });
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(val: string): void {
    taskService.getTaskById(val).then((queryResult) => {
      this.task = queryResult;
      const taskType = this.taskType;
      if (this.$options.components) {
        getAsyncModule(
          ModuleComponentType.MODERATOR_CONTENT,
          taskType,
          this.moduleName
        ).then((component) => {
          if (this.$options.components)
            this.$options.components['ModuleContentComponent'] = component;
          this.componentLoadIndex++;
        });
      }
      setModuleStyles(TaskType[this.task.taskType]);
      sessionService.getPublicScreen(this.sessionId).then((queryResult) => {
        if (queryResult) this.publicScreenTaskId = queryResult.id;
        else this.publicScreenTaskId = '';
      });
    });
  }

  async editTask(): Promise<void> {
    this.showSettings = true;
  }

  async deleteTask(): Promise<void> {
    await taskService.deleteTask(this.taskId).then((deleted) => {
      if (deleted) this.$router.go(-1);
    });
  }
}
</script>

<style lang="scss" scoped>
.task_definition {
  background-color: var(--color-background-gray);
  min-height: 100vh;

  &__content {
    padding: 2rem;
  }
}
</style>
