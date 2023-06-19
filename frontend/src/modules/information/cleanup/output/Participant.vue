<template>
  <div ref="gameContainer" class="mapSpace">
    <select-challenge v-if="gameState === GameStep.Select" @play="startGame" />
    <drive-to-location
      v-if="gameState === GameStep.Drive && sizeCalculated && module"
      :parameter="module.parameter"
      :vehicle="vehicle"
      :vehicle-type="vehicleType"
      v-on:goalReached="goalReached"
    />
    <clean-up-particles
      v-if="gameState === GameStep.CleanUp"
      :vehicle="vehicle"
      :vehicle-type="vehicleType"
      :trackingData="trackingData"
      @finished="cleanupFinished"
    />
    <show-result
      v-if="gameState === GameStep.Result"
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

enum GameStep {
  Select,
  Drive,
  CleanUp,
  Result,
}

@Options({
  components: {
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
    { speed: 20, persons: 1, combustion: 0.0005 },
    { speed: 40, persons: 1, combustion: 0.001 },
    { speed: 100, persons: 1, combustion: 0.002 },
    { speed: 100, persons: 1, combustion: 0.002 },
    { speed: 50, persons: 1, combustion: 0.001 },
    { speed: 30, persons: 1, combustion: 0.0005 },
  ];
  gameState = GameStep.Select;
  GameStep = GameStep;

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
    this.gameState = GameStep.Drive;
  }

  goalReached(trackingData): void {
    this.trackingData = trackingData;
    this.gameState = GameStep.CleanUp;
  }

  cleanupFinished(particleState: { [key: string]: ParticleState }): void {
    this.particleState = particleState;
    this.gameState = GameStep.Result;
  }
}
</script>

<style lang="scss" scoped>
.mapSpace {
  position: relative;
}
</style>
