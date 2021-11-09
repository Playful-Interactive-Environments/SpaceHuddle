<template>
  <div class="participant-background">
    <div class="participant-container overview full-height">
      <div class="overview__header">
        <MenuBar />
        <SessionInfo :title="sessionName" :description="sessionDescription" />
      </div>
      <el-collapse v-model="openTabs" class="white participant">
        <el-collapse-item
          v-for="topic in filteredTopics"
          :key="topic.id"
          :name="topic.id"
        >
          <template #title>
            {{ topic.title }}
          </template>
          <span
            class="overview__module"
            v-for="task in topic.tasks"
            :key="task.id"
          >
            <TaskCard
              :type="TaskType[task.taskType]"
              :task="task"
              isParticipant="true"
              :sessionId="sessionId"
              v-on:timerEnds="getTopicsAndTasks"
            />
          </span>
        </el-collapse-item>
      </el-collapse>
    </div>
  </div>
</template>

<script lang="ts">
import { Vue, Options } from 'vue-class-component';
import MenuBar from '@/components/participant/molecules/Menubar.vue';
import SessionInfo from '@/components/participant/molecules/SessionInfo.vue';
import TaskCard from '@/components/moderator/organisms/cards/TaskCard.vue';
import TaskType from '@/types/enum/TaskType';
import * as taskService from '@/services/task-service';
import * as participantService from '@/services/participant-service';
import * as sessionService from '@/services/session-service';
import { Topic } from '@/types/api/Topic';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

@Options({
  components: {
    MenuBar,
    SessionInfo,
    TaskCard,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ParticipantOverviewOld extends Vue {
  topics: Topic[] = [];
  TaskType = TaskType;
  sessionName = '';
  sessionDescription = '';
  sessionId = '';
  readonly intervalTime = 10000;
  interval!: any;
  openTabs: string[] = [];

  mounted(): void {
    this.getSessionInfo();
    this.getTopicsAndTasks();
    this.startIdeaInterval();
  }

  startIdeaInterval(): void {
    this.interval = setInterval(this.getTopicsAndTasks, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }

  get filteredTopics(): Topic[] {
    return this.topics.filter((topic) => topic.tasks && topic.tasks.length > 0);
  }

  async getTopicsAndTasks(): Promise<void> {
    participantService
      .getTopicList(EndpointAuthorisationType.PARTICIPANT)
      .then(async (topics) => {
        for (let i = 0; i < topics.length; i++) {
          const topic = topics[i];
          await taskService
            .getTaskList(topic.id, EndpointAuthorisationType.PARTICIPANT)
            .then((queryResult) => {
              topic.tasks = queryResult;
            });
        }
        this.topics = topics;
        this.openTabs = this.topics.map((topic) => topic.id);
      });
  }

  async getSessionInfo(): Promise<void> {
    sessionService
      .getParticipantSession(EndpointAuthorisationType.PARTICIPANT)
      .then((queryResult) => {
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
  background: url('~@/assets/illustrations/stars-background-repeat.png');
  //background-attachment: fixed;
  background-size: contain;

  &__module + .overview__module {
    margin-left: 1.5rem;
  }

  &__header {
    padding: 1rem 2rem;
  }
}
</style>
