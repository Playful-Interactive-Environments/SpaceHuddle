<template>
  <header class="header">
    <Logo class="header__logo" />
  </header>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import Logo from '@/components/shared/atoms/Logo.vue';

@Options({
  components: {
    Logo,
  },
})
export default class PublicHeader extends Vue {
}
</script>

<style lang="scss" scoped>
.header {
  height: var(--header-height);
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;

  &__logo {
    padding: 0 2em;
  }
}
</style>
