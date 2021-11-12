<template>
  <ModeratorNavigationLayout
    v-if="task"
    :current-route-title="$t(`enum.taskType.${TaskType[task.taskType]}`)"
    :key="componentLoadIndex"
  >
    <template v-slot:sidebar>
      <Sidebar
        :title="task.name"
        :pretitle="task.taskType"
        :description="task.description"
        v-on:openSettings="editTask"
        v-on:delete="deleteTask"
      >
        <template #footerContent>
          <div class="sidebar__toggles" v-if="task">
            <ModuleShare
              :task="task"
              :is-on-public-screen="task.id === publicScreenTaskId"
            />
          </div>
          <router-link
            v-if="session.id"
            :to="`/public-screen/${session.id}`"
            target="_blank"
          >
            <button class="btn btn--mint btn--fullwidth sidebar__button">
              {{ $t('general.publicScreen') }}
            </button>
          </router-link>
        </template>
      </Sidebar>
    </template>
    <template v-slot:header></template>
    <template v-slot:content>
      <ModuleContentComponent :task-id="taskId" />
      <TaskSettingsOld
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
  getAsyncDefaultModule,
  getAsyncModule,
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
import TaskSettingsOld from '@/components/moderator/organisms/settings/TaskSettingsOld.vue';
import { EventType } from '@/types/enum/EventType';
import ModuleShare from '@/components/moderator/molecules/ModuleShare.vue';
import { ComponentLoadingState } from '@/types/enum/ComponentLoadingState';

@Options({
  components: {
    ModuleShare,
    Sidebar,
    ModeratorNavigationLayout,
    TaskSettingsOld,
    ModuleContentComponent: getEmptyComponent(),
  },
})
export default class ModeratorModuleContent extends Vue {
  @Prop({ default: '' }) readonly sessionId!: string;
  @Prop({ default: '' }) readonly taskId!: string;
  componentLoadIndex = 0;
  componentLoadingState: ComponentLoadingState = ComponentLoadingState.NONE;

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
        if (
          this.$options.components &&
          this.componentLoadIndex == 0 &&
          this.componentLoadingState == ComponentLoadingState.NONE
        ) {
          this.componentLoadingState = ComponentLoadingState.DEFAULT;
          this.$options.components['ModuleContentComponent'] = component;
          this.componentLoadIndex++;
        }
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
          if (this.$options.components) {
            this.componentLoadingState = ComponentLoadingState.SELECTED;
            this.$options.components['ModuleContentComponent'] = component;
            this.componentLoadIndex++;
          }
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

.sidebar {
  &__toggles {
    margin-bottom: 1rem;
  }
}
</style>
