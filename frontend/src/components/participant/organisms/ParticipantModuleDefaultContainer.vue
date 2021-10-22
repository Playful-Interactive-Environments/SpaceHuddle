<template>
  <el-container class="participant-container">
    <el-header class="grid-container">
      <div class="grid-item">
        <div class="participant-container__planetDiv">
          <slot name="planet" />
        </div>
      </div>
      <div class="grid-item participant-container--center">
        <div class="participant-container--uppercase">
          {{ $t('participant.organism.modelDefaultContainer.timeLeft') }}
        </div>
        <Timer
          class="participant-container__timer"
          :task="task"
          v-on:timerEnds="$router.go(-1)"
        ></Timer>
      </div>
    </el-header>
    <el-container class="half-card">
      <el-header>
        <TaskInfo
          :type="taskType"
          :title="taskName"
          :description="taskDescription"
          :is-participant="true"
        />
      </el-header>
      <el-main class="el-main__overflow">
        <slot />
      </el-main>
    </el-container>
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
  task: Task | null = null;

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
@keyframes planetAnimation {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
  100% {
    transform: scale(1);
  }
}

.participant-container {
  color: #fff;
  background: var(--color-darkblue);
  background-image: url('~@/assets/illustrations/background.png');
  background-attachment: fixed;
  background-repeat: no-repeat;
  background-size: cover;
  height: 100vh;

  &__planetDiv {
    position: relative;
    left: 1rem;
    width: 11rem;
    height: 11rem;
    transition: transform 0.2;

    &--animation {
      animation-name: planetAnimation;
      animation-duration: 0.6s;
    }
  }

  &__timer {
    text-transform: uppercase;
    color: white;
    font-size: 1.8rem;
    padding: 0;
    background-color: transparent;
  }

  &--uppercase {
    text-transform: uppercase;
    color: white;
    font-size: 0.75rem;
    text-align: center;
  }

  &--center {
    margin: auto;
  }
}

.half-card {
  flex-grow: 1;
  justify-content: space-between;
  //align-items: center;
  background-color: white;
  color: #1d2948;
  border-radius: 30px 30px 0 0;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
  margin: 0;
  padding: 1.5rem 2rem;
}

.grid-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  margin-top: 4rem;
  padding: 1rem 2rem 0.7rem 2rem;
}

.half-card {
  max-height: 100%;
  overflow: auto;
}
</style>
