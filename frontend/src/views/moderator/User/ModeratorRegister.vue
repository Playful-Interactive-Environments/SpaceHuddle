<template>
  <div class="register">
    <section class="register__bg-section full-height flex-column centered">
      <h2 class="heading heading--medium heading--white">
        {{ $t('moderator.view.register.login.header') }}
      </h2>
      <p class="register__text">
        {{ $t('moderator.view.register.login.info') }}
      </p>
      <router-link to="login">
        <el-button type="info" class="outline">
          {{ $t('moderator.view.register.login.submit') }}
        </el-button>
      </router-link>
    </section>
    <section class="full-height flex-column centered">
      <div class="register__content">
        <h1 class="heading heading--medium">
          {{ $t('moderator.view.register.header') }}
        </h1>
        <p class="register__description">
          {{ $t('moderator.view.register.info') }}
        </p>
        <ValidationForm
          :form-data="formData"
          submit-label-key="moderator.view.register.submit"
          v-on:submitDataValid="save"
          ref="dataForm"
        >
          <!--<el-form-item
            :label="$t('moderator.view.register.prototypeKey')"
            prop="prototypeKey"
            :rules="[
              defaultFormRules.ruleRequired,
              defaultFormRules.ruleMatch('apple-pie', 'keyNotMatch'),
            ]"
          >
            <el-input
              :placeholder="$t('moderator.view.register.prototypeKeyInfo')"
              v-model="formData.prototypeKey"
            />
          </el-form-item>-->
          <el-form-item
            :label="$t('moderator.view.register.email')"
            prop="email"
            :rules="[defaultFormRules.ruleRequired, defaultFormRules.ruleEmail]"
          >
            <el-input
              type="email"
              name="email"
              autocomplete="on"
              :placeholder="$t('moderator.view.register.emailInfo')"
              v-model="formData.email"
            />
          </el-form-item>
          <el-form-item
            :label="$t('moderator.view.register.password')"
            prop="password"
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
              :placeholder="$t('moderator.view.register.passwordInfo')"
              v-model="formData.password"
            />
          </el-form-item>
          <el-form-item
            :label="$t('moderator.view.register.passwordConform')"
            prop="passwordRepeat"
            :rules="[
              defaultFormRules.ruleRequired,
              defaultFormRules.rulePassword,
              defaultFormRules.ruleToShort(8),
              defaultFormRules.ruleToLong(255),
              defaultFormRules.ruleMatch(formData.password, 'passwordNotMatch'),
            ]"
          >
            <el-input
              type="password"
              :placeholder="$t('moderator.view.register.passwordConformInfo')"
              v-model="formData.passwordRepeat"
            />
          </el-form-item>
        </ValidationForm>
      </div>
    </section>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import * as userService from '@/services/user-service';
import { getSingleTranslatedErrorMessage } from '@/services/exception-service';
import { ElMessage } from 'element-plus';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import { ValidationData } from '@/types/ui/ValidationRule';
import ValidationForm from '@/components/shared/molecules/ValidationForm.vue';
import ValidationMethods from '@/types/ui/ValidationMethods';

@Options({
  components: {
    ValidationForm,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorRegister extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  dataForm(): ValidationMethods {
    return this.$refs.dataForm as ValidationMethods;
  }

  formData: ValidationData = {
    email: '',
    password: '',
    passwordRepeat: '',
    //prototypeKey: '',
  };

  async save(): Promise<void> {
    await userService
      .registerUser(
        this.formData.email,
        this.formData.password,
        this.formData.passwordRepeat
      )
      .then(
        () => {
          this.$router.push({
            name: 'moderator-login',
          });
          ElMessage({
            message: (this as any).$t('info.accountCreated'),
            type: 'success',
            center: true,
            showClose: true,
          });
        },
        (error) => {
          this.formData.stateMessage = getSingleTranslatedErrorMessage(error);
        }
      );
  }
}
</script>

<style lang="scss" scoped>
.register {
  display: grid;
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
    padding: 0.5rem 2rem 0;
  }
}

@media only screen and (min-width: 950px) {
  .register {
    grid-template-columns: 3fr 5fr;
  }
}

@media only screen and (max-width: 949px) {
  .register {
    grid-template-rows: 2fr 6fr;

    section {
      min-height: 100%;
    }
  }
}
</style>
