<template>
  <AnalyticsChart :session-id="sessionId" :received-tasks="selectedTasks" :topics="topics" />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Task } from '@/types/api/Task';
import { getAsyncModule, getEmptyComponent } from '@/modules';
import ModuleComponentType from '@/modules/ModuleComponentType';
import TaskType from '@/types/enum/TaskType';
import AnalyticsChart from '@/components/moderator/organisms/analytics/analytics.vue';
import { Topic } from '@/types/api/Topic';
import * as taskService from '@/services/task-service';
import * as topicService from '@/services/topic-service';
import { ParticipantInfo } from '@/types/api/Participant';
import * as cashService from '@/services/cash-service';

/* eslint-disable @typescript-eslint/no-explicit-any*/

@Options({
  components: {
    AnalyticsChart,
    PublicScreenComponent: getEmptyComponent(),
  },
})
export default class Analytics extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly task!: Task;
  @Prop({ default: 0 }) readonly timeModifier!: number;
  @Prop({ default: null }) readonly taskParameter!: any;
  @Prop({ default: false }) readonly paused!: boolean;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  componentLoadIndex = 0;

  topics: Topic[] = [];
  participants: ParticipantInfo[] = [];
  viewDetailsForParticipant: ParticipantInfo | null = null;

  showSettings = false;

  selectedTasks: Task[] = [];
  taskCashEntry!: cashService.SimplifiedCashEntry<Task[]>;

  get topicId(): string | null {
    if (this.task) return this.task.topicId;
    return null;
  }

  get sessionId(): string | null {
    if (this.task) return this.task.sessionId;
    return null;
  }

  getModuleName(task: Task): string[] {
    if (task && task.modules && task.modules.length > 0)
      return task.modules.map((module) => module.name);
    return ['default'];
  }

  @Watch('task', { immediate: true })
  async onTaskChanged(): Promise<void> {
    if (this.$options.components) {
      const taskType = TaskType[this.task.taskType];
      await getAsyncModule(
        ModuleComponentType.PUBLIC_SCREEN,
        taskType,
        this.getModuleName(this.task),
        false
      ).then((component) => {
        if (this.$options.components) {
          this.$options.components['PublicScreenComponent'] = component;
          this.componentLoadIndex++;
        }
      });
    }
  }

  @Watch('task', { immediate: true })
  async onSessionIdChanged(): Promise<void> {
    this.taskCashEntry = taskService.registerGetTaskList(
      this.task.topicId,
      this.updateTasks,
      EndpointAuthorisationType.MODERATOR,
      2 * 60
    );
  }

  updateTasks(tasks: Task[]): void {
    this.selectedTasks = tasks.sort((a, b) => (a.order > b.order ? 1 : 0));
    console.log(this.selectedTasks);
  }
}
</script>

<style lang="scss" scoped>
#analytics {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3rem;
  hr {
    margin: 1.5rem 0 0.5rem 0;
    width: 70%;
    background-color: var(--color-background-dark);
    border-radius: var(--border-radius);
  }
  .AnalyticsParallelCoordinates {
    height: 50vh;
    width: 100%;
  }
  .AnalyticsTables {
    height: 40vh;
    width: 100%;
  }
}
</style>
