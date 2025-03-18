<template>
  <div class="moderatorContent">
    <el-checkbox v-model="allSelected" @change="onAllSelectedChanged"
      >Include all in voting</el-checkbox
    >
    <el-checkbox-group v-model="includedGroups">
      <el-checkbox
        v-for="ideaGroup in groupedIdeas"
        :label="ideaGroup[0].participantId"
        :key="ideaGroup[0].participantId"
        class="GalleryCheckbox"
      >
        <div class="GalleryContainer">
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
            {{ getRatingSum(ideaGroup[0].id) }}
            <font-awesome-icon class="like-heart" :icon="['fas', 'heart']" />
          </p>
          <font-awesome-icon
            class="participant-icon"
            :icon="ideaGroup[0].avatar[0].symbol"
            :style="{ color: ideaGroup[0].avatar[0].color }"
          ></font-awesome-icon>
        </div>
      </el-checkbox>
    </el-checkbox-group>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Bar } from 'vue-chartjs';
import * as taskService from '@/services/task-service';
import { VoteResult } from '@/types/api/Vote';
import { EventType } from '@/types/enum/EventType';
import { IModeratorContent } from '@/types/ui/IModeratorContent';
import * as cashService from '@/services/cash-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as themeColors from '@/utils/themeColors';
import * as viewService from '@/services/view-service';
import * as moduleService from '@/services/module-service';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import { Idea } from '@/types/api/Idea';
import { Task } from '@/types/api/Task';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import Gallery from '@/modules/common/visualisation_master/organisms/gallery.vue';
import * as votingService from '@/services/voting-service';

interface IdeaGroup {
  groupId: string | null;
  ideaIds: string[];
}

@Options({
  components: {
    Gallery,
    IdeaCard,
    Bar,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue implements IModeratorContent {
  @Prop() readonly taskId!: string;
  votes: VoteResult[] = [];
  chartData: any = {
    labels: [],
    datasets: [],
  };
  task: Task | null = null;
  groupedIdeas: Idea[][] = [];

  includedGroups: (string | null)[] = [];

  inputCash!: cashService.SimplifiedCashEntry<Idea[]>;

  allSelected = true;

  get contrastColor(): string {
    return themeColors.getContrastColor();
  }

  voteCashEntry!: cashService.SimplifiedCashEntry<VoteResult[]>;
  @Watch('taskId', { immediate: true })
  reloadTaskSettings(): void {
    this.deregisterAll();
    this.voteCashEntry = votingService.registerGetResult(
      this.taskId,
      this.updateVotes,
      EndpointAuthorisationType.MODERATOR,
      20
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

  updateVotes(votes: VoteResult[]): void {
    this.votes = votes;
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

  @Watch('includedGroups', { immediate: true })
  onIncludedGroupsChanged(): void {
    this.updateModule();
    this.allSelected = this.includedGroups.length === this.groupedIdeas.length;
  }

  @Watch('groupedIdeas', { immediate: true })
  onGroupedIdeasChanged(): void {
    this.updateModule();
    this.allSelected = this.includedGroups.length === this.groupedIdeas.length;
  }

  onAllSelectedChanged(): void {
    if (!this.allSelected) {
      this.includedGroups = [];
    } else {
      this.includedGroups = this.groupedIdeas.map(
        (group) => group[0].participantId
      );
    }
  }

  updateModule(): void {
    if (this.task) {
      const ideaGroups: IdeaGroup[] = [];
      for (const groupId of this.includedGroups) {
        const groupOfId = this.groupedIdeas.find(
          (group) => groupId === group[0].participantId
        );
        if (groupOfId) {
          ideaGroups.push({
            groupId: groupId,
            ideaIds: groupOfId.map((idea) => idea.id),
          });
        }
      }
      this.task.modules[0].parameter.ideaGroups = ideaGroups;
      this.task.modules[0].parameter.includedGroups = this.includedGroups;
      moduleService.putModule(this.task.modules[0]);
    }
  }

  async mounted(): Promise<void> {
    this.eventBus.off(EventType.CHANGE_SETTINGS);
    this.eventBus.on(EventType.CHANGE_SETTINGS, async (taskId) => {
      if (this.taskId === taskId) {
        await this.voteCashEntry.refreshData();
      }
    });
  }

  getRatingSum(id: string): string {
    const vote = this.votes.find((vote) => vote.idea.id === id);
    if (vote) {
      return String(vote.ratingSum);
    }
    return '';
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateInputIdeas);
    cashService.deregisterAllGet(this.updateVotes);
    cashService.deregisterAllGet(this.updateTask);
  }

  unmounted(): void {
    this.deregisterAll();
  }
}
</script>

<style lang="scss" scoped>
.moderatorContent {
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  gap: 3rem;

  width: 100%;
}

.el-checkbox-group {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 2rem;
}

.el-checkbox.GalleryCheckbox {
  height: 30rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  align-items: center;
  justify-content: center;
}

.el-checkbox.GalleryCheckbox::v-deep(.el-checkbox__label) {
  display: block;
  font-size: var(--font-size-default);
  height: 100%;
  width: 20rem;
  padding: 0;
}

.gallery {
  width: 20rem;
  height: 100%;
}

.GalleryContainer {
  position: relative;
  width: 20rem;
  height: 100%;
}

.like-count {
  position: absolute;
  bottom: 1rem;
  right: 1rem;
  text-align: right;
  font-size: var(--font-size-xlarge);
}

.participant-icon {
  position: absolute;
  bottom: 1rem;
  left: 1rem;
  text-align: left;
  font-size: var(--font-size-xlarge);
}
</style>
