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
          <div v-if="points">
            <font-awesome-icon icon="coins" /> {{ points }}
          </div>
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
        <el-scrollbar v-if="theme === 'calendar'">
          <table>
            <tr
              v-for="(row, index) in tableTasks[topic.id]"
              :key="index"
              class="dependency-row"
              :style="{
                display: rowVisible(topic.id, index) ? 'table-row' : 'none',
              }"
            >
              <td
                v-for="task in row"
                :id="task.id"
                :key="task.id"
                :rowspan="task.dependency.duration"
                :style="{
                  display: taskIsVisible(task) ? 'table-cell' : 'none',
                  '--rowspan': task.dependency.duration,
                }"
              >
                <el-card
                  class="task-card"
                  :style="{
                    '--module-color': getColor(task),
                  }"
                  :class="{
                    disabled:
                      topicDependencyLimit[topic.id] < task.dependency.start,
                  }"
                  v-on:click="
                    () => {
                      if (
                        topicDependencyLimit[topic.id] >= task.dependency.start
                      )
                        $router.push(`/participant-module-content/${task.id}`);
                    }
                  "
                >
                  <div class="media link">
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
                    <el-dropdown
                      v-if="hasFinishCheckMark(task)"
                      class="media-right"
                    >
                      <font-awesome-icon :icon="['far', 'circle-check']" />
                      <template #dropdown>
                        <el-dropdown-menu>
                          <el-dropdown-item v-on:click="finishTask(task)">
                            {{ $t('participant.view.overview.finishTask') }}
                          </el-dropdown-item>
                        </el-dropdown-menu>
                      </template>
                    </el-dropdown>
                    <Timer
                      v-if="task.remainingTime !== null"
                      :auth-header-typ="EndpointAuthorisationType.PARTICIPANT"
                      class="media-right timer"
                      :entity="task"
                      v-on:timerEnds="refreshTask(task)"
                    ></Timer>
                  </div>
                </el-card>
              </td>
            </tr>
          </table>
        </el-scrollbar>
        <div
          v-else
          class="default-theme media link"
          v-for="task in topic.tasks"
          :key="task.id"
          :style="{
            '--module-color': getColor(task),
            display: taskIsVisible(task) ? 'flex' : 'none',
          }"
          :class="{
            disabled: topicDependencyLimit[topic.id] < task.dependency.start,
          }"
          v-on:click="
            () => {
              if (topicDependencyLimit[topic.id] >= task.dependency.start)
                $router.push(`/participant-module-content/${task.id}`);
            }
          "
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
          <el-dropdown v-if="hasFinishCheckMark(task)" class="media-right">
            <font-awesome-icon :icon="['far', 'circle-check']" />
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item v-on:click="finishTask(task)">
                  {{ $t('participant.view.overview.finishTask') }}
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
          <Timer
            v-if="task.remainingTime !== null"
            :auth-header-typ="EndpointAuthorisationType.PARTICIPANT"
            class="media-right timer"
            :entity="task"
            v-on:timerEnds="refreshTask(task)"
          ></Timer>
        </div>
      </el-collapse-item>
    </el-collapse>
    <!--<router-link
      :to="`/public-screen/${this.sessionId}/${EndpointAuthorisationType.PARTICIPANT}`"
    >
      <el-button class="fullwidth">
        <template #icon>
          <font-awesome-icon :icon="['fac', 'presentation']" />
        </template>
        {{ $t('participant.view.overview.publicScreen') }}
      </el-button>
    </router-link>-->
    <router-link to="/join">
      <el-button class="fullwidth">
        <template #icon>
          <font-awesome-icon icon="sign-out-alt" />
        </template>
        {{ $t('participant.view.overview.disconnect') }}
      </el-button>
    </router-link>
  </ParticipantDefaultContainer>
  <LanguageSettings
    v-model:show-modal="showLanguageSettings"
    :isParticipant="true"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Watch } from 'vue-property-decorator';
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
import * as taskParticipantService from '@/services/task-participant-service';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { TaskParticipantState } from '@/types/api/TaskParticipantState';
import TaskParticipantStatesType from '@/types/enum/TaskParticipantStatesType';
import { ElMessageBox } from 'element-plus';
import { getModuleConfig } from '@/modules';

@Options({
  components: {
    FontAwesomeIcon,
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
  theme: string | null = '';
  openTabs: string[] = [];
  EndpointAuthorisationType = EndpointAuthorisationType;
  avatar!: Avatar;
  showLanguageSettings = false;
  points = 0;
  states: TaskParticipantState[] = [];
  topicDependencyLimit: { [key: string]: number } = {};
  tableTasks: { [key: string]: Task[][] } = {};
  finishManuel: { [key: string]: boolean } = {};

  hasFinishCheckMark(task: Task): boolean {
    if (this.finishManuel[task.id]) {
      const state = this.states.find((item) => item.taskId === task.id);
      if (state) return true;
    }
    return false;
  }

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

  taskIsVisible(task: Task): boolean {
    const state = this.states.find((item) => item.taskId === task.id);
    return !(state && state.state === TaskParticipantStatesType.FINISHED);
  }

  rowVisible(topicId: string, index: number): boolean {
    for (let i = 0; i <= index; i++) {
      const rowTasks = this.tableTasks[topicId][i].filter((task) =>
        this.taskIsVisible(task)
      );
      if (rowTasks.length > 0) return true;
    }
    return false;
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
    cashService.deregisterAllGet(this.updateStates);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  updateSession(session: Session): void {
    this.sessionName = session.title;
    this.sessionDescription = session.description;
    this.sessionId = session.id;
    this.theme = session.theme;
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
        30
      );
    });
    this.topics = topics;
    this.openTabs = this.topics.map((topic) => topic.id);
  }

  @Watch('sessionId', { immediate: true })
  onSessionIdChanged(): void {
    cashService.deregisterAllGet(this.updateStates);
    if (this.sessionId) {
      taskParticipantService.registerGetListFromSession(
        this.sessionId,
        this.updateStates,
        EndpointAuthorisationType.PARTICIPANT,
        60 * 60
      );
    }
  }

  updateStates(states: TaskParticipantState[]): void {
    this.points = taskParticipantService.calculatePoints(states);
    this.states = states;
    this.calculateDisabledList();
  }

  calculateDisabledList(): void {
    for (const topic of this.topics) {
      this.topicDependencyLimit[topic.id] = Number.MAX_VALUE;
      if (topic.tasks) {
        const tasks = topic.tasks.sort((a, b) => {
          if (a.dependency.start === b.dependency.start)
            return a.dependency.duration - b.dependency.duration;
          return a.dependency.start - b.dependency.start;
        });
        for (const task of tasks) {
          const start = task.dependency.start;
          if (start > this.topicDependencyLimit[topic.id]) break;
          const duration = task.dependency.duration;
          const end = start + duration;
          const state = this.states.find((item) => item.taskId === task.id);
          if (!state) {
            this.topicDependencyLimit[topic.id] = start;
            break;
          }
          if (state.state !== TaskParticipantStatesType.FINISHED) {
            if (duration === 1) {
              this.topicDependencyLimit[topic.id] = start;
              break;
            } else {
              this.topicDependencyLimit[topic.id] = end - 1;
            }
          }
        }
      }
    }
  }

  updateTableTasks(): void {
    for (const topic of this.topics) {
      this.tableTasks[topic.id] = [];
      if (topic.tasks) {
        const tasks = topic.tasks;
        let maxRows = 0;
        const tableTasks: Task[][] = [];
        for (const task of tasks) {
          (task.dependency as any).durationInvert = -task.dependency.duration;
          const taskEnd = task.dependency.start + task.dependency.duration;
          if (maxRows < taskEnd) maxRows = taskEnd;
        }
        for (let row = 0; row < maxRows; row++) {
          tableTasks[row] = tasks
            .filter((item) => item.dependency.start === row)
            .sort((a, b) => {
              if (a.dependency.start === b.dependency.start)
                return b.dependency.duration - a.dependency.duration;
              return b.dependency.start - a.dependency.start;
            });
        }
        this.tableTasks[topic.id] = tableTasks;
      }
    }
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
    this.calculateDisabledList();
    this.updateTableTasks();
    this.updateModuleConfig(tasks);
  }

  async updateModuleConfig(tasks: Task[]): Promise<void> {
    for (const task of tasks) {
      this.finishManuel[task.id] = true;
      for (const module of task.modules) {
        const configValue = await getModuleConfig(
          'finishManuel',
          task.taskType.toLowerCase(),
          module.name
        );
        if (configValue === false) {
          this.finishManuel[task.id] = false;
          break;
        }
      }
    }
  }

  get filteredTopics(): Topic[] {
    return this.topics.filter((topic) => topic.tasks && topic.tasks.length > 0);
  }

  finishTask(task: Task): void {
    ElMessageBox.confirm(
      (this as any).$t('participant.view.overview.finishQuestion'),
      (this as any).$t('participant.view.overview.finishTask'),
      {
        confirmButtonText: (this as any).$t('participant.view.overview.yes'),
        cancelButtonText: (this as any).$t('participant.view.overview.no'),
        type: 'warning',
      }
    ).then(() => {
      const state = this.states.find((item) => item.taskId === task.id);
      if (state) {
        state.state = TaskParticipantStatesType.FINISHED;
        taskParticipantService.putParticipantState(task.id, state).then(() => {
          this.calculateDisabledList();
        });
      }
    });
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
    margin-top: 1rem;
    margin-right: 0.5rem;
    margin-left: 0;
  }

  &-right.timer {
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
  pointer-events: auto;
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

table {
  border-collapse: collapse;
  width: 100%;
}

.dependency-row {
  height: 5rem;
  border-top: dashed var(--color-dark-contrast) 1px;

  td {
    height: calc(5rem * var(--rowspan));
    min-width: 12rem;
    padding: 0.1rem;
  }
}

.task-card {
  background-color: color-mix(in srgb, var(--module-color) 30%, transparent);
  height: 100%;
  cursor: pointer;

  .media {
    border-top: unset;
  }

  .media.link svg {
    margin-left: 0.5rem;
    margin-right: 0;
  }

  .media.link .media-right svg {
    margin-left: 0;
    width: unset;
  }
}

.task-card.el-card::v-deep(.el-card__body) {
  padding: 0;
}

.disabled {
  background-color: var(--color-gray-inactive-light);
  cursor: not-allowed;
}

.disabled .link {
  cursor: not-allowed;
}

.default-theme.disabled:last-child {
  border-radius: 0 0 1rem 1rem;
}
</style>
