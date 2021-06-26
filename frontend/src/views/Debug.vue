<template>
  <div class="container--centered">
    <h1>Debug Page</h1>
    <div class="container--centered">
      <label for="jwt">Custom JWT</label>
      <input v-model="jwt" id="jwt" type="text" class="input" />
      <button class="btn" @click="setJwt">Fake Login 🚀</button>
    </div>
  </div>
</template>
<script lang="ts">
import { Vue } from 'vue-class-component';
import { setAccessToken } from '@/services/auth-service';
export default class Debug extends Vue {
  jwt = '';

  setJwt(): void {
    const isValidToken = this.jwt.match('Bearer');
    if (!isValidToken) {
      this.jwt = `Bearer ${this.jwt}`;
    }
    alert('Congratulations, your token is set! Let the adventures begin!');
    setAccessToken(this.jwt);
  }
}
</script>
