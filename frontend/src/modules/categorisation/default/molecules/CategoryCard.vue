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
      v-on:click="showDetails"
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
      <div class="card__text">
        <div class="card__title">
          {{ categoryName }}
          <span class="actions" v-if="isEditable">
            <el-dropdown
              class="card__menu"
              v-on:command="menuItemSelected($event)"
            >
              <span class="el-dropdown-link">
                <font-awesome-icon icon="ellipsis-h" />
              </span>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item command="edit">
                    <font-awesome-icon icon="pen" />
                  </el-dropdown-item>
                  <el-dropdown-item command="delete">
                    <font-awesome-icon icon="trash" />
                  </el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </span>
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
    size="60%"
  >
    <template v-slot:title>
      <div class="media">
        <div class="media-left">
          <img
            v-if="categoryImage"
            :src="categoryImage"
            class="header__image"
            alt=""
          />
          <img
            v-if="categoryLink && !categoryImage"
            :src="categoryLink"
            class="header__image"
            alt=""
          />
        </div>
        <div class="media-content">
          <h2
            class="heading heading--regular"
            :style="{ color: categoryColor }"
          >
            {{ categoryName }}
          </h2>
          <div v-if="categoryDescription" >
            {{ categoryDescription }}
          </div>
        </div>
      </div>
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
      class="categorisation__content layout__columns"
      :style="{
        'background-color': categoryColor,
      }"
      v-if="category && ideas"
    >
      <draggable v-model="ideaList" item-key="id" @end="dragDone" group="idea">
        <template v-slot:item="{ element }">
          <IdeaCard
            :id="element.id"
            :idea="element"
            :is-selectable="false"
            :is-editable="true"
            class="item"
            style="height: unset"
          >
            <template #action v-if="category != null">
              <font-awesome-icon
                icon="ban"
                v-on:click="removeIdea(element.id, $event)"
              />
            </template>
          </IdeaCard>
        </template>
      </draggable>
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
import draggable from 'vuedraggable';

@Options({
  components: {
    IdeaCard,
    CategorySettings,
    draggable,
  },
  emits: ['categoryChanged', 'update:ideas'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class CategoryCard extends Vue {
  @Prop() category!: Category;
  @Prop({ default: [] }) ideas!: Idea[];
  @Prop({ default: true }) isEditable!: boolean;

  displayDetails = false;
  displayEditCategory = false;

  get ideaList(): Idea[] {
    return this.ideas;
  }

  set ideaList(ideas: Idea[]) {
    this.$emit('update:ideas', ideas);
  }

  get categoryName(): string {
    if (this.category) return this.category.keywords;
    return (this as any).$t('module.categorisation.default.settings.undefined');
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

  showDetails(): void {
    if (this.isEditable) this.displayDetails = true;
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

  menuItemSelected(command: string): void {
    switch (command) {
      case 'edit':
        this.editCategory();
        break;
      case 'delete':
        this.deleteCategory();
        break;
    }
  }

  /* eslint-disable @typescript-eslint/explicit-module-boundary-types*/
  async dragDone(): Promise<void> {
    await categorisationService.addIdeasToCategory(
      this.category.id,
      this.ideas.map((idea) => idea.id)
    );
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

.header {
  &__image {
    width: 10rem;
    border-radius: var(--border-radius-xs);
  }
}

.el-card,
.item {
  width: 100%;
  height: 100%;
}

.card {
  &__text {
    padding: 14px;
  }
}

.el-dropdown-link {
  color: white;
}
</style>
