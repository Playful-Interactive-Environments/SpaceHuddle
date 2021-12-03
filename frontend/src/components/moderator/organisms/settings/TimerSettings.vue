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
import * as timerService from '@/services/timer-service';
import ValidationForm, {
  ValidationFormCall,
} from '@/components/shared/molecules/ValidationForm.vue';
import { ValidationData } from '@/types/ui/ValidationRule';
import FromSubmitItem from '@/components/shared/molecules/FromSubmitItem.vue';
import { defaultFormRules, ValidationRuleDefinition } from '@/utils/formRules';
import TaskStates from '@/types/enum/TaskStates';
import { TimerEntity } from '@/types/enum/TimerEntity';

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
  @Prop({ default: TimerEntity.TASK }) entityName!: string;
  @Prop() entity!: any;
  @Prop({ default: null }) defaultTimerSeconds!: number | null;

  get defaultTime(): number {
    if (this.defaultTimerSeconds) return this.defaultTimerSeconds;
    return 60 * 10;
  }

  get hasTimeLimitByDefault(): boolean {
    return this.defaultTimerSeconds !== null;
  }

  formData: ValidationData = {
    hasTimeLimit: true,
    remindingTime: new Date(0),
  };

  showSettings = false;

  mounted(): void {
    this.reset();
  }

  get showDeactivate(): boolean {
    return this.entityState === TaskStates.ACTIVE;
  }

  handleClose(done: { (): void }): void {
    this.reset();
    done();
    this.$emit('update:showModal', false);
  }

  setRemindingTime(time: number): void {
    this.formData.remindingTime = timerService.getDate(time);
  }

  get entityState(): string {
    if ('state' in this.entity) return this.entity.state;
    return '';
  }

  set entityState(value: string) {
    if ('state' in this.entity) this.entity.state = value;
  }

  get entityRemainingTime(): number | null {
    if ('remainingTime' in this.entity) return this.entity.remainingTime;
    else if ('parameter' in this.entity)
      return this.entity.parameter.remainingTime;
    return null;
  }

  set entityRemainingTime(value: number | null) {
    if ('remainingTime' in this.entity) this.entity.remainingTime = value;
    else if ('parameter' in this.entity)
      this.entity.parameter.remainingTime = value;
  }

  deactivateTimer(): void {
    this.entityState = TaskStates.WAIT;
    timerService.update(this.entityName, this.entity.id, this.entity);
    this.reset();
    this.showSettings = false;
    this.$emit('update:showModal', false);
  }

  reset(): void {
    this.formData.hasTimeLimit = !!(
      !this.entity ||
      this.entityRemainingTime ||
      this.hasTimeLimitByDefault
    );
    if (this.formData.hasTimeLimit) {
      let time = this.defaultTime;
      if (
        this.entity &&
        this.entityRemainingTime &&
        this.entityRemainingTime > 0
      )
        time = this.entityRemainingTime;
      this.setRemindingTime(time);
    } else {
      this.formData.remindingTime = null;
    }
    this.formData.call = ValidationFormCall.CLEAR_VALIDATE;
  }

  get remainingSeconds(): number | null {
    if (!this.formData.hasTimeLimit || !this.formData.remindingTime)
      return null;

    return timerService.getSeconds(this.formData.remindingTime);
  }

  @Watch('showModal', { immediate: true })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showSettings = showModal;
  }

  @Watch('entity', { immediate: true })
  onEntityChanged(): void {
    this.reset();
  }

  @Watch('formData.hasTimeLimit', { immediate: true })
  onHasTimeLimitChanged(val: boolean): void {
    if (val && this.formData.remindingTime === null) {
      this.setRemindingTime(this.defaultTime);
    }
  }

  save(): void {
    this.entityState = TaskStates.ACTIVE;
    this.entityRemainingTime = this.remainingSeconds;
    timerService.update(this.entityName, this.entity.id, this.entity);
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
