<template>
  <div>
    <div class="link new" @click="levelSelected(null)">
      {{ $t('module.information.findit.participant.newLevel') }}
    </div>
    <div
      class="link media"
      :class="{ own: isOwnLevel(idea) }"
      v-for="idea of ideas"
      :key="idea.id"
      @click="levelSelected(idea)"
    >
      <div class="media-content">{{ idea.keywords }}</div>
      <div class="media-right">
        <el-rate
          v-model="mapping[idea.id]"
          size="large"
          :max="3"
          :disabled="true"
        />
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import * as placeable from '@/modules/information/findit/types/Placeable';
import * as cashService from '@/services/cash-service';
import * as authService from '@/services/auth-service';
import * as ideaService from '@/services/idea-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as taskParticipantService from '@/services/task-participant-service';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import { GameStep } from '@/modules/information/findit/output/Participant.vue';

@Options({
  components: {},
  emits: ['selectionDone'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class SelectState extends Vue {
  @Prop() readonly taskId!: string;
  ideas: Idea[] = [];
  result: TaskParticipantIterationStep[] = [];
  mapping: { [key: string]: number } = {};

  unmounted(): void {
    cashService.deregisterAllGet(this.updateIdeas);
    cashService.deregisterAllGet(this.updateIterationSteps);
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

  isOwnLevel(level: Idea): boolean {
    return level.participantId === authService.getParticipantId();
  }

  levelSelected(level: Idea | null) {
    if (!level) {
      this.$emit('selectionDone', [], null);
    } else {
      this.$emit(
        'selectionDone',
        level.parameter as placeable.PlaceableBase[],
        level.id
      );
    }
  }
}
</script>

<style lang="scss" scoped>
.link {
  background-color: var(--color-primary);
  color: white;
  border-radius: var(--border-radius);
  margin: 1rem;
  padding: 1rem;
}

.new {
  background-color: var(--color-dark-contrast-light);
}

.own {
  background-color: var(--color-informing);
}

.el-rate {
  --el-rate-fill-color: white;
  --el-rate-disabled-void-color: var(--color-gray-inactive);
}
</style>
