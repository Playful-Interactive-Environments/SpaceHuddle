<template>
  <div class="module-content">
    <el-tabs v-model="activeTab" v-if="selectedLevel">
      <el-tab-pane
        :label="$t('module.information.findit.moderatorContent.tabs.play')"
        name="play"
      >
      </el-tab-pane>
      <el-tab-pane
        :label="$t('module.information.findit.moderatorContent.tabs.edit')"
        name="edit"
        v-if="
          !selectedLevel.parameter.state ||
          selectedLevel.parameter.state !== LevelWorkflowType.approved
        "
      >
      </el-tab-pane>
    </el-tabs>
    <el-container>
      <el-aside v-if="selectedLevel">
        <PlayState
          v-if="activeTab === 'play'"
          :taskId="taskId"
          :level="selectedLevel"
          :auth-header-typ="EndpointAuthorisationType.MODERATOR"
        ></PlayState>
        <BuildState
          v-if="activeTab === 'edit'"
          :level="selectedLevel"
          v-model:level-type="selectedLevelType"
          :task-id="taskId"
          :auth-header-typ="EndpointAuthorisationType.MODERATOR"
          @approved="approved"
        ></BuildState>
      </el-aside>
      <el-main>
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
              :text="$t('module.information.findit.moderatorContent.add')"
              :is-column="true"
              @addNew="showSettings = true"
            />
          </template>
        </draggable>
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
      :idea="addIdea"
      :title="$t('module.information.default.moderatorContent.settingsTitle')"
      @updateData="addData"
    >
      <el-form-item
        :label="$t('module.information.findit.moderatorContent.levelType')"
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
                `module.information.findit.participant.placeables.${configType}.name`
              )
            "
          >
            <font-awesome-icon
              :icon="getSettingsForLevelType(configType).icon"
            />
            &nbsp;
            {{
              $t(
                `module.information.findit.participant.placeables.${configType}.name`
              )
            }}
          </el-option>
        </el-select>
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
import LevelStatistic from '@/modules/information/findit/organisms/LevelStatistic.vue';
import gameConfig from '@/modules/information/findit/data/gameConfig.json';
import BuildState from '@/modules/information/findit/organisms/BuildState.vue';
import PlayState from '@/modules/information/findit/organisms/PlayState.vue';
import * as configParameter from '@/modules/information/findit/utils/configParameter';
import { LevelWorkflowType } from '@/modules/information/findit/types/LevelWorkflowType';
import * as themeColors from '@/utils/themeColors';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const emptyParameter = {
  state: LevelWorkflowType.created,
  type: configParameter.getDefaultLevelType(),
  items: [],
};

@Options({
  components: {
    FontAwesomeIcon,
    PlayState,
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

  getSettingsForLevel = configParameter.getSettingsForLevel;
  getSettingsForLevelType = configParameter.getSettingsForLevelType;
  LevelWorkflowType = LevelWorkflowType;

  get showStatistic(): boolean {
    return !!this.activeStatisticIdeaId;
  }

  set showStatistic(value: boolean) {
    if (!value) this.activeStatisticIdeaId = null;
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
  padding-right: 2rem;
}

.module-content {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  padding-bottom: 1rem;
}
</style>
