<template>
  <ModeratorNavigationLayout
    v-if="topic"
    :current-route-title="topic.title"
    :key="componentLoadIndex"
    content-class="customContentLayout"
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
          <span v-if="isModerator" v-on:click="showDependencies = true">
            <ToolTip
              :effect="'light'"
              :text="
                $t('moderator.organism.settings.topicDependencySettings.header')
              "
            >
              <font-awesome-icon
                class="awesome-icon"
                icon="table"
              ></font-awesome-icon>
            </ToolTip>
          </span>
          <span v-on:click="download">
            <ToolTip
              :effect="'light'"
              :text="$t('moderator.organism.settings.sidebarSettings.download')"
            >
              <font-awesome-icon
                v-if="!isDownloading"
                class="awesome-icon"
                icon="download"
              ></font-awesome-icon>
              <font-awesome-icon
                v-else
                class="awesome-icon fa-spin"
                icon="spinner"
              ></font-awesome-icon>
            </ToolTip>
          </span>
          <!--          <TutorialStep
            v-if="isModerator"
            type="sessionDetails"
            step="coModerator"
            :order="4"
          >-->
          <span v-if="isModerator" v-on:click="showRoles = true">
            <ToolTip
              :effect="'light'"
              :text="
                $t('moderator.organism.settings.facilitatorSettings.header')
              "
            >
              <font-awesome-icon
                class="awesome-icon"
                icon="user-group"
              ></font-awesome-icon>
            </ToolTip>
          </span>
          <!--          </TutorialStep>-->
          <!--          <TutorialStep
            v-if="isModerator"
            type="sessionDetails"
            step="participants"
            :order="10"
          >-->
          <span v-if="isModerator" v-on:click="showParticipants = true">
            <ToolTip
              :effect="'light'"
              :text="
                $t('moderator.organism.settings.participantSettings.header')
              "
            >
              <font-awesome-icon
                class="awesome-icon"
                icon="users"
              ></font-awesome-icon>
            </ToolTip>
          </span>
          <span v-on:click="showAnalytics = true" v-if="isModerator">
            <ToolTip
              :effect="'light'"
              :text="$t('moderator.organism.settings.analytics.header')"
            >
              <font-awesome-icon
                class="awesome-icon"
                icon="chart-column"
              ></font-awesome-icon>
            </ToolTip>
          </span>
          <!--          </TutorialStep>-->
          <!--          <el-dropdown>
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
          </el-dropdown>-->
        </template>
        <template #headerContent>
          <el-collapse accordion v-model="activeTab">
            <el-collapse-item
              v-for="taskCategory in Object.keys(
                TaskCategoriesWithUsableModules
              )"
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
                          <ToolTip
                            :text="
                              $t(
                                'moderator.view.topicDetails.participantCount'
                              ) +
                              ': ' +
                              task.participantCount
                            "
                          >
                            <font-awesome-icon icon="users" />
                          </ToolTip>
                          {{ task.participantCount }}
                        </span>
                        <span class="separator"> | </span>
                      </template>
                      <template #moduleInfoRight>
                        <el-dropdown
                          class="card__menu media-right"
                          v-on:command="taskCommand(task, $event)"
                          trigger="click"
                        >
                          <span
                            class="el-dropdown-link"
                            @click="stopPropagation"
                          >
                            <ToolTip
                              :text="
                                $t(
                                  'moderator.organism.settings.taskSettings.settings'
                                )
                              "
                            >
                              <font-awesome-icon icon="ellipsis-h" />
                            </ToolTip>
                          </span>
                          <template #dropdown>
                            <el-dropdown-menu>
                              <el-dropdown-item command="edit">
                                <ToolTip
                                  :placement="'right'"
                                  :text="
                                    $t(
                                      'moderator.organism.settings.taskSettings.edit'
                                    )
                                  "
                                >
                                  <font-awesome-icon icon="pen" />
                                </ToolTip>
                              </el-dropdown-item>
                              <el-dropdown-item command="delete">
                                <ToolTip
                                  :placement="'right'"
                                  :text="
                                    $t(
                                      'moderator.organism.settings.taskSettings.delete'
                                    )
                                  "
                                >
                                  <font-awesome-icon icon="trash" />
                                </ToolTip>
                              </el-dropdown-item>
                              <el-dropdown-item command="move">
                                <ToolTip
                                  :placement="'right'"
                                  :text="
                                    $t(
                                      'moderator.organism.settings.taskSettings.move'
                                    )
                                  "
                                >
                                  <font-awesome-icon icon="icons" />
                                </ToolTip>
                              </el-dropdown-item>
                              <el-dropdown-item command="clone">
                                <ToolTip
                                  :placement="'right'"
                                  :text="
                                    $t(
                                      'moderator.organism.settings.taskSettings.clone'
                                    )
                                  "
                                >
                                  <font-awesome-icon icon="clone" />
                                </ToolTip>
                              </el-dropdown-item>
                              <el-dropdown-item command="statistic">
                                <ToolTip
                                  :placement="'right'"
                                  :text="
                                    $t(
                                      'moderator.organism.settings.taskSettings.statistic'
                                    )
                                  "
                                >
                                  <font-awesome-icon icon="chart-column" />
                                </ToolTip>
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
  <TaskMoveSettings
    v-model:show-modal="showTaskMoveSettings"
    :task-id="taskSettingsId"
    @taskUpdated="reloadTasks"
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
  <el-dialog
    v-model="showStatistic"
    width="calc(var(--app-width) * 0.8)"
    :key="statisticComponentLoadIndex"
  >
    <template #header>
      {{ $t('moderator.view.topicDetails.statistic') }}
      "{{ dialogTask.name }}"
    </template>
    <TaskStatistic :task-id="dialogTask.id" />
    <ModuleStatisticComponent :task-id="dialogTask.id" />
  </el-dialog>
  <ParticipantSettings
    v-if="isModerator"
    v-model:show-modal="showParticipants"
    :session-id="sessionId"
  />
  <TopicDependencySettings
    v-if="isModerator"
    v-model:show-modal="showDependencies"
    :session-id="sessionId"
    :topic-id="topicId"
  />
  <AnalyticsTopicView
    v-if="isModerator"
    v-model:show-modal="showAnalytics"
    :session-id="sessionId"
    class="analyticsTopicView"
    :style="{
      width: '95%',
      maxWidth: '100%',
      height: '90%',
      maxHeight: '90%',
      overflow: 'scroll',
      margin: '2.5%',
    }"
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
  TaskCategoryType,
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
import { IModeratorContent } from '@/types/ui/IModeratorContent';
import { color } from 'chart.js/helpers';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import { SessionRole } from '@/types/api/SessionRole';
import { reactivateTutorial } from '@/services/tutorial-service';
import { ElMessageBox } from 'element-plus';
import TaskStatistic from '@/components/moderator/organisms/statistics/TaskStatistic.vue';
import ParticipantSettings from '@/components/moderator/organisms/settings/ParticipantSettings.vue';
import TopicDependencySettings from '@/components/moderator/organisms/settings/TopicDependencySettings.vue';
import ToolTip from '@/components/shared/atoms/ToolTip.vue';
import TaskMoveSettings from '@/components/moderator/organisms/settings/TaskMoveSettings.vue';
import AnalyticsTopicView from '@/components/moderator/organisms/analytics/AnalyticsTopicView.vue';

@Options({
  components: {
    AnalyticsTopicView,
    TaskMoveSettings,
    ToolTip,
    ParticipantSettings,
    TaskStatistic,
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
    TopicDependencySettings,
    ModuleContentComponent: getEmptyComponent(),
    ModuleStatisticComponent: getEmptyComponent(),
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
  showTaskMoveSettings = false;
  showTopicSettings = false;
  showAnalytics = false;
  tasks: Task[] = [];
  activeTab = TaskCategoryOption.BRAINSTORMING;
  previousActiveTab = TaskCategoryOption.BRAINSTORMING;
  previousActiveTask: Task | null = null;
  activeTask: Task | null = null;
  componentLoadIndex = 0;
  reloadTaskIndex = 0;
  componentLoadingState: ComponentLoadingState = ComponentLoadingState.NONE;
  showRoles = false;
  showStatistic = false;
  showParticipants = false;
  showDependencies = false;
  statisticComponentLoadIndex = 0;

  TaskType = TaskType;
  TaskCategory = TaskCategory;

  inactiveColor = color([192, 196, 204]);

  reactivateTutorial(): void {
    reactivateTutorial('topicDetails', this.eventBus);
    reactivateTutorial('taskTimeline', this.eventBus);
  }

  get TaskCategoriesWithUsableModules(): { [name: string]: TaskCategoryType } {
    const taskCategories: { [name: string]: TaskCategoryType } = {};
    for (const category of Object.keys(TaskCategory)) {
      let moduleCount = 0;
      for (const taskType of TaskCategory[category].taskTypes) {
        if (this.moduleIcon[taskType.toUpperCase()]) {
          moduleCount += Object.keys(
            this.moduleIcon[taskType.toUpperCase()]
          ).length;
        }
      }
      if (moduleCount > 0) {
        taskCategories[category] = TaskCategory[category];
      }
    }
    return taskCategories;
  }

  stopPropagation(event: Event) {
    event.stopPropagation();
  }

  get isModerator(): boolean {
    return (
      this.sessionRole === UserType.MODERATOR ||
      this.sessionRole === UserType.OWNER
    );
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

  @Watch('sessionId', { immediate: true })
  onSessionIdChanged(): void {
    cashService.deregisterAllGet(this.updateSession);
    cashService.deregisterAllGet(this.updateRole);
    sessionService.registerGetById(
      this.sessionId,
      this.updateSession,
      EndpointAuthorisationType.MODERATOR,
      60 * 60
    );
    sessionRoleService.registerGetOwn(
      this.sessionId,
      this.updateRole,
      EndpointAuthorisationType.MODERATOR,
      60 * 60
    );
  }

  taskCash!: cashService.SimplifiedCashEntry<Task[]>;
  @Watch('topicId', { immediate: true })
  onTopicIdChanged(): void {
    cashService.deregisterAllGet(this.updateTopic);
    cashService.deregisterAllGet(this.updateTasks);
    topicService.registerGetTopicById(
      this.topicId,
      this.updateTopic,
      EndpointAuthorisationType.MODERATOR,
      60 * 60
    );
    this.taskCash = taskService.registerGetTaskList(
      this.topicId,
      this.updateTasks,
      EndpointAuthorisationType.MODERATOR,
      30
    );
  }

  moduleIcon: { [name: string]: { [name: string]: string[] } } = {};
  mounted(): void {
    this.loadTaskTypes();
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

  updateRole(role: SessionRole): void {
    this.sessionRole = role.role;
  }

  updateSession(session: Session): void {
    this.session = session;
  }

  updateTopic(topic: Topic): void {
    this.topic = topic;
  }

  //eslint-disable-next-line @typescript-eslint/no-unused-vars
  updateTasks(tasks: Task[], topicId: string): void {
    let activeTask: Task | undefined = undefined;
    this.tasks = tasks;
    if (!this.activeTask && this.taskId) {
      activeTask = this.tasks.find((task) => task.id === this.taskId);
    } else if (
      !this.activeTask &&
      !this.tasks.find(
        (task) => TaskType[task.taskType] == TaskType.BRAINSTORMING
      )
    ) {
      activeTask = this.tasks[0];
    } else {
      activeTask = this.tasks.find((task) => task.id == this.activeTaskId);
    }
    if (activeTask) this.changeTask(activeTask);
    else this.activeTask = null;
  }

  get topicTitle(): string {
    if (this.topic)
      return `${this.topic.order + 1}. ${(this as any).$t(
        'moderator.view.topicDetails.topic'
      )}`;
    return '';
  }

  reloadTasks(task: Task): void {
    const changeIndex = this.tasks.findIndex((t) => t.id == task.id);
    if (changeIndex === -1) {
      this.tasks.push(task);
      this.changeTask(task);
    } else this.tasks[changeIndex] = task;
    this.reloadTaskIndex++;
    if (this.activeTask && this.activeTask.id === task.id) {
      const moduleContent = this.$refs.moduleContent as IModeratorContent;
      if (moduleContent && 'reloadTaskSettings' in moduleContent)
        moduleContent.reloadTaskSettings();
    }
  }

  pauseReload = false;

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
      TaskCategory[taskCategory].taskTypes.includes(TaskType.INFORMATION) ||
      TaskCategory[taskCategory].taskTypes.includes(TaskType.PLAYING)
    )
      return true;
    return !!this.tasks.find(
      (task) =>
        TaskType[task.taskType] === TaskType.BRAINSTORMING ||
        TaskType[task.taskType] === TaskType.INFORMATION
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
    this.deregisterAll();
    setTimeout(() => {
      topicService.deleteTopic(this.topicId).then((deleted) => {
        if (deleted) {
          topicService.refreshGetTopicList(this.sessionId);
          this.$router.go(-1);
        } else {
          this.onSessionIdChanged();
          this.onTopicIdChanged();
        }
      });
    }, 100);
  }

  dialogTask: Task | null = null;
  taskCommand(task: Task, command: string): void {
    this.dialogTask = task;
    this.statisticComponentLoadIndex++;
    const activeTask = this.activeTask;
    switch (command) {
      case 'edit':
        this.taskSettingsCategory = getCategoryOfType(TaskType[task.taskType]);
        this.taskSettingsId = task.id;
        this.showTaskSettings = true;
        break;
      case 'move':
        this.taskSettingsId = task.id;
        this.showTaskMoveSettings = true;
        break;
      case 'delete':
        this.pauseReload = true;
        if (this.activeTask?.id == task.id) this.activeTask = null;
        taskService.deleteTask(task.id).then((deleted) => {
          if (deleted) {
            if (this.taskCash) this.taskCash.refreshData();
            const index = this.tasks.findIndex((t) => t.id === task.id);
            if (index > -1) {
              this.tasks.splice(index, 1);
            }
            if (activeTask) {
              this.setActiveTab(activeTask.taskType);
              if (!this.activeTask) {
                this.$router.replace({ params: { taskId: undefined } });
              }
            }
          } else if (activeTask?.id == task.id) {
            this.changeTask(activeTask);
          }
          this.pauseReload = false;
        });
        break;
      case 'clone':
        try {
          ElMessageBox.confirm(
            this.$t('moderator.view.topicDetails.clonePrompt'),
            this.$t('moderator.view.topicDetails.clone'),
            {
              boxType: 'confirm',
              confirmButtonText: this.$t('moderator.view.topicDetails.clone'),
            }
          ).then(async () => {
            const clonedTask = await taskService.clone(task.id);
            taskService.refreshGetTaskList(this.topicId);
            this.taskSettingsCategory = getCategoryOfType(
              TaskType[task.taskType]
            );
            this.taskSettingsId = clonedTask.id;
            this.showTaskSettings = true;
          });
        } catch {
          // do nothing if the MessageBox is declined
        }
        break;
      case 'statistic':
        getAsyncModule(
          ModuleComponentType.MODULE_STATISTIC,
          TaskType[task.taskType],
          task.modules[0].name
        ).then((component) => {
          if (this.$options.components) {
            this.$options.components['ModuleStatisticComponent'] = component;
            this.showStatistic = true;
            this.statisticComponentLoadIndex++;
          }
        });
        break;
    }
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTopic);
    cashService.deregisterAllGet(this.updateRole);
    cashService.deregisterAllGet(this.updateSession);
    cashService.deregisterAllGet(this.updateTasks);
  }

  unmounted(): void {
    this.deregisterAll();
    this.topic = null;
    this.tasks = [];
    this.activeTask = null;
  }

  isDownloading = false;
  download(): void {
    if (this.isDownloading) return;
    this.isDownloading = true;
    topicService.exportTopic(this.topicId, 'XLSX').then((result) => {
      const blob = this.convertBase64toBlob(
        result.base64,
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      );
      const blobURL = URL.createObjectURL(blob);
      window.open(blobURL, '_self');
      this.isDownloading = false;
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
    color: var(--color-dark-contrast-dark);
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

.el-collapse {
  margin: 1rem 0;
  border-top: unset;
  border-bottom: unset;
}

.el-collapse::v-deep(.el-collapse-item) {
  border-radius: var(--border-radius-xs);
  margin-top: 0.5rem;
  background-color: var(--color-background);
}

.el-collapse::v-deep(.el-collapse-item__header) {
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

.el-collapse::v-deep(.el-collapse-item__wrap) {
  border-bottom: unset;
}

.el-collapse::v-deep(.el-collapse-item__content) {
  padding: 0.5rem;
}

.el-collapse::v-deep(.taskType) {
  width: 100%;
}

.el-collapse::v-deep(.taskCategory) {
  padding-left: 0.5rem;
}

.el-dropdown {
  color: var(--module-color);
}

.el-space {
  width: 100%;
}

.el-space::v-deep(.el-space__item) {
  width: 100%;
}

.el-card {
  margin-bottom: unset;
}

.el-card::v-deep(.el-card__body) {
  padding: 0.5rem 0.8rem;
}

.el-card::v-deep(.person) {
  font-weight: var(--font-weight-semibold);
  font-size: 0.7rem;
  color: var(--module-color);
  border-radius: var(--border-radius-xs);
  padding: 0.3rem;
}

.el-card::v-deep(.separator) {
  color: var(--module-color);
  font-weight: var(--font-weight-semibold);
}

.selected {
  .person {
    color: white;
  }

  .separator {
    color: white;
  }

  .el-dropdown {
    color: white;
  }
}

.add--column.add,
.add--column.add::v-deep(.el-card__body) {
  min-height: unset;
  padding: 0.1rem;
  flex-direction: row;
}

.module-info::v-deep(.media-right) {
  margin-left: 0.2rem;
}

.selected {
  border-width: 3px;
  border-color: var(--module-color);
  background-color: var(--module-color);

  .module-info::v-deep(.module-info__type) {
    color: white;
  }
}

p {
  margin-bottom: 0.5rem;
}

.awesome-icon {
  color: var(--color-dark-contrast-light);
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
    background-color: var(--color-brainstorming);
    color: white;
    padding: 0.1rem 0.7rem;
    letter-spacing: 0;
    font-size: var(--font-size-default);
    border-radius: 100px;
    margin-right: 0.2rem;
  }
}

.main-layout::v-deep(.customContentLayout) {
  display: flex;
  flex-direction: column;
  align-items: stretch;
}

.el-switch {
  --el-switch-on-color: var(--color-dark-contrast-light);
}
</style>

<style lang="scss">
.analyticsTopicView {
  -ms-overflow-style: none; /* IE and Edge */
  scrollbar-width: none; /* Firefox */
  .el-dialog__body {
    max-height: none;
  }
}

.analyticsTopicView::-webkit-scrollbar {
  display: none;
}
</style>
