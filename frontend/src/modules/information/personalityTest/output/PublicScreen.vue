<template>
  <ResultTypeResult
    :task-id="taskId"
    :chart-type="ResultChartType.PUBLIC"
    :test="test"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import ResultTypeResult from '@/modules/information/personalityTest/organisms/ResultTypeResult.vue';
import { ResultChartType } from '@/modules/information/personalityTest/types/ResultChartType';
import * as cashService from '@/services/cash-service';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';

@Options({
  components: { ResultTypeResult },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  test = '';

  ResultChartType = ResultChartType;

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
    this.test = task.modules[0].parameter.test;
  }
}
</script>

<style lang="scss" scoped></style>
