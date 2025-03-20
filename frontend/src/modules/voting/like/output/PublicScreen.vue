<template>
  <div class="likePublicScreen">
    <div
      class="GalleryContainer"
      v-for="ideaGroup in groupedIdeas"
      :key="ideaGroup[0].participantId"
    >
      <Gallery
        class="gallery"
        :task-id="taskId"
        :ideas="ideaGroup"
        :time-modifier="1"
        :portrait="true"
        :type="''"
        :indicator-position="'none'"
      />
      <p class="like-count">
        <span>{{ getRatingSum(ideaGroup[0].id) || 0 }}</span>
        <font-awesome-icon class="like-heart" :icon="['fas', 'heart']" />
      </p>
      <font-awesome-icon
        class="participant-icon"
        :icon="ideaGroup[0].avatar[0].symbol"
        :style="{ color: ideaGroup[0].avatar[0].color }"
      ></font-awesome-icon>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Bar } from 'vue-chartjs';
import * as votingService from '@/services/voting-service';
import { VoteResult } from '@/types/api/Vote';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import * as themeColors from '@/utils/themeColors';
import Gallery from '@/modules/common/visualisation_master/organisms/gallery.vue';
import * as viewService from '@/services/view-service';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import * as taskService from '@/services/task-service';
import { Idea } from '@/types/api/Idea';
import {Task} from "@/types/api/Task";

@Options({
  components: {
    Gallery,
    Bar,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  votes: VoteResult[] = [];
  chartData: any = {
    labels: [],
    datasets: [],
  };
  task: Task | null = null;

  groupedIdeas: Idea[][] = [];
  includedGroups: string[] = [];

  inputCash!: cashService.SimplifiedCashEntry<Idea[]>;

  get contrastColor(): string {
    return themeColors.getContrastColor();
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    votingService.registerGetResult(
      this.taskId,
      this.updateVotes,
      this.authHeaderTyp
    );
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.MODERATOR,
      30
    );
    this.inputCash = viewService.registerGetInputIdeas(
      this.taskId,
      IdeaSortOrder.PARTICIPANT,
      null,
      this.updateInputIdeas,
      EndpointAuthorisationType.MODERATOR,
      30
    );
  }

  updateTask(task: Task): void {
    this.task = task;
    this.includedGroups = this.task.modules[0].parameter.includedGroups || [];
  }

  updateInputIdeas(ideas: Idea[]): void {
    const groupedIdeas = ideas.reduce((acc, idea) => {
      const { participantId } = idea;
      const key = participantId === null ? 'null' : participantId;
      if (!acc[key]) {
        acc[key] = [];
      }
      acc[key].push(idea);
      return acc;
    }, {});
    this.groupedIdeas = Object.values(groupedIdeas);
  }

  updateVotes(votes: VoteResult[]): void {
    this.votes = votes;
  }

  getRatingSum(id: string): string {
    const vote = this.votes.find((vote) => vote.idea.id === id);
    if (vote) {
      return String(vote.ratingSum);
    }
    return '';
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateVotes);
  }

  unmounted(): void {
    this.deregisterAll();
  }
}
</script>

<style lang="scss" scoped>


.likePublicScreen {
  width: 100%;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 3rem;
}

.GalleryContainer {
  position: relative;
  width: 20rem;
  height: 30rem;
}

.like-count {
  position: absolute;
  bottom: 1rem;
  right: 1rem;
  text-align: right;
  font-size: var(--font-size-xlarge);
  color: var(--color-evaluating);

  display: flex;
  align-items: center;
  gap: 0.3rem;
  span {
    font-size: var(--font-size-large);
    font-weight: var(--font-weight-semibold);
  }
}

.participant-icon {
  position: absolute;
  bottom: 1rem;
  left: 1rem;
  text-align: left;
  font-size: var(--font-size-xlarge);
}
</style>
