<template>
  <el-form-item
    :label="$t('module.brainstorming.chat.moderatorConfig.showAll')"
    :prop="`${rulePropPath}.showAll`"
    :rules="[defaultFormRules.ruleRequired]"
  >
    <el-switch v-model="modelValue.showAll" />
  </el-form-item>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';

@Options({
  components: {},
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorConfig extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  @Prop() readonly rulePropPath!: string;

  @Prop() readonly moduleId!: string;
  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop({ default: {} }) modelValue!: any;

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue && !this.modelValue.showAll) {
      this.modelValue.showAll = false;
    }
  }
}
</script>
