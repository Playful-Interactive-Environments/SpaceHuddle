<template>
  <div class="overview container container--fullheight">
    <h1>Hello fellow explorer!</h1>
    <p>here you can see all active topics and modules</p>
  </div>
</template>

<script lang="ts">
import { Vue } from 'vue-class-component';

export default class ModuleOverview extends Vue {}
</script>

<style lang="scss" scoped>
.overview {
  color: #fff;
  background: var(--color-darkblue);
}
</style>
