<template>
  <div class="categorisation" ref="item">
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
      <main class="categorisation__content">
        <!-- TODO: categorisation module content -->
        Categorisation content works!
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
import Sidebar from '@/components/moderator/organisms/Sidebar.vue';
import ModuleType from '../../types/ModuleType';
import NavigationWithBack from '@/components/moderator/organisms/NavigationWithBack.vue';
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';
import * as taskService from '@/services/task-service';
import * as sessionService from '@/services/session-service';
import { EventType } from '@/types/EventType';

@Options({
  components: {
    IdeaCard,
    Sidebar,
    NavigationWithBack,
  },
})
export default class ModeratorCategorisation extends Vue {
  @Prop({ default: '' }) readonly sessionId!: string;
  @Prop({ default: '' }) readonly taskId!: string;

  task: Task | null = null;
  publicScreenTask: Task | null = null;
  ideas: Idea[] = [];
  ModuleType = ModuleType;

  async mounted(): Promise<void> {
    this.task = await taskService.getTaskById(this.taskId);
    setModuleStyles(
      this.$refs.item as HTMLElement,
      ModuleType[this.task.taskType]
    );
    this.publicScreenTask = await sessionService.getPublicScreen(
      this.sessionId
    );
  }

  changePublicScreen(): void {
    this.eventBus.emit(EventType.CHANGE_PUBLIC_SCREEN, this.taskId);
  }
}
</script>

<style lang="scss" scoped>
.categorisation {
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
