<template>
  <div class="menubar">
    <div class="menubar__logo">GAB</div>
    <div class="menubar__icon">|||</div>
  </div>
</template>

<script lang="ts">
import { Vue, Options } from 'vue-class-component';

@Options({
  components: {},
})
export default class Menubar extends Vue {}
</script>

<style lang="scss" scoped>
.menubar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: white;
  margin-bottom: 1rem;

  &__logo {
    font-weight: var(--font-weight-bold);
  }

  &__icon {
    transform: rotate(90deg) scaleY(1.2);
    letter-spacing: 0.1rem;
  }
}
</style>
