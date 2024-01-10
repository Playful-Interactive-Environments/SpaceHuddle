<template>
  <el-container ref="container">
    <el-header height="150px" class="statistic">
      <el-tabs v-model="activeProgressTab">
        <el-tab-pane
          :name="MissionProgressParameter.influenceAreas"
          :label="
            $t(
              'module.brainstorming.missionmap.participant.tabs.influenceAreas'
            )
          "
        >
        </el-tab-pane>
        <el-tab-pane
          v-if="showProgress && module.parameter.effectElectricity"
          :name="MissionProgressParameter.electricity"
          :label="
            $t('module.brainstorming.missionmap.participant.tabs.electricity')
          "
        >
        </el-tab-pane>
      </el-tabs>
      <MissionProgressChart
        v-if="showProgress"
        :task-id="taskId"
        :mission-progress-parameter="activeProgressTab"
      />
    </el-header>
    <el-container>
      <el-aside width="70vw" class="mapSpace">
        <IdeaMap
          v-if="sizeLoaded"
          :ideas="ideas"
          :parameter="module?.parameter"
          :canChangePosition="() => false"
          :calculate-size="false"
          v-model:selected-idea="selectedIdea"
          v-on:visibleIdeasChanged="visibleIdeasChanged"
          v-on:selectionColorChanged="selectionColor = $event"
        >
        </IdeaMap>
      </el-aside>
      <el-main>
        <section
          v-if="ideas.length === 0"
          class="centered public-screen__error"
        >
          <p>
            {{ $t('module.brainstorming.missionmap.publicScreen.noIdeas') }}
          </p>
        </section>
        <div v-else class="public-screen__content">
          <section class="layout__columns">
            <IdeaCard
              v-for="(idea, index) in visibleIdeas"
              :idea="idea"
              :key="index"
              :is-editable="false"
              :isSelected="idea.id === selectedIdea?.id"
              :selectionColor="selectionColor"
              :background-color="getIdeaColor(idea)"
              v-model:collapseIdeas="filter.collapseIdeas"
              v-on:click="selectedIdea = idea"
            >
              <div>
                <font-awesome-icon icon="coins" />
                {{ idea.parameter.points }}
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
                  {{ idea.parameter.influenceAreas[parameter] }}
                </div>
              </div>
            </IdeaCard>
          </section>
        </div>
      </el-main>
    </el-container>
  </el-container>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as ideaService from '@/services/idea-service';
import * as taskService from '@/services/task-service';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import {
  defaultFilterData,
  FilterData,
  getFilterForTask,
} from '@/components/moderator/molecules/IdeaFilter.vue';
import { Task } from '@/types/api/Task';
import * as cashService from '@/services/cash-service';
import IdeaMap from '@/components/shared/organisms/IdeaMap.vue';
import { Module } from '@/types/api/Module';
import gameConfig from '@/modules/brainstorming/missionmap/data/gameConfig.json';
import { setHash } from '@/utils/url';
import * as themeColors from '@/utils/themeColors';
import { VoteParameterResult } from '@/types/api/Vote';
import MissionProgressChart, {
  MissionProgressParameter,
} from '@/modules/brainstorming/missionmap/organisms/MissionProgressChart.vue';
import * as progress from '@/modules/brainstorming/missionmap/utils/progress';
import { CombinedInputManager } from '@/types/input/CombinedInputManager';
import { registerDomElement, unregisterDomElement } from '@/vunit';

@Options({
  computed: {
    gameConfig() {
      return gameConfig;
    },
    MissionProgressParameter() {
      return MissionProgressParameter;
    },
  },
  components: {
    IdeaMap,
    IdeaCard,
    MissionProgressChart,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  module: Module | null = null;
  ideas: Idea[] = [];
  readonly newTimeSpan = 10000;
  filter: FilterData = { ...defaultFilterData };
  sizeLoaded = false;
  visibleIdeas: Idea[] = [];
  voteResults: VoteParameterResult[] = [];
  selectedIdea: Idea | null = null;
  decidedIdeas: Idea[] = [];
  selectionColor = '#0192d0';
  showProgress = false;
  inputManager!: CombinedInputManager;
  activeProgressTab = MissionProgressParameter.influenceAreas;

  get progress(): { [key: string]: progress.ProgressValues } {
    return progress.getProgress(this.decidedIdeas, this.module);
  }

  getIdeaColor(idea: Idea): string {
    if (this.isDecided(idea.id))
      return themeColors.getBrainstormingColor('-light');
    return '#ffffff';
  }

  isDecided(ideaId: string): boolean {
    return !!this.decidedIdeas.find((idea) => idea.id === ideaId);
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    this.inputManager = new CombinedInputManager(
      this.taskId,
      this.filter.orderType,
      this.authHeaderTyp,
      true,
      'points'
    );
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      this.authHeaderTyp,
      30
    );
    this.inputManager.callbackUpdateIdeas = this.updateIdeas;
    this.inputManager.callbackUpdateVotes = this.updateVotes;
  }

  @Watch('selectedIdea', { immediate: true })
  onSelectedIdeaChanged(): void {
    if (this.selectedIdea) setHash(this.selectedIdea.id);
  }

  updateTask(task: Task): void {
    this.filter = getFilterForTask(task);
    this.inputManager.setOrderType(this.filter.orderType, false);
    if (task.modules.length === 1) this.module = task.modules[0];
    else {
      const module = task.modules.find((t) => t.name === 'missionmap');
      this.module = module ?? null;
    }
    if (this.module) this.showProgress = true;
  }

  updateIdeas(): void {
    let ideas = this.inputManager.ideas;
    ideas = ideas.filter((idea) => idea.parameter.shareData);
    ideas = this.filter.orderAsc ? ideas : ideas.reverse();
    ideas = ideaService.filterIdeas(
      ideas,
      this.filter.stateFilter,
      this.filter.textFilter
    );
    this.ideas = ideas;
    this.calculateDecidedIdeas();
  }

  updateVotes(): void {
    this.updateVoteResult(this.inputManager.votingResult);
  }

  updateVoteResult(votes: VoteParameterResult[]): void {
    this.voteResults = votes;
    this.calculateDecidedIdeas();
  }

  calculateDecidedIdeas(): void {
    this.decidedIdeas = progress.calculateDecidedIdeasFromResult(
      this.voteResults,
      this.ideas
    );
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    if (this.inputManager) this.inputManager.deregisterAll();
  }

  domKey = '';
  mounted(): void {
    const dom = (this.$refs.container as any)?.$el as HTMLElement;
    this.domKey = registerDomElement(
      dom,
      () => {
        this.sizeLoaded = true;
      },
      2000
    );
  }

  unmounted(): void {
    this.deregisterAll();
    unregisterDomElement(this.domKey);
  }

  visibleIdeasChanged(ideas: Idea[]): void {
    this.visibleIdeas = ideas;
  }
}
</script>

<style lang="scss" scoped>
.public-screen__content {
  //display: grid;
  //grid-template-columns: 80% 20%;
}

.new {
  padding-left: 1rem;
}

.mapSpace {
  height: 100%;
  margin-right: 1rem;
}

.statistic {
  margin-bottom: 3rem;
}
</style>
