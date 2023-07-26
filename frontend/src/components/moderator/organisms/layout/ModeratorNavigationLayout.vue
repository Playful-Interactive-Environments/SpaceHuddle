<template>
  <el-container class="main-layout">
    <el-aside class="sidebar" v-if="hasSidebar">
      <slot name="sidebar"></slot>
    </el-aside>
    <el-container>
      <el-header>
        <div class="sidebar__mobile_header">
          <SidebarHeader v-model:sidebar-visible="displaySettings" />
        </div>
        <el-scrollbar>
          <div class="level" v-if="!hasSidebar">
            <span class="level-left">
              <font-awesome-icon
                v-if="!hasSidebar"
                :icon="getIconName()"
                class="logo"
              />
              <el-page-header
                v-if="currentRouteTitle.length > 0"
                class="level-item"
                :content="currentRouteTitle"
                :title="$t('general.back')"
                @back="$router.go(-1)"
              />
            </span>
            <nav
              class="level-right"
              :class="{
                'nav--white': white,
              }"
            >
              <router-link to="/sessions" class="level-item">
                <font-awesome-icon icon="home" class="nav__item-icon" />
                {{ $t('moderator.molecule.navigation.sessions') }}
              </router-link>
              <router-link to="/profile" class="level-item">
                <font-awesome-icon icon="user" class="nav__item-icon" />
                {{ $t('moderator.molecule.navigation.profile') }}
              </router-link>
              <span
                class="level-item sidebar__trigger"
                v-on:click="displaySettings = true"
                v-if="hasSidebar"
              >
                <font-awesome-icon icon="cog" class="nav__item-icon" />
                {{ $t('moderator.molecule.navigation.settings') }}
              </span>
            </nav>
          </div>
        </el-scrollbar>
        <slot name="header"></slot>
      </el-header>
      <el-main class="main-layout__content">
        <slot name="content"></slot>
      </el-main>
    </el-container>
  </el-container>

  <el-drawer
    v-model="displaySettings"
    direction="ltr"
    size="300px"
    :key="displaySettings"
    class="sidebar"
  >
    <slot name="sidebar"></slot>
  </el-drawer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import Sidebar from '@/components/moderator/organisms/layout/Sidebar.vue';
import SidebarHeader from '@/components/moderator/organisms/layout/SidebarHeader.vue';
import { EventType } from '@/types/enum/EventType';

@Options({
  components: {
    SidebarHeader,
    Sidebar,
  },
  emits: ['update:sidebarVisible'],
})
export default class ModeratorNavigationLayout extends Vue {
  @Prop({ default: false }) white!: boolean;
  @Prop({ default: '' }) readonly currentRouteTitle!: string;
  displaySettings = false;

  mounted(): void {
    this.eventBus.off(EventType.CHANGE_SIDEBAR_VISIBILITY);
    this.eventBus.on(EventType.CHANGE_SIDEBAR_VISIBILITY, async (visible) => {
      this.displaySettings = visible as boolean;
    });
  }

  get hasSidebar(): boolean {
    return !!this.$slots.sidebar;
  }

  getIconName(): string[] {
    return process.env.VUE_APP_THEME == 'ecopolis'
        ? ['fac', 'EcopolisLogoWithName']
        : ['fac', 'logoWithName'];
  }
}
</script>

<style lang="scss" scoped>
.main-layout {
  background-color: var(--color-background);
  min-height: var(--app-height);

  &__content {
    flex-grow: 1;
    padding: 1rem 2rem 0 2rem;
  }
}

.level-right {
  min-height: var(--header-height);
  padding: 0 2em 0 0;

  .level-item {
    padding-left: 3em;
    margin-top: 2em;
    margin-bottom: 2em;
  }
}

.level-left {
  min-height: var(--header-height);
  padding: 0 0 0 2em;

  .level-item {
    padding-right: 3em;
    margin-top: 2em;
    margin-bottom: 2em;
  }
}

.nav {
  &__item {
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

.el-page-header {
  margin-left: 3rem;
}
</style>
