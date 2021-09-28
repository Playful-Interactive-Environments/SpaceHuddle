<template>
  <Toggle
    :label="$t('general.moduleActive')"
    :isActive="task.state === TaskStates.ACTIVE"
    v-if="hasParticipantComponent"
    @toggleClicked="changeActiveState"
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
import { Task } from '@/types/api/Task';
import { hasModule } from '@/modules';
import ModuleComponentType from '@/modules/ModuleComponentType';
import { EventType } from '@/types/enum/EventType';
import TaskStates from '@/types/enum/TaskStates';
import TaskType from '@/types/enum/TaskType';
import Toggle from '@/components/moderator/atoms/Toggle.vue';
import TimerSettings from '@/components/moderator/organisms/TimerSettings.vue';

@Options({
  components: {
    TimerSettings,
    Toggle,
  },
})
export default class ModuleShare extends Vue {
  @Prop() task!: Task;
  @Prop({ default: false }) isOnPublicScreen!: boolean;

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

  async changeActiveState(): Promise<void> {
    if (this.task.state === TaskStates.WAIT) this.showTimerSettings = true;
    if (this.task.state === TaskStates.ACTIVE) this.showTimerSettings = false;
    this.eventBus.emit(EventType.CHANGE_PARTICIPANT_STATE, this.task);
  }
}
</script>

<style scoped></style>
