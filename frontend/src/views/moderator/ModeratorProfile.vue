<template>
  <div class="profile">
    <Header />
    <div
      class="
        profile__content
        container
        container--spaced
        container--fullheight-header
        container--centered
      "
    >
      <h1>Your Profile</h1>
      <p class="profile__email">Email Address: {{ email }}</p>
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
  email = '';

  mounted(): void {
    this.email = authService.getUserData() || '';
  }

  async logout(): Promise<void> {
    authService.removeAccessToken();
    await this.$router.push({ name: 'home' });
  }
}
</script>

<style lang="scss" scoped>
.profile {
  background-color: var(--color-background-gray);

  h1 {
    margin-top: 0;
  }

  &__content {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding-bottom: 3rem;
  }

  &__email {
    margin-bottom: 2rem;
  }
}
</style>
