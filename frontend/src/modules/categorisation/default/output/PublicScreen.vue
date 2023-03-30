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
            :ideas="categoryIdeas(category.id)"
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
            v-for="(idea, index) in categoryIdeas(category.id)"
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
import { reloadCollapseTabs } from '@/utils/collapse';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import CategoryCard from '@/modules/categorisation/default/molecules/CategoryCard.vue';
import * as categorisationService from '@/services/categorisation-service';
import {
  defaultFilterData,
  FilterData,
  getFilterForTask,
} from '@/components/moderator/molecules/IdeaFilter.vue';
import * as cashService from '@/services/cash-service';
import { View } from '@/types/api/View';
import * as ideaService from '@/services/idea-service';

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
  openTabs: string[] = [];
  CategoryUndefined = CategoryUndefined;
  filter: FilterData = { ...defaultFilterData };

  reloadTabState = true;
  categoryCash!: cashService.SimplifiedCashEntry<Category[]>;
  inputCash!: cashService.SimplifiedCashEntry<Idea[]>;
  outputCash!: cashService.SimplifiedCashEntry<Idea[]>;
  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      this.authHeaderTyp,
      60 * 60
    );
    this.categoryCash = categorisationService.registerGetCategoriesForTask(
      this.taskId,
      this.updateCategories,
      this.authHeaderTyp,
      10
    );
    this.inputCash = viewService.registerGetInputIdeas(
      this.taskId,
      this.filter.orderType,
      null,
      this.updateInputIdeas,
      EndpointAuthorisationType.MODERATOR,
      5 * 60
    );
    this.outputCash = viewService.registerGetInputIdeas(
      this.taskId,
      IdeaSortOrderHierarchy,
      this.taskId,
      this.updateCategorisedIdeas,
      EndpointAuthorisationType.MODERATOR,
      10,
      'categorised::'
    );
  }

  @Watch('task.topicId', { immediate: true })
  onTopicIdChanged(): void {
    if (this.task) {
      viewService.registerGetList(
        this.task.topicId,
        this.updateViews,
        EndpointAuthorisationType.MODERATOR,
        60 * 60
      );
    }
  }

  updateTask(task: Task): void {
    this.task = task;
    this.filter = getFilterForTask(this.task);
  }

  views: View[] = [];
  updateViews(views: View[]): void {
    this.views = views;
  }

  inputIdeas: Idea[] = [];
  updateInputIdeas(ideas: Idea[]): void {
    this.inputIdeas = ideas;
    this.updateIdeas();
  }

  categorisedIdeas: Idea[] = [];
  updateCategorisedIdeas(ideas: Idea[]): void {
    this.categorisedIdeas = ideas;
    this.updateIdeas();
  }

  updateCategories(categories: Category[]): void {
    this.categories = categories;
    this.updateIdeas();
  }

  @Watch('filter.orderType', { deep: true, immediate: true })
  onFilterDataChanged(): void {
    if (this.inputCash) {
      this.inputCash.parameter.urlParameter = ideaService.getIdeaListParameter(
        this.filter.orderType,
        null
      );
      this.inputCash.refreshData();
    }
  }

  categoryIdeas(category: string): Idea[] {
    if (category in this.orderGroupContent) {
      return this.orderGroupContent[category].ideas;
    }
    return [];
  }

  updateIdeas(): void {
    const oldTabs = Object.keys(this.orderGroupContent);
    const categorizedIdeas = ideaService.getOrderGroups(
      viewService.customizeView(
        this.categorisedIdeas,
        this.views,
        (this as any).$t,
        this.filter.stateFilter,
        this.filter.textFilter,
        this.task ? this.task.parameter.input.length : 1,
        false
      ),
      this.filter.orderAsc,
      this.orderGroupContent,
      () => {
        return true;
      },
      false
    );
    this.ideas = categorizedIdeas.ideas;
    this.orderGroupContent = categorizedIdeas.oderGroups;
    const uncategorizedIdeas = viewService.customizeView(
      this.inputIdeas,
      this.views,
      (this as any).$t,
      this.filter.stateFilter,
      this.filter.textFilter,
      this.task ? this.task.parameter.input.length : 1,
      false
    );
    if (this.orderGroupContent[CategoryUndefined]) {
      this.orderGroupContent[CategoryUndefined].ideas =
        uncategorizedIdeas.filter((idea) =>
          this.ideas.find(
            (categoryIdea) =>
              categoryIdea.id === idea.id && !categoryIdea.category
          )
        );
    }

    reloadCollapseTabs(
      this.openTabs,
      oldTabs,
      Object.keys(this.orderGroupContent),
      this.reloadTabState
    ).then((tabs) => (this.openTabs = tabs));
    this.reloadTabState = false;
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateViews);
    cashService.deregisterAllGet(this.updateCategories);
    cashService.deregisterAllGet(this.updateCategorisedIdeas);
    cashService.deregisterAllGet(this.updateInputIdeas);
  }

  unmounted(): void {
    this.deregisterAll();
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

.sticky-header {
  background-color: var(--color-background-gray);
}

.sticky-header::v-deep(.column) {
  background-color: var(--color-background-gray);
  .el-card,
  .item {
    height: 100%;
  }
}

.column {
  max-width: 16rem;
  min-width: 10rem;
}

.column::v-deep(.el-card) {
  height: unset;
}

.column::v-deep(.item) {
  height: unset;
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
