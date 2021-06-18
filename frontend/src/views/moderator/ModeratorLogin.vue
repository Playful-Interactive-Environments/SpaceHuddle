<template>
  <div class="login container--fullheight">
    <section class="container container--fullheight container--centered">
      <div class="login__content">
        <h1 class="heading heading--medium">Login</h1>
        <p class="login__description">
          Go ahead and login to access all your existing resources create new
          brainstorming sessions.
        </p>
        <form @submit="submit">
          <h3 class="heading heading--xs">Email</h3>
          <input
            class="input input--fullwidth"
            name="email"
            placeholder="Enter your email"
            type="text"
          />
          <h3 class="heading heading--xs">Password</h3>
          <input
            class="input input--fullwidth"
            name="password"
            placeholder="Enter your password"
            type="text"
          />
          <form-error :errors="errors"></form-error>
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
import { Options, Vue } from 'vue-class-component';
import FormError from '../../components/shared/atoms/FormError.vue';

@Options({
  components: {
    FormError,
  },
})
export default class ModeratorLogin extends Vue {}
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
