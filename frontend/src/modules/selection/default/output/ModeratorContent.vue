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
          v-for="type in SortOrderOptions"
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
    <el-collapse-item key="selection" name="selection">
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
          key="selection"
          id="selection"
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
  <!--<Expand>
    <template v-slot:title>
      {{ $t('module.selection.default.moderatorContent.selection') }}
    </template>
    <template v-slot:icons>
      <span class="icon" v-on:click="addSelectedToSelection()">
        <font-awesome-icon icon="plus" />
      </span>
      <span class="icon" v-on:click="removeSelectedFromSelection()">
        <font-awesome-icon icon="minus" />
      </span>
    </template>
    <template v-slot:content>
      <main class="layout__4columns">
        <IdeaCard
          :idea="idea"
          v-for="(idea, index) in selection"
          :key="index"
          :is-selectable="true"
          :is-editable="false"
          v-model:is-selected="ideasSelectionRemove[idea.id]"
        />
      </main>
    </template>
  </Expand>-->
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

        <!--<IdeaCard
          :idea="idea"
          v-for="(idea, index) in item.ideas"
          :key="index"
          :is-editable="false"
          :is-selectable="true"
          v-model:is-selected="ideasSelectionAdd[idea.id]"
        />-->
      </div>
    </el-collapse-item>
  </el-collapse>

  <!--<Expand v-for="(item, key) in orderGroupContent" :key="key">
    <template v-slot:title>{{ key.toUpperCase() }}</template>
    <template v-slot:content>
      <main class="layout__4columns">
        <IdeaCard
          :idea="idea"
          v-for="(idea, index) in item"
          :key="index"
          :is-editable="false"
          :is-selectable="true"
          v-model:is-selected="ideasSelectionAdd[idea.id]"
        />
      </main>
    </template>
  </Expand>-->
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import * as ideaService from '@/services/idea-service';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import IdeaSortOrder, {
  IdeaSortOrderCategorisation,
} from '@/types/enum/IdeaSortOrder';
import Expand from '@/components/shared/atoms/Expand.vue';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
import * as selectionService from '@/services/selection-service';
import { EventType } from '@/types/enum/EventType';
import TaskType from '@/types/enum/TaskType';
import CollapseTitle from '@/components/moderator/atoms/CollapseTitle.vue';
import FilterSection from '@/components/moderator/atoms/FilterSection.vue';
import draggable from 'vuedraggable';
import { OrderGroupList } from '@/types/api/OrderGroup';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

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
  task: Task | null = null;
  ideas: Idea[] = [];
  selection: Idea[] = [];
  orderGroupContent: OrderGroupList = {};
  //ideasSelectionAdd: { [name: string]: boolean } = {};
  //ideasSelectionRemove: { [name: string]: boolean } = {};
  readonly intervalTime = 10000;
  interval!: any;
  orderType = this.SortOrderOptions[0].orderType;
  categoryTasks: Task[] = [];
  openTabs: string[] = [];
  openTabsSelection: string[] = ['selection'];
  displayCount = 3;
  isDragging = false;

  IdeaSortOrder = IdeaSortOrder;

  get SortOrderOptions(): { orderType: string; ref: Task | null }[] {
    const result: { orderType: string; ref: Task | null }[] = Object.keys(
      IdeaSortOrder
    ).map((orderType) => {
      return { orderType: orderType.toLowerCase(), ref: null };
    });
    if (this.categoryTasks) {
      this.categoryTasks.forEach((task) => {
        result.push({ orderType: IdeaSortOrderCategorisation, ref: task });
      });
    }
    return result;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getIdeas(true);
  }

  async getTask(): Promise<void> {
    if (this.taskId) {
      await taskService.getTaskById(this.taskId).then((task) => {
        this.task = task;
        taskService.getTaskList(task.topicId).then((tasks) => {
          this.categoryTasks = tasks.filter(
            (task) => task.taskType.toLowerCase() == TaskType.CATEGORISATION
          );
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
            /*ideas.forEach((ideaItem) => {
              if (!this.ideasSelectionRemove[ideaItem.id])
                this.ideasSelectionRemove[ideaItem.id] = false;
            });*/
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
        /*await ideaService
          .getIdeasForTask(
            this.task.parameter.brainstormingTaskId,
            this.orderType
          )
          .then((ideas) => {
            this.orderGroupContent = {};
            this.ideas = ideas;
            ideas
              .filter((idea) => !selectedIds.includes(idea.id))
              .forEach((ideaItem) => {
                //if (!this.ideasSelectionAdd[ideaItem.id])
                //  this.ideasSelectionAdd[ideaItem.id] = false;
                if (ideaItem.order) {
                  const orderGroup = this.orderGroupContent[ideaItem.order];
                  if (!orderGroup) {
                    let color = null;
                    if (ideaItem.category)
                      color = ideaItem.category.parameter.color;
                    this.orderGroupContent[ideaItem.order] = {
                      ideas: [ideaItem],
                      avatar: ideaItem.avatar,
                      color: color,
                      displayCount: 3,
                    };
                  } else {
                    orderGroup.ideas.push(ideaItem);
                  }
                }
              });
          });*/
      }
    }
    const newKeys = Object.keys(this.orderGroupContent);
    if (reloadTabState) this.openTabs = newKeys;
    else {
      const addedKeys = newKeys.filter((item) => oldKeys.indexOf(item) < 0);
      this.openTabs = this.openTabs.concat(addedKeys);
    }
  }

  /*async addSelectedToSelection(): Promise<void> {
    if (this.task && this.task.parameter && this.task.parameter.selectionId) {
      const selection: string[] = [];
      for (let [ideaId, isSelected] of Object.entries(this.ideasSelectionAdd)) {
        if (isSelected) {
          selection.push(ideaId);
          this.ideasSelectionAdd[ideaId] = false;
        }
      }
      if (selection.length > 0) {
        await selectionService
          .addIdeasToSelection(this.task.parameter.selectionId, selection)
          .then(() => {
            this.getIdeas();
          });
      } else {
        this.eventBus.emit(EventType.SHOW_SNACKBAR, {
          type: SnackbarType.ERROR,
          message: 'error.vuelidate.noSelection',
        });
      }
    }
  }

  async removeSelectedFromSelection(): Promise<void> {
    if (this.task && this.task.parameter && this.task.parameter.selectionId) {
      const selection: string[] = [];
      for (let [ideaId, isSelected] of Object.entries(
        this.ideasSelectionRemove
      )) {
        if (isSelected) {
          selection.push(ideaId);
          this.ideasSelectionRemove[ideaId] = false;
        }
      }
      if (selection.length > 0) {
        await selectionService
          .removeIdeasFromSelection(this.task.parameter.selectionId, selection)
          .then(() => {
            this.getIdeas();
          });
      } else {
        this.eventBus.emit(EventType.SHOW_SNACKBAR, {
          type: SnackbarType.ERROR,
          message: 'error.vuelidate.noSelection',
        });
      }
    }
  }*/

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
      if (event.to.id == 'selection') {
        await selectionService.addIdeasToSelection(
          this.task.parameter.selectionId,
          [event.item.id]
        );
      } else if (event.from.id == 'selection') {
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
.column {
  margin: 0.5rem;
}

.icon {
  margin-right: 0.5em;
}
</style>
