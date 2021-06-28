<template>
  <div class="voting" ref="item">
    <div v-if="task">
      <Sidebar
        :session-id="sessionId"
        :title="task.name"
        :pretitle="task.taskType"
        :description="task.description"
        :moduleType="ModuleType[task.taskType]"
        :is-on-public-screen="task.id === publicScreenTask?.id"
        @changePublicScreen="changePublicScreen"
      />
      <NavigationWithBack :back-route="'/session/' + sessionId" />
      <div class="brainstorming__header">
        <BackButton :route="'/session/' + sessionId" />
        <Navigation />
      </div>
      <main class="voting__content">
        <!-- TODO: voting module content -->
        Voting content works!
      </main>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { Task } from '@/services/task-service';
import { Idea } from '@/services/idea-service';
import { setModuleStyles } from '@/utils/moduleStyles';
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';
import ModuleType from '../../types/ModuleType';
import NavigationWithBack from '@/components/moderator/organisms/NavigationWithBack.vue';
import Sidebar from '@/components/moderator/organisms/Sidebar.vue';
import * as taskService from '@/services/task-service';
import * as sessionService from '@/services/session-service';
import { EventType } from '@/types/EventType';

@Options({
  components: {
    IdeaCard,
    NavigationWithBack,
    Sidebar,
  },
})
export default class ModeratorVoting extends Vue {
  @Prop({ default: '' }) readonly sessionId!: string;
  @Prop({ default: '' }) readonly taskId!: string;

  task: Task | null = null;
  publicScreenTask: Task | null = null;
  ideas: Idea[] = [];
  ModuleType = ModuleType;

  async mounted(): Promise<void> {
    this.task = await taskService.getTaskById(this.taskId);
    this.ideas = await taskService.getIdeasForTask(this.taskId);
    this.publicScreenTask = await sessionService.getPublicScreen(
      this.sessionId
    );
    setModuleStyles(
      this.$refs.item as HTMLElement,
      ModuleType[this.task.taskType]
    );
  }

  changePublicScreen(): void {
    this.eventBus.emit(EventType.CHANGE_PUBLIC_SCREEN, this.taskId);
  }
}
</script>

<style lang="scss" scoped>
.voting {
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
