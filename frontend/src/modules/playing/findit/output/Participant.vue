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
    <CollectedState
      v-if="gameStep === GameStep.Collect && gameState === GameState.Game"
      :level="selectedLevel"
      :play-state-result="playStateResult"
      @replayFinished="replayFinished"
    />
    <el-button
      type="secondary"
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
import CollectedState from '@/modules/playing/findit/organisms/CollectedState.vue';
import { registerDomElement, unregisterDomElement } from '@/vunit';
import TaskParticipantIterationStatesType from '@/types/enum/TaskParticipantIterationStatesType';

export enum GameStep {
  Select = 'select',
  Build = 'build',
  Play = 'play',
  Collect = 'collect',
}

enum GameState {
  Info = 'info',
  Game = 'game',
}

@Options({
  components: {
    CollectedState,
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
  playStateResult: PlayStateResult | null = null;

  // Flag which indicates if the window size has finished calculating.
  sizeCalculated = false;

  // The general state of the game (tutorial, playing, ...) and smaller steps (edit-mode, play-mode, ...) within those states.
  gameStep = GameStep.Select;
  GameStep = GameStep;
  gameState = GameState.Game;
  GameState = GameState;

  trackingManager!: TrackingManager;

  gameConfig = gameConfig;

  // Vue Callbacks for mounting and unmounting / loading and unloading.
  domKey = '';
  mounted(): void {
    this.domKey = registerDomElement(
      this.$refs.gameContainer as HTMLElement,
      () => {
        document.body.style.overflowY = 'hidden';
        this.sizeCalculated = true;
      },
      500
    );
  }

  unmounted(): void {
    document.body.style.removeProperty('overflow-y');
    this.deregisterAll();
    unregisterDomElement(this.domKey);
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
      case GameStep.Select:
        return [
          { key: 'placing', texture: 'placing.gif' },
          { key: 'finding', texture: 'finding.gif' },
        ];
    }
    return [];
  }

  // Callbacks when stages are finished.
  async editFinished(
    newLevelId: string | null,
    result: BuildStateResult
  ): Promise<void> {
    this.gameStep = GameStep.Select;
    this.gameState = GameState.Info;

    if (this.trackingManager && newLevelId) {
      (result as any).step = GameStep.Build;
      await this.trackingManager.createInstanceStep(
        newLevelId,
        TaskParticipantIterationStepStatesType.NEUTRAL,
        result,
        1,
        null,
        true
      );
    }

    if (this.trackingManager) {
      await this.trackingManager.saveIteration({
        gameStep: this.gameStep,
        levelId: null,
      });
    }
  }

  async playFinished(result: PlayStateResult): Promise<void> {
    this.playStateResult = result;
    this.gameStep = GameStep.Collect;
    //this.gameState = GameState.Info;

    if (this.trackingManager && this.selectedLevel) {
      (result as any).step = GameStep.Play;
      (result as any).state = this.selectedLevel?.parameter.state;
      (result as any).isOwn = this.selectedLevel?.isOwn;
      await this.trackingManager.createInstanceStep(
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
      await this.trackingManager.saveIteration(
        {
          gameStep: this.gameStep,
          levelId: null,
        },
        result.stars >= 2
          ? TaskParticipantIterationStatesType.WIN
          : TaskParticipantIterationStatesType.LOOS
      );
      if (
        this.trackingManager.state &&
        (!this.trackingManager.state.parameter.rate ||
          this.trackingManager.state.parameter.rate < result.stars)
      ) {
        this.trackingManager.setFinishedState(this.module, {
          rate: result.stars,
        });
      } else this.trackingManager.setFinishedState(this.module);
    }
  }

  replayFinished(): void {
    this.gameStep = GameStep.Select;
    this.gameState = GameState.Info;
  }
}
</script>

<style lang="scss" scoped>
.gameSpace {
  position: relative;
}
.tutorialButton {
  position: absolute;
  margin: 0;
  bottom: 0.2rem;
  left: 0.2rem;
  text-align: center;
  background-color: transparent;
}
</style>
