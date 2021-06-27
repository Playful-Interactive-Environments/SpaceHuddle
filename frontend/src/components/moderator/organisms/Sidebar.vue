<template>
  <section class="sidebar">
    <div class="sidebar__top">
      <div class="sidebar__logo"></div>
      <!-- <router-link :to="`/session/${session.id}`"></router-link> -->
      <div class="sidebar__row">
        <div>{{ pretitle }}</div>
        <div class="sidebar__icon" aria-label="settings" role="button"></div>
      </div>
      <h1 class="heading heading--regular heading--white">
        {{ title }}
      </h1>
      <div class="sidebar__text">
        {{ description }}
      </div>
      <ModuleCount v-if="session" />
    </div>
    <div class="sidebar__bottom">
      <SessionCode :code="session.connectionKey" v-if="session" />
      <div class="sidebar__toggles" v-else>
        <Toggle label="Active" v-if="!(moduleType === ModuleType.SELECTION)" />
        <Toggle label="Public Screen" />
      </div>
      <button
        class="btn btn--mint btn--fullwidth"
        :class="{ sidebar__button: !session }"
      >
        Public Screen
      </button>
    </div>
  </section>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { Session } from '@/services/session-service';
import SessionCode from '@/components/moderator/molecules/SessionCode.vue';
import ModuleCount from '@/components/moderator/molecules/ModuleCount.vue';
import Toggle from '@/components/moderator/atoms/Toggle.vue';
import ModuleType from '../../../types/ModuleType';

@Options({
  components: {
    ModuleCount,
    SessionCode,
    Toggle,
  },
})
export default class Sidebar extends Vue {
  @Prop({ default: null }) readonly session?: Session;
  @Prop({ default: '' }) readonly title!: string;
  @Prop({ default: '' }) readonly pretitle!: string;
  @Prop({ default: '' }) readonly description!: string;
  @Prop({ default: null }) readonly moduleType!: ModuleType;

  ModuleType = ModuleType;
}
</script>

<style lang="scss" scoped>
.sidebar {
  background-color: var(--color-darkblue);
  position: fixed;
  left: 0;
  top: 0;
  width: var(--sidebar-width);
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
  box-sizing: border-box;

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
    mask-image: url('../../../assets/icons/settings.svg');
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

  &__toggles {
    margin-bottom: 1rem;
  }

  &__button {
    background-color: var(--module-color);
  }
}
</style>
