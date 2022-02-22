<template>
  <div class="participant-background">
    <div class="participant-container overview full-height">
      <div class="overview__header">
        <MenuBar />
        <SessionInfo :title="sessionName" :description="sessionDescription" />
      </div>
      <el-collapse v-model="openTabs">
        <el-collapse-item
          v-for="topic in filteredTopics"
          :key="topic.id"
          :name="topic.id"
        >
          <template #title>
            {{ topic.title }}
          </template>
          <div
            class="media link"
            v-for="task in topic.tasks"
            :key="task.id"
            :style="{
              '--module-color': getColor(task),
            }"
            v-on:click="$router.push(`/participant-module-content/${task.id}`)"
          >
            <font-awesome-icon
              :icon="getIcon(task)"
              class="media-left"
              :style="{
                color: getColor(task),
              }"
            />
            <!--<img
              :src="
                require(`@/assets/illustrations/planets/${
                  TaskType[task.taskType]
                }.png`)
              "
              alt="planet"
              class="media-left"
            />-->
            <TaskInfo class="media-content" :taskId="task.id" />
            <Timer
              v-if="task.remainingTime !== null"
              :auth-header-typ="EndpointAuthorisationType.PARTICIPANT"
              class="media-right"
              :entity="task"
              v-on:timerEnds="getTopicsAndTasks"
            ></Timer>
          </div>
        </el-collapse-item>
      </el-collapse>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import MenuBar from '@/components/participant/molecules/Menubar.vue';
import SessionInfo from '@/components/participant/molecules/SessionInfo.vue';
import TaskType from '@/types/enum/TaskType';
import * as taskService from '@/services/task-service';
import * as participantService from '@/services/participant-service';
import * as sessionService from '@/services/session-service';
import { Topic } from '@/types/api/Topic';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import TaskInfo from '@/components/shared/molecules/TaskInfo.vue';
import Timer from '@/components/shared/atoms/Timer.vue';
import { getColorOfType, getIconOfType } from '@/types/enum/TaskCategory';
import { Task } from '@/types/api/Task';

@Options({
  components: {
    Timer,
    TaskInfo,
    MenuBar,
    SessionInfo,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ParticipantOverview extends Vue {
  topics: Topic[] = [];
  TaskType = TaskType;
  sessionName = '';
  sessionDescription = '';
  sessionId = '';
  readonly intervalTime = 10000;
  interval!: any;
  openTabs: string[] = [];
  EndpointAuthorisationType = EndpointAuthorisationType;

  getColor(task: Task): string | undefined {
    if (task.taskType) {
      return getColorOfType(TaskType[task.taskType.toUpperCase()]);
    }
  }

  getIcon(task: Task): string | undefined {
    if (task.taskType) {
      return getIconOfType(TaskType[task.taskType.toUpperCase()]);
    }
  }

  mounted(): void {
    this.getSessionInfo();
    this.getTopicsAndTasks();
    this.startInterval();
  }

  startInterval(): void {
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
.media {
  background-color: var(--color-transparent);
  &-left {
    margin: 1rem;
    width: 5rem;
  }

  &-content {
    margin: 1rem;
  }

  &-right {
    background-color: var(--module-color);
    margin: 1rem;
  }

  + .media {
    margin-top: unset;
    padding-top: unset;
  }
}

.el-collapse::v-deep {
  margin-bottom: unset;
  --el-collapse-header-font-color: white;

  .el-collapse-item__content {
    padding-bottom: unset;
  }
}

.overview {
  color: #fff;
  background: var(--color-darkblue);
  background: url('~@/assets/illustrations/stars-background-dark.png');
  //background-attachment: fixed;
  background-size: contain;

  &__module + .overview__module {
    margin-left: 1.5rem;
  }

  &__header {
    padding: 1rem 2rem;
  }
}

.media-left {
  font-size: 2.5rem;
}
</style>
