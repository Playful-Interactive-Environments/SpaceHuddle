<template>
  <el-tooltip placement="right">
    <template #content>
      <div style="max-width: 50vw">
        {{ $t(`module.${taskType}.${moduleName}.description.description`) }}
      </div>
    </template>
    <font-awesome-icon :icon="icon" v-if="icon" />
    <font-awesome-icon icon="times" v-else />
  </el-tooltip>
  &nbsp;&nbsp;
  <span>
    {{ $t(`module.${taskType}.${moduleName}.description.title`) }}
  </span>
</template>

<script lang="ts">
import { Prop, Watch } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import { getModuleConfig } from '@/modules';

@Options({
  components: {},
})
export default class ModuleItem extends Vue {
  @Prop() moduleName!: string;
  @Prop() taskType!: string;
  icon: string | null = null;

  @Watch('taskType', { immediate: true })
  onTaskTypeChanged(): void {
    this.setIcon();
  }

  @Watch('module', { immediate: true })
  onModuleChanged(): void {
    this.setIcon();
  }

  async setIcon(): Promise<void> {
    await getModuleConfig('icon', this.taskType, this.moduleName).then(
      (result) => (this.icon = result)
    );
  }
}
</script>

<style lang="scss" scoped></style>
