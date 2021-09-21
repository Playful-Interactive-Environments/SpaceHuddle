<template>
  <div class="task_definition" ref="item">
    <div v-if="task">
      <Sidebar
        :session-id="sessionId"
        :title="task.name"
        :pretitle="task.taskType"
        :description="task.description"
        taskType="TaskType[task.taskType]"
        :is-on-public-screen="task.id === publicScreenTask?.id"
        :task="task"
        v-on:openSettings="editTask"
        v-on:delete="deleteTask"
      />
      <NavigationWithBack :back-route="'/session/' + sessionId" />
      <form-error :errors="errors"></form-error>
      <main class="task_definition__content">
        <ModuleContentComponent :task-id="taskId" />
      </main>
    </div>
    <ModalTaskCreate
      v-model:show-modal="showModalTaskCreate"
      :task-id="taskId"
      :key="componentLoadIndex"
    />
  </div>
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
import { setModuleStyles } from '@/utils/moduleStyles';

import * as sessionService from '@/services/session-service';
import * as taskService from '@/services/task-service';

import Sidebar from '@/components/moderator/organisms/Sidebar.vue';
import NavigationWithBack from '@/components/moderator/organisms/NavigationWithBack.vue';
import FormError from '@/components/shared/atoms/FormError.vue';
import ModalTaskCreate from '@/components/shared/molecules/ModalTaskCreate.vue';

@Options({
  components: {
    Sidebar,
    NavigationWithBack,
    FormError,
    ModalTaskCreate,
    ModuleContentComponent: getEmptyComponent(),
  },
})
export default class ModeratorModuleContent extends Vue {
  @Prop({ default: '' }) readonly sessionId!: string;
  @Prop({ default: '' }) readonly taskId!: string;
  componentLoadIndex = 0;

  task: Task | null = null;
  publicScreenTask: Task | null = null;
  TaskType = TaskType;
  TaskStates = TaskStates;
  errors: string[] = [];
  showModalTaskCreate = false;

  mounted(): void {
    this.loadDefaultModule();
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

  get moduleName(): string {
    if (this.task && this.task.modules && this.task.modules.length > 0)
      return this.task.modules[0].name;
    return 'default';
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
      setModuleStyles(
        this.$refs.item as HTMLElement,
        TaskType[this.task.taskType]
      );
      sessionService.getPublicScreen(this.sessionId).then((queryResult) => {
        this.publicScreenTask = queryResult;
      });
    });
  }

  async editTask(): Promise<void> {
    this.showModalTaskCreate = true;
  }

  async deleteTask(): Promise<void> {
    await taskService.deleteTask(this.taskId);
    this.$router.go(-1);
  }
}
</script>

<style lang="scss" scoped>
.task_definition {
  background-color: var(--color-background-gray);
  margin-left: var(--sidebar-width);
  min-height: 100vh;

  &__content {
    padding: 2rem;
  }
}
</style>
