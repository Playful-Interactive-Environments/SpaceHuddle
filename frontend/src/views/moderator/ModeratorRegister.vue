<template>
  <div class="register container--fullheight">
    <section
      class="
        register__bg-section
        container container--fullheight container--centered
      "
    >
      <h2 class="heading heading--medium heading--white">Welcome back!</h2>
      <p class="register__text">
        If you already have an account, just go ahead and login to access all
        your existing resources.
      </p>
      <router-link to="login">
        <button class="btn btn--outline-white">Login</button>
      </router-link>
    </section>
    <section class="container container--fullheight container--centered">
      <div class="register__content">
        <h1 class="heading heading--medium">Register</h1>
        <p class="register__description">
          Please enter your personal info to create an account.
        </p>
        <form @submit.prevent="registerUser">
          <h3 class="heading heading--xs">Email</h3>
          <input
            class="input input--fullwidth"
            name="email"
            placeholder="Enter your email"
            type="email"
            v-model.trim="context.$v.email.$model"
            @blur="context.$v.email.$touch()"
          />
          <FormError
            v-if="context.$v.email.$error"
            :errors="context.$v.email.$errors"
            :isSmall="true"
          />
          <h3 class="heading heading--xs">Password</h3>
          <input
            class="input input--fullwidth"
            name="password"
            placeholder="Enter your password"
            type="password"
            v-model.trim="context.$v.password.$model"
            @blur="context.$v.password.$touch()"
          />
          <FormError
            v-if="context.$v.password.$error"
            :errors="context.$v.password.$errors"
            :isSmall="true"
          />
          <h4 class="heading heading--xs">Password repeat</h4>
          <input
            class="input input--fullwidth"
            name="passwordRepeat"
            placeholder="Repeat your password"
            type="password"
            v-model.trim="context.$v.passwordRepeat.$model"
            @blur="context.$v.passwordRepeat.$touch()"
          />
          <FormError
            v-if="context.$v.passwordRepeat.$error"
            :errors="context.$v.passwordRepeat.$errors"
            :isSmall="true"
          />
          <button
            class="btn btn--wide btn--gradient btn--fullwidth"
            type="submit"
          >
            Register
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
  sameAs,
  helpers,
} from '@vuelidate/validators';
import useVuelidate from '@vuelidate/core';
import FormError from '@/components/shared/atoms/FormError.vue';

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
      max: maxLength(12),
      containsUppercase: function (value: string) {
        return !/[A-Z]/.test(value);
      },
      containsLowercase: function (value: string) {
        return !/[a-z]/.test(value);
      },
      containsNumber: function (value: string) {
        return !/[0-9]/.test(value);
      },
      containsSpecial: function (value: string) {
        return !/[#?!@$%^&*-]/.test(value);
      },
    },
    passwordRepeat: {
      required,
      min: minLength(8),
      max: maxLength(12),
      sameAsPassword: sameAs('password'),
    },
  },
})
export default class ModeratorRegister extends Vue {
  email = '';
  password = '';
  passwordRepeat = '';

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

  async registerUser(): Promise<void> {
    await this.context.$v.$validate();
    if (this.context.$v.$error) return;
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
    background-image: url('../../assets/illustrations/bg_without_telescope.png');
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
