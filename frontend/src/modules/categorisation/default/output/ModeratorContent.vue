<template>
  <FilterSection>
    <label for="orderType" class="heading heading--xs">{{
      $t('module.categorisation.default.moderatorContent.sortOrder')
    }}</label>
    <select
      v-model="orderType"
      id="orderType"
      class="select select--fullwidth"
      @change="getIdeas(true)"
    >
      <option
        v-for="type in sortOrderOptions"
        :key="type.orderType"
        :value="
          type.ref ? `${type.orderType}&refId=${type.ref.id}` : type.orderType
        "
      >
        <span>
          {{ $t(`enum.ideaSortOrder.${type.orderType}`) }}
        </span>
        <span v-if="type.ref"> - {{ type.ref.name }} </span>
      </option>
    </select>
  </FilterSection>
  <div class="columns is-multiline is-mobile">
    <draggable
      class="column"
      v-for="(orderGroup, orderGroupKey) in orderGroupContentCards"
      :key="orderGroupKey"
      :id="orderGroup.category ? orderGroup.category.id : null"
      v-model="orderGroup.ideas"
      draggable=".item"
      item-key="id"
      group="idea"
      @end="dragDone"
    >
      <template v-slot:header>
        <CategoryCard
          :category="orderGroup.category"
          :ideas="orderGroup.ideas"
          @categoryChanged="getIdeas"
        >
        </CategoryCard>
      </template>
      <template v-slot:item>
        <span></span>
      </template>
    </draggable>
  </div>

  <el-collapse v-model="openTabs">
    <el-collapse-item
      v-for="(item, key) in orderGroupContentSelection"
      :key="key"
      :name="key"
    >
      <template #title>
        <CollapseTitle :text="key" :color="item.color">
          <span
            role="button"
            class="icon"
            v-if="item.ideas.length > item.displayCount"
            v-on:click="item.displayCount = 1000"
          >
            <font-awesome-icon icon="ellipsis-h" />
          </span>
        </CollapseTitle>
      </template>
      <div class="layout__4columns">
        <draggable
          :id="item.category ? item.category.id : null"
          v-model="item.ideas"
          draggable=".item"
          item-key="id"
          group="idea"
          @end="dragDone"
        >
          <template v-slot:item="{ element, index }">
            <IdeaCard
              :key="element.id"
              v-if="index < item.displayCount"
              :id="element.id"
              :idea="element"
              :is-selectable="false"
              :is-editable="false"
              class="item"
            />
          </template>
        </draggable>
      </div>
    </el-collapse-item>
  </el-collapse>
  <AddItem
    :text="$t('module.categorisation.default.moderatorContent.add')"
    @addNew="openCategorySettings"
  />
  <CategorySettings
    v-if="task"
    v-model:show-modal="showCategorySettings"
    :task-id="task.id"
    v-model:category-id="editCategoryId"
    @categoryCreated="getIdeas"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import draggable from 'vuedraggable';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import Expand from '@/components/shared/atoms/Expand.vue';
import * as categorisationService from '@/services/categorisation-service';
import { Category } from '@/types/api/Category';
import * as ideaService from '@/services/idea-service';
import * as taskService from '@/services/task-service';
import { IdeaSortOrderCategorisation } from '@/types/enum/IdeaSortOrder';
import { Idea } from '@/types/api/Idea';
import { Task } from '@/types/api/Task';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import CategorySettings from '@/modules/categorisation/default/molecules/CategorySettings.vue';
import CategoryCard from '@/modules/categorisation/default/molecules/CategoryCard.vue';
import CollapseTitle from '@/components/moderator/atoms/CollapseTitle.vue';
import FilterSection from '@/components/moderator/atoms/FilterSection.vue';
import { OrderGroupList, SortOrderOption } from '@/types/api/OrderGroup';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

class CategoryContent {
  ideas: Idea[];
  category: Category;

  constructor(category: Category, ideas: Idea[] = []) {
    this.ideas = ideas;
    this.category = category;
  }

  get color(): string | null {
    if (this.category) return this.category.parameter.color;
    return null;
  }
}

interface CategoryContentList {
  [name: string]: CategoryContent;
}

@Options({
  components: {
    IdeaCard,
    Expand,
    AddItem,
    CategorySettings,
    CategoryCard,
    CollapseTitle,
    FilterSection,
    draggable,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue {
  @Prop() readonly taskId!: string;
  showCategorySettings = false;
  editCategoryId: string | null = null;
  task: Task | null = null;
  categories: Category[] = [];
  ideas: Idea[] = [];
  orderGroupContentCards: CategoryContentList = {};
  orderGroupContentSelection: OrderGroupList = {};
  openTabs: string[] = [];
  isDragging = false;

  newCategory = {
    keywords: '',
    description: '',
  };
  readonly intervalTime = 10000;
  interval!: any;

  sortOrderOptions: SortOrderOption[] = [];
  orderType = '';

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getIdeas(true);
  }

  async getTask(): Promise<void> {
    if (this.taskId) {
      await taskService.getTaskById(this.taskId).then((task) => {
        this.task = task;
        ideaService.getSortOrderOptions(task.topicId).then((options) => {
          this.sortOrderOptions = options;
          if (options.length > 0) this.orderType = options[0].orderType;
        });
      });
    }
  }

  async getIdeas(reloadTabState = false): Promise<void> {
    if (this.isDragging) return;

    const oldKeys = Object.keys(this.orderGroupContentSelection);

    if (this.taskId) {
      if (!this.task) await this.getTask();
      await this.getCategories();
      const orderGroupContent: CategoryContentList = {};
      this.categories.forEach((category) => {
        orderGroupContent[category.keywords] = new CategoryContent(
          category,
          []
        );
      });

      if (this.task) {
        let categoryOrder = `[${IdeaSortOrderCategorisation},${this.orderType}]`;
        if (
          !this.orderType ||
          this.orderType.startsWith(IdeaSortOrderCategorisation)
        )
          categoryOrder = IdeaSortOrderCategorisation;
        await ideaService
          .getIdeasForTask(
            this.task.parameter.brainstormingTaskId,
            categoryOrder,
            this.taskId
          )
          .then((ideas) => {
            this.ideas = ideas;
            ideas
              .filter((ideaItem) => ideaItem.category)
              .forEach((ideaItem) => {
                if (ideaItem.order) {
                  const orderGroup = orderGroupContent[ideaItem.order];
                  if (orderGroup) {
                    orderGroup.ideas.push(ideaItem);
                  }
                }
              });
          });

        await ideaService
          .getOrderGroups(
            this.task.parameter.brainstormingTaskId,
            this.orderType,
            this.taskId,
            EndpointAuthorisationType.MODERATOR,
            this.orderGroupContentSelection,
            (idea: Idea) => !idea.category
          )
          .then((result) => {
            this.orderGroupContentSelection = result.oderGroups;
          });
      }

      this.orderGroupContentCards = orderGroupContent;
    }
    const newKeys = Object.keys(this.orderGroupContentSelection);
    if (reloadTabState) this.openTabs = newKeys;
    else {
      const addedKeys = newKeys.filter((item) => oldKeys.indexOf(item) < 0);
      this.openTabs = this.openTabs.concat(addedKeys);
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

  openCategorySettings(): void {
    this.editCategoryId = null;
    this.showCategorySettings = true;
  }

  /* eslint-disable @typescript-eslint/explicit-module-boundary-types*/
  async dragDone(event: any): Promise<void> {
    this.isDragging = true;
    if (event.to.id) {
      await categorisationService.addIdeasToCategory(event.to.id, [
        event.item.id,
      ]);
    } else {
      await categorisationService.removeIdeasFromCategory(event.from.id, [
        event.item.id,
      ]);
    }
    await this.getIdeas();
    this.isDragging = false;
  }
}
</script>

<style lang="scss" scoped>
.item {
  cursor: grab;
}

.icon {
  margin-right: 0.5em;
}
</style>
