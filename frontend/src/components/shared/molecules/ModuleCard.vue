<template>
  <router-link
    :to="
      isClient
        ? `/${sessionId}/brainstorming/${task.id}`
        : `/${type}/${sessionId}/${task.id}`
    "
  >
    <article
      ref="item"
      class="module-card"
      :class="{ 'module-card--client': isClient }"
    >
      <img
        :src="require(`@/assets/illustrations/planets/${type}.png`)"
        alt="planet"
        class="module-card__planet"
      />
      <ModuleInfo
        :type="type"
        :title="task.name"
        :description="'Module description here ...'"
      />
      <Timer
        class="module-card__timer"
        v-if="
          !(type === ModuleType.INFORMATION || type === ModuleType.SELECTION)
        "
      />
      <div class="module-card__toggles" v-if="!isClient">
        <Toggle
          label="Active"
          :isActive="task.state === TaskStates.ACTIVE"
          v-if="type !== ModuleType.SELECTION"
          @toggleClicked="changeActiveState"
        />
        <Toggle
          label="Public Screen"
          :isActive="isOnPublicScreen"
          @toggleClicked="changePublicScreen($event)"
        />
      </div>
      <div class="module-card__drag" v-if="!isClient">
        <img
          src="@/assets/icons/drag-dots.svg"
          alt="draggable"
          class="module-card__dots-icon"
        />
      </div>
    </article>
  </router-link>
</template>

<script lang="ts">
import { Prop } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import { setModuleStyles } from '../../../utils/moduleStyles';
import { Task } from '@/services/task-service';
import ModuleInfo from '@/components/shared/molecules/ModuleInfo.vue';
import Timer from '@/components/shared/atoms/Timer.vue';
import Toggle from '@/components/moderator/atoms/Toggle.vue';
import ModuleType from '@/types/ModuleType';
import mitt from 'mitt';
import * as sessionService from '@/services/session-service';
import * as taskService from '@/services/task-service';
import TaskStates from '../../../types/TaskStates';

@Options({
  components: {
    ModuleInfo,
    Timer,
    Toggle,
  },
})
export default class ModuleCard extends Vue {
  @Prop() readonly sessionId!: string;
  @Prop({ default: ModuleType.BRAINSTORMING }) type!: ModuleType;
  @Prop({ default: null }) task!: Task;
  @Prop({ default: false }) isClient!: boolean;
  @Prop({ default: false }) isOnPublicScreen!: boolean;

  ModuleType = ModuleType;
  TaskStates = TaskStates;

  mounted(): void {
    setModuleStyles(this.$refs.item as HTMLElement, this.type);
  }

  updated(): void {
    setModuleStyles(this.$refs.item as HTMLElement, this.type);
  }

  changePublicScreen(show: boolean): void {
    this.eventBus.emit('changePublicScreen', this.task.id);
  }

  async changeActiveState(): Promise<void> {
    this.task.state =
      this.task.state === TaskStates.ACTIVE
        ? TaskStates.WAIT
        : TaskStates.ACTIVE;
    await taskService.updateTask(this.task);
  }
}
</script>

<style lang="scss" scoped>
.module-card {
  position: relative;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: white;
  border-radius: var(--border-radius);
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
  padding: 1.5rem 5rem 1.5rem 4.5rem;
  margin-left: 3rem;

  &__planet {
    position: absolute;
    left: -4rem;
    top: 50%;
    transform: translateY(-50%);
    width: 8rem;
  }

  &__toggles {
    display: flex;
    flex-direction: column;
    margin-left: 3rem;
    width: 12rem;
  }

  &__drag {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    background-color: var(--color-mint);
    background-color: var(--module-color);
    border-radius: 0 var(--border-radius) var(--border-radius) 0;
    display: flex;
    justify-content: center;
    align-items: center;
    width: 35px;
    align-self: stretch;
    cursor: pointer;
  }

  &__dots-icon {
    width: 12px;
    height: auto;
  }

  &--client {
    flex-direction: column;
    margin-left: 0;
    padding: 1.5rem 1.5rem 0;
    text-align: center;
    color: var(--color-darkblue);
    width: 65vw;
    margin-bottom: 1.5rem;

    .module-card__planet {
      position: static;
      transform: none;
      margin-bottom: 0.5rem;
    }

    .module-card__timer {
      margin-top: 0.5rem;
      transform: translateY(50%);
    }
  }
}
</style>
