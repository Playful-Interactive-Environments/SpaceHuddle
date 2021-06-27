<template>
  <section class="sidebar">
    <div class="sidebar__top">
      <div class="sidebar__logo">
        <Logo />
      </div>
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
      <ModuleCount v-if="isSession" />
    </div>
    <div class="sidebar__bottom">
      <SessionCode v-if="isSession" :code="sessionConnectionKey" />
      <div class="sidebar__toggles" v-else>
        <Toggle label="Active" v-if="!(moduleType === ModuleType.SELECTION)" />
        <Toggle
          label="Public Screen"
          :isActive="isOnPublicScreen"
          @toggleClicked="$emit('changePublicScreen')"
        />
      </div>
      <router-link v-if="sessionId" :to="`/public-screen/${sessionId}`">
        <button
          class="btn btn--mint btn--fullwidth"
          :class="{ sidebar__button: !isSession }"
        >
          Public Screen
        </button>
      </router-link>
    </div>
  </section>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { Session } from '@/services/session-service';
import Logo from '@/components/moderator/atoms/Logo.vue';
import ModuleCount from '@/components/moderator/molecules/ModuleCount.vue';
import ModuleType from '../../../types/ModuleType';
import SessionCode from '@/components/moderator/molecules/SessionCode.vue';
import Toggle from '@/components/moderator/atoms/Toggle.vue';

@Options({
  components: {
    Logo,
    ModuleCount,
    SessionCode,
    Toggle,
  },
})
export default class Sidebar extends Vue {
  @Prop({ default: false }) readonly isSession!: Session;
  @Prop({ default: '' }) readonly sessionId!: string;
  @Prop({ default: '' }) readonly sessionConnectionKey!: string;
  @Prop({ default: '' }) readonly title!: string;
  @Prop({ default: '' }) readonly pretitle!: string;
  @Prop({ default: '' }) readonly description!: string;
  @Prop({ default: null }) readonly moduleType!: ModuleType;
  @Prop({ default: false }) readonly isOnPublicScreen!: boolean;

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
  padding: 2rem 2rem 1rem;
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
    transition: background-color 0.2s, opacity 0.5s;

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
