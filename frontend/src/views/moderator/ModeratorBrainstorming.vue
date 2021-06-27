<template>
  <div class="brainstorming" ref="item">
    <div v-if="task">
      <Sidebar
        :title="task.name"
        :pretitle="task.taskType"
        :description="task.description"
        :moduleType="ModuleType[task.taskType]"
      />
      <div class="brainstorming__header">
        <BackButton :route="'/session/' + sessionId" />
        <Navigation />
      </div>
      <main class="brainstorming__content">
        <IdeaCard
          :idea="idea"
          v-for="(idea, index) in ideas"
          :key="index"
          @ideaDeleted="getIdeas"
        />
      </main>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { Task } from '../../services/task-service';
import { Idea } from '../../services/idea-service';
import { setModuleStyles } from '../../utils/moduleStyles';
import Sidebar from '@/components/moderator/organisms/Sidebar.vue';
import ModuleType from '../../types/ModuleType';
import Navigation from '@/components/moderator/molecules/Navigation.vue';
import BackButton from '@/components/moderator/atoms/BackButton.vue';
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';
import * as taskService from '@/services/task-service';

@Options({
  components: {
    IdeaCard,
    Navigation,
    Sidebar,
    BackButton,
  },
})
export default class ModeratorBrainstorming extends Vue {
  @Prop({ default: '' }) readonly sessionId!: string;
  @Prop({ default: '' }) readonly taskId!: string;

  task: Task | null = null;
  ideas: Idea[] = [];
  ModuleType = ModuleType;
  ideaInterval!: number;
  readonly interval = 3000;

  async mounted(): Promise<void> {
    this.task = await taskService.getTaskById(this.taskId);
    this.getIdeas();
    this.startIdeaInterval();
    setModuleStyles(
      this.$refs.item as HTMLElement,
      ModuleType[this.task.taskType]
    );
  }

  destroyed(): void {
    clearInterval(this.ideaInterval);
  }

  async getIdeas(): Promise<void> {
    this.ideas = await taskService.getIdeasForTask(this.taskId);
  }

  startIdeaInterval(): void {
    this.ideaInterval = setInterval(this.getIdeas, this.interval);
  }
}
</script>

<style lang="scss" scoped>
.brainstorming {
  background-color: var(--color-background-gray);
  margin-left: var(--sidebar-width);
  min-height: 100vh;

  &__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: var(--header-height);
    padding-left: 2rem;
  }

  &__content {
    padding: 2rem;
    column-width: 22vw;
    column-gap: 1rem;
  }
}
</style>
