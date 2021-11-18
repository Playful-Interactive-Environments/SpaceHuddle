<template>
  <el-empty
    :description="$t('module.none.publicScreen.header')"
    v-loading="loading"
  >
    <p>{{ $t('module.none.publicScreen.info') }}</p>
  </el-empty>
  <!--<main class="flex-column full-height-header centered public-screen__error">
    <h2>{{ $t('module.none.publicScreen.header') }}</h2>
    <p>{{ $t('module.none.publicScreen.info') }}</p>
  </main>-->
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

@Options({
  components: {},
})
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  loading = true;
}
</script>

<style lang="scss" scoped>
.el-empty::v-deep {
  margin-top: 8rem;
  color: var(--el-text-color-secondary);

  .el-loading-mask {
    background-color: unset;
  }
}
</style>
