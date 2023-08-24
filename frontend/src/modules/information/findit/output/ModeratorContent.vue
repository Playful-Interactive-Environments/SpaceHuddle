<template>
  <div>
    <BuildState
      v-if="selectedLevel"
      :level="selectedLevel"
      v-model:level-type="selectedLevelType"
      :task-id="taskId"
      :auth-header-typ="EndpointAuthorisationType.MODERATOR"
      height="30rem"
    ></BuildState>
    <draggable
      v-model="ideas"
      id="ideas"
      item-key="id"
      class="layout__columns"
      @end="dragDone"
    >
      <template v-slot:item="{ element }">
        <IdeaCard
          :idea="element"
          :isDraggable="true"
          :canChangeState="false"
          :showState="false"
          :portrait="false"
          :is-selected="selectedLevel && selectedLevel.id === element.id"
          @ideaDeleted="refreshIdeas()"
          @customCommand="dropdownCommand($event, element)"
          :style="{ '--level-type-color': getSettingsForLevel(element).color }"
          @click="selectedLevel = element"
        >
          <template #icon>
            <div class="level-icon">
              <font-awesome-icon :icon="getSettingsForLevel(element).icon" />
            </div>
          </template>
          <template #dropdown>
            <el-dropdown-item command="statistic">
              <font-awesome-icon icon="chart-column" />
            </el-dropdown-item>
          </template>
        </IdeaCard>
      </template>
    </draggable>
    <el-dialog
      v-model="showStatistic"
      :key="activeStatisticIdeaId"
      width="calc(var(--app-width) * 0.8)"
    >
      <template #header>
        {{ $t('moderator.view.topicDetails.statistic') }}
      </template>
      <LevelStatistic :task-id="this.taskId" :idea-id="activeStatisticIdeaId" />
    </el-dialog>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import * as ideaService from '@/services/idea-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import IdeaSettings from '@/components/moderator/organisms/settings/IdeaSettings.vue';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import draggable from 'vuedraggable';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import { IModeratorContent } from '@/types/ui/IModeratorContent';
import * as cashService from '@/services/cash-service';
import LevelStatistic from '@/modules/information/findit/organisms/LevelStatistic.vue';
import gameConfig from '@/modules/information/findit/data/gameConfig.json';
import BuildState from '@/modules/information/findit/organisms/BuildState.vue';

@Options({
  components: {
    BuildState,
    LevelStatistic,
    AddItem,
    IdeaSettings,
    IdeaCard,
    draggable,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue implements IModeratorContent {
  @Prop() readonly taskId!: string;
  ideas: Idea[] = [];
  activeStatisticIdeaId: string | null = null;
  selectedLevel: Idea | null = null;
  selectedLevelType = '';
  EndpointAuthorisationType = EndpointAuthorisationType;

  get showStatistic(): boolean {
    return !!this.activeStatisticIdeaId;
  }

  set showStatistic(value: boolean) {
    if (!value) this.activeStatisticIdeaId = null;
  }

  get defaultLevelType(): string {
    return Object.keys(gameConfig)[0];
  }

  getLevelTypeForLevel(level: Idea): string {
    return level.parameter.type ? level.parameter.type : this.defaultLevelType;
  }

  getSettingsForLevel(level: Idea): any {
    return gameConfig[this.getLevelTypeForLevel(level)].settings;
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateIdeas);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  ideaCash!: cashService.SimplifiedCashEntry<Idea[]>;
  @Watch('taskId', { immediate: true })
  reloadTaskSettings(): void {
    this.deregisterAll();
    this.ideaCash = ideaService.registerGetIdeasForTask(
      this.taskId,
      IdeaSortOrder.ORDER,
      null,
      this.updateIdeas,
      EndpointAuthorisationType.MODERATOR,
      10
    );
  }

  updateIdeas(ideas: Idea[]): void {
    this.ideas = ideas;
  }

  refreshIdeas(): void {
    this.ideaCash.refreshData();
  }

  /* eslint-disable @typescript-eslint/explicit-module-boundary-types*/
  async dragDone(): Promise<void> {
    this.ideas.forEach((idea, index) => {
      idea.order = index;
      ideaService.putIdea(idea, EndpointAuthorisationType.MODERATOR, false);
    });
  }

  dropdownCommand(command: string, idea: Idea): void {
    switch (command) {
      case 'statistic':
        this.activeStatisticIdeaId = idea.id;
        break;
    }
  }
}
</script>

<style scoped>
.level-icon {
  font-size: 2rem;
  margin: 0.8rem;
  color: var(--level-type-color);
}
</style>
