<template>
  <section v-if="ideas.length === 0" class="centered public-screen__error">
    <p>{{ $t('module.brainstorming.default.publicScreen.noIdeas') }}</p>
  </section>
  <div v-else class="public-screen__content">
    <section class="layout__columns">
      <IdeaCard
        v-for="(idea, index) in oldIdeas"
        :idea="idea"
        :key="index"
        :is-editable="false"
      />
    </section>
    <section class="layout__columns new" v-if="isModerator">
      <IdeaCard
        v-for="(idea, index) in newIdeas"
        :idea="idea"
        :key="index"
        :is-editable="false"
      />
    </section>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as ideaService from '@/services/idea-service';
import * as taskService from '@/services/task-service';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { defaultFilterData } from '@/components/moderator/molecules/IdeaFilter.vue';

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
  ideas: Idea[] = [];
  readonly intervalTime = 10000;
  interval!: any;
  readonly newTimeSpan = 10000;

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getIdeas();
  }

  get isModerator(): boolean {
    return this.authHeaderTyp === EndpointAuthorisationType.MODERATOR;
  }

  get newIdeas(): Idea[] {
    const currentDate = new Date();
    return this.ideas
      .filter(
        (idea) =>
          currentDate.getTime() - new Date(idea.timestamp).getTime() <=
          this.newTimeSpan
      )
      .slice(0, 3);
  }

  get oldIdeas(): Idea[] {
    if (this.isModerator) {
      const currentDate = new Date();
      return this.ideas.filter(
        (idea) =>
          currentDate.getTime() - new Date(idea.timestamp).getTime() >
          this.newTimeSpan
      );
    }
    return this.ideas;
  }

  async getIdeas(): Promise<void> {
    if (this.taskId) {
      const filter = { ...defaultFilterData };

      await taskService
        .getTaskById(this.taskId, this.authHeaderTyp)
        .then((task) => {
          if (task.parameter && task.parameter.orderType)
            filter.orderType = task.parameter.orderType;
          if (task.parameter && task.parameter.orderType)
            filter.stateFilter = task.parameter.stateFilter;
          if (task.parameter && task.parameter.orderType)
            filter.textFilter = task.parameter.textFilter;
        });

      await ideaService
        .getIdeasForTask(
          this.taskId,
          filter.orderType,
          null,
          this.authHeaderTyp
        )
        .then((ideas) => {
          ideas = ideaService.filterIdeas(
            ideas,
            filter.stateFilter,
            filter.textFilter
          );
          if (
            filter.orderType.toUpperCase() ==
            IdeaSortOrder.TIMESTAMP.toUpperCase()
          )
            this.ideas = ideas.reverse();
          else this.ideas = ideas;
        });
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

<style lang="scss" scoped>
.public-screen__content {
  display: grid;
  grid-template-columns: 80% 20%;
}

.new {
  padding-left: 1rem;
}
</style>
