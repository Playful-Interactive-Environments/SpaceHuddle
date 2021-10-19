<template>
  <el-badge :value="ideas.length" class="item">
    <el-card
      shadow="never"
      :style="{
        'background-color': categoryColor,
        'border-color': categoryColor,
        color: 'white',
      }"
      :body-style="{
        padding: '0px',
      }"
      v-on:click="displayDetails = true"
    >
      <img
        v-if="categoryImage"
        :src="categoryImage"
        class="card__image"
        alt=""
      />
      <img
        v-if="categoryLink && !categoryImage"
        :src="categoryLink"
        class="card__image"
        alt=""
      />
      <div style="padding: 14px">
        <div class="card__title">
          {{ categoryName }}
        </div>
        <div v-if="categoryDescription" class="card__content">
          {{ categoryDescription }}
        </div>
      </div>
    </el-card>
  </el-badge>
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
        :is-editable="true"
        class="item"
        style="height: unset"
      >
        <template #action v-if="category != null">
          <font-awesome-icon
            icon="ban"
            v-on:click="removeIdea(idea.id, $event)"
          />
        </template>
      </IdeaCard>
    </main>
  </el-drawer>
  <CategorySettings
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
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as categorisationService from '@/services/categorisation-service';
import CategorySettings from '@/modules/categorisation/default/molecules/CategorySettings.vue';

@Options({
  components: {
    IdeaCard,
    CategorySettings,
  },
  emits: ['categoryChanged'],
})
export default class CategoryCard extends Vue {
  @Prop() category!: Category;
  @Prop() ideas!: Idea[];

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

  get categoryImage(): string | null {
    if (this.category) return this.category.image;
    return '';
  }

  get categoryLink(): string | null {
    if (this.category) return this.category.link;
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

  removeIdea(ideaId: string, event: PointerEvent): void {
    event.stopImmediatePropagation();
    categorisationService
      .removeIdeasFromCategory(this.category.id, [ideaId])
      .then((done) => {
        if (done) {
          const index = this.ideas.findIndex((idea) => idea.id == ideaId);
          if (index) this.ideas.splice(index, 1);
          this.$emit('categoryChanged');
        }
      });
  }

  async editCategory(): Promise<void> {
    this.displayEditCategory = true;
  }

  async deleteCategory(): Promise<void> {
    if (this.category && this.category.id) {
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

.el-card,
.item {
  width: 100%;
  height: 100%;
}
</style>
