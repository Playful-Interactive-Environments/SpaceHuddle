<template>
  <ValidationForm
    :form-data="formData"
    :use-default-submit="false"
    v-on:submitDataValid="save"
    v-on:reset="reset"
  >
    <el-dialog
      v-model="showSettings"
      :title="$t('shared.organism.game.levelSettings.levelName')"
      :before-close="handleClose"
      width="calc(var(--app-width) * 0.8)"
    >
      <el-form-item prop="levelName" :rules="[defaultFormRules.ruleRequired]">
        <span class="layout__level">
          <el-input v-model="formData.levelName" name="levelName" />
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
  },
  emits: ['update:showModal', 'update:levelName', 'saveLevel'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class LevelSettings extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  @Prop({ default: false }) showModal!: boolean;
  @Prop({ default: null }) levelName!: string;

  formData: ValidationData = {
    levelName: '',
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
    this.formData.levelName = this.levelName;
    this.formData.call = ValidationFormCall.CLEAR_VALIDATE;
  }

  @Watch('showModal', { immediate: true })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showSettings = showModal;
    this.reset();
  }

  @Watch('levelName', { immediate: true, deep: true })
  async onLinkChanged(levelName: string): Promise<void> {
    if (!this.showSettings) {
      this.formData.levelName = levelName;
    }
  }

  async save(): Promise<void> {
    const levelName = this.formData.levelName;
    this.$emit('update:levelName', levelName);
    this.reset();
    this.showSettings = false;
    this.$emit('update:showModal', false);
    this.$emit('saveLevel', levelName);
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
