<template>
  <el-form-item
    :label="$t('module.voting.settings.taskParameter.selection')"
    :prop="`parameter.dependencyTaskId`"
    :rules="[defaultFormRules.ruleSelection]"
    v-if="dependencyTasks.length !== 1"
  >
    <el-select
      v-model="modelValue.parameter.dependencyTaskId"
      class="select--fullwidth"
    >
      <el-option
        v-for="task in dependencyTasks"
        :key="task.id"
        :value="task.id"
        :label="`${$t(`enum.taskType.${TaskType[task.taskType]}`)}: ${
          task.name
        }`"
      >
      </el-option>
    </el-select>
  </el-form-item>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as taskService from '@/services/task-service';
import { Task, TaskSettingsData } from '@/types/api/Task';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import TaskType from '@/types/enum/TaskType';

@Options({
  components: {},
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TaskParameter extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop({ default: {} }) modelValue!: TaskSettingsData;
  task: Task | null = null;
  dependencyTasks: Task[] = [];

  TaskType = TaskType;

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (
      this.modelValue.parameter &&
      !this.modelValue.parameter.dependencyTaskId
    ) {
      this.modelValue.parameter.dependencyTaskId = null;
    }
  }

  async loadDependencyTasks(): Promise<void> {
    const topicId = this.task ? this.task.topicId : this.topicId;

    if (topicId) {
      await taskService.getTaskList(topicId).then((tasks) => {
        this.dependencyTasks = tasks.filter(
          (task) =>
            task.taskType == TaskType.BRAINSTORMING.toUpperCase() ||
            task.taskType == TaskType.SELECTION.toUpperCase()
        );
        if (this.dependencyTasks.length == 1) {
          this.modelValue.parameter.dependencyTaskId =
            this.dependencyTasks[0].id;
        }
      });
    }
  }

  @Watch('taskId', { immediate: true })
  async onTaskIdChanged(): Promise<void> {
    await this.getTask();
  }

  @Watch('topicId', { immediate: true })
  async onTopicIdChanged(): Promise<void> {
    await this.loadDependencyTasks();
  }

  async getTask(): Promise<void> {
    if (this.taskId) {
      await taskService.getTaskById(this.taskId).then((task) => {
        this.task = task;
      });
      await this.loadDependencyTasks();
    }
  }
}
</script>
