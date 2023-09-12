<template>
  <IdeaMap
    :ideas="ideas"
    :parameter="module?.parameter"
    :canChangePosition="() => false"
    v-model:selected-idea="selectedIdea"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaMap from '@/components/shared/organisms/IdeaMap.vue';
import { Prop, Watch } from 'vue-property-decorator';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import * as ideaService from '@/services/idea-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as taskParticipantService from '@/services/task-participant-service';
import { Idea } from '@/types/api/Idea';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import { GameStep } from '@/modules/information/coolit/output/Participant.vue';
import { Module } from '@/types/api/Module';

@Options({
  components: {
    IdeaMap,
  },
  emits: [],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class SelectLevel extends Vue {
  @Prop() readonly trackingManager!: TrackingManager;
  @Prop() readonly taskId!: string;
  @Prop() readonly module!: Module;
  ideas: Idea[] = [];
  result: TaskParticipantIterationStep[] = [];
  mapping: { [key: string]: number } = {};
  selectedIdea: Idea | null = null;

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      ideaService.registerGetIdeasForTask(
        this.taskId,
        null,
        null,
        this.updateIdeas,
        EndpointAuthorisationType.PARTICIPANT,
        3
      );
      taskParticipantService.registerGetIterationStepList(
        this.taskId,
        this.updateIterationSteps,
        EndpointAuthorisationType.PARTICIPANT,
        2 * 60
      );
    }
  }

  updateIdeas(ideas: Idea[]): void {
    this.ideas = ideas;
    this.calculateResult();
  }

  updateIterationSteps(steps: TaskParticipantIterationStep[]): void {
    const ideaList = steps
      .map((item) => item.ideaId)
      .filter(
        (value, index, self) =>
          self.findIndex((item) => item === value) === index
      );
    this.result = [];
    for (const ideaId of ideaList) {
      const played = steps
        .filter(
          (item) =>
            item.ideaId === ideaId && item.parameter.step === GameStep.Play
        )
        .sort((a, b) => b.parameter.stars - a.parameter.stars);
      if (played.length > 0) this.result.push(played[0]);
    }
    this.calculateResult();
  }

  calculateResult(): void {
    if (this.ideas && this.result) {
      const mapping: { [key: string]: number } = {};
      for (const idea of this.ideas) {
        mapping[idea.id] = this.getStarsForIdea(idea.id);
      }
      this.mapping = mapping;
    }
  }

  getStarsForIdea(ideaId: string): number {
    if (this.result) {
      const resultItem = this.result.find(
        (item) => item && item.ideaId === ideaId
      );
      if (resultItem) return resultItem.parameter.stars;
    }
    return 0;
  }
}
</script>

<style scoped lang="scss"></style>
