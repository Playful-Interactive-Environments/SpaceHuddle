<template>
  <div class="sidebar__page-header">
    <span class="toggleSidebarButton link" @click="toggleVisible">
      <font-awesome-icon :icon="displaySettings ? 'xmark' : 'bars'" />
    </span>
    <div class="sidebar__logo">
      <font-awesome-icon :icon="getIconName()" class="logo" />
    </div>
    <span class="sidebar_icons">
      <router-link to="/sessions">
        <font-awesome-icon icon="home" />
      </router-link>
      <router-link to="/profile">
        <font-awesome-icon icon="user" />
      </router-link>
    </span>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { EventType } from '@/types/enum/EventType';

@Options({
  components: {},
  emits: ['update:sidebarVisible'],
})
export default class SidebarHeader extends Vue {
  @Prop({ default: true }) readonly sidebarVisible!: boolean;
  displaySettings = false;

  toggleVisible(): void {
    const newVisibility = !this.displaySettings;
    this.$emit('update:sidebarVisible', newVisibility);
    this.eventBus.emit(EventType.CHANGE_SIDEBAR_VISIBILITY, newVisibility);
  }

  @Watch('sidebarVisible', { immediate: true })
  onSidebarVisibleChanged(): void {
    this.displaySettings = this.sidebarVisible;
  }

  getIconName(): string[] {
    return process.env.VUE_APP_THEME == 'ecopolis'
        ? ['fac', 'EcopolisLogoWithName']
        : ['fac', 'logoWithName'];
  }
}
</script>

<style lang="scss" scoped>
.sidebar {
  &__page-header {
    display: flex;
    justify-content: space-between;
    width: 100%;

    span.sidebar_icons {
      align-items: center;

      a {
        color: white;
        font-size: 14px;
        margin-left: 0.6rem;
        margin-top: 0;
      }
    }

    span.sidebar_icons {
      display: inline-flex;
    }
  }

  &__logo {
    font-size: 1.3rem;
  }
}
</style>
