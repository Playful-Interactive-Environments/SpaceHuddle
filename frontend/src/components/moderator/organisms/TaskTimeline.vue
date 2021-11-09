<template>
  <div
    class="task-timeline"
    :class="{ 'task-timeline__vertical': direction === 'vertical' }"
    v-if="tasks.length > 0"
  >
    <div
      :class="{ media: !isVertical, stretch: isVertical }"
      :style="{ 'grid-template-rows': `1fr ${tasks.length * 2 - 1}fr` }"
    >
      <el-switch
        v-model="usePublicScreen"
        :class="{ 'media-left': !isVertical }"
        :style="{
          width: isVertical ? 'auto' : `calc(100% / (${tasks.length} * 2))`,
        }"
      />
      <el-slider
        class="media-content"
        v-if="tasks.length > 0"
        :disabled="!usePublicScreen"
        :max="tasks.length - 1"
        v-model="activeOnPublicScreen"
        :vertical="isVertical"
        :style="{
          margin: isVertical
            ? '0.3rem' //`0.3rem 0.3rem 2.3rem 0.3rem`
            : `0.3rem calc(100% / (${tasks.length} * 2)) 0.3rem 0rem`,
        }"
        :format-tooltip="tooltip"
        :height="isVertical ? `100%` : ''"
      ></el-slider>
    </div>
    <el-steps :direction="direction" :active="active" align-center>
      <draggable
        v-model="tasks"
        tag="transition-group"
        item-key="order"
        handle=".el-step__head"
        @end="dragDone"
      >
        <template #item="{ element, index }">
          <el-step icon="-">
            <template #icon>
              <img
                :src="
                  require(`@/assets/illustrations/planets/${element.taskType.toLowerCase()}.png`)
                "
                alt="planet"
              />
            </template>
            <template #title>
              <el-badge
                :value="formattedTime(element.remainingTime)"
                :hidden="!isActive(element)"
              >
                <el-checkbox-button
                  :checked="isActive(element)"
                  v-on:change="setActive(element, $event)"
                >
                  <font-awesome-icon icon="mobile" />
                </el-checkbox-button>
              </el-badge>
            </template>
            <template #description>
              <span class="link" v-on:click="taskClicked(element, index)">
                {{ element.name }}
              </span>
            </template>
          </el-step>
        </template>
      </draggable>
    </el-steps>
    <TimerSettings
      v-if="showTimerSettings"
      v-model:showModal="showTimerSettings"
      :task="timerTask"
    />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as taskService from '@/services/task-service';
import { convertToSaveVersion, Task } from '@/types/api/Task';
import draggable from 'vuedraggable';
import * as sessionService from '@/services/session-service';
import { ElMessage } from 'element-plus';
import TaskStates from '@/types/enum/TaskStates';
import { EventType } from '@/types/enum/EventType';
import TimerSettings from '@/components/moderator/organisms/settings/TimerSettings.vue';

@Options({
  components: {
    draggable,
    TimerSettings,
  },
  emits: ['changeActiveElement'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TaskTimeline extends Vue {
  @Prop() readonly topicId!: string;
  @Prop() readonly sessionId!: string;
  @Prop({ default: 'horizontal' }) readonly direction!: string;
  @Prop({ default: 0 }) readonly active!: number;

  tasks: Task[] = [];
  publicScreenTaskId = '';
  timerTask: Task | null = null;

  get showTimerSettings(): boolean {
    return this.timerTask !== null;
  }

  set showTimerSettings(value: boolean) {
    if (!value) this.timerTask = null;
  }

  get isVertical(): boolean {
    return this.direction === 'vertical';
  }

  @Watch('topicId', { immediate: true })
  async onTopicIdChanged(): Promise<void> {
    await this.getTasks();
  }

  @Watch('sessionId', { immediate: true })
  async onSessionIdChanged(): Promise<void> {
    await this.getPublicScreen();
  }

  async getTasks(): Promise<void> {
    taskService.getTaskList(this.topicId).then((tasks) => {
      this.tasks = tasks;
    });
  }

  dragDone(): void {
    const tasks = this.tasks;
    if (tasks) {
      for (let i = 0; i < tasks.length; i++) {
        tasks[i].order = i;
        taskService.putTask(tasks[i].id, convertToSaveVersion(tasks[i]));
      }
    }
  }

  taskClicked(task: Task, index: number): void {
    this.$emit('changeActiveElement', task);
    //this.active = index;
  }

  isActive(task: Task): boolean {
    return taskService.isActive(task);
  }

  setActive(task: Task, newValue: boolean): void {
    task.state = newValue ? TaskStates.ACTIVE : TaskStates.WAIT;
    const saveVersion = convertToSaveVersion(task);
    taskService.updateTask(saveVersion).then(() => {
      if (newValue) this.timerTask = task;
      ElMessage({
        message: (this as any).$t('info.updateParticipantState'),
        type: 'success',
        center: true,
        showClose: true,
      });
    });
  }

  tooltip(index: number): string {
    const publicTask = this.tasks[index];
    return publicTask.name;
  }

  goToDetails(taskId: string): void {
    this.$router.push(`/module-content/${this.sessionId}/${taskId}`);
  }

  async getPublicScreen(): Promise<void> {
    sessionService.getPublicScreen(this.sessionId).then((queryResult) => {
      if (queryResult) {
        this.publicScreenTaskId = queryResult.id;
      }
    });
  }

  get usePublicScreen(): boolean {
    return this.activeOnPublicScreen > -1;
  }

  set usePublicScreen(use: boolean) {
    if (use && this.tasks.length > 0)
      this.publicScreenTaskId = this.tasks[0].id;
    else if (!use) this.publicScreenTaskId = '';
    const publicScreenTaskId = this.publicScreenTaskId
      ? this.publicScreenTaskId
      : '{taskId}';
    sessionService
      .displayOnPublicScreen(this.sessionId, publicScreenTaskId)
      .then(() => {
        ElMessage({
          message: (this as any).$t('info.updatePublicScreen'),
          type: 'success',
          center: true,
          showClose: true,
        });

        this.eventBus.emit(
          EventType.CHANGE_PUBLIC_SCREEN,
          this.publicScreenTaskId
        );
      });
  }

  get activeOnPublicScreen(): number {
    const index = this.tasks.findIndex(
      (task) => task.id == this.publicScreenTaskId
    );
    if (this.isVertical && index > -1) return this.tasks.length - 1 - index;
    return index;
  }

  set activeOnPublicScreen(index: number) {
    if (this.usePublicScreen) {
      if (this.isVertical) index = this.tasks.length - 1 - index;
      if (this.tasks.length > index) {
        const publicTask = this.tasks[index];
        if (
          this.publicScreenTaskId &&
          this.publicScreenTaskId !== '' &&
          this.publicScreenTaskId !== publicTask.id
        ) {
          sessionService
            .displayOnPublicScreen(this.sessionId, publicTask.id)
            .then(() => {
              this.publicScreenTaskId = publicTask.id;
              ElMessage({
                message: (this as any).$t('info.updatePublicScreen'),
                type: 'success',
                center: true,
                showClose: true,
              });

              this.eventBus.emit(
                EventType.CHANGE_PUBLIC_SCREEN,
                this.publicScreenTaskId
              );
            });
        }
      }
    }
  }

  formattedTime(timeLeft: number | null): string {
    if (timeLeft !== null) {
      let minutes = Math.floor(timeLeft / 60);
      let seconds = timeLeft - minutes * 60;
      return `${minutes}:${seconds < 10 ? '0' + seconds : seconds}`;
    }
    return 'Ꝏ';
  }

  interval!: any;
  readonly intervalTime = 1000;
  startTimer(): void {
    clearInterval(this.interval);
    this.interval = setInterval(() => this.refreshTimer(), this.intervalTime);
  }

  refreshTimer(): void {
    this.tasks.forEach((task) => {
      if (task.remainingTime !== null && task.remainingTime > 0) {
        task.remainingTime -= 1;
        if (task.remainingTime == 0) {
          this.setActive(task, false);
        }
      } else if (task.state == TaskStates.ACTIVE && task.remainingTime == 0) {
        this.setActive(task, false);
      }
    });
  }

  mounted(): void {
    this.startTimer();
  }

  unmounted(): void {
    clearInterval(this.interval);
  }
}
</script>

<style lang="scss" scoped>
.media-left {
  margin-right: unset;
  margin-top: auto;
  margin-bottom: auto;
}

.public-screen-slider {
  background-color: aqua;
  display: inline-flex;
  flex-direction: row;
  justify-content: space-between;
  width: 100%;
  height: 100%;
}

.el-step::v-deep {
  .el-step__icon {
    background: var(--color-background-gray);
    cursor: grab;
  }

  .el-step__title {
    &.is-process,
    &.is-finish {
      color: inherit;
    }
  }
}

.el-slider::v-deep {
  .el-slider__button {
    mask-image: url('~@/assets/icons/svg/public-screen.svg');
    mask-repeat: no-repeat;
    mask-position: center;
    mask-size: contain;
    background-color: white;
    border-width: 4px 5px 8px 4px;
    border-color: var(--color-primary);
    border-radius: unset;
    width: calc(var(--el-slider-button-size) + 4px);
  }
}

.el-checkbox-button::v-deep {
  &:first-child .el-checkbox-button__inner,
  &:last-child .el-checkbox-button__inner {
    border-radius: 50%;
    border: unset;
  }
  .el-checkbox-button__inner {
    color: var(--el-text-color-placeholder);
    border-radius: 0;
    border: unset;
    margin-bottom: 1rem;
    padding: 0.3rem 0.5rem;
    font-size: var(--font-size-large);
  }

  &.is-checked .el-checkbox-button__inner {
    color: var(--color-primary);
    background-color: unset;
  }
}

.el-badge::v-deep {
  .el-badge__content--primary {
    right: calc(5px + var(--el-badge-size) / 2);
  }
}

.task-timeline {
  &__vertical {
    display: flex;

    .stretch {
      position: relative;
      top: -2rem;
      display: inline-flex;
      flex-direction: column;
      gap: 1rem;
    }

    .el-switch::v-deep.is-checked .el-switch__core {
      border-color: var(--color-darkblue-light);
      background-color: var(--color-darkblue-light);
    }

    .el-step::v-deep {
      .el-step__icon {
        background-color: var(--color-primary);
      }

      .el-step__main {
        display: flex;

        .el-step__description {
          padding: 0.6rem;

          &.is-process,
          &.is-finish {
            color: white;
          }
        }
      }
    }

    .el-slider::v-deep {
      .el-slider__button {
        background-color: var(--color-primary);
        border-color: white;
      }

      .el-slider__runway.disabled .el-slider__button {
        border-color: var(--el-slider-disable-color);
      }

      .el-slider__bar {
        background-color: var(--el-slider-runway-background-color);
      }

      .el-slider__runway {
        background-color: white;
      }
    }

    .el-checkbox-button::v-deep {
      &.is-checked .el-checkbox-button__inner {
        color: white;
      }
    }

    .el-badge::v-deep {
      .el-badge__content--primary {
        background-color: white;
        color: var(--color-primary);
        border-color: var(--color-primary);
      }
    }
  }
}
</style>
