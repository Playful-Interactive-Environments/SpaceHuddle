<template>
  <div class="login container--fullheight">
    <section class="container container--fullheight container--centered">
      <div class="login__content">
        <h1 class="heading heading--medium">Login</h1>
        <p class="login__description">
          Go ahead and login to access all your existing resources create new
          brainstorming sessions.
        </p>
        <form @submit.prevent="loginUser">
          <h3 class="heading heading--xs">Email</h3>
          <input
            class="input input--fullwidth"
            name="email"
            placeholder="Enter your email"
            type="email"
            v-model="email"
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
            v-model="password"
            @blur="context.$v.password.$touch()"
          />
          <FormError
            v-if="context.$v.password.$error"
            :errors="context.$v.password.$errors"
            :isSmall="true"
          />
          <button
            class="btn btn--wide btn--gradient btn--fullwidth"
            type="submit"
          >
            Login
          </button>
          <p class="login__forgot-pw" role="button">Forgot Password?</p>
        </form>
      </div>
    </section>
    <section
      class="
        login__bg-section
        container container--fullheight container--centered
      "
    >
      <div class="container--centered">
        <h2 class="heading heading--medium heading--white">New here?</h2>
        <p class="login__text">
          Go ahead and create an account if you want to use our great
          brainstorming tool.
        </p>
        <router-link to="register">
          <button class="btn btn--outline-white">Register</button>
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
import * as authService from '@/services/moderator/auth-service';
import * as userService from '@/services/moderator/user-service';

@Options({
  components: {
    FormError,
  },
  validations: {
    email: {
      // email,
      required,
    },
    password: {
      required,
      min: minLength(8),
      max: maxLength(12),
    },
  },
})
export default class ModeratorLogin extends Vue {
  email = '';
  password = '';

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

  async loginUser(): Promise<void> {
    await this.context.$v.$validate();
    if (this.context.$v.$error) return;

    const data = await userService.loginUser(this.email, this.password);

    if (data.accessToken) {
      authService.setAccessToken(data.accessToken);
      console.log(authService.getAccessToken());
      this.$router.push({
        name: 'moderator-session-overview',
      });
    } else {
      // TODO: error snackbar if something went wrong
    }
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
