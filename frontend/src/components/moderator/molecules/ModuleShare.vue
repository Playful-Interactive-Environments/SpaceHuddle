<template>
  <Toggle
    :label="$t('general.moduleActive')"
    :isActive="task.state === TaskStates.ACTIVE && task.remainingTime > 0"
    v-if="hasParticipantComponent"
    @toggleClicked="changeActiveState($event)"
  />
  <Toggle
    :label="$t('general.publicScreen')"
    :isActive="isOnPublicScreen"
    v-if="hasPublicScreenComponent"
    @toggleClicked="changePublicScreen"
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
import Toggle from '@/components/moderator/atoms/Toggle.vue';
import TimerSettings from '@/components/moderator/organisms/TimerSettings.vue';
import {
  addError,
  clearErrors,
  getErrorMessage,
} from '@/services/exception-service';
import * as taskService from '@/services/task-service';
import SnackbarType from '@/types/enum/SnackbarType';

@Options({
  components: {
    TimerSettings,
    Toggle,
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

  @Watch('task', { immediate: true })
  onTaskChanged(): void {
    hasModule(ModuleComponentType.PARTICIPANT, this.taskType, 'default').then(
      (result) => (this.hasParticipantComponent = result)
    );
    hasModule(ModuleComponentType.PUBLIC_SCREEN, this.taskType, 'default').then(
      (result) => (this.hasPublicScreenComponent = result)
    );
  }

  changePublicScreen(isActive: boolean): void {
    this.eventBus.emit(
      EventType.CHANGE_PUBLIC_SCREEN,
      isActive ? this.task.id : '{taskId}'
    );
  }

  async changeActiveState(newValue: boolean): Promise<void> {
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

    //this.eventBus.emit(EventType.CHANGE_PARTICIPANT_STATE, this.task);
  }
}
</script>

<style scoped></style>
