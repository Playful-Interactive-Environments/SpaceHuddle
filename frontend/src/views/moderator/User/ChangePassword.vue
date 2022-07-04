<template>
  <ModeratorNavigationLayout
    :currentRouteTitle="$t('moderator.view.changePassword.header')"
  >
    <template v-slot:content>
      <div class="confirm__content full-height-header">
        <h1>{{ $t('moderator.view.changePassword.header') }}</h1>
        <p class="profile__email">
          {{ $t('moderator.view.changePassword.info') }} {{ email }}
        </p>
        <ValidationForm
          :form-data="formData"
          submit-label-key="moderator.view.changePassword.submit"
          v-on:submitDataValid="save"
          ref="dataForm"
        >
          <el-form-item
            :label="$t('moderator.view.changePassword.oldPassword')"
            prop="oldPassword"
            :rules="[
              defaultFormRules.ruleRequired,
              defaultFormRules.rulePassword,
              defaultFormRules.ruleToShort(8),
              defaultFormRules.ruleToLong(255),
            ]"
          >
            <el-input
              type="password"
              :placeholder="$t('moderator.view.changePassword.oldPasswordInfo')"
              v-model="formData.oldPassword"
            />
          </el-form-item>
          <el-form-item
            :label="$t('moderator.view.changePassword.newPassword')"
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
              :placeholder="$t('moderator.view.changePassword.newPasswordInfo')"
              v-model="formData.newPassword"
            />
          </el-form-item>
          <el-form-item
            :label="$t('moderator.view.changePassword.passwordConform')"
            prop="passwordRepeat"
            :rules="[
              defaultFormRules.ruleRequired,
              defaultFormRules.rulePassword,
              defaultFormRules.ruleToShort(8),
              defaultFormRules.ruleToLong(255),
              defaultFormRules.ruleMatch(
                formData.newPassword,
                'passwordNotMatch'
              ),
            ]"
          >
            <el-input
              type="password"
              :placeholder="
                $t('moderator.view.changePassword.passwordConformInfo')
              "
              v-model="formData.passwordRepeat"
            />
          </el-form-item>
        </ValidationForm>
      </div>
    </template>
  </ModeratorNavigationLayout>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import ValidationForm from '@/components/shared/molecules/ValidationForm.vue';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import ValidationMethods from '@/types/ui/ValidationMethods';
import { ValidationData } from '@/types/ui/ValidationRule';
import * as userService from '@/services/user-service';
import * as authService from '@/services/auth-service';
import { getSingleTranslatedErrorMessage } from '@/services/exception-service';
import ModeratorNavigationLayout from '@/components/moderator/organisms/layout/ModeratorNavigationLayout.vue';

@Options({
  components: {
    ValidationForm,
    ModeratorNavigationLayout,
  },
})
export default class ChangePassword extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  dataForm(): ValidationMethods {
    return this.$refs.dataForm as ValidationMethods;
  }

  formData: ValidationData = {
    oldPassword: '',
    newPassword: '',
    passwordRepeat: '',
  };

  email = '';

  mounted(): void {
    this.email = authService.getUserData() || '';
  }

  async save(): Promise<void> {
    await userService
      .changePassword(
        this.formData.oldPassword,
        this.formData.newPassword,
        this.formData.passwordRepeat
      )
      .then(
        () => {
          this.$router.push({ name: 'moderator-profile' });
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

      .el-input {
        --el-input-background-color: white;
      }
    }
  }

  &__email {
    margin-bottom: 2rem;
  }
}
</style>
