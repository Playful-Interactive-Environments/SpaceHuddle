<template>
  <div class="item">
    <el-badge :value="ideas.length" class="item">
      <el-card
        v-if="category"
        :style="{
          'background-color': category.parameter.color,
          color: 'white',
        }"
        :body-style="{
          'text-align': 'center',
        }"
      >
        <h2 class="heading heading--regular">
          {{ category.keywords }}
        </h2>
        <el-tooltip placement="top">
          <template #content>
            <div style="max-width: 50vw">
              {{ category.description }}
            </div>
          </template>
          <p
            style="
              overflow: hidden;
              white-space: nowrap;
              text-overflow: ellipsis;
            "
          >
            {{ category.description }}
          </p>
        </el-tooltip>
      </el-card>
      <el-card
        v-else
        :style="{
          color: 'var(--color-darkblue)',
        }"
        :body-style="{
          'text-align': 'center',
        }"
      >
        <h2 class="heading heading__undefined heading--regular">undefined</h2>
      </el-card>
    </el-badge>
  </div>
</template>

<script lang="ts">
import { Prop } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import { Category } from '@/types/api/Category';
import { Idea } from '@/types/api/Idea';

@Options({
  components: {},
})
export default class CategoryCard extends Vue {
  @Prop() category!: Category;
  @Prop() ideas!: Idea[];
  @Prop({ default: false }) modelValue!: boolean;
}
</script>

<style lang="scss" scoped>
.icon {
  text-align: center;
  width: 100%;
  margin: 0.8em 0em;
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
