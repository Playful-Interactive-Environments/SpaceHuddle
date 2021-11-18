<template>
  <div>
    <div
      class="task-timeline"
      :class="{ 'task-timeline__vertical': direction === 'vertical' }"
      v-if="tasks.length > 0 && !readonly"
    >
      <div
        :class="{ media: !isVertical, stretch: isVertical }"
        :style="{ 'grid-template-rows': `1fr ${tasks.length * 2 - 1}fr` }"
      >
        <el-tooltip
          placement="top-start"
          :content="$t('moderator.organism.taskTimeline.activatePublicScreen')"
        >
          <el-switch
            v-model="usePublicScreen"
            :class="{ 'media-left': !isVertical }"
            :style="{
              width: isVertical
                ? 'auto'
                : `calc(100% / (${activePageTasks.length} * 2))`,
            }"
          />
        </el-tooltip>
        <el-slider
          class="media-content"
          v-if="activePageTasks.length > 0"
          :disabled="!usePublicScreen"
          :max="activePageTasks.length - 1"
          v-model="activeOnPublicScreen"
          :vertical="isVertical"
          :style="{
            margin: isVertical
              ? '0.3rem' //`0.3rem 0.3rem 2.3rem 0.3rem`
              : `0.3rem calc(100% / (${activePageTasks.length} * 2)) 0.3rem 0rem`,
          }"
          :format-tooltip="tooltip"
          :height="isVertical ? `100%` : ''"
        ></el-slider>
      </div>
      <el-steps :direction="direction" :active="active" align-center>
        <draggable
          v-model="activePageTasks"
          tag="transition-group"
          item-key="order"
          handle=".el-step__head"
          @end="dragDone"
        >
          <template #item="{ element, index }">
            <el-step icon="-">
              <template #icon>
                <el-tooltip
                  placement="top"
                  :content="$t('moderator.organism.taskTimeline.changeOrder')"
                >
                  <img
                    :src="
                      require(`@/assets/illustrations/planets/${element.taskType.toLowerCase()}.png`)
                    "
                    alt="planet"
                  />
                </el-tooltip>
              </template>
              <template #title>
                <el-badge
                  :value="formattedTime(element.remainingTime)"
                  :hidden="!isActive(element)"
                  :class="{ 'no-module': !hasParticipantComponent[element.id] }"
                >
                  <el-tooltip
                    placement="top"
                    :content="
                      $t('moderator.organism.taskTimeline.activateParticipant')
                    "
                  >
                    <el-button
                      :class="{ 'is-checked': isActive(element) }"
                      v-on:click="setActive(element)"
                      circle
                    >
                      <font-awesome-icon icon="mobile" />
                    </el-button>
                  </el-tooltip>
                </el-badge>
              </template>
              <template #description>
                <el-tooltip
                  placement="top"
                  :content="$t('moderator.organism.taskTimeline.selectTask')"
                  v-if="isLinkedToTask"
                >
                  <span class="link" v-on:click="taskClicked(element, index)">
                    {{ element.name }}
                  </span>
                </el-tooltip>
                <span v-else>
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
    <div
      class="task-timeline"
      :class="{ 'task-timeline__vertical': direction === 'vertical' }"
      v-else-if="tasks.length > 0 && readonly"
    >
      <el-steps
        :direction="direction"
        :active="activeTaskIndex"
        align-center
        class="readonly"
      >
        <el-step
          icon="-"
          v-for="(element, index) in activePageTasks"
          :key="element.id"
        >
          <template #icon>
            <img
              :src="
                require(`@/assets/illustrations/planets/${element.taskType.toLowerCase()}.png`)
              "
              alt="planet"
            />
          </template>
          <template #description>
            <span class="link" v-on:click="taskClicked(element, index)">
              {{ element.name }}
            </span>
          </template>
        </el-step>
      </el-steps>
    </div>
    <el-pagination
      v-if="pages.length > 1"
      :page-size="pageSize"
      :pager-count="11"
      layout="prev, pager, next"
      :total="tasks.length"
      v-model:current-page="activePage"
      :class="{ 'is-vertical': direction === 'vertical' }"
    >
    </el-pagination>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as taskService from '@/services/task-service';
import { convertToSaveVersion, Task } from '@/types/api/Task';
import draggable from 'vuedraggable';
import * as sessionService from '@/services/session-service';
import TaskStates from '@/types/enum/TaskStates';
import { EventType } from '@/types/enum/EventType';
import TimerSettings from '@/components/moderator/organisms/settings/TimerSettings.vue';
import { hasModule } from '@/modules';
import ModuleComponentType from '@/modules/ModuleComponentType';
import TaskType from '@/types/enum/TaskType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

@Options({
  components: {
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
  @Prop({ default: 0 }) readonly active!: number;
  @Prop({ default: false }) readonly readonly!: boolean;
  @Prop({ default: true }) readonly isLinkedToTask!: boolean;
  @Prop() activeTaskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;

  tasks: Task[] = [];
  publicScreenTaskId = '';
  timerTask: Task | null = null;
  hasParticipantComponent: { [name: string]: boolean } = {};
  activePage = 1;
  pageSize = 10;
  pages: Task[][] = [];

  get showTimerSettings(): boolean {
    return this.timerTask !== null;
  }

  set showTimerSettings(value: boolean) {
    if (!value) this.timerTask = null;
  }

  get isVertical(): boolean {
    return this.direction === 'vertical';
  }

  get activeTaskIndex(): number {
    if (this.activePageTasks && this.activeTaskId) {
      const index = this.activePageTasks.findIndex(
        (task) => task.id == this.activeTaskId
      );
      if (index > -1) return index;
    }
    return 0;
  }

  get activePageTasks(): Task[] {
    if (this.pages.length > 0 && this.pages.length >= this.activePage)
      return this.pages[this.activePage - 1];
    return [];
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
    taskService.getTaskList(this.topicId, this.authHeaderTyp).then((tasks) => {
      this.tasks = tasks;
      for (let i = 0; i < tasks.length / this.pageSize; i++) {
        this.pages[i] = [];
      }
      tasks.forEach((task, index) => {
        this.pages[Math.floor(index / this.pageSize)].push(task);
        if (task.id == this.activeTaskId) {
          this.activePage = Math.floor(index / this.pageSize) + 1;
        }
        hasModule(
          ModuleComponentType.PARTICIPANT,
          TaskType[task.taskType],
          'default'
        ).then((result) => (this.hasParticipantComponent[task.id] = result));
      });
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

  setActive(task: Task): void {
    this.timerTask = task;
  }

  setInactive(task: Task): void {
    task.state = TaskStates.WAIT;
    const saveVersion = convertToSaveVersion(task);
    taskService.updateTask(saveVersion);
  }

  tooltip(index: number): string {
    //const publicTask = this.tasks[index];
    return (this as any).$t(
      'moderator.organism.taskTimeline.changePublicScreen'
    ); // publicTask.name;
  }

  goToDetails(taskId: string): void {
    this.$router.push(`/module-content/${this.sessionId}/${taskId}`);
  }

  async getPublicScreen(): Promise<void> {
    sessionService
      .getPublicScreen(this.sessionId, this.authHeaderTyp)
      .then((queryResult) => {
        if (queryResult) {
          this.publicScreenTaskId = queryResult.id;
        }
      });
  }

  get usePublicScreen(): boolean {
    return this.activeOnPublicScreen > -1;
  }

  set usePublicScreen(use: boolean) {
    if (use && this.activePageTasks.length > 0)
      this.publicScreenTaskId = this.activePageTasks[0].id;
    else if (!use) this.publicScreenTaskId = '';
    const publicScreenTaskId = this.publicScreenTaskId
      ? this.publicScreenTaskId
      : '{taskId}';
    sessionService
      .displayOnPublicScreen(this.sessionId, publicScreenTaskId)
      .then(() => {
        this.$emit('changePublicScreen', this.publicScreenTaskId);

        this.eventBus.emit(
          EventType.CHANGE_PUBLIC_SCREEN,
          this.publicScreenTaskId
        );
      });
  }

  get activeOnPublicScreen(): number {
    const index = this.activePageTasks.findIndex(
      (task) => task.id == this.publicScreenTaskId
    );
    if (this.isVertical && index > -1) return this.tasks.length - 1 - index;
    return index;
  }

  set activeOnPublicScreen(index: number) {
    if (this.usePublicScreen) {
      if (this.isVertical) index = this.activePageTasks.length - 1 - index;
      if (this.activePageTasks.length > index) {
        const publicTask = this.activePageTasks[index];
        if (
          this.publicScreenTaskId &&
          this.publicScreenTaskId !== '' &&
          this.publicScreenTaskId !== publicTask.id
        ) {
          sessionService
            .displayOnPublicScreen(this.sessionId, publicTask.id)
            .then(() => {
              this.publicScreenTaskId = publicTask.id;

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
          this.setInactive(task);
        }
      } else if (task.state == TaskStates.ACTIVE && task.remainingTime == 0) {
        this.setInactive(task);
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
.el-pagination::v-deep {
  text-align: center;
  button,
  button:disabled,
  li {
    background-color: unset;
  }

  &.is-vertical {
    button:enabled,
    li {
      color: white;
    }

    .active {
      color: var(--color-purple);
    }
  }

  li {
    font-weight: var(--font-weight-default);
    color: var(--color-primary);
  }

  .active {
    color: var(--color-purple);
    font-weight: var(--font-weight-bold);
  }
}

.no-module {
  visibility: hidden;
}

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

.el-steps::v-deep {
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

  &.readonly {
    .is-icon {
      width: 25px;
    }

    .el-step__description {
      padding-top: 12.5px;

      &.is-process {
        padding-top: 0;
      }
    }

    .is-wait img {
      -webkit-filter: grayscale(1); /* Webkit */
      filter: gray; /* IE6-9 */
      filter: grayscale(1); /* W3C */
    }

    .is-process {
      font-weight: var(--font-weight-bold);
      .is-icon {
        width: 50px;
      }
    }

    .is-finish {
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

.el-badge::v-deep {
  margin-right: 0.7rem;
}

.el-button::v-deep {
  &.is-circle {
    padding: 0;
    color: var(--color-gray-inactive);
    background-color: unset;
    border: unset;
    font-size: 20pt;
    min-height: unset;
    min-width: 25px;

    &.is-checked {
      color: var(--color-primary);
    }
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

    .el-button::v-deep {
      &.is-circle.is-checked {
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
