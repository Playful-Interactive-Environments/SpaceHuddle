<template>
  <div class="navigation-with-back">
    <el-page-header :content="currentRouteTitle" @back="$router.go(-1)" />
    <Navigation />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import Navigation from '@/components/moderator/molecules/Navigation.vue';

@Options({
  components: {
    Navigation,
  },
})
export default class NavigationWithBack extends Vue {
  @Prop({ default: '' }) readonly currentRouteTitle!: string;
}
</script>

<style lang="scss" scoped>
.navigation-with-back {
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: var(--header-height);
  padding-left: 2rem;
}
</style>
