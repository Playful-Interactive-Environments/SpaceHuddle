<template>
  <div v-if="session" class="detail">
    <Sidebar
      :session="session"
      :title="session.title"
      :pretitle="formatDate(session.creationDate)"
      :description="session.description"
    />
    <main class="detail__content">
      <TopicExpand v-for="(topic, index) in topics" :key="topic">
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
                />
              </li>
            </template>
          </draggable>
          <AddItem text="Add module" @addNew="addModule" />
        </template>
      </TopicExpand>
      <button @click="addTopic">add topic test</button>
    </main>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import draggable from 'vuedraggable';
import Sidebar from '@/components/moderator/organisms/Sidebar.vue';
import ModuleCard from '@/components/shared/molecules/ModuleCard.vue';
import TopicExpand from '@/components/shared/atoms/TopicExpand.vue';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import { Prop } from 'vue-property-decorator';
import * as sessionService from '@/services/moderator/session-service';
import * as topicService from '@/services/moderator/topic-service';
import { formatDate } from '@/utils/date';
import { Session } from '@/services/moderator/session-service';
import { Topic } from '../../services/moderator/topic-service';
import ModuleType from '@/types/ModuleType';

@Options({
  components: {
    Sidebar,
    ModuleCard,
    TopicExpand,
    draggable,
    AddItem,
  },
})
export default class ModeratorSessionDetails extends Vue {
  @Prop() readonly sessionId!: string;

  session: Session | null = null;
  topics: Topic[] = [];
  formatDate = formatDate;

  ModuleType = ModuleType;

  async mounted(): Promise<void> {
    this.session = await sessionService.getById(this.sessionId);
    this.topics = await sessionService.getTopicsList(this.session.id);
    this.topics.forEach(async (topic) => {
      topic.tasks = await topicService.getTaskList(topic.id);
    });
  }

  async addTopic(): Promise<void> {
    const data = await sessionService.postTopic(this.sessionId, {
      title: 'Test topic',
      description: 'asdf',
    });
    console.log('topic added', data);
  }

  addModule(): void {
    console.log('add module');
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
    padding: 2rem 3rem;
  }

  &__module + .detail__module {
    margin-top: 1.5rem;
  }
}
</style>
