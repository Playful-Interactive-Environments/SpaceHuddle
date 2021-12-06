<template>
  <ProcessTimeline
    v-model="tasks"
    v-model:publicScreen="publicTask"
    v-model:activeItem="editTask"
    translationModuleName="taskTimeline"
    :entityName="TimerEntity.TASK"
    :direction="direction"
    :readonly="readonly"
    :canDisablePublicTimeline="true"
    :isLinkedToDetails="isLinkedToTask"
    :startParticipantOnPublicChange="false"
    keyPropertyName="id"
    :defaultTimerSeconds="null"
    :authHeaderTyp="authHeaderTyp"
    :hasParticipantOption="(item) => hasParticipantComponent[item.id]"
    :contentListIcon="
      (item) =>
        require(`@/assets/illustrations/planets/${item.taskType.toLowerCase()}.png`)
    "
    :getKey="(item) => item.id"
    :getTitle="(item) => item.name"
    :getTimerEntity="(item) => item"
    @changeOrder="dragDone"
    @changeActiveElement="onEditTaskChanged"
    @changePublicScreen="onPublicTaskChanged"
  >
  </ProcessTimeline>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as taskService from '@/services/task-service';
import { convertToSaveVersion, Task } from '@/types/api/Task';
import draggable from 'vuedraggable';
import * as sessionService from '@/services/session-service';
import { EventType } from '@/types/enum/EventType';
import TimerSettings from '@/components/moderator/organisms/settings/TimerSettings.vue';
import { hasModule } from '@/modules';
import ModuleComponentType from '@/modules/ModuleComponentType';
import TaskType from '@/types/enum/TaskType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import ProcessTimeline from '@/components/moderator/organisms/Timeline/ProcessTimeline.vue';
import { TimerEntity } from '@/types/enum/TimerEntity';

@Options({
  components: {
    ProcessTimeline,
    draggable,
    TimerSettings,
  },
  emits: ['changeActiveElement', 'changePublicScreen'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TaskTimeline extends Vue {
  @Prop() readonly topicId!: string;
  @Prop() readonly sessionId!: string;
  @Prop({ default: 'horizontal' }) readonly direction!: string;
  @Prop({ default: false }) readonly readonly!: boolean;
  @Prop({ default: true }) readonly isLinkedToTask!: boolean;
  @Prop() activeTaskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;

  tasks: Task[] = [];
  publicTask: Task | null = null;
  editTask: Task | null = null;
  hasParticipantComponent: { [name: string]: boolean } = {};

  TimerEntity = TimerEntity;

  getTaskFromId(id: string): Task | null {
    const task = this.tasks.find((task) => task.id == id);
    if (task) return task;
    return null;
  }

  async onEditTaskChanged(): Promise<void> {
    if (this.editTask) this.$emit('changeActiveElement', this.editTask);
  }

  async onPublicTaskChanged(): Promise<void> {
    console.log('change public screen');
    console.log(this.publicTask?.name);
    if (this.publicTask) {
      sessionService
        .displayOnPublicScreen(this.sessionId, this.publicTask.id)
        .then(() => {
          this.eventBus.emit(
            EventType.CHANGE_PUBLIC_SCREEN,
            this.publicTask?.id
          );
        });
    }
    if (this.publicTask) this.$emit('changePublicScreen', this.publicTask.id);
  }

  @Watch('activeTaskId', { immediate: true })
  async onActiveTaskIdChanged(): Promise<void> {
    const task = this.getTaskFromId(this.activeTaskId);
    if (task) this.editTask = task;
  }

  @Watch('topicId', { immediate: true })
  async onTopicIdChanged(): Promise<void> {
    await this.getTasks();
  }

  async getTasks(): Promise<void> {
    taskService.getTaskList(this.topicId, this.authHeaderTyp).then((tasks) => {
      this.tasks = tasks;
      if (this.activeTaskId) this.onActiveTaskIdChanged();
      this.getPublicScreen();
      tasks.forEach((task) => {
        hasModule(
          ModuleComponentType.PARTICIPANT,
          TaskType[task.taskType],
          'default'
        ).then(
          (result) =>
            (this.hasParticipantComponent[task.id] =
              result && !task.syncPublicParticipant)
        );
      });
    });
  }

  async getPublicScreen(): Promise<void> {
    sessionService
      .getPublicScreen(this.sessionId, this.authHeaderTyp)
      .then((queryResult) => {
        if (queryResult) {
          const task = this.getTaskFromId(queryResult.id);
          if (task) this.publicTask = task;
        }
      });
  }

  /* eslint-disable @typescript-eslint/explicit-module-boundary-types*/
  async dragDone(list: any[]): Promise<void> {
    list.forEach((task, index) => {
      task.order = index;
      taskService.putTask(task.id, convertToSaveVersion(task));
    });
  }
}
</script>
