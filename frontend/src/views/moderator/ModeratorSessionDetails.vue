<template>
  <ModeratorNavigationLayout v-if="session">
    <template v-slot:sidebar>
      <Sidebar
        :is-session="true"
        :session-id="session.id"
        :session-connection-key="session.connectionKey"
        :title="session.title"
        :pretitle="formatDate(session.creationDate)"
        :description="session.description"
        v-on:openSettings="editSession"
        v-on:delete="deleteSession"
      />
    </template>
    <template v-slot:content>
      <el-collapse v-model="openTabs">
        <el-collapse-item
          v-for="(topic, index) in topics"
          :key="topic.id"
          :name="topic.id"
        >
          <template #title>
            <CollapseTitle :text="topic.title">
              <span role="button" class="icon" v-on:click="editTopic(topic.id)">
                <font-awesome-icon icon="pen" />
              </span>
              <span
                role="button"
                class="icon"
                v-on:click="deleteTopic(topic.id)"
              >
                <font-awesome-icon icon="trash" />
              </span>
            </CollapseTitle>
          </template>
          <draggable
            v-model="topics[index].tasks"
            tag="transition-group"
            item-key="order"
            handle=".card__drag"
            @end="dragDone(index)"
          >
            <template #item="{ element }">
              <div class="detail__module">
                <TaskCard
                  :sessionId="sessionId"
                  :type="TaskType[element.taskType]"
                  :task="element"
                  :isOnPublicScreen="element.id === publicScreenTaskId"
                  @changePublicScreen="changePublicScreen($event)"
                />
              </div>
            </template>
          </draggable>
          <AddItem
            :text="$t('moderator.view.sessionDetails.addTask')"
            @addNew="openModalModuleCreate(topic.id)"
          />
        </el-collapse-item>
      </el-collapse>
      <AddItem
        :text="$t('moderator.view.sessionDetails.addTopic')"
        @addNew="showTopicSettings = true"
      />
      <TaskSettings
        v-model:show-modal="showTaskSettings"
        :topic-id="addNewTopicId"
        @taskUpdated="getTopics"
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
    </template>
  </ModeratorNavigationLayout>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import draggable from 'vuedraggable';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import TaskSettings from '@/components/moderator/organisms/settings/TaskSettings.vue';
import TaskCard from '@/components/moderator/organisms/cards/TaskCard.vue';
import ModeratorNavigationLayout from '@/components/moderator/organisms/layout/ModeratorNavigationLayout.vue';
import Sidebar from '@/components/moderator/organisms/Sidebar.vue';
import { formatDate } from '@/utils/date';
import TaskType from '@/types/enum/TaskType';
import * as sessionService from '@/services/session-service';
import * as topicService from '@/services/topic-service';
import * as taskService from '@/services/task-service';
import { Session } from '@/types/api/Session';
import { Topic } from '@/types/api/Topic';
import { convertToSaveVersion } from '@/types/api/Task';
import { EventType } from '@/types/enum/EventType';
import TopicSettings from '@/components/moderator/organisms/settings/TopicSettings.vue';
import CollapseTitle from '@/components/moderator/atoms/CollapseTitle.vue';
import SessionSettings from '@/components/moderator/organisms/settings/SessionSettings.vue';

@Options({
  components: {
    AddItem,
    draggable,
    TaskSettings,
    TopicSettings,
    TaskCard,
    ModeratorNavigationLayout,
    Sidebar,
    CollapseTitle,
    SessionSettings,
  },
})
export default class ModeratorSessionDetails extends Vue {
  @Prop() readonly sessionId!: string;

  session: Session | null = null;
  topics: Topic[] = [];
  publicScreenTaskId = '';
  showTaskSettings = false;
  showTopicSettings = false;
  showSessionSettings = false;
  formatDate = formatDate;
  addNewTopicId = '';
  editTopicId = '';
  errors: string[] = [];
  openTabs: string[] = [];

  TaskType = TaskType;

  async mounted(): Promise<void> {
    this.eventBus.off(EventType.CHANGE_PUBLIC_SCREEN);
    this.eventBus.on(EventType.CHANGE_PUBLIC_SCREEN, async (id) => {
      await this.changePublicScreen(id as string);
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
        this.openTabs = this.topics.map((topic) => topic.id);
        this.getPublicScreen();
      });
    });
  }

  editTopic(topicId: string): void {
    this.editTopicId = topicId;
    this.showTopicSettings = true;
  }

  deleteTopic(topicId: string): void {
    topicService.deleteTopic(topicId).then(() => this.getTopics());
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
      }
    });
  }

  openModalModuleCreate(topicId: string): void {
    this.addNewTopicId = topicId;
    this.showTaskSettings = true;
  }

  dragDone(topicIndex: number): void {
    const tasks = this.topics[topicIndex].tasks;
    if (tasks) {
      for (let i = 0; i < tasks.length; i++) {
        tasks[i].order = i;
        taskService.putTask(tasks[i].id, convertToSaveVersion(tasks[i]));
      }
    }
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

  &__module + .detail__module {
    margin-top: 1.5rem;
  }
}
</style>
