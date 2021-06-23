<template>
  <div class="categorization" ref="item">
    <div v-if="task">
      <!-- TODO: task description missing -->
      <Sidebar
        :title="task.name"
        :pretitle="task.taskType"
        :description="task.name"
        :moduleType="ModuleType[task.taskType]"
      />
      <main class="categorization__content">
        <!-- TODO: categorization module content -->
        Categorization content works!
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
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';
import * as taskService from '@/services/moderator/task-service';

@Options({
  components: {
    Sidebar,
    IdeaCard,
  },
})
export default class ModeratorCategorization extends Vue {
  @Prop({ default: '' }) readonly taskId!: string;

  task: Task | null = null;
  ideas: Idea[] = [];
  ModuleType = ModuleType;

  async mounted(): Promise<void> {
    this.task = await taskService.getTaskById(this.taskId);
    // TODO: change once grouping is renamed to categorization
    // setModuleStyles(
    //   this.$refs.item as HTMLElement,
    //   ModuleType[this.task.taskType]
    // );
  }
}
</script>

<style lang="scss" scoped>
.categorization {
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
