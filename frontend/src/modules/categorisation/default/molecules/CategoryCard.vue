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
  <el-drawer v-model="displayDetails" modal-class="darkblue">
    <template v-slot:title>
      <h2 class="heading heading--regular" :style="{ color: categoryColor }">
        {{ categoryName }}
      </h2>
    </template>
    <main
      class="categorisation__content"
      :style="{
        'background-color': categoryColor,
        padding: '10px',
        height: '100%',
      }"
    >
      <IdeaCard
        v-for="idea in ideas"
        :key="idea.id"
        :id="idea.id"
        :idea="idea"
        :is-selectable="false"
        :is-deletable="false"
        class="item"
        style="height: unset"
      />
    </main>
  </el-drawer>
</template>

<script lang="ts">
import { Prop } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import { Category } from '@/types/api/Category';
import { Idea } from '@/types/api/Idea';
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';

@Options({
  components: {
    IdeaCard,
  },
})
export default class CategoryCard extends Vue {
  @Prop() category!: Category;
  @Prop() ideas!: Idea[];
  @Prop({ default: false }) modelValue!: boolean;

  displayDetails = false;

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
}
</script>

<style lang="scss" scoped>
.categorisation {
  &__content {
    width: 100%;
    column-width: 22vw;
    column-gap: 1rem;
  }
}

.icon {
  text-align: center;
  width: 100%;
  margin: 0.8em 0;
  font-size: 40pt;
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
