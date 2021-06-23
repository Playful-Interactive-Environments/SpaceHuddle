<template>
  <div class="brainstorming" ref="item">
    <!-- TODO: task description missing -->
    <Sidebar
      :title="task.name"
      :pretitle="task.ModuleType"
      :description="task.name"
    />
    <main class="brainstorming__content"></main>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { Task } from '../../services/moderator/task-service';
import Sidebar from '@/components/moderator/organisms/Sidebar.vue';
import ModuleType from '../../types/ModuleType';
import TaskStates from '../../types/TaskStates';
import ModuleColors from '../../types/ModuleColors';
import { setModuleStyles } from '../../utils/moduleStyles';

@Options({
  components: {
    Sidebar,
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

  mounted(): void {
    setModuleStyles(this.$refs.item as HTMLElement, ModuleType.BRAINSTORMING);
  }
}
</script>

<style lang="scss" scoped></style>
