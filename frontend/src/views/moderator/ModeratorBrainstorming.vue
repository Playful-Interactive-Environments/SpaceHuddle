<template>
  <div class="brainstorming" ref="item">
    <!-- TODO: task description missing -->
    <Sidebar
      :title="task.name"
      :pretitle="task.ModuleType"
      :description="task.name"
    />
    <main class="brainstorming__content">
      <IdeaCard
        :keywords="'1 My first Idea'"
        :description="'This is my first idea for the brainstroming and it is a little bit longer.'"
      />
      <IdeaCard
        :description="'2 This is my first idea for the brainstroming and it is a little bit longer.'"
      />
      <IdeaCard
        :keywords="'3 My first Idea'"
        :description="'This is my first idea for the brainstroming and it is a little bit longer.'"
      />

      <IdeaCard
        :description="'4 This is my first idea for the brainstroming and it is a little bit longer.'"
      />
      <IdeaCard
        :keywords="'My first Idea'"
        :description="'This is my first idea for the brainstroming and it is a little bit longer.'"
      />
      <IdeaCard
        :keywords="'My first Idea'"
        :description="'This is my first idea for the brainstroming and it is a little bit longer.'"
      />
      <IdeaCard
        :keywords="'My first Idea'"
        :description="'This is my first idea for the brainstroming and it is a little bit longer.'"
      />
      <IdeaCard
        :keywords="'My first Idea'"
        :description="'This is my first idea for the brainstroming and it is a little bit longer.'"
      />
    </main>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { Task, Idea } from '../../services/moderator/task-service';
import { setModuleStyles } from '../../utils/moduleStyles';
import Sidebar from '@/components/moderator/organisms/Sidebar.vue';
import ModuleType from '../../types/ModuleType';
import TaskStates from '../../types/TaskStates';
import ModuleColors from '../../types/ModuleColors';
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';

@Options({
  components: {
    Sidebar,
    IdeaCard,
  },
})
export default class ModeratorBrainstorming extends Vue {
  @Prop({ default: '' }) readonly taskId!: string;
  task: Task = {
    id: 'string',
    taskType: ModuleType.BRAINSTORMING,
    name: 'Task 1',
    parameter: null, // TODO: ask what options can be provided
    order: 2,
    state: TaskStates.ACTIVE, // TODO: ask what possible states a task can be in - WAIT,
  };

  idea: Idea = {
    id: 'uuid',
    state: 'NEW',
    timestamp: '2021-06-23',
    description: 'string',
    keywords: 'string',
    image: 'string',
    link: 'string',
  };

  itemsFirstCol = 0;
  itemsSecondCol = 0;
  itemsThirdCol = 0;

  mounted(): void {
    setModuleStyles(this.$refs.item as HTMLElement, this.task.taskType);
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

  &__column {
    display: flex;
    flex-direction: column;

    gap: 1rem;
  }
}
</style>
