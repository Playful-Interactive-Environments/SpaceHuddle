<template>
  <el-form-item
    :label="$t('module.voting.settings.taskParameter.displayFinished')"
  >
    <el-switch v-model="modelValue.parameter.displayFinished" />
  </el-form-item>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as taskService from '@/services/task-service';
import { Task, TaskSettingsData } from '@/types/api/Task';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';

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

  @Watch('taskId', { immediate: true })
  async onTaskIdChanged(): Promise<void> {
    this.deregisterAll();
    if (this.taskId) {
      taskService.registerGetTaskById(
        this.taskId,
        this.updateTask,
        EndpointAuthorisationType.MODERATOR,
        60 * 60
      );
    }
  }

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (
      this.modelValue.parameter &&
      !this.modelValue.parameter.displayFinished
    ) {
      this.modelValue.parameter.displayFinished = false;
    }
  }

  updateTask(task: Task): void {
    this.task = task;
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
  }

  unmounted(): void {
    this.deregisterAll();
  }
}
</script>
