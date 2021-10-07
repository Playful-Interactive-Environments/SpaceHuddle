<template>
  <FilterSection>
    <label for="orderType" class="heading heading--xs">{{
      $t('module.selection.default.moderatorContent.sortOrder')
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

      <div class="layout__4columns">
        <draggable
          :key="SELECTION_KEY"
          :id="SELECTION_KEY"
          v-model="selection"
          draggable=".item"
          item-key="id"
          group="idea"
          @end="dragDone"
        >
          <template v-slot:item="{ element, index }">
            <IdeaCard
              :key="element.id"
              v-if="index < displayCount"
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
      <div class="layout__4columns">
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
              :is-editable="false"
              class="item"
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
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import Expand from '@/components/shared/atoms/Expand.vue';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
import * as selectionService from '@/services/selection-service';
import { EventType } from '@/types/enum/EventType';
import CollapseTitle from '@/components/moderator/atoms/CollapseTitle.vue';
import FilterSection from '@/components/moderator/atoms/FilterSection.vue';
import draggable from 'vuedraggable';
import { OrderGroupList, SortOrderOption } from '@/types/api/OrderGroup';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

const SELECTION_KEY = 'selection';

@Options({
  components: {
    IdeaCard,
    Expand,
    CollapseTitle,
    FilterSection,
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
  displayCount = 3;
  isDragging = false;
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

    const oldKeys = Object.keys(this.orderGroupContent);
    if (this.taskId) {
      if (!this.task) await this.getTask();
      if (this.task) {
        await selectionService
          .getIdeasForSelection(this.task.parameter.selectionId)
          .then((ideas) => {
            this.selection = ideas;
          });
        const selectedIds: string[] = this.selection.map((idea) => idea.id);

        await ideaService
          .getOrderGroups(
            this.task.parameter.brainstormingTaskId,
            this.orderType,
            null,
            EndpointAuthorisationType.MODERATOR,
            this.orderGroupContent,
            (idea: Idea) => !selectedIds.includes(idea.id)
          )
          .then((result) => {
            this.orderGroupContent = result.oderGroups;
            this.ideas = result.ideas;
          });
      }
    }
    const newKeys = Object.keys(this.orderGroupContent);
    if (reloadTabState) this.openTabs = newKeys;
    else {
      const addedKeys = newKeys.filter((item) => oldKeys.indexOf(item) < 0);
      this.openTabs = this.openTabs.concat(addedKeys);
    }
  }

  async mounted(): Promise<void> {
    this.startIdeaInterval();

    this.eventBus.off(EventType.CHANGE_SETTINGS);
    this.eventBus.on(EventType.CHANGE_SETTINGS, async () => {
      await this.getTask();
      await this.getIdeas();
    });
  }

  startIdeaInterval(): void {
    this.interval = setInterval(this.getIdeas, this.intervalTime);
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
          [event.item.id]
        );
      } else if (event.from.id == SELECTION_KEY) {
        await selectionService.removeIdeasFromSelection(
          this.task.parameter.selectionId,
          [event.item.id]
        );
      }
    }
    this.isDragging = false;
    await this.getIdeas();
  }
}
</script>

<style lang="scss" scoped>
.icon {
  margin-right: 0.5em;
}
</style>
