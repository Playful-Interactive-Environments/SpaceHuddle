<template>
  <el-scrollbar class="sidebar" height="100%" max-height="100vh">
    <el-container class="sidebar__container">
      <el-header>
        <div class="sidebar__top">
          <div class="sidebar__logo">
            <Logo />
          </div>
          <div class="sidebar__management">
            <div>{{ pretitle }}</div>
            <div class="sidebar__icon" aria-label="settings" role="button">
              <font-awesome-icon
                class="icon"
                icon="trash"
                v-on:click="$emit('delete', $event)"
              />
              <font-awesome-icon
                class="icon"
                icon="cog"
                v-on:click="$emit('openSettings', $event)"
              />
            </div>
          </div>
          <h1 class="heading heading--regular heading--white">
            {{ title }}
          </h1>
          <div class="sidebar__text">
            {{ description }}
          </div>
          <ModuleCount v-if="isSession" :session="session" />
        </div>
      </el-header>
      <el-main></el-main>
      <el-footer>
        <div class="sidebar__bottom">
          <SessionCode v-if="isSession" :code="sessionConnectionKey" />
          <div class="sidebar__toggles" v-else>
            <ModuleShare
              v-if="task"
              :task="task"
              :is-on-public-screen="isOnPublicScreen"
            />
          </div>
          <router-link
            v-if="session.id"
            :to="`/public-screen/${session.id}`"
            target="_blank"
          >
            <button
              class="btn btn--mint btn--fullwidth"
              :class="{ sidebar__button: !isSession }"
            >
              {{ $t('general.publicScreen') }}
            </button>
          </router-link>
        </div>
      </el-footer>
    </el-container>
  </el-scrollbar>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { Session } from '@/types/api/Session';
import Logo from '@/components/shared/atoms/Logo.vue';
import ModuleCount from '@/components/moderator/molecules/ModuleCount.vue';
import TaskType from '@/types/enum/TaskType';
import SessionCode from '@/components/moderator/molecules/SessionCode.vue';
import ModuleShare from '@/components/moderator/molecules/ModuleShare.vue';
import { Task } from '@/types/api/Task';

@Options({
  components: {
    Logo,
    ModuleCount,
    SessionCode,
    ModuleShare,
  },
})
export default class Sidebar extends Vue {
  @Prop({ default: false }) readonly isSession!: boolean;
  @Prop() readonly session!: Session;
  @Prop({ default: '' }) readonly sessionConnectionKey!: string;
  @Prop({ default: '' }) readonly title!: string;
  @Prop({ default: '' }) readonly pretitle!: string;
  @Prop({ default: '' }) readonly description!: string;
  @Prop() readonly taskType!: TaskType;
  @Prop({ default: false }) readonly isOnPublicScreen!: boolean;
  @Prop() task!: Task;

  TaskType = TaskType;
}
</script>

<style lang="scss" scoped>
@import '~@/assets/styles/icons.scss';

.sidebar {
  background-color: var(--color-darkblue);
  position: fixed;
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

    &:hover {
      color: white;
      opacity: 0.7;
    }
  }

  &__text {
    margin-bottom: 1.5rem;
  }

  &__toggles {
    margin-bottom: 1rem;
  }

  &__button {
    background-color: var(--module-color);
  }

  .icon {
    margin-left: 0.5em;
  }
}
</style>
