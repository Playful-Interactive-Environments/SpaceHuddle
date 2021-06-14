<template>
  <div class="container--fullheight">
    <h1>Login</h1>
    <form>
      <label>
        <input
          name="username"
          v-model="roomCode"
          placeholder="Enter Session PIN"
          type="text"
        />
        <form-error :errors="errors"></form-error>
      </label>
      <button class="btn btn--mint" @click="submit">Join session</button>
    </form>
  </div>
</template>

<script lang="ts">
import { Vue } from 'vue-class-component';
export default class Login extends Vue {}
</script>

<style scoped></style>
