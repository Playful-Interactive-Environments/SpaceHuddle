import * as cashService from '@/services/cash-service';
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Idea } from '@/types/api/Idea';
import { Module } from '@/types/api/Module';
import * as ideaService from '@/services/idea-service';
import * as votingService from '@/services/voting-service';
import { VoteParameterResult } from '@/types/api/Vote';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import * as progress from '@/modules/brainstorming/missionmap/utils/progress';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export class MissionProgress {
  taskId: string;
  task!: Task;
  module: Module | undefined = undefined;
  ideas: Idea[] = [];
  decidedIdeas: Idea[] = [];
  voteResult: VoteParameterResult[] = [];
  authHeaderType = EndpointAuthorisationType.MODERATOR;
  saveElectricityProgress = false;
  ideaCash!: cashService.SimplifiedCashEntry<Idea[]>;

  constructor(
    taskId: string,
    orderType: string | null = IdeaSortOrder.TIMESTAMP,
    authHeaderType = EndpointAuthorisationType.MODERATOR,
    saveElectricityProgress = false
  ) {
    this.taskId = taskId;
    this.authHeaderType = authHeaderType;
    this.saveElectricityProgress = saveElectricityProgress;
    this.deregisterAll();
    taskService.registerGetTaskById(
      taskId,
      (result) => this._updateTask(result),
      authHeaderType,
      60 * 60
    );
    this.ideaCash = ideaService.registerGetIdeasForTask(
      taskId,
      orderType,
      null,
      (result) => this._updateIdeas(result),
      authHeaderType,
      20
    );
    votingService.registerGetParameterResult(
      taskId,
      'points',
      (result) => this._updateVoteResult(result),
      authHeaderType,
      60
    );
  }

  deregisterAll(): void {
    cashService.deregisterAllGet((result: any) => this._updateTask(result));
    cashService.deregisterAllGet((result) => this._updateIdeas(result));
    cashService.deregisterAllGet((result) => this._updateVoteResult(result));
    if (this.saveElectricityProgress) delete globalThis.electricityProgress;
  }

  _updateTask(task: Task): void {
    this.task = task;
    if (task.modules.length === 1) this.module = task.modules[0];
    else {
      this.module = task.modules.find((t) => t.name === 'missionmap');
    }
  }

  _updateIdeas(ideas: Idea[]): void {
    this.ideas = ideas;
  }

  _updateVoteResult(votes: VoteParameterResult[]): void {
    this.voteResult = votes;
    this.calculateDecidedIdeas();
  }

  calculateDecidedIdeas(): void {
    this.decidedIdeas = progress.calculateDecidedIdeasFromResult(
      this.voteResult,
      this.ideas
    );
    if (this.saveElectricityProgress) {
      globalThis.electricityProgress = this.getElectricityProgress();
    }
  }

  getProgress(): {
    [key: string]: progress.ProgressValues;
  } {
    return progress.getProgress(this.decidedIdeas, this.module);
  }

  getElectricityProgress(): {
    [key: string]: progress.ProgressValues;
  } {
    return progress.getElectricityProgress(this.decidedIdeas, this.module);
  }
}
