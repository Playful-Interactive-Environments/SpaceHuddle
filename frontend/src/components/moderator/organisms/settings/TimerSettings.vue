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
        :rules="[
          defaultFormRules.ruleRequiredIf(formData.hasTimeLimit),
          defaultFormRules.ruleDate,
        ]"
      >
        <div class="level">
          <span class="level-left">
            <el-switch
              class="level-item"
              v-model="formData.hasTimeLimit"
              :inactive-text="
                $t('moderator.organism.settings.timerSettings.useTimer')
              "
            />
            <el-time-picker
              v-if="formData.hasTimeLimit"
              class="level-item"
              v-model="formData.remindingTime"
              :disabled="!formData.hasTimeLimit"
            />
          </span>
        </div>
      </el-form-item>
      <template #footer>
        <FromSubmitItem
          :form-state-message="formData.stateMessage"
          submit-label-key="moderator.organism.settings.timerSettings.submit"
        />
        <el-button
          class="deactivate"
          v-on:click="deactivateTimer"
          v-if="showDeactivate"
        >
          {{ $t('moderator.organism.settings.timerSettings.deactivate') }}
        </el-button>
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
import { defaultFormRules, ValidationRuleDefinition } from '@/utils/formRules';
import TaskStates from '@/types/enum/TaskStates';

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
    hasTimeLimit: true,
    remindingTime: new Date(0),
  };

  showSettings = false;
  defaultTime = 1000 * 60 * 10;

  mounted(): void {
    this.reset();
  }

  get showDeactivate(): boolean {
    return this.task.state === TaskStates.ACTIVE;
  }

  handleClose(done: { (): void }): void {
    this.reset();
    done();
    this.$emit('update:showModal', false);
  }

  setRemindingTime(time: number): void {
    const remindingTime = new Date(time);
    remindingTime.setHours(
      remindingTime.getHours() + remindingTime.getTimezoneOffset() / 60
    );
    this.formData.remindingTime = remindingTime;
  }

  deactivateTimer(): void {
    this.task.state = TaskStates.WAIT;
    const saveVersion = convertToSaveVersion(this.task);
    taskService.updateTask(saveVersion);
    this.reset();
    this.showSettings = false;
    this.$emit('update:showModal', false);
  }

  reset(): void {
    this.formData.hasTimeLimit = !!(!this.task || this.task.remainingTime);
    if (this.formData.hasTimeLimit) {
      let time = this.defaultTime;
      if (this.task && this.task.remainingTime && this.task.remainingTime > 0)
        time = this.task.remainingTime * 1000;
      this.setRemindingTime(time);
    } else {
      this.formData.remindingTime = null;
    }
    this.formData.call = ValidationFormCall.CLEAR_VALIDATE;
  }

  get remainingSeconds(): number | null {
    if (!this.formData.hasTimeLimit || !this.formData.remindingTime)
      return null;
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

  @Watch('task', { immediate: true })
  onTaskChanged(): void {
    this.reset();
  }

  @Watch('formData.hasTimeLimit', { immediate: true })
  onHasTimeLimitChanged(val: boolean): void {
    if (val && this.formData.remindingTime === null) {
      this.setRemindingTime(this.defaultTime);
    }
  }

  save(): void {
    this.task.state = TaskStates.ACTIVE;
    this.task.remainingTime = this.remainingSeconds;
    const saveVersion = convertToSaveVersion(this.task);
    taskService.updateTask(saveVersion);
    this.reset();
    this.showSettings = false;
    this.$emit('update:showModal', false);
  }
}
</script>

<style scoped>
.deactivate {
  width: 100%;
}
</style>
