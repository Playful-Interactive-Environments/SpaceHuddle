<template>
  <div ref="gameContainer" class="mapSpace">
    <!-- Tutorial/Info for Edit-Mode -->
    <module-info
      v-if="gameState === GameState.Info && module"
      translation-path="module.information.forestfires.participant.tutorial"
      image-directory="/assets/games/forestfires/tutorial"
      :module-info-entry-data-list="tutorialList"
      file-extension="svg"
      @infoRead="gameState = GameState.Game"
      :showTutorialOnlyOnce="module.parameter.showTutorialOnlyOnce"
    />
    <!-- Edit-Mode -->
    <PlacementState
      v-if="gameStep === GameStep.Build && gameState === GameState.Game"
      :taskId="taskId"
      @editFinished="editFinished"
    />
    <!-- Play-Mode -->
    <PLayState
      v-if="gameStep === GameStep.Play && gameState === GameState.Game"
      :taskId="taskId"
      @playFinished="playFinished"
    />
  </div>
</template>

<script lang="ts">
// Vue
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';

// spaceHuddle API and services
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as moduleService from '@/services/module-service';
import { Module } from '@/types/api/Module';
import * as cashService from '@/services/cash-service';

// Custom Modules
import PlacementState from '@/modules/information/forestfires/organisms/PlacementState.vue';
import PLayState from '@/modules/information/forestfires/organisms/PlayState.vue';
import ModuleInfo from '@/components/participant/molecules/ModuleInfo.vue';

enum GameStep {
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
    PlacementState,
    PLayState,
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

  // Flag which indicates if the window size has finished calculating.
  sizeCalculated = false;

  // The general state of the game (tutorial, playing, ...) and smaller steps (edit-mode, play-mode, ...) within those states.
  gameStep = GameStep.Build;
  GameStep = GameStep;
  gameState = GameState.Info;
  GameState = GameState;

  // Vue Callbacks for mounting and unmounting / loading and unloading.
  mounted(): void {
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
  get tutorialList(): string[] {
    switch (this.gameStep) {
      case GameStep.Build:
        return ['click', 'place', 'play', 'remove'];
      case GameStep.Play:
        return ['collect', 'hazards', 'info'];
    }
    return [];
  }

  // Callbacks when stages are finished.
  editFinished(): void {
    this.gameStep = GameStep.Play;
    this.gameState = GameState.Info;
  }

  playFinished(): void {
    this.gameStep = GameStep.Build;
    this.gameState = GameState.Info;
  }
}
</script>

<style lang="scss" scoped>
.mapSpace {
  position: relative;
}
</style>
