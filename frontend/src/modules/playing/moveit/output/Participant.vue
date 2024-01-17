<template>
  <div ref="gameContainer" class="mapSpace">
    <module-info
      v-if="gameState === GameState.Info && module"
      translation-path="module.playing.moveit.participant.tutorial"
      image-directory="/assets/games/moveit/tutorial"
      :module-info-entry-data-list="tutorialList"
      @infoRead="gameState = GameState.Game"
      :info-type="`move-it-${gameStep}`"
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
      :navigation="navigationType"
      :moving-type="movingType"
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
      :trackingData="trackingData"
      :tracking-manager="trackingManager"
      :vehicle="vehicle"
    />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import DriveToLocation from '@/modules/playing/moveit/organisms/DriveToLocation.vue';
import { TrackingData } from '@/modules/playing/moveit/utils/trackingData';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as moduleService from '@/services/module-service';
import { Module } from '@/types/api/Module';
import * as cashService from '@/services/cash-service';
import CleanUpParticles, {
  ParticleState,
} from '@/modules/playing/moveit/organisms/CleanUpParticles.vue';
import SelectChallenge, {
  MovingType,
  NavigationType,
} from '@/modules/playing/moveit/organisms/SelectChallenge.vue';
import ShowResult from '@/modules/playing/moveit/organisms/ShowResult.vue';
import ModuleInfo, {
  ModuleInfoEntryData,
} from '@/components/participant/molecules/ModuleInfo.vue';
import * as formulas from '@/modules/playing/moveit/utils/formulas';
import * as configCalculation from '@/modules/playing/moveit/utils/configCalculation';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import { MissionProgress } from '@/modules/brainstorming/missionmap/types/MissionProgress';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import * as vehicleCalculation from '@/modules/playing/moveit/types/Vehicle';
import { FeatureCollection } from 'geojson';
import * as turf from '@turf/turf';
import * as particleStateUtil from '@/modules/playing/moveit/utils/particleState';
import TaskParticipantIterationStatesType from '@/types/enum/TaskParticipantIterationStatesType';
import { registerDomElement, unregisterDomElement } from '@/vunit';

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
    carbonDioxide: {
      totalCount: 18,
      collectedCount: 15,
      timelineOutside: [],
      timelineCollected: [],
      timelineInput: [],
    },
    dust: {
      totalCount: 10,
      collectedCount: 9,
      timelineOutside: [],
      timelineCollected: [],
      timelineInput: [],
    },
    methane: {
      totalCount: 12,
      collectedCount: 9,
      timelineOutside: [],
      timelineCollected: [],
      timelineInput: [],
    },
    microplastic: {
      totalCount: 5,
      collectedCount: 1,
      timelineOutside: [],
      timelineCollected: [],
      timelineInput: [],
    },
  };
  startTime = Date.now();
  stepTime = Date.now();
  inputTaskId = '';
  navigationType: NavigationType = NavigationType.joystick;
  movingType: MovingType = MovingType.free;

  trackingData: TrackingData[] = [];
  gameStep = GameStep.Select;
  GameStep = GameStep;
  gameState = GameState.Info;
  GameState = GameState;

  trackingManager!: TrackingManager;
  missionProgress!: MissionProgress;

  get tutorialList(): (string | ModuleInfoEntryData)[] {
    switch (this.gameStep) {
      case GameStep.Select:
        return [
          { key: 'select', texture: 'select.jpg' },
          { key: 'statistics', texture: 'stats.gif' },
        ];
      case GameStep.Drive:
        if (this.vehicle.category === 'bus') {
          return [
            { key: 'drive', texture: 'drive.gif' },
            { key: 'statsGame', texture: 'statsGame.png' },
            this.NavTutorial,
            { key: 'bus', texture: 'bus.jpg' },
          ];
        }
        return [
          { key: 'drive', texture: 'drive.gif' },
          { key: 'statsGame', texture: 'statsGame.png' },
          this.NavTutorial,
        ];
      case GameStep.CleanUp:
        return [
          { key: 'cleanUp', texture: 'cleanUp.gif' },
          { key: 'nudging', texture: 'nudging.gif' },
        ];
      case GameStep.Result:
        return [{ key: 'improve', texture: 'improve.gif' }];
    }
    return [];
  }

  get NavTutorial(): any {
    switch (this.navigationType) {
      case NavigationType.speed:
        return { key: 'speed', texture: 'Controls2.png' };
      case NavigationType.joystick:
        return { key: 'joystick', texture: 'Controls3.png' };
      case NavigationType.combined:
        return { key: 'combined', texture: 'Controls1.png' };
    }
    return { key: 'speed', texture: 'Controls2.png' };
  }

  domKey = '';
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
    let distanceTraveled = 0;
    for (const test of testData) {
      distanceTraveled += test.distance;
      this.trackingData.push({
        speed: test.speed,
        distance: test.distance,
        distanceTraveled: distanceTraveled,
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

    this.domKey = registerDomElement(
      this.$refs.gameContainer as HTMLElement,
      () => {
        this.sizeCalculated = true;
      },
      500
    );
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateModule);
    cashService.deregisterAllGet(this.updateTask);
    if (this.trackingManager) this.trackingManager.deregisterAll();
    if (this.missionProgress) this.missionProgress.deregisterAll();
  }

  unmounted(): void {
    this.deregisterAll();
    unregisterDomElement(this.domKey);
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      this.trackingManager = new TrackingManager(this.taskId, {
        gameStep: GameStep.Select,
        vehicle: this.vehicle,
        drive: {
          stops: 0,
          persons: 1,
          routePath: null,
          routePathLength: 0,
          drivenPath: null,
          drivenPathLength: 0,
          trackingData: [],
        },
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

  async startGame(
    vehicle: vehicleCalculation.Vehicle,
    navigationType: NavigationType,
    movingType: MovingType
  ): Promise<void> {
    this.vehicle = vehicle;
    this.gameStep = GameStep.Drive;
    this.gameState = GameState.Info;
    this.navigationType = navigationType;
    this.movingType = movingType;

    if (this.trackingManager) {
      await this.trackingManager.createInstanceStep(
        null,
        TaskParticipantIterationStepStatesType.NEUTRAL,
        {
          vehicle: vehicle,
          navigation: navigationType,
          movingType: movingType,
          selectTime: Date.now() - this.stepTime,
          playTime: Date.now() - this.startTime,
          drive: {
            stops: 0,
            persons: 1,
            routePath: null,
            routePathLength: 0,
            drivenPath: null,
            drivenPathLength: 0,
            trackingData: [],
          },
          particleState: {},
          rate: 0,
        }
      );
      await this.trackingManager.saveIteration({
        gameStep: GameStep.Drive,
        vehicle: vehicle,
      });
    }
    this.stepTime = Date.now();
  }

  async goalReached(
    trackingData: TrackingData[],
    routePath: FeatureCollection,
    drivenPath: FeatureCollection,
    personCount: number,
    stops: number
  ): Promise<void> {
    this.trackingData = trackingData;
    this.gameStep = GameStep.CleanUp;
    this.gameState = GameState.Info;

    if (this.trackingManager) {
      await this.trackingManager.saveIterationStep({
        drive: {
          stops: stops,
          persons: personCount,
          routePath: routePath,
          routePathLength: turf.length(routePath),
          drivenPath: drivenPath,
          drivenPathLength: turf.length(drivenPath),
          trackingData: trackingData,
        },
        driveTime: Date.now() - this.stepTime,
        playTime: Date.now() - this.startTime,
      });
      await this.trackingManager.saveIteration({
        gameStep: GameStep.CleanUp,
        trackingData: trackingData,
      });
    }
    this.stepTime = Date.now();
  }

  async cleanupFinished(particleState: {
    [key: string]: ParticleState;
  }): Promise<void> {
    this.particleState = particleState;
    this.gameStep = GameStep.Result;
    this.gameState = GameState.Info;

    if (this.trackingManager) {
      const successRate = particleStateUtil.successRate(particleState);
      await this.trackingManager.saveIterationStep(
        {
          rate: successRate,
          particleState: particleState,
          cleanupTime: Date.now() - this.stepTime,
          playTime: Date.now() - this.startTime,
        },
        successRate === 3
          ? TaskParticipantIterationStepStatesType.CORRECT
          : TaskParticipantIterationStepStatesType.WRONG,
        successRate,
        null,
        true,
        (item) =>
          vehicleCalculation.isSameVehicle(item.parameter.vehicle, this.vehicle)
      );
      await this.trackingManager.saveIteration(
        {
          rate: successRate,
          gameStep: GameStep.Result,
          particleState: particleState,
        },
        successRate >= 2
          ? TaskParticipantIterationStatesType.WIN
          : TaskParticipantIterationStatesType.LOOS
      );

      if (
        this.trackingManager.state &&
        (!this.trackingManager.state.parameter.rate ||
          this.trackingManager.state.parameter.rate < successRate)
      ) {
        this.trackingManager.setFinishedState(this.module, {
          rate: successRate,
        });
      } else this.trackingManager.setFinishedState(this.module);
    }
    this.stepTime = Date.now();
  }
}
</script>

<style lang="scss" scoped>
.mapSpace {
  position: relative;
  overflow: auto;
}
</style>
