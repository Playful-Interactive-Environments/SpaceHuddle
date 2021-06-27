<template>
  <div v-if="session" class="detail">
    <Sidebar
      :session="session"
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
            item-key="id"
          >
            <template #item="{ element }">
              <li class="detail__module">
                <ModuleCard
                  :sessionId="sessionId"
                  :type="ModuleType[element.taskType]"
                  :task="element"
                  :isOnPublicScreen="element.id === publicScreenTaskId"
                  @changePublicScreen="changePublicScreen($event)"
                />
              </li>
            </template>
          </draggable>
          <AddItem
            text="Add module"
            @addNew="openModalModuleCreate(topic.id)"
          />
        </template>
      </TopicExpand>
    </main>
    <ModalModuleCreate
      v-model:show-modal="showModalModuleCreate"
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
import ModalModuleCreate from '@/components/shared/molecules/ModalModuleCreate.vue';
import ModuleCard from '@/components/shared/molecules/ModuleCard.vue';
import Navigation from '@/components/moderator/molecules/Navigation.vue';
import TopicExpand from '@/components/shared/atoms/TopicExpand.vue';
import Sidebar from '@/components/moderator/organisms/Sidebar.vue';
import { formatDate } from '@/utils/date';
import ModuleType from '@/types/ModuleType';
import SnackbarType from '@/types/SnackbarType';
import * as sessionService from '@/services/session-service';
import * as topicService from '@/services/topic-service';
import { Session } from '@/services/session-service';
import { Topic } from '../../services/topic-service';

@Options({
  components: {
    AddItem,
    draggable,
    ModalModuleCreate,
    ModuleCard,
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
  showModalModuleCreate = false;
  formatDate = formatDate;
  addNewTopicId = '';

  ModuleType = ModuleType;

  async mounted(): Promise<void> {
    await this.getTopics();
  }

  async getTopics(): Promise<void> {
    this.session = await sessionService.getById(this.sessionId);
    this.topics = await sessionService.getTopicsList(this.session.id);
    this.topics.forEach(async (topic) => {
      topic.tasks = await topicService.getTaskList(topic.id);
    });
    await this.getPublicScreen();

    this.eventBus.on('changePublicScreen', async (id) => {
      this.publicScreenTaskId = id as string;
      // TODO: change endpoint to toggle public screen
      let data = await sessionService.displayOnPublicScreen(
        this.sessionId,
        this.publicScreenTaskId
      );
      this.eventBus.emit('showSnackbar', {
        type: SnackbarType.SUCCESS,
        message: 'Successfully updated public screen.',
      });
    });
  }

  addModule(): void {
    console.log('add module');
  }

  async getPublicScreen(): Promise<void> {
    let data = await sessionService.getPublicScreen(this.sessionId);
    if (data) {
      this.publicScreenTaskId = data.id;
    }
  }

  openModalModuleCreate(topicId: string): void {
    this.addNewTopicId = topicId;
    this.showModalModuleCreate = true;
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
