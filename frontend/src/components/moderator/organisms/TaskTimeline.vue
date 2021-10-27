<template>
  <div>
    <el-slider
      v-if="tasks.length > 0"
      :max="tasks.length - 1"
      v-model="activeOnPublicScreen"
      :vertical="direction === 'vertical'"
      :style="{
        margin:
          direction === 'vertical'
            ? `calc(100% / (${tasks.length} * 2)) 0.3rem`
            : `0.3rem calc(100% / (${tasks.length} * 2))`,
      }"
      :format-tooltip="tooltip"
    ></el-slider>
    <el-steps :direction="direction" :active="active" align-center>
      <draggable
        v-model="tasks"
        tag="transition-group"
        item-key="order"
        handle=".el-step__head"
        @end="dragDone"
      >
        <template #item="{ element }">
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
              <el-checkbox-button
                :checked="isActive(element)"
                v-on:change="setActive(element, $event)"
              >
                <font-awesome-icon icon="mobile" />
              </el-checkbox-button>
            </template>
            <template #description>
              <router-link :to="`/module-content/${sessionId}/${element.id}`">
                {{ element.name }}
              </router-link>
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
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TaskTimeline extends Vue {
  @Prop() readonly topicId!: string;
  @Prop() readonly sessionId!: string;
  @Prop({ default: 'horizontal' }) readonly direction!: string;

  active = 0;
  tasks: Task[] = [];
  publicScreenTaskId = '';
  timerTask: Task | null = null;

  get showTimerSettings(): boolean {
    return this.timerTask !== null;
  }

  set showTimerSettings(value: boolean) {
    if (!value) this.timerTask = null;
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

  get activeOnPublicScreen(): number {
    return this.tasks.findIndex((task) => task.id == this.publicScreenTaskId);
  }

  set activeOnPublicScreen(index: number) {
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
</script>

<style lang="scss" scoped>
.el-step::v-deep {
  .el-step__icon {
    background: var(--color-background-gray);
    cursor: grab;
  }
}

.el-slider::v-deep {
  .el-slider__button {
    background: url('~@/assets/icons/svg/public-screen.svg') white;
    border: unset;
    border-radius: unset;
    background-size: cover;
    width: calc(var(--el-slider-button-size) + 4px);
  }
}

.el-checkbox-button::v-deep {
  &:first-child .el-checkbox-button__inner,
  &:last-child .el-checkbox-button__inner {
    border-radius: 50%;
  }
  .el-checkbox-button__inner {
    border-radius: 0;
    margin-bottom: 1rem;
    padding: 0.3rem 0.5rem;
    font-size: var(--font-size-large);
  }
}
</style>
