<template>
  <el-scrollbar class="sidebar" height="100%" max-height="var(--app-height)">
    <el-container class="sidebar__container">
      <el-header>
        <div class="sidebar__top">
          <SidebarHeader />
          <div class="sidebar__management">
            <div v-if="!!$slots.management">
              <slot name="management" />
            </div>
            <div v-else>
              <el-page-header
                v-if="showBack"
                :content="currentRouteTitle"
                :title="$t('general.back')"
                @back="$router.go(-1)"
              />
            </div>
            <div class="sidebar__icon" aria-label="settings" role="button">
              <slot name="settings" />
              <span v-on:click="$emit('openSettings', $event)">
                <ToolTip
                  :effect="'light'"
                  :text="$t('moderator.organism.settings.topicSettings.edit')"
                >
                  <font-awesome-icon
                    class="awesome-icon"
                    icon="cog"
                    v-if="canModify"
                  />
                </ToolTip>
              </span>
              <span v-on:click="$emit('delete', $event)">
                <ToolTip
                  :effect="'light'"
                  :text="$t('moderator.organism.settings.topicSettings.delete')"
                >
                  <font-awesome-icon
                    class="awesome-icon"
                    icon="trash"
                    v-if="canModify"
                  />
                </ToolTip>
              </span>
            </div>
          </div>
          <h1 class="heading heading--regular heading--white threeLineText">
            {{ title }}
          </h1>
          <el-divider></el-divider>
          <div class="sidebar__text threeLineText">
            {{ description }}
          </div>
          <slot name="headerContent"></slot>
        </div>
      </el-header>
      <el-main>
        <slot name="mainContent"></slot>
      </el-main>
    </el-container>
  </el-scrollbar>
  <div class="sidebar__footer">
    <div class="sidebar__bottom session-code" v-if="session">
      <SessionCode :code="session.connectionKey" />
      <TutorialStep type="sessionDetails" step="publicScreen" :order="3">
        <router-link
          v-if="session.id"
          :to="`/public-screen/${session.id}`"
          target="_blank"
        >
          <el-button type="info">
            <font-awesome-icon :icon="['fac', 'presentation']" />
          </el-button>
        </router-link>
      </TutorialStep>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { Session } from '@/types/api/Session';
import SessionCode from '@/components/moderator/molecules/SessionCode.vue';
import TutorialStep from '@/components/shared/atoms/TutorialStep.vue';
import SidebarHeader from '@/components/moderator/organisms/layout/SidebarHeader.vue';
import ToolTip from "@/components/shared/atoms/ToolTip.vue";

@Options({
  components: {ToolTip, SidebarHeader, SessionCode, TutorialStep },
  emits: ['openSettings', 'delete'],
})
export default class Sidebar extends Vue {
  @Prop({ default: '' }) readonly title!: string;
  @Prop({ default: '' }) readonly description!: string;
  @Prop({ default: true }) readonly canModify!: boolean;
  @Prop({ default: '' }) readonly currentRouteTitle!: string;
  @Prop({ default: true }) readonly showBack!: boolean;
  @Prop() readonly session!: Session;

  get info(): string {
    const doc = document.documentElement;
    return `${window.innerWidth}, ${window.innerHeight}, ${window.outerWidth}, ${window.outerHeight}, ${doc.clientWidth}, ${doc.clientHeight}, ${window.screen.availWidth}, ${window.screen.availHeight}`;
  }
}
</script>

<style lang="scss" scoped>
.sidebar {
  background-color: var(--color-dark-contrast);
  position: fixed;
  z-index: 100;
  left: 0;
  top: 0;
  width: var(--sidebar-width);
  min-width: var(--sidebar-min-width);
  height: 100%;
  min-height: var(--app-height);
  max-height: var(--app-height);
  padding: 1.5rem 1.5rem 4rem;
  color: white;

  &__footer {
    position: fixed;
    z-index: 200;
    left: 0;
    top: calc(var(--app-height) - 4rem);
    //bottom: 0;
    width: var(--sidebar-width);
    min-width: var(--sidebar-min-width);
    padding: 0.5rem 1.5rem 1rem;
    background-color: var(--color-dark-contrast);
  }

  &__page-header {
    margin-bottom: 2rem;
  }

  &__container {
    height: 100%;
  }

  &__bottom {
    background-color: var(--color-dark-contrast);
  }

  &__logo {
    font-size: 1.3rem;
  }

  &__management {
    display: flex;
    justify-content: space-between;
    color: var(--color-dark-contrast-light);
    margin-bottom: 0.5rem;
    text-transform: uppercase;
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

  .awesome-icon {
    margin-left: 0.5em;

    &:hover {
      color: white;
      opacity: 0.7;
    }
  }

  .session-code {
    display: flex;
    flex-direction: row;
    justify-content: space-between;

    .session-code {
      width: 100%;
      padding-right: 0.5rem;
    }

    .el-button {
      width: 40.57px;
      min-width: 40.57px;
      height: 40.57px;
      border-radius: 50%;
    }
  }
}

.el-page-header {
  --el-border-color-base: var(--color-dark-contrast-light);
  line-height: unset;
  display: inline-block;
}

.el-page-header::v-deep(.el-page-header__content) {
  text-transform: uppercase;
  font-size: 14px;
}

.el-page-header::v-deep(.el-page-header__left) {
  margin-right: 0; // 20px;
}

.el-page-header::v-deep(.el-page-header__left)::after {
  right: -10px;
}

.el-divider--horizontal {
  --el-border-color-base: var(--color-dark-contrast-light);
  border-radius: 1rem;
  margin: 0.3rem 0;
  border-top: 3px var(--el-border-color-base) var(--el-border-style);
}
</style>
