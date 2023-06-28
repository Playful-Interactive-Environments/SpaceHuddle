<template>
  <div ref="gameContainer" class="mapSpace">
    <module-info
      v-if="gameState === GameState.Info"
      translation-path="module.information.cleanup.participant.tutorial"
      image-directory="/assets/games/cleanup/tutorial"
      :module-info-entry-data-list="tutorialList"
      @infoRead="gameState = GameState.Game"
      :info-type="gameStep"
    />
    <select-challenge
      v-if="gameStep === GameStep.Select && gameState === GameState.Game"
      v-model:tracking-state="state"
      v-model:tracking-iteration="iteration"
      @play="startGame"
    />
    <drive-to-location
      v-if="gameStep === GameStep.Drive && sizeCalculated && module"
      :parameter="module.parameter"
      :vehicle="vehicle"
      :vehicle-type="vehicleType"
      v-model:tracking-state="state"
      v-model:tracking-iteration="iteration"
      v-on:goalReached="goalReached"
    />
    <clean-up-particles
      v-if="gameStep === GameStep.CleanUp && gameState === GameState.Game"
      :vehicle="vehicle"
      :vehicle-type="vehicleType"
      :trackingData="trackingData"
      v-model:tracking-state="state"
      v-model:tracking-iteration="iteration"
      @finished="cleanupFinished"
    />
    <show-result
      v-if="gameStep === GameStep.Result && gameState === GameState.Game"
      :particle-state="particleState"
      v-model:tracking-state="state"
      v-model:tracking-iteration="iteration"
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
import * as taskParticipantService from '@/services/task-participant-service';
import TaskParticipantIterationStatesType from '@/types/enum/TaskParticipantIterationStatesType';
import { TaskParticipantIteration } from '@/types/api/TaskParticipantIteration';
import { TaskParticipantState } from '@/types/api/TaskParticipantState';
import TaskParticipantStatesType from '@/types/enum/TaskParticipantStatesType';
import { delay } from '@/utils/wait';

enum GameStep {
  Select = 'select',
  Drive = 'drive',
  CleanUp = 'clean up',
  Result = 'result',
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

  trackingData: TrackingData[] = [];
  gameStep = GameStep.Select;
  GameStep = GameStep;
  gameState = GameState.Info;
  GameState = GameState;

  iteration: TaskParticipantIteration | null = null;
  state: TaskParticipantState | null = null;

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
          return ['speed', 'personCount', 'addPersons'];
        return ['speed'];
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
  }

  unmounted(): void {
    this.deregisterAll();
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      this.deregisterAll();
      taskParticipantService.registerGetList(
        this.taskId,
        this.updateState,
        EndpointAuthorisationType.PARTICIPANT,
        2 * 60
      );
      taskParticipantService
        .postParticipantIteration(this.taskId, {
          state: TaskParticipantIterationStatesType.IN_PROGRESS,
          parameter: {
            gameStep: GameStep.Select,
            vehicle: {
              category: this.vehicle,
              type: this.vehicleType,
            },
            trackingData: [],
            particleState: {},
          },
        })
        .then((result) => {
          this.iteration = result;
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

    if (this.iteration) {
      this.iteration.parameter.gameStep = GameStep.Drive;
      this.iteration.parameter.vehicle = vehicle;
    }
  }

  goalReached(trackingData): void {
    this.trackingData = trackingData;
    this.gameStep = GameStep.CleanUp;
    this.gameState = GameState.Info;

    if (this.iteration) {
      this.iteration.parameter.gameStep = GameStep.CleanUp;
      this.iteration.parameter.trackingData = trackingData;
    }
  }

  cleanupFinished(particleState: { [key: string]: ParticleState }): void {
    this.particleState = particleState;
    this.gameStep = GameStep.Result;
    this.gameState = GameState.Info;

    if (this.iteration) {
      this.iteration.parameter.gameStep = GameStep.Result;
      this.iteration.parameter.particleState = particleState;
    }
    if (
      this.state &&
      this.module &&
      'replayabel' in this.module.parameter &&
      !this.module.parameter.replayabel
    ) {
      this.state.state = TaskParticipantStatesType.FINISHED;
    }
  }

  updateState(stateList: TaskParticipantState[]): void {
    if (stateList.length > 0) {
      this.state = stateList[0];
      if (this.state.state === TaskParticipantStatesType.FINISHED) {
        //
      }
    }
  }

  @Watch('state', { immediate: true, deep: true })
  onStateChanged(): void {
    if (this.state) {
      taskParticipantService.putParticipantState(this.taskId, this.state);
    }
  }

  isSavingIterationData = false;
  @Watch('iteration', { immediate: true, deep: true })
  async onIterationChanged(): Promise<void> {
    if (!this.isSavingIterationData) {
      this.isSavingIterationData = true;
      await delay(100);
      if (this.iteration) {
        await taskParticipantService.putParticipantIteration(
          this.taskId,
          this.iteration
        );
      }
      this.isSavingIterationData = false;
    }
  }
}
</script>

<style lang="scss" scoped>
.mapSpace {
  position: relative;
}
</style>
