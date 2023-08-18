<template>
  <el-container ref="container">
    <el-header>
      <el-tabs v-model="activeTab" v-if="showProgress">
        <el-tab-pane
          v-for="tabName of ['origin', 'general']"
          :key="tabName"
          :label="$t(`module.information.missionmap.enum.progress.${tabName}`)"
          :name="tabName"
        >
          <el-form label-position="top" :status-icon="true">
            <el-form-item
              v-for="parameter of Object.keys(gameConfig.parameter)"
              :key="parameter"
              :label="
                $t(`module.information.missionmap.gameConfig.${parameter}`)
              "
              :prop="`parameter.influenceAreas.${parameter}`"
              :style="{
                '--parameter-color': gameConfig.parameter[parameter].color,
                '--state-color': getStateColor(progress[parameter][tabName]),
              }"
            >
              <template #label>
                {{
                  $t(`module.information.missionmap.gameConfig.${parameter}`)
                }}
                <font-awesome-icon
                  :icon="gameConfig.parameter[parameter].icon"
                />
              </template>
              <el-slider
                v-model="progress[parameter][tabName]"
                :min="-5"
                :max="5"
                disabled
              />
            </el-form-item>
          </el-form>
        </el-tab-pane>
      </el-tabs>
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
          <p>{{ $t('module.information.missionmap.publicScreen.noIdeas') }}</p>
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
              v-model:fadeIn="ideaTransform[idea.id]"
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
import gameConfig from '@/modules/information/missionmap/data/gameConfig.json';
import { setHash } from '@/utils/url';
import * as themeColors from '@/utils/themeColors';
import * as votingService from '@/services/voting-service';
import { VoteParameterResult } from '@/types/api/Vote';

interface ProgressValues {
  origin: number;
  general: number;
}

@Options({
  computed: {
    gameConfig() {
      return gameConfig;
    },
  },
  components: {
    IdeaMap,
    IdeaCard,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  module: Module | undefined = undefined;
  ideas: Idea[] = [];
  ideaTransform: { [id: string]: boolean } = {};
  readonly newTimeSpan = 10000;
  filter: FilterData = { ...defaultFilterData };
  sizeLoaded = false;
  visibleIdeas: Idea[] = [];
  voteResults: VoteParameterResult[] = [];
  selectedIdea: Idea | null = null;
  decidedIdeas: Idea[] = [];
  selectionColor = '#0192d0';
  activeTab = 'general';
  showProgress = false;

  get progress(): { [key: string]: ProgressValues } {
    const result: { [key: string]: ProgressValues } = {};
    if (this.module) {
      for (const parameterName in gameConfig.parameter) {
        const origin = this.module.parameter[parameterName];
        result[parameterName] = {
          origin: origin,
          general: origin,
        };
        for (const idea of this.decidedIdeas) {
          const influence = idea.parameter.influenceAreas[parameterName];
          result[parameterName].general += influence;
        }
      }
    }
    return result;
  }

  getStateColor(state: number): string {
    if (state < 0) return themeColors.getRedColor();
    if (state < 2) return themeColors.getYellowColor();
    return themeColors.getGreenColor();
  }

  getIdeaColor(idea: Idea): string {
    if (this.isDecided(idea.id))
      return themeColors.getBrainstormingColor('-light');
    return '#ffffff';
  }

  isDecided(ideaId: string): boolean {
    return !!this.decidedIdeas.find((idea) => idea.id === ideaId);
  }

  ideaCash!: cashService.SimplifiedCashEntry<Idea[]>;
  votingParameterCash!: cashService.SimplifiedCashEntry<VoteParameterResult[]>;
  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.MODERATOR,
      30
    );
    this.ideaCash = ideaService.registerGetIdeasForTask(
      this.taskId,
      this.filter.orderType,
      null,
      this.updateIdeas,
      this.authHeaderTyp,
      20
    );
    this.votingParameterCash = votingService.registerGetParameterResult(
      this.taskId,
      'points',
      this.updateVoteResult,
      EndpointAuthorisationType.PARTICIPANT,
      60
    );
  }

  @Watch('selectedIdea', { immediate: true })
  onSelectedIdeaChanged(): void {
    if (this.selectedIdea) setHash(this.selectedIdea.id);
  }

  updateTask(task: Task): void {
    this.filter = getFilterForTask(task);
    this.ideaCash.parameter.urlParameter = ideaService.getIdeaListParameter(
      this.filter.orderType,
      null
    );
    if (task.modules.length === 1) this.module = task.modules[0];
    else {
      this.module = task.modules.find((t) => t.name === 'map');
    }
    if (this.module) this.showProgress = true;
  }

  updateIdeas(ideas: Idea[]): void {
    const currentDate = new Date();
    ideas = ideas.filter((idea) => idea.parameter.shareData);
    ideas = this.filter.orderAsc ? ideas : ideas.reverse();
    ideas = ideaService.filterIdeas(
      ideas,
      this.filter.stateFilter,
      this.filter.textFilter
    );
    this.ideas = ideas;

    this.ideaTransform = Object.assign(
      {},
      ...this.ideas.map((idea) => {
        const timeSpan =
          currentDate.getTime() - new Date(idea.timestamp).getTime();
        return { [idea.id]: timeSpan <= this.newTimeSpan };
      })
    );
    this.calculateDecidedIdeas();
  }

  updateVoteResult(votes: VoteParameterResult[]): void {
    this.voteResults = votes;
    this.calculateDecidedIdeas();
  }

  calculateDecidedIdeas(): void {
    if (this.voteResults.length > 0) {
      this.decidedIdeas = [];
      for (const idea of this.ideas) {
        const vote = this.voteResults.find((vote) => vote.ideaId === idea.id);
        if (vote) {
          if (vote.sum >= idea.parameter.points) {
            this.decidedIdeas.push(idea);
          }
        }
      }
    }
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateIdeas);
  }

  mounted(): void {
    setTimeout(() => {
      const dom = (this.$refs.container as any)?.$el as HTMLElement;
      if (dom) {
        const targetWidth = dom.parentElement?.offsetWidth;
        const targetHeight = dom.parentElement?.offsetHeight;
        if (targetWidth && targetHeight) {
          (dom as any).style.width = `${targetWidth}px`;
          (dom as any).style.height = `${targetHeight - 100}px`;
        }
        this.sizeLoaded = true;
      }
    }, 2000);
  }

  unmounted(): void {
    this.deregisterAll();
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

.el-form-item::v-deep(.el-form-item__label) {
  color: var(--parameter-color);
}

.el-form-item .el-slider {
  --el-slider-runway-bg-color: color-mix(
    in srgb,
    var(--state-color) 30%,
    transparent
  );
  --el-slider-disabled-color: var(--state-color);
}

.el-tabs::v-deep(.el-tabs__nav-next),
.el-tabs::v-deep(.el-tabs__nav-prev) {
  line-height: unset;
}
</style>
