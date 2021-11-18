<template>
  <section v-if="ideas.length === 0" class="centered public-screen__error">
    <p>{{ $t('module.brainstorming.default.publicScreen.noIdeas') }}</p>
  </section>
  <section v-else class="public-screen__content fixHead">
    <el-container>
      <el-header class="columns is-mobile sticky-header">
        <div class="column" v-for="category in categories" :key="category.id">
          <CategoryCard
            :category="category"
            :ideas="categoryIdeas(category)"
            :isEditable="false"
          >
          </CategoryCard>
        </div>
      </el-header>
      <el-main class="columns is-mobile">
        <div class="column" v-for="category in categories" :key="category.id">
          <IdeaCard
            :idea="idea"
            v-for="(idea, index) in categoryIdeas(category)"
            :key="index"
            :is-selectable="false"
            :is-editable="false"
            :style="{ 'border-color': category.parameter.color }"
          />
        </div>
      </el-main>
    </el-container>
    <!--<el-collapse v-model="openTabs">
      <el-collapse-item
        v-for="(item, key) in orderGroupContent"
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
        <main class="layout__columns">
          <IdeaCard
            :idea="idea"
            v-for="(idea, index) in item.filteredIdeas"
            :key="index"
            :is-selectable="false"
            :is-editable="false"
          />
        </main>
      </el-collapse-item>
    </el-collapse>-->
  </section>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as ideaService from '@/services/idea-service';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import { IdeaSortOrderCategorisation } from '@/types/enum/IdeaSortOrder';
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';
import { Category } from '@/types/api/Category';
import { OrderGroupList } from '@/types/api/OrderGroup';
import CollapseTitle from '@/components/moderator/atoms/CollapseTitle.vue';
import { reloadCollapseContent } from '@/utils/collapse';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import CategoryCard from '@/modules/categorisation/default/molecules/CategoryCard.vue';
import * as categorisationService from '@/services/categorisation-service';

@Options({
  components: {
    CategoryCard,
    IdeaCard,
    CollapseTitle,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  task: Task | null = null;
  categories: Category[] = [];
  ideas: Idea[] = [];
  orderGroupContent: OrderGroupList = {};
  readonly intervalTime = 10000;
  interval!: any;
  openTabs: string[] = [];

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getCollapseContent(true);
  }

  async getTask(): Promise<void> {
    if (this.taskId) {
      await taskService
        .getTaskById(this.taskId, this.authHeaderTyp)
        .then((task) => {
          this.task = task;
        });
    }
  }

  categoryIdeas(category: Category): Idea[] {
    if (category.keywords in this.orderGroupContent)
      return this.orderGroupContent[category.keywords].ideas;
    return [];
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
    if (this.taskId) {
      if (!this.task) await this.getTask();
      await this.getCategories();

      if (
        this.task &&
        this.task.parameter &&
        this.task.parameter.dependencyTaskId
      ) {
        await ideaService
          .getOrderGroups(
            this.task.parameter.dependencyTaskId,
            IdeaSortOrderCategorisation,
            this.taskId,
            this.authHeaderTyp,
            this.orderGroupContent
          )
          .then((result) => {
            this.ideas = result.ideas;
            this.orderGroupContent = result.oderGroups;
          });
      }
    }
    return Object.keys(this.orderGroupContent);
  }

  async getCategories(): Promise<void> {
    if (this.taskId) {
      await categorisationService
        .getCategoriesForTask(this.taskId, this.authHeaderTyp)
        .then((categories) => {
          this.categories = categories;
        });
    }
  }

  async mounted(): Promise<void> {
    this.startIdeaInterval();
  }

  startIdeaInterval(): void {
    this.interval = setInterval(this.getCollapseContent, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }
}
</script>

<style lang="scss" scoped>
.fixHead {
  padding-right: 1rem;
  padding-bottom: 1rem;
  overflow-y: auto;
  height: 68vh;

  .sticky-header {
    position: sticky;
    top: 0;
  }
}

.el-main {
  overflow: unset;
}

.expand {
  &__header {
    color: white;
  }
}

.expand > :first-child {
  color: white;
}

.sticky-header::v-deep {
  background-color: var(--color-background-gray);

  .column {
    background-color: var(--color-background-gray);
    .el-card,
    .item {
      height: 100%;
    }
  }
}

.column::v-deep {
  max-width: 16rem;
  min-width: 10rem;

  .el-card,
  .item {
    height: unset;
  }
}
</style>
