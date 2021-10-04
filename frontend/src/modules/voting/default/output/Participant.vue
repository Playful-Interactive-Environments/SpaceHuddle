<template>
  <ParticipantModuleDefaultContainer :task-id="taskId">
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
    <div v-if="ideaPointer >= ideas.length">
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
import * as selectService from '@/services/selection-service';
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

  mounted(): void {
    this.initConfig(5);
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
        });
    }
  }

  async getIdeas(): Promise<void> {
    if (this.taskId) {
      if (!this.task) await this.getTask();
      if (!this.module) await this.getModule();
      if (this.task) {
        await selectService
          .getIdeasForSelection(
            this.task?.parameter.selectionId,
            EndpointAuthorisationType.PARTICIPANT
          )
          .then((ideas) => {
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
      }, 200);
    }
  }
}
</script>

<style scoped></style>
