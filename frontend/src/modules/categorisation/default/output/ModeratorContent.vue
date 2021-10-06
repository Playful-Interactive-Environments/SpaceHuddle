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
      <option v-for="type in SortOrderOptions" :key="type" :value="type">
        {{ $t(`enum.ideaSortOrder.${IdeaSortOrder[type]}`) }}
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
              :is-selectable="true"
              v-model:is-selected="ideasSelection[element.id]"
              @ideaDeleted="getIdeas"
              :is-editable="false"
              class="item"
            />
          </template>
        </draggable>
      </div>
    </el-collapse-item>
  </el-collapse>

  <!--
  <Expand
    v-for="(orderGroup, orderGroupKey) in orderGroupContentSelection"
    :key="orderGroupKey"
  >
    <template v-slot:title>
      <span
        v-if="orderGroup.category"
        :style="{ color: orderGroup.category.parameter.color }"
      >
        {{ orderGroupKey.toUpperCase() }}
      </span>
      <span v-else>{{ orderGroupKey.toUpperCase() }}</span>
    </template>
    <template v-slot:icons>
      <span
        role="button"
        class="icon"
        v-if="orderGroup.ideas.length > orderGroup.displayCount"
        v-on:click="orderGroup.displayCount = 1000"
      >
        <font-awesome-icon icon="ellipsis-h" />
      </span>
      <span v-if="orderGroupKey !== 'undefined'">
        <span
          class="icon"
          v-on:click="addSelectedToCategory(orderGroup.category.id)"
        >
          <font-awesome-icon icon="plus" />
        </span>
        <span class="icon" v-on:click="editCategory(orderGroup.category.id)">
          <font-awesome-icon icon="pen" />
        </span>
        <span class="icon" v-on:click="deleteCategory(orderGroup.category.id)">
          <font-awesome-icon icon="trash" />
        </span>
      </span>
    </template>
    <template v-slot:content>
      <main class="layout__4columns">
        <draggable
          :id="orderGroup.category ? orderGroup.category.id : null"
          v-model="orderGroup.ideas"
          draggable=".item"
          item-key="id"
          group="idea"
          @end="dragDone"
        >
          <template v-slot:item="{ element, index }">
            <IdeaCard
              :key="element.id"
              v-if="index < orderGroup.displayCount"
              :id="element.id"
              :idea="element"
              :is-selectable="true"
              v-model:is-selected="ideasSelection[element.id]"
              @ideaDeleted="getIdeas"
              :is-editable="false"
              class="item"
            />
          </template>
        </draggable>
      </main>
    </template>
  </Expand>
  -->
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
import { EventType } from '@/types/enum/EventType';
import SnackbarType from '@/types/enum/SnackbarType';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import CategorySettings from '@/modules/categorisation/default/molecules/CategorySettings.vue';
import CategoryCard from '@/modules/categorisation/default/molecules/CategoryCard.vue';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import CollapseTitle from '@/components/moderator/atoms/CollapseTitle.vue';
import FilterSection from '@/components/moderator/atoms/FilterSection.vue';

class CategoryContent {
  ideas: Idea[];
  category: Category | null;
  displayCount: number;

  constructor(
    category: Category | null = null,
    ideas: Idea[] = [],
    displayCount = 3
  ) {
    this.ideas = ideas;
    this.category = category;
    this.displayCount = displayCount;
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
  ideasSelection: { [name: string]: boolean } = {};
  orderGroupContentCards: CategoryContentList = {};
  orderGroupContentSelection: CategoryContentList = {};
  openTabs: string[] = [];

  newCategory = {
    keywords: '',
    description: '',
  };
  readonly intervalTime = 10000;
  interval!: any;

  IdeaSortOrder = IdeaSortOrder;
  orderType = this.SortOrderOptions[0];

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getIdeas(true);
  }

  get SortOrderOptions(): Array<keyof typeof IdeaSortOrder> {
    return Object.keys(IdeaSortOrder) as Array<keyof typeof IdeaSortOrder>;
  }

  filterIdeas(ideas: Idea[], count: number): Idea[] {
    return ideas.slice(0, count);
  }

  async getTask(): Promise<void> {
    if (this.taskId) {
      await taskService.getTaskById(this.taskId).then((task) => {
        this.task = task;
      });
    }
  }

  async getIdeas(reloadTabState = false): Promise<void> {
    const oldKeys = Object.keys(this.orderGroupContentSelection);
    const filter = (
      list: CategoryContentList,
      getUndefined = true
    ): CategoryContentList => {
      return Object.fromEntries(
        Object.entries(list).filter(([key, v]) => {
          if (!getUndefined) return key != 'undefined';
          return key == 'undefined';
        })
      );
    };

    if (this.taskId) {
      if (!this.task) await this.getTask();
      await this.getCategories();
      let displayCount = 3;
      if ('undefined' in this.orderGroupContentSelection)
        displayCount =
          this.orderGroupContentSelection['undefined'].displayCount;
      const orderGroupContent: CategoryContentList = {
        undefined: new CategoryContent(null, [], displayCount),
      };
      this.categories.forEach((category) => {
        displayCount = 3;
        if (category.keywords in this.orderGroupContentCards)
          displayCount =
            this.orderGroupContentCards[category.keywords].displayCount;
        orderGroupContent[category.keywords] = new CategoryContent(category, [], displayCount);
      });

      if (this.task) {
        await ideaService
          .getIdeasForTask(
            this.task.parameter.brainstormingTaskId,
            `[${IdeaSortOrderCategorisation},${this.orderType}]`,
            this.taskId
          )
          .then((ideas) => {
            this.ideas = ideas;
            ideas.forEach((ideaItem) => {
              if (!this.ideasSelection[ideaItem.id])
                this.ideasSelection[ideaItem.id] = false;
              if (!ideaItem.order) ideaItem.order = 'undefined';
              if (ideaItem.order) {
                const orderGroup = orderGroupContent[ideaItem.order];
                if (!orderGroup) {
                  orderGroupContent[ideaItem.order] = new CategoryContent(null, [ideaItem]);
                } else {
                  orderGroup.ideas.push(ideaItem);
                }
              }
            });
          });
      }

      this.orderGroupContentCards = filter(orderGroupContent, false);
      this.orderGroupContentSelection = filter(orderGroupContent, true);
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

  async getIntervalContent(): Promise<void> {
    await this.getIdeas();
  }

  async submitCategory(): Promise<void> {
    if (this.taskId) {
      await categorisationService
        .postCategory(this.taskId, this.newCategory)
        .then(() => {
          this.getIdeas();
        });
    }
  }

  async addSelectedToCategory(categoryId: string): Promise<void> {
    const selection: string[] = [];
    for (let [ideaId, isSelected] of Object.entries(this.ideasSelection)) {
      if (isSelected) {
        selection.push(ideaId);
        this.ideasSelection[ideaId] = false;
      }
    }
    if (selection.length > 0) {
      await categorisationService
        .addIdeasToCategory(categoryId, selection)
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

  async editCategory(categoryId: string): Promise<void> {
    this.editCategoryId = categoryId;
    this.showCategorySettings = true;
  }

  async deleteCategory(categoryId: string): Promise<void> {
    await categorisationService.deleteCategory(categoryId).then(() => {
      this.getIdeas();
    });
  }

  async mounted(): Promise<void> {
    this.startIdeaInterval();
  }

  startIdeaInterval(): void {
    this.interval = setInterval(this.getIntervalContent, this.intervalTime);
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
    if (event.to.id) {
      await categorisationService.addIdeasToCategory(event.to.id, [
        event.item.id,
      ]);
    } else {
      await categorisationService.removeIdeasFromCategory(event.from.id, [
        event.item.id,
      ]);
    }
  }
}
</script>

<style lang="scss" scoped>
.item {
  cursor: grab;
}

.column {
  margin: 0.5rem;
}

.icon {
  margin-right: 0.5em;
}
</style>
