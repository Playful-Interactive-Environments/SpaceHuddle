<template>
  <div class="grid">
    <div class="public-screen__content">
      <section class="layout__columns">
        <IdeaCard
          v-for="(idea, index) in ideas"
          :idea="idea"
          :key="index"
          :is-editable="false"
          v-model:collapseIdeas="filter.collapseIdeas"
        />
      </section>
    </div>
    <div class="public-screen__content">
      <section class="layout__columns">
        <IdeaCard
          v-for="(idea, index) in ideas"
          :idea="idea"
          :key="index"
          :is-editable="false"
          v-model:collapseIdeas="filter.collapseIdeas"
        />
      </section>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as ideaService from '@/services/idea-service';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import {
  defaultFilterData,
  FilterData,
} from '@/components/moderator/molecules/IdeaFilter.vue';
import * as taskService from '@/services/task-service';

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
  filter: FilterData = { ...defaultFilterData };

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getIdeas();
  }

  async getIdeas(): Promise<void> {
    if (this.taskId) {
      const filter = { ...defaultFilterData };

      await taskService
        .getTaskById(this.taskId, this.authHeaderTyp)
        .then((task) => {
          if (task.parameter && task.parameter.orderType)
            filter.orderType = task.parameter.orderType;
          if (task.parameter && task.parameter.stateFilter)
            filter.stateFilter = task.parameter.stateFilter;
          if (task.parameter && task.parameter.textFilter)
            filter.textFilter = task.parameter.textFilter;
          if (task.parameter && task.parameter.collapseIdeas)
            filter.collapseIdeas = task.parameter.collapseIdeas;
        });

      this.filter = filter;

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
.grid {
  display: grid;
  grid-template-rows: 1fr 1fr;
  min-height: 200%;
}

.public-screen__content {
  max-width: 80%;
  animation: bounce 50s linear;
  position: relative;
  animation-iteration-count: infinite;
  min-height: 100%;
  padding-bottom: 10rem;
}

.layout__columns {
  column-width: 15rem;
}

@keyframes bounce {
  0% {
    left: 0;
    top: -100%;
  }
  10% {
    left: 20%;
    top: -100%;
  }
  20% {
    left: 0;
    top: -100%;
  }
  25% {
    left: 0;
    top: -75%;
  }
  35% {
    left: 20%;
    top: -75%;
  }
  45% {
    left: 0;
    top: -75%;
  }
  50% {
    left: 0;
    top: -50%;
  }
  60% {
    left: 20%;
    top: -50%;
  }
  70% {
    left: 0;
    top: -50%;
  }
  75% {
    left: 0;
    top: -25%;
  }
  85% {
    left: 20%;
    top: -25%;
  }
  95% {
    left: 0;
    top: -25%;
  }
  100% {
    left: 0;
    top: 0;
  }
}
</style>
