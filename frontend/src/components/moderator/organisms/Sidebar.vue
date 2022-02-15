<template>
  <el-scrollbar class="sidebar" height="100%" max-height="100vh">
    <el-container class="sidebar__container">
      <el-header>
        <div class="sidebar__top">
          <div class="sidebar__logo">
            <Logo />
          </div>
          <div class="sidebar__management">
            <div>{{ preTitle }}</div>
            <div class="sidebar__icon" aria-label="settings" role="button">
              <span v-on:click="$emit('delete', $event)">
                <font-awesome-icon class="icon" icon="trash" v-if="canModify" />
              </span>
              <span v-on:click="$emit('openSettings', $event)">
                <font-awesome-icon class="icon" icon="cog" v-if="canModify" />
              </span>
              <slot name="settings"></slot>
            </div>
          </div>
          <h1 class="heading heading--regular heading--white">
            {{ title }}
          </h1>
          <div class="sidebar__text">
            {{ description }}
          </div>
          <slot name="headerContent"></slot>
        </div>
      </el-header>
      <el-main>
        <slot name="mainContent"></slot>
      </el-main>
      <el-footer>
        <div class="sidebar__bottom">
          <slot name="footerContent"></slot>
        </div>
      </el-footer>
    </el-container>
  </el-scrollbar>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import Logo from '@/components/shared/atoms/Logo.vue';

@Options({
  components: {
    Logo,
  },
})
export default class Sidebar extends Vue {
  @Prop({ default: '' }) readonly title!: string;
  @Prop({ default: '' }) readonly preTitle!: string;
  @Prop({ default: '' }) readonly description!: string;
  @Prop({ default: true }) readonly canModify!: boolean;
}
</script>

<style lang="scss" scoped>
@import '~@/assets/styles/icons.scss';

.sidebar {
  background-color: var(--color-darkblue);
  position: fixed;
  z-index: 100;
  left: 0;
  top: 0;
  width: var(--sidebar-width);
  min-width: var(--sidebar-min-width);
  height: 100%;
  min-height: 100vh;
  padding: 2rem 2rem 1rem;
  color: white;

  &__container {
    height: 100%;
  }

  &__logo {
    width: 100%;
    height: 2rem;
    margin-bottom: 2rem;
  }

  &__management {
    display: flex;
    justify-content: space-between;
    color: var(--color-darkblue-light);
    margin-bottom: 1rem;
  }

  &__icon {
    cursor: pointer;
  }

  &__text {
    margin-bottom: 1.5rem;
  }

  &__button {
    background-color: var(--module-color);
  }

  .icon {
    margin-left: 0.5em;

    &:hover {
      color: white;
      opacity: 0.7;
    }
  }
}
</style>
