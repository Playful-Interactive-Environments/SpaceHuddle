<template>
  <div
    :class="{ 'module-info--centered': isParticipant }"
    class="module-info"
    :style="{ '--module-color': getColor() }"
  >
    <span class="media">
      <span class="media-left" v-if="!!$slots.moduleInfoLeft">
        <slot name="moduleInfoLeft" />
      </span>
      <span class="media-content" v-if="showType">
        <el-breadcrumb
          separator=" | "
          class="module-info__type oneLineText"
          v-if="taskType"
        >
          <el-breadcrumb-item v-for="module in moduleInfo" :key="module">
            <span v-if="module">
              {{ $t(`module.${taskType}.${module}.description.title`) }}
            </span>
          </el-breadcrumb-item>
        </el-breadcrumb>
      </span>
      <span class="media-right" v-if="!!$slots.moduleInfoRight">
        <slot name="moduleInfoRight" />
      </span>
    </span>
    <el-collapse
      class="module-info__description"
      :class="{ twoLineText: shortenDescription }"
      v-if="description && collapseDescription"
    >
      <el-collapse-item>
        <template v-slot:title>
          <h3
            :class="{
              'heading--regular': isParticipant,
              twoLineText: shortenDescription,
            }"
            class="module-info__title"
          >
            {{ title }}
          </h3>
        </template>
        <div class="task-info">
          {{ description }}
        </div>
      </el-collapse-item>
    </el-collapse>
    <h3
      v-else
      :class="{
        'heading--regular': isParticipant,
        twoLineText: shortenDescription,
      }"
      class="module-info__title"
    >
      {{ title }}
    </h3>
    <p
      class="module-info__description"
      :class="{ twoLineText: shortenDescription }"
      v-if="description && !collapseDescription"
    >
      {{ description }}
    </p>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import TaskType from '@/types/enum/TaskType';
import { getColorOfType } from '@/types/enum/TaskCategory';
import * as taskService from '@/services/task-service';
import { ModuleType } from '@/types/enum/ModuleType';
import { getModulesForTaskType } from '@/modules';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Task } from '@/types/api/Task';
import * as cashService from '@/services/cash-service';

@Options({
  components: {},
})
export default class TaskInfo extends Vue {
  @Prop() taskId!: string;
  @Prop({ default: false }) isParticipant!: boolean;
  @Prop({ default: true }) shortenDescription!: boolean;
  @Prop({ default: true }) showType!: boolean;
  @Prop({ default: [] }) modules!: string[];
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  @Prop({ default: false }) collapseDescription!: boolean;

  taskType: TaskType | null = null;
  title = '';
  description = '';
  mainModule = ['default'];

  @Watch('taskId', { immediate: true })
  async onTaskIdChanged(): Promise<void> {
    this.deregisterAll();
    if (this.taskId) {
      taskService.registerGetTaskById(
        this.taskId,
        this.updateTask,
        this.authHeaderTyp,
        60 * 60
      );
    }
  }

  updateTask(task: Task): void {
    this.title = task.name;
    this.description = task.description;
    if (task.taskType) {
      this.taskType = TaskType[task.taskType.toUpperCase()];
      if (this.taskType && this.modules.length === 0) {
        getModulesForTaskType(
          [task.taskType.toUpperCase() as keyof typeof TaskType],
          ModuleType.MAIN
        ).then((mainList) => {
          this.mainModule = task.modules
            .filter((module) =>
              mainList.find((main) => main.moduleName === module.name)
            )
            .map((module) => module.name);
        });
      }
    }
  }

  getColor(): string | undefined {
    if (this.taskType) {
      return getColorOfType(this.taskType);
    }
  }

  get moduleInfo(): string[] {
    if (this.modules.length > 0) return this.modules;
    return this.mainModule;
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
  }

  unmounted(): void {
    this.deregisterAll();
  }
}
</script>

<style lang="scss" scoped>
.media-content {
  align-self: center;
}
.media-left {
  align-self: center;
  margin-right: 0.5rem;
}
.media-right {
  align-self: center;
  margin-left: 0.5rem;
}

@import '~@/assets/styles/breakpoints.scss';

.module-info {
  flex-grow: 1;
  font-size: var(--font-size-small);
  text-align: left;

  h3 {
    margin-top: 0.5rem;
    line-height: 1.2;
  }

  &__type {
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--module-color);
  }

  &__title {
    font-weight: var(--font-weight-bold);
    margin: 0.2rem 0 0.1rem;
  }

  &__description {
    margin-top: 0.5rem;
    line-height: 1.2;
    text-align: left;
    white-space: pre-line;

    @include md {
      margin-top: 0;
      line-height: 1.3;
    }
  }

  &--centered {
    max-width: 100%;
    font-size: var(--font-size-default);
    margin-bottom: 1em;
    line-height: 1.75em;

    span {
      line-height: 2em;
    }
  }
}

.el-breadcrumb::v-deep(.el-breadcrumb__inner),
.el-breadcrumb::v-deep(.el-breadcrumb__item):last-child .el-breadcrumb__inner,
.el-breadcrumb::v-deep(.el-breadcrumb__separator) {
  color: unset;
  margin: unset;
}

.el-breadcrumb::v-deep(.el-breadcrumb__item) {
  float: unset;
  display: inline;
}

.el-collapse {
  border-top: unset;
  border-bottom: unset;
  margin-bottom: 0;
}

.el-collapse::v-deep(.el-collapse-item__wrap) {
  border-bottom: unset;
}

.el-collapse::v-deep(.el-collapse-item__header) {
  border-bottom: unset;
  justify-content: flex-start;
  align-items: unset;
  gap: 0.5rem;
  height: unset;
}

.el-collapse::v-deep(.el-collapse-item__arrow) {
  margin: 0.5rem;
}
</style>
