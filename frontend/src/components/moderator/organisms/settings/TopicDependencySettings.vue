<template>
  <el-dialog v-model="showDialog" :before-close="handleClose">
    <template #header>
      <span class="el-dialog__title">
        {{ $t('moderator.organism.settings.topicDependencySettings.header') }}
      </span>
    </template>
    <table>
      <draggable
        v-for="(row, index) in tableTasks"
        :key="index"
        v-model="tableTasks[index]"
        group="task"
        item-key="id"
        @change="dragDone($event, index)"
        tag="tr"
        class="dependency-row"
      >
        <td
          v-for="element in row"
          :id="element.id"
          :key="element.id"
          :rowspan="element.dependency.duration"
          :style="{ '--rowspan': element.dependency.duration }"
        >
          <el-card
            class="task-card"
            :style="{
              '--task-color': getColorOfType(element.taskType.toLowerCase()),
            }"
          >
            <el-slider
              vertical
              :max="-1"
              :min="-(element.dependency.duration + 1)"
              show-stops
              v-model="element.dependency.durationInvert"
              :format-tooltip="(value) => -value"
              :format-value-text="(value) => -value"
              @change="durationChanged(element, $event)"
            />
            <div class="task-card-content">
              <font-awesome-icon
                :icon="getIconOfType(element.taskType.toLowerCase())"
              />
              {{ element.name }}
            </div>
          </el-card>
        </td>
      </draggable>
    </table>
  </el-dialog>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as taskService from '@/services/task-service';
import * as cashService from '@/services/cash-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { VueDraggableNext } from 'vue-draggable-next';
import { convertToSaveVersion, Task } from '@/types/api/Task';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { getIconOfType, getColorOfType } from '@/types/enum/TaskCategory';

@Options({
  components: {
    FontAwesomeIcon,
    draggable: VueDraggableNext,
  },
  emits: [],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TopicDependencySettings extends Vue {
  @Prop({ default: false }) showModal!: boolean;
  @Prop({}) sessionId!: string;
  @Prop({}) topicId!: string;

  tasks: Task[] = [];
  tableTasks: Task[][] = [];
  getIconOfType = getIconOfType;
  getColorOfType = getColorOfType;

  showDialog = false;
  @Watch('showModal', { immediate: false, flush: 'post' })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showDialog = showModal;
  }

  handleClose(): void {
    this.$emit('update:showModal', false);
  }

  @Watch('topicId', { immediate: true })
  onTopicIdChanged(): void {
    taskService.registerGetTaskList(
      this.topicId,
      this.updateTasks,
      EndpointAuthorisationType.MODERATOR,
      60
    );
  }

  updateTasks(tasks: Task[]): void {
    this.tasks = tasks;
    this.updateTableTasks();
  }

  updateTableTasks(): void {
    let maxRows = 0;
    const tableTasks: Task[][] = [];
    for (const task of this.tasks) {
      (task.dependency as any).durationInvert = -task.dependency.duration;
      const taskEnd = task.dependency.start + task.dependency.duration;
      if (maxRows < taskEnd) maxRows = taskEnd;
    }
    for (let row = 0; row < maxRows; row++) {
      tableTasks[row] = this.tasks
        .filter((item) => item.dependency.start === row)
        .sort((a, b) => {
          if (a.dependency.start === b.dependency.start)
            return b.dependency.duration - a.dependency.duration;
          return b.dependency.start - a.dependency.start;
        });
    }
    tableTasks[maxRows] = [];
    this.tableTasks = tableTasks;
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTasks);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  dragDone(event: any, rowIndex: number): void {
    if (event.added) {
      event.added.element.dependency.start = rowIndex;
      taskService.putTask(convertToSaveVersion(event.added.element));
      this.updateTableTasks();
    }
  }

  durationChanged(task: Task): void {
    task.dependency.duration = -(task.dependency as any).durationInvert;
    taskService.putTask(convertToSaveVersion(task));
    this.updateTableTasks();
  }
}
</script>

<style lang="scss" scoped>
table {
  border-collapse: collapse;
  width: 100%;
}

.dependency-row {
  height: 5rem;
  border-bottom: dashed var(--color-dark-contrast) 1px;

  td {
    height: calc(5rem * var(--rowspan));
  }
}

.task-card {
  background-color: var(--task-color);
  height: 100%;
}

.el-card {
  position: relative;
}

.el-slider.is-vertical {
  top: 0;
  left: -0.5rem;
  position: absolute;
}

.task-card-content {
}
</style>
