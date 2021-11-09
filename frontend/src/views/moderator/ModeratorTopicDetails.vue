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
        v-on:openSettings="editTopic"
        v-on:delete="deleteTopic"
      >
        <template #footerContent>
          <TaskTimeline
            v-if="tasks"
            direction="vertical"
            :topic-id="topicId"
            :session-id="sessionId"
            :active="activeTaskIndex"
            v-on:changeActiveElement="changeTask"
          ></TaskTimeline>
        </template>
      </Sidebar>
    </template>
    <template v-slot:content>
      <!--<TaskTimeline
        v-if="tasks"
        :topic-id="topicId"
        :session-id="sessionId"
        :active="activeTaskIndex"
        v-on:changeActiveElement="changeTask"
      ></TaskTimeline>
      <el-divider></el-divider>-->
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
            <span :style="{ color: TaskTypeColor[taskType] }">
              {{ $t(`enum.taskType.${taskType}`) }}
            </span>
          </template>
          <el-space wrap>
            <el-card
              v-for="task in tasks.filter(
                (task) => TaskType[task.taskType] === taskType
              )"
              :key="task.id"
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
            <AddItem
              :text="$t('moderator.view.sessionDetails.addTask')"
              :isColumn="true"
              @addNew="displayTaskSettings(taskType)"
            />
          </el-space>
        </el-tab-pane>
      </el-tabs>
      <el-divider></el-divider>
      <ModuleContentComponent v-if="activeTask" :task-id="activeTask.id" />
      <TaskSettings
        v-model:show-modal="showTaskSettings"
        :task-type="taskSettingsType"
        :topic-id="topicId"
        :task-id="taskSettingsId"
        @taskUpdated="getTasks"
      />
      <TopicSettings
        v-model:show-modal="showTopicSettings"
        :session-id="sessionId"
        :topic-id="topicId"
      />
    </template>
  </ModeratorNavigationLayout>
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
import TaskTimeline from '@/components/moderator/organisms/TaskTimeline.vue';
import Sidebar from '@/components/moderator/organisms/Sidebar.vue';
import TaskType from '@/types/enum/TaskType';
import TaskTypeColor from '@/types/TaskTypeColor';
import TaskCard from '@/components/moderator/organisms/cards/TaskCard.vue';
import TaskInfo from '@/components/shared/molecules/TaskInfo.vue';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import {
  getAsyncModule,
  getEmptyComponent,
  getModuleConfig,
  getModulesForTaskType,
} from '@/modules';
import ModuleComponentType from '@/modules/ModuleComponentType';
import { ModuleType } from '@/types/enum/ModuleType';

@Options({
  components: {
    AddItem,
    TaskInfo,
    TaskCard,
    Sidebar,
    TaskSettings,
    TopicSettings,
    ModeratorNavigationLayout,
    CollapseTitle,
    TaskTimeline,
    ModuleContentComponent: getEmptyComponent(),
  },
})
export default class ModeratorTopicDetails extends Vue {
  @Prop() readonly sessionId!: string;
  @Prop() readonly topicId!: string;

  topic: Topic | null = null;
  session: Session | null = null;
  showTaskSettings = false;
  showTopicSettings = false;
  tasks: Task[] = [];
  activeTab = TaskType.BRAINSTORMING;
  activeTask: Task | null = null;
  componentLoadIndex = 0;

  TaskType = TaskType;
  TaskTypeColor = TaskTypeColor;

  get activeTaskIndex(): number {
    if (this.tasks && this.activeTask) {
      const activeId = this.activeTask.id;
      return this.tasks.findIndex((t) => t.id == activeId);
    }
    return 0;
  }

  moduleIcon: { [name: string]: { [name: string]: string } } = {};
  mounted(): void {
    Object.keys(TaskType).forEach((taskTypeName) => {
      const taskType = taskTypeName as unknown as keyof typeof TaskType;
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

  getModuleIcon(task: Task): string {
    return this.moduleIcon[task.taskType][task.modules[0].name];
  }

  @Watch('sessionId', { immediate: true })
  onSessionIdChanged(): void {
    sessionService.getById(this.sessionId).then((session) => {
      this.session = session;
    });
  }

  @Watch('topicId', { immediate: true })
  onTopicIdChanged(): void {
    topicService.getTopicById(this.topicId).then((topic) => {
      this.topic = topic;
    });
    this.getTasks();
  }

  getTasks(): void {
    taskService.getTaskList(this.topicId).then((tasks) => {
      this.tasks = tasks;
      if (this.tasks.length > this.activeTaskIndex)
        this.changeTask(this.tasks[this.activeTaskIndex]);
    });
  }

  /*@Watch('activeTask', { immediate: true })
  onActiveTaskChanged(): void {
    if (this.$options.components && this.activeTask) {
      getAsyncModule(
        ModuleComponentType.MODERATOR_CONTENT,
        TaskType[this.activeTask.taskType]
      ).then((component) => {
        if (this.$options.components)
          this.$options.components['ModuleContentComponent'] = component;
        this.componentLoadIndex++;
      });
    }
  }*/

  taskTypeAvailable(taskType: TaskType): boolean {
    if (taskType == TaskType.BRAINSTORMING || taskType == TaskType.INFORMATION)
      return true;
    return !!this.tasks.find(
      (task) => TaskType[task.taskType] === TaskType.BRAINSTORMING
    );
  }

  changeTask(newTask: Task): void {
    if (this.$options.components && newTask) {
      getAsyncModule(
        ModuleComponentType.MODERATOR_CONTENT,
        TaskType[newTask.taskType]
      ).then((component) => {
        if (this.$options.components)
          this.$options.components['ModuleContentComponent'] = component;
        this.componentLoadIndex++;
        this.activeTask = newTask;
        this.activeTab = TaskType[newTask.taskType];
      });
    }
  }

  taskSettingsType = '';
  taskSettingsId: string | null = null;
  displayTaskSettings(taskType: string): void {
    this.taskSettingsType = taskType.toUpperCase();
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
    switch (command) {
      case 'edit':
        this.taskSettingsType = task.taskType;
        this.taskSettingsId = task.id;
        this.showTaskSettings = true;
        break;
      case 'delete':
        taskService.deleteTask(task.id).then((result) => {
          if (result) {
            this.getTasks();
          }
        });
        break;
    }
  }
}
</script>

<style lang="scss" scoped>
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
</style>
