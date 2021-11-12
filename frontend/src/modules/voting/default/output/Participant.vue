<template>
  <ParticipantModuleDefaultContainer :task-id="taskId" :module="moduleName">
    <template v-slot:planet>
      <img
        src="@/assets/illustrations/planets/voting.png"
        alt="planet"
        class="module-container__planet"
      />
    </template>
    <div v-if="ideaPointer < ideas.length">
      <span v-if="ideaPointer < ideas.length">
        <IdeaCard
          :idea="ideas[ideaPointer]"
          :is-selectable="false"
          :is-editable="false"
        />
      </span>
      <el-rate
        v-model:model-value="rate"
        :max="maxRate"
        v-on:change="vote($event)"
      ></el-rate>
    </div>
    <div v-if="finished">
      {{ $t('module.voting.default.participant.thanks') }}
    </div>
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/ParticipantModuleDefaultContainer.vue';
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';
import * as moduleService from '@/services/module-service';
import * as ideaService from '@/services/idea-service';
import * as votingService from '@/services/voting-service';
import { Idea } from '@/types/api/Idea';
import { Vote } from '@/types/api/Vote';
import { Module } from '@/types/api/Module';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';

@Options({
  components: {
    ParticipantModuleDefaultContainer,
    IdeaCard,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  task: Task | null = null;
  module: Module | null = null;
  ideas: Idea[] = [];
  votes: Vote[] = [];
  ideaPointer = 0;
  rate = 0;
  maxRate = 5;
  readonly intervalTime = 10000;
  interval!: any;

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  get finished(): boolean {
    return (
      this.votes.length > 0 &&
      this.ideaPointer >= this.ideas.length &&
      this.ideaPointer > 0
    );
  }

  mounted(): void {
    this.initConfig(5);
    this.startIdeaInterval();
  }

  startIdeaInterval(): void {
    this.interval = setInterval(this.reloadIdeas, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }

  reloadIdeas(): void {
    if (this.finished) this.getIdeas();
  }

  initConfig(count: number): void {
    this.maxRate = count;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getIdeas();
  }

  async getTask(): Promise<void> {
    if (this.taskId) {
      await taskService
        .getTaskById(this.taskId, EndpointAuthorisationType.PARTICIPANT)
        .then((task) => {
          this.task = task;
        });
    }
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
          if (this.maxRate != parseInt(this.module.parameter.maxRate))
            this.getVotes();
        });
    }
  }

  async getVotes(): Promise<void> {
    if (this.taskId) {
      if (this.module) this.initConfig(parseInt(this.module.parameter.maxRate));
      else this.initConfig(5);
      await votingService
        .getVotes(this.taskId, EndpointAuthorisationType.PARTICIPANT)
        .then((votes) => {
          this.votes = votes;
          votes.forEach((vote) => {
            const ideaIndex = this.ideas.findIndex(
              (idea) => idea.id == vote.ideaId
            );
            if (ideaIndex >= 0) this.ideas.splice(ideaIndex, 1);
          });
          this.ideaPointer = 0;
        });
    }
  }

  async getIdeas(): Promise<void> {
    if (this.taskId) {
      if (!this.task) await this.getTask();
      if (!this.module) await this.getModule();
      if (this.task && this.task.parameter.dependencyTaskId) {
        await ideaService
          .getIdeasForTask(
            this.task.parameter.dependencyTaskId,
            null,
            null,
            EndpointAuthorisationType.PARTICIPANT
          )
          .then((ideas) => {
            this.ideaPointer = ideas.length;
            this.ideas = ideas;
            this.getVotes();
          });
      }
    }
  }

  async vote(rate: number): Promise<void> {
    if (this.ideaPointer < this.ideas.length) {
      const idea = this.ideas[this.ideaPointer];
      votingService
        .postVote(this.taskId, {
          ideaId: idea.id,
          rating: rate,
          detailRating: rate,
        })
        .then((vote) => {
          this.votes.push(vote);
        });
      setTimeout(() => {
        this.rate = 0;
        this.ideaPointer++;
      }, 2000);
    }
  }
}
</script>

<style scoped></style>
