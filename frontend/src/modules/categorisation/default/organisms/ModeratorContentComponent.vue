<template>
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
  <Expand v-for="(item, key) in orderGroupContent" :key="key">
    <template v-slot:title>
      {{ key.toUpperCase() }}
    </template>
    <template v-slot:icons v-if="key !== 'undefined'">
      <span class="icon" v-on:click="addSelectedToCategory(item.category.id)">
        <font-awesome-icon icon="plus" />
      </span>
      <span class="icon" v-on:click="editCategory(item.category.id)">
        <font-awesome-icon icon="pen" />
      </span>
      <span class="icon" v-on:click="deleteCategory(item.category.id)">
        <font-awesome-icon icon="trash" />
      </span>
    </template>
    <template v-slot:content>
      <main class="categorisation__content">
        <IdeaCard
          :idea="idea"
          v-for="(idea, index) in filterIdeas(item.ideas, item.displayCount)"
          :key="index"
          :is-selectable="true"
          v-model:is-selected="ideasSelection[idea.id]"
          @ideaDeleted="getIdeas"
        />
        <span role="button"
          v-if="item.ideas.length > item.displayCount"
          v-on:click="item.displayCount = 1000"
        >
          <font-awesome-icon icon="ellipsis-h" />
        </span>
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
    :category-id="editCategoryId"
    @categoryCreated="getIdeas"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
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
import {registerRuntimeCompiler} from "vue";

@Options({
  components: {
    IdeaCard,
    Expand,
    AddItem,
    ModalCategoryCreate,
  },
})
export default class ModeratorContentComponent extends Vue {
  @Prop() readonly taskId!: string;
  showModalCategoryCreate = false;
  editCategoryId: string | null = null;
  task: Task | null = null;
  categories: Category[] = [];
  ideas: Idea[] = [];
  ideasSelection: { [name: string]: boolean } = {};
  orderGroupContent: {
    [name: string]: {
      ideas: Idea[];
      category: Category | null;
      displayCount: number;
    };
  } = {};
  newCategory = {
    keywords: '',
    description: '',
  };
  readonly interval = 10000;
  updateInterval!: any;

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getIdeas();
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
            IdeaSortOrderCategorisation,
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
    this.updateInterval = setInterval(this.getIntervalContent, this.interval);
  }

  unmounted(): void {
    clearInterval(this.updateInterval);
  }

  openModalCategoryCreate(): void {
    this.editCategoryId = null;
    this.showModalCategoryCreate = true;
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
    //display: table;

    > * {
      //display: table-cell;
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

.column {
  margin: 0.5rem;
}

.icon {
  margin-right: 0.5em;
}
</style>
