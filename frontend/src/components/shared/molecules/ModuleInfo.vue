<template>
  <div class="module-info">
    <span class="module-info__type">{{ type }}</span>
    <h3 class="module-info__title">{{ title }}</h3>
    <p class="module-info__description">{{ description }}</p>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import ModuleType from '@/types/ModuleType';

@Options({
  components: {},
})
export default class ModuleInfo extends Vue {
  @Prop({ default: ModuleType.BRAINSTORMING }) type!: ModuleType;
  @Prop({ default: '' }) title!: string;
  @Prop({ default: '' }) description!: string;
}
</script>

<style lang="scss" scoped>
.module-info {
  flex-grow: 1;

  &__type {
    font-size: var(--font-size-small);
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--module-color);
  }

  &__title {
    font-weight: var(--font-weight-bold);
    margin: 0.2rem 0 0.1rem;
  }

  &__description {
    font-size: var(--font-size-small);
  }
}
</style>
