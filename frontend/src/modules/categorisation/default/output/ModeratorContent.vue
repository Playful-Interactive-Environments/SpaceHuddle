<template>
  <IdeaFilter
    :taskId="taskId"
    v-model="filter"
    @change="getCollapseContent(true)"
  />
  <div ref="data" class="media">
    <div class="media-left no-category" ref="noCategoryColumn">
      <el-scrollbar
        native
        :height="`calc(100vh - ${topNoCategoryColumn}px - 1rem)`"
      >
        <el-collapse v-model="openTabs">
          <el-collapse-item
            v-for="(item, key) in orderGroupContentSelection"
            :key="key"
            :name="key"
          >
            <template #title>
              <CollapseTitle :text="key" :avatar="item.avatar">
                <span
                  role="button"
                  class="awesome-icon"
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
                handle=".drag-item"
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
                    v-model:collapseIdeas="filter.collapseIdeas"
                    class="drag-item"
                    @ideaDeleted="getCollapseContent"
                  />
                </template>
                <template v-slot:footer>
                  <AddItem
                    v-if="item.ideas.length > item.displayCount"
                    :text="
                      $t('module.selection.default.moderatorContent.displayAll')
                    "
                    :isColumn="false"
                    v-on:click="item.displayCount = 1000"
                    class="showMore"
                  />
                </template>
              </draggable>
            </div>
          </el-collapse-item>
        </el-collapse>
      </el-scrollbar>
    </div>
    <div class="media-content">
      <div class="scroll-x">
        <draggable
          class="columns is-mobile drag-header"
          v-model="orderGroupContentCardValues"
          handle=".drag-item"
          item-key="id"
          group="category"
          @end="dragCategory"
        >
          <template v-slot:item="{ element }">
            <draggable
              :id="element.id"
              class="column"
              v-model="element.ideas"
              handle=".drag-item-none"
              item-key="id"
              group="idea"
            >
              <template v-slot:header>
                <CategoryCard
                  v-if="element.category.id !== addCategoryKey"
                  :category="element.category"
                  v-model:ideas="element.ideas"
                  @categoryChanged="getCollapseContent"
                  class="drag-item"
                >
                </CategoryCard>
                <AddItem
                  v-else
                  :text="
                    $t('module.categorisation.default.moderatorContent.add')
                  "
                  :is-column="true"
                  @addNew="openCategorySettings"
                />
              </template>
              <template v-slot:item>
                <span></span>
              </template>
            </draggable>
          </template>
        </draggable>
        <div class="columns is-mobile" ref="categoryColumns">
          <draggable
            class="column group-items"
            v-for="orderGroup in orderGroupContentCardValues"
            :key="orderGroup.id"
            :id="orderGroup.category ? orderGroup.category.id : null"
            v-model="orderGroup.ideas"
            handle=".drag-item"
            item-key="id"
            group="idea"
            @end="dragDone"
            :style="{
              'max-height': `calc(100vh - ${topCategoryColumns}px - 1rem)`,
            }"
          >
            <template v-slot:item="{ element }">
              <IdeaCard
                :key="element.id"
                :id="element.id"
                :idea="element"
                :is-selectable="false"
                :is-editable="true"
                :isDraggable="true"
                v-model:collapseIdeas="filter.collapseIdeas"
                class="drag-item el-main"
                :style="{ 'border-color': orderGroup.category.parameter.color }"
                @ideaDeleted="getCollapseContent"
              />
            </template>
          </draggable>
        </div>
      </div>
    </div>
  </div>

  <CategorySettings
    v-if="task"
    v-model:show-modal="showCategorySettings"
    :task-id="task.id"
    :addIdeas="orderGroupContentCards[addCategoryKey].ideas"
    :order="categories.length"
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
import * as taskService from '@/services/task-service';
import * as viewService from '@/services/view-service';
import IdeaSortOrder, {
  IdeaSortOrderHierarchy,
} from '@/types/enum/IdeaSortOrder';
import { Idea } from '@/types/api/Idea';
import { Task } from '@/types/api/Task';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import CategorySettings from '@/modules/categorisation/default/molecules/CategorySettings.vue';
import CategoryCard from '@/modules/categorisation/default/molecules/CategoryCard.vue';
import CollapseTitle from '@/components/moderator/atoms/CollapseTitle.vue';
import { OrderGroup, OrderGroupList } from '@/types/api/OrderGroup';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { reloadCollapseContent } from '@/utils/collapse';
import {
  CategoryContent,
  CategoryContentList,
} from '@/types/api/CategoryContent';
import { EventType } from '@/types/enum/EventType';
import { IModeratorContent } from '@/types/ui/IModeratorContent';
import IdeaFilter, {
  defaultFilterData,
  FilterData,
} from '@/components/moderator/molecules/IdeaFilter.vue';

@Options({
  components: {
    IdeaFilter,
    IdeaCard,
    AddItem,
    CategorySettings,
    CategoryCard,
    CollapseTitle,
    draggable,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue implements IModeratorContent {
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

  filter: FilterData = { ...defaultFilterData };

  get orderGroupContentCardValues(): CategoryContent[] {
    return Object.values(this.orderGroupContentCards).sort(
      (a, b) => a.order - b.order
    );
  }

  set orderGroupContentCardValues(list: CategoryContent[]) {
    list.forEach((item, index) => {
      if (item.id !== this.addCategoryKey) item.category.order = index;
    });
  }

  @Watch('taskId', { immediate: true })
  async reloadTaskSettings(): Promise<void> {
    this.categories = [];
    this.ideas = [];
    this.openTabs = [];
    this.orderGroupContentSelection = {};
    this.orderGroupContentCards = {};
    this.orderGroupContentCards[this.addCategoryKey] = this.addCategory;
    await this.getTask();
    await this.getCollapseContent(true);
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
        switch (this.filter.orderType) {
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
      await taskService.getTaskById(this.taskId).then(async (task) => {
        this.task = task;
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
    const startTaskId = this.taskId;

    if (startTaskId) {
      if (!this.task || this.task.id !== startTaskId) await this.getTask();
      await this.getCategories();
      const orderGroupContent: CategoryContentList = {};
      this.categories.forEach((category) => {
        orderGroupContent[category.keywords] = new CategoryContent(
          category,
          []
        );
      });

      if (this.task && this.task.parameter.input) {
        const getCategorizedIdeas = viewService.getViewIdeas(
          this.task.topicId,
          this.task.parameter.input,
          IdeaSortOrderHierarchy,
          startTaskId,
          EndpointAuthorisationType.MODERATOR,
          (this as any).$t,
          this.filter.stateFilter,
          this.filter.textFilter
        );
        const getUncategorizedIdeas = viewService.getViewOrderGroups(
          this.task.topicId,
          this.task.parameter.input,
          this.filter.orderType,
          null,
          EndpointAuthorisationType.MODERATOR,
          this.orderGroupContentSelection,
          (this as any).$t,
          this.filter.stateFilter,
          this.filter.textFilter
        );

        await getCategorizedIdeas.then((ideas) => {
          if (this.taskId === startTaskId) {
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
          }
        });

        await getUncategorizedIdeas.then((result) => {
          if (this.taskId === startTaskId) {
            const orderGroupName = (this as any).$t(
              'module.categorisation.default.moderatorContent.undefined'
            );
            let orderGroupContentSelection: OrderGroupList = {};
            let orderGroupKeys: string[] = [];
            switch (this.filter.orderType) {
              case IdeaSortOrder.TIMESTAMP:
              case IdeaSortOrder.ALPHABETICAL:
              case IdeaSortOrder.ORDER:
                orderGroupContentSelection[orderGroupName] = new OrderGroup(
                  result.ideas.filter((idea) =>
                    this.ideas.find(
                      (categoryIdea) =>
                        categoryIdea.id === idea.id && !categoryIdea.category
                    )
                  )
                );
                break;
              default:
                orderGroupKeys = Object.keys(result.oderGroups);
                for (const orderGroup of orderGroupKeys) {
                  const orderGroupIdeas = result.oderGroups[
                    orderGroup
                  ].ideas.filter((idea) =>
                    this.ideas.find(
                      (categoryIdea) =>
                        categoryIdea.id === idea.id && !categoryIdea.category
                    )
                  );
                  result.oderGroups[orderGroup].ideas = orderGroupIdeas;
                  if (orderGroupIdeas.length === 0)
                    delete result.oderGroups[orderGroup];
                }
                orderGroupContentSelection = result.oderGroups;
            }
            Object.keys(orderGroupContentSelection).forEach((key) => {
              if (key in this.orderGroupContentSelection)
                orderGroupContentSelection[key].displayCount =
                  this.orderGroupContentSelection[key].displayCount;
            });
            this.orderGroupContentSelection = orderGroupContentSelection;
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

  topNoCategoryColumn = 80;
  topCategoryColumns = 160;

  async mounted(): Promise<void> {
    this.orderGroupContentCards[this.addCategoryKey] = this.addCategory;
    this.startInterval();

    this.eventBus.off(EventType.CHANGE_SETTINGS);
    this.eventBus.on(EventType.CHANGE_SETTINGS, async (taskId) => {
      if (this.taskId === taskId) {
        await this.reloadData();
      }
    });
  }

  startInterval(): void {
    this.interval = setInterval(this.reloadData, this.intervalTime);
  }

  async reloadData(): Promise<void> {
    await this.getTask();
    await this.getCollapseContent();
  }

  updated(): void {
    this.loadTopPositions();
  }

  loadTopPositions(): void {
    if (this.$refs.noCategoryColumn) {
      this.topNoCategoryColumn = (
        this.$refs.noCategoryColumn as HTMLElement
      ).getBoundingClientRect().top;
    }
    if (this.$refs.categoryColumns) {
      this.topCategoryColumns = (
        this.$refs.categoryColumns as HTMLElement
      ).getBoundingClientRect().top;
    }
  }

  unmounted(): void {
    clearInterval(this.interval);
  }

  openCategorySettings(): void {
    this.editCategoryId = null;
    this.showCategorySettings = true;
  }

  async dragCategory(): Promise<void> {
    this.categories.forEach((category) => {
      categorisationService.putCategory(category);
    });
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
    } else if (event.from.id) {
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
.add::v-deep {
  &.add--column {
    min-height: unset;

    .el-card__body {
      min-height: unset;
      padding: 5px 14px;
    }
  }
}

.no-category {
  max-width: 20%;
  min-width: 10rem;
  padding-right: 1rem;
  border-right: 2px var(--color-primary) solid;
}

.scroll-x {
  overflow-x: auto;
  scrollbar-color: var(--color-primary) var(--color-gray);
  scrollbar-width: thin;
  padding: 1rem 1rem 0 1rem;
  margin: 0 -1rem;

  .columns:last-child {
    margin-bottom: 0;
  }
}

.group-items {
  max-height: 50vh;
  overflow-y: auto;
  scrollbar-color: var(--color-primary) var(--color-gray);
  scrollbar-width: thin;
  padding: 0 0.75rem;
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

.awesome-icon {
  margin-right: 0.5em;
}

.drag-item {
  cursor: grab;
}

.showMore {
  color: var(--color-purple-dark);
  border-color: var(--color-purple-dark);
  cursor: pointer;
}

.el-card::v-deep {
  .el-card__body {
    padding: 14px;
  }
}
</style>
