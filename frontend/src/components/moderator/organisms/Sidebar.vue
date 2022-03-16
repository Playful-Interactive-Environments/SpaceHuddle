<template>
  <el-scrollbar class="sidebar" height="100%" max-height="100vh">
    <el-container class="sidebar__container">
      <el-header>
        <div class="sidebar__top">
          <div class="sidebar__page-header">
            <span>
              <el-page-header
                v-if="showBack"
                :content="currentRouteTitle"
                :title="$t('general.back')"
                @back="$router.go(-1)"
              />
              <router-link to="/sessions">
                <font-awesome-icon icon="home" />
              </router-link>
              <router-link to="/profile">
                <font-awesome-icon icon="user" />
              </router-link>
            </span>
            <div class="sidebar__logo">
              <font-awesome-icon :icon="['fac', 'logoWithName']" class="logo" />
            </div>
          </div>
          <div class="sidebar__management">
            <div>{{ preTitle }}</div>
            <div class="sidebar__icon" aria-label="settings" role="button">
              <span v-on:click="$emit('delete', $event)">
                <font-awesome-icon
                  class="awesome-icon"
                  icon="trash"
                  v-if="canModify"
                />
              </span>
              <span v-on:click="$emit('openSettings', $event)">
                <font-awesome-icon
                  class="awesome-icon"
                  icon="cog"
                  v-if="canModify"
                />
              </span>
              <slot name="settings"></slot>
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
      <!--<el-footer>
        <div class="sidebar__bottom">
          <slot name="footerContent"></slot>
        </div>
      </el-footer>-->
      <!--<el-affix position="bottom" :offset="15">
        <div class="sidebar__bottom" v-if="session">
          <SessionCode :code="session.connectionKey" />
          <TutorialStep type="sessionDetails" step="publicScreen" :order="3">
            <router-link
              v-if="session.id"
              :to="`/public-screen/${session.id}`"
              target="_blank"
            >
              <el-button type="info" class="fullwidth">
                {{ $t('general.publicScreen') }}
              </el-button>
            </router-link>
          </TutorialStep>
        </div>
      </el-affix>-->
      <!--<el-footer>
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
      </el-footer>-->
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

@Options({
  components: { SessionCode, TutorialStep },
})
export default class Sidebar extends Vue {
  @Prop({ default: '' }) readonly title!: string;
  @Prop({ default: '' }) readonly preTitle!: string;
  @Prop({ default: '' }) readonly description!: string;
  @Prop({ default: true }) readonly canModify!: boolean;
  @Prop({ default: '' }) readonly currentRouteTitle!: string;
  @Prop({ default: true }) readonly showBack!: boolean;
  @Prop() readonly session!: Session;
}
</script>

<style lang="scss" scoped>
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
  padding: 2rem 2rem 5rem;
  color: white;

  &__footer {
    position: fixed;
    z-index: 200;
    bottom: 0;
    width: var(--sidebar-width);
    min-width: var(--sidebar-min-width);
    padding: 1rem 2rem;
    background-color: var(--color-darkblue);
  }

  &__page-header {
    display: flex;
    justify-content: space-between;
    width: 100%;
    //height: 2rem;
    margin-bottom: 2rem;

    span {
      display: inline-flex;

      a {
        color: white;
        font-size: 14px;
        margin-right: 0.6rem;
        margin-top: 0;
      }
    }
  }

  &__container {
    height: 100%;
  }

  &__bottom {
    background-color: var(--color-darkblue);
  }

  &__logo {
    font-size: 14px;
  }

  &__management {
    display: flex;
    justify-content: space-between;
    color: var(--color-darkblue-light);
    margin-bottom: 1rem;
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

.el-page-header::v-deep {
  color: white;
  line-height: unset;
  display: inline-block;
  .el-page-header__content {
    color: white;
    text-transform: uppercase;
    font-size: 14px;
  }

  .el-page-header__left {
    margin-right: 20px;
  }

  .el-page-header__left::after {
    right: -10px;
  }
}

.el-divider--horizontal {
  --el-border-color-base: var(--color-darkblue-light);
  border-radius: 1rem;
  margin: 0.3rem 0;
  border-top: 3px var(--el-border-color-base) var(--el-border-style);
}
</style>
