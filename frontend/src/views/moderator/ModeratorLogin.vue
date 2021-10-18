<template>
  <div class="login container2--fullheight">
    <section class="container2 container2--fullheight container2--centered">
      <div class="login__content">
        <h1 class="heading heading--medium">
          {{ $t('moderator.view.login.header') }}
        </h1>
        <p class="login__description">
          {{ $t('moderator.view.login.info') }}
        </p>
        <ValidationForm
          :form-data="formData"
          submit-label-key="moderator.view.login.submit"
          v-on:submitDataValid="save"
        >
          <el-form-item
            :label="$t('moderator.view.login.email')"
            prop="email"
            :rules="[defaultFormRules.ruleRequired, defaultFormRules.ruleEmail]"
          >
            <el-input
              v-model="formData.email"
              type="email"
              name="email"
              autocomplete="on"
              :placeholder="$t('moderator.view.login.emailInfo')"
            />
          </el-form-item>
          <el-form-item
            :label="$t('moderator.view.login.password')"
            prop="password"
            :rules="[
              defaultFormRules.ruleRequired,
              defaultFormRules.ruleToShort(8),
              defaultFormRules.ruleToLong(255),
            ]"
          >
            <el-input
              v-model="formData.password"
              type="password"
              name="password"
              :placeholder="$t('moderator.view.login.passwordInfo')"
            ></el-input>
          </el-form-item>
          <template #afterSubmit>
            <el-form-item>
              <p class="login__forgot-pw" role="button">
                {{ $t('moderator.view.login.forgot') }}
              </p>
            </el-form-item>
          </template>
        </ValidationForm>
      </div>
    </section>
    <section
      class="
        login__bg-section
        container2 container2--fullheight container2--centered
      "
    >
      <div class="container2--centered">
        <h2 class="heading heading--medium heading--white">
          {{ $t('moderator.view.login.register.header') }}
        </h2>
        <p class="login__text">
          {{ $t('moderator.view.login.register.info') }}
        </p>
        <router-link to="register">
          <button class="btn btn--outline btn--outline--white">
            {{ $t('moderator.view.login.register.submit') }}
          </button>
        </router-link>
      </div>
    </section>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import * as authService from '@/services/auth-service';
import * as userService from '@/services/user-service';
import { getSingleTranslatedErrorMessage } from '@/services/exception-service';
import { ValidationData } from '@/types/ui/ValidationRule';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import ValidationForm from '@/components/shared/molecules/ValidationForm.vue';

@Options({
  components: {
    ValidationForm,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorLogin extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  formData: ValidationData = {
    email: '',
    password: '',
  };

  async save(): Promise<void> {
    await userService
      .loginUser(this.formData.email, this.formData.password)
      .then(
        (queryResult) => {
          if (queryResult.accessToken) {
            authService.setAccessTokenModerator(queryResult.accessToken);
            authService.setUserData(this.formData.email);
            this.$router.push({
              name: 'moderator-session-overview',
            });
          }
        },
        (error) => {
          this.formData.stateMessage = getSingleTranslatedErrorMessage(error);
        }
      );
  }
}
</script>

<style lang="scss" scoped>
.login {
  display: grid;
  grid-template-columns: 5fr 3fr;
  padding: 0;
  margin: 0;

  &__bg-section {
    color: #fff;
    background-color: var(--color-darkblue);
    background-image: url('~@/assets/illustrations/bg_without_telescope.png');
    background-position: center;
    background-size: cover;
  }

  &__content {
    width: 35vw;
    max-width: 520px;
    text-align: left;
  }

  &__description {
    line-height: 1.5;
    margin: 1.5rem 0 1rem;
  }

  &__text {
    line-height: 1.5;
    padding: 0 2rem;
  }

  &__forgot-pw {
    text-align: center;
    cursor: pointer;
  }
}
</style>
