<template>
  <div class="information" ref="item">
    <div v-if="task">
      <Sidebar
        :title="task.name"
        :pretitle="task.taskType"
        :description="task.description"
        :moduleType="'information'"
      />
      <NavigationWithBack :back-route="'/session/' + sessionId" />
      <main class="information__content">
        <!-- TODO: information module content -->
        Information content works!
      </main>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { Task } from '../../services/task-service';
import { setModuleStyles } from '../../utils/moduleStyles';
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';
import ModuleType from '../../types/ModuleType';
import NavigationWithBack from '@/components/moderator/organisms/NavigationWithBack.vue';
import Sidebar from '@/components/moderator/organisms/Sidebar.vue';
import * as taskService from '@/services/task-service';

@Options({
  components: {
    IdeaCard,
    NavigationWithBack,
    Sidebar,
  },
})
export default class ModeratorInformation extends Vue {
  @Prop({ default: '' }) readonly sessionId!: string;
  @Prop({ default: '' }) readonly taskId!: string;

  task: Task | null = null;
  // ModuleType = ModuleType;

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
.information {
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
