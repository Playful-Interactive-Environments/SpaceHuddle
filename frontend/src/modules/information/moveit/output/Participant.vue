<template>
  <div ref="gameContainer" class="mapSpace">
    <module-info
      v-if="gameState === GameState.Info && module"
      translation-path="module.information.moveit.participant.tutorial"
      image-directory="/assets/games/moveit/tutorial"
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
      :tracking-manager="trackingManager"
      v-on:goalReached="goalReached"
    />
    <clean-up-particles
      v-if="gameStep === GameStep.CleanUp && gameState === GameState.Game"
      :vehicle="vehicle"
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
} from '@/modules/information/moveit/organisms/DriveToLocation.vue';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as moduleService from '@/services/module-service';
import { Module } from '@/types/api/Module';
import * as cashService from '@/services/cash-service';
import CleanUpParticles, {
  ParticleState,
} from '@/modules/information/moveit/organisms/CleanUpParticles.vue';
import SelectChallenge from '@/modules/information/moveit/organisms/SelectChallenge.vue';
import ShowResult from '@/modules/information/moveit/organisms/ShowResult.vue';
import ModuleInfo from '@/components/participant/molecules/ModuleInfo.vue';
import * as formulas from '@/modules/information/moveit/utils/formulas';
import * as configCalculation from '@/modules/information/moveit/utils/configCalculation';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import { MissionProgress } from '@/modules/information/missionmap/types/MissionProgress';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import TaskParticipantIterationStatesType from '@/types/enum/TaskParticipantIterationStatesType';
import * as vehicleCalculation from '@/modules/information/moveit/types/Vehicle';

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
  vehicle: vehicleCalculation.Vehicle = { category: 'car', type: 'sport' };
  particleState: { [key: string]: ParticleState } = {
    carbonDioxide: { totalCount: 18, collectedCount: 15 },
    dust: { totalCount: 10, collectedCount: 9 },
    methane: { totalCount: 12, collectedCount: 9 },
    microplastic: { totalCount: 5, collectedCount: 1 },
  };
  startTime = Date.now();
  stepTime = Date.now();
  inputTaskId = '';

  trackingData: TrackingData[] = [];
  gameStep = GameStep.Select;
  GameStep = GameStep;
  gameState = GameState.Info;
  GameState = GameState;

  trackingManager!: TrackingManager;
  missionProgress!: MissionProgress;

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
        if (this.vehicle.category === 'bus')
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
      { speed: 50, persons: 1, distance: 0.02 },
      { speed: 100, persons: 1, distance: 0.04 },
      { speed: 200, persons: 1, distance: 0.08 },
      { speed: 300, persons: 1, distance: 0.12 },
      { speed: 300, persons: 1, distance: 0.12 },
      { speed: 300, persons: 1, distance: 0.12 },
      { speed: 300, persons: 1, distance: 0.12 },
      { speed: 300, persons: 1, distance: 0.12 },
      { speed: 300, persons: 1, distance: 0.12 },
      { speed: 300, persons: 1, distance: 0.12 },
      { speed: 300, persons: 1, distance: 0.12 },
      { speed: 200, persons: 1, distance: 0.08 },
      { speed: 50, persons: 1, distance: 0.02 },
      { speed: 30, persons: 1, distance: 0.01 },
    ];
    const vehicleParameter = configCalculation.getVehicleParameter(
      this.vehicle
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
    cashService.deregisterAllGet(this.updateTask);
    if (this.trackingManager) this.trackingManager.deregisterAll();
    if (this.missionProgress) this.missionProgress.deregisterAll();
  }

  unmounted(): void {
    this.deregisterAll();
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      this.trackingManager = new TrackingManager(this.taskId, {
        gameStep: GameStep.Select,
        vehicle: this.vehicle,
        trackingData: [],
        particleState: {},
        rate: 0,
      });
      taskService.registerGetTaskById(
        this.taskId,
        this.updateTask,
        EndpointAuthorisationType.PARTICIPANT,
        60 * 60
      );
    }
  }

  updateTask(task: Task): void {
    if (task.parameter.input.length > 0)
      this.inputTaskId = task.parameter.input[0].view.id;
  }

  @Watch('inputTaskId', { immediate: true })
  onInputTaskIdChanged(): void {
    if (this.inputTaskId) {
      if (this.missionProgress) this.missionProgress.deregisterAll();
      this.missionProgress = new MissionProgress(
        this.inputTaskId,
        null,
        EndpointAuthorisationType.PARTICIPANT,
        true
      );
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

  newRun(): void {
    if (this.trackingManager) {
      this.trackingManager.saveIteration(
        {
          gameStep: GameStep.Select,
          vehicle: this.vehicle,
          trackingData: [],
          particleState: {},
          rate: 0,
        },
        TaskParticipantIterationStatesType.IN_PROGRESS
      );
    }
  }

  startGame(vehicle: vehicleCalculation.Vehicle): void {
    this.vehicle = vehicle;
    this.gameStep = GameStep.Drive;
    this.gameState = GameState.Info;

    if (this.trackingManager) {
      this.trackingManager.createInstanceStep(
        null,
        TaskParticipantIterationStepStatesType.NEUTRAL,
        {
          vehicle: vehicle,
          selectTime: Date.now() - this.stepTime,
          playTime: Date.now() - this.startTime,
          trackingData: [],
          particleState: {},
          rate: 0,
        }
      );
      this.trackingManager.saveIteration({
        gameStep: GameStep.Drive,
        vehicle: vehicle,
      });
    }
    this.stepTime = Date.now();
  }

  goalReached(trackingData): void {
    this.trackingData = trackingData;
    this.gameStep = GameStep.CleanUp;
    this.gameState = GameState.Info;

    if (this.trackingManager) {
      this.trackingManager.saveIterationStep({
        trackingData: trackingData,
        driveTime: Date.now() - this.stepTime,
        playTime: Date.now() - this.startTime,
      });
      this.trackingManager.saveIteration({
        gameStep: GameStep.CleanUp,
        trackingData: trackingData,
      });
    }
    this.stepTime = Date.now();
  }

  cleanupFinished(particleState: { [key: string]: ParticleState }): void {
    this.particleState = particleState;
    this.gameStep = GameStep.Result;
    this.gameState = GameState.Info;

    if (this.trackingManager) {
      this.trackingManager.saveIterationStep({
        particleState: particleState,
        cleanupTime: Date.now() - this.stepTime,
        playTime: Date.now() - this.startTime,
      });
      this.trackingManager.saveIteration({
        gameStep: GameStep.Result,
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
}
</style>
