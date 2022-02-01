<template>
  <el-form
    ref="dataForm"
    :model="formData"
    label-position="top"
    :status-icon="true"
    v-on:validate="formItemValidated"
    v-on:submit="submitData"
    v-on:reset="reset"
  >
    <slot></slot>
    <FromSubmitItem
      v-if="useDefaultSubmit"
      :form-state-message="formData.stateMessage"
      :submit-label-key="submitLabelKey"
    />
    <slot name="afterSubmit"></slot>
  </el-form>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { ElForm } from 'element-plus';
import { ValidationData } from '@/types/ui/ValidationRule';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import FromSubmitItem from '@/components/shared/molecules/FromSubmitItem.vue';
import ValidationMethods from '@/types/ui/ValidationMethods';

export enum ValidationFormCall {
  RESET = 'reset',
  CLEAR_VALIDATE = 'clear_validate',
  SUBMIT_DATA = 'submit_data',
}

@Options({
  components: {
    FromSubmitItem,
  },
  emits: ['submitDataValid', 'reset'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ValidationForm extends Vue implements ValidationMethods {
  @Prop({ default: { stateMessage: '' } }) formData!: ValidationData;
  @Prop({ default: 'submit' }) submitLabelKey!: string;
  @Prop({ default: true }) useDefaultSubmit!: boolean;
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  submitCheckActive = false;

  dataForm(): typeof ElForm {
    return this.$refs.dataForm as typeof ElForm;
  }

  formItemValidated(prop: string): void {
    if (!this.submitCheckActive && prop != 'stateMessage') {
      this.formData.stateMessage = '';
      if (this.dataForm()) this.dataForm().validateField('stateMessage');
    }
  }

  @Watch('formData', { immediate: true, deep: true })
  onFormDataChanged(value: ValidationData): void {
    if (!('stateMessage' in value)) value.stateMessage = '';
    if (!('call' in value)) value.call = '';

    if (value.call.length > 0) {
      switch (value.call) {
        case ValidationFormCall.RESET:
          this.reset();
          break;
        case ValidationFormCall.CLEAR_VALIDATE:
          this.clearValidate();
          break;
        case ValidationFormCall.SUBMIT_DATA:
          this.submitData();
          break;
      }
      value.call = '';
    }
  }

  public clearValidate(): void {
    this.dataForm()?.clearValidate();
  }

  public reset(): void {
    this.$emit('reset');
    this.clearValidate();
  }

  public async submitData(event: Event | null = null): Promise<void> {
    this.submitCheckActive = true;
    if (event) event.preventDefault();
    this.formData.stateMessage = '';
    const form = this.dataForm();
    form?.validate(async (valid, fields) => {
      if (valid) {
        this.$emit('submitDataValid');
      } else {
        this.formData.stateMessage = (this as any).$t(
          'error.vuelidate.validationErrors'
        );
      }
    });
    this.submitCheckActive = false;
  }

  public validateField(field: string): void {
    const form = this.dataForm();
    form?.validateField(field);
  }

  public fieldValue(field: string): any {
    return eval(`this.formData.${field}`);
  }
}
</script>

<style lang="scss" scoped></style>
