<template>
  <ValidationForm
    :form-data="formData"
    :use-default-submit="false"
    v-on:submitDataValid="save"
    v-on:reset="reset"
  >
    <el-dialog
      v-model="showSettings"
      :title="$t('moderator.organism.settings.linkSettings.link')"
      :before-close="handleClose"
      width="calc(var(--app-width) * 0.8)"
    >
      <el-form-item prop="editLink" :rules="[defaultFormRules.ruleUrl]">
        <span class="layout__level">
          <el-input
            v-model="formData.editLink"
            name="link"
            autocomplete="on"
            :placeholder="
              $t('moderator.organism.settings.linkSettings.linkExample')
            "
          />
          <span style="margin-right: 0">
            <el-button type="primary" native-type="submit" circle>
              <font-awesome-icon icon="check" style="font-size: 1.5rem" />
            </el-button>
          </span>
        </span>
      </el-form-item>
      <el-form-item
        prop="stateMessage"
        :rules="[defaultFormRules.ruleStateMessage]"
      >
      </el-form-item>
    </el-dialog>
  </ValidationForm>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import myUpload from 'vue-image-crop-upload/upload-3.vue';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import { ValidationData } from '@/types/ui/ValidationRule';
import ValidationForm, {
  ValidationFormCall,
} from '@/components/shared/molecules/ValidationForm.vue';
import FromSubmitItem from '@/components/shared/molecules/FromSubmitItem.vue';

@Options({
  components: {
    ValidationForm,
    FromSubmitItem,
    'my-upload': myUpload,
  },
  emits: ['update:showModal', 'update:link', 'imageChanged'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class LinkSettings extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  @Prop({ default: false }) showModal!: boolean;
  @Prop({ default: null }) link!: string;

  formData: ValidationData = {
    editLink: '',
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
    this.formData.editLink = this.link;
    this.formData.call = ValidationFormCall.CLEAR_VALIDATE;
  }

  @Watch('showModal', { immediate: true })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showSettings = showModal;
    this.reset();
  }

  @Watch('link', { immediate: true, deep: true })
  async onLinkChanged(link: string): Promise<void> {
    if (!this.showSettings) {
      this.formData.editLink = link;
    }
  }

  async save(): Promise<void> {
    this.$emit('update:link', this.formData.editLink);
    this.reset();
    this.showSettings = false;
    this.$emit('update:showModal', false);
    this.$emit('imageChanged', null);
  }
}
</script>

<style lang="scss" scoped>
.el-form-item .el-form-item {
  margin-bottom: 1rem;
}
.el-button.is-circle {
  padding: 0.5rem;
}
</style>
