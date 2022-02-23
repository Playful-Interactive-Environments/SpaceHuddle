<template>
  <div
    :class="{ 'module-info--centered': isParticipant }"
    class="module-info"
    :style="{ '--module-color': getColor() }"
  >
    <el-breadcrumb separator="|" class="module-info__type" v-if="taskType">
      <!--<el-breadcrumb-item>
        {{ $t(`enum.taskType.${taskType}`) }}
      </el-breadcrumb-item>-->
      <el-breadcrumb-item v-for="module in moduleInfo" :key="module">
        {{ $t(`module.${taskType}.${module}.description.title`) }}
      </el-breadcrumb-item>
    </el-breadcrumb>
    <h3
      :class="{
        'heading--regular': isParticipant,
        shortDescription: shortenDescription,
      }"
      class="module-info__title"
    >
      {{ title }}
    </h3>
    <p
      class="module-info__description"
      :class="{ shortDescription: shortenDescription }"
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

@Options({
  components: {},
})
export default class TaskInfo extends Vue {
  @Prop() taskId!: string;
  @Prop({ default: false }) isParticipant!: boolean;
  @Prop({ default: true }) shortenDescription!: boolean;
  @Prop({ default: [] }) modules!: string[];
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;

  taskType: TaskType | null = null;
  title = '';
  description = '';
  mainModule = ['default'];

  @Watch('taskId', { immediate: true })
  async onTaskIdChanged(): Promise<void> {
    if (this.taskId) {
      taskService.getTaskById(this.taskId, this.authHeaderTyp).then((task) => {
        this.taskType = TaskType[task.taskType.toUpperCase()];
        this.title = task.name;
        this.description = task.description;
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
      });
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
}
</script>

<style lang="scss" scoped>
@import '~@/assets/styles/breakpoints.scss';

.module-info {
  flex-grow: 1;
  font-size: var(--font-size-small);

  @include md {
    //max-width: 60%;
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
    text-align: justify;
    white-space: pre-line;

    @include md {
      margin-top: 0;
      line-height: 1.3;
    }
  }

  &--centered {
    //text-align: center;
    max-width: 100%;
    font-size: var(--font-size-default);
    margin-bottom: 1em;
    line-height: 1.75em;

    span {
      line-height: 3em;
    }
  }
}

.shortDescription {
  display: -webkit-box;
  line-clamp: 2;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-align: left;
}

.el-breadcrumb::v-deep {
  .el-breadcrumb__inner,
  .el-breadcrumb__item:last-child .el-breadcrumb__inner,
  .el-breadcrumb__separator {
    color: unset;
  }

  .el-breadcrumb__item {
    float: unset;
  }
}
</style>
