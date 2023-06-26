<template>
  <div ref="gameContainer" class="mapSpace">
    <!--<module-info
      v-if="gameStep === GameStep.Select && gameState === GameState.Info"
      :module-info-entry-data-list="[
        {
          imageUrl: '/assets/games/cleanup/tutorial/traffic.jpg',
          title: 'traffic',
          text: $t('module.information.cleanup.participant.tutorial.traffic'),
        },
        {
          imageUrl: '/assets/games/cleanup/tutorial/vehicleType.jpg',
          title: 'vehicleType',
          text: $t('module.information.cleanup.participant.tutorial.vehicleType'),
        },
        {
          imageUrl: '/assets/games/cleanup/tutorial/emissionStats.jpg',
          title: 'emissionStats',
          text: $t('module.information.cleanup.participant.tutorial.emissionStats'),
        },
        {
          imageUrl: '/assets/games/cleanup/tutorial/emissionStatsVariable.jpg',
          title: 'emissionStatsVariable',
          text: $t('module.information.cleanup.participant.tutorial.emissionStatsVariable'),
        },
      ]"
      @infoRead="gameState = GameState.Game"
    />-->
    <module-info
      v-if="gameStep === GameStep.Select && gameState === GameState.Info"
      translation-path="module.information.cleanup.participant.tutorial"
      image-directory="/assets/games/cleanup/tutorial"
      :module-info-entry-data-list="[
        'traffic',
        'vehicleType',
        'emissionStats',
        'emissionStatsVariable',
      ]"
      @infoRead="gameState = GameState.Game"
    />
    <select-challenge
      v-if="gameStep === GameStep.Select && gameState === GameState.Game"
      @play="startGame"
    />
    <module-info
      v-if="gameStep === GameStep.Drive && gameState === GameState.Info"
      translation-path="module.information.cleanup.participant.tutorial"
      image-directory="/assets/games/cleanup/tutorial"
      :module-info-entry-data-list="
        vehicle === 'bus' ? ['speed', 'personCount', 'addPersons'] : ['speed']
      "
      @infoRead="gameState = GameState.Game"
    />
    <drive-to-location
      v-if="gameStep === GameStep.Drive && sizeCalculated && module"
      :parameter="module.parameter"
      :vehicle="vehicle"
      :vehicle-type="vehicleType"
      v-on:goalReached="goalReached"
    />
    <module-info
      v-if="gameStep === GameStep.CleanUp && gameState === GameState.Info"
      translation-path="module.information.cleanup.participant.tutorial"
      image-directory="/assets/games/cleanup/tutorial"
      :module-info-entry-data-list="['cleanUp', 'maxCount', 'particleTypes']"
      @infoRead="gameState = GameState.Game"
    />
    <clean-up-particles
      v-if="gameStep === GameStep.CleanUp && gameState === GameState.Game"
      :vehicle="vehicle"
      :vehicle-type="vehicleType"
      :trackingData="trackingData"
      @finished="cleanupFinished"
    />
    <module-info
      v-if="gameStep === GameStep.Result && gameState === GameState.Info"
      translation-path="module.information.cleanup.participant.tutorial"
      image-directory="/assets/games/cleanup/tutorial"
      :module-info-entry-data-list="['improveSpeed', 'improveElectricity']"
      @infoRead="gameState = GameState.Game"
    />
    <show-result
      v-if="gameStep === GameStep.Result && gameState === GameState.Game"
      :particle-state="particleState"
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

enum GameStep {
  Select,
  Drive,
  CleanUp,
  Result,
}

enum GameState {
  Info,
  Game,
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

  trackingData: TrackingData[] = [
    { speed: 20, persons: 1, consumption: 0.0005, distance: 0.001 },
    { speed: 40, persons: 1, consumption: 0.001, distance: 0.002 },
    { speed: 100, persons: 1, consumption: 0.002, distance: 0.004 },
    { speed: 100, persons: 1, consumption: 0.002, distance: 0.004 },
    { speed: 50, persons: 1, consumption: 0.001, distance: 0.002 },
    { speed: 30, persons: 1, consumption: 0.0005, distance: 0.001 },
  ];
  gameStep = GameStep.Select;
  GameStep = GameStep;
  gameState = GameState.Info;
  GameState = GameState;

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

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateModule);
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

  updateModule(module: Module): void {
    this.module = module;
  }

  startGame(vehicle: any): void {
    this.vehicle = vehicle.category;
    this.vehicleType = vehicle.type;
    this.gameStep = GameStep.Drive;
    this.gameState = GameState.Info;
  }

  goalReached(trackingData): void {
    this.trackingData = trackingData;
    this.gameStep = GameStep.CleanUp;
    this.gameState = GameState.Info;
  }

  cleanupFinished(particleState: { [key: string]: ParticleState }): void {
    this.particleState = particleState;
    this.gameStep = GameStep.Result;
    this.gameState = GameState.Info;
  }
}
</script>

<style lang="scss" scoped>
.mapSpace {
  position: relative;
}
</style>
