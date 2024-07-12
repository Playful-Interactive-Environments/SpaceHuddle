<template>
  <ResultTypeResult
    :task-id="taskId"
    :chart-type="ResultChartType.STATISTIC"
    :test="test"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ResultTypeResult from '@/modules/information/personalityTest/organisms/ResultTypeResult.vue';
import { Bar } from 'vue-chartjs';
import * as cashService from '@/services/cash-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';
import { ResultChartType } from '@/modules/information/personalityTest/types/ResultChartType';

@Options({
  components: { ResultTypeResult, Bar },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModuleStatistic extends Vue {
  @Prop() readonly taskId!: string;

  ResultChartType = ResultChartType;

  task: Task | null = null;
  test = '';

  unmounted(): void {
    cashService.deregisterAllGet(this.updateTask);
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    cashService.deregisterAllGet(this.updateTask);

    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.MODERATOR,
      60 * 60
    );
  }

  updateTask(task: Task): void {
    this.task = task;
    this.test = task.modules[0].parameter.test;
  }
}
</script>
