<template>
  <el-container class="main-layout">
    <el-aside class="sidebar" v-if="hasSidebar">
      <slot name="sidebar"></slot>
    </el-aside>
    <el-container>
      <el-header>
        <el-scrollbar>
          <div class="level" v-if="!hasSidebar">
            <span class="level-left">
              <font-awesome-icon
                v-if="!hasSidebar"
                :icon="['fac', 'logoWithName']"
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
              <!--<router-link to="/sessions" class="level-item">
                <font-awesome-icon icon="database" class="nav__item-icon" />
                {{ $t('moderator.molecule.navigation.sessions') }}
              </router-link>-->
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
  >
    <slot name="sidebar"></slot>
  </el-drawer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import Sidebar from '@/components/moderator/organisms/Sidebar.vue';

@Options({
  components: {
    Sidebar,
  },
})
export default class ModeratorNavigationLayout extends Vue {
  @Prop({ default: false }) white!: boolean;
  @Prop({ default: '' }) readonly currentRouteTitle!: string;
  displaySettings = false;

  get hasSidebar(): boolean {
    return !!this.$slots.sidebar;
  }
}
</script>

<style lang="scss" scoped>
.main-layout {
  background-color: var(--color-background-gray);
  min-height: 100vh;

  &__content {
    flex-grow: 1;
    padding: 1rem 2rem;
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

.logo {
  font-size: 1.3rem;
}
</style>
