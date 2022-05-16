<template>
  <ParticipantDefaultContainer class="participant-overview">
    <template #header>
      <div class="participant-header__header">
        <MenuBar />
      </div>
      <div class="participant-header__infobox">
        <div>
          <span class="participant-header__info twoLineText">
            {{ sessionName }}
          </span>
          <span
            class="participant-header__info__description twoLineText"
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
    </template>
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
          v-on:click="$router.push(`/participant-module-content/${task.id}`)"
        >
          <font-awesome-icon
            :icon="getIcon(task)"
            class="media-left"
            :style="{
              color: getColor(task),
            }"
          />
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
    <router-link
      :to="`/public-screen/${this.sessionId}/${EndpointAuthorisationType.PARTICIPANT}`"
    >
      <el-button class="fullwidth">
        <template #icon>
          <font-awesome-icon :icon="['fac', 'presentation']" />
        </template>
        {{ $t('participant.view.overview.publicScreen') }}
      </el-button>
    </router-link>
    <router-link to="/join">
      <el-button class="fullwidth">
        <template #icon>
          <font-awesome-icon icon="sign-out-alt" />
        </template>
        {{ $t('participant.view.overview.disconnect') }}
      </el-button>
    </router-link>
  </ParticipantDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import MenuBar from '@/components/participant/molecules/Menubar.vue';
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
import ParticipantDefaultContainer from '@/components/participant/organisms/layout/ParticipantDefaultContainer.vue';

@Options({
  components: {
    ParticipantDefaultContainer,
    Timer,
    TaskInfo,
    MenuBar,
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
.el-button.fullwidth::v-deep {
  margin: 0 1rem 1rem;
  width: calc(100% - 2rem);
  justify-content: flex-start;

  .el-icon {
    margin-right: 5.6rem;
  }
}

.participant-overview::v-deep {
  .el-main {
    display: block;

    .el-collapse {
      margin-bottom: 1rem;
    }
  }
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

.media.link svg {
  margin: auto 0;
  margin-left: 1.5rem;
  margin-right: 0.5rem;
  width: 40px;
}

.participant-header {
  &__header {
    padding-bottom: 2rem;
  }

  &__infobox {
    display: flex;
    justify-content: space-between;

    .image {
      min-width: 5rem;
      max-width: 5rem;
    }
  }

  &__info {
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

.el-collapse::v-deep {
  margin-bottom: unset;
  --el-collapse-header-font-color: white;
  border: unset;
  width: 100%;

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
</style>
