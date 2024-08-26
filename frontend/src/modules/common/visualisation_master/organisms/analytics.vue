<template>
  <div id="analyticsContainer">
    <el-button>test update</el-button>
    <div v-if="steps.length > 0" class="allAnimationContainer">
      <div
        v-for="stepCategory of steps"
        :key="stepCategory.module"
        class="animationContainer"
        :ref="stepCategory.module"
      >
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import {
  defaultFilterData,
  FilterData,
} from '@/components/moderator/molecules/IdeaFilterBase.vue';
import { Task } from '@/types/api/Task';
import { hasModule } from '@/modules';
import ModuleComponentType from '@/modules/ModuleComponentType';
import TaskType from '@/types/enum/TaskType';
import * as taskService from '@/services/task-service';
import * as taskParticipantService from '@/services/task-participant-service';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import { getRandomColorList } from '@/utils/colors';

@Options({
  components: {
    IdeaCard,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly task!: Task;
  @Prop({ default: 0 }) readonly timeModifier!: number;
  @Prop({ default: null }) readonly taskParameter!: any;
  @Prop({ default: [] }) readonly ideas!: Idea[];
  @Prop({ default: false }) readonly paused!: boolean;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  filter: FilterData = { ...defaultFilterData };

  tasks: Task[] = [];
  gameTasks: Task[] = [];

  animationTimeInSeconds = 100;

  steps: {
    module: string;
    steps: TaskParticipantIterationStep[];
    newSteps: TaskParticipantIterationStep[];
  }[] = [];

  get topicId(): string | null {
    if (this.task) return this.task.topicId;
    return null;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.topicId) {
      taskService.registerGetTaskList(
        this.topicId,
        this.updateTasks,
        this.authHeaderTyp,
        30 * 60
      );
    }
  }

  @Watch('gameTasks', { immediate: true })
  onGameTasksChanged(): void {
    for (const task of this.gameTasks) {
      taskParticipantService.registerGetIterationStepList(
        task.id,
        (steps: TaskParticipantIterationStep[]) => {
          this.updateIterationSteps(task.modules[0].name, steps);
        },
        EndpointAuthorisationType.MODERATOR,
        15
      );
    }
  }

  updateTasks(tasks: Task[], topicId: string): void {
    this.tasks = tasks;
    this.gameTasks = this.tasks
      .filter((task) => task.taskType === 'PLAYING')
      .sort();
  }

  handleNewEntries(taskId: string, steps: TaskParticipantIterationStep[]) {
    const refArray = this.$refs[taskId];

    // Ensure the refArray is not undefined and is an array
    if (Array.isArray(refArray) && refArray.length > 0) {
      const element = refArray[0] as HTMLElement; // Assuming you want to target the first element

      steps.forEach((step, index) => {
        const divElement = document.createElement('div');
        divElement.setAttribute('key', step.id + Date.now());

        // Inside the div
        const pElement = document.createElement('p');
        pElement.textContent = taskId + index;

        divElement.appendChild(pElement);
        element.appendChild(divElement);

        divElement.style.animationDuration = this.animationTimeInSeconds + 's';
        divElement.classList.add('animateMoveLeftRight');

        setTimeout(() => {
          element.removeChild(divElement);
        }, this.animationTimeInSeconds * 1000);
      });
    }
  }

  updateIterationSteps(
    taskId: string,
    steps: TaskParticipantIterationStep[]
  ): void {
    let newEntries: TaskParticipantIterationStep[] = [];
    if (this.steps.find((entry) => entry.module === taskId)) {
      newEntries = this.findNewIterations(
        this.steps.find((entry) => entry.module === taskId)?.steps,
        steps
      );
      if (newEntries.length > 0) {
        this.handleNewEntries(taskId, newEntries);
      }
    }

    const stepsEntry = { module: taskId, steps: steps, newSteps: newEntries };
    if (
      this.steps.findIndex((entry) => entry.module === stepsEntry.module) !=
        -1 &&
      stepsEntry.newSteps.length > 0
    ) {
      this.steps[
        this.steps.findIndex((entry) => entry.module === stepsEntry.module)
      ] = stepsEntry;
    } else if (
      this.steps.findIndex((entry) => entry.module === stepsEntry.module) === -1
    ) {
      this.steps.push(stepsEntry);
    }
  }

  findNewIterations(steps1: any, steps2: any) {
    const countMap: { [key: string]: number } = {};

    steps1 = steps1.filter((step) => step.parameter.finished);
    steps2 = steps2.filter((step) => step.parameter.finished);

    for (const step of steps1) {
      countMap[step.id] = (countMap[step.id] || 0) + 1;
    }

    for (const step of steps2) {
      countMap[step.id] = (countMap[step.id] || 0) - 1;
    }

    const extraElements: any = [];
    if (steps1.length === steps2.length) {
      for (let i = 0; i < steps1.length; i++) {
        if (!this.deepEqual(steps1[i], steps2[i])) {
          if (steps2[i].parameter.finished) {
            extraElements.push(steps2[i]);
          }
        }
      }
    } else {
      for (const step of steps2) {
        if (countMap[step.id] < 0) {
          if (step.parameter.finished) {
            extraElements.push(step);
          }
          countMap[step.id] = 0;
        }
      }
    }
    return extraElements;
  }

  deepEqual(obj1: any, obj2: any): boolean {
    if (obj1 === obj2) {
      return true;
    }

    if (
      typeof obj1 !== 'object' ||
      obj1 === null ||
      typeof obj2 !== 'object' ||
      obj2 === null
    ) {
      return false;
    }

    const keys1 = Object.keys(obj1);
    const keys2 = Object.keys(obj2);

    if (keys1.length !== keys2.length) {
      return false;
    }

    for (const key of keys1) {
      if (!keys2.includes(key)) {
        return false;
      }
      if (!this.deepEqual(obj1[key], obj2[key])) {
        return false;
      }
    }

    return true;
  }
}
</script>

<style lang="scss" scoped>
.analyticsContainer {
  width: 100%;
  height: 100%;
  position: relative;
}

.animationContainer {
  border: 1px solid red;
  width: 100%;
  height: 10vh;
}
</style>

<style lang="scss">

.animateMoveLeftRight {
  animation: moveLeftRight 100s forwards ease !important;
  background-color: red !important;
}

@keyframes moveLeftRight {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(100%);
  }
}

</style>
