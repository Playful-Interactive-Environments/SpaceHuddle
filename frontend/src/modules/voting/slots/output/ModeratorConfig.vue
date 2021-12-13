<template>
  <el-form-item
    :label="$t('module.voting.slots.moderatorConfig.slotCount')"
    :prop="`${rulePropPath}.slotCount`"
    :rules="[defaultFormRules.ruleRequired, defaultFormRules.ruleNumber]"
  >
    <el-input-number
      v-model="modelValue.slotCount"
      :min="1"
      :placeholder="$t('module.voting.slots.moderatorConfig.slotCountExample')"
    />
  </el-form-item>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as moduleService from '@/services/module-service';
import { Module } from '@/types/api/Module';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';

@Options({
  components: {},
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorConfig extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  @Prop() readonly moduleId!: string;
  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop() readonly rulePropPath!: string;
  @Prop({ default: {} }) modelValue!: any;
  module: Module | null = null;

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue && !this.modelValue.slotCount) {
      this.modelValue.slotCount = 3;
    }
  }

  @Watch('moduleId', { immediate: true })
  async onModuleIdChanged(): Promise<void> {
    await this.getModule();
  }

  async getModule(): Promise<void> {
    if (this.moduleId) {
      await moduleService.getModuleById(this.moduleId).then((module) => {
        this.module = module;
      });
    }
  }
}
</script>
