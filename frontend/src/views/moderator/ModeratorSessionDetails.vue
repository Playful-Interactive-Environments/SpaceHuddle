<template>
  <ModeratorNavigationLayout v-if="session">
    <template v-slot:sidebar>
      <Sidebar
        :title="session.title"
        :preTitle="formatDate(session.creationDate)"
        :description="session.description"
        :canModify="isModerator"
        v-on:openSettings="editSession"
        v-on:delete="deleteSession"
      >
        <template #settings>
          <span v-on:click="disconnect" v-if="!isModerator">
            <font-awesome-icon
              class="icon"
              icon="user-slash"
            ></font-awesome-icon>
          </span>
          <span v-else v-on:click="showRoles = true">
            <font-awesome-icon class="icon" icon="users"></font-awesome-icon>
          </span>
        </template>
        <template #headerContent>
          <span :class="{ expired: isExpired }">
            {{
              $t('moderator.organism.settings.sessionSettings.expirationDate')
            }}: {{ formatDate(session.expirationDate) }}
          </span>
          <ModuleCount :session="session" />
        </template>
        <template #footerContent>
          <SessionCode :code="session.connectionKey" />
          <router-link
            v-if="session.id"
            :to="`/public-screen/${session.id}`"
            target="_blank"
          >
            <button class="btn btn--mint btn--fullwidth">
              {{ $t('general.publicScreen') }}
            </button>
          </router-link>
        </template>
      </Sidebar>
    </template>
    <template v-slot:content>
      <draggable
        v-model="topics"
        tag="transition-group"
        item-key="order"
        handle=".card__drag"
        @end="dragDone"
      >
        <template #item="{ element }">
          <div class="detail__module">
            <TopicCard
              :sessionId="sessionId"
              :topic="element"
              :canModify="isModerator"
              v-on:topicDeleted="getTopics"
            >
              <TaskTimeline
                :topic-id="element.id"
                :session-id="sessionId"
                style="margin-bottom: 1rem"
                :is-linked-to-task="false"
                v-on:changePublicScreen="publicScreenTopic = element.id"
                :key="publicScreenTopic"
              ></TaskTimeline>
            </TopicCard>
          </div>
        </template>
      </draggable>
      <AddItem
        v-if="isModerator"
        :text="$t('moderator.view.sessionDetails.addTopic')"
        @addNew="showTopicSettings = true"
      />
      <TopicSettings
        v-model:show-modal="showTopicSettings"
        :session-id="sessionId"
        :topic-id="editTopicId"
        @topicUpdated="getTopics"
      />
      <SessionSettings
        v-model:show-modal="showSessionSettings"
        :session-id="sessionId"
        @sessionUpdated="getTopics"
      />
      <FacilitatorSettings
        v-if="isModerator"
        v-model:showModal="showRoles"
        :sessionId="sessionId"
      />
    </template>
  </ModeratorNavigationLayout>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import draggable from 'vuedraggable';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import TaskSettingsOld from '@/components/moderator/organisms/settings/TaskSettingsOld.vue';
import TaskCard from '@/components/moderator/organisms/cards/TaskCard.vue';
import ModeratorNavigationLayout from '@/components/moderator/organisms/layout/ModeratorNavigationLayout.vue';
import { formatDate } from '@/utils/date';
import TaskType from '@/types/enum/TaskType';
import * as sessionService from '@/services/session-service';
import * as sessionRoleService from '@/services/session-role-service';
import * as topicService from '@/services/topic-service';
import * as taskService from '@/services/task-service';
import { Session } from '@/types/api/Session';
import { Topic } from '@/types/api/Topic';
import { EventType } from '@/types/enum/EventType';
import TopicSettings from '@/components/moderator/organisms/settings/TopicSettings.vue';
import CollapseTitle from '@/components/moderator/atoms/CollapseTitle.vue';
import SessionSettings from '@/components/moderator/organisms/settings/SessionSettings.vue';
import TaskTimeline from '@/components/moderator/organisms/Timeline/TaskTimeline.vue';
import Sidebar from '@/components/moderator/organisms/Sidebar.vue';
import ModuleCount from '@/components/moderator/molecules/ModuleCount.vue';
import SessionCode from '@/components/moderator/molecules/SessionCode.vue';
import TopicCard from '@/components/moderator/organisms/cards/TopicCard.vue';
import FacilitatorSettings from '@/components/moderator/organisms/settings/FacilitatorSettings.vue';
import UserType from '@/types/enum/UserType';

@Options({
  components: {
    FacilitatorSettings,
    TopicCard,
    ModuleCount,
    SessionCode,
    Sidebar,
    AddItem,
    draggable,
    TaskSettingsOld,
    TopicSettings,
    TaskCard,
    ModeratorNavigationLayout,
    CollapseTitle,
    SessionSettings,
    TaskTimeline,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorSessionDetails extends Vue {
  @Prop() readonly sessionId!: string;

  session: Session | null = null;
  sessionRole = '';
  topics: Topic[] = [];
  publicScreenTaskId = '';
  showTopicSettings = false;
  showSessionSettings = false;
  showRoles = false;
  formatDate = formatDate;
  editTopicId = '';
  publicScreenTopic = '';
  readonly intervalTime = 3000;
  interval!: any;

  TaskType = TaskType;

  get isModerator(): boolean {
    return this.sessionRole === UserType.MODERATOR;
  }

  get isExpired(): boolean {
    if (this.session) {
      const date = new Date(this.session.expirationDate);
      return date <= new Date();
    }
    return true;
  }

  async mounted(): Promise<void> {
    this.eventBus.off(EventType.CHANGE_PUBLIC_SCREEN);
    this.eventBus.on(EventType.CHANGE_PUBLIC_SCREEN, async (id) => {
      await this.changePublicScreen(id as string);
    });
    this.startInterval();
  }

  @Watch('sessionId', { immediate: true })
  async onSessionIdChanged(): Promise<void> {
    await sessionRoleService.getOwn(this.sessionId).then((role) => {
      this.sessionRole = role.role;
    });
    await this.getTopics();
  }

  async changePublicScreen(id: string | null): Promise<void> {
    this.publicScreenTaskId = id as string;
  }

  async getTopics(): Promise<void> {
    sessionService.getById(this.sessionId).then((queryResult) => {
      this.session = queryResult;
      topicService.getTopicsList(this.session.id).then((queryResult) => {
        this.topics = queryResult;
        this.topics.forEach(async (topic) => {
          taskService.getTaskList(topic.id).then((queryResult) => {
            topic.tasks = queryResult;
            topic.tasks.sort((a, b) => (a.order > b.order ? 1 : 0));
          });
        });
        this.getPublicScreen();
      });
    });
  }

  async editSession(): Promise<void> {
    this.showSessionSettings = true;
  }

  async deleteSession(): Promise<void> {
    await sessionService.remove(this.sessionId).then((deleted) => {
      if (deleted) this.$router.go(-1);
    });
  }

  async getPublicScreen(): Promise<void> {
    sessionService.getPublicScreen(this.sessionId).then((queryResult) => {
      if (queryResult) {
        this.publicScreenTaskId = queryResult.id;
        this.publicScreenTopic = queryResult.topicId;
      }
    });
  }

  dragDone(): void {
    if (this.topics) {
      for (let i = 0; i < this.topics.length; i++) {
        this.topics[i].order = i;
        const topic = {
          id: this.topics[i].id,
          title: this.topics[i].title,
          description: this.topics[i].description,
          order: this.topics[i].order,
        };
        topicService.putTopic(this.topics[i].id, topic);
      }
    }
  }

  startInterval(): void {
    this.interval = setInterval(this.getTopics, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }

  disconnect(): void {
    sessionRoleService.removeOwn(this.sessionId).then((deleted) => {
      if (deleted) this.$router.go(-1);
    });
  }
}
</script>

<style lang="scss" scoped>
.detail {
  background-color: var(--color-background-gray);
  min-height: 100vh;

  &__content {
    flex-grow: 1;
    padding: 0 3rem;
  }

  &__module {
    margin-top: 0.5rem;
    margin-bottom: 1rem;
  }
}

.expired {
  color: var(--color-red);
}

.icon {
  margin-left: 0.5em;
}
</style>
