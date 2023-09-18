<template>
  <div ref="gameContainer" class="gameSpace">
    <module-info
      v-if="gameState === GameState.Info && module"
      translation-path="module.playing.findit.participant.tutorial"
      image-directory="/assets/games/findit/tutorial"
      :module-info-entry-data-list="tutorialList"
      :info-type="`find-it-${gameStep}`"
      @infoRead="gameState = GameState.Game"
      :showTutorialOnlyOnce="module.parameter.showTutorialOnlyOnce"
    />
    <SelectState
      v-if="gameStep === GameStep.Select && gameState === GameState.Game"
      :task-id="taskId"
      :gameConfig="gameConfig"
      :tracking-manager="trackingManager"
      @selectionDone="levelSelected"
    />
    <LevelBuilder
      v-if="gameStep === GameStep.Build && gameState === GameState.Game"
      :taskId="taskId"
      :level-type="selectedLevelType"
      :gameConfig="gameConfig"
      @editFinished="editFinished"
    />
    <PlayState
      v-if="gameStep === GameStep.Play && gameState === GameState.Game"
      :taskId="taskId"
      :level="selectedLevel"
      @playFinished="playFinished"
    />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as moduleService from '@/services/module-service';
import { Module } from '@/types/api/Module';
import * as cashService from '@/services/cash-service';
import LevelBuilder, {
  BuildStateResult,
} from '@/components/shared/organisms/game/LevelBuilder.vue';
import PlayState, {
  PlayStateResult,
} from '@/modules/playing/findit/organisms/PlayState.vue';
import SelectState from '@/modules/playing/findit/organisms/SelectState.vue';
import ModuleInfo, {
  ModuleInfoEntryData,
} from '@/components/participant/molecules/ModuleInfo.vue';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import { Idea } from '@/types/api/Idea';
import gameConfig from '@/modules/playing/findit/data/gameConfig.json';

export enum GameStep {
  Select = 'select',
  Build = 'build',
  Play = 'play',
}

enum GameState {
  Info = 'info',
  Game = 'game',
}

@Options({
  components: {
    ModuleInfo,
    LevelBuilder,
    PlayState,
    SelectState,
  },
  emits: ['update:useFullSize'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  @Prop({ default: false }) readonly useFullSize!: boolean;
  @Prop({ default: '' }) readonly backgroundClass!: string;
  module: Module | null = null;
  selectedLevel: Idea | null = null;
  selectedLevelType: string | null = null;
  //spritesheet!: PIXI.Spritesheet;

  // Flag which indicates if the window size has finished calculating.
  sizeCalculated = false;

  // The general state of the game (tutorial, playing, ...) and smaller steps (edit-mode, play-mode, ...) within those states.
  gameStep = GameStep.Select;
  GameStep = GameStep;
  gameState = GameState.Info;
  GameState = GameState;

  trackingManager!: TrackingManager;

  gameConfig = gameConfig;

  // Vue Callbacks for mounting and unmounting / loading and unloading.
  mounted(): void {
    /*PIXI.Assets.load('/assets/games/findit/tutorial/animations.json').then(
      (sheet) => {
        this.spritesheet = sheet;
      }
    );*/
    setTimeout(() => {
      const dom = this.$refs.gameContainer as HTMLElement;
      if (dom) {
        const targetWidth = dom.parentElement?.offsetWidth;
        const targetHeight = dom.parentElement?.offsetHeight;
        if (targetWidth && targetHeight) {
          (dom as any).style.width = `${targetWidth}px`;
          (dom as any).style.height = `${targetHeight}px`;
        }
        this.sizeCalculated = true;
      }
    }, 500);
  }

  unmounted(): void {
    this.deregisterAll();
    //PIXI.Assets.unload('/assets/games/findit/tutorial/animations.json');
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateModule);
    if (this.trackingManager) this.trackingManager.deregisterAll();
  }

  levelSelected(levelType: string, level: Idea | null) {
    this.selectedLevelType = levelType;
    this.selectedLevel = level;
    this.gameState = GameState.Info;
    this.gameStep = !level ? GameStep.Build : GameStep.Play;

    if (this.trackingManager) {
      this.trackingManager.saveIteration({
        gameStep: this.gameStep,
        levelId: level?.id,
      });
    }
  }

  // Watch and update the current module in case any changes happen.
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

  updateModule(module: Module): void {
    this.module = module;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      this.trackingManager = new TrackingManager(this.taskId, {});
    }
  }

  // Get the filenames for the tutorial images. Should all have the same file extension!
  get tutorialList(): (string | ModuleInfoEntryData)[] {
    switch (this.gameStep) {
      case GameStep.Build:
        return [{ key: 'placing', texture: 'placing.gif' }];
      case GameStep.Play:
        return [{ key: 'finding', texture: 'finding.gif' }];
    }
    return [];
  }

  // Callbacks when stages are finished.
  editFinished(newLevelId: string | null, result: BuildStateResult): void {
    this.gameStep = GameStep.Select;
    this.gameState = GameState.Info;

    if (this.trackingManager && newLevelId) {
      (result as any).step = GameStep.Build;
      this.trackingManager.createInstanceStep(
        newLevelId,
        TaskParticipantIterationStepStatesType.NEUTRAL,
        result,
        1,
        null,
        true
      );
    }

    if (this.trackingManager) {
      this.trackingManager.saveIteration({
        gameStep: this.gameStep,
        levelId: null,
      });
    }
  }

  playFinished(result: PlayStateResult): void {
    this.gameStep = GameStep.Select;
    this.gameState = GameState.Info;

    if (this.trackingManager && this.selectedLevel) {
      (result as any).step = GameStep.Play;
      this.trackingManager.createInstanceStep(
        this.selectedLevel.id,
        result.collected === result.total
          ? TaskParticipantIterationStepStatesType.CORRECT
          : TaskParticipantIterationStepStatesType.WRONG,
        result,
        result.stars,
        null,
        true,
        (item) => item.parameter.step === GameStep.Play
      );
    }
    this.selectedLevel = null;

    if (this.trackingManager) {
      this.trackingManager.saveIteration({
        gameStep: this.gameStep,
        levelId: null,
      });
      this.trackingManager.setFinishedState(this.module);
    }

    if (
      this.trackingManager &&
      this.trackingManager.state &&
      this.trackingManager.state.parameter.rate < result.stars
    ) {
      this.trackingManager.saveState({ rate: result.stars });
    }
  }
}
</script>

<style lang="scss" scoped>
.gameSpace {
  position: relative;
}
</style>