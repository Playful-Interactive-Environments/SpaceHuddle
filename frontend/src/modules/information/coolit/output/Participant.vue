<template>
  <ParticipantModuleDefaultContainer
    :task-id="taskId"
    :module="moduleName"
    style="padding: 1rem 2rem 0"
  >
    <CoolIt />
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import CoolIt from '@/games/coolit/CoolIt.vue';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/layout/ParticipantModuleDefaultContainer.vue';
import { Prop, Watch } from 'vue-property-decorator';
import { Module } from '@/types/api/Module';
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import * as moduleService from '@/services/module-service';

@Options({
  components: {
    ParticipantModuleDefaultContainer,
    CoolIt,
  },
})
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  @Prop({ default: false }) readonly useFullSize!: boolean;
  @Prop({ default: '' }) readonly backgroundClass!: string;

  module: Module | null = null;
  task: Task | null = null;

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateModule);
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.PARTICIPANT,
      60 * 60
    );
  }

  @Watch('moduleId', { immediate: true })
  onModuleIdChanged(): void {
    if (this.moduleId) {
      moduleService.registerGetModuleById(
        this.moduleId,
        this.updateModule,
        EndpointAuthorisationType.PARTICIPANT,
        60 * 60
      );
    }
  }

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  mounted(): void {
    this.$emit('update:useFullSize', true);
    this.$emit('update:drawNavigation', false);
  }

  unmounted(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateModule);
  }
  updateTask(task: Task): void {
    this.task = task;
  }

  updateModule(module: Module): void {
    this.module = module;
  }
}
</script>
