<template>
  <el-container v-loading="loading" class="fill">
    <!--<h2>{{ $t('module.none.moderatorContent.header') }}</h2>
    <p>{{ $t('module.none.moderatorContent.info') }}</p>-->
  </el-container>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { IModeratorContent } from '@/types/ui/IModeratorContent';

@Options({
  components: {},
})
export default class ModeratorContent extends Vue implements IModeratorContent {
  @Prop() readonly taskId!: string;
  loading = true;

  reloadTaskSettings(): void {
    //
  }
}
</script>

<style lang="scss" scoped>
.fill::v-deep(.el-loading-mask) {
  background-color: unset;
}
</style>
