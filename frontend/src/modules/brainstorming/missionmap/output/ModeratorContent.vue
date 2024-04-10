<template>
  <IdeaFilter :taskId="taskId" v-model="filter" @change="reloadIdeas(true)" />
  <div v-if="module">
    <el-tabs v-model="activeProgressTab">
      <el-tab-pane
        :name="MissionProgressParameter.influenceAreas"
        :label="
          $t('module.brainstorming.missionmap.participant.tabs.influenceAreas')
        "
      >
      </el-tab-pane>
      <el-tab-pane
        v-if="module.parameter.effectElectricity"
        :name="MissionProgressParameter.electricity"
        :label="
          $t('module.brainstorming.missionmap.participant.tabs.electricity')
        "
      >
      </el-tab-pane>
    </el-tabs>
    <MissionProgressChart
      :task-id="taskId"
      :mission-progress-parameter="activeProgressTab"
    />
  </div>
  <IdeaMap
    v-if="module"
    class="mapSpace"
    :ideas="ideas"
    :canChangePosition="(idea) => this.inputManager.isCurrentIdea(idea.id)"
    :highlightCondition="(idea) => !idea.parameter.shareData"
    v-model:selected-idea="selectedIdea"
    :parameter="module?.parameter"
    :calculate-size="false"
    v-on:ideaPositionChanged="saveIdea"
    v-on:selectionColorChanged="selectionColor = $event"
  >
  </IdeaMap>
  <el-collapse v-model="openTabs">
    <el-collapse-item
      v-for="(item, key) in orderGroupContent"
      :key="key"
      :name="key"
    >
      <template #title>
        <CollapseTitle :text="key" :avatar="item.avatar">
          <span
            role="button"
            class="awesome-icon"
            v-if="item.ideas.length > item.displayCount"
            v-on:click="item.displayCount = 1000"
          >
            <font-awesome-icon icon="ellipsis-h" />
          </span>
        </CollapseTitle>
      </template>
      <draggable
        v-model="item.filteredIdeas"
        :id="key"
        item-key="id"
        class="layout__columns"
        v-if="orderIsChangeable"
        @end="dragDone"
      >
        <template v-slot:item="{ element }">
          <IdeaCard
            :idea="element"
            :is-editable="this.inputManager.isCurrentIdea(element.id)"
            :isDraggable="true"
            :handleEditable="false"
            :is-sharable="true"
            :isSelected="element.id === selectedIdea?.id"
            :selectionColor="selectionColor"
            :background-color="getIdeaColor(element)"
            :share-state="element.parameter.shareData"
            v-model:collapseIdeas="filter.collapseIdeas"
            @ideaDeleted="reloadIdeas()"
            @ideaStartEdit="editIdea(element)"
            v-on:click="selectedIdea = element"
            @sharedStatusChanged="sharedStatusChanged(element, $event)"
          >
            <div>
              <font-awesome-icon icon="coins" />
              {{ getPointsForIdea(element.id) }} /
              {{ element.parameter.points }}
            </div>
            <div class="columns is-mobile">
              <div
                class="column"
                v-for="parameter of Object.keys(gameConfig.parameter)"
                :key="parameter"
                :style="{
                  color: gameConfig.parameter[parameter].color,
                }"
              >
                <font-awesome-icon
                  :icon="gameConfig.parameter[parameter].icon"
                />
                {{ element.parameter.influenceAreas[parameter] }}
              </div>
            </div>
          </IdeaCard>
        </template>
        <template v-slot:footer>
          <AddItem
            v-if="item.ideas.length > item.displayCount"
            :text="
              $t('module.brainstorming.missionmap.moderatorContent.displayAll')
            "
            :isColumn="false"
            @addNew="item.displayCount = 1000"
            class="showMore"
          />
          <AddItem
            :text="$t('module.brainstorming.missionmap.moderatorContent.add')"
            :is-column="true"
            @addNew="editNewImage"
          />
        </template>
      </draggable>
      <div class="layout__columns" v-else>
        <IdeaCard
          :idea="idea"
          v-for="(idea, index) in item.filteredIdeas"
          :key="index"
          :isSelected="idea.id === selectedIdea?.id"
          :is-editable="this.inputManager.isCurrentIdea(idea.id)"
          :handleEditable="false"
          :is-sharable="true"
          :selectionColor="selectionColor"
          :background-color="getIdeaColor(idea)"
          :share-state="idea.parameter.shareData"
          v-model:collapseIdeas="filter.collapseIdeas"
          @ideaDeleted="reloadIdeas()"
          @ideaStartEdit="editIdea(idea)"
          v-on:click="selectedIdea = idea"
          @sharedStatusChanged="sharedStatusChanged(idea, $event)"
        >
          <div>
            <font-awesome-icon icon="coins" />
            {{ getPointsForIdea(idea.id) }} / {{ idea.parameter.points }}
          </div>
          <div class="columns is-mobile">
            <div
              class="column"
              v-for="parameter of Object.keys(gameConfig.parameter)"
              :key="parameter"
              :style="{
                color: gameConfig.parameter[parameter].color,
              }"
            >
              <font-awesome-icon :icon="gameConfig.parameter[parameter].icon" />
              {{ idea.parameter.influenceAreas[parameter] }}
            </div>
          </div>
        </IdeaCard>
        <AddItem
          v-if="item.ideas.length > item.displayCount"
          :text="
            $t('module.brainstorming.missionmap.moderatorContent.displayAll')
          "
          :isColumn="false"
          @addNew="item.displayCount = 1000"
          class="showMore"
        />
        <AddItem
          :text="$t('module.brainstorming.missionmap.moderatorContent.add')"
          :is-column="true"
          @addNew="editNewImage"
        />
      </div>
    </el-collapse-item>
  </el-collapse>
  <MissionSettings
    v-model:show-modal="showSettings"
    :taskId="taskId"
    :module-id="module?.id"
    :idea="settingsIdea"
    @updateData="addData"
    ref="ideaSettings"
  >
  </MissionSettings>
  <el-dialog v-model="showReason" :before-close="resetReason">
    <template #header>
      {{ $t('module.brainstorming.missionmap.moderatorContent.reason') }}
    </template>
    <el-input
      v-model="settingsIdea.parameter.reasonsForRejection"
      :rows="3"
      type="textarea"
      :placeholder="
        $t(
          'module.brainstorming.missionmap.moderatorContent.reasonsForRejection'
        )
      "
    />
    <el-button @click="saveReason">
      {{ $t('module.brainstorming.missionmap.moderatorContent.save') }}
    </el-button>
  </el-dialog>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import { Task } from '@/types/api/Task';
import * as ideaService from '@/services/idea-service';
import * as taskService from '@/services/task-service';
import * as cashService from '@/services/cash-service';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import CollapseTitle from '@/components/moderator/atoms/CollapseTitle.vue';
import { OrderGroup, OrderGroupList } from '@/types/api/OrderGroup';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { reloadCollapseTabs } from '@/utils/collapse';
import draggable from 'vuedraggable';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import { EventType } from '@/types/enum/EventType';
import { IModeratorContent } from '@/types/ui/IModeratorContent';
import {
  defaultFilterData,
  FilterData,
} from '@/components/moderator/molecules/IdeaFilterBase.vue';
import IdeaFilter from '@/components/moderator/molecules/IdeaFilter.vue';
import IdeaMap from '@/components/shared/organisms/IdeaMap.vue';
import { Module } from '@/types/api/Module';
import gameConfig from '@/modules/brainstorming/missionmap/data/gameConfig.json';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Vote } from '@/types/api/Vote';
import { setEmptyParameterIfNotExists } from '@/modules/brainstorming/missionmap/utils/parameter';
import * as themeColors from '@/utils/themeColors';
import MissionProgressChart, {
  MissionProgressParameter,
} from '@/modules/brainstorming/missionmap/organisms/MissionProgressChart.vue';
import * as progress from '@/modules/brainstorming/missionmap/utils/progress';
import { MissionInputData } from '@/modules/brainstorming/missionmap/types/MissionInputData';
import { CombinedInputManager } from '@/types/input/CombinedInputManager';
import IdeaStates from '@/types/enum/IdeaStates';
import MissionSettings from '@/modules/brainstorming/missionmap/organisms/MissionSettings.vue';

@Options({
  computed: {
    IdeaStates() {
      return IdeaStates;
    },
    gameConfig() {
      return gameConfig;
    },
    MissionProgressParameter() {
      return MissionProgressParameter;
    },
  },
  components: {
    MissionSettings,
    FontAwesomeIcon,
    IdeaMap,
    AddItem,
    IdeaCard,
    CollapseTitle,
    draggable,
    IdeaFilter,
    MissionProgressChart,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue implements IModeratorContent {
  @Prop() readonly taskId!: string;
  module: Module | null = null;
  task: Task | null = null;
  ideas: Idea[] = [];
  votes: Vote[] = [];
  decidedIdeas: Idea[] = [];
  orderGroupContent: OrderGroupList = {};
  openTabs: string[] = [];
  filter: FilterData = { ...defaultFilterData };
  selectedIdea: Idea | null = null;
  selectionColor = '#0192d0';
  addIdea: any = {
    keywords: '',
    description: '',
    link: null,
    image: null, // the datebase64 url of created image
    order: 0,
    parameter: {},
  };
  settingsIdea = this.addIdea;
  showSettings = false;
  showReason = false;
  missionInput!: MissionInputData;
  inputManager!: CombinedInputManager;
  activeProgressTab = MissionProgressParameter.influenceAreas;

  getIdeaColor(idea: Idea): string {
    if (!idea.parameter.shareData)
      return themeColors.getInformingColor('-light');
    if (this.isDecided(idea.id))
      return themeColors.getBrainstormingColor('-light');
    return '#ffffff';
  }

  getPointsForIdea(ideaId: string): number {
    const ideaVotes = this.votes.filter((vote) => vote.ideaId === ideaId);
    let points = 0;
    for (const vote of ideaVotes) {
      points += vote.parameter.points;
    }
    return points;
  }

  get orderIsChangeable(): boolean {
    return this.filter.orderType === IdeaSortOrder.ORDER;
  }

  get progress(): { [key: string]: progress.ProgressValues } {
    return progress.getProgress(this.decidedIdeas, this.module);
  }

  isDecided(ideaId: string): boolean {
    return !!this.decidedIdeas.find((idea) => idea.id === ideaId);
  }

  @Watch('taskId', { immediate: true })
  reloadTaskSettings(): void {
    this.deregisterAll();
    /*if (this.taskId) {
      this.missionInput = new MissionInputData(this.taskId);
    }*/
    this.inputManager = new CombinedInputManager(
      this.taskId,
      this.filter.orderType,
      EndpointAuthorisationType.MODERATOR,
      true,
      'points'
    );
    this.inputManager.callbackUpdateIdeas = this.updateIdeas;
    this.inputManager.callbackUpdateVotes = this.updateVotes;
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.MODERATOR,
      60 * 60
    );
  }

  updateVotes(): void {
    this.votes = this.inputManager.votes;
    this.calculateDecidedIdeas();
  }

  updateTask(task: Task): void {
    this.task = task;
    if (task.modules.length === 1) this.module = task.modules[0];
    else {
      const module = task.modules.find((t) => t.name === 'missionmap');
      this.module = module ?? null;
    }
    this.resetAddIdea();
  }

  updateIdeas(): void {
    const ideas = this.inputManager.ideas;
    for (const idea of ideas) {
      setEmptyParameterIfNotExists(idea, () => this.module);
    }
    const orderType = this.filter.orderType;
    const dataList = ideaService.getOrderGroups(
      ideas,
      this.filter.orderAsc,
      this.orderGroupContent
    );
    let orderGroupName = '';
    let orderGroupContent: OrderGroupList = {};
    switch (orderType) {
      case IdeaSortOrder.TIMESTAMP:
      case IdeaSortOrder.ALPHABETICAL:
      case IdeaSortOrder.ORDER:
        dataList.ideas = ideaService.filterIdeas(
          dataList.ideas,
          this.filter.stateFilter,
          this.filter.textFilter
        );
        orderGroupName = (this as any).$t(
          `module.brainstorming.default.moderatorContent.${orderType}`
        );
        orderGroupContent[orderGroupName] = new OrderGroup(dataList.ideas);
        break;
      default:
        for (const key of Object.keys(dataList.oderGroups)) {
          dataList.oderGroups[key].ideas = ideaService.filterIdeas(
            dataList.oderGroups[key].ideas,
            this.filter.stateFilter,
            this.filter.textFilter
          );
        }
        orderGroupContent = dataList.oderGroups;
    }
    Object.keys(orderGroupContent).forEach((key) => {
      if (key in this.orderGroupContent)
        orderGroupContent[key].displayCount =
          this.orderGroupContent[key].displayCount;
    });
    const oldTabs = Object.keys(this.orderGroupContent);
    this.orderGroupContent = orderGroupContent;
    this.ideas = dataList.ideas;
    const newTabs = Object.keys(this.orderGroupContent);

    reloadCollapseTabs(
      this.openTabs,
      oldTabs,
      newTabs,
      this.reloadTabState
    ).then((tabs) => (this.openTabs = tabs));
    this.reloadTabState = false;
    this.calculateDecidedIdeas();
  }

  sharedStatusChanged(idea: Idea, value: boolean) {
    if (value) {
      idea.parameter.shareData = true;
      idea.state = IdeaStates.THUMBS_UP;
    } else {
      idea.parameter.shareData = false;
      idea.state = IdeaStates.THUMBS_DOWN;
      this.settingsIdea = idea;
      this.showReason = true;
    }
    this.saveIdea(idea);
  }

  calculateDecidedIdeas(): void {
    this.decidedIdeas = progress.calculateDecidedIdeasFromVotes(
      this.votes,
      this.ideas
    );
  }

  reloadTabState = true;
  reloadIdeas(reloadTabState = false): void {
    if (this.inputManager)
      this.inputManager.setOrderType(this.filter.orderType, true);
    this.reloadTabState = reloadTabState;
  }

  async mounted(): Promise<void> {
    this.eventBus.off(EventType.CHANGE_SETTINGS);
    this.eventBus.on(EventType.CHANGE_SETTINGS, async (taskId) => {
      if (this.taskId === taskId) {
        this.reloadIdeas();
      }
    });
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    if (this.missionInput) this.missionInput.deregisterAll();
    if (this.inputManager) this.inputManager.deregisterAll();
  }

  unmounted(): void {
    this.deregisterAll();
  }

  /* eslint-disable @typescript-eslint/explicit-module-boundary-types*/
  async dragDone(event: any): Promise<void> {
    const key = event.from.id;
    const ideas = this.orderGroupContent[key].filteredIdeas;
    ideas.forEach((idea) => {
      ideaService.putIdea(idea, EndpointAuthorisationType.MODERATOR, false);
    });
  }

  saveIdea(idea: Idea): void {
    ideaService.putIdea(idea, EndpointAuthorisationType.MODERATOR).then(() => {
      this.refreshIdeas();
    });
  }

  saveReason(): void {
    this.saveIdea(this.settingsIdea);
    this.settingsIdea = this.addIdea;
    this.showReason = false;
  }

  resetReason(): void {
    this.settingsIdea = this.addIdea;
    this.showReason = false;
  }

  refreshIdeas(): void {
    this.inputManager.refreshIdeas();
  }

  @Watch('showSettings', { immediate: true })
  onShowSettingsChanged(): void {
    if (this.showSettings) {
      this.addIdea.order = this.ideas.length;
    }
  }

  addData(newIdea: Idea): void {
    if (!this.settingsIdea.id) {
      this.inputManager.addIdea(newIdea);
      //this.ideas = this.inputManager.ideas;
      this.updateIdeas();
    }
    this.resetAddIdea();
  }

  resetAddIdea(): void {
    this.settingsIdea = {
      keywords: '',
      description: '',
      image: null,
      link: null,
      order: this.ideas.length,
      parameter: {},
    };
    this.addIdea.keywords = '';
    this.addIdea.description = '';
    this.addIdea.image = null;
    this.addIdea.link = null;
    this.addIdea.order = this.ideas.length;
    this.addIdea.parameter = {};
    setEmptyParameterIfNotExists(this.addIdea, () => this.module).then(() => {
      this.settingsIdea = this.addIdea;
    });
  }

  editNewImage(): void {
    this.resetAddIdea();
    this.showSettings = true;
  }

  editIdea(idea: Idea): void {
    this.settingsIdea = idea;
    this.showSettings = true;
  }
}
</script>

<style lang="scss" scoped>
.showMore {
  color: var(--color-highlight-dark);
  border-color: var(--color-highlight-dark);
  cursor: pointer;
}

.el-card::v-deep(.el-card__body) {
  padding: 14px;
}

.mapSpace {
  height: 20rem;
}

.el-form-item::v-deep(.el-form-item__label) {
  color: var(--parameter-color);
}

.link {
  font-size: var(--font-size-xxlarge);
  color: var(--color-dark-contrast-light);
  padding-right: 0.5rem;
}

.is-active {
  color: var(--color-dark-contrast-dark);
}
</style>
