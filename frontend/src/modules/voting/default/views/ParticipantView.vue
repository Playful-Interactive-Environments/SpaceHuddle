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
      <button
        v-for="(idea, index) in seats"
        :key="index"
        type="submit"
        class="btn btn--gradient btn--fullwidth"
        @click.prevent="vote(index + 1)"
      >
        <span v-if="idea">
          {{ idea.keywords }}
        </span>
        <span v-if="!idea">
          <font-awesome-icon icon="angle-double-left" />
          {{ $t('module.voting.default.participant.emptySlot') }}
          <font-awesome-icon icon="angle-double-right" />
        </span>
      </button>
      <br />
      <br />
      <span v-if="ideaPointer < ideas.length">
        {{ ideas[ideaPointer].keywords }}
      </span>
      <br />
      <br />
      <button
        type="submit"
        class="btn btn--gradient btn--fullwidth"
        @click.prevent="vote(0)"
      >
        {{ $t('module.voting.default.participant.skip') }}
      </button>
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
import * as selectService from '@/services/selection-service';
import * as votingService from '@/services/voting-service';
import { Idea } from '@/types/api/Idea';
import { Vote } from '@/types/api/Vote';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

@Options({
  components: {
    ParticipantModuleDefaultContainer,
  },
})
export default class ParticipantView extends Vue {
  @Prop() readonly taskId!: string;
  task: Task | null = null;
  ideas: Idea[] = [];
  votes: Vote[] = [];
  seats: (Idea | null)[] = [null, null, null];
  ideaPointer = 0;

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getIdeas();
  }

  async getTask(): Promise<void> {
    if (this.taskId) {
      await taskService.getTaskById(this.taskId).then((task) => {
        this.task = task;
      });
    }
  }

  async getVotes(): Promise<void> {
    if (this.taskId) {
      this.seats = [null, null, null];
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
        });
    }
  }

  async getIdeas(): Promise<void> {
    if (this.taskId) {
      if (!this.task) await this.getTask();
      if (this.task) {
        await selectService
          .getIdeasForSelection(this.task?.parameter.selectionId)
          .then((ideas) => {
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
