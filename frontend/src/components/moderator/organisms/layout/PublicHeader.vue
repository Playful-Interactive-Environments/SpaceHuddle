<template>
  <header class="media">
    <font-awesome-icon
      :icon="getIconName()"
      class="media-left logo"
    />
    <span class="media-content">
      <slot></slot>
    </span>
  </header>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";

@Options({
  components: {FontAwesomeIcon},
})

export default class PublicHeader extends Vue {
  getIconName(): string[] {
    return process.env.VUE_APP_THEME == 'ecopolis'
      ? ['fac', 'EcopolisLogoWithName']
      : ['fac', 'logoWithName'];
  }
}
</script>

<style lang="scss" scoped>
.media {
  padding-top: 2em;
}
.logo {
  padding: 0 2em;
}
</style>
