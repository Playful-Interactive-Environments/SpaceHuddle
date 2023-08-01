<template>
  <el-card shadow="never">
    <el-container style="height: 100%">
      <el-header>
        <div>
          <div class="top-container">
            <p class="el-card__date">{{ formatDate(session.creationDate) }}</p>
            <el-dropdown
              v-if="isModerator"
              class="card__menu"
              v-on:command="menuItemSelected"
            >
              <span class="el-dropdown-link">
                <font-awesome-icon icon="ellipsis-h" />
              </span>
              <template #dropdown>
                <el-dropdown-item command="edit">
                  {{ $t('moderator.organism.cards.sessionCard.edit') }}
                </el-dropdown-item>
                <el-dropdown-item command="clone">
                  {{ $t('moderator.organism.cards.sessionCard.clone') }}
                </el-dropdown-item>
                <el-dropdown-item command="delete">
                  {{ $t('moderator.organism.cards.sessionCard.delete') }}
                </el-dropdown-item>
              </template>
            </el-dropdown>
          </div>
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
        <div class="el-card__content">
          <span v-if="hasSubject" class="flex-grow subject">{{
            this.session.subject
          }}</span>
        </div>
        <ModuleCount :session="session" />
        <div class="el-card__content">
          <SessionCode :code="session.connectionKey" button-type="primary" />
          <router-link :to="`/session/${session.id}`" class="flex-grow">
            <el-button class="fullwidth" type="info">
              {{ $t('moderator.organism.cards.sessionCard.select') }}
            </el-button>
          </router-link>
        </div>
      </el-footer>
    </el-container>
  </el-card>
  <SessionSettings
    v-model:show-modal="showSettings"
    :session-id="sessionEditId"
    @sessionUpdated="onSessionUpdated"
    @update:subject="onSubjectUpdated"
  />
</template>

<script lang="ts">
import { Prop, Watch } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import { Session } from '@/types/api/Session';
import { formatDate } from '@/utils/date';
import { clone } from '@/services/session-service';
import SessionCode from '@/components/moderator/molecules/SessionCode.vue';
import SessionSettings from '@/components/moderator/organisms/settings/SessionSettings.vue';
import ModuleCount from '@/components/moderator/molecules/ModuleCount.vue';
import { ElMessageBox } from 'element-plus';
import * as sessionService from '@/services/session-service';
import * as sessionRoleService from '@/services/session-role-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { SessionRole } from '@/types/api/SessionRole';
import UserType from '@/types/enum/UserType';

@Options({
  components: {
    SessionCode,
    ModuleCount,
    SessionSettings,
  },
  emits: ['updated'],
})
export default class SessionCard extends Vue {
  @Prop() session!: Session;

  formatDate = formatDate;

  sessionEditId = '';
  showSettings = false;
  hasSubject = false;
  sessionRole = '';

  get isModerator(): boolean {
    return this.sessionRole === UserType.MODERATOR;
  }

  mounted(): void {
    this.hasSubject =
      this.session.subject != null || this.session.subject != undefined;
  }

  @Watch('session.id', { immediate: true })
  onSessionIdChanged(): void {
    if (this.session) {
      sessionRoleService.registerGetOwn(
        this.session.id,
        this.updateRole,
        EndpointAuthorisationType.MODERATOR,
        60 * 60
      );
    }
  }

  updateRole(role: SessionRole): void {
    this.sessionRole = role.role;
  }

  menuItemSelected(command: string): void {
    switch (command) {
      case 'clone':
        this.cloneSession();
        break;
      case 'edit':
        this.sessionEditId = this.session.id;
        this.showSettings = true;
        break;
      case 'delete':
        this.deleteSession();
        break;
      default:
        break;
    }
  }
  onSessionUpdated(): void {
    this.showSettings = false;
    // If a session has been cloned, navigate to the new session
    if (this.session.id !== this.sessionEditId) {
      this.$router.push(`/session/${this.sessionEditId}`);
    } else {
      this.$emit('updated');
    }
  }

  onSubjectUpdated(subjectState: boolean): void {
    this.hasSubject = subjectState;
    this.$emit('updated');
  }

  async cloneSession(): Promise<void> {
    try {
      await ElMessageBox.confirm(
        this.$t('moderator.organism.cards.sessionCard.clonePrompt'),
        this.$t('moderator.organism.cards.sessionCard.clone'),
        {
          boxType: 'confirm',
          confirmButtonText: this.$t(
            'moderator.organism.cards.sessionCard.clone'
          ),
        }
      );
      const clonedSession = await clone(this.session.id);
      this.sessionEditId = clonedSession.id;
      this.showSettings = true;
    } catch {
      // do nothing if the MessageBox is declined
    }
  }
  async deleteSession(): Promise<void> {
    setTimeout(() => {
      sessionService.remove(this.session.id).then((deleted) => {
        if (deleted) {
          sessionService.refreshGetSessionList();
          this.$emit('updated');
        }
      });
    }, 100);
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

.top-container {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: flex-start;
}

.flex-grow {
  flex-grow: 1;
}

.subject {
  width: auto;
  justify-content: flex-start;
  padding: 0.25rem 0.5rem;
  margin: 0;
  color: white;
  background-color: var(--color-dark-contrast);
  border-radius: 10px;
}

ModuleCount {
  margin-bottom: 0.5rem;
}
</style>
