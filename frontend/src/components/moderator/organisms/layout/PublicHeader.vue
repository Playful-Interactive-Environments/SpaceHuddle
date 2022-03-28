<template>
  <header class="media">
    <font-awesome-icon
      :icon="['fac', 'logoWithName']"
      class="media-left logo"
    />
    <span class="media-content">
      <slot></slot>
    </span>
  </header>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';

@Options({
  components: {},
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
