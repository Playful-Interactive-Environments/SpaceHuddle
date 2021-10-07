<template>
  <el-dialog v-model="showSettings" width="80vw" :before-close="handleClose">
    <template #title>
      <span class="el-dialog__title">{{
        $t('moderator.organism.settings.timerSettings.header')
      }}</span>
    </template>
    <label class="heading heading--xs">{{
      $t('moderator.organism.settings.timerSettings.time')
    }}</label>
    <el-time-picker v-model="remindingTime" />
    <FormError
      v-if="context.$v.remindingTime.$error"
      :errors="context.$v.remindingTime.$errors"
      :isSmall="true"
    />
    <template #footer>
      <button
        type="submit"
        class="btn btn--gradient btn--fullwidth"
        @click.prevent="saveTime"
      >
        {{ $t('moderator.organism.settings.timerSettings.submit') }}
      </button>
    </template>
  </el-dialog>
</template>

<script lang="ts">
import { Options, Vue, setup } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import useVuelidate from '@vuelidate/core';
import { required } from '@vuelidate/validators';
import * as taskService from '@/services/task-service';
import { convertToSaveVersion, Task } from '@/types/api/Task';

import FormError from '@/components/shared/atoms/FormError.vue';

@Options({
  components: {
    FormError,
  },
  emits: ['update:showModal'],
  validations: {
    remindingTime: {
      required,
    },
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TimerSettings extends Vue {
  @Prop({ default: false }) showModal!: boolean;
  @Prop() task!: Task;

  showSettings = false;
  remindingTime: Date | null = new Date(0);

  mounted(): void {
    this.reset();
  }

  handleClose(done: { (): void }): void {
    this.reset();
    this.context.$v.$reset();
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
    this.remindingTime = remindingTime;
  }

  get remainingSeconds(): number {
    if (!this.remindingTime) return -1;
    const remindingTime = this.remindingTime;
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

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

  saveTime(): void {
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
