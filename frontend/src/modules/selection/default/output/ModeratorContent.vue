<template>
  <IdeaFilter
    :taskId="taskId"
    v-model="filter"
    @change="getCollapseContent(true)"
  />
  <div ref="data" class="media">
    <div class="media-left unselected">
      <el-scrollbar
        native
        :height="`calc(var(--app-height) - ${topContentPosition}px - 1rem)`"
      >
        <el-collapse v-model="openTabs">
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
            <div class="layout__columns">
              <draggable
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

            <div class="layout__columns">
              <draggable
                :key="SELECTION_KEY"
                :id="SELECTION_KEY"
                v-model="selection"
                draggable=".item"
                item-key="id"
                group="idea"
                @end="dragDone"
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
                    @ideaDeleted="getCollapseContent"
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
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
import * as selectionService from '@/services/selection-service';
import { EventType } from '@/types/enum/EventType';
import CollapseTitle from '@/components/moderator/atoms/CollapseTitle.vue';
import draggable from 'vuedraggable';
import { OrderGroup, OrderGroupList } from '@/types/api/OrderGroup';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { reloadCollapseContent } from '@/utils/collapse';
import IdeaSortOrder, { DefaultDisplayCount } from '@/types/enum/IdeaSortOrder';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import { IModeratorContent } from '@/types/ui/IModeratorContent';
import IdeaFilter, {
  defaultFilterData,
  FilterData,
} from '@/components/moderator/molecules/IdeaFilter.vue';

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
  ideas: Idea[] = [];
  selection: Idea[] = [];
  orderGroupContent: OrderGroupList = {};
  readonly intervalTime = 10000;
  interval!: any;
  openTabs: string[] = [];
  openTabsSelection: string[] = [SELECTION_KEY];
  displayCount = DefaultDisplayCount;
  isDragging = false;
  filter: FilterData = { ...defaultFilterData };

  topContentPosition = 80;

  loadTopPositions(): void {
    if (this.$refs.data) {
      this.topContentPosition = (
        this.$refs.data as HTMLElement
      ).getBoundingClientRect().top;
    }
  }

  @Watch('taskId', { immediate: true })
  reloadTaskSettings(): void {
    this.getCollapseContent(true);
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
      Object.keys(this.orderGroupContent),
      this.getIdeas,
      reloadTabState
    ).then((tabs) => (this.openTabs = tabs));
  }

  async getIdeas(): Promise<string[]> {
    if (this.isDragging) return Object.keys(this.orderGroupContent);

    if (this.taskId) {
      if (!this.task) await this.getTask();
      if (this.task && this.task.parameter.selectionId) {
        await selectionService
          .getIdeasForSelection(this.task.parameter.selectionId)
          .then((ideas) => {
            this.selection = ideaService.filterIdeas(
              ideas,
              this.filter.stateFilter,
              this.filter.textFilter
            );
          });
        const selectedIds: string[] = this.selection.map((idea) => idea.id);

        await viewService
          .getViewOrderGroups(
            this.task.topicId,
            this.task.parameter.input,
            this.filter.orderType,
            this.filter.orderAsc,
            null,
            EndpointAuthorisationType.MODERATOR,
            this.orderGroupContent,
            (this as any).$t,
            this.filter.stateFilter,
            this.filter.textFilter,
            (idea: Idea) => !selectedIds.includes(idea.id)
          )
          .then((result) => {
            const orderGroupName = (this as any).$t(
              'module.selection.default.moderatorContent.unselected'
            );
            let orderGroupContent: OrderGroupList = {};
            switch (this.filter.orderType) {
              case IdeaSortOrder.TIMESTAMP:
              case IdeaSortOrder.ALPHABETICAL:
              case IdeaSortOrder.ORDER:
                orderGroupContent[orderGroupName] = new OrderGroup(
                  result.ideas.filter((idea) => !selectedIds.includes(idea.id))
                );
                break;
              default:
                orderGroupContent = result.oderGroups;
            }
            Object.keys(orderGroupContent).forEach((key) => {
              if (key in this.orderGroupContent)
                orderGroupContent[key].displayCount =
                  this.orderGroupContent[key].displayCount;
            });
            this.orderGroupContent = orderGroupContent;
            this.ideas = result.ideas;
          });
      }
    }
    return Object.keys(this.orderGroupContent);
  }

  async mounted(): Promise<void> {
    this.startInterval();

    this.eventBus.off(EventType.CHANGE_SETTINGS);
    this.eventBus.on(EventType.CHANGE_SETTINGS, async (taskId) => {
      if (this.taskId === taskId) {
        await this.reloadData();
      }
    });
    setTimeout(() => {
      this.loadTopPositions();
    }, 500);
  }

  async reloadData(): Promise<void> {
    await this.getTask();
    await this.getCollapseContent();
  }

  startInterval(): void {
    this.interval = setInterval(this.reloadData, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
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
    await this.getCollapseContent();
  }
}
</script>

<style lang="scss" scoped>
.awesome-icon {
  margin-right: 0.5em;
}

.showMore {
  color: var(--color-purple-dark);
  border-color: var(--color-purple-dark);
  cursor: pointer;
}

.el-card::v-deep(.el-card__body) {
  padding: 14px;
}

.unselected {
  max-width: 50%;
  min-width: 10rem;
  padding-right: 1rem;
  border-right: 2px var(--color-primary) solid;
}
</style>
