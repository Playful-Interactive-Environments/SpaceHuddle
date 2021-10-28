<template>
  <ParticipantModuleDefaultContainer :task-id="taskId" :module="moduleName">
    <template v-slot:planet>
      <img
        src="@/assets/illustrations/planets/brainstorming.png"
        alt="planet"
        class="module-container__planet"
      />
    </template>
    <div class="centered flex-column fill">
      <span v-if="randomIdea">{{ randomIdea.keywords }}</span>
      <br />
      <span v-if="randomIdea">{{ randomIdea.description }}</span>
    </div>
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/ParticipantModuleDefaultContainer.vue';
import * as ideaService from '@/services/idea-service';
import * as moduleService from '@/services/module-service';
import { Idea } from '@/types/api/Idea.ts';
import { Module } from '@/types/api/Module';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

@Options({
  components: {
    ParticipantModuleDefaultContainer,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  module: Module | null = null;
  readonly intervalTime = 10000;
  interval!: any;

  randomIdea: Idea | null = null;

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  async mounted(): Promise<void> {
    await this.getTaskIdeas();
    this.startIdeaInterval();
  }

  @Watch('moduleId', { immediate: true })
  onModuleIdChanged(): void {
    this.getModule();
  }

  async getModule(): Promise<void> {
    if (this.moduleId) {
      await moduleService
        .getModuleById(this.moduleId, EndpointAuthorisationType.PARTICIPANT)
        .then((module) => {
          this.module = module;
        });
    }
  }

  startIdeaInterval(): void {
    this.interval = setInterval(this.getTaskIdeas, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }

  async getTaskIdeas(): Promise<void> {
    ideaService
      .getIdeasForTask(
        this.taskId,
        null,
        null,
        EndpointAuthorisationType.PARTICIPANT
      )
      .then((queryResult) => {
        const randomIndex = Math.floor(Math.random() * queryResult.length);
        this.randomIdea = queryResult[randomIndex];
      });
  }
}
</script>

<style lang="scss" scoped></style>
