<template>
  <el-form-item></el-form-item>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as taskService from '@/services/task-service';
import { Task, TaskSettingsData } from '@/types/api/Task';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import * as selectionService from '@/services/selection-service';
import { CustomParameter } from '@/types/ui/CustomParameter';

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

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue.parameter) {
      //todo: set default parameter
    }
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
    }
  }

  async updateParameterForSaving(): Promise<void> {
    if (this.modelValue.parameter) {
      if (this.modelValue.parameter.selectionId) {
        const selectionId = this.modelValue.parameter.selectionId;
        await selectionService.putSelection({
          id: selectionId,
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
