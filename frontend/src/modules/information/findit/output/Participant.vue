<template>
  <div ref="gameContainer" class="gameSpace">
    <module-info
      v-if="gameState === GameState.Info && module"
      translation-path="module.information.findit.participant.tutorial"
      image-directory="/assets/games/forestfires/tutorial"
      :module-info-entry-data-list="tutorialList"
      :info-type="`find-it-${gameStep}`"
      @infoRead="gameState = GameState.Game"
      :showTutorialOnlyOnce="module.parameter.showTutorialOnlyOnce"
    />
    <SelectState
      v-if="gameStep === GameStep.Select && gameState === GameState.Game"
      :task-id="taskId"
      @selectionDone="levelSelected"
    />
    <BuildState
      v-if="gameStep === GameStep.Build && gameState === GameState.Game"
      :taskId="taskId"
      :gameConfig="gameConfig"
      @editFinished="editFinished"
    />
    <PlayState
      v-if="gameStep === GameStep.Play && gameState === GameState.Game"
      :taskId="taskId"
      :level-data="selectedLevel"
      :gameConfig="gameConfig"
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
import BuildState from '@/modules/information/findit/organisms/BuildState.vue';
import PlayState from '@/modules/information/findit/organisms/PlayState.vue';
import SelectState from '@/modules/information/findit/organisms/SelectState.vue';
import ModuleInfo, {
  ModuleInfoEntryData,
} from '@/components/participant/molecules/ModuleInfo.vue';
import Placeable from '@/modules/information/findit/types/Placeable';
import * as PIXI from 'pixi.js';

enum GameStep {
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
    BuildState,
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
  @Prop({ default: {} }) readonly gameConfig!: any;
  module: Module | null = null;
  selectedLevel: Placeable[] = [];
  spritesheet!: PIXI.Spritesheet;

  // Flag which indicates if the window size has finished calculating.
  sizeCalculated = false;

  // The general state of the game (tutorial, playing, ...) and smaller steps (edit-mode, play-mode, ...) within those states.
  gameStep = GameStep.Select;
  GameStep = GameStep;
  gameState = GameState.Info;
  GameState = GameState;

  // Vue Callbacks for mounting and unmounting / loading and unloading.
  mounted(): void {
    PIXI.Assets.load('/assets/games/forestfires/tutorial/animations.json').then(
      (sheet) => {
        this.spritesheet = sheet;
      }
    );
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
    cashService.deregisterAllGet(this.updateModule);
    PIXI.Assets.unload('/assets/games/forestfires/tutorial/animations.json');
  }

  levelSelected(level: Placeable[]) {
    this.selectedLevel = level;
    this.gameState = GameState.Info;
    this.gameStep = level.length === 0 ? GameStep.Build : GameStep.Play;
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

  // Get the filenames for the tutorial images. Should all have the same file extension!
  get tutorialList(): (string | ModuleInfoEntryData)[] {
    switch (this.gameStep) {
      case GameStep.Build:
        return [
          {
            key: 'click',
            texture: 'click.png',
          },
          {
            key: 'place',
            texture: 'select.jpg',
          },
          {
            key: 'move',
            texture: this.spritesheet.animations['drag'], //'drag.json',
          },
          {
            key: 'rotate',
            texture: this.spritesheet.animations['rotate'],
          },
          {
            key: 'remove',
            texture: 'delete.jpg',
          },
        ];
      case GameStep.Play:
        return [
          { key: 'info', texture: 'info.jpg' },
          { key: 'hazards', texture: 'select.jpg' },
          { key: 'collect', texture: 'collect.jpg' },
        ];
    }
    return [];
  }

  // Callbacks when stages are finished.
  editFinished(): void {
    this.gameStep = GameStep.Select;
    this.gameState = GameState.Info;
  }

  playFinished(): void {
    this.gameStep = GameStep.Select;
    this.gameState = GameState.Info;
  }
}
</script>

<style lang="scss" scoped>
.gameSpace {
  position: relative;
}
</style>
