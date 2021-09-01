<template>
  <div class="register container2--fullheight">
    <section
      class="
        register__bg-section
        container2 container2--fullheight container2--centered
      "
    >
      <h2 class="heading heading--medium heading--white">{{ $t("moderator.view.register.login.header") }}</h2>
      <p class="register__text">
        {{ $t("moderator.view.register.login.info") }}
      </p>
      <router-link to="login">
        <button class="btn btn--outline-white">{{ $t("moderator.view.register.login.submit") }}</button>
      </router-link>
    </section>
    <section class="container2 container2--fullheight container2--centered">
      <div class="register__content">
        <h1 class="heading heading--medium">{{ $t("moderator.view.register.header") }}</h1>
        <p class="register__description">
          {{ $t("moderator.view.register.info") }}

        </p>
        <form @submit.prevent="registerUser">
          <h3 class="heading heading--xs">{{ $t("moderator.view.register.email") }}</h3>
          <input
            class="input input--fullwidth"
            name="email"
            :placeholder="$t('moderator.view.register.emailInfo')"
            type="email"
            v-model.trim="email"
            autocomplete="email"
            @blur="context.$v.email.$touch()"
          />
          <FormError
            v-if="context.$v.email.$error"
            :errors="context.$v.email.$errors"
            :isSmall="true"
          />
          <h3 class="heading heading--xs">{{ $t("moderator.view.register.password") }}</h3>
          <input
            class="input input--fullwidth"
            name="password"
            :placeholder="$t('moderator.view.register.passwordInfo')"
            type="password"
            autocomplete="new-password"
            v-model.trim="password"
            @blur="context.$v.password.$touch()"
          />
          <FormError
            v-if="context.$v.password.$error"
            :errors="context.$v.password.$errors"
            :isSmall="true"
          />
          <h4 class="heading heading--xs">{{ $t("moderator.view.register.passwordConform") }}</h4>
          <input
            class="input input--fullwidth"
            name="passwordRepeat"
            :placeholder="$t('moderator.view.register.passwordConformInfo')"
            type="password"
            autocomplete="new-password"
            v-model.trim="passwordRepeat"
            @blur="context.$v.passwordRepeat.$touch()"
          />
          <FormError
            v-if="
              context.$v.passwordRepeat.$error ||
              (context.$v.passwordRepeat.$dirty && hasMatchingPasswords)
            "
            :errors="
              context.$v.passwordRepeat.$error
                ? context.$v.passwordRepeat.$errors
                : { passwordRepeatMsg }
            "
            :isSmall="true"
          />
          <form-error :errors="errors"></form-error>
          <button class="btn btn--gradient btn--fullwidth" type="submit">
            {{ $t("moderator.view.register.submit") }}
          </button>
        </form>
      </div>
    </section>
  </div>
</template>

<script lang="ts">
import { Options, Vue, setup } from 'vue-class-component';
import {
  maxLength,
  minLength,
  required,
  email,
  helpers,
} from '@vuelidate/validators';
import useVuelidate from '@vuelidate/core';
import FormError from '@/components/shared/atoms/FormError.vue';
import States from '@/types/enum/States';
import SnackbarType from '@/types/enum/SnackbarType';
import * as userService from '@/services/user-service';
import { EventType } from '@/types/enum/EventType';
import {
  getErrorMessage,
  addError,
  clearErrors,
} from '@/services/exception-service';
import * as taskService from "@/services/task-service";
import {setModuleStyles} from "@/utils/moduleStyles";
import * as sessionService from "@/services/session-service";

@Options({
  components: {
    FormError,
  },
  validations: {
    email: {
      email,
      required,
    },
    password: {
      required,
      min: minLength(8),
      max: maxLength(255),
      containsUppercase: helpers.withMessage(
        'wrongPasswordSyntax',
        (value) => {
          return (
            /[A-Z]/.test(value as string) &&
            /[a-z]/.test(value as string) &&
            /[0-9]/.test(value as string) &&
            /[#?!@$%^&*-]/.test(value as string)
          );
        }
      ),
    },
    passwordRepeat: {
      required,
      min: minLength(8)
    },
  },
})
export default class ModeratorRegister extends Vue {
  email = '';
  password = '';
  passwordRepeat = '';
  readonly passwordRepeatMsg = 'error.vuelidate.passwordNotMatch';
  errors: string[] = [];

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

  async registerUser(): Promise<void> {
    clearErrors(this.errors);
    await this.context.$v.$validate();
    if (this.context.$v.$error || this.hasMatchingPasswords) return;


    userService
      .registerUser(
        this.email,
        this.password,
        this.passwordRepeat
      )
      .then(
        () => {
          this.$router.push({
            name: 'moderator-login',
          });
          this.eventBus.emit(EventType.SHOW_SNACKBAR, {
            type: SnackbarType.SUCCESS,
            message: 'info.accountCreated',
          });
        },
        (error) => {
          addError(this.errors, getErrorMessage(error));
        }
      );
  }

  get hasMatchingPasswords(): boolean {
    return this.password !== this.passwordRepeat;
  }
}
</script>

<style lang="scss" scoped>
.register {
  display: grid;
  grid-template-columns: 3fr 5fr;
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

  &__forgot-pw {
    text-align: center;
    cursor: pointer;
  }

  button {
    margin: 1.5rem 0 1rem;
  }

  input {
    margin-top: 0;
  }
}
</style>
