<template>
  <el-dialog
    v-model="showSettings"
    :before-close="handleClose"
    width="calc(var(--app-width) * 0.8)"
  >
    <template #header>
      <span class="el-dialog__title">
        {{ $t('moderator.organism.settings.languageSettings.header') }}
      </span>
      <br />
      <br />
      <p>
        {{ $t('moderator.organism.settings.languageSettings.info') }}
      </p>
    </template>
    <ValidationForm
      :form-data="formData"
      :use-default-submit="false"
      v-on:submitDataValid="save"
      v-on:reset="reset"
    >
      <el-form-item prop="editLink" :rules="[defaultFormRules.ruleUrl]">
        <span class="layout__level">
          <el-select v-model="formData.locale">
            <el-option value="en" :label="$t('enum.locale.en')"></el-option>
            <el-option value="de" :label="$t('enum.locale.de')"></el-option>
          </el-select>
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
    </ValidationForm>
  </el-dialog>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { ValidationData } from '@/types/ui/ValidationRule';
import ValidationForm from '@/components/shared/molecules/ValidationForm.vue';
import FromSubmitItem from '@/components/shared/molecules/FromSubmitItem.vue';
import * as authService from '@/services/auth-service';
import { defaultFormRules, ValidationRuleDefinition } from '@/utils/formRules';
import * as participantService from '@/services/participant-service';
import * as userService from '@/services/user-service';

@Options({
  components: {
    ValidationForm,
    FromSubmitItem,
  },
  emits: ['update:showModal'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class LanguageSettings extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  @Prop({ default: false }) showModal!: boolean;

  showSettings = false;

  formData: ValidationData = {
    locale: 'en',
  };

  getDefaultLanguage(): string {
    const storedLanguage = authService.getLocale();
    if (!storedLanguage) {
      const language = navigator.language.substring(0, 2);
      return language && ['en', 'de'].includes(language)
        ? language
        : process.env.VUE_APP_I18N_LOCALE || 'en';
    }
    return storedLanguage;
  }

  mounted(): void {
    this.reset();
  }

  handleClose(done: { (): void }): void {
    this.reset();
    done();
    this.$emit('update:showModal', false);
  }

  reset(): void {
    this.formData.locale = this.getDefaultLanguage();
  }

  @Watch('showModal', { immediate: true })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showSettings = showModal;
    this.reset();
  }

  async save(): Promise<void> {
    authService.setLocale(this.formData.locale);
    this.$i18n.locale = this.formData.locale;
    if (authService.isParticipant()) {
      await participantService.changeParameter({
        locale: this.formData.locale,
      });
    } else {
      await userService.changeParameter({
        locale: this.formData.locale,
      });
    }
    this.reset();
    this.showSettings = false;
    this.$emit('update:showModal', false);
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
