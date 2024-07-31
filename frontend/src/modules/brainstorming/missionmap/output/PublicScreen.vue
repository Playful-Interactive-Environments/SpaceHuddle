<template>
  <el-container ref="container">
    <el-aside width="50vw" class="mapSpace">
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
            <template #label>
              <font-awesome-icon icon="leaf" />
              &nbsp;
              <font-awesome-icon icon="heart" />
              &nbsp;
              <font-awesome-icon icon="sack-dollar" />
              &nbsp;
              {{
                $t(
                  'module.brainstorming.missionmap.participant.tabs.influenceAreas'
                )
              }}
            </template>
          </el-tab-pane>
          <el-tab-pane
            v-if="showProgress && module.parameter.effectElectricity"
            :name="MissionProgressParameter.electricity"
            :label="
              $t('module.brainstorming.missionmap.participant.tabs.electricity')
            "
          >
            <template #label>
              <font-awesome-icon icon="plug-circle-bolt" />
              &nbsp;
              {{
                $t(
                  'module.brainstorming.missionmap.participant.tabs.electricity'
                )
              }}
            </template>
          </el-tab-pane>
        </el-tabs>
        <MissionProgressChart
          v-if="showProgress"
          :task-id="taskId"
          :mission-progress-parameter="activeProgressTab"
          :showIdeas="false"
        />
      </el-header>
    </el-aside>
    <el-container ref="mapSpace" class="ideaSpace">
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
          <section class="ideaList columns is-multiline is-gapless">
            <IdeaCard
              v-for="(idea, index) in ideas.sort(
                (a, b) => decisionIndex(a.id) - decisionIndex(b.id)
              )"
              :idea="idea"
              :key="index"
              class="ideaCard column"
              :show-state="false"
              :is-editable="false"
              :background-color="getIdeaColor(idea)"
              fix-height="15rem"
              image-height="33%"
            >
              <template #icon>
                <font-awesome-icon icon="person-booth" />
              </template>
              <template #image_overlay>
                <div class="media image_overlay">
                  <div class="media-content">
                    <font-awesome-icon icon="coins" />
                    {{ getVoteResultForIdea(idea.id)?.sum }} /
                    <font-awesome-icon icon="greater-than-equal" />
                    {{ idea.parameter.points }}
                    <br />
                    <font-awesome-icon icon="user-pen" />
                    {{ getVoteResultForIdea(idea.id)?.count }} /
                    <font-awesome-icon icon="greater-than-equal" />
                    {{ idea.parameter.minParticipants }}
                  </div>
                  <div class="media-right">
                    <font-awesome-icon
                      v-for="parameter of getInfluenceAreasForIdea(idea)"
                      :key="parameter"
                      :style="{
                        color: gameConfig.parameter[parameter].color,
                      }"
                      :icon="gameConfig.parameter[parameter].icon"
                    />
                  </div>
                </div>
              </template>
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
} from '@/components/moderator/molecules/IdeaFilterBase.vue';
import { getFilterForTask } from '@/components/moderator/molecules/IdeaFilter.vue';
import { Task } from '@/types/api/Task';
import * as cashService from '@/services/cash-service';
import { Module } from '@/types/api/Module';
import gameConfig from '@/modules/brainstorming/missionmap/data/gameConfig.json';
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
  voteResults: VoteParameterResult[] = [];
  decidedIdeas: Idea[] = [];
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

  decisionIndex(ideaId: string): number {
    const index = this.decidedIdeas.findIndex((idea) => idea.id === ideaId);
    if (index >= 0) return index;
    return 10000;
  }

  getInfluenceAreasForIdea(idea: Idea): string[] {
    const areas: string[] = [];
    for (const parameter of Object.keys(gameConfig.parameter)) {
      if (idea.parameter.influenceAreas[parameter] > 0) areas.push(parameter);
    }
    return areas;
  }

  getVoteResultForIdea(ideaId: string): VoteParameterResult | undefined {
    return this.voteResults.find((vote) => vote.ideaId === ideaId);
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
        const dom = (this.$refs.mapSpace as any)?.$el as HTMLElement;
        this.domKey = registerDomElement(
          dom,
          (targetWidth, targetHeight) => {
            const minHeight = 300;
            if (targetHeight < minHeight) {
              dom.style.height = `${minHeight}px`;
            }
            this.sizeLoaded = true;
          },
          2000
        );
      },
      2000
    );
  }

  unmounted(): void {
    this.deregisterAll();
    unregisterDomElement(this.domKey);
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
  overflow: hidden;
  .map {
    height: 50%;
  }
}

.ideaSpace {
  border-left: 2px solid var(--color-background-dark);
  padding-left: 1rem;
}

.statistic {
  height: unset;
  //margin-bottom: 3rem;
}

.layout__columns {
  column-width: 15rem;
}

.ideaList {
  margin: -0.25rem;

  > .ideaCard {
    flex: none;
    flex-grow: 1;
    flex-shrink: 0;
    width: 15rem;
    height: 23rem;
    border-radius: var(--border-radius);
    border: solid var(--color-dark-contrast) 5px;
    margin: 0.25rem;
  }
}

.image_overlay {
  padding: 0.2rem;
  background-color: color-mix(
    in srgb,
    var(--color-dark-contrast) 60%,
    transparent
  );

  .media-content {
    color: white;
    padding-left: 0.5rem;
  }

  .media-right {
    color: white;
    text-align: right;
  }

  svg {
    padding-right: 0.5rem;
  }
}
</style>
