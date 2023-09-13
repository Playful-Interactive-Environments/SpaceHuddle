<template>
  <div ref="gameContainer" class="mapSpace">
    <module-info
      v-if="gameState === GameState.Info && module"
      translation-path="module.playing.coolit.participant.tutorial"
      image-directory="/assets/games/coolit/tutorial"
      :module-info-entry-data-list="tutorialList"
      @infoRead="gameState = GameState.Game"
      :info-type="`cool-it-${gameStep}`"
      :showTutorialOnlyOnce="module.parameter.showTutorialOnlyOnce"
    />
    <select-level
      v-if="gameStep === GameStep.Select && gameState === GameState.Game"
      :tracking-manager="trackingManager"
      :task-id="taskId"
      :module="module"
      @play="startLevel"
    />
    <play-level
      v-if="gameStep === GameStep.Play && gameState === GameState.Game"
      :level="activeLevel"
      :tracking-manager="trackingManager"
      @finished="levelPlayed"
    />
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
import { ParticleState } from '@/modules/playing/coolit/organisms/PlayLevel.vue';

export enum GameStep {
  Select = 'select',
  Play = 'play',
}

enum GameState {
  Info = 'info',
  Game = 'game',
}

@Options({
  components: {
    PlayLevel,
    SelectLevel,
    ModuleInfo,
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
  activeLevel = 0;
  particleState: { [key: string]: ParticleState } = {
    carbonDioxide: { totalCount: 18, movedCount: 0 },
    dust: { totalCount: 10, movedCount: 0 },
    methane: { totalCount: 12, movedCount: 0 },
    microplastic: { totalCount: 5, movedCount: 0 },
  };

  gameStep = GameStep.Select;
  GameStep = GameStep;
  gameState = GameState.Info;
  GameState = GameState;

  get tutorialList(): (string | ModuleInfoEntryData)[] {
    switch (this.gameStep) {
      case GameStep.Select:
        return [{ key: 'select', texture: 'respiratory-sphere.jpg' }];
      case GameStep.Play:
        return [{ key: 'play', texture: 'respiratory-sphere.jpg' }];
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
        level: 0,
        particleState: {},
        rate: 0,
      });
    }
  }

  updateModule(module: Module): void {
    this.module = module;
  }

  startLevel(level: number): void {
    this.gameStep = GameStep.Play;
    this.gameState = GameState.Info;
    this.activeLevel = level;

    if (this.trackingManager) {
      this.trackingManager.createInstanceStep(
        null,
        TaskParticipantIterationStepStatesType.NEUTRAL,
        {
          level: level,
          selectTime: Date.now() - this.stepTime,
          playTime: Date.now() - this.startTime,
          particleState: {},
          rate: 0,
        }
      );
      this.trackingManager.saveIteration({
        gameStep: GameStep.Play,
        level: level,
      });
    }
    this.stepTime = Date.now();
  }

  levelPlayed(particleState: { [key: string]: ParticleState }): void {
    this.particleState = particleState;
    this.gameStep = GameStep.Select;
    this.gameState = GameState.Info;

    if (this.trackingManager) {
      this.trackingManager.saveIterationStep({
        particleState: particleState,
        cleanupTime: Date.now() - this.stepTime,
        playTime: Date.now() - this.startTime,
      });
      this.trackingManager.saveIteration({
        particleState: particleState,
      });
      this.trackingManager.setFinishedState(this.module);
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
</style>
