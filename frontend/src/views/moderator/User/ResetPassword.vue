<template>
  <div class="confirm__content full-height-header">
    <h1>{{ $t('moderator.view.resetPassword.header') }}</h1>
    <p class="profile__email">
      {{ $t('moderator.view.resetPassword.info') }}
    </p>
    <ValidationForm
      :form-data="formData"
      submit-label-key="moderator.view.resetPassword.submit"
      v-on:submitDataValid="save"
      ref="dataForm"
    >
      <el-form-item
        :label="$t('moderator.view.resetPassword.email')"
        prop="email"
        :rules="[defaultFormRules.ruleRequired, defaultFormRules.ruleEmail]"
      >
        <el-input
          v-model="formData.email"
          type="email"
          name="email"
          autocomplete="on"
          :placeholder="$t('moderator.view.resetPassword.emailInfo')"
        />
      </el-form-item>
    </ValidationForm>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import ValidationForm from '@/components/shared/molecules/ValidationForm.vue';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import { ValidationData } from '@/types/ui/ValidationRule';
import * as userService from '@/services/user-service';
import { Prop } from 'vue-property-decorator';
import { getSingleTranslatedErrorMessage } from '@/services/exception-service';

@Options({
  components: {
    ValidationForm,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ResetPassword extends Vue {
  @Prop() readonly token!: string;
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  formData: ValidationData = {
    email: '',
  };

  async save(): Promise<void> {
    await userService.resetPassword(this.formData.email).then(
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
  background-color: var(--color-background-gray);

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
