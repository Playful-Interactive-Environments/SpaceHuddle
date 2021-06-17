<template>
  <div class="grid-container container--fullheight">
    <div class="grid-item container--fullheight background-image">
      <div class="inline center container--centered">
        <h2>Welcome back!</h2>
        <p class="center-text">
          Voluptua at vero eos et accusam et justo duo dolores et ea rebum. Stet
          clita gergren.
        </p>
        <button class="btn btn--outline-white">Login</button>
      </div>
    </div>
    <div class="grid-item container container--centered center">
      <div class="inline">
        <h1 class="heading heading--regular">Register</h1>
        <p>
          Voluptua at vero eos et accusam et justo duo dolores et ea rebum. Stet
          clita kasd gubergren.
        </p>
        <div class="inline">
          <h4>Email</h4>
          <input name="email" placeholder="Enter your email" type="text" />

          <h4>Password</h4>
          <input
            name="password"
            placeholder="Enter your password"
            type="text"
          />
          <h4>Password repeat</h4>
          <input
            name="password"
            placeholder="Repeat your password"
            type="text"
          />
        </div>
        <form-error :errors="errors"></form-error>
        <form @submit="submit">
          <button class="btn btn--wide btn--gradient" type="submit">
            Register
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Vue } from 'vue-class-component';

export default class Register extends Vue {}
</script>

<style scoped>
.center {
  margin: auto;
}

.background-image {
  color: #fff;
  background-image: url('../../assets/illustrations/telescope.png');
  background-position: center;
  background-size: cover;
  display: flex;
  align-items: stretch;
}

.grid-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  padding: 0;
  margin: 0;
}

p {
  width: 300px;
  text-align: left;
}
h1 {
  text-align: left;
}

h4 {
  padding: 0;
  margin-bottom: 0;
  margin-left: 5px;
  text-align: left;
}
.inline {
  display: inline-block;
}
</style>
