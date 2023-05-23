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
        <div class="image" v-if="avatar">
          <el-tooltip
            :content="$t('participant.view.overview.avatar')"
            placement="top"
          >
            <span class="avatar" v-on:click="showLanguageSettings = true">
              <span>
                <font-awesome-icon
                  :icon="avatar.symbol"
                  :style="{ color: avatar.color }"
                ></font-awesome-icon>
              </span>
            </span>
          </el-tooltip>
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
            v-on:timerEnds="refreshTask(task)"
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
  <LanguageSettings v-model:show-modal="showLanguageSettings" />
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
import { Session } from '@/types/api/Session';
import * as cashService from '@/services/cash-service';
import { Avatar } from '@/types/api/Participant';
import * as authService from '@/services/auth-service';
import LanguageSettings from '@/components/moderator/organisms/settings/LanguageSettings.vue';

@Options({
  components: {
    LanguageSettings,
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
  openTabs: string[] = [];
  EndpointAuthorisationType = EndpointAuthorisationType;
  avatar!: Avatar;
  showLanguageSettings = false;

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

  topicCash!: cashService.SimplifiedCashEntry<Topic[]>;
  mounted(): void {
    this.avatar = authService.getAvatar();
    sessionService.registerGetParticipantSession(
      this.updateSession,
      EndpointAuthorisationType.PARTICIPANT,
      60 * 60
    );
    this.topicCash = participantService.registerGetTopicList(
      this.updateTopics,
      EndpointAuthorisationType.PARTICIPANT,
      60 * 60
    );
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateSession);
    cashService.deregisterAllGet(this.updateTopics);
    cashService.deregisterAllGet(this.updateTasks);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  updateSession(session: Session): void {
    this.sessionName = session.title;
    this.sessionDescription = session.description;
    this.sessionId = session.id;
  }

  updateTopics(topics: Topic[]): void {
    const deletedTopics = this.topics
      .filter((tOld) => {
        return !topics.find((tNew) => tNew.id === tOld.id);
      })
      .map((t) => t.id);
    this.deregisterGetTasks(deletedTopics);
    const newTopics = topics.filter((tNew) => {
      return !this.topics.find((tOld) => tNew.id === tOld.id);
    });
    newTopics.forEach(async (topic) => {
      taskService.registerGetTaskList(
        topic.id,
        this.updateTasks,
        EndpointAuthorisationType.PARTICIPANT,
        10
      );
    });
    this.topics = topics;
    this.openTabs = this.topics.map((topic) => topic.id);
  }

  refreshTopics(): void {
    this.topicCash.refreshData();
  }

  refreshTask(task: Task): void {
    taskService.refreshGetTaskList(task.topicId);
  }

  deregisterGetTasks(topics: string[] | null = null): void {
    if (!topics) topics = this.topics.map((t) => t.id);
    topics.forEach(async (topic) => {
      taskService.deregisterGetTaskList(topic, this.updateTasks);
    });
  }

  updateTasks(tasks: Task[], topicId: string): void {
    const topic = this.topics.find((topic) => topic.id === topicId);
    if (topic) {
      topic.tasks = tasks;
      topic.tasks.sort((a, b) => (a.order > b.order ? 1 : 0));
    }
  }

  get filteredTopics(): Topic[] {
    return this.topics.filter((topic) => topic.tasks && topic.tasks.length > 0);
  }
}
</script>

<style lang="scss" scoped>
.el-button.fullwidth {
  margin: 0 1rem 1rem;
  width: calc(100% - 2rem);
  justify-content: flex-start;
}

.el-button.fullwidth::v-deep(.el-icon) {
  margin-right: 5.6rem;
}

.participant-overview::v-deep(.el-main) {
  display: block;

  .el-collapse {
    margin-bottom: 1rem;
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
      max-width: 7rem;
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

.el-collapse {
  margin-bottom: unset;
  --el-collapse-header-font-color: white;
  border: unset;
  width: 100%;
}

.el-collapse::v-deep(.el-collapse-item) {
  border-radius: 1rem;
  background-color: white;
  //background-color: var(--color-transparent-dark);
  margin: 1rem;
}

.el-collapse::v-deep(.el-collapse-item__wrap) {
  border: unset;
}

.el-collapse::v-deep(.el-collapse-item__content) {
  padding-bottom: unset;
}

.avatar {
  display: block;
  border-radius: 20rem;
  border: 3px white solid;
  width: 4rem;
  aspect-ratio: 1;
  padding: 0.2rem;
  text-align: center;
  vertical-align: center;

  span {
    display: block;
    padding: 0.5rem;
    font-size: 2rem;
    width: 100%;
    height: 100%;
    border-radius: 20rem;
    background-color: white;
  }
}
</style>
