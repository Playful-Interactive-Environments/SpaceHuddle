<template>
  <div ref="gameContainer" class="mapSpace">
    <drive-to-location
      v-if="gameState === GameStep.Drive && sizeCalculated && module"
      :parameter="module.parameter"
      :vehicle="vehicle"
      v-on:goalReached="goalReached"
    />
    <clean-up-particles
      v-if="gameState === GameStep.CleanUp"
      :vehicle="vehicle"
      :trackingData="trackingData"
    />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import DriveToLocation from '@/modules/information/cleanup/organisms/DriveToLocation.vue';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as moduleService from '@/services/module-service';
import { Module } from '@/types/api/Module';
import * as cashService from '@/services/cash-service';
import CleanUpParticles from '@/modules/information/cleanup/organisms/CleanUpParticles.vue';

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

  trackingData: number[] = [20, 40, 100, 100, 50, 30];
  gameState = GameStep.CleanUp;
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

  goalReached(trackingData): void {
    this.trackingData = trackingData;
    this.gameState = GameStep.CleanUp;
  }
}
</script>

<style lang="scss" scoped>
.mapSpace {
  position: relative;
}
</style>
