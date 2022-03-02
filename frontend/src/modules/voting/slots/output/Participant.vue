<template>
  <ParticipantModuleDefaultContainer :task-id="taskId" :module="moduleName">
    <div v-if="ideaPointer < ideas.length">
      {{ $t('module.voting.slots.participant.info') }}
      <el-button
        v-for="(idea, index) in seats"
        :key="index"
        class="fullwidth outline-dashed"
        @click.prevent="vote(index + 1)"
      >
        <span v-if="idea">
          {{ $t('module.voting.slots.participant.replace') }} "{{
            idea.keywords
          }}"
        </span>
        <span v-if="!idea">
          <font-awesome-icon icon="angle-double-left" />
          {{ $t('module.voting.slots.participant.emptySlot') }}
          <font-awesome-icon icon="angle-double-right" />
        </span>
      </el-button>
      <span v-if="ideaPointer < ideas.length">
        <IdeaCard
          :idea="ideas[ideaPointer]"
          :is-selectable="false"
          :is-editable="false"
        />
      </span>
      <el-button
        type="warning"
        nativeType="submit"
        class="fullwidth"
        @click.prevent="vote(0)"
      >
        {{ $t('module.voting.slots.participant.skip') }}
      </el-button>
    </div>
    <div v-if="finished">
      {{ $t('module.voting.slots.participant.thanks') }}
    </div>
    <div v-if="waiting">
      {{ $t('module.voting.slots.participant.waiting') }}
    </div>
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/layout/ParticipantModuleDefaultContainer.vue';
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';
import * as moduleService from '@/services/module-service';
import * as viewService from '@/services/view-service';
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
  seats: (Idea | null)[] = [];
  ideaPointer = 0;
  readonly intervalTime = 10000;
  interval!: any;

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  get finished(): boolean {
    return this.votes.length > 0 && this.ideaPointer >= this.ideas.length;
  }

  get waiting(): boolean {
    return this.ideas.length === 0 && this.votes.length === 0;
  }

  mounted(): void {
    if (this.seats.length == 0) {
      this.initSeats(3);
    }
    this.startInterval();
  }

  startInterval(): void {
    this.interval = setInterval(this.reloadIdeas, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }

  reloadIdeas(): void {
    if (this.finished) this.getIdeas();
  }

  initSeats(count: number): void {
    this.seats = [];
    for (let i = 0; i < count; i++) {
      this.seats.push(null);
    }
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
          if (this.seats.length != this.module.parameter.slotCount)
            this.getVotes();
        });
    }
  }

  async getVotes(): Promise<void> {
    if (this.taskId) {
      if (this.module) this.initSeats(this.module.parameter.slotCount);
      else this.initSeats(3);
      await votingService
        .getVotes(this.taskId, EndpointAuthorisationType.PARTICIPANT)
        .then((votes) => {
          this.votes = votes;
          votes.forEach((vote) => {
            if (vote.rating > 0) {
              this.seats[vote.rating - 1] = this.ideas.filter(
                (idea) => idea.id == vote.ideaId
              )[0];
            }
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
      if (this.task && this.task.parameter.input) {
        await viewService
          .getIdeas(
            this.task.parameter.input,
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

  async vote(slot: number): Promise<void> {
    if (this.ideaPointer < this.ideas.length) {
      const idea = this.ideas[this.ideaPointer];
      votingService
        .postVote(this.taskId, {
          ideaId: idea.id,
          rating: slot,
          detailRating: slot > 0 ? 1 : 0,
        })
        .then((vote) => {
          this.votes.push(vote);
        });
      if (slot > 0) {
        if (this.seats[slot - 1]) {
          const slotIdea = this.seats[slot - 1];
          if (slotIdea) {
            const slotVote = this.votes.filter(
              (vote) => vote.ideaId == slotIdea?.id
            )[0];
            await votingService.putVote(slotVote.id, {
              ideaId: slotIdea.id,
              rating: 0,
              detailRating: 0,
            });
          }
        }
        this.seats[slot - 1] = idea;
      }
      this.ideaPointer++;
    }
  }
}
</script>

<style scoped></style>
