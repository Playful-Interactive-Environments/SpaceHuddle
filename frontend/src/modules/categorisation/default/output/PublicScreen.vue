<template>
  <section v-if="ideas.length === 0" class="centered public-screen__error">
    <p>{{ $t('module.brainstorming.default.publicScreen.noIdeas') }}</p>
  </section>
  <section v-else class="public-screen__content fixHead">
    <el-container>
      <el-header class="columns is-mobile sticky-header">
        <div class="column">
          <CategoryCard
            :ideas="categoryIdeas(CategoryUndefined)"
            :isEditable="false"
          >
          </CategoryCard>
        </div>
        <div class="column" v-for="category in categories" :key="category.id">
          <CategoryCard
            :category="category"
            :ideas="categoryIdeas(category.keywords)"
            :isEditable="false"
          >
          </CategoryCard>
        </div>
      </el-header>
      <el-main class="columns is-mobile">
        <div class="column">
          <IdeaCard
            :idea="idea"
            v-for="(idea, index) in categoryIdeas(CategoryUndefined)"
            :key="index"
            :is-selectable="false"
            :is-editable="false"
            :style="{ 'border-color': 'var(--color-primary)' }"
            v-model:collapseIdeas="filter.collapseIdeas"
          />
        </div>
        <div class="column" v-for="category in categories" :key="category.id">
          <IdeaCard
            :idea="idea"
            v-for="(idea, index) in categoryIdeas(category.keywords)"
            :key="index"
            :is-selectable="false"
            :is-editable="false"
            :style="{ 'border-color': category.parameter.color }"
            v-model:collapseIdeas="filter.collapseIdeas"
          />
        </div>
      </el-main>
    </el-container>
  </section>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as viewService from '@/services/view-service';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import { IdeaSortOrderHierarchy } from '@/types/enum/IdeaSortOrder';
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';
import { Category, CategoryUndefined } from '@/types/api/Category';
import { OrderGroupList } from '@/types/api/OrderGroup';
import CollapseTitle from '@/components/moderator/atoms/CollapseTitle.vue';
import { reloadCollapseContent } from '@/utils/collapse';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import CategoryCard from '@/modules/categorisation/default/molecules/CategoryCard.vue';
import * as categorisationService from '@/services/categorisation-service';
import {
  defaultFilterData,
  FilterData,
  getFilterForTask,
} from '@/components/moderator/molecules/IdeaFilter.vue';

@Options({
  components: {
    CategoryCard,
    IdeaCard,
    CollapseTitle,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  task: Task | null = null;
  categories: Category[] = [];
  ideas: Idea[] = [];
  orderGroupContent: OrderGroupList = {};
  readonly intervalTime = 10000;
  interval!: any;
  openTabs: string[] = [];
  CategoryUndefined = CategoryUndefined;
  filter: FilterData = { ...defaultFilterData };

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getCollapseContent(true);
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

  categoryIdeas(category: string): Idea[] {
    if (category in this.orderGroupContent)
      return this.orderGroupContent[category].ideas;
    return [];
  }

  async getCollapseContent(reloadTabState = false): Promise<void> {
    reloadCollapseContent(
      this.openTabs,
      Object.keys(this.orderGroupContent),
      this.getIdeas,
      reloadTabState
    ).then((tabs) => (this.openTabs = tabs));
  }

  async getIdeas(): Promise<string[]> {
    if (this.taskId) {
      await this.getTask();
      //if (!this.task) await this.getTask();
      await this.getCategories();

      if (this.task && this.task.parameter && this.task.parameter.input) {
        this.filter = getFilterForTask(this.task);

        const getCategorizedIdeas = viewService.getViewOrderGroups(
          this.task.topicId,
          this.task.parameter.input,
          IdeaSortOrderHierarchy,
          true,
          this.taskId,
          this.authHeaderTyp,
          this.orderGroupContent,
          (this as any).$t,
          this.filter.stateFilter,
          this.filter.textFilter
        );
        const getUncategorizedIdeas = viewService.getViewIdeas(
          this.task.topicId,
          this.task.parameter.input,
          this.filter.orderType,
          null,
          this.authHeaderTyp,
          (this as any).$t,
          this.filter.stateFilter,
          this.filter.textFilter
        );

        await getCategorizedIdeas.then((result) => {
          this.ideas = result.ideas;
          this.orderGroupContent = result.oderGroups;
        });
        await getUncategorizedIdeas.then((result) => {
          if (this.orderGroupContent[CategoryUndefined]) {
            this.orderGroupContent[CategoryUndefined].ideas = result.filter(
              (idea) =>
                this.ideas.find(
                  (categoryIdea) =>
                    categoryIdea.id === idea.id && !categoryIdea.category
                )
            );
          }
        });
      }
    }
    return Object.keys(this.orderGroupContent);
  }

  async getCategories(): Promise<void> {
    if (this.taskId) {
      await categorisationService
        .getCategoriesForTask(this.taskId, this.authHeaderTyp)
        .then((categories) => {
          this.categories = categories;
        });
    }
  }

  async mounted(): Promise<void> {
    this.startInterval();
  }

  startInterval(): void {
    this.interval = setInterval(this.getCollapseContent, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }
}
</script>

<style lang="scss" scoped>
.fixHead {
  padding-right: 1rem;
  padding-bottom: 1rem;
  overflow-y: auto;
  scrollbar-color: var(--color-primary) var(--color-gray);
  scrollbar-width: thin;
  //height: calc(var(--app-height) * 0.68);

  .sticky-header {
    position: sticky;
    top: -2px;
  }
}

.el-main {
  overflow: unset;
}

.expand {
  &__header {
    color: white;
  }
}

.expand > :first-child {
  color: white;
}

.sticky-header::v-deep {
  background-color: var(--color-background-gray);

  .column {
    background-color: var(--color-background-gray);
    .el-card,
    .item {
      height: 100%;
    }
  }
}

.column::v-deep {
  max-width: 16rem;
  min-width: 10rem;

  .el-card,
  .item {
    height: unset;
  }
}

@media screen and (max-width: 768px) {
  .fixHead {
    max-width: var(--app-width);
    margin-right: -2.5rem;
    margin-left: -2.5rem;
    padding-right: 2.5rem;
    padding-left: 2.5rem;
  }
}
</style>
