<template>
  <div class="module-count">
    <div class="module-count__item">
      <span class="module-count__count">2</span>
      {{ $t('moderator.molecule.moduleCount.topics') }}
    </div>
    <div class="module-count__item">
      <span class="module-count__count">7</span>
      {{ $t('moderator.molecule.moduleCount.modules') }}
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';

@Options({
  components: {},
})
export default class ModuleCount extends Vue {}
</script>

<style lang="scss" scoped>
.module-count {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: var(--font-size-small);
  text-transform: uppercase;
  letter-spacing: 2px;
  margin-top: 1rem;
  line-height: 1;
  flex-wrap: wrap;
  gap: 0.6rem;

  &__item {
    white-space: nowrap;
  }

  &__count {
    background-color: var(--color-mint-light);
    color: white;
    padding: 0.1rem 0.7rem;
    letter-spacing: 0;
    font-size: var(--font-size-default);
    border-radius: 100px;
    margin-right: 0.2rem;
  }
}
</style>
