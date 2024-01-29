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
  module: Module | null = null;
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
      this.callUpdateTask,
      authHeaderType,
      60 * 60
    );
    this.ideaCash = ideaService.registerGetIdeasForTask(
      taskId,
      orderType,
      null,
      this.callUpdateIdeas,
      authHeaderType,
      20
    );
    votingService.registerGetParameterResult(
      taskId,
      'points',
      this.callUpdateVoteResult,
      authHeaderType,
      60
    );
  }

  private readonly callUpdateTask = (result: any) => this._updateTask(result);
  private readonly callUpdateIdeas = (result: any) => this._updateIdeas(result);
  private readonly callUpdateVoteResult = (result: any) =>
    this._updateVoteResult(result);

  deregisterAll(): void {
    cashService.deregisterAllGet(this.callUpdateTask);
    cashService.deregisterAllGet(this.callUpdateIdeas);
    cashService.deregisterAllGet(this.callUpdateVoteResult);
    if (this.saveElectricityProgress) delete globalThis.electricityProgress;
  }

  _updateTask(task: Task): void {
    this.task = task;
    if (task.modules.length === 1) this.module = task.modules[0];
    else {
      const module = task.modules.find((t) => t.name === 'missionmap');
      this.module = module ?? null;
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
