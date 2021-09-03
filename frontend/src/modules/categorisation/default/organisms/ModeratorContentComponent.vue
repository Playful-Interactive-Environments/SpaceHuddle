<template>
  <header class="categorisation__header columns">
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
      {{ $t('module.categorisation.default.moderatorContent.submit') }}
    </button>
  </header>
  <main class="categorisation__content">
    <IdeaCard
      :idea="category"
      v-for="(category, index) in categories"
      :key="index"
      @ideaDeleted="getCategories"
    />
  </main>
</template>

<script lang="ts">
import {Options, Vue} from 'vue-class-component';
import {Prop, Watch} from 'vue-property-decorator';
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';
import * as categorisationService from '@/services/categorisation-service';
import {Category} from '@/types/api/Category';
import EndpointAuthorisationType from "@/types/enum/EndpointAuthorisationType";

@Options({
  components: {
    IdeaCard,
  },
})
export default class ModeratorContentComponent extends Vue {
  @Prop() readonly taskId!: string;
  categories: Category[] = [];
  newCategory = {
    keywords: '',
    description: '',
  };
  readonly interval = 3000;
  updateInterval!: any;

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getCategories();
  }

  async getCategories(): Promise<void> {
    if (this.taskId) {
      await categorisationService
        .getCategoriesForTask(this.taskId, EndpointAuthorisationType.MODERATOR)
        .then((categories) => {
          this.categories = categories;
        });
    }
  }

  async submitCategory(): Promise<void> {
    if (this.taskId) {
      await categorisationService
        .postCategory(this.taskId, this.newCategory)
        .then(() => {
          this.getCategories();
        });
    }
  }

  async mounted(): Promise<void> {
    this.startIdeaInterval();
  }

  startIdeaInterval(): void {
    this.updateInterval = setInterval(this.getCategories, this.interval);
  }

  unmounted(): void {
    clearInterval(this.updateInterval);
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
</style>
