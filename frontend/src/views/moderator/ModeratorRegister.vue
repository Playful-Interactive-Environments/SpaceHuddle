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
          <h4 class="heading heading--xs">Password repeat</h4>
          <input
            class="input input--fullwidth"
            name="password"
            placeholder="Repeat your password"
            type="text"
          />
          <form-error :errors="errors"></form-error>
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
import { Vue } from 'vue-class-component';

export default class ModeratorRegister extends Vue {}
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
