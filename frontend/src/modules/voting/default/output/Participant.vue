<template>
  <ParticipantModuleDefaultContainer :task-id="taskId" :module="moduleName">
    <div class="media" v-if="ideaPointer < ideas.length">
      <IdeaCard
        class="media-left"
        :idea="ideas[ideaPointer]"
        :is-selectable="false"
        :is-editable="false"
      />
      <el-rate
        class="media-content"
        v-model:model-value="rate"
        :max="maxRate"
        v-on:change="saveVoting($event)"
      ></el-rate>
    </div>
    <div v-if="finished">
      {{ $t('module.voting.default.participant.thanks') }}
    </div>
    <div v-if="waiting">
      {{ $t('module.voting.default.participant.waiting') }}
    </div>
    <br />
    <el-divider v-if="validVotes.length > 0"></el-divider>
    <div
      class="media"
      v-for="vote in validVotes.sort((a, b) =>
        b.timestamp.localeCompare(a.timestamp)
      )"
      :key="vote.id"
    >
      <IdeaCard
        v-if="voteIdea(vote.ideaId)"
        class="media-left"
        :idea="voteIdea(vote.ideaId)"
        :is-selectable="false"
        :is-editable="false"
        :cutLongTexts="true"
      />
      <el-rate
        class="media-content"
        v-model:model-value="vote.rating"
        :max="maxRate"
        v-on:change="saveVoting($event, vote)"
      ></el-rate>
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
  allIdeas: Idea[] = [];
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
    return this.validVotes.length > 0 && this.ideaPointer >= this.ideas.length;
  }

  get waiting(): boolean {
    return this.allIdeas.length === 0;
  }

  get validVotes(): Vote[] {
    return this.votes.filter((vote) =>
      this.allIdeas.find((idea) => idea.id === vote.ideaId)
    );
  }

  mounted(): void {
    this.initConfig(5);
    this.startInterval();
  }

  startInterval(): void {
    this.interval = setInterval(this.reloadIdeas, this.intervalTime);
  }

  voteIdea(ideaId: string | null): Idea | undefined {
    return this.allIdeas.find((idea) => idea.id === ideaId);
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
      if (this.task && this.task.parameter.input) {
        await viewService
          .getIdeas(
            this.task.parameter.input,
            null,
            null,
            EndpointAuthorisationType.PARTICIPANT
          )
          .then((ideas) => {
            this.allIdeas = [...ideas];
            this.ideaPointer = ideas.length;
            this.ideas = ideas;
            this.getVotes();
          });
      }
    }
  }

  scrollToTop(delay = 100): void {
    setTimeout(() => {
      window.scroll(0, 0);
    }, delay);
  }

  async saveVoting(rate: number, vote: Vote | null = null): Promise<void> {
    setTimeout(() => {
      if (!vote) {
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
          this.rate = 0;
          this.ideaPointer++;
          this.scrollToTop(0);
        }
      } else {
        vote.rating = rate;
        vote.detailRating = rate;
        votingService.putVote(vote.id, vote);
      }
    }, 1000);
  }
}
</script>

<style lang="scss" scoped>
.media-left {
  width: 50%;
}

.media-content {
  align-self: center;
  text-align: center;
}

.el-divider {
  background-color: var(--color-primary);
  height: 2px;
}

.el-rate {
  --el-rate-font-size: 2rem;
  --el-rate-icon-size: 2rem;
  height: unset;
}

@media only screen and (max-width: 400px) {
  .media {
    display: block;
  }
  .media-left {
    width: unset;
  }
}
</style>
