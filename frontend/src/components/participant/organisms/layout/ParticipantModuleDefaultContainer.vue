<template>
  <el-container class="module-content">
    <el-header>
      <TaskInfo
        :taskId="taskId"
        :modules="[module]"
        :is-participant="true"
        :shortenDescription="false"
        :auth-header-typ="EndpointAuthorisationType.PARTICIPANT"
      />
    </el-header>
    <el-main class="el-main__overflow">
      <slot />
    </el-main>
    <el-affix v-if="!!$slots.footer" position="bottom" :offset="20">
      <slot name="footer"></slot>
    </el-affix>
  </el-container>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import Timer from '@/components/shared/atoms/Timer.vue';
import TaskInfo from '@/components/shared/molecules/TaskInfo.vue';
import TaskType from '@/types/enum/TaskType';
import { Prop, Watch } from 'vue-property-decorator';
import { setModuleStyles } from '@/utils/moduleStyles';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

@Options({
  components: {
    Timer,
    TaskInfo,
  },
})
export default class ParticipantModuleDefaultContainer extends Vue {
  @Prop({ required: true }) taskId!: string;
  @Prop({ default: 'default' }) module!: string;
  task: Task | null = null;

  EndpointAuthorisationType = EndpointAuthorisationType;

  goBack(): void {
    if (!this.isSyncedWithPublicScreen) this.$router.go(-1);
  }

  get isSyncedWithPublicScreen(): boolean {
    if (this.task && this.task.modules) {
      return !!this.task.modules.find((module) => module.syncPublicParticipant);
    }
    return true;
  }

  get taskType(): TaskType | null {
    if (this.task) return TaskType[this.task.taskType];
    return null;
  }

  get taskName(): string | null {
    if (this.task) return this.task.name;
    return null;
  }

  get taskDescription(): string | null {
    if (this.task) return this.task.description;
    return null;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(val: string): void {
    taskService
      .getTaskById(val, EndpointAuthorisationType.PARTICIPANT)
      .then((queryResult) => {
        this.task = queryResult;
        if (this.taskType) setModuleStyles(this.taskType);
      });
  }
}
</script>

<style lang="scss" scoped>
.right {
  position: absolute;
  top: 1rem;
  right: 2rem;
}

.module-content {
  background-color: white;
  color: #1d2948;
  flex-grow: 1;
  justify-content: space-between;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
  margin: 0;
  padding: 1rem 2rem;
}

.el-affix::v-deep {
  .el-affix--fixed {
    background-color: white;
    padding: 1rem 0;
    margin-bottom: -1.3rem;
  }

  .el-form {
    margin-bottom: 2.3rem;
  }

  .el-form-item--default {
    margin-bottom: 0;
  }

  .media {
    flex-direction: row;
    width: 100%;
  }
}
</style>
