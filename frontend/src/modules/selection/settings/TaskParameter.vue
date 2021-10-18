<template>
  <el-form-item
    :label="$t('module.selection.settings.taskParameter.name')"
    :prop="`${rulePropPath}.selectionName`"
    :rules="[defaultFormRules.ruleRequired, defaultFormRules.ruleToLong(255)]"
  >
    <el-input
      v-model="modelValue.selectionName"
      name=""
      :placeholder="$t('module.selection.settings.taskParameter.nameExample')"
    />
  </el-form-item>
  <el-form-item
    :label="$t('module.selection.settings.taskParameter.brainstorming')"
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
      ></el-option>
    </el-select>
  </el-form-item>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as taskService from '@/services/task-service';
import * as selectionService from '@/services/selection-service';
import { Task } from '@/types/api/Task';
import { CustomParameter } from '@/types/ui/CustomParameter';
import TaskType from '@/types/enum/TaskType';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';

@Options({
  components: {},
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TaskParameter extends Vue implements CustomParameter {
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
    if (this.modelValue) {
      if (this.modelValue.selectionId) {
        await selectionService
          .getSelectionById(this.modelValue.selectionId)
          .then((selection) => {
            this.modelValue.selectionName = selection.name;
          });
      }
      if (!this.modelValue.brainstormingTaskId)
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
    if (this.modelValue) {
      if (this.modelValue.selectionId) {
        const selectionId = this.modelValue.selectionId;
        await selectionService.putSelection(selectionId, {
          name: this.modelValue.selectionName,
        });
      } else {
        const topicId = this.task ? this.task.topicId : this.topicId;
        if (topicId) {
          await selectionService
            .postSelection(topicId, { name: this.modelValue.selectionName })
            .then((selection) => {
              this.modelValue.selectionId = selection.id;
            });
        }
      }
      delete this.modelValue.selectionName;
    }
  }
}
</script>
