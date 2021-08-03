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
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { Task } from '@/types/api/Task';
import { hasModule } from '@/modules';
import ModuleComponentType from '@/modules/ModuleComponentType';
import { EventType } from '@/types/enum/EventType';
import TaskStates from '@/types/enum/TaskStates';
import TaskType from '@/types/enum/TaskType';
import Toggle from '@/components/moderator/atoms/Toggle.vue';

@Options({
  components: {
    Toggle,
  },
})
export default class ModuleShare extends Vue {
  @Prop() task!: Task;
  @Prop({ default: false }) isOnPublicScreen!: boolean;

  hasParticipantComponent = false;
  hasPublicScreenComponent = false;
  TaskStates = TaskStates;

  get taskType(): TaskType | null {
    if (this.task) return TaskType[this.task.taskType];
    return null;
  }

  mounted(): void {
    this.hasParticipantComponent = hasModule(
      ModuleComponentType.PARTICIPANT,
      this.taskType,
      'default'
    );
    this.hasPublicScreenComponent = hasModule(
      ModuleComponentType.PUBLIC_SCREEN,
      this.taskType,
      'default'
    );
  }

  changePublicScreen(isActive: boolean): void {
    this.eventBus.emit(
      EventType.CHANGE_PUBLIC_SCREEN,
      isActive ? this.task.id : '{taskId}'
    );
  }

  async changeActiveState(): Promise<void> {
    this.eventBus.emit(EventType.CHANGE_PARTICIPANT_STATE, this.task);
  }
}
</script>

<style scoped></style>
