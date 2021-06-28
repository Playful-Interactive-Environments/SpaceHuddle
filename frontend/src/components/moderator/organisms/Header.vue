<template>
  <header class="header">
    <Logo class="header__logo" />
    <Navigation :white="white" />
  </header>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import Logo from '@/components/moderator/atoms/Logo.vue';
import Navigation from '@/components/moderator/molecules/Navigation.vue';

@Options({
  components: {
    Logo,
    Navigation,
  },
})
export default class Header extends Vue {
  @Prop({ default: true }) hasLogo!: boolean;
  @Prop({ default: false }) white!: boolean;
}
</script>

<style lang="scss" scoped>
.header {
  height: var(--header-height);
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;

  &__logo {
    padding: 0 2em;
  }
}
</style>
