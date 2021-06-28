<template>
  <div class="menubar">
    <Logo class="menubar__logo" />
    <div class="menubar__icon">|||</div>
  </div>
</template>

<script lang="ts">
import { Vue, Options } from 'vue-class-component';
import Logo from '@/components/shared/atoms/Logo.vue';

@Options({
  components: {
    Logo,
  },
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
    display: flex;
    align-items: center;
    width: 40vw;
    margin-bottom: -6px;
  }

  &__icon {
    transform: rotate(90deg) scaleY(1.2);
    letter-spacing: 0.1rem;
  }
}
</style>
