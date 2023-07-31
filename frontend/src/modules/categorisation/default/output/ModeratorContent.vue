<template>
  <IdeaFilter :taskId="taskId" v-model="filter" @change="refreshInput(true)" />
  <div ref="data" class="media">
    <div class="media-left no-category" ref="noCategoryColumn">
      <el-scrollbar
        native
        :height="`calc(var(--app-height) - ${topNoCategoryColumn}px - 1rem)`"
      >
        <el-collapse
          v-model="openTabs"
          :style="{
            height: `calc(var(--app-height) - ${topNoCategoryColumn}px - 1rem)`,
          }"
        >
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
            <div class="layout__columns uncategorizedDragArea">
              <draggable
                class="uncategorizedDragArea"
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
                    @ideaDeleted="refreshInAndOutput"
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
            <div class="column">
              <CategoryCard
                v-if="element.category.id !== addCategoryKey"
                :category="element.category"
                v-model:ideas="element.ideas"
                @categoryChanged="refreshCategories"
                @categoryDeleted="refreshOutputAndCategories"
                class="drag-item"
              >
              </CategoryCard>
              <AddItem
                v-else
                :text="$t('module.categorisation.default.moderatorContent.add')"
                :is-column="true"
                @addNew="openCategorySettings"
              />
            </div>
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
              height: `calc(var(--app-height) - ${topCategoryColumns}px - 1rem)`,
            }"
          >
            <template v-slot:header>
              <AddItem
                :text="
                  $t('module.categorisation.default.moderatorContent.dragIdea')
                "
                :is-column="true"
                :is-clickable="false"
                :display-plus="false"
                v-if="orderGroup.ideas.length === 0"
              />
            </template>
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
                @ideaDeleted="refreshInAndOutput"
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
    @categoryCreated="categoryCreated"
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
import { reloadCollapseTabs } from '@/utils/collapse';
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
import * as cashService from '@/services/cash-service';
import { View } from '@/types/api/View';
import * as ideaService from '@/services/idea-service';

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

  categoryCash!: cashService.SimplifiedCashEntry<Category[]>;
  inputCash!: cashService.SimplifiedCashEntry<Idea[]>;
  outputCash!: cashService.SimplifiedCashEntry<Idea[]>;
  @Watch('taskId', { immediate: true })
  async reloadTaskSettings(): Promise<void> {
    this.deregisterAll();
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.MODERATOR,
      60 * 60
    );
    this.categoryCash = categorisationService.registerGetCategoriesForTask(
      this.taskId,
      this.updateCategories,
      EndpointAuthorisationType.MODERATOR,
      20
    );
    this.inputCash = viewService.registerGetInputIdeas(
      this.taskId,
      this.filter.orderType,
      null,
      this.updateInputIdeas,
      EndpointAuthorisationType.MODERATOR,
      20
    );
    this.outputCash = viewService.registerGetInputIdeas(
      this.taskId,
      IdeaSortOrderHierarchy,
      this.taskId,
      this.updateCategorisedIdeas,
      EndpointAuthorisationType.MODERATOR,
      20,
      'categorised::'
    );
    this.categories = [];
    this.ideas = [];
    this.openTabs = [];
    this.orderGroupContentSelection = {};
    this.orderGroupContentCards = {};
    this.orderGroupContentCards[this.addCategoryKey] = this.addCategory;
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
        const inputIdea = this.inputIdeas.find((item) => item.id === idea.id);
        if (inputIdea && inputIdea.orderGroup === idea.orderGroup) {
          switch (this.filter.orderType) {
            case IdeaSortOrder.TIMESTAMP:
            case IdeaSortOrder.ALPHABETICAL:
            case IdeaSortOrder.ORDER:
              this.orderGroupContentSelection[orderGroupName].ideas.push(
                inputIdea
              );
              break;
            default:
              if (inputIdea)
                this.orderGroupContentSelection[
                  inputIdea.orderGroup
                ].ideas.push(inputIdea);
          }
        } else {
          this.orderGroupContentCards[idea.orderText].ideas.push(idea);
        }
      });
      this.orderGroupContentCards[this.addCategoryKey].ideas = [];
    }
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

  async categoryCreated(category: Category): Promise<void> {
    if (this.orderGroupContentCards[this.addCategoryKey].ideas.length > 0) {
      const idea = this.orderGroupContentCards[this.addCategoryKey].ideas[0];
      this.history.push({
        toCategoryId: category.id,
        fromCategoryId: idea.category ? idea.category.id : '',
        ideas: this.orderGroupContentCards[this.addCategoryKey].ideas.map(
          (idea) => idea.id
        ),
      });
      this.refreshOutput();
    }
    await this.refreshCategories();
  }

  reloadTabState = true;
  refreshCategories(reloadTabState = false): void {
    this.reloadTabState = reloadTabState;
    if (this.categoryCash) this.categoryCash.refreshData();
  }

  refreshOutputAndCategories(reloadTabState = false): void {
    this.reloadTabState = reloadTabState;
    if (this.categoryCash) this.categoryCash.refreshData();
    if (this.outputCash) this.outputCash.refreshData();
  }

  refreshInput(reloadTabState = false): void {
    this.reloadTabState = reloadTabState;
    if (this.inputCash) this.inputCash.refreshData();
  }

  refreshOutput(reloadTabState = false): void {
    this.reloadTabState = reloadTabState;
    if (this.outputCash) this.outputCash.refreshData();
  }

  refreshInAndOutput(reloadTabState = false): void {
    this.reloadTabState = reloadTabState;
    if (this.outputCash) this.outputCash.refreshData();
    if (this.inputCash) this.inputCash.refreshData();
  }

  updateIdeas(): void {
    if (this.isDragging) return;
    const orderGroupContent: CategoryContentList = {};
    this.categories.forEach((category) => {
      orderGroupContent[category.id] = new CategoryContent(category, []);
    });
    const categorizedIdeas = viewService.customizeView(
      this.categorisedIdeas,
      this.views,
      (this as any).$t,
      this.filter.stateFilter,
      this.filter.textFilter,
      this.task ? this.task.parameter.input.length : 1,
      false
    );
    categorizedIdeas
      .filter((ideaItem) => ideaItem.category)
      .forEach((ideaItem) => {
        if (ideaItem.orderText) {
          const orderGroup = orderGroupContent[ideaItem.orderText];
          if (orderGroup) {
            orderGroup.ideas.push(ideaItem);
          }
        }
      });
    this.ideas = categorizedIdeas;
    const uncategorizedIdeas = ideaService.getOrderGroups(
      viewService.customizeView(
        this.inputIdeas,
        this.views,
        (this as any).$t,
        this.filter.stateFilter,
        this.filter.textFilter,
        this.task ? this.task.parameter.input.length : 1
      ),
      this.filter.orderAsc,
      this.orderGroupContentSelection,
      () => {
        return true;
      }
    );
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
          uncategorizedIdeas.ideas.filter((idea) =>
            this.ideas.find(
              (categoryIdea) =>
                categoryIdea.id === idea.id && !categoryIdea.category
            )
          )
        );
        break;
      default:
        orderGroupKeys = Object.keys(uncategorizedIdeas.oderGroups);
        for (const orderGroup of orderGroupKeys) {
          const orderGroupIdeas = uncategorizedIdeas.oderGroups[
            orderGroup
          ].ideas.filter((idea) =>
            this.ideas.find(
              (categoryIdea) =>
                categoryIdea.id === idea.id && !categoryIdea.category
            )
          );
          uncategorizedIdeas.oderGroups[orderGroup].ideas = orderGroupIdeas;
          if (orderGroupIdeas.length === 0)
            delete uncategorizedIdeas.oderGroups[orderGroup];
        }
        orderGroupContentSelection = uncategorizedIdeas.oderGroups;
    }
    Object.keys(orderGroupContentSelection).forEach((key) => {
      if (key in this.orderGroupContentSelection)
        orderGroupContentSelection[key].displayCount =
          this.orderGroupContentSelection[key].displayCount;
    });
    const oldTabs = Object.keys(this.orderGroupContentSelection);
    this.orderGroupContentSelection = orderGroupContentSelection;
    this.orderGroupContentCards = orderGroupContent;
    this.orderGroupContentCards[this.addCategoryKey] = this.addCategory;

    reloadCollapseTabs(
      this.openTabs,
      oldTabs,
      Object.keys(this.orderGroupContentSelection),
      this.reloadTabState
    ).then((tabs) => (this.openTabs = tabs));
    this.reloadTabState = false;
  }

  topNoCategoryColumn = 80;
  topCategoryColumns = 160;

  async mounted(): Promise<void> {
    this.orderGroupContentCards[this.addCategoryKey] = this.addCategory;
    this.eventBus.off(EventType.CHANGE_SETTINGS);
    //eslint-disable-next-line @typescript-eslint/no-unused-vars
    this.eventBus.on(EventType.CHANGE_SETTINGS, async (taskId) => {
      this.refreshInput();
    });
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

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateViews);
    cashService.deregisterAllGet(this.updateInputIdeas);
    cashService.deregisterAllGet(this.updateCategorisedIdeas);
    cashService.deregisterAllGet(this.updateCategories);
  }

  unmounted(): void {
    this.deregisterAll();
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
    await this.addToHistory(event);
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
            this.orderGroupContentCards[category.id].ideas.map(
              (idea) => idea.id
            )
          );
        }
        this.refreshOutput();
      }
    } else if (event.from.id) {
      await categorisationService.removeIdeasFromCategory(event.from.id, [
        event.item.id,
      ]);
      this.refreshOutput();
    }
    this.isDragging = false;
  }

  history: { toCategoryId: string; fromCategoryId: string; ideas: string[] }[] =
    [];
  historyIndex = -1;
  async addToHistory(event: any): Promise<void> {
    this.history.length = this.historyIndex + 1;
    if (event.to.id) {
      if (event.to.id !== this.addCategoryKey) {
        const category = this.categories.find(
          (category) => category.id == event.to.id
        );
        if (category) {
          this.history.push({
            toCategoryId: event.to.id,
            fromCategoryId: event.from.id,
            ideas: this.orderGroupContentCards[category.id].ideas.map(
              (idea) => idea.id
            ),
          });
        }
      }
    } else if (event.from.id) {
      this.history.push({
        toCategoryId: event.to.id,
        fromCategoryId: event.from.id,
        ideas: [event.item.id],
      });
    }
    this.historyIndex = this.history.length - 1;
  }

  async executeHistoryStep(index = -1): Promise<void> {
    if (this.history.length > 0) {
      const historyStep =
        index > -1 && this.history.length > index
          ? this.history[index]
          : this.history[this.history.length - 1];
      if (historyStep.toCategoryId) {
        await categorisationService.addIdeasToCategory(
          historyStep.toCategoryId,
          historyStep.ideas
        );
      } else if (historyStep.fromCategoryId) {
        await categorisationService.removeIdeasFromCategory(
          historyStep.fromCategoryId,
          historyStep.ideas
        );
      }
      this.refreshInAndOutput();
      this.refreshCategories();
    }
  }
}
</script>

<style lang="scss" scoped>
.add--column.add {
  min-height: unset;
}

.add--column.add::v-deep(.el-card__body) {
  min-height: unset;
  padding: 5px 14px;
}

.no-category {
  max-width: 20%;
  min-width: 10rem;
  padding-right: 1rem;
  border-right: 2px var(--color-primary) solid;

  .el-collapse {
    display: flex;
    flex-direction: column;

    .el-collapse-item {
      flex-grow: 1;
    }

    .el-collapse-item::v-deep(.el-collapse-item__wrap) {
      height: calc(100% - 3rem);
    }

    .el-collapse-item::v-deep(.el-collapse-item__content) {
      padding-bottom: 1rem;
      height: 100%;
    }
  }

  .uncategorizedDragArea {
    height: 100%;
  }
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
  //max-height: calc(var(--app-height) / 2);
  overflow-y: auto;
  scrollbar-color: var(--color-primary) var(--color-gray);
  scrollbar-width: thin;
  padding: 0 0.75rem;
}

.drag-header::v-deep(.column) {
  .el-card,
  .item {
    height: 100%;
  }
}

.column {
  max-width: 25%;
  min-width: 10rem;
}

.column::v-deep(.el-card) {
  height: unset;
}

.column::v-deep(.item) {
  height: unset;
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
  color: var(--color-highlight-dark);
  border-color: var(--color-highlight-dark);
  cursor: pointer;
}

.el-card::v-deep(.el-card__body) {
  padding: 14px;
}
</style>
