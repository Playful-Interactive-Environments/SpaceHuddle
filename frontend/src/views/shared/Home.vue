<template>
  <Header :hasNavigation="false" />
  <div class="container2 container2--spaced container2--fullheight-header home">
    <section class="home__content">
      <h1 class="heading heading--big">{{ $t("shared.view.home.header") }}</h1>
      <p class="home__text">
        {{ $t("shared.view.home.info") }}
      </p>
      <router-link to="/login" class="home__link">
        <button class="btn btn--gradient">{{ $t("shared.view.home.login") }}</button>
      </router-link>
      <router-link to="/join">
        <button class="btn btn--outline">{{ $t("shared.view.home.join") }}</button>
      </router-link>
    </section>
    <section class="home__img">
      <img
        src="@/assets/illustrations/telescope_round.png"
        alt="telescope-illustration"
      />
    </section>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import Header from '@/components/moderator/organisms/Header.vue';

@Options({
  components: {
    Header,
  },
})
export default class Home extends Vue {}
</script>

<style lang="scss" scoped>
@import '~@/assets/styles/breakpoints.scss';

.home {
  flex: 1;
  align-items: center;
  box-sizing: border-box;
  flex-direction: column-reverse;
  justify-content: center;
  padding: 1rem;
  gap: 1rem;
  text-align: center;

  &__content {
    max-width: 100vw;

    @include md {
      max-width: 35vw;
    }
  }

  &__link {
    margin: 0 auto;

    @include md {
      margin-right: 1rem;
    }
  }

  &__text {
    margin: 0.8rem 0 1.2rem;

    @include md {
      margin: 1.5rem 0 2rem;
    }
  }

  &__img {
    max-width: 75vw;

    @include md {
      max-width: 50vw;
    }
  }

  @include md {
    flex-direction: row;
    justify-content: space-between;
    padding: 2rem 4rem;
    gap: 3rem;
    text-align: left;
  }
}
</style>
