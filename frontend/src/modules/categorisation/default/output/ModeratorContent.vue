<template>
  <header class="categorisation__header">
    <label for="orderType" class="heading heading--xs">{{
      $t('module.categorisation.default.moderatorContent.sortOrder')
    }}</label>
    <select
      v-model="orderType"
      id="orderType"
      class="select select--fullwidth"
      @change="getIdeas"
    >
      <option v-for="type in SortOrderOptions" :key="type" :value="type">
        {{ $t(`enum.ideaSortOrder.${IdeaSortOrder[type]}`) }}
      </option>
    </select>
  </header>
  <!--<header class="categorisation__header columns">
    <label for="categoryName" class="column is-one-quarter heading heading--xs">
      {{ $t('module.categorisation.default.moderatorContent.categoryName') }}
    </label>
    <input
      v-model="newCategory.keywords"
      id="categoryName"
      class="column input"
      :placeholder="
        $t(
          'module.categorisation.default.moderatorContent.categoryNamePlaceholder'
        )
      "
    />
    <button
      class="column is-one-fifth btn btn--blue"
      @click.prevent="submitCategory"
    >
      <font-awesome-icon icon="plus" />
    </button>
  </header>-->
  <div class="columns is-multiline is-mobile">
    <draggable
      class="column"
      v-for="(orderGroup, orderGroupKey) in orderGroupContent"
      :key="orderGroupKey"
      :id="orderGroup.category ? orderGroup.category.id : null"
      v-model="orderGroup.ideas"
      draggable=".item"
      item-key="id"
      group="idea"
      @end="dragDone"
    >
      <template v-slot:header>
        <CategoryCard :category="orderGroup.category" :ideas="orderGroup.ideas">
        </CategoryCard>
      </template>
      <template v-slot:item>
        <span></span>
      </template>
    </draggable>
  </div>
  <Expand
    v-for="(orderGroup, orderGroupKey) in orderGroupContent"
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
      <main class="categorisation__content">
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
              v-if="index < orderGroup.displayCount"
              :id="element.id"
              :idea="element"
              :is-selectable="true"
              :key="element.id"
              v-model:is-selected="ideasSelection[element.id]"
              @ideaDeleted="getIdeas"
              class="item"
            />
          </template>
        </draggable>
      </main>
    </template>
  </Expand>
  <AddItem
    :text="$t('module.categorisation.default.moderatorContent.add')"
    @addNew="openModalCategoryCreate"
  />
  <ModalCategoryCreate
    v-if="task"
    v-model:show-modal="showModalCategoryCreate"
    :task-id="task.id"
    v-model:category-id="editCategoryId"
    @categoryCreated="getIdeas"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import draggable from 'vuedraggable';
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';
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
import ModalCategoryCreate from '@/modules/categorisation/default/molecules/ModalCategoryCreate.vue';
import CategoryCard from '@/modules/categorisation/default/molecules/CategoryCard.vue';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';

interface CategoryContent {
  [name: string]: {
    ideas: Idea[];
    category: Category | null;
    displayCount: number;
  };
}

@Options({
  components: {
    IdeaCard,
    Expand,
    AddItem,
    ModalCategoryCreate,
    CategoryCard,
    draggable,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue {
  @Prop() readonly taskId!: string;
  showModalCategoryCreate = false;
  editCategoryId: string | null = null;
  task: Task | null = null;
  categories: Category[] = [];
  ideas: Idea[] = [];
  ideasSelection: { [name: string]: boolean } = {};
  orderGroupContent: CategoryContent = {};
  newCategory = {
    keywords: '',
    description: '',
  };
  readonly intervalTime = 10000;
  interval!: any;

  IdeaSortOrder = IdeaSortOrder;
  orderType = this.SortOrderOptions[0];

  get categoryContentList(): CategoryContent {
    return Object.fromEntries(
      Object.entries(this.orderGroupContent).filter(
        ([key, v]) => key != 'undefined'
      )
    );
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getIdeas();
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

  async getIdeas(): Promise<void> {
    if (this.taskId) {
      if (!this.task) await this.getTask();
      await this.getCategories();
      let displayCount = 3;
      if ('undefined' in this.orderGroupContent)
        displayCount = this.orderGroupContent['undefined'].displayCount;
      const orderGroupContent = {
        undefined: { ideas: [], category: null, displayCount: displayCount },
      };
      this.categories.forEach((category) => {
        displayCount = 3;
        if (category.keywords in this.orderGroupContent)
          displayCount = this.orderGroupContent[category.keywords].displayCount;
        orderGroupContent[category.keywords] = {
          ideas: [],
          category: category,
          displayCount: displayCount,
        };
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
                  orderGroupContent[ideaItem.order] = {
                    ideas: [ideaItem],
                    category: null,
                    displayCount: 3,
                  };
                } else {
                  orderGroup.ideas.push(ideaItem);
                }
              }
            });
          });
      }

      this.orderGroupContent = orderGroupContent;
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
    this.showModalCategoryCreate = true;
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

  openModalCategoryCreate(): void {
    this.editCategoryId = null;
    this.showModalCategoryCreate = true;
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
.categorisation {
  &__header {
    padding: 0.5rem 1rem;
    margin-bottom: 1rem;
    border-radius: var(--border-radius);
    background-color: var(--color-darkblue);
    width: 100%;
    display: table;

    > * {
      display: table-cell;
    }

    label {
      width: 20%;
    }

    .heading {
      color: white;
    }
  }

  &__content {
    width: 100%;
    column-width: 22vw;
    column-gap: 1rem;
  }
}

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
