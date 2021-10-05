<template>
  <article
    ref="item"
    class="task-card"
    :class="{ 'task-card--participant': isParticipant }"
  >
    <router-link
      class="clickArea"
      :to="
        isParticipant
          ? `/participant-module-content/${task.id}`
          : `/module-content/${sessionId}/${task.id}`
      "
    >
      <span
        class="task-card-content"
        :class="{ 'task-card-content--participant': isParticipant }"
      >
        <img
          :src="require(`@/assets/illustrations/planets/${type}.png`)"
          alt="planet"
          class="task-card__planet"
        />
        <TaskInfo
          :type="type"
          :title="task.name"
          :description="task.description"
        />
      </span>
    </router-link>

    <span
      class="task-card-content"
      :class="{ 'task-card-content--participant': isParticipant }"
    >
      <Timer
        class="task-card__timer"
        :isActive="task.state === TaskStates.ACTIVE"
        :task="task"
        v-on:timerEnds="$emit('timerEnds')"
        v-on:click="timerClicked"
        v-if="!(type === TaskType.INFORMATION || type === TaskType.SELECTION)"
      />
      <div class="task-card__toggles" v-if="!isParticipant">
        <ModuleShare :task="task" :is-on-public-screen="isOnPublicScreen" />
      </div>
      <div class="task-card__drag" v-if="!isParticipant">
        <font-awesome-icon icon="grip-vertical" class="task-card__drag__icon" />
      </div>
    </span>
  </article>
  <TimerSettings
    v-if="showTimerSettings"
    v-model:showModal="showTimerSettings"
    :task="task"
  />
</template>

<script lang="ts">
import { Prop } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import { setModuleStyles } from '@/utils/moduleStyles';
import { Task } from '@/types/api/Task';
import TaskInfo from '@/components/shared/molecules/TaskInfo.vue';
import Timer from '@/components/shared/atoms/Timer.vue';
import ModuleShare from '@/components/moderator/molecules/ModuleShare.vue';
import TaskType from '@/types/enum/TaskType';
import TaskStates from '@/types/enum/TaskStates';
import TimerSettings from '@/components/moderator/organisms/settings/TimerSettings.vue';

@Options({
  components: {
    TaskInfo,
    Timer,
    ModuleShare,
    TimerSettings,
  },
  emits: ['timerEnds', 'changePublicScreen'],
})
export default class TaskCard extends Vue {
  @Prop({ default: '' }) readonly sessionId!: string;
  @Prop({ default: TaskType.BRAINSTORMING }) type!: TaskType;
  @Prop() task!: Task;
  @Prop({ default: false }) isParticipant!: boolean;
  @Prop({ default: false }) isOnPublicScreen!: boolean;

  TaskType = TaskType;
  TaskStates = TaskStates;
  showTimerSettings = false;

  get moduleName(): string {
    if (this.task && this.task.modules && this.task.modules.length > 0)
      return this.task.modules[0].name;
    return 'default';
  }

  mounted(): void {
    setModuleStyles(this.$refs.item as HTMLElement, this.type);
  }

  updated(): void {
    setModuleStyles(this.$refs.item as HTMLElement, this.type);
  }

  timerClicked(): void {
    if (!this.isParticipant) {
      this.showTimerSettings = true;
    }
  }
}
</script>

<style lang="scss" scoped>
.clickArea {
  width: 100%;
}

.task-card-content {
  display: flex;
  align-items: center;

  &--participant {
    flex-direction: column;
    text-align: center;
    color: var(--color-darkblue);
  }
}

.task-card {
  position: relative;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: white;
  border-radius: var(--border-radius-xs);
  padding: 1.5rem 5rem 1.5rem 4.5rem;
  margin-left: 3rem;

  &:hover {
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
    transition: all 200ms;
  }

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
    margin-left: 2rem;
    width: 12rem;
  }

  &__drag {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    background-color: var(--color-mint);
    background-color: var(--module-color);
    border-radius: 0 var(--border-radius-xs) var(--border-radius-xs) 0;
    display: flex;
    justify-content: center;
    align-items: center;
    width: 35px;
    align-self: stretch;
    cursor: pointer;

    &__icon {
      color: white;
    }
  }

  &__dots-icon {
    width: 12px;
    height: auto;
  }

  &--participant {
    flex-direction: column;
    margin-left: 0;
    padding: 1.5rem 1.5rem 2.2rem;
    text-align: center;
    color: var(--color-darkblue);
    width: 65vw;
    margin-bottom: 1.5rem;
    min-height: calc(100% - 18.4px);

    .task-card__planet {
      position: static;
      transform: none;
      margin-bottom: 0.5rem;
    }

    .task-card__timer {
      position: absolute;
      bottom: 0;
      transform: translateY(50%);
    }
  }
}
</style>
