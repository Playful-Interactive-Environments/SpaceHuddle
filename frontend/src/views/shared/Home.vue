<template>
  <div class="container container--fullheight home">
    <section class="home__content">
      <h1 class="heading heading--big">Brainstorm to infinity and beyond!</h1>
      <p class="home__text">
        Spacehuddle takes a new approach to brainstorming with multiple people.
        For creating new brainstorming sessions, use the Moderator tool. You can
        then join an existing brainstorming session via the session code.
      </p>
      <router-link to="/login" class="home__link">
        <button class="btn btn--gradient">Moderator tool</button>
      </router-link>
      <router-link to="/join">
        <button class="btn btn--outline">Join Brainstorming</button>
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
import HelloWorld from '@/components/HelloWorld.vue'; // @ is an alias to /src

@Options({
  components: {
    HelloWorld,
  },
})
export default class Home extends Vue {}
</script>

<style lang="scss" scoped>
.home {
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  box-sizing: border-box;
  padding: 2rem 4rem;
  gap: 3rem;
  flex: 1;

  &__content {
    max-width: 35vw;

    @media screen and (max-width: 768px) {
      max-width: 100vw;
    }
  }

  &__link {
    margin-right: 1rem;

    @media screen and (max-width: 768px) {
      margin: 0 auto;
    }
  }

  &__text {
    margin: 1.5rem 0 2rem;

    @media screen and (max-width: 768px) {
      margin: 0.8rem 0 1.2rem;
    }
  }

  &__img {
    max-width: 50vw;
    @media screen and (max-width: 768px) {
      max-width: 75vw;
    }
  }

  @media screen and (max-width: 768px) {
    flex-direction: column-reverse;
    justify-content: center;
    padding: 1rem;
    gap: 1rem;
    text-align: center;
  }
}
</style>
