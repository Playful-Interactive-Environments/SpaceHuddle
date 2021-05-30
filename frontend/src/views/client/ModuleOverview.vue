<template>
  <layout-client :centered="true" class="join">
    <h1>Hello fellow explorer!</h1>
    <p>here you can see all active topics and modules</p>
  </layout-client>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import LayoutClient from '../../components/layout/LayoutClient.vue';

@Options({
  components: {
    LayoutClient,
  },
})
export default class ModuleOverview extends Vue {}
</script>

<style scoped>
.join {
  color: #fff;
}
</style>
