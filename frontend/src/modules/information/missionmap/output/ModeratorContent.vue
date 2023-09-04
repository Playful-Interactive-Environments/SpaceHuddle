<template>
  <IdeaFilter :taskId="taskId" v-model="filter" @change="reloadIdeas(true)" />
  <MissionProgress
    v-if="module"
    :progress="progress"
    :progress-tabs="['origin', 'progress']"
  />
  <IdeaMap
    class="mapSpace"
    :ideas="ideas"
    :canChangePosition="
      (idea) => this.ownIdeas.find((item) => item.id === idea.id) !== undefined
    "
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
            :is-editable="
              this.ownIdeas.find((item) => item.id === element.id) !== undefined
            "
            :isDraggable="true"
            :handleEditable="false"
            :isSelected="element.id === selectedIdea?.id"
            :selectionColor="selectionColor"
            :background-color="getIdeaColor(element)"
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
          :is-editable="
            this.ownIdeas.find((item) => item.id === idea.id) !== undefined
          "
          :handleEditable="false"
          :selectionColor="selectionColor"
          :background-color="getIdeaColor(idea)"
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
    ref="ideaSettings"
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
      :rules="[{ validator: validateElectricity }]"
    >
      <template #label>
        {{ $t(`module.information.moveit.enums.electricity.${parameter}`) }}
        <font-awesome-icon :icon="additionalParameter[parameter].icon" />
      </template>
      <el-input-number
        v-if="settingsIdea.parameter.electricity"
        v-model="settingsIdea.parameter.electricity[parameter]"
        :min="-100"
        :max="100"
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
import * as themeColors from '@/utils/themeColors';
import MissionProgress from '@/modules/information/missionmap/organisms/MissionProgress.vue';
import { ValidationRules } from '@/types/ui/ValidationRule';
import ValidationForm from '@/components/shared/molecules/ValidationForm.vue';
import * as progress from '@/modules/information/missionmap/utils/progress';
import { MissionInputData } from '@/modules/information/missionmap/types/MissionInputData';
import * as viewService from '@/services/view-service';

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
    MissionProgress,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue implements IModeratorContent {
  @Prop() readonly taskId!: string;
  module: Module | undefined = undefined;
  task: Task | null = null;
  ideas: Idea[] = [];
  votes: Vote[] = [];
  decidedIdeas: Idea[] = [];
  orderGroupContent: OrderGroupList = {};
  openTabs: string[] = [];
  filter: FilterData = { ...defaultFilterData };
  ideaCashEntry!: cashService.SimplifiedCashEntry<Idea[]>;
  inputCashEntry!: cashService.SimplifiedCashEntry<Idea[]>;
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
  missionInput!: MissionInputData;

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

  get additionalParameter(): any {
    if (this.module && this.module.parameter.effectElectricity)
      return gameConfigMoveIt.electricity;
    return {};
  }

  get progress(): { [key: string]: progress.ProgressValues } {
    return progress.getProgress(this.decidedIdeas, this.module);
  }

  isDecided(ideaId: string): boolean {
    return !!this.decidedIdeas.find((idea) => idea.id === ideaId);
  }

  validateElectricity(
    rule: ValidationRules,
    value: string,
    // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
    callback: any
  ): boolean {
    const parameterList = Object.keys(this.additionalParameter);
    if (parameterList.length === 0) {
      callback();
      return true;
    }
    let sum = 0;
    for (const parameterName of parameterList) {
      const parameterValue =
        this.settingsIdea.parameter.electricity[parameterName];
      sum += parameterValue;
    }
    const form = (this.$refs.ideaSettings as IdeaSettings).$refs
      .dataForm as ValidationForm;
    form.clearValidate();
    if (sum === 0) {
      callback();
      return true;
    } else {
      const errorText = (this as any).$t(
        'module.information.missionmap.moderatorContent.electricityValidationErrors'
      );
      callback(new Error(`${errorText} ${sum}`));
      return false;
    }
  }

  @Watch('taskId', { immediate: true })
  reloadTaskSettings(): void {
    this.deregisterAll();
    /*if (this.taskId) {
      this.missionInput = new MissionInputData(this.taskId);
    }*/
    this.ideaCashEntry = ideaService.registerGetIdeasForTask(
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
    this.inputCashEntry = viewService.registerGetInputIdeas(
      this.taskId,
      this.filter.orderType,
      null,
      this.updateInputIdeas,
      EndpointAuthorisationType.MODERATOR,
      20
    );
  }

  updateVotes(votes: Vote[]): void {
    this.votes = votes;
    this.calculateDecidedIdeas();
  }

  updateTask(task: Task): void {
    this.task = task;
    if (task.modules.length === 1) this.module = task.modules[0];
    else {
      this.module = task.modules.find((t) => t.name === 'missionmap');
    }
    this.resetAddIdea();
  }

  ownIdeas: Idea[] = [];
  inputIdeas: Idea[] = [];
  updateIdeas(ideas: Idea[]): void {
    this.ownIdeas = ideas;
    this.updateIdeaList();
  }

  updateInputIdeas(ideas: Idea[]): void {
    this.inputIdeas = ideas;
    this.updateIdeaList();
  }

  updateIdeaList(): void {
    const ideas = [...this.ownIdeas, ...this.inputIdeas].sort((a, b) => {
      if (a.orderGroup === b.orderGroup) {
        return b.orderText.localeCompare(a.orderText);
      } else {
        return b.orderGroup.localeCompare(a.orderGroup);
      }
    });
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

  calculateDecidedIdeas(): void {
    this.decidedIdeas = progress.calculateDecidedIdeasFromVotes(
      this.votes,
      this.ideas
    );
  }

  reloadTabState = true;
  reloadIdeas(reloadTabState = false): void {
    this.inputCashEntry.parameter.urlParameter =
      ideaService.getIdeaListParameter(this.filter.orderType, null);
    this.ideaCashEntry.parameter.urlParameter =
      ideaService.getIdeaListParameter(this.filter.orderType, null);
    this.reloadTabState = reloadTabState;
    this.inputCashEntry.refreshData(false);
    this.ideaCashEntry.refreshData(false);
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
    if (this.missionInput) this.missionInput.deregisterAll();
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
    this.inputCashEntry.refreshData();
    this.ideaCashEntry.refreshData();
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
