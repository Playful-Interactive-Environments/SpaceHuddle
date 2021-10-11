<template>
  <el-card class="card" shadow="hover" :body-style="{ padding: '0px' }">
    <span class="level" ref="item">
      <span
        class="level-left"
        style="cursor: pointer"
        v-on:click="
          $router.push(
            isParticipant
          ? `/participant-module-content/${task.id}`
          : `/module-content/${sessionId}/${task.id}`)">
        <div class="level-item card__planet" v-if="!isParticipant">
          <img
            :src="require(`@/assets/illustrations/planets/${type}.png`)"
            alt="planet"
          />
          <div class="card__half-card"></div>
        </div>
        <div class="level-item card__planet_only" v-if="isParticipant">
          <img
            :src="require(`@/assets/illustrations/planets/${type}.png`)"
            alt="planet"
          />
        </div>
        <div class="level-item card__info">
          <TaskInfo
            :type="type"
            :title="task.name"
            :description="task.description"
          />
        </div>
      </span>
      <span class="level-right">
        <Timer
          class="card__timer level-item"
          :isActive="task.state === TaskStates.ACTIVE"
          :task="task"
          v-on:timerEnds="$emit('timerEnds')"
          v-on:click="timerClicked"
          v-if="!(type === TaskType.INFORMATION || type === TaskType.SELECTION)"
        />
        <div class="level-item">
          <div class="card__toggles" v-if="!isParticipant">
            <ModuleShare :task="task" :is-on-public-screen="isOnPublicScreen" />
          </div>
        </div>
        <div class="card__drag level-item" v-if="!isParticipant">
          <font-awesome-icon icon="grip-vertical" class="card__drag__icon" />
        </div>
      </span>
    </span>
  </el-card>
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
    setModuleStyles(this.type, this.$refs.item as HTMLElement);
  }

  updated(): void {
    setModuleStyles(this.type, this.$refs.item as HTMLElement);
  }

  timerClicked(event: PointerEvent): void {
    if (!this.isParticipant) {
      this.showTimerSettings = true;
    }
  }
}
</script>

<style lang="scss" scoped>
.level {
  align-items: stretch;
}

.card {
  min-width: 18rem;

  &__planet_only {
    padding-top: 1.5rem;
    img {
      //margin: 0.5rem;
      width: 8rem;
    }
  }

  &__planet {
    background-image: url('~@/assets/illustrations/stars-background_without_dust.png');
    background-size: cover;

    img {
      margin: 0.5rem;
      width: 8rem;
    }
  }

  &__half-card {
    background-color: white;
    border-radius: var(--border-radius-xs) 0 0 var(--border-radius-xs);
    width: var(--border-radius-xs);
    align-self: stretch;
  }

  &__drag {
    background-color: var(--color-mint);
    background-color: var(--module-color);
    border-radius: 0 var(--border-radius-xs) var(--border-radius-xs) 0;
    width: 1.5rem;
    align-self: stretch;
    cursor: grab;

    &__icon {
      color: white;
    }
  }
}

@media only screen and (max-width: 768px) {
  .level-right {
    display: flex;
    align-items: center;
  }
}

@media only screen and (min-width: 950px) {
  .level-left,
  .level-right {
    max-width: 50%;
  }

  .level {
    align-items: stretch;
  }

  .level-right .level-item:not(:last-child) {
    margin-right: 1.2rem;
  }

  .card {
    &__info {
      max-width: calc(100% - 10rem);
    }
  }
}

@media only screen and (max-width: 949px) {
  .module-info {
    text-align: center;
  }

  .level-item {
    flex-direction: column;
  }

  .level,
  .level-left,
  .level-right {
    flex-direction: column;
    align-items: stretch;
  }

  .level-right {
    align-items: center;
  }

  .level-left .level-item:not(:last-child) {
    margin-right: 0;
  }

  .level-item:not(:last-child) {
    margin-bottom: 0.75rem;
  }

  .level-left + .level-right {
    margin-top: 1.5rem;
  }

  .card {
    &__planet {
      width: unset;
    }

    &__half-card {
      height: var(--border-radius-xs);
      width: unset;
      border-radius: var(--border-radius-xs) var(--border-radius-xs) 0 0;
    }

    &__drag {
      width: unset;
      height: 1.5rem;
      border-radius: 0 0 var(--border-radius-xs) var(--border-radius-xs);
    }
  }
}

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
