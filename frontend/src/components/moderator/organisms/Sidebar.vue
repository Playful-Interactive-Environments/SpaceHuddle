<template>
  <section class="sidebar">
    <div class="sidebar__top">
      <div class="sidebar__logo"></div>
      <div class="sidebar__row">
        <div>24.06.2021</div>
        <div class="sidebar__icon" aria-label="settings" role="button"></div>
      </div>
      <h1 class="heading heading--regular heading--white">Marketing Meeting</h1>
      <div class="sidebar__text">
        Short description about the goals of this session and other stuff ...
      </div>
      <ModuleCount />
    </div>
    <div class="sidebar__bottom">
      <SessionCode />
      <button class="btn btn--mint btn--fullwidth">Public Screen</button>
    </div>
  </section>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import SessionCode from '@/components/moderator/molecules/SessionCode.vue';
import ModuleCount from '@/components/moderator/molecules/ModuleCount.vue';

@Options({
  components: {
    ModuleCount,
    SessionCode,
  },
})
export default class Sidebar extends Vue {}
</script>

<style lang="scss" scoped>
.sidebar {
  background-color: var(--color-darkblue);
  width: 25vw;
  min-width: 300px;
  min-height: 100vh;
  height: 100vh;
  padding: 1rem 2rem;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  color: white;
  line-height: 1.4;

  &__logo {
    width: 100%;
    height: 2rem;
    margin-bottom: 2rem;
  }

  &__row {
    display: flex;
    justify-content: space-between;
    color: var(--color-darkblue-light);
    margin-bottom: 1rem;
  }

  &__icon {
    width: 20px;
    height: auto;
    cursor: pointer;
    mask-image: url(../../../assets/icons/settings.svg);
    mask-repeat: no-repeat;
    background-color: var(--color-darkblue-light);
    transition: background-color 0.2s opacity 0.5s;

    &:hover {
      background-color: white;
      opacity: 0.7;
    }
  }

  &__text {
    margin-bottom: 1.5rem;
  }
}
</style>
