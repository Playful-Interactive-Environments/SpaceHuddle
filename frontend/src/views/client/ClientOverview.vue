<template>
  <div class="overview container--fullheight">
    <div class="container">
      <MenuBar />
      <SessionInfo
        :title="'GAB project meeting'"
        :description="'The purpose of this meeting is to find a name for our new game assisted brainstroming application.'"
      />
    </div>
    <TopicExpand v-for="topic in filteredTopics" :key="topic.id" :isRow="true">
      <template v-slot:title>{{ topic.title }}</template>
      <template v-slot:content>
        <li class="overview__module" v-for="task in topic.tasks" :key="task.id">
          <ModuleCard
            :type="ModuleType[task.taskType]"
            :task="task"
            :isClient="true"
            :sessionId="sessionKey"
          />
        </li>
      </template>
    </TopicExpand>
  </div>
</template>

<script lang="ts">
import { Vue, Options } from 'vue-class-component';
import MenuBar from '@/components/client/molecules/Menubar.vue';
import SessionInfo from '@/components/client/molecules/SessionInfo.vue';
import TopicExpand from '@/components/shared/atoms/TopicExpand.vue';
import ModuleCard from '@/components/shared/molecules/ModuleCard.vue';
import ModuleType from '../../types/ModuleType';
import { Prop } from 'vue-property-decorator';
import * as topicService from '@/services/topic-service';
import * as participantService from '@/services/participant-service';
import { Topic } from '@/services/topic-service';

@Options({
  components: {
    MenuBar,
    SessionInfo,
    TopicExpand,
    ModuleCard,
  },
})
export default class ClientOverview extends Vue {
  @Prop({ required: true }) sessionKey!: string;

  topics: Topic[] = [];
  ModuleType = ModuleType;

  async mounted(): Promise<void> {
    await this.getTopicsAndTasks();
  }

  get filteredTopics(): Topic[] {
    return this.topics.filter((topic) => topic.tasks && topic.tasks.length > 0);
  }

  async getTopicsAndTasks(): Promise<void> {
    this.topics = await participantService.getTopicList();
    this.topics.forEach(async (topic) => {
      topic.tasks = await topicService.getParticipantTasks(topic.id);
    });
  }
}
</script>

<style lang="scss" scoped>
.overview {
  color: #fff;
  background: var(--color-darkblue);
  background-image: url('../../assets/illustrations/stars-background.png');
  background-attachment: fixed;
  background-repeat: no-repeat;
  background-size: cover;

  &__module + .overview__module {
    margin-left: 1.5rem;
  }
}
</style>
