<template>
  <FilterSection>
    <label for="orderType" class="heading heading--xs">{{
      $t('module.selection.default.moderatorContent.sortOrder')
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
  <el-collapse v-model="openTabsSelection">
    <el-collapse-item :key="SELECTION_KEY" :name="SELECTION_KEY">
      <template #title>
        <CollapseTitle
          :text="$t('module.selection.default.moderatorContent.selection')"
        >
          <span
            role="button"
            class="icon"
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
              class="item"
              @ideaDeleted="getCollapseContent"
            />
          </template>
          <template v-slot:footer>
            <AddItem
              v-if="selection.length > displayCount"
              :text="$t('module.selection.default.moderatorContent.displayAll')"
              :isColumn="false"
              @addNew="displayCount = 1000"
              class="showMore"
            />
          </template>
        </draggable>
      </div>
    </el-collapse-item>
  </el-collapse>
  <el-collapse v-model="openTabs">
    <el-collapse-item
      v-for="(item, key) in orderGroupContent"
      :key="key"
      :name="key"
    >
      <template #title>
        <CollapseTitle :text="key" :avatar="item.avatar" :color="item.color">
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
              class="item"
              @ideaDeleted="getCollapseContent"
            />
          </template>
          <template v-slot:footer>
            <AddItem
              v-if="item.ideas.length > item.displayCount"
              :text="$t('module.selection.default.moderatorContent.displayAll')"
              :isColumn="false"
              v-on:click="item.displayCount = 1000"
              class="showMore"
            />
          </template>
        </draggable>
      </div>
    </el-collapse-item>
  </el-collapse>
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
import FilterSection from '@/components/moderator/atoms/FilterSection.vue';
import draggable from 'vuedraggable';
import {
  OrderGroup,
  OrderGroupList,
  SortOrderOption,
} from '@/types/api/OrderGroup';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { reloadCollapseContent } from '@/utils/collapse';
import IdeaSortOrder, {
  DefaultIdeaSortOrder,
  DefaultDisplayCount,
} from '@/types/enum/IdeaSortOrder';
import AddItem from '@/components/moderator/atoms/AddItem.vue';

const SELECTION_KEY = 'selection';

@Options({
  components: {
    IdeaCard,
    CollapseTitle,
    FilterSection,
    AddItem,
    draggable,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue {
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
  sortOrderOptions: SortOrderOption[] = [];
  orderType: string = DefaultIdeaSortOrder;

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getCollapseContent(true);
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
      Object.keys(this.orderGroupContent),
      this.getIdeas,
      reloadTabState
    ).then((tabs) => (this.openTabs = tabs));
  }

  async getIdeas(): Promise<string[]> {
    if (this.isDragging) return Object.keys(this.orderGroupContent);

    if (this.taskId) {
      if (!this.task) await this.getTask();
      if (this.task) {
        await selectionService
          .getIdeasForSelection(this.task.parameter.selectionId)
          .then((ideas) => {
            this.selection = ideas;
          });
        const selectedIds: string[] = this.selection.map((idea) => idea.id);

        await viewService
          .getOrderGroups(
            this.task.parameter.input,
            this.orderType,
            null,
            EndpointAuthorisationType.MODERATOR,
            this.orderGroupContent,
            (idea: Idea) => !selectedIds.includes(idea.id)
          )
          .then((result) => {
            const orderGroupName = (this as any).$t(
              'module.selection.default.moderatorContent.unselected'
            );
            let orderGroupContent: OrderGroupList = {};
            switch (this.orderType) {
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
.icon {
  margin-right: 0.5em;
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
