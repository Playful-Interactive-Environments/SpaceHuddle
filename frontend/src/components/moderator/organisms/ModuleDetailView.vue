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
        :is-active="task.state === TaskStates.ACTIVE"
        @changeActiveState="changeActiveState"
        @changePublicScreen="changePublicScreen"
      />
      <NavigationWithBack :back-route="'/session/' + sessionId" />
      <form-error :errors="errors"></form-error>
      <main class="task_definition__content">
        <slot />
      </main>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { Task } from '@/types/api/Task';
import { Idea } from '@/types/api/Idea';
import { setModuleStyles } from '@/utils/moduleStyles';
import Sidebar from '@/components/moderator/organisms/Sidebar.vue';
import ModuleType from '@/types/enum/ModuleType';
import NavigationWithBack from '@/components/moderator/organisms/NavigationWithBack.vue';
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';
import * as taskService from '@/services/task-service';
import * as sessionService from '@/services/session-service';
import { EventType } from '@/types/enum/EventType';
import FormError from '@/components/shared/atoms/FormError.vue';
import TaskStates from '@/types/enum/TaskStates';

@Options({
  components: {
    IdeaCard,
    Sidebar,
    NavigationWithBack,
    FormError
  },
})
export default class ModuleDetailView extends Vue {
  @Prop({ default: '' }) readonly sessionId!: string;
  @Prop({ default: '' }) readonly taskId!: string;

  task: Task | null = null;
  publicScreenTask: Task | null = null;
  ideas: Idea[] = [];
  ModuleType = ModuleType;
  TaskStates = TaskStates;
  errors: string[] = [];

  async mounted(): Promise<void> {
    taskService.getTaskById(this.taskId).then((queryResult) => {
      this.task = queryResult;
      setModuleStyles(
        this.$refs.item as HTMLElement,
        ModuleType[this.task.taskType]
      );
      sessionService.getPublicScreen(this.sessionId).then((queryResult) => {
        this.publicScreenTask = queryResult;
      });
    });
  }

  async changeActiveState(): Promise<void> {
    this.eventBus.emit(EventType.CHANGE_PARTICIPANT_STATE, this.task);
  }

  changePublicScreen(isActive: boolean): void {
    this.eventBus.emit(EventType.CHANGE_PUBLIC_SCREEN, isActive ? this.taskId : '{taskId}');
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
