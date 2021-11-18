<template>
  <FilterSection>
    <label for="orderType" class="heading heading--xs">{{
      $t('module.categorisation.default.moderatorContent.sortOrder')
    }}</label>
    <select
      v-model="orderType"
      id="orderType"
      class="select select--fullwidth"
      @change="getCollapseContent(true)"
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
  <div class="scroll-x">
    <div class="columns is-mobile drag-header">
      <draggable
        class="column"
        v-for="(orderGroup, orderGroupKey) in orderGroupContentCards"
        :key="orderGroupKey"
        :id="orderGroup.category ? orderGroup.category.id : null"
        v-model="orderGroup.ideas"
        draggable=".drag-item"
        item-key="id"
        group="idea"
        @end="dragDone"
      >
        <template v-slot:header>
          <CategoryCard
            v-if="orderGroup.category.id !== addCategoryKey"
            :category="orderGroup.category"
            v-model:ideas="orderGroup.ideas"
            @categoryChanged="getCollapseContent"
          >
          </CategoryCard>
          <AddItem
            v-else
            :text="$t('module.categorisation.default.moderatorContent.add')"
            :is-column="true"
            @addNew="openCategorySettings"
          />
        </template>
        <template v-slot:item>
          <span></span>
        </template>
      </draggable>
    </div>
    <div class="columns is-mobile">
      <draggable
        class="column group-items"
        v-for="(orderGroup, orderGroupKey) in orderGroupContentCards"
        :key="orderGroupKey"
        :id="orderGroup.category ? orderGroup.category.id : null"
        v-model="orderGroup.ideas"
        draggable=".drag-item"
        item-key="id"
        group="idea"
        @end="dragDone"
      >
        <!--<template v-slot:header>
          <CategoryCard
            v-if="orderGroup.category.id !== addCategoryKey"
            :category="orderGroup.category"
            v-model:ideas="orderGroup.ideas"
            @categoryChanged="getCollapseContent"
          >
          </CategoryCard>
          <AddItem
            v-else
            :text="$t('module.categorisation.default.moderatorContent.add')"
            :is-column="true"
            @addNew="openCategorySettings"
          />
        </template>-->
        <template v-slot:item="{ element }">
          <IdeaCard
            :key="element.id"
            :id="element.id"
            :idea="element"
            :is-selectable="false"
            :is-editable="true"
            :isDraggable="true"
            class="drag-item el-main"
            :style="{ 'border-color': orderGroup.category.parameter.color }"
          />
        </template>
      </draggable>
    </div>
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
      <div class="layout__columns">
        <draggable
          :id="item.category ? item.category.id : null"
          v-model="item.ideas"
          draggable=".drag-item"
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
              :is-editable="true"
              :isDraggable="true"
              class="drag-item"
            />
          </template>
        </draggable>
      </div>
    </el-collapse-item>
  </el-collapse>
  <CategorySettings
    v-if="task"
    v-model:show-modal="showCategorySettings"
    :task-id="task.id"
    :addIdeas="orderGroupContentCards[addCategoryKey].ideas"
    v-model:category-id="editCategoryId"
    @categoryCreated="getCollapseContent"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import draggable from 'vuedraggable';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as categorisationService from '@/services/categorisation-service';
import { Category } from '@/types/api/Category';
import * as ideaService from '@/services/idea-service';
import * as taskService from '@/services/task-service';
import IdeaSortOrder, {
  DefaultIdeaSortOrder,
  IdeaSortOrderCategorisation,
} from '@/types/enum/IdeaSortOrder';
import { Idea } from '@/types/api/Idea';
import { Task } from '@/types/api/Task';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import CategorySettings from '@/modules/categorisation/default/molecules/CategorySettings.vue';
import CategoryCard from '@/modules/categorisation/default/molecules/CategoryCard.vue';
import CollapseTitle from '@/components/moderator/atoms/CollapseTitle.vue';
import FilterSection from '@/components/moderator/atoms/FilterSection.vue';
import {
  OrderGroup,
  OrderGroupList,
  SortOrderOption,
} from '@/types/api/OrderGroup';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { reloadCollapseContent } from '@/utils/collapse';

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

  readonly addCategoryKey = '<new>';
  addCategory = new CategoryContent({
    id: this.addCategoryKey,
    keywords: this.addCategoryKey,
    parameter: { color: 'white' },
  } as Category);
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
  orderType: string = DefaultIdeaSortOrder;

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getCollapseContent(true);
  }

  @Watch('showCategorySettings', { immediate: true })
  onShowCategorySettingsChanged(): void {
    if (
      !this.showCategorySettings &&
      this.addCategoryKey in this.orderGroupContentCards
    ) {
      this.orderGroupContentCards[this.addCategoryKey].ideas.forEach((idea) => {
        const orderGroupName = (this as any).$t(
          'module.categorisation.default.moderatorContent.undefined'
        );
        switch (this.orderType) {
          case IdeaSortOrder.TIMESTAMP:
          case IdeaSortOrder.ALPHABETICAL:
          case IdeaSortOrder.ORDER:
            this.orderGroupContentSelection[orderGroupName].ideas.push(idea);
            break;
          default:
            this.orderGroupContentSelection[idea.orderGroup].ideas.push(idea);
        }
      });
      this.orderGroupContentCards[this.addCategoryKey].ideas = [];
    }
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

  async getCollapseContent(reloadTabState = false): Promise<void> {
    reloadCollapseContent(
      this.openTabs,
      Object.keys(this.orderGroupContentSelection),
      this.getIdeas,
      reloadTabState
    ).then((tabs) => (this.openTabs = tabs));
  }

  async getIdeas(): Promise<string[]> {
    if (this.isDragging) return Object.keys(this.orderGroupContentSelection);

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

      if (this.task && this.task.parameter.dependencyTaskId) {
        let categoryOrder = `[${IdeaSortOrderCategorisation},${this.orderType}]`;
        if (
          !this.orderType ||
          this.orderType.startsWith(IdeaSortOrderCategorisation)
        )
          categoryOrder = IdeaSortOrderCategorisation;
        await ideaService
          .getIdeasForTask(
            this.task.parameter.dependencyTaskId,
            categoryOrder,
            this.taskId
          )
          .then((ideas) => {
            this.ideas = ideas;
            ideas
              .filter((ideaItem) => ideaItem.category)
              .forEach((ideaItem) => {
                if (ideaItem.orderGroup) {
                  const orderGroup = orderGroupContent[ideaItem.orderGroup];
                  if (orderGroup) {
                    orderGroup.ideas.push(ideaItem);
                  }
                }
              });
          });

        await ideaService
          .getOrderGroups(
            this.task.parameter.dependencyTaskId,
            this.orderType,
            this.taskId,
            EndpointAuthorisationType.MODERATOR,
            this.orderGroupContentSelection,
            (idea: Idea) => !idea.category
          )
          .then((result) => {
            const orderGroupName = (this as any).$t(
              'module.categorisation.default.moderatorContent.undefined'
            );
            switch (this.orderType) {
              case IdeaSortOrder.TIMESTAMP:
              case IdeaSortOrder.ALPHABETICAL:
              case IdeaSortOrder.ORDER:
                this.orderGroupContentSelection = {};
                this.orderGroupContentSelection[orderGroupName] =
                  new OrderGroup(result.ideas.filter((idea) => !idea.category));
                break;
              default:
                this.orderGroupContentSelection = result.oderGroups;
            }
          });
      }

      this.orderGroupContentCards = orderGroupContent;
      this.orderGroupContentCards[this.addCategoryKey] = this.addCategory;
    }
    return Object.keys(this.orderGroupContentSelection);
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
    this.orderGroupContentCards[this.addCategoryKey] = this.addCategory;
    this.startIdeaInterval();
  }

  startIdeaInterval(): void {
    this.interval = setInterval(this.getCollapseContent, this.intervalTime);
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
      if (event.to.id === this.addCategoryKey) {
        this.openCategorySettings();
      } else {
        const category = this.categories.find(
          (category) => category.id == event.to.id
        );
        if (category) {
          await categorisationService.addIdeasToCategory(
            event.to.id,
            this.orderGroupContentCards[category.keywords].ideas.map(
              (idea) => idea.id
            )
          );
        }
      }
    } else {
      await categorisationService.removeIdeasFromCategory(event.from.id, [
        event.item.id,
      ]);
    }
    await this.getCollapseContent();
    this.isDragging = false;
  }
}
</script>

<style lang="scss" scoped>
.scroll-x {
  overflow-x: auto;
  padding: 1rem 1rem;
  margin: 0 -1rem;
}

.group-items {
  max-height: 50vh;
  overflow-y: auto;
}

.drag-header::v-deep {
  .column {
    .el-card,
    .item {
      height: 100%;
    }
  }
}

.column::v-deep {
  max-width: 25%;
  min-width: 10rem;

  .el-card,
  .item {
    height: unset;
  }
}

.item {
  cursor: grab;
}

.icon {
  margin-right: 0.5em;
}
</style>
