<template>
  <el-form-item></el-form-item>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
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
  @Prop({ default: {} }) modelValue!: any;
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
    if (this.modelValue) {
      //todo: set default parameter
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
