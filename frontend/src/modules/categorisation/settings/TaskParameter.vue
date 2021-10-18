<template>
  <el-form-item
    :label="$t('module.categorisation.settings.taskParameter.brainstorming')"
    :prop="`${rulePropPath}.brainstormingTaskId`"
    :rules="[defaultFormRules.ruleSelection]"
  >
    <el-select
      v-model="modelValue.brainstormingTaskId"
      class="select--fullwidth"
    >
      <el-option
        v-for="task in brainstormingTasks"
        :key="task.id"
        :value="task.id"
        :label="task.name"
      >
      </el-option>
    </el-select>
  </el-form-item>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
import TaskType from '@/types/enum/TaskType';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';

@Options({
  components: {},
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TaskParameter extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  @Prop() readonly rulePropPath!: string;

  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop({ default: {} }) modelValue!: any;
  task: Task | null = null;
  errors: string[] = [];
  brainstormingTasks: Task[] = [];

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue && !this.modelValue.brainstormingTaskId) {
      this.modelValue.brainstormingTaskId = null;
    }
  }

  async loadBrainstormingTasks(): Promise<void> {
    const topicId = this.task ? this.task.topicId : this.topicId;

    if (topicId) {
      await taskService.getTaskList(topicId).then((tasks) => {
        this.brainstormingTasks = tasks.filter(
          (task) => task.taskType == TaskType.BRAINSTORMING.toUpperCase()
        );
      });
    }
  }

  @Watch('taskId', { immediate: true })
  async onTaskIdChanged(): Promise<void> {
    await this.getTask();
  }

  @Watch('topicId', { immediate: true })
  async onTopicIdChanged(): Promise<void> {
    await this.loadBrainstormingTasks();
  }

  async getTask(): Promise<void> {
    if (this.taskId) {
      await taskService.getTaskById(this.taskId).then((task) => {
        this.task = task;
      });
      await this.loadBrainstormingTasks();
    }
  }
}
</script>
