<template>
  <div class="participant-background">
    <el-container class="participant-container full-height">
      <el-header class="overview">
        <div class="overview__header">
          <MenuBar />
          <!--<SessionInfo :title="sessionName" :description="sessionDescription" />-->
        </div>
        <div class="overview__infobox">
          <div>
            <span class="overview__info twoLineText">
              {{ sessionName }}
            </span>
            <span
              class="overview__info__description twoLineText"
              v-if="sessionDescription"
            >
              {{ sessionDescription }}
            </span>
          </div>
          <div class="image">
            <img
              src="@/assets/illustrations/planets/brainstorming.png"
              alt="planet"
            />
          </div>
        </div>
      </el-header>
      <el-main>
        <el-collapse v-model="openTabs">
          <el-collapse-item
            v-for="topic in filteredTopics"
            :key="topic.id"
            :name="topic.id"
          >
            <template #title>
              <span class="oneLineText">
                {{ topic.title }}
              </span>
            </template>
            <div
              class="media link"
              v-for="task in topic.tasks"
              :key="task.id"
              :style="{
                '--module-color': getColor(task),
              }"
              v-on:click="
                $router.push(`/participant-module-content/${task.id}`)
              "
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
              <TaskInfo
                class="media-content"
                :taskId="task.id"
                :auth-header-typ="EndpointAuthorisationType.PARTICIPANT"
              />
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
      </el-main>
    </el-container>
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
.participant-container {
  //background-color: white;
  border-right: var(--color-primary) 1px solid;
  border-left: var(--color-primary) 1px solid;
}

.media {
  border-top: 1px solid rgba(128, 128, 128, 0.5);
  //background-color: var(--color-transparent);
  //color: var(--el-color-white);
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
  border: unset;

  .el-collapse-item {
    border-radius: 1rem;
    background-color: white;
    //background-color: var(--color-transparent-dark);
    margin: 1rem;

    &__wrap {
      border: unset;
    }

    &__content {
      padding-bottom: unset;
    }
  }
}

.el-main::v-deep {
  margin-top: -1rem;
}

.overview {
  color: #fff;
  background: var(--color-darkblue);
  background-image: url('~@/assets/illustrations/stars-background-dark.png');
  mask-image: radial-gradient(
      circle farthest-corner at 100% 100%,
      transparent 69%,
      white 70%
    ),
    radial-gradient(
      circle farthest-corner at 0% 100%,
      transparent 69%,
      white 70%
    ),
    linear-gradient(white, white);
  mask-size: 1rem 1rem, 1rem 1rem, 100% calc(100% - 1rem + 1px);
  mask-position: bottom left, bottom right, top left;
  mask-repeat: no-repeat;
  padding-bottom: 1rem;
  //background-attachment: fixed;
  background-size: contain;

  &__module + .overview__module {
    margin-left: 1.5rem;
  }

  &__header {
    padding: 1rem 1rem;
  }

  &__infobox {
    display: flex;
    justify-content: space-between;
    padding: 1rem;

    .image {
      min-width: 5rem;
      max-width: 5rem;

      /*-webkit-transform: rotate(-65deg);
      -moz-transform: rotate(-65deg);
      -ms-transform: rotate(-65deg);
      -o-transform: rotate(-65deg);
      transform: rotate(-65deg) translate(0, -20px);*/
      //padding: 1rem;
    }
  }

  &__info {
    //background-color: var(--color-transparent-dark);
    //color: var(--color-primary);
    font-weight: var(--font-weight-semibold);

    &__description {
      padding-top: 0.5rem;
      font-weight: var(--font-weight-default);
      font-size: var(--font-size-small);
    }
  }
}

.media-left {
  font-size: 2.5rem;
}

.el-collapse-item::v-deep {
  .el-collapse-item__header {
    color: var(--color-primary);
  }
}
</style>
