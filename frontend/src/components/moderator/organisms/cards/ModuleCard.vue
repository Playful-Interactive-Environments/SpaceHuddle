<template>
  <el-card
    shadow="never"
    body-style="text-align: center"
  >
    <h2 class="heading heading--regular">
      {{ $t(`module.${taskType}.${moduleName}.description.title`) }}
    </h2>
    <el-switch v-model="selected"> </el-switch>
    <div class="icon"><font-awesome-icon :icon="icon" v-if="icon" /></div>
    <el-tooltip placement="top">
      <template #content>
        <div style="max-width: 50vw">
          {{ $t(`module.${taskType}.${moduleName}.description.description`) }}
        </div>
      </template>
      <p style="overflow: hidden; white-space: nowrap; text-overflow: ellipsis">
        {{ $t(`module.${taskType}.${moduleName}.description.description`) }}
      </p>
    </el-tooltip>
  </el-card>
</template>

<script lang="ts">
import { Prop, Watch } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import { getModuleConfig } from '@/modules';

@Options({
  components: {},
})
export default class ModuleCard extends Vue {
  @Prop() moduleName!: string;
  @Prop() taskType!: string;
  @Prop({ default: false }) modelValue!: boolean;
  icon: string | null = null;
  selected = false;

  @Watch('modelValue', { immediate: true })
  onModelValueChanged(value: boolean): void {
    if (this.selected != value) this.selected = value;
  }

  @Watch('selected', { immediate: true })
  onSelectedChanged(value: boolean): void {
    if (this.modelValue != value) this.$emit('update:modelValue', value);
  }

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

<style lang="scss" scoped>
.icon {
  text-align: center;
  width: 100%;
  margin: 0.8em 0em;
  font-size: 40pt;
}
</style>
