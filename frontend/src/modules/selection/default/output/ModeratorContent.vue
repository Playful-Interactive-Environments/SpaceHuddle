<template>
  <IdeaFilter :taskId="taskId" v-model="filter" @change="refreshInput(true)" />
  <div ref="data" class="media">
    <div class="media-left unselected">
      <el-scrollbar
        native
        :height="`calc(var(--app-height) - ${topContentPosition}px - 1rem)`"
      >
        <el-collapse
          v-model="openTabs"
          :style="{
            height: `calc(var(--app-height) - ${topContentPosition}px - 1rem)`,
          }"
        >
          <el-collapse-item
            v-for="(item, key) in orderGroupContent"
            :key="key"
            :name="key"
          >
            <template #title>
              <CollapseTitle
                :text="key"
                :avatar="item.avatar"
                :color="item.color"
              >
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
            <div class="unselectedDragArea">
              <draggable
                class="layout__columns unselectedDragArea"
                :id="key"
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
                    :isDraggable="true"
                    v-model:collapseIdeas="filter.collapseIdeas"
                    class="item"
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
      <el-scrollbar
        native
        :height="`calc(var(--app-height) - ${topContentPosition}px - 1rem)`"
      >
        <el-collapse v-model="openTabsSelection">
          <el-collapse-item :key="SELECTION_KEY" :name="SELECTION_KEY">
            <template #title>
              <CollapseTitle
                :text="
                  $t('module.selection.default.moderatorContent.selection')
                "
              >
                <span
                  role="button"
                  class="awesome-icon"
                  v-if="selection.length > displayCount"
                  v-on:click="displayCount = 1000"
                >
                  <font-awesome-icon icon="ellipsis-h" />
                </span>
              </CollapseTitle>
            </template>

            <div
              ref="draggableStart"
              :style="{
                'min-height': `calc(var(--app-height) - ${topDraggableStart}px - 2.1rem)`,
              }"
            >
              <draggable
                class="layout__columns"
                :key="SELECTION_KEY"
                :id="SELECTION_KEY"
                v-model="selection"
                draggable=".item"
                item-key="id"
                group="idea"
                @end="dragDone"
                :style="{
                  'min-height': `calc(var(--app-height) - ${topDraggableStart}px - 2.1rem)`,
                }"
              >
                <template v-slot:header>
                  <AddItem
                    :text="$t('module.selection.default.moderatorContent.add')"
                    :isColumn="true"
                    :is-clickable="false"
                    v-if="selection.length === 0"
                  />
                </template>
                <template v-slot:item="{ element, index }">
                  <IdeaCard
                    :key="element.id"
                    v-if="index < displayCount"
                    :id="element.id"
                    :idea="element"
                    :is-selectable="false"
                    :isDraggable="true"
                    v-model:collapseIdeas="filter.collapseIdeas"
                    class="item"
                    @ideaDeleted="refreshInAndOutput"
                  />
                </template>
                <template v-slot:footer>
                  <AddItem
                    v-if="selection.length > displayCount"
                    :text="
                      $t('module.selection.default.moderatorContent.displayAll')
                    "
                    :isColumn="false"
                    @addNew="displayCount = 1000"
                    class="showMore"
                  />
                </template>
              </draggable>
            </div>
          </el-collapse-item>
        </el-collapse>
      </el-scrollbar>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import * as ideaService from '@/services/idea-service';
import * as viewService from '@/services/view-service';
import * as cashService from '@/services/cash-service';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
import * as selectionService from '@/services/selection-service';
import * as selectService from '@/services/selection-service';
import { EventType } from '@/types/enum/EventType';
import CollapseTitle from '@/components/moderator/atoms/CollapseTitle.vue';
import draggable from 'vuedraggable';
import { OrderGroup, OrderGroupList } from '@/types/api/OrderGroup';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { reloadCollapseTabs } from '@/utils/collapse';
import IdeaSortOrder, { DefaultDisplayCount } from '@/types/enum/IdeaSortOrder';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import { IModeratorContent } from '@/types/ui/IModeratorContent';
import IdeaFilter, {
  defaultFilterData,
  FilterData,
} from '@/components/moderator/molecules/IdeaFilter.vue';
import { View } from '@/types/api/View';

const SELECTION_KEY = 'selection';

@Options({
  components: {
    IdeaCard,
    CollapseTitle,
    AddItem,
    draggable,
    IdeaFilter,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue implements IModeratorContent {
  @Prop() readonly taskId!: string;
  readonly SELECTION_KEY = SELECTION_KEY;

  task: Task | null = null;
  selectionId: string | null = null;
  ideas: Idea[] = [];
  selection: Idea[] = [];
  orderGroupContent: OrderGroupList = {};
  openTabs: string[] = [];
  openTabsSelection: string[] = [SELECTION_KEY];
  displayCount = DefaultDisplayCount;
  isDragging = false;
  filter: FilterData = { ...defaultFilterData };

  topContentPosition = 80;
  topDraggableStart = 100;

  inputCash!: cashService.SimplifiedCashEntry<Idea[]>;
  @Watch('taskId', { immediate: true })
  reloadTaskSettings(): void {
    this.deregisterAll();
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.MODERATOR,
      60 * 60
    );
    this.inputCash = viewService.registerGetInputIdeas(
      this.taskId,
      this.filter.orderType,
      null,
      this.updateInputIdeas,
      EndpointAuthorisationType.MODERATOR,
      20
    );
  }

  loadTopPositions(): void {
    if (this.$refs.data) {
      this.topContentPosition = (
        this.$refs.data as HTMLElement
      ).getBoundingClientRect().top;
    }
    if (this.$refs.draggableStart) {
      this.topDraggableStart = (
        this.$refs.draggableStart as HTMLElement
      ).getBoundingClientRect().top;
    }
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
    this.selectionId = this.task?.parameter?.selectionId;
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

  selectionCash!: cashService.SimplifiedCashEntry<Idea[]>;
  @Watch('selectionId', { immediate: true })
  onSelectionIdChanged(): void {
    if (this.selectionId)
      this.selectionCash = selectService.registerGetIdeasForSelection(
        this.selectionId,
        this.updateSelectedIdeas,
        EndpointAuthorisationType.MODERATOR,
        20
      );
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

  reloadTabState = true;
  refreshSelection(reloadTabState = false): void {
    this.reloadTabState = reloadTabState;
    if (this.selectionCash) this.selectionCash.refreshData();
  }

  refreshInput(reloadTabState = false): void {
    this.reloadTabState = reloadTabState;
    if (this.inputCash) this.inputCash.refreshData();
  }

  refreshInAndOutput(reloadTabState = false): void {
    this.reloadTabState = reloadTabState;
    if (this.selectionCash) this.selectionCash.refreshData();
    if (this.inputCash) this.inputCash.refreshData();
  }

  updateSelectedIdeas(ideas: Idea[]): void {
    this.selection = ideaService.filterIdeas(
      ideas,
      this.filter.stateFilter,
      this.filter.textFilter
    );
    this.updateIdeas();
  }

  updateIdeas(): void {
    const selectedIds: string[] = this.selection.map((idea) => idea.id);
    const ideas = ideaService.getOrderGroups(
      viewService.customizeView(
        this.inputIdeas,
        this.views,
        (this as any).$t,
        this.filter.stateFilter,
        this.filter.textFilter,
        this.task ? this.task.parameter.input.length : 1
      ),
      this.filter.orderAsc,
      this.orderGroupContent,
      (idea: Idea) => !selectedIds.includes(idea.id)
    );
    const orderGroupName = (this as any).$t(
      'module.selection.default.moderatorContent.unselected'
    );
    let orderGroupContent: OrderGroupList = {};
    switch (this.filter.orderType) {
      case IdeaSortOrder.TIMESTAMP:
      case IdeaSortOrder.ALPHABETICAL:
      case IdeaSortOrder.ORDER:
        orderGroupContent[orderGroupName] = new OrderGroup(
          ideas.ideas.filter((idea) => !selectedIds.includes(idea.id))
        );
        break;
      default:
        orderGroupContent = ideas.oderGroups;
    }
    Object.keys(orderGroupContent).forEach((key) => {
      if (key in this.orderGroupContent)
        orderGroupContent[key].displayCount =
          this.orderGroupContent[key].displayCount;
    });
    const oldTabs = Object.keys(this.orderGroupContent);
    this.orderGroupContent = orderGroupContent;
    this.ideas = ideas.ideas;

    reloadCollapseTabs(
      this.openTabs,
      oldTabs,
      Object.keys(this.orderGroupContent),
      this.reloadTabState
    ).then((tabs) => (this.openTabs = tabs));
    this.reloadTabState = false;
  }

  async mounted(): Promise<void> {
    this.eventBus.off(EventType.CHANGE_SETTINGS);
    this.eventBus.on(EventType.CHANGE_SETTINGS, async (taskId) => {
      if (this.taskId === taskId) {
        this.refreshInput();
      }
    });
    setTimeout(() => {
      this.loadTopPositions();
    }, 500);
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateViews);
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateInputIdeas);
    cashService.deregisterAllGet(this.updateSelectedIdeas);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  /* eslint-disable @typescript-eslint/explicit-module-boundary-types*/
  async dragDone(event: any): Promise<void> {
    this.isDragging = true;
    if (this.task && this.task.parameter && this.task.parameter.selectionId) {
      if (event.to.id == SELECTION_KEY) {
        await selectionService.addIdeasToSelection(
          this.task.parameter.selectionId,
          this.selection.map((idea) => idea.id) // [event.item.id]
        );
      } else if (event.from.id == SELECTION_KEY) {
        await selectionService.removeIdeasFromSelection(
          this.task.parameter.selectionId,
          [event.item.id]
        );
      }
    }
    this.isDragging = false;
    this.refreshSelection();
  }
}
</script>

<style lang="scss" scoped>
.awesome-icon {
  margin-right: 0.5em;
}

.showMore {
  color: var(--color-highlight-dark);
  border-color: var(--color-highlight-dark);
  cursor: pointer;
}

.el-card::v-deep(.el-card__body) {
  padding: 14px;
}

.el-collapse-item::v-deep(.el-collapse-item__content) {
  padding-bottom: 1rem;
}

.unselected {
  max-width: 50%;
  min-width: 10rem;
  padding-right: 1rem;
  border-right: 2px var(--color-primary) solid;

  .el-collapse {
    display: flex;
    flex-direction: column;
  }

  .el-collapse-item {
    flex-grow: 1;
  }

  .el-collapse-item::v-deep(.el-collapse-item__wrap) {
    height: calc(100% - 3rem);
  }

  .el-collapse-item::v-deep(.el-collapse-item__content) {
    height: 100%;
  }

  .unselectedDragArea {
    height: 100%;
  }
}
</style>
