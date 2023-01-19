<template>
  <el-card shadow="never">
    <el-container style="height: 100%">
      <el-header>
        <div>
          <p class="el-card__date">{{ formatDate(session.creationDate) }}</p>
          <h2 class="heading heading--regular threeLineText line-break">
            {{ session.title }}
          </h2>
          <p class="el-card__description threeLineText line-break">
            {{ session.description }}
          </p>
        </div>
      </el-header>
      <el-main> </el-main>
      <el-footer>
        <ModuleCount :session="session" />
        <div class="el-card__content">
          <SessionCode :code="session.connectionKey" button-type="primary" />
          <div class="session-link-container">
            <router-link :to="`/session/${session.id}`" class="flex-grow">
              <el-button class="fullwidth" type="info">
                {{ $t('moderator.organism.session.overview.select') }}
              </el-button>
            </router-link>
            <el-dropdown class="card__menu" v-on:command="menuItemSelected">
              <span class="el-dropdown-link">
                <font-awesome-icon icon="ellipsis-h" />
              </span>
              <template #dropdown>
                <el-dropdown-item command="clone">
                  {{ $t('moderator.organism.session.overview.clone') }}
                </el-dropdown-item>
              </template>
            </el-dropdown>
          </div>
        </div>
      </el-footer>
    </el-container>
  </el-card>
</template>

<script lang="ts">
import { Prop } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import { Session } from '@/types/api/Session';
import { formatDate } from '@/utils/date';
import { clone } from '@/services/session-service';
import SessionCode from '@/components/moderator/molecules/SessionCode.vue';
import ModuleCount from '@/components/moderator/molecules/ModuleCount.vue';
import { ElMessageBox } from 'element-plus';
import app from '@/main';

@Options({
  components: {
    SessionCode,
    ModuleCount,
  },
})
export default class SessionCard extends Vue {
  @Prop() session!: Session;

  formatDate = formatDate;

  menuItemSelected(command: string): void {
    switch (command) {
      case 'clone':
        this.cloneSession();
        break;
      default:
        break;
    }
  }

  async cloneSession(): Promise<void> {
    try {
      await ElMessageBox.confirm(
        app.config.globalProperties.$i18n.translateWithFallback(
          'moderator.organism.session.overview.clonePrompt'
        ),
        app.config.globalProperties.$i18n.translateWithFallback(
          'moderator.organism.session.overview.clone'
        ),
        {
          boxType: 'confirm',
          confirmButtonText:
            app.config.globalProperties.$i18n.translateWithFallback(
              'moderator.organism.session.overview.clone'
            ),
        }
      );
      const clonedSession = await clone(this.session.id);
      this.$router.push(`/session/${clonedSession.id}`);
    } catch {
      // do nothing if the MessageBox is declined
    }
  }
}
</script>

<style lang="scss" scoped>
.el-card {
  background: #fff;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  height: 100%;

  &__date {
    text-transform: uppercase;
    color: #aaaaaa;
    font-size: var(--font-size-small);
    margin-bottom: 1rem;
  }

  &__description {
    text-align: left;
  }

  &__content {
    margin-top: 0.5rem;
  }
}

.session-link-container {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 5px;
}

.flex-grow {
  flex-grow: 1;
}

ModuleCount {
  margin-bottom: 0.5rem;
}
</style>
