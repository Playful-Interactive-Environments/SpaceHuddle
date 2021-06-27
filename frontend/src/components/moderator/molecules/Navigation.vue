<template>
  <nav class="nav">
    <router-link to="/sessions" class="nav__item">
      <img
        src="../../../assets/icons/session-overview.svg"
        alt="session overview icon"
        class="icon icon--m nav__item-icon"
      />
      Sessions
    </router-link>
    <router-link to="/profile" class="nav__item">
      <img
        src="../../../assets/icons/user.svg"
        alt="user icon"
        class="icon icon--m nav__item-icon"
      />
      Profile
    </router-link>
  </nav>
</template>

<script lang="ts">
import { Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

export default class Navigation extends Vue {
  @Prop({ default: true }) hasLogo!: boolean;
}
</script>

<style lang="scss" scoped>
.nav {
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
    }
  }
}

.router-link-active {
  font-weight: bold;
}
</style>
