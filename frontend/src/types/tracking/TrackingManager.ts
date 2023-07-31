import * as taskParticipantService from '@/services/task-participant-service';
import TaskParticipantIterationStatesType from '@/types/enum/TaskParticipantIterationStatesType';
import TaskParticipantStatesType from '@/types/enum/TaskParticipantStatesType';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import { TaskParticipantIteration } from '@/types/api/TaskParticipantIteration';
import { TaskParticipantState } from '@/types/api/TaskParticipantState';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import { Module } from '@/types/api/Module';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export class TrackingManager {
  taskId: string;
  iteration: TaskParticipantIteration | null = null;
  iterationStep: TaskParticipantIterationStep | null = null;
  state: TaskParticipantState | null = null;

  constructor(taskId: string, initInstanceContent: any) {
    this.taskId = taskId;
    this.deregisterAll();
    taskParticipantService.registerGetList(
      this.taskId,
      (result: any) => this._updateState(result),
      EndpointAuthorisationType.PARTICIPANT,
      2 * 60
    );
    taskParticipantService
      .postParticipantIteration(this.taskId, {
        state: TaskParticipantIterationStatesType.IN_PROGRESS,
        parameter: initInstanceContent,
      })
      .then((result) => {
        this.iteration = result;
      });
  }

  deregisterAll(): void {
    cashService.deregisterAllGet((result: any) => this._updateState(result));
  }

  _updateState(stateList: TaskParticipantState[]): void {
    if (stateList.length > 0) {
      this.state = stateList[0];
      if (this.state.state === TaskParticipantStatesType.FINISHED) {
        //
      }
    }
  }

  setFinishedState(module: Module | null): void {
    if (
      this.state &&
      module &&
      'replayabel' in module.parameter &&
      !module.parameter.replayabel
    ) {
      this.saveState({}, TaskParticipantStatesType.FINISHED);
    }
  }

  saveState(
    contentChanges: any | null = null,
    changedState: TaskParticipantStatesType | null = null
  ): void {
    if (this.state) {
      if (changedState) this.state.state = changedState;
      if (contentChanges) {
        for (const key of Object.keys(contentChanges)) {
          this.state.parameter[key] = contentChanges[key];
        }
      }
      taskParticipantService.putParticipantState(this.taskId, this.state);
    }
  }

  async saveIteration(
    contentChanges: any | null = null,
    changedState: TaskParticipantIterationStatesType | null = null
  ): Promise<void> {
    if (this.iteration) {
      if (changedState) this.iteration.state = changedState;
      if (contentChanges) {
        for (const key of Object.keys(contentChanges)) {
          this.iteration.parameter[key] = contentChanges[key];
        }
      }
      await taskParticipantService.putParticipantIteration(
        this.taskId,
        this.iteration
      );
    }
  }

  createInstanceStep(
    ideaId: string,
    state: TaskParticipantIterationStepStatesType,
    initContent: any
  ) {
    if (this.iteration) {
      taskParticipantService
        .postParticipantIterationStep(this.taskId, {
          iteration: this.iteration?.iteration,
          ideaId: ideaId,
          state: state,
          parameter: initContent,
        })
        .then((result) => {
          this.iterationStep = result;
        });
    }
  }

  async saveIterationStep(
    contentChanges: any | null = null,
    changedState: TaskParticipantIterationStepStatesType | null = null
  ): Promise<void> {
    if (this.iterationStep) {
      if (changedState) this.iterationStep.state = changedState;
      if (contentChanges) {
        for (const key of Object.keys(contentChanges)) {
          this.iterationStep.parameter[key] = contentChanges[key];
        }
      }
      await taskParticipantService.putParticipantIterationStep(
        this.taskId,
        this.iterationStep
      );
    }
  }
}
