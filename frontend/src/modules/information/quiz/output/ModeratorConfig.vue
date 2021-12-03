<template>
  <el-form-item
    :label="$t('module.information.default.moderatorConfig.answerCount')"
    :prop="`${rulePropPath}.answerCount`"
    :rules="[defaultFormRules.ruleRequired, defaultFormRules.ruleNumber]"
  >
    <el-input-number
      v-model="modelValue.answerCount"
      :placeholder="
        $t('module.information.default.moderatorConfig.answerCountExample')
      "
    />
  </el-form-item>
  <el-form-item
    :label="$t('module.information.default.moderatorConfig.time')"
    :prop="`${rulePropPath}.defaultQuestionTime`"
    :rules="[
      defaultFormRules.ruleRequiredIf(modelValue.hasTimeLimit),
      defaultFormRules.ruleDate,
    ]"
  >
    <div class="level">
      <span class="level-left">
        <el-switch
          class="level-item"
          v-model="hasTimeLimit"
          :inactive-text="
            $t('moderator.organism.settings.timerSettings.useTimer')
          "
        />
        <el-time-picker
          v-if="hasTimeLimit"
          class="level-item"
          v-model="defaultQuestionTime"
          :disabled="!hasTimeLimit"
        />
      </span>
    </div>
  </el-form-item>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as moduleService from '@/services/module-service';
import { Module } from '@/types/api/Module';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import * as timerService from '@/services/timer-service';

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
  module: Module | null = null;
  defaultTime = 60;

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue && !this.modelValue.answerCount) {
      this.modelValue.answerCount = 4;
    }
    if (this.modelValue && !this.modelValue.defaultQuestionTime) {
      this.modelValue.defaultQuestionTime = this.defaultTime;
    }
  }

  get hasTimeLimit(): boolean {
    return this.modelValue.defaultQuestionTime !== null;
  }

  set hasTimeLimit(value: boolean) {
    if (value) this.modelValue.defaultQuestionTime = this.defaultTime;
    else this.modelValue.defaultQuestionTime = null;
  }

  get defaultQuestionTime(): Date {
    if (this.modelValue.defaultQuestionTime)
      return timerService.getDate(this.modelValue.defaultQuestionTime);
    return new Date(0);
  }

  set defaultQuestionTime(value: Date) {
    this.modelValue.defaultQuestionTime = timerService.getSeconds(value);
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
