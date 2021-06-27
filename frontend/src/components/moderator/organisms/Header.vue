<template>
  <header class="header">
    <Logo class="header__logo" />
    <Navigation />
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
}
</script>

<style lang="scss" scoped>
.header {
  display: flex;
  justify-content: space-between;
  width: 100%;

  &__logo {
    padding: 2em;
  }
}
</style>
