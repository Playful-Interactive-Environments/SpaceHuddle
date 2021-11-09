<template>
  <el-form-item></el-form-item>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as taskService from '@/services/task-service';
import { Task, TaskSettingsData } from '@/types/api/Task';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';

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
}
</script>
