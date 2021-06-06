<template>
  <div class="detail">
    <Sidebar />
    <main class="detail__content">Session Detail works</main>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import Sidebar from '@/components/moderator/organisms/Sidebar.vue';

@Options({
  components: {
    Sidebar,
  },
})
export default class SessionDetail extends Vue {}
</script>

<style lang="scss" scoped>
.detail {
  display: flex;
  justify-content: space-between;

  &__content {
    flex-grow: 1;
  }
}
</style>
