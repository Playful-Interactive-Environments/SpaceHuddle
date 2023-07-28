<template>
  <div ref="gameContainer" class="mapSpace">
    <module-info
      v-if="gameState === GameState.Info && module"
      translation-path="module.information.cleanup.participant.tutorial"
      image-directory="/assets/games/cleanup/tutorial"
      :module-info-entry-data-list="tutorialList"
      @infoRead="gameState = GameState.Game"
      :info-type="`clean-up-${gameStep}`"
      :showTutorialOnlyOnce="module.parameter.showTutorialOnlyOnce"
    />
    <select-challenge
      v-if="gameStep === GameStep.Select && gameState === GameState.Game"
      :tracking-manager="trackingManager"
      @play="startGame"
    />
    <drive-to-location
      v-if="
        gameStep === GameStep.Drive &&
        sizeCalculated &&
        module &&
        gameState === GameState.Game
      "
      :parameter="module.parameter"
      :vehicle="vehicle"
      :vehicle-type="vehicleType"
      :tracking-manager="trackingManager"
      v-on:goalReached="goalReached"
    />
    <clean-up-particles
      v-if="gameStep === GameStep.CleanUp && gameState === GameState.Game"
      :vehicle="vehicle"
      :vehicle-type="vehicleType"
      :trackingData="trackingData"
      :tracking-manager="trackingManager"
      @finished="cleanupFinished"
    />
    <show-result
      v-if="gameStep === GameStep.Result && gameState === GameState.Game"
      :particle-state="particleState"
      :tracking-manager="trackingManager"
    />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import DriveToLocation, {
  TrackingData,
} from '@/modules/information/cleanup/organisms/DriveToLocation.vue';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as moduleService from '@/services/module-service';
import { Module } from '@/types/api/Module';
import * as cashService from '@/services/cash-service';
import CleanUpParticles, {
  ParticleState,
} from '@/modules/information/cleanup/organisms/CleanUpParticles.vue';
import SelectChallenge from '@/modules/information/cleanup/organisms/SelectChallenge.vue';
import ShowResult from '@/modules/information/cleanup/organisms/ShowResult.vue';
import ModuleInfo from '@/components/participant/molecules/ModuleInfo.vue';
import * as formulas from '@/modules/information/cleanup/utils/formulas';
import * as configCalculation from '@/modules/information/cleanup/utils/configCalculation';
import { TrackingManager } from '@/types/tracking/TrackingManager';

enum GameStep {
  Select = 'select',
  Drive = 'drive',
  CleanUp = 'clean-up',
  Result = 'result',
}

enum GameState {
  Info = 'info',
  Game = 'game',
}

@Options({
  components: {
    ModuleInfo,
    CleanUpParticles,
    DriveToLocation,
    SelectChallenge,
    ShowResult,
  },
  emits: ['update:useFullSize'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  @Prop({ default: false }) readonly useFullSize!: boolean;
  @Prop({ default: '' }) readonly backgroundClass!: string;
  sizeCalculated = false;
  module: Module | null = null;
  vehicle = 'car';
  vehicleType = 'sport';
  particleState: { [key: string]: ParticleState } = {
    carbonDioxide: { totalCount: 18, collectedCount: 15 },
    dust: { totalCount: 10, collectedCount: 9 },
    methane: { totalCount: 12, collectedCount: 9 },
    microplastic: { totalCount: 5, collectedCount: 1 },
  };

  trackingData: TrackingData[] = [];
  gameStep = GameStep.Select;
  GameStep = GameStep;
  gameState = GameState.Info;
  GameState = GameState;

  trackingManager!: TrackingManager;

  get tutorialList(): string[] {
    switch (this.gameStep) {
      case GameStep.Select:
        return [
          'traffic',
          'vehicleType',
          'emissionStats',
          'emissionStatsVariable',
        ];
      case GameStep.Drive:
        if (this.vehicle === 'bus')
          return ['speed', 'threshold', 'personCount', 'addPersons'];
        return ['speed', 'threshold'];
      case GameStep.CleanUp:
        return ['cleanUp', 'maxCount', 'particleTypes'];
      case GameStep.Result:
        return ['improveSpeed', 'improveElectricity'];
    }
    return [];
  }

  mounted(): void {
    const testData = [
      { speed: 20, persons: 1, distance: 0.001 },
      { speed: 40, persons: 1, distance: 0.002 },
      { speed: 100, persons: 1, distance: 0.004 },
      { speed: 300, persons: 1, distance: 0.012 },
      { speed: 300, persons: 1, distance: 0.012 },
      { speed: 300, persons: 1, distance: 0.012 },
      { speed: 300, persons: 1, distance: 0.012 },
      { speed: 300, persons: 1, distance: 0.012 },
      { speed: 300, persons: 1, distance: 0.012 },
      { speed: 300, persons: 1, distance: 0.012 },
      { speed: 300, persons: 1, distance: 0.012 },
      { speed: 100, persons: 1, distance: 0.004 },
      { speed: 50, persons: 1, distance: 0.002 },
      { speed: 30, persons: 1, distance: 0.001 },
    ];
    const vehicleParameter = configCalculation.getVehicleParameter(
      this.vehicle,
      this.vehicleType
    );
    for (const test of testData) {
      this.trackingData.push({
        speed: test.speed,
        distance: test.distance,
        persons: test.persons,
        consumption: formulas.consumption(
          test.speed,
          test.distance,
          vehicleParameter
        ),
        tireWareRate: formulas.tireWareRate(
          test.speed,
          test.distance,
          vehicleParameter
        ),
      });
    }

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

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateModule);
    if (this.trackingManager) this.trackingManager.deregisterAll();
  }

  unmounted(): void {
    this.deregisterAll();
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      this.trackingManager = new TrackingManager(this.taskId, {
        gameStep: GameStep.Select,
        vehicle: {
          category: this.vehicle,
          type: this.vehicleType,
        },
        trackingData: [],
        particleState: {},
        rate: 0,
      });
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

  updateModule(module: Module): void {
    this.module = module;
  }

  startGame(vehicle: any): void {
    this.vehicle = vehicle.category;
    this.vehicleType = vehicle.type;
    this.gameStep = GameStep.Drive;
    this.gameState = GameState.Info;

    if (this.trackingManager) {
      this.trackingManager.saveIteration({
        gameStep: GameStep.Drive,
        vehicle: vehicle,
      });
    }
  }

  goalReached(trackingData): void {
    this.trackingData = trackingData;
    this.gameStep = GameStep.CleanUp;
    this.gameState = GameState.Info;

    if (this.trackingManager) {
      this.trackingManager.saveIteration({
        gameStep: GameStep.CleanUp,
        trackingData: trackingData,
      });
    }
  }

  cleanupFinished(particleState: { [key: string]: ParticleState }): void {
    this.particleState = particleState;
    this.gameStep = GameStep.Result;
    this.gameState = GameState.Info;

    if (this.trackingManager) {
      this.trackingManager.saveIteration({
        gameStep: GameStep.Result,
        particleState: particleState,
      });
      this.trackingManager.setFinishedState(this.module);
    }
  }
}
</script>

<style lang="scss" scoped>
.mapSpace {
  position: relative;
}
</style>
