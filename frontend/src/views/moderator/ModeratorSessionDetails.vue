<template>
  <div v-if="session" class="detail">
    <Sidebar
      :is-session="true"
      :session-id="session.id"
      :session-connection-key="session.connectionKey"
      :title="session.title"
      :pretitle="formatDate(session.creationDate)"
      :description="session.description"
    />
    <Navigation />
    <main class="detail__content">
      <TopicExpand v-for="(topic, index) in topics" :key="topic.id">
        <template v-slot:title>{{ topic.title }}</template>
        <template v-slot:content>
          <draggable
            v-model="topics[index].tasks"
            tag="transition-group"
            item-key="order"
            handle=".task-card__drag"
            @end="dragDone(index)"
          >
            <template #item="{ element }">
              <li class="detail__module">
                <TaskCard
                  :sessionId="sessionId"
                  :type="TaskType[element.taskType]"
                  :task="element"
                  :isOnPublicScreen="element.id === publicScreenTaskId"
                  @changePublicScreen="changePublicScreen($event)"
                />
              </li>
            </template>
          </draggable>
          <AddItem
            :text="$t('moderator.organism.module.overview.add')"
            @addNew="openModalModuleCreate(topic.id)"
          />
        </template>
      </TopicExpand>
    </main>
    <ModalTaskCreate
      v-model:show-modal="showModalTaskCreate"
      :topic-id="addNewTopicId"
      @moduleCreated="getTopics"
    />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import draggable from 'vuedraggable';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import ModalTaskCreate from '@/components/shared/molecules/ModalTaskCreate.vue';
import TaskCard from '@/components/shared/molecules/TaskCard.vue';
import Navigation from '@/components/moderator/molecules/Navigation.vue';
import TopicExpand from '@/components/shared/atoms/TopicExpand.vue';
import Sidebar from '@/components/moderator/organisms/Sidebar.vue';
import { formatDate } from '@/utils/date';
import TaskType from '@/types/enum/TaskType';
import SnackbarType from '@/types/enum/SnackbarType';
import * as sessionService from '@/services/session-service';
import * as topicService from '@/services/topic-service';
import * as taskService from '@/services/task-service';
import { Session } from '@/types/api/Session';
import { Topic } from '@/types/api/Topic';
import { convertToSaveVersion } from '@/types/api/Task';
import { EventType } from '@/types/enum/EventType';
import {
  getErrorMessage,
  addError,
  clearErrors,
} from '@/services/exception-service';

@Options({
  components: {
    AddItem,
    draggable,
    ModalTaskCreate,
    TaskCard,
    Navigation,
    TopicExpand,
    Sidebar,
  },
})
export default class ModeratorSessionDetails extends Vue {
  @Prop() readonly sessionId!: string;

  session: Session | null = null;
  topics: Topic[] = [];
  publicScreenTaskId = '';
  showModalTaskCreate = false;
  formatDate = formatDate;
  addNewTopicId = '';
  errors: string[] = [];

  TaskType = TaskType;

  async mounted(): Promise<void> {
    this.eventBus.off(EventType.CHANGE_PUBLIC_SCREEN);
    this.eventBus.on(EventType.CHANGE_PUBLIC_SCREEN, async (id) => {
      await this.changePublicScreen(id as string);
    });
    await this.getTopics();
  }

  async changePublicScreen(id: string | null): Promise<void> {
    clearErrors(this.errors);
    this.publicScreenTaskId = id as string;
    sessionService
      .displayOnPublicScreen(this.sessionId, this.publicScreenTaskId)
      .then(
        () => {
          this.eventBus.emit(EventType.SHOW_SNACKBAR, {
            type: SnackbarType.SUCCESS,
            message: 'info.updatePublicScreen',
          });
        },
        (error) => {
          addError(this.errors, getErrorMessage(error));
        }
      );
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

  async getPublicScreen(): Promise<void> {
    sessionService.getPublicScreen(this.sessionId).then((queryResult) => {
      if (queryResult) {
        this.publicScreenTaskId = queryResult.id;
      }
    });
  }

  openModalModuleCreate(topicId: string): void {
    this.addNewTopicId = topicId;
    this.showModalTaskCreate = true;
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
  margin-left: var(--sidebar-width);
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
