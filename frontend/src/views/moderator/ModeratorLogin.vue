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
        <form @submit.prevent="loginUser">
          <h3 class="heading heading--xs">
            {{ $t('moderator.view.login.email') }}
          </h3>
          <input
            class="input input--fullwidth"
            name="email"
            :placeholder="$t('moderator.view.login.emailInfo')"
            type="email"
            v-model="email"
            @blur="context.$v.email.$touch()"
          />
          <FormError
            v-if="context.$v.email.$error"
            :errors="context.$v.email.$errors"
            :isSmall="true"
          />
          <h3 class="heading heading--xs">
            {{ $t('moderator.view.login.password') }}
          </h3>
          <input
            class="input input--fullwidth"
            name="password"
            :placeholder="$t('moderator.view.login.passwordInfo')"
            type="password"
            v-model="password"
            @blur="context.$v.password.$touch()"
          />
          <FormError
            v-if="context.$v.password.$error"
            :errors="context.$v.password.$errors"
            :isSmall="true"
          />
          <form-error :errors="errors"></form-error>
          <button class="btn btn--gradient btn--fullwidth" type="submit">
            {{ $t('moderator.view.login.submit') }}
            Login
          </button>
          <p class="login__forgot-pw" role="button">
            {{ $t('moderator.view.login.forgot') }}
          </p>
        </form>
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
          <button class="btn btn--outline-white">
            {{ $t('moderator.view.login.register.submit') }}
          </button>
        </router-link>
      </div>
    </section>
  </div>
</template>

<script lang="ts">
import { Options, Vue, setup } from 'vue-class-component';
import { maxLength, minLength, required, email } from '@vuelidate/validators';
import useVuelidate from '@vuelidate/core';
import FormError from '@/components/shared/atoms/FormError.vue';
import * as authService from '@/services/auth-service';
import * as userService from '@/services/user-service';
import {
  addError,
  clearErrors,
  getErrorMessage,
} from '@/services/exception-service';

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
    },
  },
})
export default class ModeratorLogin extends Vue {
  email = '';
  password = '';
  errors: string[] = [];

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

  async loginUser(): Promise<void> {
    clearErrors(this.errors);

    await this.context.$v.$validate();
    if (this.context.$v.$error) return;

    userService.loginUser(this.email, this.password).then(
      (queryResult) => {
        if (queryResult.accessToken) {
          authService.setAccessTokenModerator(queryResult.accessToken);
          authService.setUserData(this.email);
          this.$router.push({
            name: 'moderator-session-overview',
          });
        }
      },
      (error) => {
        addError(this.errors, getErrorMessage(error));
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

  button {
    margin: 1.5rem 0 1rem;
  }

  input {
    margin-top: 0;
  }
}
</style>
