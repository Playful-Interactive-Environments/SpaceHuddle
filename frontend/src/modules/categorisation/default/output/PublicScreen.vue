<template>
  <section
    v-if="ideas.length === 0"
    class="container2 container2--centered public-screen__error"
  >
    <p>{{ $t('module.brainstorming.default.publicScreen.noIdeas') }}</p>
  </section>
  <section v-else class="public-screen__content">
    <Expand v-for="(item, key) in orderGroupContent" :key="key">
      <template v-slot:title>
        {{ key.toUpperCase() }}
      </template>
      <template v-slot:content>
        <main class="categorisation__content">
          <IdeaCard
            :idea="idea"
            v-for="(idea, index) in item.ideas"
            :key="index"
            :is-selectable="false"
            :is-deletable="false"
          />
        </main>
      </template>
    </Expand>
  </section>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';
import * as ideaService from '@/services/idea-service';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import IdeaSortOrder, {
  IdeaSortOrderCategorisation,
} from '@/types/enum/IdeaSortOrder';
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';
import { Category } from '@/types/api/Category';
import * as categorisationService from '@/services/categorisation-service';
import Expand from '@/components/shared/atoms/Expand.vue';

@Options({
  components: {
    IdeaCard,
    Expand,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  task: Task | null = null;
  categories: Category[] = [];
  ideas: Idea[] = [];
  orderGroupContent: {
    [name: string]: { ideas: Idea[] };
  } = {};
  readonly intervalTime = 10000;
  interval!: any;

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getIdeas();
  }

  async getTask(): Promise<void> {
    if (this.taskId) {
      await taskService.getTaskById(this.taskId).then((task) => {
        this.task = task;
      });
    }
  }

  async getIdeas(): Promise<void> {
    if (this.taskId) {
      if (!this.task) await this.getTask();
      await this.getCategories();
      const orderGroupContent = {};

      if (this.task) {
        await ideaService
          .getIdeasForTopic(
            this.task.topicId,
            IdeaSortOrderCategorisation,
            this.taskId
          )
          .then((ideas) => {
            this.ideas = ideas;
            ideas.forEach((ideaItem) => {
              if (ideaItem.order) {
                const orderGroup = orderGroupContent[ideaItem.order];
                if (!orderGroup) {
                  orderGroupContent[ideaItem.order] = {
                    ideas: [ideaItem]
                  };
                } else {
                  orderGroup.ideas.push(ideaItem);
                }
              }
            });
          });
      }

      this.orderGroupContent = orderGroupContent;
    }
  }

  async getCategories(): Promise<void> {
    if (this.taskId) {
      await categorisationService
        .getCategoriesForTask(this.taskId)
        .then((categories) => {
          this.categories = categories;
        });
    }
  }

  async mounted(): Promise<void> {
    this.startIdeaInterval();
  }

  startIdeaInterval(): void {
    this.interval = setInterval(this.getIdeas, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }
}
</script>

<style lang="scss" scoped>
.categorisation {
  &__content {
    width: 100%;
    column-width: 21vw;
    column-gap: 0.5rem;
  }
}

.expand {
  &__header {
    color: white;
  }
}

.expand > :first-child {
  color: white;
}
</style>
