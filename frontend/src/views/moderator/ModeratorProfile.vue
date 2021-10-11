<template>
  <ModeratorNavigationLayout>
    <template v-slot:content>
      <div
        class="
          profile__content
          container2
          container2--spaced
          container2--fullheight-header
          container2--centered
        "
      >
        <h1>{{ $t('moderator.view.profile.header') }}</h1>
        <p class="profile__email">
          {{ $t('moderator.view.profile.info') }} {{ email }}
        </p>
        <button class="btn btn--gradient" @click="logout">
          {{ $t('moderator.view.profile.submit') }}
        </button>
      </div>
    </template>
  </ModeratorNavigationLayout>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import * as authService from '@/services/auth-service';
import ModeratorNavigationLayout from '@/components/moderator/organisms/Layout/ModeratorNavigationLayout.vue';

@Options({
  components: {
    ModeratorNavigationLayout,
  },
})
export default class ModeratorProfile extends Vue {
  email = '';
  errors: string[] = [];

  mounted(): void {
    this.email = authService.getUserData() || '';
  }

  logout(): void {
    authService.removeAccessToken();
    this.$router.push({ name: 'home' });
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
