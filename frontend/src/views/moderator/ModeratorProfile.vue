<template>
  <div class="profile">
    <Header />
    <div class="container container--spaced container--fullheight">
      <h1>Your Profile</h1>
      <button class="btn btn--gradient" @click="logout">Logout</button>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import Header from '@/components/moderator/organisms/Header.vue';
import * as authService from '@/services/auth-service';

@Options({
  components: {
    Header,
  },
})
export default class ModeratorProfile extends Vue {
  async logout(): Promise<void> {
    authService.removeAccessToken();
    await this.$router.push({ name: 'home' });
  }
}
</script>

<style scoped>
.profile {
  background-color: var(--color-background-gray);
}
</style>
