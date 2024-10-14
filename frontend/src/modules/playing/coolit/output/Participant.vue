<template>
  <div ref="gameContainer" class="mapSpace">
    <module-info
      v-if="module"
      translation-path="module.playing.coolit.participant.tutorial"
      image-directory="/assets/games/coolit/tutorial"
      :module-info-entry-data-list="tutorialList"
      :active="gameState === GameState.Info"
      @infoRead="
        gameState =
          gameStep === GameStep.Play && !tutorialNotShown
            ? GameState.Tutorial
            : GameState.Game
      "
      @tutorialNotShown="() => (tutorialNotShown = true)"
      :info-type="`cool-it-${gameStep}`"
      :showTutorialOnlyOnce="module.parameter.showTutorialOnlyOnce"
    />
    <select-level
      v-if="gameStep === GameStep.Select && gameState === GameState.Game"
      :tracking-manager="trackingManager"
      :task-id="taskId"
      :module="module"
      :open-high-score="levelDone"
      :win-time="180000"
      @play="startLevel"
    />
    <TutorialGameHeat
      v-if="gameStep === GameStep.Play && gameState === GameState.Tutorial"
      @done="gameState = GameState.Game"
    />
    <play-level
      v-if="gameStep === GameStep.Play && gameState === GameState.Game"
      :task-id="taskId"
      :level="activeLevel"
      :tracking-manager="trackingManager"
      :temperature-rise="temperatureRise"
      :win-time="180000"
      @finished="levelPlayed"
      @replayFinished="replayedFinished"
    />
    <el-button
      v-if="gameStep === GameStep.Select && gameState === GameState.Game"
      class="tutorialButton"
      @click="gameState = GameState.Info"
      ><font-awesome-icon :icon="['fas', 'lightbulb']"
    /></el-button>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ModuleInfo, {
  ModuleInfoEntryData,
} from '@/components/participant/molecules/ModuleInfo.vue';
import { Module } from '@/types/api/Module';
import * as moduleService from '@/services/module-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import SelectLevel from '@/modules/playing/coolit/organisms/SelectLevel.vue';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import * as cashService from '@/services/cash-service';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import PlayLevel from '@/modules/playing/coolit/organisms/PlayLevel.vue';
import { PlayStateResult } from '@/modules/playing/coolit/organisms/PlayLevel.vue';
import { Idea } from '@/types/api/Idea';
import TaskParticipantIterationStatesType from '@/types/enum/TaskParticipantIterationStatesType';
import TutorialGameMolecule from '@/modules/playing/coolit/organisms/tutorial/molecule.vue';
import TutorialGameHeat from '@/modules/playing/coolit/organisms/tutorial/heat.vue';

export enum GameStep {
  Select = 'select',
  Play = 'play',
}

enum GameState {
  Info = 'info',
  Game = 'game',
  Tutorial = 'tutorial',
}

@Options({
  components: {
    PlayLevel,
    SelectLevel,
    ModuleInfo,
    TutorialGameMolecule,
    TutorialGameHeat,
  },
  emits: [],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  @Prop({ default: false }) readonly useFullSize!: boolean;
  @Prop({ default: '' }) readonly backgroundClass!: string;
  module: Module | null = null;
  trackingManager!: TrackingManager;
  startTime = Date.now();
  stepTime = Date.now();
  activeLevel: Idea | null = null;
  temperatureRise = 0;
  levelDone = false;
  tutorialNotShown = false;

  gameStep = GameStep.Select;
  GameStep = GameStep;
  gameState = GameState.Info;
  GameState = GameState;

  get tutorialList(): (string | ModuleInfoEntryData)[] {
    switch (this.gameStep) {
      case GameStep.Select:
        return [{ key: 'atmosphere', texture: 'gases.gif' }];
      case GameStep.Play:
        return [
          { key: 'light', texture: 'playing1.gif' },
          { key: 'heat', texture: 'playing2.gif' },
          { key: 'play', texture: 'temperatureBar.png' },
        ];
    }
    return [];
  }

  async mounted(): Promise<void> {
    //
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateModule);
    if (this.trackingManager) this.trackingManager.deregisterAll();
  }

  unmounted(): void {
    this.deregisterAll();
  }

  @Watch('gameState', { immediate: true })
  onGameStateChanged(): void {
    if (this.gameState === GameState.Info) {
      this.tutorialNotShown = false;
    }
  }

  @Watch('moduleId', { immediate: true })
  onModuleIdChanged(): void {
    if (this.moduleId) {
      moduleService.registerGetModuleById(
        this.moduleId,
        this.updateModule,
        EndpointAuthorisationType.PARTICIPANT,
        60 * 60
      );
    }
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      this.trackingManager = new TrackingManager(this.taskId, {
        gameStep: GameStep.Select,
        level: '',
        stars: 0,
        hitCount: 0,
        temperature: 0,
        moleculeState: {},
        obstacleState: {},
      });
    }
  }

  updateModule(module: Module): void {
    this.module = module;
  }

  async startLevel(level: Idea, temperatureRise: number): Promise<void> {
    this.temperatureRise = temperatureRise;
    this.gameStep = GameStep.Play;
    this.gameState = GameState.Info;
    this.activeLevel = level;

    if (this.trackingManager) {
      await this.trackingManager.createInstanceStep(
        level.id,
        TaskParticipantIterationStepStatesType.NEUTRAL,
        {
          level: level.id,
          state: level.parameter.state,
          isOwn: level.isOwn,
          temperatureRise: this.temperatureRise,
          selectTime: Date.now() - this.stepTime,
          playTime: Date.now() - this.startTime,
          stars: 0,
          hitCount: 0,
          temperature: 0,
          moleculeState: {},
          obstacleState: {},
        }
      );
      await this.trackingManager.saveIteration({
        gameStep: GameStep.Play,
        level: level,
      });
    }
    this.stepTime = Date.now();
  }

  async levelPlayed(state: PlayStateResult): Promise<void> {
    this.levelDone = true;

    if (this.trackingManager) {
      await this.trackingManager.saveIterationStep(
        {
          state: state,
          coolItTime: Date.now() - this.stepTime,
          playTime: Date.now() - this.startTime,
          stars: state.stars,
          hitCount: state.moleculeHitCount,
          temperature: state.temperature,
          moleculeState: state.moleculeState,
          obstacleState: state.obstacleState,
          finished: true,
        },
        state.stars === 3
          ? TaskParticipantIterationStepStatesType.CORRECT
          : TaskParticipantIterationStepStatesType.WRONG,
        state.stars,
        null,
        true,
        (item) => item.parameter.level === this.activeLevel?.id
      );
      await this.trackingManager.saveIteration(
        {},
        state.stars >= 2
          ? TaskParticipantIterationStatesType.WIN
          : TaskParticipantIterationStatesType.LOOS
      );

      if (
        this.trackingManager.state &&
        (!this.trackingManager.state.parameter.rate ||
          this.trackingManager.state.parameter.rate < state.stars)
      ) {
        this.trackingManager.setFinishedState(this.module, {
          rate: state.stars,
        });
      } else this.trackingManager.setFinishedState(this.module);
    }
    this.stepTime = Date.now();
  }

  replayedFinished(): void {
    this.gameStep = GameStep.Select;
    //this.gameState = GameState.Info;

    if (this.trackingManager) {
      this.trackingManager.saveIterationStep({
        replayTime: Date.now() - this.stepTime,
        playTime: Date.now() - this.startTime,
        finished: false,
      });
    }
    this.stepTime = Date.now();
  }
}
</script>

<style lang="scss" scoped>
.mapSpace {
  position: relative;
  width: 100%;
}
.tutorialButton {
  position: absolute;
  margin: 0;
  top: 0.4rem;
  left: 0.4rem;
  text-align: center;
  background-color: white;
  box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.1);
}
</style>
