<template>
  <div class="overview container--fullheight">
    <div class="container">
      <MenuBar />
      <SessionInfo :title="sessionName" :description="sessionDescription" />
      <form-error :errors="errors"></form-error>
    </div>
    <TopicExpand v-for="topic in filteredTopics" :key="topic.id" :isRow="true">
      <template v-slot:title>{{ topic.title }}</template>
      <template v-slot:content>
        <li class="overview__module" v-for="task in topic.tasks" :key="task.id">
          <ModuleCard
            :type="ModuleType[task.taskType]"
            :task="task"
            :isClient="true"
            :sessionId="sessionId"
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
import ModuleType from '@/types/ModuleType';
import * as taskService from '@/services/task-service';
import * as participantService from '@/services/participant-service';
import * as sessionService from '@/services/session-service';
import { Topic } from '@/services/topic-service';
import FormError from '@/components/shared/atoms/FormError.vue';

@Options({
  components: {
    MenuBar,
    SessionInfo,
    TopicExpand,
    ModuleCard,
    FormError
  },
})
export default class ClientOverview extends Vue {
  topics: Topic[] = [];
  ModuleType = ModuleType;
  sessionName = '';
  sessionDescription = '';
  sessionId = '';
  errors: string[] = [];

  mounted(): void {
    this.getSessionInfo();
    this.getTopicsAndTasks();
  }

  get filteredTopics(): Topic[] {
    return this.topics.filter((topic) => topic.tasks && topic.tasks.length > 0);
  }

  async getTopicsAndTasks(): Promise<void> {
    participantService.getTopicList().then((queryResult) => {
      this.topics = queryResult;
      this.topics.forEach(async (topic) => {
        taskService.getTaskList(topic.id).then((queryResult) => {
          topic.tasks = queryResult;
        });
      });
    });
  }

  async getSessionInfo(): Promise<void> {
    sessionService.getClientSession().then((queryResult) => {
      this.sessionName = queryResult.title;
      this.sessionDescription = queryResult.description;
      this.sessionId = queryResult.id;
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
