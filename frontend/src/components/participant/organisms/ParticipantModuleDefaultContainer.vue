<template>
  <div class="module-container container2--fullheight container2">
    <div class="grid-container">
      <div class="grid-item">
        <div class="module-container__planetDiv">
          <slot name="planet" />
        </div>
      </div>
      <div class="grid-item module-container--center">
        <div class="module-container--uppercase">
          {{ $t('participant.organism.modelDefaultContainer.timeLeft') }}
        </div>
        <Timer
          class="module-container__timer"
          :isActive="true"
          :task="task"
          v-on:timerEnds="$router.go(-1)"
        ></Timer>
      </div>
    </div>
    <HalfCard :type="taskType">
      <TaskInfo
        :type="taskType"
        :title="taskName"
        :description="taskDescription"
        :is-participant="true"
      />
      <slot />
    </HalfCard>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import MenuBar from '@/components/participant/molecules/Menubar.vue';
import Timer from '@/components/shared/atoms/Timer.vue';
import HalfCard from '@/components/shared/atoms/HalfCard.vue';
import TaskInfo from '@/components/shared/molecules/TaskInfo.vue';
import TaskType from '@/types/enum/TaskType';
import { Prop, Watch } from 'vue-property-decorator';
import { setModuleStyles } from '@/utils/moduleStyles';
import { maxLength, required } from '@vuelidate/validators';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

@Options({
  components: {
    MenuBar,
    Timer,
    HalfCard,
    TaskInfo,
  },
  validations: {
    keywords: {
      max: maxLength(36),
    },
    description: {
      required,
      max: maxLength(255),
    },
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

.module-container {
  color: #fff;
  background: var(--color-darkblue);
  background-image: url('~@/assets/illustrations/background.png');
  background-attachment: fixed;
  background-repeat: no-repeat;
  background-size: cover;

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

  &__planet {
    position: absolute;
    left: 1rem;
    width: 11rem;
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
  &--bottom {
    margin-top: 0.75rem;
  }
  .fade-enter-active,
  .fade-leave-active {
    transition: opacity 0.3s;
  }
  .fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */ {
    opacity: 0;
  }
}

.container2 {
  padding-bottom: 0;
  max-height: 100vh;
  position: relative;
}

.grid-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  margin-top: 4rem;
}

.half-card {
  max-height: 100%;
  overflow: auto;
}
</style>
