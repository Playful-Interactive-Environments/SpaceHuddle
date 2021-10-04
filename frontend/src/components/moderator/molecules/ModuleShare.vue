<template>
  <el-switch
    class="is-big"
    v-if="hasParticipantComponent"
    v-model="participant"
    :width="56"
    :inactive-text="$t('general.moduleActive')"
    :active-color="taskTypeColor"
  />
  <el-switch
    class="is-big"
    v-if="hasPublicScreenComponent"
    v-model="publicScreen"
    :width="56"
    :inactive-text="$t('general.publicScreen')"
    :active-color="taskTypeColor"
  />
  <TimerSettings
    v-if="showTimerSettings"
    v-model:showModal="showTimerSettings"
    :task="task"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { convertToSaveVersion, Task } from '@/types/api/Task';
import { hasModule } from '@/modules';
import ModuleComponentType from '@/modules/ModuleComponentType';
import { EventType } from '@/types/enum/EventType';
import TaskStates from '@/types/enum/TaskStates';
import TaskType from '@/types/enum/TaskType';
import TimerSettings from '@/components/moderator/organisms/settings/TimerSettings.vue';
import {
  addError,
  clearErrors,
  getErrorMessage,
} from '@/services/exception-service';
import * as taskService from '@/services/task-service';
import SnackbarType from '@/types/enum/SnackbarType';
import TaskTypeColor from '@/types/TaskTypeColor';
import * as sessionService from '@/services/session-service';

@Options({
  components: {
    TimerSettings,
  },
})
export default class ModuleShare extends Vue {
  @Prop() task!: Task;
  @Prop({ default: false }) isOnPublicScreen!: boolean;
  errors: string[] = [];

  hasParticipantComponent = false;
  hasPublicScreenComponent = false;
  TaskStates = TaskStates;
  showTimerSettings = false;

  get taskType(): TaskType | null {
    if (this.task) return TaskType[this.task.taskType];
    return null;
  }

  get taskTypeColor(): string {
    if (this.taskType) return TaskTypeColor[this.taskType];
    return 'gray';
  }

  @Watch('task', { immediate: true })
  onTaskChanged(): void {
    hasModule(ModuleComponentType.PARTICIPANT, this.taskType, 'default').then(
      (result) => (this.hasParticipantComponent = result)
    );
    hasModule(ModuleComponentType.PUBLIC_SCREEN, this.taskType, 'default').then(
      (result) => (this.hasPublicScreenComponent = result)
    );
  }

  get publicScreen(): boolean {
    return this.isOnPublicScreen;
  }

  set publicScreen(newValue: boolean) {
    if (this.task) {
      const publicScreenTaskId = newValue ? this.task.id : '{taskId}';
      clearErrors(this.errors);
      sessionService
        .displayOnPublicScreen(this.task.sessionId, publicScreenTaskId)
        .then(
          () => {
            this.eventBus.emit(EventType.SHOW_SNACKBAR, {
              type: SnackbarType.SUCCESS,
              message: 'info.updatePublicScreen',
            });

            this.eventBus.emit(
              EventType.CHANGE_PUBLIC_SCREEN,
              publicScreenTaskId
            );
          },
          (error) => {
            addError(this.errors, getErrorMessage(error));
          }
        );
    }
  }

  get participant(): boolean {
    if (this.task)
      return (
        this.task.state === TaskStates.ACTIVE && this.task.remainingTime > 0
      );
    return false;
  }

  set participant(newValue: boolean) {
    this.showTimerSettings = newValue;
    clearErrors(this.errors);
    if (this.task) {
      this.task.state = newValue ? TaskStates.ACTIVE : TaskStates.WAIT;
      const saveVersion = convertToSaveVersion(this.task);
      taskService.updateTask(saveVersion).then(
        () => {
          this.eventBus.emit(EventType.SHOW_SNACKBAR, {
            type: SnackbarType.SUCCESS,
            message: 'info.updateParticipantState',
          });
        },
        (error) => {
          addError(this.errors, getErrorMessage(error));
        }
      );
    }
  }
}
</script>

<style scoped></style>
