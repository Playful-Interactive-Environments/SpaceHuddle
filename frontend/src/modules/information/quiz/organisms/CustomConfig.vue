<template>
  <el-form-item
    :label="$t('module.information.quiz.moderatorConfig.answerCount')"
    :prop="`${rulePropPath}.answerCount`"
    :rules="[defaultFormRules.ruleRequired, defaultFormRules.ruleNumber]"
  >
    <el-input-number
      v-model="modelValue.answerCount"
      :min="2"
      :placeholder="
        $t('module.information.quiz.moderatorConfig.answerCountExample')
      "
    />
  </el-form-item>
  <el-form-item
    v-if="limitQuestionType === null"
    :label="$t('module.information.quiz.moderatorConfig.questionType')"
    :prop="`${rulePropPath}.questionType`"
  >
    <el-select v-model="modelValue.questionType">
      <el-option
        v-for="questionType in Object.values(QuestionType)"
        :key="questionType"
        :value="questionType"
        :label="$t(`module.information.quiz.enum.questionType.${questionType}`)"
      >
      </el-option>
    </el-select>
  </el-form-item>
  <el-form-item
    v-if="modelValue.questionType === QuestionType.QUIZ"
    :label="$t('module.information.quiz.moderatorConfig.moderatedQuestionFlow')"
    :prop="`${rulePropPath}.moderatedQuestionFlow`"
  >
    <el-switch
      class="level-item"
      v-model="modelValue.moderatedQuestionFlow"
      :inactive-text="
        $t('module.information.quiz.moderatorConfig.moderatedQuestionFlow')
      "
    />
  </el-form-item>
  <el-form-item
    v-if="
      modelValue.moderatedQuestionFlow &&
      modelValue.questionType === QuestionType.QUIZ
    "
    :label="$t('module.information.quiz.moderatorConfig.time')"
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
import { QuestionnaireType } from '@/modules/information/quiz/types/QuestionnaireType';
import { CustomParameter, CustomSync } from '@/types/ui/CustomParameter';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';

@Options({
  components: {},
  emits: ['update'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorConfig
  extends Vue
  implements CustomParameter, CustomSync
{
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  @Prop() readonly rulePropPath!: string;

  @Prop() readonly moduleId!: string;
  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop({ default: {} }) modelValue!: any;
  @Prop({ default: null }) limitQuestionType!: QuestionnaireType | null;
  module: Module | null = null;
  defaultTime = 60;

  QuestionType = QuestionnaireType;

  @Watch('limitQuestionType', { immediate: true })
  onLimitQuestionTypeChanged(): void {
    if (this.limitQuestionType)
      this.modelValue.questionType = this.limitQuestionType;
  }

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue && !this.modelValue.answerCount) {
      this.modelValue.answerCount = 2;
    }
    if (this.modelValue && !this.modelValue.questionType) {
      this.modelValue.questionType = QuestionnaireType.QUIZ;
    }
    if (
      this.modelValue &&
      this.modelValue.moderatedQuestionFlow === undefined
    ) {
      this.modelValue.moderatedQuestionFlow = true;
    }
    if (this.modelValue && !this.modelValue.defaultQuestionTime) {
      this.modelValue.defaultQuestionTime = this.defaultTime;
    }
  }

  @Watch('modelValue.moderatedQuestionFlow', { immediate: true })
  onModeratedQuestionFlowChanged(): void {
    this.$emit('update');
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
    if (this.moduleId) {
      moduleService.registerGetModuleById(
        this.moduleId,
        this.updateModule,
        EndpointAuthorisationType.MODERATOR,
        60 * 60
      );
    }
  }

  updateModule(module: Module): void {
    this.module = module;
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateModule);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  async updateParameterForSaving(): Promise<void> {
    if (
      this.modelValue &&
      this.modelValue.questionType === QuestionnaireType.SURVEY
    ) {
      delete this.modelValue.moderatedQuestionFlow;
    }

    if (this.modelValue && !this.modelValue.moderatedQuestionFlow) {
      delete this.modelValue.defaultQuestionTime;
    }
  }

  customSyncPublicParticipant(): boolean {
    if (
      this.modelValue &&
      this.modelValue.moderatedQuestionFlow !== undefined
    ) {
      return this.modelValue.moderatedQuestionFlow;
    }
    return false;
  }
}
</script>
