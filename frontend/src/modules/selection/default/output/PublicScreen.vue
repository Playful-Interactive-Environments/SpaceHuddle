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
} from '@/components/moderator/molecules/IdeaFilter.vue';

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
  ideas: Idea[] = [];
  readonly intervalTime = 10000;
  interval!: any;
  filter: FilterData = { ...defaultFilterData };

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getIdeas();
  }

  async getTask(): Promise<void> {
    if (this.taskId) {
      await taskService
        .getTaskById(this.taskId, this.authHeaderTyp)
        .then((task) => {
          this.task = task;
        });
    }
  }

  async getIdeas(): Promise<void> {
    if (this.taskId) {
      await this.getTask();
      //if (!this.task) await this.getTask();
      if (this.task?.parameter.selectionId) {
        const filter = { ...defaultFilterData };
        if (this.task.parameter && this.task.parameter.orderType)
          filter.orderType = this.task.parameter.orderType;
        if (this.task.parameter && this.task.parameter.stateFilter)
          filter.stateFilter = this.task.parameter.stateFilter;
        if (this.task.parameter && this.task.parameter.textFilter)
          filter.textFilter = this.task.parameter.textFilter;
        if (this.task.parameter && this.task.parameter.collapseIdeas)
          filter.collapseIdeas = this.task.parameter.collapseIdeas;

        this.filter = filter;

        await selectService
          .getIdeasForSelection(
            this.task?.parameter.selectionId,
            this.authHeaderTyp
          )
          .then((ideas) => {
            this.ideas = ideaService.filterIdeas(
              ideas,
              filter.stateFilter,
              filter.textFilter
            );
          });
      }
    }
  }

  async mounted(): Promise<void> {
    this.startInterval();
  }

  startInterval(): void {
    this.interval = setInterval(this.getIdeas, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }
}
</script>

<style lang="scss" scoped></style>
