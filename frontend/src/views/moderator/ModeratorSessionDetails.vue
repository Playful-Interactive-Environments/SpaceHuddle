<template>
  <ModeratorNavigationLayout v-if="session">
    <template v-slot:sidebar>
      <Sidebar
        :title="session.title"
        :description="session.description"
        :canModify="isModerator"
        :session="session"
        v-on:openSettings="editSession"
        v-on:delete="deleteSession"
      >
        <template #management>
          {{ formatDate(session.creationDate) }}
        </template>
        <template #settings>
          <span v-on:click="download">
            <ToolTip
              :effect="'light'"
              :text="$t('moderator.organism.settings.sidebarSettings.download')"
            >
              <font-awesome-icon
                v-if="!isDownloading"
                class="awesome-icon"
                icon="download"
              ></font-awesome-icon>
              <font-awesome-icon
                v-else
                class="awesome-icon fa-spin"
                icon="spinner"
              ></font-awesome-icon>
            </ToolTip>
          </span>
          <!--          <TutorialStep
            v-if="!isModerator"
            type="sessionDetails"
            step="disconnect"
            :order="4"
          >-->
          <span v-on:click="disconnect" v-if="!isModerator">
            <font-awesome-icon
              class="awesome-icon"
              icon="user-slash"
            ></font-awesome-icon>
          </span>
          <!--          </TutorialStep>
          <TutorialStep
            v-else
            type="sessionDetails"
            step="coModerator"
            :order="4"
          >-->

          <span v-on:click="showRoles = true">
            <ToolTip
              :effect="'light'"
              :text="
                $t('moderator.organism.settings.facilitatorSettings.header')
              "
            >
              <font-awesome-icon
                class="awesome-icon"
                icon="user-group"
              ></font-awesome-icon>
            </ToolTip>
          </span>
          <!--          </TutorialStep>
          <TutorialStep
            v-if="isModerator"
            type="sessionDetails"
            step="participants"
            :order="10"
          >-->
          <span v-on:click="showParticipants = true">
            <ToolTip
              :effect="'light'"
              :text="
                $t('moderator.organism.settings.participantSettings.header')
              "
            >
              <font-awesome-icon
                class="awesome-icon"
                icon="users"
              ></font-awesome-icon>
            </ToolTip>
          </span>
          <!--          </TutorialStep>-->
          <!--          <el-dropdown>
            <span class="el-dropdown-link">
              <font-awesome-icon class="awesome-icon" icon="info-circle" />
            </span>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item v-on:click="reactivateTutorial">
                  {{ $t('tutorial.reactivate') }}
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>-->
        </template>
        <template #headerContent>
          <span :class="{ expired: isExpired }">
            {{
              $t('moderator.organism.settings.sessionSettings.expirationDate')
            }}: {{ formatDate(session.expirationDate) }}
          </span>
          <ModuleCount :session="session" />
        </template>
      </Sidebar>
    </template>
    <template v-slot:content>
      <draggable
        v-if="topics"
        v-model="topics"
        item-key="order"
        handle=".card__drag"
        @end="dragDone"
      >
        <template #item="{ element }">
          <div class="detail__module" :key="element.order">
            <TopicCard
              :sessionId="sessionId"
              :topic="element"
              :canModify="isModerator"
              v-on:topicDeleted="deleteTopic"
            >
              <TaskTimeline
                :topic-id="element.id"
                :session-id="sessionId"
                :is-linked-to-task="false"
                :key="publicScreenTopic"
              ></TaskTimeline>
            </TopicCard>
          </div>
        </template>
      </draggable>
      <TutorialStep
        v-if="isModerator"
        type="sessionDetails"
        step="addTopic"
        :order="5"
        :width="450"
      >
        <AddItem
          :text="$t('moderator.view.sessionDetails.addTopic')"
          @addNew="showTopicSettings = true"
        />
      </TutorialStep>
      <TopicSettings
        v-model:show-modal="showTopicSettings"
        :session-id="sessionId"
        :topic-id="editTopicId"
        @topicUpdated="reloadTopics"
      />
      <SessionSettings
        v-model:show-modal="showSessionSettings"
        :session-id="sessionId"
        @sessionUpdated="reloadSession"
      />
      <FacilitatorSettings
        v-if="isModerator"
        v-model:showModal="showRoles"
        :sessionId="sessionId"
      />
      <ParticipantSettings
        v-if="isModerator"
        v-model:show-modal="showParticipants"
        :session-id="sessionId"
      />
    </template>
  </ModeratorNavigationLayout>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import ModeratorNavigationLayout from '@/components/moderator/organisms/layout/ModeratorNavigationLayout.vue';
import { formatDate } from '@/utils/date';
import TaskType from '@/types/enum/TaskType';
import * as sessionService from '@/services/session-service';
import * as sessionRoleService from '@/services/session-role-service';
import * as topicService from '@/services/topic-service';
import * as taskService from '@/services/task-service';
import { Session } from '@/types/api/Session';
import { Topic } from '@/types/api/Topic';
import TopicSettings from '@/components/moderator/organisms/settings/TopicSettings.vue';
import CollapseTitle from '@/components/moderator/atoms/CollapseTitle.vue';
import SessionSettings from '@/components/moderator/organisms/settings/SessionSettings.vue';
import TaskTimeline from '@/components/moderator/organisms/Timeline/TaskTimeline.vue';
import Sidebar from '@/components/moderator/organisms/layout/Sidebar.vue';
import ModuleCount from '@/components/moderator/molecules/ModuleCount.vue';
import SessionCode from '@/components/moderator/molecules/SessionCode.vue';
import TopicCard from '@/components/moderator/organisms/cards/TopicCard.vue';
import FacilitatorSettings from '@/components/moderator/organisms/settings/FacilitatorSettings.vue';
import UserType from '@/types/enum/UserType';
import TutorialStep from '@/components/shared/atoms/TutorialStep.vue';
import draggable from 'vuedraggable';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import { Task } from '@/types/api/Task';
import { SessionRole } from '@/types/api/SessionRole';
import { reactivateTutorial } from '@/services/tutorial-service';
import ParticipantSettings from '@/components/moderator/organisms/settings/ParticipantSettings.vue';
import ToolTip from '@/components/shared/atoms/ToolTip.vue';

@Options({
  components: {
    ToolTip,
    ParticipantSettings,
    TutorialStep,
    FacilitatorSettings,
    TopicCard,
    ModuleCount,
    SessionCode,
    Sidebar,
    AddItem,
    draggable,
    TopicSettings,
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
  showParticipants = false;
  formatDate = formatDate;
  editTopicId = '';
  publicScreenTopic = '';

  TaskType = TaskType;

  reactivateTutorial(): void {
    reactivateTutorial('sessionDetails', this.eventBus);
    reactivateTutorial('taskTimeline', this.eventBus);
  }

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

  topicsCashEntry!: cashService.SimplifiedCashEntry<Topic[]>;
  sessionCashEntry!: cashService.SimplifiedCashEntry<Session>;
  @Watch('sessionId', { immediate: true })
  async onSessionIdChanged(): Promise<void> {
    this.deregisterAll();
    this.sessionCashEntry = sessionService.registerGetById(
      this.sessionId,
      this.updateSession,
      EndpointAuthorisationType.MODERATOR,
      10 * 60
    );
    this.topicsCashEntry = topicService.registerGetTopicsList(
      this.sessionId,
      this.updateTopics,
      EndpointAuthorisationType.MODERATOR,
      2 * 60
    );

    sessionService.registerGetPublicScreen(
      this.sessionId,
      this.updatePublicTask,
      EndpointAuthorisationType.MODERATOR,
      60 * 60
    );
    sessionRoleService.registerGetOwn(
      this.sessionId,
      this.updateRole,
      EndpointAuthorisationType.MODERATOR,
      60 * 60
    );
  }

  updateRole(role: SessionRole): void {
    this.sessionRole = role.role;
  }

  updateSession(session: Session): void {
    this.session = session;
  }

  reloadSession(): void {
    this.sessionCashEntry.refreshData();
  }

  updateTopics(topics: Topic[]): void {
    const deletedTopics = this.topics
      .filter((tOld) => {
        return !topics.find((tNew) => tNew.id === tOld.id);
      })
      .map((t) => t.id);
    this.deregisterGetTasks(deletedTopics);
    const newTopics = topics.filter((tNew) => {
      return !this.topics.find((tOld) => tNew.id === tOld.id);
    });
    this.topics = topics;
    newTopics.forEach(async (topic) => {
      taskService.registerGetTaskList(
        topic.id,
        this.updateTasks,
        EndpointAuthorisationType.MODERATOR,
        5 * 60
      );
    });
  }

  updatePublicTask(task: Task): void {
    if (task) {
      this.publicScreenTaskId = task.id;
      this.publicScreenTopic = task.topicId;
    } else {
      this.publicScreenTaskId = '';
      this.publicScreenTopic = '';
    }
  }

  async changePublicScreen(id: string | null): Promise<void> {
    this.publicScreenTaskId = id as string;
  }

  deregisterGetTasks(topics: string[] | null = null): void {
    if (!topics) topics = this.topics.map((t) => t.id);
    topics.forEach(async (topic) => {
      taskService.deregisterGetTaskList(topic, this.updateTasks);
    });
  }

  reloadTopics(topic: Topic): void {
    const storedTopic = this.topics.find((t) => t.id === topic.id);
    if (storedTopic) {
      storedTopic.title = topic.title;
      storedTopic.description = topic.description;
      storedTopic.order = topic.order;
      storedTopic.activeTaskId = topic.activeTaskId;
    }
  }

  deleteTopic(topicId: string): void {
    this.deregisterGetTasks([topicId]);
    const index = this.topics.findIndex((t) => t.id === topicId);
    if (index > -1) {
      this.topics.splice(index, 1);
    }
  }

  updateTasks(tasks: Task[], topicId: string): void {
    const topic = this.topics.find((topic) => topic.id === topicId);
    if (topic) {
      topic.tasks = tasks;
      topic.tasks.sort((a, b) => (a.order > b.order ? 1 : 0));
    }
  }

  async editSession(): Promise<void> {
    this.showSessionSettings = true;
  }

  async deleteSession(): Promise<void> {
    this.deregisterAll();
    setTimeout(() => {
      sessionService.remove(this.sessionId).then((deleted) => {
        if (deleted) {
          sessionService.refreshGetSessionList();
          this.$router.go(-1);
        } else {
          this.onSessionIdChanged();
        }
      });
    }, 100);
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
        topicService.putTopic(topic);
      }
    }
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateSession);
    cashService.deregisterAllGet(this.updateTasks);
    cashService.deregisterAllGet(this.updateTopics);
    cashService.deregisterAllGet(this.updateRole);
    cashService.deregisterAllGet(this.updatePublicTask);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  isDownloading = false;
  download(): void {
    if (this.isDownloading) return;
    this.isDownloading = true;
    sessionService.exportSession(this.sessionId, 'XLSX').then((result) => {
      const blob = this.convertBase64toBlob(
        result.base64,
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      );
      const blobURL = URL.createObjectURL(blob);
      window.open(blobURL, '_self');
      this.isDownloading = false;
    });
  }

  convertBase64toBlob(content: string, contentType: string): Blob {
    contentType = contentType || '';
    const sliceSize = 512;
    const byteCharacters = window.atob(content); //method which converts base64 to binary
    const byteArrays: Uint8Array[] = [];
    for (let offset = 0; offset < byteCharacters.length; offset += sliceSize) {
      const slice = byteCharacters.slice(offset, offset + sliceSize);
      const byteNumbers = new Array(slice.length);
      for (let i = 0; i < slice.length; i++) {
        byteNumbers[i] = slice.charCodeAt(i);
      }
      const byteArray = new Uint8Array(byteNumbers);
      byteArrays.push(byteArray);
    }
    return new Blob(byteArrays, {
      type: contentType,
    }); //statement which creates the blob
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
  background-color: var(--color-background);
  min-height: var(--app-height);

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
  color: var(--color-evaluating);
}

.awesome-icon {
  color: var(--color-dark-contrast-light);
  margin-left: 0.5em;

  &:hover {
    color: white;
    opacity: 0.7;
  }
}
</style>
