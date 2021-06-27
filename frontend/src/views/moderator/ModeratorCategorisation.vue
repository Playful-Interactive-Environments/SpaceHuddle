<template>
  <div class="categorisation" ref="item">
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
import { Task } from '../../services/task-service';
import { Idea } from '../../services/idea-service';
import { setModuleStyles } from '../../utils/moduleStyles';
import Sidebar from '@/components/moderator/organisms/Sidebar.vue';
import ModuleType from '../../types/ModuleType';
import Navigation from '@/components/moderator/molecules/Navigation.vue';
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';
import BackButton from '@/components/moderator/atoms/BackButton.vue';
import * as taskService from '@/services/task-service';

@Options({
  components: {
    IdeaCard,
    Sidebar,
    Navigation,
    BackButton,
  },
})
export default class ModeratorCategorisation extends Vue {
  @Prop({ default: '' }) readonly taskId!: string;

  task: Task | null = null;
  ideas: Idea[] = [];
  ModuleType = ModuleType;

  async mounted(): Promise<void> {
    this.task = await taskService.getTaskById(this.taskId);
    setModuleStyles(
      this.$refs.item as HTMLElement,
      ModuleType[this.task.taskType]
    );
  }
}
</script>

<style lang="scss" scoped>
.categorisation {
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
