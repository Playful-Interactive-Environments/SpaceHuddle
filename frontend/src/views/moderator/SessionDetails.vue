<template>
  <div v-if="session" class="detail">
    <Sidebar :session="session" />
    <main class="detail__content">
      <div v-for="(topic, index) in topics" :key="topic">
        <TopicExpand>
          <template v-slot:title>Topic Uno</template>
          <template v-slot:content>
            <draggable
              v-model="topics[index]"
              tag="transition-group"
              item-key="type"
            >
              <template #item="{ element }">
                <li class="detail__module">
                  <ModuleItem :type="element.type" />
                </li>
              </template>
            </draggable>
            <AddItem text="Add module" @addNew="addModule" />
          </template>
        </TopicExpand>
      </div>
    </main>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import draggable from 'vuedraggable';
import Sidebar from '@/components/moderator/organisms/Sidebar.vue';
import ModuleItem from '@/components/moderator/molecules/ModuleItem.vue';
import TopicExpand from '@/components/shared/atoms/TopicExpand.vue';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import { Prop } from 'vue-property-decorator';
import * as sessionService from '@/services/moderator/session-service';
import * as topicService from '@/services/moderator/topic-service';
import { Topic } from '@/services/moderator/topic-service';
import { Session } from '@/services/moderator/session-service';

@Options({
  components: {
    Sidebar,
    ModuleItem,
    TopicExpand,
    draggable,
    AddItem,
  },
})
export default class SessionDetails extends Vue {
  @Prop() readonly id!: string;

  session: Session | null = null;
  topics: Topic[] = [];
  public topicExpanded = true;

  async mounted(): Promise<void> {
    this.session = await sessionService.getById(this.id);
    this.topics = await sessionService.getTopicsList(this.session.id);
    await this.topics.forEach(async (topic) => {
      // TODO: call task endpoint to get the tasks for the topic
      const currentTaskList = await topicService.getTaskList(topic.id);
      console.log(currentTaskList);
    });
    console.log(this.topics);
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
