import * as taskParticipantService from '@/services/task-participant-service';
import * as taskService from '@/services/task-service';
import TaskParticipantIterationStatesType from '@/types/enum/TaskParticipantIterationStatesType';
import TaskParticipantStatesType from '@/types/enum/TaskParticipantStatesType';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import { TaskParticipantIteration } from '@/types/api/TaskParticipantIteration';
import { TaskParticipantState } from '@/types/api/TaskParticipantState';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import { Module } from '@/types/api/Module';
import EndpointType from '@/types/enum/EndpointType';
import { Task } from '@/types/api/Task';

export interface GameplayResult {
  stars: number;
  points: number;
  playCount: number;
}

export interface GameplayAndSpentResult extends GameplayResult {
  pointsSpent: number;
}

export interface GameplayHierarchyItem {
  parameter: { gameplayResult: GameplayResult | GameplayAndSpentResult };
}

/* eslint-disable @typescript-eslint/no-explicit-any*/
export class TrackingManager {
  taskId: string;
  task!: Task;
  iteration: TaskParticipantIteration | null = null;
  iterationStep: TaskParticipantIterationStep | null = null;
  iterationList: TaskParticipantIteration[] = [];
  iterationStepList: TaskParticipantIterationStep[] = [];
  stepList: TaskParticipantIterationStep[] = [];
  state: TaskParticipantState | null = null;
  readonly pointsPerStar = 100;
  readonly maxPoints = 1000;

  iterationsCash!: cashService.SimplifiedCashEntry<TaskParticipantIteration[]>;
  stepsCash!: cashService.SimplifiedCashEntry<TaskParticipantIterationStep[]>;
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
        this.iterationsCash = taskParticipantService.registerGetIterationList(
          this.taskId,
          (result: any) => this._updateIterations(result),
          EndpointAuthorisationType.PARTICIPANT,
          2 * 60
        );
        this.stepsCash = taskParticipantService.registerGetIterationStepList(
          this.taskId,
          (result: any) => this._updateSteps(result),
          EndpointAuthorisationType.PARTICIPANT,
          2 * 60
        );
      });
    taskService.registerGetTaskById(
      this.taskId,
      (result: any) => this._updateTask(result),
      EndpointAuthorisationType.PARTICIPANT,
      60 * 60
    );
  }

  deregisterAll(): void {
    cashService.deregisterAllGet((result: any) => this._updateState(result));
    cashService.deregisterAllGet((result: any) =>
      this._updateIterations(result)
    );
    cashService.deregisterAllGet((result: any) => this._updateSteps(result));
    cashService.deregisterAllGet((result: any) => this._updateTask(result));
  }

  _updateState(stateList: TaskParticipantState[]): void {
    if (stateList.length > 0) {
      this.state = stateList[0];
      if (this.state.state === TaskParticipantStatesType.FINISHED) {
        //
      }
    }
  }

  _updateIterations(iterationList: TaskParticipantIteration[]): void {
    this.iterationList = iterationList;
  }

  _updateSteps(stepList: TaskParticipantIterationStep[]): void {
    if (this.iteration) {
      this.stepList = stepList;
      this.iterationStepList = stepList.filter(
        (item) => item.iteration === this.iteration?.iteration
      );
    }
  }

  _updateTask(task: Task): void {
    this.task = task;
  }

  getStarPoints(stars: number): number {
    return stars * this.pointsPerStar;
  }

  getGameplayResult(
    stars: number,
    playPoints: number | null = null,
    playCount = 1,
    maxPoints: number | null = null,
    pointsSpent: number | null = null
  ): GameplayResult | GameplayAndSpentResult {
    const points = playPoints !== null ? playPoints : this.getStarPoints(stars);
    const result = {
      stars: stars,
      points: maxPoints !== null && points > maxPoints ? maxPoints : points,
      playCount: playCount,
    };
    if (pointsSpent !== null)
      (result as GameplayAndSpentResult).pointsSpent = pointsSpent;
    return result;
  }

  getGameplayResultFromChild(
    list: GameplayHierarchyItem[],
    limitToMaxPoints = false
  ): GameplayResult | GameplayAndSpentResult {
    let stars = 0;
    let points = 0;
    let playCount = 0;
    let pointsSpent = 0;
    let hasSpentPoints = false;
    for (const item of list) {
      if (item.parameter.gameplayResult) {
        stars += item.parameter.gameplayResult.stars;
        points += item.parameter.gameplayResult.points;
        const parameterPlayCount = item.parameter.gameplayResult.playCount;
        playCount += parameterPlayCount ? parameterPlayCount : 1;
        const parameterPointsSpent = (item.parameter.gameplayResult as any)
          .pointsSpent;
        if (parameterPointsSpent !== undefined) hasSpentPoints = true;
        pointsSpent += parameterPointsSpent ? parameterPointsSpent : 0;
      }
    }
    return this.getGameplayResult(
      stars,
      points,
      playCount,
      limitToMaxPoints ? this.maxPoints : null,
      hasSpentPoints ? pointsSpent : null
    );
  }

  setFinishedState(module: Module | null): void {
    if (
      this.state &&
      module &&
      'replayabel' in module.parameter &&
      !module.parameter.replayabel
    ) {
      this.setManualFinishedState();
    }
  }

  setManualFinishedState(): void {
    if (this.state) {
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

  saveStatePointsFromIterations(): void {
    if (this.state) {
      this.state.parameter.gameplayResult = this.getGameplayResultFromChild(
        this.iterationList,
        true
      );
      taskParticipantService
        .putParticipantState(this.taskId, this.state)
        .then(() => {
          if (this.task) {
            cashService.refreshCash(
              `/${EndpointType.SESSION}/${this.task.sessionId}/${EndpointType.PARTICIPANT_STATE}`
            );
          }
        });
    }
  }

  async saveIteration(
    contentChanges: any | null = null,
    changedState: TaskParticipantIterationStatesType | null = null,
    stars: number | null = null,
    updateStateSum = false
  ): Promise<void> {
    if (this.iteration) {
      if (stars !== null)
        this.iteration.parameter.gameplayResult = this.getGameplayResult(stars);
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
      await this.iterationsCash.refreshData();
      if (updateStateSum) {
        await this.saveStatePointsFromIterations();
      }
    }
  }

  async saveIterationPoints(stars: number): Promise<void> {
    if (this.iteration) {
      this.iteration.parameter.gameplayResult = this.getGameplayResult(stars);
      await taskParticipantService.putParticipantIteration(
        this.taskId,
        this.iteration
      );
      await this.iterationsCash.refreshData();
    }
  }

  async saveIterationPointsSpent(points: number): Promise<void> {
    if (this.iteration) {
      if (this.iteration.parameter.gameplayResult)
        this.iteration.parameter.gameplayResult.pointsSpent += points;
      else
        this.iteration.parameter.gameplayResult = this.getGameplayResult(
          0,
          null,
          1,
          null,
          points
        );
      await taskParticipantService.putParticipantIteration(
        this.taskId,
        this.iteration
      );
      await this.iterationsCash.refreshData();
    }
  }

  async saveIterationPointsFromSteps(): Promise<void> {
    if (this.iteration) {
      this.iteration.parameter.gameplayResult = this.getGameplayResultFromChild(
        this.iterationStepList
      );
      await taskParticipantService.putParticipantIteration(
        this.taskId,
        this.iteration
      );
      await this.iterationsCash.refreshData();
    }
  }

  _getLimitedStarPoints(
    ideaId: string | null,
    stars: number | null = null,
    starLimitRule:
      | ((step: TaskParticipantIterationStep) => boolean)
      | null = null
  ): number {
    if (stars !== null) {
      let starPoints = stars ? this.getStarPoints(stars) : 0;
      if (starLimitRule) {
        const previousIdeaPointsList = this.stepList.filter(
          (item) =>
            item.ideaId === ideaId &&
            item.parameter.gameplayResult &&
            starLimitRule(item)
        );
        const previousIdeaPoints = previousIdeaPointsList.reduce(
          (sum, item) => sum + item.parameter.gameplayResult.points,
          0
        );
        starPoints -= previousIdeaPoints;
        if (starPoints < 0) starPoints = 0;
      }
      return starPoints;
    }
    return 0;
  }

  async createInstanceStep(
    ideaId: string | null,
    state: TaskParticipantIterationStepStatesType,
    initContent: any,
    stars: number | null = null,
    pointsSpent: number | null = null,
    updateInstanceSum = false,
    starLimitRule:
      | ((step: TaskParticipantIterationStep) => boolean)
      | null = null
  ): Promise<void> {
    if (this.iteration) {
      if (stars !== null || pointsSpent !== null) {
        initContent.gameplayResult = this.getGameplayResult(
          stars !== null ? stars : 0,
          this._getLimitedStarPoints(ideaId, stars, starLimitRule),
          1,
          null,
          pointsSpent
        );
      }
      this.iterationStep =
        await taskParticipantService.postParticipantIterationStep(this.taskId, {
          iteration: this.iteration?.iteration,
          ideaId: ideaId,
          state: state,
          parameter: initContent,
        });
      await this.stepsCash.refreshData();
      if (updateInstanceSum && this.iterationStep.parameter.gameplayResult) {
        await this.saveIterationPointsFromSteps();
        await this.saveStatePointsFromIterations();
      }
    }
  }

  async createInstanceStepPoints(
    ideaId: string | null,
    state: TaskParticipantIterationStepStatesType,
    initContent: any,
    points: number | null = null,
    pointsSpent: number | null = null,
    updateInstanceSum = false
  ): Promise<void> {
    if (this.iteration) {
      if (points !== null || pointsSpent !== null)
        initContent.gameplayResult = this.getGameplayResult(
          0,
          points !== null ? points : 0,
          1,
          null,
          pointsSpent
        );
      this.iterationStep =
        await taskParticipantService.postParticipantIterationStep(this.taskId, {
          iteration: this.iteration?.iteration,
          ideaId: ideaId,
          state: state,
          parameter: initContent,
        });
      await this.stepsCash.refreshData();
      if (updateInstanceSum && this.iterationStep.parameter.gameplayResult) {
        await this.saveIterationPointsFromSteps();
        await this.saveStatePointsFromIterations();
      }
    }
  }

  async saveIterationStep(
    contentChanges: any | null = null,
    changedState: TaskParticipantIterationStepStatesType | null = null,
    stars: number | null = null,
    updateInstanceSum = false,
    starLimitRule:
      | ((step: TaskParticipantIterationStep) => boolean)
      | null = null
  ): Promise<void> {
    if (this.iterationStep) {
      if (stars !== null)
        this.iterationStep.parameter.gameplayResult = this.getGameplayResult(
          stars,
          this._getLimitedStarPoints(
            this.iterationStep.ideaId,
            stars,
            starLimitRule
          )
        );
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
      await this.stepsCash.refreshData();
      if (updateInstanceSum && this.iterationStep.parameter.gameplayResult) {
        await this.saveIterationPointsFromSteps();
        await this.saveStatePointsFromIterations();
      }
    }
  }

  saveIterationStepPoints(
    stars: number,
    starLimitRule:
      | ((step: TaskParticipantIterationStep) => boolean)
      | null = null
  ): void {
    if (this.iterationStep) {
      this.iterationStep.parameter.gameplayResult = this.getGameplayResult(
        stars,
        this._getLimitedStarPoints(
          this.iterationStep.ideaId,
          stars,
          starLimitRule
        )
      );
      taskParticipantService
        .putParticipantIterationStep(this.taskId, this.iterationStep)
        .then(() => {
          this.stepsCash.refreshData();
        });
    }
  }

  saveIterationStepPointsSpent(points: number): void {
    if (this.iterationStep) {
      if (this.iterationStep.parameter.gameplayResult)
        this.iterationStep.parameter.gameplayResult.pointsSpent += points;
      else
        this.iterationStep.parameter.gameplayResult = this.getGameplayResult(
          0,
          null,
          1,
          null,
          points
        );
      taskParticipantService
        .putParticipantIterationStep(this.taskId, this.iterationStep)
        .then(() => {
          this.stepsCash.refreshData();
        });
    }
  }
}
