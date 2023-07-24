<template>
  <div class="confirm__content full-height-header">
    <h1>{{ $t('moderator.view.forgetPassword.header') }}</h1>
    <p class="profile__email">
      {{ $t('moderator.view.forgetPassword.info') }} {{ email }}
    </p>
    <ValidationForm
      :form-data="formData"
      submit-label-key="moderator.view.forgetPassword.submit"
      v-on:submitDataValid="save"
      ref="dataForm"
    >
      <el-form-item
        :label="$t('moderator.view.forgetPassword.newPassword')"
        prop="newPassword"
        :rules="[
          defaultFormRules.ruleRequired,
          defaultFormRules.rulePassword,
          defaultFormRules.ruleToShort(8),
          defaultFormRules.ruleToLong(255),
          defaultFormRules.ruleTrigger(dataForm, 'passwordRepeat'),
        ]"
      >
        <el-input
          type="password"
          :placeholder="$t('moderator.view.forgetPassword.newPasswordInfo')"
          v-model="formData.newPassword"
        />
      </el-form-item>
      <el-form-item
        :label="$t('moderator.view.forgetPassword.passwordConform')"
        prop="passwordRepeat"
        :rules="[
          defaultFormRules.ruleRequired,
          defaultFormRules.rulePassword,
          defaultFormRules.ruleToShort(8),
          defaultFormRules.ruleToLong(255),
          defaultFormRules.ruleMatch(formData.newPassword, 'passwordNotMatch'),
        ]"
      >
        <el-input
          type="password"
          :placeholder="$t('moderator.view.forgetPassword.passwordConformInfo')"
          v-model="formData.passwordRepeat"
        />
      </el-form-item>
    </ValidationForm>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import ValidationForm from '@/components/shared/molecules/ValidationForm.vue';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import ValidationMethods from '@/types/ui/ValidationMethods';
import { ValidationData } from '@/types/ui/ValidationRule';
import * as userService from '@/services/user-service';
import { Prop } from 'vue-property-decorator';
import jwt_decode from 'jwt-decode';
import { getSingleTranslatedErrorMessage } from '@/services/exception-service';

@Options({
  components: {
    ValidationForm,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ForgetPassword extends Vue {
  @Prop() readonly token!: string;
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  dataForm(): ValidationMethods {
    return this.$refs.dataForm as ValidationMethods;
  }

  formData: ValidationData = {
    newPassword: '',
    passwordRepeat: '',
  };

  email = '';

  mounted(): void {
    const decoded = jwt_decode(this.token) as any;
    this.email = decoded.username;
  }

  async save(): Promise<void> {
    await userService
      .changeForgetPassword(
        this.token,
        this.formData.newPassword,
        this.formData.passwordRepeat
      )
      .then(
        () => {
          this.$router.push({ name: 'moderator-login' });
        },
        (error) => {
          this.formData.stateMessage = getSingleTranslatedErrorMessage(error);
        }
      );
  }
}
</script>

<style lang="scss" scoped>
.confirm {
  background-color: var(--color-background);

  h1 {
    margin-top: 0;
  }

  &__content {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding-bottom: 3rem;

    form {
      margin-top: 3rem;
      width: 100%;
      max-width: 30rem;
    }
  }

  &__email {
    margin-bottom: 2rem;
  }
}
</style>
