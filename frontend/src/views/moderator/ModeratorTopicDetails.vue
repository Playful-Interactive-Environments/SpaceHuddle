<template>
  <ModeratorNavigationLayout
    v-if="topic"
    :current-route-title="topic.title"
    :key="componentLoadIndex"
  >
    <template v-slot:sidebar>
      <Sidebar
        :title="topic.title"
        :description="topic.description"
        :canModify="isModerator"
        v-on:openSettings="editTopic"
        v-on:delete="deleteTopic"
      >
        <template #settings>
          <TutorialStep
            v-if="isModerator"
            type="sessionDetails"
            step="coModerator"
            :order="4"
          >
            <span v-on:click="showRoles = true">
              <font-awesome-icon class="icon" icon="users"></font-awesome-icon>
            </span>
          </TutorialStep>
          <span v-on:click="download">
            <font-awesome-icon class="icon" icon="download"></font-awesome-icon>
          </span>
          <el-dropdown>
            <span class="el-dropdown-link">
              <font-awesome-icon class="icon" icon="info-circle" />
            </span>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item v-on:click="reactivateTutorial">
                  {{ $t('tutorial.reactivate') }}
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </template>
        <template #headerContent>
          <div v-if="activeTask">
            <span class="label">
              {{ $t('moderator.view.topicDetails.participantCount') }}: &emsp;
            </span>
            <span>{{ activeTask.participantCount }}</span>
          </div>
        </template>
        <template #footerContent>
          <TaskTimeline
            v-if="tasks"
            direction="vertical"
            :topicId="topicId"
            :sessionId="sessionId"
            :activeTaskId="activeTaskId"
            v-on:changeActiveElement="changeTask"
          ></TaskTimeline>
          <SessionCode v-if="session" :code="session.connectionKey" />
          <router-link
            v-if="sessionId"
            :to="`/public-screen/${sessionId}`"
            target="_blank"
          >
            <button class="btn btn--mint btn--fullwidth">
              {{ $t('general.publicScreen') }}
            </button>
          </router-link>
        </template>
      </Sidebar>
    </template>
    <template v-slot:content>
      <el-tabs v-model="activeTab">
        <el-tab-pane
          v-for="taskType in Object.values(TaskType)"
          :key="taskType"
          :label="$t(`enum.taskType.${taskType}`)"
          :name="taskType"
          :disabled="!taskTypeAvailable(taskType)"
        >
          <template #label>
            <img
              :src="require(`@/assets/illustrations/planets/${taskType}.png`)"
              alt="planet"
              style="width: 1.5rem"
            />
            <TutorialStep
              type="topicDetails"
              step="taskType"
              :order="1"
              :width="450"
              placement="bottom"
              :disableTutorial="taskType !== TaskType.BRAINSTORMING"
            >
              <span
                class="taskType"
                :style="{ '--module-color': TaskTypeColor[taskType] }"
              >
                {{ $t(`enum.taskType.${taskType}`) }}
              </span>
            </TutorialStep>
          </template>
          <el-space wrap>
            <TutorialStep
              v-for="(task, index) in tasks.filter(
                (task) => TaskType[task.taskType] === taskType
              )"
              :key="task.id"
              type="topicDetails"
              step="selectTask"
              :order="3"
              :displayAllDuplicates="true"
              :disableTutorial="index !== 0"
            >
              <el-card
                v-on:click="changeTask(task)"
                :style="{ '--module-color': TaskTypeColor[taskType] }"
                class="link"
                :class="{ selected: isActive(task) }"
              >
                <p class="media">
                  <font-awesome-icon
                    class="media-left"
                    :icon="getModuleIcon(task)"
                  ></font-awesome-icon>
                  <span class="media-content"></span>
                  <el-dropdown
                    class="card__menu media-right"
                    v-on:command="taskCommand(task, $event)"
                  >
                    <span class="el-dropdown-link">
                      <font-awesome-icon icon="ellipsis-h" />
                    </span>
                    <template #dropdown>
                      <el-dropdown-menu>
                        <el-dropdown-item command="edit">
                          <font-awesome-icon icon="pen" />
                        </el-dropdown-item>
                        <el-dropdown-item command="delete">
                          <font-awesome-icon icon="trash" />
                        </el-dropdown-item>
                      </el-dropdown-menu>
                    </template>
                  </el-dropdown>
                </p>
                <TaskInfo
                  :title="task.name"
                  :description="task.description"
                  :type="TaskType[task.taskType]"
                  :modules="task.modules.map((module) => module.name)"
                ></TaskInfo>
              </el-card>
            </TutorialStep>
            <TutorialStep
              type="topicDetails"
              step="addTask"
              :order="2"
              :displayAllDuplicates="true"
            >
              <AddItem
                :text="$t('moderator.view.topicDetails.addTask')"
                :isColumn="true"
                @addNew="displayTaskSettings(taskType)"
              />
            </TutorialStep>
          </el-space>
        </el-tab-pane>
      </el-tabs>
      <el-divider></el-divider>
      <ModuleContentComponent v-if="activeTask" :task-id="activeTaskId" />
    </template>
  </ModeratorNavigationLayout>
  <TaskSettings
    v-model:show-modal="showTaskSettings"
    :task-type="taskSettingsType"
    :topic-id="topicId"
    :task-id="taskSettingsId"
    @taskUpdated="reloadTasks"
    @showTimerSettings="displayTimerSettings"
  />
  <TopicSettings
    v-model:show-modal="showTopicSettings"
    :session-id="sessionId"
    :topic-id="topicId"
  />
  <TimerSettings
    v-if="showTimerSettings"
    v-model:showModal="showTimerSettings"
    :entity="timerTask"
  />
  <FacilitatorSettings
    v-if="isModerator"
    v-model:showModal="showRoles"
    :sessionId="sessionId"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import TaskSettings from '@/components/moderator/organisms/settings/TaskSettings.vue';
import ModeratorNavigationLayout from '@/components/moderator/organisms/layout/ModeratorNavigationLayout.vue';
import * as topicService from '@/services/topic-service';
import * as taskService from '@/services/task-service';
import * as sessionService from '@/services/session-service';
import { Topic } from '@/types/api/Topic';
import { Task } from '@/types/api/Task';
import { Session } from '@/types/api/Session';
import TopicSettings from '@/components/moderator/organisms/settings/TopicSettings.vue';
import CollapseTitle from '@/components/moderator/atoms/CollapseTitle.vue';
import TaskTimeline from '@/components/moderator/organisms/Timeline/TaskTimeline.vue';
import Sidebar from '@/components/moderator/organisms/Sidebar.vue';
import TaskType from '@/types/enum/TaskType';
import TaskTypeColor from '@/types/TaskTypeColor';
import TaskCard from '@/components/moderator/organisms/cards/TaskCard.vue';
import TaskInfo from '@/components/shared/molecules/TaskInfo.vue';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import UserType from '@/types/enum/UserType';
import * as sessionRoleService from '@/services/session-role-service';
import {
  getAsyncModule,
  getEmptyComponent,
  getModuleConfig,
  getModulesForTaskType,
} from '@/modules';
import ModuleComponentType from '@/modules/ModuleComponentType';
import { ModuleType } from '@/types/enum/ModuleType';
import SessionCode from '@/components/moderator/molecules/SessionCode.vue';
import { ComponentLoadingState } from '@/types/enum/ComponentLoadingState';
import TimerSettings from '@/components/moderator/organisms/settings/TimerSettings.vue';
import TutorialStep from '@/components/shared/atoms/TutorialStep.vue';
import FacilitatorSettings from '@/components/moderator/organisms/settings/FacilitatorSettings.vue';
import { reactivateTutorial } from '@/services/auth-service';

@Options({
  components: {
    TutorialStep,
    TimerSettings,
    AddItem,
    TaskInfo,
    TaskCard,
    Sidebar,
    TaskSettings,
    TopicSettings,
    ModeratorNavigationLayout,
    CollapseTitle,
    TaskTimeline,
    SessionCode,
    FacilitatorSettings,
    ModuleContentComponent: getEmptyComponent(),
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorTopicDetails extends Vue {
  @Prop() readonly sessionId!: string;
  @Prop() readonly topicId!: string;
  @Prop() readonly taskId!: string;

  topic: Topic | null = null;
  session: Session | null = null;
  sessionRole = '';
  showTaskSettings = false;
  showTopicSettings = false;
  tasks: Task[] = [];
  activeTab = TaskType.BRAINSTORMING;
  previousActiveTask: Task | null = null;
  activeTask: Task | null = null;
  componentLoadIndex = 0;
  componentLoadingState: ComponentLoadingState = ComponentLoadingState.NONE;
  showRoles = false;
  readonly intervalTime = 3000;
  interval!: any;

  TaskType = TaskType;
  TaskTypeColor = TaskTypeColor;

  reactivateTutorial(): void {
    reactivateTutorial('topicDetails', this.eventBus);
    reactivateTutorial('taskTimeline', this.eventBus);
  }

  get isModerator(): boolean {
    return this.sessionRole === UserType.MODERATOR;
  }

  get activeTaskIndex(): number {
    if (this.tasks && this.activeTask) {
      const activeId = this.activeTask.id;
      return this.tasks.findIndex((t) => t.id == activeId);
    }
    const index = this.tasks.findIndex(
      (t) => TaskType[t.taskType] === TaskType.BRAINSTORMING
    );
    if (index) return index;
    return 0;
  }

  get activeTaskId(): string {
    if (this.activeTask) {
      return this.activeTask.id;
    }
    const brainstormingTask = this.tasks.find(
      (t) => TaskType[t.taskType] === TaskType.BRAINSTORMING
    );
    if (brainstormingTask) return brainstormingTask.id;
    if (this.tasks.length > 0) return this.tasks[0].id;
    return '';
  }

  moduleIcon: { [name: string]: { [name: string]: string } } = {};
  mounted(): void {
    this.loadTaskTypes();
    this.startInterval();
  }

  loadTaskTypes(): void {
    Object.keys(TaskType).forEach((taskTypeName) => {
      const taskType = taskTypeName as unknown as keyof typeof TaskType;
      if (!(taskTypeName in this.moduleIcon))
        this.moduleIcon[taskTypeName] = {};
      getModulesForTaskType(taskType, ModuleType.MAIN).then((modules) => {
        modules.forEach((moduleName) => {
          this.moduleIcon[taskTypeName][moduleName] = 'circle';
          getModuleConfig('icon', TaskType[taskType], moduleName).then(
            (icon) => (this.moduleIcon[taskType][moduleName] = icon)
          );
        });
      });
    });
  }

  timerTask: Task | null = null;
  get showTimerSettings(): boolean {
    return this.timerTask !== null;
  }

  set showTimerSettings(show: boolean) {
    if (!show) this.timerTask = null;
  }

  displayTimerSettings(task: Task | null): void {
    setTimeout(() => (this.timerTask = task ? task : null), 500);
  }

  getModuleIcon(task: Task): string {
    if (task && task.modules.length > 0)
      return this.moduleIcon[task.taskType][task.modules[0].name];
    return 'circle';
  }

  @Watch('sessionId', { immediate: true })
  onSessionIdChanged(): void {
    sessionRoleService.getOwn(this.sessionId).then((role) => {
      this.sessionRole = role.role;
    });
    sessionService.getById(this.sessionId).then((session) => {
      this.session = session;
    });
  }

  @Watch('topicId', { immediate: true })
  onTopicIdChanged(): void {
    topicService.getTopicById(this.topicId).then((topic) => {
      this.topic = topic;
    });
    this.getTasks().then(() => {
      if (this.taskId) {
        const queryTask = this.tasks.find((task) => task.id === this.taskId);
        if (queryTask) this.changeTask(queryTask);
      }
    });
  }

  async getTasks(): Promise<void> {
    await taskService.getTaskList(this.topicId).then((tasks) => {
      this.tasks = tasks;
      if (
        !this.activeTask &&
        !this.tasks.find(
          (task) => TaskType[task.taskType] == TaskType.BRAINSTORMING
        )
      ) {
        this.activeTask = this.tasks[0];
      } else {
        const activeTask = this.tasks.find(
          (task) => task.id == this.activeTaskId
        );
        if (activeTask) this.activeTask = activeTask;
      }
    });
    if (this.tasks.length > this.activeTaskIndex)
      await this.changeTask(this.tasks[this.activeTaskIndex]);
  }

  reloadTasks(taskId: string): void {
    const newTaskIsAdded = !this.tasks.find((task) => task.id == taskId);
    this.getTasks().then(() => {
      const activeTask = this.tasks.find((task) => task.id == taskId);
      if (activeTask) {
        if (newTaskIsAdded) {
          this.changeTask(activeTask);
        }
        this.activeTab = TaskType[activeTask.taskType];
      }
    });
  }

  pauseReload = false;
  reloadData(): void {
    if (!this.pauseReload) {
      topicService.getTopicById(this.topicId).then((topic) => {
        this.topic = topic;
      });
      this.getTasks();
    }
  }

  @Watch('activeTab', { immediate: true })
  onActiveTabChanged(): void {
    const activeTabTask = this.tasks.find(
      (task) => TaskType[task.taskType] === this.activeTab
    );
    if (
      activeTabTask &&
      (!this.activeTask || this.activeTask.taskType !== activeTabTask.taskType)
    )
      this.changeTask(activeTabTask);
  }

  taskTypeAvailable(taskType: TaskType): boolean {
    if (taskType == TaskType.BRAINSTORMING || taskType == TaskType.INFORMATION)
      return true;
    return !!this.tasks.find(
      (task) => TaskType[task.taskType] === TaskType.BRAINSTORMING
    );
  }

  hasNewActiveTask(newTask: Task): boolean {
    return (
      !this.previousActiveTask ||
      !newTask ||
      this.previousActiveTask.id !== newTask.id
    );
  }

  async changeTask(newTask: Task): Promise<void> {
    if (this.$options.components && newTask && this.hasNewActiveTask(newTask)) {
      await getAsyncModule(
        ModuleComponentType.MODERATOR_CONTENT,
        TaskType[newTask.taskType],
        newTask.modules[0].name
      ).then((component) => {
        if (this.$options.components) {
          this.componentLoadingState = ComponentLoadingState.SELECTED;
          this.$options.components['ModuleContentComponent'] = component;
          this.componentLoadIndex++;
        }
        this.activeTask = newTask;
        this.previousActiveTask = this.activeTask;
        this.activeTab = TaskType[newTask.taskType];
        this.$router.replace({ params: { taskId: newTask.id } });
      });
    }
  }

  taskSettingsType = '';
  taskSettingsId: string | null = null;
  displayTaskSettings(taskType: string): void {
    this.taskSettingsType = taskType.toUpperCase();
    this.taskSettingsId = null;
    this.showTaskSettings = true;
  }

  isActive(task: Task): boolean {
    if (this.activeTask && task) {
      return this.activeTask.id == task.id;
    }
    return false;
  }

  editTopic(): void {
    this.showTopicSettings = true;
  }

  deleteTopic(): void {
    topicService.deleteTopic(this.topicId).then((deleted) => {
      if (deleted) this.$router.go(-1);
    });
  }

  taskCommand(task: Task, command: string): void {
    const activeTask = this.activeTask;
    switch (command) {
      case 'edit':
        this.taskSettingsType = task.taskType;
        this.taskSettingsId = task.id;
        this.showTaskSettings = true;
        break;
      case 'delete':
        this.pauseReload = true;
        if (this.activeTask?.id == task.id) this.activeTask = null;
        taskService.deleteTask(task.id).then((result) => {
          if (result) {
            this.getTasks().then(() => {
              if (activeTask) {
                this.activeTab = TaskType[activeTask.taskType];
              }
            });
          } else if (activeTask?.id == task.id) {
            this.activeTask = activeTask;
          }
          this.pauseReload = false;
        });
        break;
    }
  }

  startInterval(): void {
    this.interval = setInterval(this.reloadData, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }

  download(): void {
    topicService.exportTopic(this.topicId, 'XLSX').then((result) => {
      const blob = this.convertBase64toBlob(
        result.base64,
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      );
      const blobURL = URL.createObjectURL(blob);
      window.open(blobURL, '_self');
    });
  }

  convertBase64toBlob(content: string, contentType: string): Blob {
    contentType = contentType || '';
    const sliceSize = 512;
    const byteCharacters = window.atob(content); //method which converts base64 to binary
    const byteArrays: Uint8Array[] = [];
    for (let offset = 0; offset < byteCharacters.length; offset += sliceSize) {
      const slice = byteCharacters.slice(offset, offset + sliceSize);
      const byteNumbers = new Array(slice.length);
      for (let i = 0; i < slice.length; i++) {
        byteNumbers[i] = slice.charCodeAt(i);
      }
      const byteArray = new Uint8Array(byteNumbers);
      byteArrays.push(byteArray);
    }
    return new Blob(byteArrays, {
      type: contentType,
    }); //statement which creates the blob
  }
}
</script>

<style lang="scss" scoped>
.is-disabled {
  .taskType {
    color: var(--el-text-color-placeholder);
  }
  img {
    -webkit-filter: grayscale(1); /* Webkit */
    filter: gray; /* IE6-9 */
    filter: grayscale(1); /* W3C */
  }
}
.taskType {
  color: var(--module-color);
}

.el-space::v-deep {
  .el-space {
    &__item {
      max-width: 18rem;
      height: 12rem;
    }
  }
}

.el-card {
  height: 100%;
}

.module-info::v-deep {
  .el-breadcrumb__item {
    float: left;
  }
}

.selected {
  border-width: 3px;
  border-color: var(--color-purple);
  //background-color: var(--color-yellow-light);
}

p {
  margin-bottom: 0.5rem;
}

.icon {
  color: var(--color-darkblue-light);
  margin-left: 0.5em;

  &:hover {
    color: white;
    opacity: 0.7;
  }
}

.label {
  display: inline;
  text-transform: uppercase;
  color: white;
}
</style>
