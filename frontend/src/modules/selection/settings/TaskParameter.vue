<template>
  <el-form-item
    :label="$t('module.selection.settings.taskParameter.brainstorming')"
    :prop="`parameter.brainstormingTaskId`"
    :rules="[defaultFormRules.ruleSelection]"
    v-if="brainstormingTasks.length !== 1"
  >
    <el-select
      v-model="modelValue.parameter.brainstormingTaskId"
      class="select--fullwidth"
    >
      <el-option
        v-for="task in brainstormingTasks"
        :key="task.id"
        :value="task.id"
        :label="task.name"
      ></el-option>
    </el-select>
  </el-form-item>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as taskService from '@/services/task-service';
import * as selectionService from '@/services/selection-service';
import { Task, TaskSettingsData } from '@/types/api/Task';
import { CustomParameter } from '@/types/ui/CustomParameter';
import TaskType from '@/types/enum/TaskType';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';

@Options({
  components: {},
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TaskParameter extends Vue implements CustomParameter {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop({ default: {} }) modelValue!: TaskSettingsData;
  task: Task | null = null;
  errors: string[] = [];
  brainstormingTasks: Task[] = [];

  @Watch('modelValue', { immediate: true, deep: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue.parameter) {
      if (!this.modelValue.parameter.brainstormingTaskId)
        this.modelValue.parameter.brainstormingTaskId = null;
    }
  }

  async loadBrainstormingTasks(): Promise<void> {
    const topicId = this.task ? this.task.topicId : this.topicId;

    if (topicId) {
      await taskService.getTaskList(topicId).then((tasks) => {
        this.brainstormingTasks = tasks.filter(
          (task) => task.taskType == TaskType.BRAINSTORMING.toUpperCase()
        );
        if (this.brainstormingTasks.length == 1) {
          this.modelValue.parameter.brainstormingTaskId =
            this.brainstormingTasks[0].id;
        }
      });
    }
  }

  @Watch('topicId', { immediate: true })
  async onTopicIdChanged(): Promise<void> {
    await this.loadBrainstormingTasks();
  }

  @Watch('taskId', { immediate: true })
  async onTaskIdChanged(): Promise<void> {
    await this.getTask();
  }

  async getTask(): Promise<void> {
    if (this.taskId) {
      await taskService.getTaskById(this.taskId).then((task) => {
        this.task = task;
      });
      await this.loadBrainstormingTasks();
    }
  }

  async updateParameterForSaving(): Promise<void> {
    if (this.modelValue.parameter) {
      if (this.modelValue.parameter.selectionId) {
        const selectionId = this.modelValue.parameter.selectionId;
        await selectionService.putSelection(selectionId, {
          name: this.modelValue.name,
        });
      } else {
        const topicId = this.task ? this.task.topicId : this.topicId;
        if (topicId) {
          await selectionService
            .postSelection(topicId, { name: this.modelValue.name })
            .then((selection) => {
              this.modelValue.parameter.selectionId = selection.id;
            });
        }
      }
    }
  }
}
</script>
