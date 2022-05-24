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
        :session="session"
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
              <font-awesome-icon
                class="awesome-icon"
                icon="user-group"
              ></font-awesome-icon>
            </span>
          </TutorialStep>
          <span v-on:click="download">
            <font-awesome-icon
              class="awesome-icon"
              icon="download"
            ></font-awesome-icon>
          </span>
          <el-dropdown>
            <span class="el-dropdown-link">
              <font-awesome-icon class="awesome-icon" icon="info-circle" />
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
          <el-collapse accordion v-model="activeTab">
            <el-collapse-item
              v-for="taskCategory in Object.keys(TaskCategory)"
              :key="taskCategory"
              :name="taskCategory"
              :style="{
                '--module-color': taskTypeAvailable(taskCategory)
                  ? TaskCategory[taskCategory].color
                  : inactiveColor,
              }"
              :class="{ 'is-disabled': !taskTypeAvailable(taskCategory) }"
              :disabled="!taskTypeAvailable(taskCategory)"
            >
              <template #title>
                <TutorialStep
                  type="topicDetails"
                  step="taskType"
                  :order="1"
                  :width="450"
                  placement="bottom"
                  :disableTutorial="
                    !TaskCategory[taskCategory].taskTypes.includes(
                      TaskType.BRAINSTORMING
                    )
                  "
                >
                  <span
                    class="taskType media"
                    :style="{
                      '--module-color': TaskCategory[taskCategory].color,
                    }"
                  >
                    <span class="media-left">
                      <font-awesome-icon
                        :icon="TaskCategory[taskCategory].icon"
                        :style="{
                          '--module-color': TaskCategory[taskCategory].color,
                        }"
                      />
                    </span>
                    <span class="taskCategory media-content">
                      {{ $t(`enum.taskCategory.${taskCategory}`) }}
                    </span>
                  </span>
                </TutorialStep>
              </template>
              <el-space wrap fill :key="reloadTaskIndex">
                <TutorialStep
                  type="topicDetails"
                  step="addTask"
                  :order="2"
                  :displayAllDuplicates="true"
                >
                  <AddItem
                    :text="$t('moderator.view.topicDetails.addTask')"
                    :isColumn="true"
                    @addNew="displayTaskSettings(taskCategory)"
                  />
                </TutorialStep>
                <TutorialStep
                  v-for="(task, index) in tasks.filter((task) =>
                    TaskCategory[taskCategory].taskTypes.includes(
                      TaskType[task.taskType]
                    )
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
                    :style="{
                      '--module-color': TaskCategory[taskCategory].color,
                    }"
                    class="link"
                    :class="{ selected: isActive(task) }"
                  >
                    <TaskInfo
                      :taskId="task.id"
                      :modules="task.modules.map((module) => module.name)"
                    >
                      <template #moduleInfoLeft>
                        <span class="person">
                          <font-awesome-icon icon="users" />
                          {{ task.participantCount }}
                        </span>
                        <span class="separator"> | </span>
                      </template>
                      <template #moduleInfoRight>
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
                      </template>
                    </TaskInfo>
                  </el-card>
                </TutorialStep>
              </el-space>
            </el-collapse-item>
          </el-collapse>
        </template>
      </Sidebar>
    </template>
    <template v-slot:content>
      <TaskTimeline
        v-if="tasks"
        :topicId="topicId"
        :sessionId="sessionId"
        :activeTaskId="activeTaskId"
        v-on:changeActiveElement="changeTask"
      ></TaskTimeline>
      <el-divider></el-divider>
      <ModuleContentComponent
        v-if="activeTask"
        :task-id="activeTaskId"
        ref="moduleContent"
      />
    </template>
  </ModeratorNavigationLayout>
  <TaskSettings
    v-model:show-modal="showTaskSettings"
    :task-types="taskSettingsTypes"
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
import { Module } from '@/types/api/Module';
import { Session } from '@/types/api/Session';
import TopicSettings from '@/components/moderator/organisms/settings/TopicSettings.vue';
import CollapseTitle from '@/components/moderator/atoms/CollapseTitle.vue';
import TaskTimeline from '@/components/moderator/organisms/Timeline/TaskTimeline.vue';
import Sidebar from '@/components/moderator/organisms/layout/Sidebar.vue';
import TaskType from '@/types/enum/TaskType';
import TaskCategory, {
  getCategoryOfType,
  TaskCategoryOption,
} from '@/types/enum/TaskCategory';
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
import { IModeratorContent } from '@/types/ui/IModeratorContent';
import { color } from 'chart.js/helpers';

@Options({
  components: {
    TutorialStep,
    TimerSettings,
    AddItem,
    TaskInfo,
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
  activeTab = TaskCategoryOption.BRAINSTORMING;
  previousActiveTab = TaskCategoryOption.BRAINSTORMING;
  previousActiveTask: Task | null = null;
  activeTask: Task | null = null;
  componentLoadIndex = 0;
  reloadTaskIndex = 0;
  componentLoadingState: ComponentLoadingState = ComponentLoadingState.NONE;
  showRoles = false;
  readonly intervalTime = 3000;
  interval!: any;

  TaskType = TaskType;
  TaskCategory = TaskCategory;

  inactiveColor = color([192, 196, 204]);

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

  moduleIcon: { [name: string]: { [name: string]: string[] } } = {};
  mounted(): void {
    this.loadTaskTypes();
    this.startInterval();
  }

  loadTaskTypes(): void {
    Object.keys(TaskType).forEach((taskTypeName) => {
      const taskType = taskTypeName as keyof typeof TaskType;
      if (!(taskTypeName in this.moduleIcon))
        this.moduleIcon[taskTypeName] = {};
      getModulesForTaskType([taskType], ModuleType.MAIN).then((modules) => {
        modules.forEach((module) => {
          this.moduleIcon[taskTypeName][module.moduleName] = ['fas', 'circle'];
          getModuleConfig('icon', TaskType[taskType], module.moduleName).then(
            (icon) => {
              getModuleConfig(
                'iconPrefix',
                TaskType[taskType],
                module.moduleName
              ).then(
                (iconPrefix) =>
                  (this.moduleIcon[taskType][module.moduleName] = [
                    iconPrefix,
                    icon,
                  ])
              );
            }
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

  getModuleIcon(task: Task): string[] {
    if (task && task.modules.length > 0)
      return this.moduleIcon[task.taskType][task.modules[0].name];
    return ['fas', 'circle'];
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

  get topicTitle(): string {
    if (this.topic)
      return `${this.topic.order + 1}. ${(this as any).$t(
        'moderator.view.topicDetails.topic'
      )}`;
    return '';
  }

  async getTasks(): Promise<void> {
    let activeTask: Task | undefined = undefined;
    await taskService.getTaskList(this.topicId).then((tasks) => {
      this.tasks = tasks;
      if (
        !this.activeTask &&
        !this.tasks.find(
          (task) => TaskType[task.taskType] == TaskType.BRAINSTORMING
        )
      ) {
        activeTask = this.tasks[0];
      } else {
        activeTask = this.tasks.find((task) => task.id == this.activeTaskId);
      }
    });
    if (activeTask) await this.changeTask(activeTask);
    else this.activeTask = null;
    /*if (this.tasks.length > this.activeTaskIndex)
      await this.changeTask(this.tasks[this.activeTaskIndex]);*/
  }

  reloadTasks(taskId: string): void {
    const newTaskIsAdded = !this.tasks.find((task) => task.id == taskId);
    this.getTasks().then(() => {
      const activeTask = this.tasks.find((task) => task.id == taskId);
      if (activeTask) {
        if (newTaskIsAdded || !this.hasNewActiveTask(activeTask)) {
          this.changeTask(activeTask);
        }
        this.setActiveTab(activeTask.taskType);
      }
      this.reloadTaskIndex++;
    });
    if (this.activeTask && this.activeTask.id === taskId) {
      const moduleContent = this.$refs.moduleContent as IModeratorContent;
      if (moduleContent && 'reloadTaskSettings' in moduleContent)
        moduleContent.reloadTaskSettings();
    }
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
    if (this.activeTab == this.previousActiveTab) {
      const taskCategory = TaskCategory[this.activeTab];
      const activeTabTasks = this.tasks.filter((task) =>
        taskCategory.taskTypes.includes(TaskType[task.taskType])
      );
      if (
        activeTabTasks.length > 0 &&
        (!this.activeTask ||
          !activeTabTasks.find((task) => task.id == this.activeTaskId))
      )
        this.changeTask(activeTabTasks[0]);
    }
  }

  taskTypeAvailable(taskCategory: string): boolean {
    if (
      TaskCategory[taskCategory].taskTypes.includes(TaskType.BRAINSTORMING) ||
      TaskCategory[taskCategory].taskTypes.includes(TaskType.INFORMATION)
    )
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

  hasNewActiveTaskType(newTask: Task): boolean {
    return (
      !this.previousActiveTask ||
      !newTask ||
      this.previousActiveTask.taskType !== newTask.taskType
    );
  }

  hasNewActiveTaskModule(newTask: Task): boolean {
    const compareModules = (
      previousModules: Module[],
      newModules: Module[]
    ): boolean => {
      for (const newModule of newModules) {
        if (
          !previousModules.find(
            (previousModule) => newModule.name === previousModule.name
          )
        )
          return false;
      }
      return true;
    };

    return (
      !this.previousActiveTask ||
      !newTask ||
      this.previousActiveTask.modules.length !== newTask.modules.length ||
      !compareModules(this.previousActiveTask.modules, newTask.modules)
    );
  }

  async changeTask(newTask: Task): Promise<void> {
    if (
      this.$options.components &&
      newTask &&
      (this.hasNewActiveTask(newTask) ||
        this.hasNewActiveTaskType(newTask) ||
        this.hasNewActiveTaskModule(newTask))
    ) {
      if (
        this.hasNewActiveTaskType(newTask) ||
        this.hasNewActiveTaskModule(newTask)
      ) {
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
          this.setActiveTab(newTask.taskType);
          this.$router.replace({ params: { taskId: newTask.id } });
        });
      } else {
        this.activeTask = newTask;
        this.previousActiveTask = this.activeTask;
        await this.$router.replace({ params: { taskId: newTask.id } });
      }
    }
  }

  private setActiveTab(taskType: string): void {
    const taskCategory = getCategoryOfType(TaskType[taskType.toUpperCase()]);
    this.previousActiveTab = this.activeTab;
    if (taskCategory)
      this.activeTab = TaskCategoryOption[taskCategory.toUpperCase()];
  }

  taskSettingsCategory: string | undefined = '';
  taskSettingsId: string | null = null;
  displayTaskSettings(taskCategory: string): void {
    this.taskSettingsCategory = taskCategory;
    this.taskSettingsId = null;
    this.showTaskSettings = true;
  }

  get taskSettingsTypes(): string[] {
    if (this.taskSettingsCategory && TaskCategory[this.taskSettingsCategory])
      return TaskCategory[this.taskSettingsCategory].taskTypes;
    return [];
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
        this.taskSettingsCategory = getCategoryOfType(TaskType[task.taskType]);
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
                this.setActiveTab(activeTask.taskType);
              }
            });
          } else if (activeTask?.id == task.id) {
            this.changeTask(activeTask);
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
    this.topic = null;
    this.tasks = [];
    this.activeTask = null;
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
    //color: var(--el-text-color-placeholder);
    color: var(--color-darkblue);
  }
  img {
    -webkit-filter: grayscale(1); /* Webkit */
    filter: gray; /* IE6-9 */
    filter: grayscale(1); /* W3C */
  }
}

.taskType {
  color: white;
  font-size: var(--font-size-default);

  .media-left {
    width: 1.5rem;
    text-align: center;
    margin-right: 0;
  }
}

.el-collapse::v-deep {
  margin: 1rem 0;
  border-top: unset;
  border-bottom: unset;

  .el-collapse-item {
    border-radius: var(--border-radius-xs);
    margin-top: 0.5rem;
    background-color: var(--color-background-gray);
  }

  .el-collapse-item__header {
    --header-radius: calc(var(--border-radius-xs) - 2px);
    padding: 0.5rem 1rem;
    background-color: var(--module-color);
    border-radius: var(--header-radius);
    color: white;
    height: unset;
    line-height: unset;
    border-bottom: unset;

    &.is-active {
      border-radius: var(--header-radius) var(--header-radius) 0 0;
    }
  }

  .el-collapse-item__wrap {
    border-bottom: unset;
  }

  .el-collapse-item__content {
    padding: 0.5rem;
  }

  .taskType {
    width: 100%;
  }

  .taskCategory {
    padding-left: 0.5rem;
  }
}

.el-dropdown::v-deep {
  color: var(--module-color);
}

.el-space::v-deep {
  width: 100%;

  .el-space {
    &__item {
      width: 100%;
    }
  }
}

.el-card::v-deep {
  margin-bottom: unset;
  .el-card__body {
    padding: 0.5rem 0.8rem;
  }

  .person {
    font-weight: var(--font-weight-semibold);
    font-size: 0.7rem;
    color: var(--module-color);
    border-radius: var(--border-radius-xs);
    padding: 0.3rem;
  }

  .separator {
    color: var(--module-color);
    font-weight: var(--font-weight-semibold);
  }
}

.selected {
  .person {
    color: white;
  }

  .separator {
    color: white;
  }

  .el-dropdown::v-deep {
    color: white;
  }
}

.add::v-deep {
  &.add--column,
  &.add--column .el-card__body {
    min-height: unset;
    padding: 0.1rem;
    flex-direction: row;
  }
}

.module-info::v-deep {
  .media-right {
    margin-left: 0.2rem;
  }
}

.selected {
  border-width: 3px;
  border-color: var(--module-color);
  background-color: var(--module-color);

  .module-info::v-deep {
    .module-info__type {
      color: white;
    }
  }
}

p {
  margin-bottom: 0.5rem;
}

.awesome-icon {
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

.module-count {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: var(--font-size-small);
  text-transform: uppercase;
  letter-spacing: 2px;
  margin-top: 1rem;
  line-height: 1;
  flex-wrap: wrap;
  gap: 0.6rem;

  &__item {
    white-space: nowrap;
  }

  &__count {
    background-color: var(--color-mint);
    color: white;
    padding: 0.1rem 0.7rem;
    letter-spacing: 0;
    font-size: var(--font-size-default);
    border-radius: 100px;
    margin-right: 0.2rem;
  }
}
</style>
