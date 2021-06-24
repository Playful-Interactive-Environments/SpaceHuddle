<template>
  <div class="voting" ref="item">
    <div v-if="task">
      <!-- TODO: task description missing -->
      <Sidebar
        :title="task.name"
        :pretitle="task.taskType"
        :description="task.name"
        :moduleType="ModuleType[task.taskType]"
      />
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
import { Task } from '../../services/moderator/task-service';
import { Idea } from '../../services/moderator/idea-service';
import { setModuleStyles } from '../../utils/moduleStyles';
import Sidebar from '@/components/moderator/organisms/Sidebar.vue';
import ModuleType from '../../types/ModuleType';
import TaskStates from '../../types/TaskStates';
import ModuleColors from '../../types/ModuleColors';
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';
import * as taskService from '@/services/moderator/task-service';

@Options({
  components: {
    Sidebar,
    IdeaCard,
  },
})
export default class ModeratorVoting extends Vue {
  @Prop({ default: '' }) readonly taskId!: string;

  task: Task | null = null;
  ideas: Idea[] = [];
  ModuleType = ModuleType;

  async mounted(): Promise<void> {
    this.task = await taskService.getTaskById(this.taskId);
    this.ideas = await taskService.getIdeasForTask(this.taskId);
    setModuleStyles(
      this.$refs.item as HTMLElement,
      ModuleType[this.task.taskType]
    );
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
