<template>
  <div class="brainstorming" ref="item">
    <div v-if="task">
      <!-- TODO: task description missing -->
      <Sidebar
        :title="task.name"
        :pretitle="task.taskType"
        :description="task.name"
        :moduleType="ModuleType[task.taskType]"
      />
      <Navigation />
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
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';
import * as taskService from '@/services/task-service';

@Options({
  components: {
    IdeaCard,
    Navigation,
    Sidebar,
  },
})
export default class ModeratorBrainstorming extends Vue {
  @Prop({ default: '' }) readonly taskId!: string;

  task: Task | null = null;
  ideas: Idea[] = [];
  ModuleType = ModuleType;

  async mounted(): Promise<void> {
    this.task = await taskService.getTaskById(this.taskId);
    this.getIdeas();
    setModuleStyles(
      this.$refs.item as HTMLElement,
      ModuleType[this.task.taskType]
    );
  }

  async getIdeas(): Promise<void> {
    this.ideas = await taskService.getIdeasForTask(this.taskId);
  }
}
</script>

<style lang="scss" scoped>
.brainstorming {
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
