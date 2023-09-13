<template>
  <div class="module-content">
    <IdeaFilter :taskId="taskId" v-model="filter" @change="reloadIdeas(true)" />

    <div class="level-layout" v-if="selectedLevel">
      <BuildState
        :level="selectedLevel"
        v-model:level-type="selectedLevelType"
        :task-id="taskId"
        :auth-header-typ="EndpointAuthorisationType.MODERATOR"
        @approved="approved"
      ></BuildState>
    </div>
    <el-container>
      <el-aside>
        <IdeaMap
          :ideas="ideas"
          :parameter="module?.parameter"
          :canChangePosition="() => true"
          v-model:selected-idea="selectedLevel"
        />
      </el-aside>
      <el-main>
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
                  :isDraggable="true"
                  :canChangeState="false"
                  :showState="false"
                  :portrait="false"
                  :is-selected="
                    selectedLevel && selectedLevel.id === element.id
                  "
                  :background-color="getLevelColor(element)"
                  @ideaDeleted="refreshIdeas()"
                  @customCommand="dropdownCommand($event, element)"
                  :style="{
                    '--level-type-color': getSettingsForLevel(element).color,
                  }"
                  @click="selectLevel(element)"
                >
                  <template #icon>
                    <div class="level-icon">
                      <font-awesome-icon
                        :icon="getSettingsForLevel(element).icon"
                      />
                    </div>
                  </template>
                  <template #dropdown>
                    <el-dropdown-item command="statistic">
                      <font-awesome-icon icon="chart-column" />
                    </el-dropdown-item>
                  </template>
                </IdeaCard>
              </template>
              <template v-slot:footer>
                <AddItem
                  :text="$t('module.playing.coolit.moderatorContent.add')"
                  :is-column="true"
                  @addNew="showSettings = true"
                />
              </template>
            </draggable>
            <div class="layout__columns" v-else>
              <IdeaCard
                v-for="(idea, index) in item.filteredIdeas"
                :key="index"
                :idea="idea"
                :isDraggable="true"
                :canChangeState="false"
                :showState="false"
                :portrait="false"
                :is-selected="selectedLevel && selectedLevel.id === idea.id"
                :background-color="getLevelColor(idea)"
                @ideaDeleted="refreshIdeas()"
                @customCommand="dropdownCommand($event, idea)"
                :style="{
                  '--level-type-color': getSettingsForLevel(idea).color,
                }"
                @click="selectLevel(idea)"
                v-model:collapseIdeas="filter.collapseIdeas"
              >
                <template #icon>
                  <div class="level-icon">
                    <font-awesome-icon :icon="getSettingsForLevel(idea).icon" />
                  </div>
                </template>
                <template #dropdown>
                  <el-dropdown-item command="statistic">
                    <font-awesome-icon icon="chart-column" />
                  </el-dropdown-item>
                </template>
              </IdeaCard>
              <AddItem
                :text="$t('module.playing.findit.moderatorContent.add')"
                :is-column="true"
                @addNew="showSettings = true"
              />
            </div>
          </el-collapse-item>
        </el-collapse>
      </el-main>
    </el-container>
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
    <IdeaSettings
      v-model:show-modal="showSettings"
      :taskId="taskId"
      :idea="settingsIdea"
      @updateData="addData"
      ref="ideaSettings"
    >
      <el-form-item
        :label="$t('module.playing.coolit.moderatorContent.levelType')"
        :prop="`parameter.shareData`"
      >
        <el-select v-model="addIdea.parameter.type">
          <el-option
            v-for="configType of Object.keys(gameConfig)"
            :key="configType"
            :value="configType"
            :style="{ color: getSettingsForLevelType(configType).color }"
            :label="
              $t(
                `module.playing.coolit.participant.placeables.${configType}.name`
              )
            "
          >
            <font-awesome-icon
              :icon="getSettingsForLevelType(configType).icon"
            />
            &nbsp;
            {{
              $t(
                `module.playing.coolit.participant.placeables.${configType}.name`
              )
            }}
          </el-option>
        </el-select>
      </el-form-item>
      <el-form-item
        :label="$t('module.playing.coolit.moderatorContent.share')"
        :prop="`parameter.shareData`"
      >
        <el-switch v-model="settingsIdea.parameter.shareData" />
      </el-form-item>
    </IdeaSettings>
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
import LevelStatistic from '@/modules/playing/coolit/organisms/LevelStatistic.vue';
import gameConfig from '@/modules/playing/coolit/data/gameConfig.json';
import BuildState from '@/modules/playing/coolit/organisms/BuildState.vue';
import * as configParameter from '@/modules/playing/coolit/utils/configParameter';
import { LevelWorkflowType } from '@/modules/playing/coolit/types/LevelWorkflowType';
import * as themeColors from '@/utils/themeColors';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import IdeaFilter, {
  defaultFilterData,
  FilterData,
} from '@/components/moderator/molecules/IdeaFilter.vue';
import { reloadCollapseTabs } from '@/utils/collapse';
import { setEmptyParameterIfNotExists } from '@/modules/brainstorming/missionmap/utils/parameter';
import { OrderGroup, OrderGroupList } from '@/types/api/OrderGroup';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
import { Module } from '@/types/api/Module';
import CollapseTitle from '@/components/moderator/atoms/CollapseTitle.vue';
import IdeaMap from '@/components/shared/organisms/IdeaMap.vue';

const emptyParameter = {
  state: LevelWorkflowType.created,
  type: configParameter.getDefaultLevelType(),
  items: [],
};

@Options({
  components: {
    IdeaMap,
    FontAwesomeIcon,
    BuildState,
    LevelStatistic,
    AddItem,
    IdeaSettings,
    IdeaCard,
    draggable,
    IdeaFilter,
    CollapseTitle,
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
  activeTab = 'play';
  gameConfig = gameConfig;
  showSettings = false;
  addIdea: any = {
    keywords: '',
    description: '',
    link: null,
    image: null, // the datebase64 url of created image
    parameter: { ...emptyParameter },
  };
  settingsIdea = this.addIdea;
  openTabs: string[] = [];
  module: Module | undefined = undefined;
  filter: FilterData = { ...defaultFilterData };
  orderGroupContent: OrderGroupList = {};

  getSettingsForLevel = configParameter.getSettingsForLevel;
  getSettingsForLevelType = configParameter.getSettingsForLevelType;
  LevelWorkflowType = LevelWorkflowType;

  get showStatistic(): boolean {
    return !!this.activeStatisticIdeaId;
  }

  set showStatistic(value: boolean) {
    if (!value) this.activeStatisticIdeaId = null;
  }

  get orderIsChangeable(): boolean {
    return this.filter.orderType === IdeaSortOrder.ORDER;
  }

  getLevelColor(level: Idea): string {
    if (level.parameter.state === LevelWorkflowType.approved) return 'white';
    return themeColors.getInformingColor('-light');
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
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.MODERATOR,
      60 * 60
    );
  }

  updateTask(task: Task): void {
    if (task.modules.length === 1) this.module = task.modules[0];
    else {
      this.module = task.modules.find((t) => t.name === 'coolit');
    }
  }

  updateIdeas(ideas: Idea[]): void {
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
  }

  reloadTabState = true;
  reloadIdeas(reloadTabState = false): void {
    this.ideaCash.parameter.urlParameter = ideaService.getIdeaListParameter(
      this.filter.orderType,
      null
    );
    this.reloadTabState = reloadTabState;
    this.ideaCash.refreshData(false);
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

  selectLevel(level: Idea): void {
    this.selectedLevelType = '';
    this.selectedLevel = level;
    this.activeTab = 'play';
  }

  approved(): void {
    this.activeTab = 'play';
  }

  @Watch('showSettings', { immediate: true })
  onShowSettingsChanged(): void {
    if (this.showSettings) {
      this.addIdea.order = this.ideas.length;
      this.addIdea.parameter = { ...emptyParameter };
    }
  }

  addData(newIdea: Idea): void {
    this.addIdea.keywords = '';
    this.addIdea.description = '';
    this.addIdea.image = null;
    this.addIdea.link = null;
    this.addIdea.parameter = { ...emptyParameter };
    this.ideas.push(newIdea);
  }
}
</script>

<style scoped>
.level-icon {
  font-size: 2rem;
  margin: 0.8rem;
  color: var(--level-type-color);
}

.el-aside {
  overflow: hidden;
  --el-aside-width: 50%;
  margin-right: 1rem;
  display: flex;
  flex-direction: column;
  justify-content: stretch;
}

.level-layout {
  height: 20rem;
  margin-bottom: 0.5rem;
}

.module-content {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  padding-bottom: 1rem;
}
</style>
