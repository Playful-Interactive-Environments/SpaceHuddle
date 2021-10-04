<template>
  <nav
    class="nav"
    :class="{
      'nav--white': white,
    }"
  >
    <router-link to="/sessions" class="nav__item">
      <font-awesome-icon icon="database" class="nav__item-icon" />
      {{ $t('moderator.molecule.navigation.sessions') }}
    </router-link>
    <router-link to="/profile" class="nav__item">
      <font-awesome-icon icon="user" class="nav__item-icon" />
      {{ $t('moderator.molecule.navigation.profile') }}
    </router-link>
  </nav>
</template>

<script lang="ts">
import { Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

export default class Navigation extends Vue {
  @Prop({ default: false }) white!: boolean;
}
</script>

<style lang="scss" scoped>
@import '~@/assets/styles/icons.scss';

.nav {
  height: var(--header-height);
  display: flex;
  justify-content: flex-end;
  align-items: center;
  padding: 0 2em;

  &__item {
    display: flex;
    align-items: center;
    margin-left: 8em;

    &-icon {
      margin-right: 0.5em;

      &--white {
        background-color: #ffffff;
      }
    }
  }

  &--white {
    a {
      color: #ffffff;
    }
  }
}

.router-link-active {
  font-weight: var(--font-weight-bold);
}
</style>
