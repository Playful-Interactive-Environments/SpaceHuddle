<template>
  <IdeaFilter :taskId="taskId" v-model="filter" @change="reloadIdeas(true)" />
  <IdeaMap
    class="mapSpace"
    :ideas="ideas"
    :canChangePosition="() => true"
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
            :isDraggable="true"
            :handleEditable="false"
            :isSelected="element.id === selectedIdea?.id"
            :selectionColor="selectionColor"
            v-model:collapseIdeas="filter.collapseIdeas"
            @ideaDeleted="reloadIdeas()"
            @ideaStartEdit="editIdea(element)"
            v-on:click="selectedIdea = element"
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
              $t('module.information.missionmap.moderatorContent.displayAll')
            "
            :isColumn="false"
            @addNew="item.displayCount = 1000"
            class="showMore"
          />
          <AddItem
            :text="$t('module.information.missionmap.moderatorContent.add')"
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
          :handleEditable="false"
          :selectionColor="selectionColor"
          v-model:collapseIdeas="filter.collapseIdeas"
          @ideaDeleted="reloadIdeas()"
          @ideaStartEdit="editIdea(idea)"
          v-on:click="selectedIdea = idea"
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
            $t('module.information.missionmap.moderatorContent.displayAll')
          "
          :isColumn="false"
          @addNew="item.displayCount = 1000"
          class="showMore"
        />
        <AddItem
          :text="$t('module.information.missionmap.moderatorContent.add')"
          :is-column="true"
          @addNew="editNewImage"
        />
      </div>
    </el-collapse-item>
  </el-collapse>
  <IdeaSettings
    v-model:show-modal="showSettings"
    :taskId="taskId"
    :idea="settingsIdea"
    @updateData="addData"
  >
    <el-form-item
      :label="$t('module.information.missionmap.moderatorContent.points')"
      :prop="`parameter.points`"
    >
      <el-slider
        v-model="settingsIdea.parameter.points"
        :min="500"
        :max="10000"
        :step="500"
      />
    </el-form-item>
    <el-form-item
      v-for="parameter of Object.keys(gameConfig.parameter)"
      :key="parameter"
      :label="$t(`module.information.missionmap.gameConfig.${parameter}`)"
      :prop="`parameter.influenceAreas.${parameter}`"
      :style="{ '--parameter-color': gameConfig.parameter[parameter].color }"
    >
      <template #label>
        {{ $t(`module.information.missionmap.gameConfig.${parameter}`) }}
        <font-awesome-icon :icon="gameConfig.parameter[parameter].icon" />
      </template>
      <el-slider
        v-if="settingsIdea.parameter.influenceAreas"
        v-model="settingsIdea.parameter.influenceAreas[parameter]"
        :min="-5"
        :max="5"
      />
    </el-form-item>
    <el-form-item
      v-for="parameter of Object.keys(additionalParameter)"
      :key="parameter"
      :label="$t(`module.information.moveit.enums.electricity.${parameter}`)"
      :prop="`parameter.electricity.${parameter}`"
      :style="{
        '--parameter-color': additionalParameter[parameter].color,
      }"
    >
      <template #label>
        {{ $t(`module.information.moveit.enums.electricity.${parameter}`) }}
        <font-awesome-icon :icon="additionalParameter[parameter].icon" />
      </template>
      <el-slider
        v-if="settingsIdea.parameter.electricity"
        v-model="settingsIdea.parameter.electricity[parameter]"
        :min="-10"
        :max="10"
      />
    </el-form-item>
    <el-form-item
      :label="
        $t('module.information.missionmap.moderatorConfig.minParticipants')
      "
      :prop="`parameter.minParticipants`"
    >
      <el-input-number
        v-model="settingsIdea.parameter.minParticipants"
        :min="1"
        :max="100"
      />
    </el-form-item>
    <el-form-item
      :label="$t('module.information.missionmap.moderatorConfig.minPoints')"
      :prop="`parameter.minPoints`"
    >
      <el-input-number
        v-model="settingsIdea.parameter.minPoints"
        :min="100"
        :max="settingsIdea.parameter.maxPoints"
      />
    </el-form-item>
    <el-form-item
      :label="$t('module.information.missionmap.moderatorConfig.maxPoints')"
      :prop="`parameter.maxPoints`"
    >
      <el-input-number
        v-model="settingsIdea.parameter.maxPoints"
        :min="settingsIdea.parameter.minPoints"
        :max="settingsIdea.parameter.points"
      />
    </el-form-item>
    <el-form-item
      :label="$t('module.information.missionmap.moderatorConfig.explanation')"
      :prop="`parameter.explanationList`"
    >
      <el-input
        v-for="(explanation, index) of settingsIdea.parameter.explanationList"
        :key="index"
        v-model="settingsIdea.parameter.explanationList[index]"
      >
        <template #prepend>
          <span style="width: 1.5rem">{{ index + 1 }}.</span>
        </template>
      </el-input>
    </el-form-item>
    <el-form-item
      :label="$t('module.information.missionmap.moderatorContent.share')"
      :prop="`parameter.shareData`"
    >
      <el-switch v-model="settingsIdea.parameter.shareData" />
    </el-form-item>
  </IdeaSettings>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import { Task } from '@/types/api/Task';
import * as ideaService from '@/services/idea-service';
import * as taskService from '@/services/task-service';
import * as votingService from '@/services/voting-service';
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
import IdeaFilter, {
  defaultFilterData,
  FilterData,
} from '@/components/moderator/molecules/IdeaFilter.vue';
import IdeaMap from '@/components/shared/organisms/IdeaMap.vue';
import { Module } from '@/types/api/Module';
import IdeaSettings from '@/components/moderator/organisms/settings/IdeaSettings.vue';
import gameConfig from '@/modules/information/missionmap/data/gameConfig.json';
import gameConfigMoveIt from '@/modules/information/moveit/data/gameConfig.json';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Vote } from '@/types/api/Vote';
import { setEmptyParameterIfNotExists } from '@/modules/information/missionmap/utils/parameter';

@Options({
  computed: {
    gameConfig() {
      return gameConfig;
    },
    gameConfigMoveIt() {
      return gameConfigMoveIt;
    },
  },
  components: {
    FontAwesomeIcon,
    IdeaMap,
    AddItem,
    IdeaCard,
    CollapseTitle,
    draggable,
    IdeaFilter,
    IdeaSettings,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue implements IModeratorContent {
  @Prop() readonly taskId!: string;
  module: Module | undefined = undefined;
  ideas: Idea[] = [];
  votes: Vote[] = [];
  orderGroupContent: OrderGroupList = {};
  openTabs: string[] = [];
  filter: FilterData = { ...defaultFilterData };
  cashEntry!: cashService.SimplifiedCashEntry<Idea[]>;
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

  get additionalParameter(): any {
    if (this.module && this.module.parameter.effectElectricity)
      return gameConfigMoveIt.electricity;
    return {};
  }

  @Watch('taskId', { immediate: true })
  reloadTaskSettings(): void {
    this.deregisterAll();
    this.cashEntry = ideaService.registerGetIdeasForTask(
      this.taskId,
      this.filter.orderType,
      null,
      this.updateIdeas,
      EndpointAuthorisationType.MODERATOR,
      20
    );
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.MODERATOR,
      60 * 60
    );
    votingService.registerGetVotes(
      this.taskId,
      this.updateVotes,
      EndpointAuthorisationType.MODERATOR,
      20
    );
  }

  updateVotes(votes: Vote[]): void {
    this.votes = votes;
  }

  updateTask(task: Task): void {
    if (task.modules.length === 1) this.module = task.modules[0];
    else {
      this.module = task.modules.find((t) => t.name === 'map');
    }
    this.resetAddIdea();
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
    this.cashEntry.parameter.urlParameter = ideaService.getIdeaListParameter(
      this.filter.orderType,
      null
    );
    this.reloadTabState = reloadTabState;
    this.cashEntry.refreshData(false);
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
    cashService.deregisterAllGet(this.updateIdeas);
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateVotes);
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

  refreshIdeas(): void {
    this.cashEntry.refreshData();
  }

  saveIdea(idea: Idea): void {
    ideaService.putIdea(idea, EndpointAuthorisationType.MODERATOR).then(() => {
      this.refreshIdeas();
    });
  }

  @Watch('showSettings', { immediate: true })
  onShowSettingsChanged(): void {
    if (this.showSettings) {
      this.addIdea.order = this.ideas.length;
    }
  }

  addData(newIdea: Idea): void {
    if (!this.settingsIdea.id) {
      this.ideas.push(newIdea);
    }
    this.resetAddIdea();
  }

  resetAddIdea(): void {
    this.settingsIdea = this.addIdea;
    this.addIdea.keywords = '';
    this.addIdea.description = '';
    this.addIdea.image = null;
    this.addIdea.link = null;
    this.addIdea.order = this.ideas.length;
    this.addIdea.parameter = {};
    setEmptyParameterIfNotExists(this.addIdea, () => this.module);
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
</style>
