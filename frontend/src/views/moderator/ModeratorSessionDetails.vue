<template>
  <div v-if="session" class="detail">
    <Sidebar :session="session" />
    <main class="detail__content">
      <TopicExpand v-for="(topic, index) in topics" :key="topic">
        <template v-slot:title>Topic Uno</template>
        <template v-slot:content>
          <draggable
            v-model="topics[index]"
            tag="transition-group"
            item-key="type"
          >
            <template #item="{ element }">
              <li class="detail__module">
                <ModuleCard :type="element.type" />
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
import { Session } from '@/services/moderator/session-service';
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

  // TODO: Exchange topics definition once CORS issues for task and topic endpoints are resolved
  // topics: Topic[] = [];
  public topics = [
    [
      { type: ModuleType.BRAINSTORMING },
      { type: ModuleType.SELECTION },
      { type: ModuleType.VOTING },
    ],
    [{ type: ModuleType.BRAINSTORMING }, { type: ModuleType.CATEGORIZATION }],
  ];
  public topicExpanded = true;

  async mounted(): Promise<void> {
    this.session = await sessionService.getById(this.sessionId);
    const topics = await sessionService.getTopicsList(this.session.id);
    console.log('topics fetched', topics);
    // TODO: fetch tasks for every topic correctly
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
