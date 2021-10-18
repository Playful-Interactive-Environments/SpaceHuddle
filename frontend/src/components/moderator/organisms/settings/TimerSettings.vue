<template>
  <ValidationForm
    :form-data="formData"
    :use-default-submit="false"
    v-on:submitDataValid="save"
    v-on:reset="reset"
  >
    <el-dialog v-model="showSettings" width="80vw" :before-close="handleClose">
      <template #title>
        <span class="el-dialog__title">{{
          $t('moderator.organism.settings.timerSettings.header')
        }}</span>
      </template>
      <el-form-item
        prop="remindingTime"
        :label="$t('moderator.organism.settings.timerSettings.time')"
        :rules="[defaultFormRules.ruleRequired, defaultFormRules.ruleDate]"
      >
        <el-time-picker v-model="formData.remindingTime" />
      </el-form-item>
      <template #footer>
        <FromSubmitItem
          :form-state-message="formData.stateMessage"
          submit-label-key="moderator.organism.settings.timerSettings.submit"
        />
      </template>
    </el-dialog>
  </ValidationForm>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as taskService from '@/services/task-service';
import { convertToSaveVersion, Task } from '@/types/api/Task';
import ValidationForm, {
  ValidationFormCall,
} from '@/components/shared/molecules/ValidationForm.vue';
import { ValidationData } from '@/types/ui/ValidationRule';
import FromSubmitItem from '@/components/shared/molecules/FromSubmitItem.vue';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';

@Options({
  components: {
    ValidationForm,
    FromSubmitItem,
  },
  emits: ['update:showModal'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TimerSettings extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  @Prop({ default: false }) showModal!: boolean;
  @Prop() task!: Task;

  formData: ValidationData = {
    remindingTime: new Date(0),
  };

  showSettings = false;

  mounted(): void {
    this.reset();
  }

  handleClose(done: { (): void }): void {
    this.reset();
    done();
    this.$emit('update:showModal', false);
  }

  reset(): void {
    let defaultTime = 1000 * 60 * 10;
    if (this.task && this.task.remainingTime && this.task.remainingTime > 0)
      defaultTime = this.task.remainingTime * 1000;
    const remindingTime = new Date(defaultTime);
    remindingTime.setHours(
      remindingTime.getHours() + remindingTime.getTimezoneOffset() / 60
    );
    this.formData.remindingTime = remindingTime;
    this.formData.call = ValidationFormCall.CLEAR_VALIDATE;
  }

  get remainingSeconds(): number {
    if (!this.formData.remindingTime) return -1;
    const remindingTime = this.formData.remindingTime;
    return (
      remindingTime.getHours() * 3600 +
      remindingTime.getMinutes() * 60 +
      remindingTime.getSeconds()
    );
  }

  @Watch('showModal', { immediate: true })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showSettings = showModal;
  }

  save(): void {
    this.task.remainingTime = this.remainingSeconds;
    const saveVersion = convertToSaveVersion(this.task);
    taskService.updateTask(saveVersion);
    this.reset();
    this.showSettings = false;
    this.$emit('update:showModal', false);
  }
}
</script>

<style scoped></style>
