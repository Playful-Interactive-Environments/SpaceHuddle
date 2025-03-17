<template>
  <div class="moderatorContent">
    <el-checkbox-group v-model="includedGroups">
      <el-checkbox
        v-for="(ideaGroup, index) in groupedIdeas"
        :label="index"
        :key="index"
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
            {{ votes.find((vote) => vote.idea.id === ideaGroup[0].id) || '' }}
            <font-awesome-icon class="" :icon="['fas', 'heart']" />
          </p>
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

  includedGroups = [];

  inputCash!: cashService.SimplifiedCashEntry<Idea[]>;

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
    console.log(votes);
  }

  updateTask(task: Task): void {
    this.task = task;
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
  }

  @Watch('groupedIdeas', { immediate: true })
  onGroupedIdeasChanged(): void {
    this.updateModule();
  }

  updateModule(): void {
    if (this.task) {
      const ideaGroups: string[][] = [];
      for (const index of this.includedGroups) {
        ideaGroups.push(this.groupedIdeas[index].map((idea) => idea.id));
      }
      this.task.modules[0].parameter.ideaGroups = ideaGroups;
      moduleService.putModule(this.task.modules[0]);
      console.log(this.task.modules[0]);
    }
  }

  async mounted(): Promise<void> {
    this.eventBus.off(EventType.CHANGE_SETTINGS);
    this.eventBus.on(EventType.CHANGE_SETTINGS, async (taskId) => {
      if (this.taskId === taskId) {
        this.voteCashEntry.refreshData();
      }
    });
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateInputIdeas);
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

  width: 100%;
}

.el-checkbox-group {
  display: flex;
  flex-direction: column;
}

.el-checkbox {
  width: 100%;
  height: 30rem;
  display: flex;
}

.el-checkbox::v-deep(.el-checkbox__label) {
  display: block;
  font-size: var(--font-size-default);
  height: 100%;
  width: 20rem;
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
</style>
