<template>
  <div class="item">
    <el-badge :value="ideas.length" class="item">
      <el-card
        :style="{
          'background-color': categoryColor,
          color: 'white',
        }"
        :body-style="{
          'text-align': 'center',
        }"
        v-on:click="displayDetails = true"
      >
        <h2 class="heading heading--regular">
          {{ categoryName }}
        </h2>
        <el-tooltip placement="top">
          <template #content>
            <div style="max-width: 50vw">
              {{ categoryDescription }}
            </div>
          </template>
          <p
            style="
              overflow: hidden;
              white-space: nowrap;
              text-overflow: ellipsis;
            "
          >
            {{ categoryDescription }}
          </p>
        </el-tooltip>
      </el-card>
    </el-badge>
  </div>
  <el-drawer
    v-model="displayDetails"
    modal-class="darkblue"
    :lock-scroll="false"
  >
    <template v-slot:title>
      <h2 class="heading heading--regular" :style="{ color: categoryColor }">
        {{ categoryName }}
      </h2>
      <span v-if="category" :style="{ color: categoryColor }">
        <span class="icon" v-on:click="editCategory()">
          <font-awesome-icon icon="pen" />
        </span>
        <span class="icon" v-on:click="deleteCategory()">
          <font-awesome-icon icon="trash" />
        </span>
      </span>
    </template>
    <main
      class="categorisation__content"
      :style="{
        'background-color': categoryColor,
      }"
    >
      <IdeaCard
        v-for="idea in ideas"
        :key="idea.id"
        :id="idea.id"
        :idea="idea"
        :is-selectable="false"
        :is-deletable="category != null"
        :custom-delete="true"
        @ideaDeleted="removeIdea($event)"
        class="item"
        style="height: unset"
      />
    </main>
  </el-drawer>
  <ModalCategoryCreate
    v-if="category"
    v-model:show-modal="displayEditCategory"
    v-model:category-id="category.id"
    @categoryCreated="updateCategory($event)"
  />
</template>

<script lang="ts">
import { Prop } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import { Category } from '@/types/api/Category';
import { Idea } from '@/types/api/Idea';
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';
import * as categorisationService from '@/services/categorisation-service';
import ModalCategoryCreate from '@/modules/categorisation/default/molecules/ModalCategoryCreate.vue';

@Options({
  components: {
    IdeaCard,
    ModalCategoryCreate,
  },
  emits: ['categoryChanged'],
})
export default class CategoryCard extends Vue {
  @Prop() category!: Category;
  @Prop() ideas!: Idea[];
  @Prop({ default: false }) modelValue!: boolean;

  displayDetails = false;
  displayEditCategory = false;

  get categoryName(): string {
    if (this.category) return this.category.keywords;
    return 'undefined';
  }

  get categoryDescription(): string {
    if (this.category) return this.category.description;
    return '';
  }

  get categoryColor(): string {
    if (this.category) return this.category.parameter.color;
    return 'var(--color-darkblue)';
  }

  updateCategory(event: Category): void {
    this.category.keywords = event.keywords;
    this.category.description = event.description;
    this.category.parameter = event.parameter;
    this.$emit('categoryChanged');
  }

  removeIdea(event: string): void {
    categorisationService
      .removeIdeasFromCategory(this.category.id, [event])
      .then((done) => {
        if (done) {
          const index = this.ideas.findIndex((idea) => idea.id == event);
          if (index) this.ideas.splice(index, 1);
          this.$emit('categoryChanged');
        }
      });
  }

  async editCategory(): Promise<void> {
    this.displayEditCategory = true;
  }

  async deleteCategory(): Promise<void> {
    await categorisationService
      .deleteCategory(this.category.id)
      .then((done) => {
        if (done) {
          this.displayDetails = false;
          this.$emit('categoryChanged');
        }
      });
  }
}
</script>

<style lang="scss" scoped>
.categorisation {
  &__content {
    width: 100%;
    padding: 10px;
    height: 100%;
  }
}

.icon {
  margin: 0.8em 0;
}

.heading {
  color: white;

  &__undefined {
    color: var(--color-darkblue);
  }
}

.item {
  width: 100%;
  height: 100%;
}

.el-card {
  height: 100%;
}
</style>
