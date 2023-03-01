<template>
  <section v-if="ideas.length === 0" class="centered public-screen__error">
    <p>{{ $t('module.brainstorming.default.publicScreen.noIdeas') }}</p>
  </section>
  <section v-else class="public-screen__content layout__columns">
    <IdeaCard
      v-for="(idea, index) in ideas"
      :idea="idea"
      :key="index"
      :is-editable="false"
      v-model:collapseIdeas="filter.collapseIdeas"
    />
  </section>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as selectService from '@/services/selection-service';
import * as ideaService from '@/services/idea-service';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import {
  defaultFilterData,
  FilterData,
  getFilterForTask,
} from '@/components/moderator/molecules/IdeaFilter.vue';
import * as cashService from '@/services/cash-service';

@Options({
  components: {
    IdeaCard,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  task: Task | null = null;
  selectionId: string | null = null;
  ideas: Idea[] = [];
  filter: FilterData = { ...defaultFilterData };

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      this.authHeaderTyp,
      60 * 60
    );
  }

  updateTask(task: Task): void {
    this.task = task;
    this.selectionId = this.task.parameter.selectionId;
  }

  @Watch('selectionId', { immediate: true })
  onSelectionIdChanged(): void {
    if (this.selectionId)
      selectService.registerGetIdeasForSelection(
        this.selectionId,
        this.updateIdeas,
        EndpointAuthorisationType.MODERATOR,
        10
      );
  }

  updateIdeas(ideas: Idea[]): void {
    if (this.task) this.filter = getFilterForTask(this.task);
    ideas = this.filter.orderAsc ? ideas : ideas.reverse();
    this.ideas = ideaService.filterIdeas(
      ideas,
      this.filter.stateFilter,
      this.filter.textFilter
    );
  }

  unmounted(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateIdeas);
  }
}
</script>

<style lang="scss" scoped></style>
