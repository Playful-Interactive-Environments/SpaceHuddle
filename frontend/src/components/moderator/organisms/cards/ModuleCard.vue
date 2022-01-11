<template>
  <el-card shadow="never" body-style="text-align: center">
    <h2
      class="heading heading--regular"
      style="overflow: hidden; white-space: nowrap; text-overflow: ellipsis"
    >
      {{ $t(`module.${taskType}.${moduleName}.description.title`) }}
    </h2>
    <TutorialStep
      v-if="moduleType !== ModuleType.MAIN"
      type="taskSettings"
      step="additionalModule"
      :order="11"
      :displayAllDuplicates="true"
      :disableTutorial="!displayTutorial"
    >
      <el-switch v-model="selected" />
    </TutorialStep>
    <TutorialStep
      v-else
      type="taskSettings"
      step="baseModule"
      :order="10"
      :displayAllDuplicates="true"
      :disableTutorial="!displayTutorial"
    >
      <el-radio v-model="mainModuleName" :label="moduleName">{{}}</el-radio>
    </TutorialStep>
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
import { ModuleType } from '@/types/enum/ModuleType';
import TutorialStep from '@/components/shared/atoms/TutorialStep.vue';

@Options({
  components: {
    TutorialStep,
  },
  emits: ['update:modelValue', 'update:mainModule'],
})
export default class ModuleCard extends Vue {
  @Prop() moduleName!: string;
  @Prop() taskType!: string;
  @Prop({ default: false }) modelValue!: boolean;
  @Prop({ default: '' }) mainModule!: string;
  @Prop({ default: true }) displayTutorial!: boolean;
  icon: string | null = null;
  moduleType: string | null = null;

  ModuleType = ModuleType;

  get selected(): boolean {
    return this.modelValue;
  }

  set selected(value: boolean) {
    this.$emit('update:modelValue', value);
  }

  get mainModuleName(): string {
    return this.mainModule;
  }

  set mainModuleName(value: string) {
    this.$emit('update:mainModule', value);
  }

  @Watch('taskType', { immediate: true })
  onTaskTypeChanged(): void {
    this.loadModuleConfig();
  }

  @Watch('module', { immediate: true })
  onModuleChanged(): void {
    this.loadModuleConfig();
  }

  async loadModuleConfig(): Promise<void> {
    await getModuleConfig('icon', this.taskType, this.moduleName).then(
      (result) => (this.icon = result)
    );
    await getModuleConfig('type', this.taskType, this.moduleName).then(
      (result) => (this.moduleType = result)
    );
  }
}
</script>

<style lang="scss" scoped>
.el-radio::v-deep .el-radio__label {
  padding-left: unset;
}

.heading {
  margin: 0;
}

.icon {
  text-align: center;
  width: 100%;
  margin: 0.6em 0;
  font-size: 40pt;
}
</style>
