<template>
  <div class="task_definition" ref="item">
    <div v-if="task">
      <Sidebar
        :session-id="sessionId"
        :title="task.name"
        :pretitle="task.taskType"
        :description="task.description"
        :moduleType="ModuleType[task.taskType]"
        :is-on-public-screen="task.id === publicScreenTask?.id"
        :task="task"
      />
      <NavigationWithBack :back-route="'/session/' + sessionId" />
      <form-error :errors="errors"></form-error>
      <main class="task_definition__content">
        <ModuleContentComponent :task-id="taskId" />
      </main>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { getAsyncModule, getAsyncDefaultModule } from '@/modules';

import ModuleComponentType from '@/modules/ModuleComponentType';
import ModuleType from '@/types/enum/ModuleType';
import TaskStates from '@/types/enum/TaskStates';
import { Task } from '@/types/api/Task';
import { setModuleStyles } from '@/utils/moduleStyles';

import * as sessionService from '@/services/session-service';
import * as taskService from '@/services/task-service';

import Sidebar from '@/components/moderator/organisms/Sidebar.vue';
import NavigationWithBack from '@/components/moderator/organisms/NavigationWithBack.vue';
import FormError from '@/components/shared/atoms/FormError.vue';

@Options({
  components: {
    Sidebar,
    NavigationWithBack,
    FormError,
    ModuleContentComponent: getAsyncDefaultModule(
      ModuleComponentType.MODERATOR_CONTENT
    ),
  },
})
export default class ModeratorModuleContent extends Vue {
  @Prop({ default: '' }) readonly sessionId!: string;
  @Prop({ default: '' }) readonly taskId!: string;

  task: Task | null = null;
  publicScreenTask: Task | null = null;
  ModuleType = ModuleType;
  TaskStates = TaskStates;
  errors: string[] = [];

  get taskType(): ModuleType | null {
    if (this.task) return ModuleType[this.task.taskType];
    return null;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(val: string): void {
    taskService.getTaskById(val).then((queryResult) => {
      this.task = queryResult;
      const taskType = this.taskType;
      if (this.$options.components) {
        this.$options.components['ModuleContentComponent'] = getAsyncModule(
          ModuleComponentType.MODERATOR_CONTENT,
          taskType
        );
      }
      setModuleStyles(
        this.$refs.item as HTMLElement,
        ModuleType[this.task.taskType]
      );
      sessionService.getPublicScreen(this.sessionId).then((queryResult) => {
        this.publicScreenTask = queryResult;
      });
    });
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
    column-width: 22vw;
    column-gap: 1rem;
  }
}
</style>