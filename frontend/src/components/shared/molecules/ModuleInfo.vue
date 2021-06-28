<template>
  <div :class="{ 'module-info--centered': isClient }" class="module-info">
    <span class="module-info__type">{{ type }}</span>
    <h3 :class="{ 'heading--regular': isClient }" class="module-info__title">
      {{ title }}
    </h3>
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
  @Prop({ default: false }) isClient!: boolean;
}
</script>

<style lang="scss" scoped>
.module-info {
  flex-grow: 1;
  font-size: var(--font-size-small);

  &__type {
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--module-color);
  }

  &__title {
    font-weight: var(--font-weight-bold);
    margin: 0.2rem 0 0.1rem;
  }

  &__description {
    display: -webkit-box;
    line-clamp: 1;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  &--centered {
    text-align: center;
    max-width: 100%;
    font-size: var(--font-size-default);
    margin-bottom: 1em;
    line-height: 1.75em;

    span {
      line-height: 3em;
    }
  }
}
</style>
