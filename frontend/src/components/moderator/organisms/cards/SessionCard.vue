<template>
  <div class="blog-card">
    <div>
      <p class="blog-card__date"><slot name="date"></slot></p>
      <h2 class="heading heading--regular"><slot name="title"></slot></h2>
      <p class="blog-card__description"><slot name="description"></slot></p>
      <slot name="topics"></slot>
    </div>
    <div class="blog-card__content">
      <slot> </slot>
    </div>
  </div>
</template>

<script lang="ts">
import { Prop } from 'vue-property-decorator';
import { Vue } from 'vue-class-component';

export default class SessionCard extends Vue {
  @Prop({ default: [] }) errors!: string[];
}
</script>

<style lang="scss" scoped>
.blog-card {
  padding: 1.5rem 2rem;
  background: #fff;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
  border-radius: var(--border-radius);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  height: 100%;

  &__date {
    text-transform: uppercase;
    color: #aaaaaa;
    font-size: var(--font-size-small);
    margin-bottom: 1rem;
  }

  &__description {
    line-clamp: 3;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  &__content {
    margin-top: 2.5rem;
  }
}
</style>
