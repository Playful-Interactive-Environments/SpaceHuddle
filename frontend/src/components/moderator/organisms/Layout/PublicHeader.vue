<template>
  <header class="media">
    <Logo class="media-left logo" />
    <span class="media-content">
      <slot></slot>
    </span>
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
export default class PublicHeader extends Vue {}
</script>

<style lang="scss" scoped>
.media {
  padding-top: 2em;
}
.logo {
  padding: 0 2em;
}
</style>
