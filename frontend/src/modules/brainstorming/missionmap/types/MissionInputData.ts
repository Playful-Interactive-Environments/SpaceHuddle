import * as viewService from '@/services/view-service';
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
export class MissionInputData {
  taskId: string;
  inputTaskId: string | null = null;
  task!: Task;
  module: Module | undefined = undefined;
  inputModule: Module | undefined = undefined;
  inputIdeas: Idea[] = [];
  ownIdeas: Idea[] = [];
  ideas: Idea[] = [];
  decidedInputIdeas: Idea[] = [];
  decidedIdeas: Idea[] = [];
  inputVoteResult: VoteParameterResult[] = [];
  ownVoteResult: VoteParameterResult[] = [];
  voteResult: VoteParameterResult[] = [];
  authHeaderType = EndpointAuthorisationType.MODERATOR;

  constructor(
    taskId: string,
    orderType: string | null = IdeaSortOrder.TIMESTAMP,
    authHeaderType = EndpointAuthorisationType.MODERATOR
  ) {
    this.taskId = taskId;
    this.authHeaderType = authHeaderType;
    this.deregisterAll();
    taskService.registerGetTaskById(
      taskId,
      (result) => this._updateTask(result),
      authHeaderType,
      60 * 60
    );

    viewService.registerGetInputIdeas(
      taskId,
      orderType,
      null,
      (result) => this._updateInputIdeas(result),
      authHeaderType,
      20
    );
    ideaService.registerGetIdeasForTask(
      taskId,
      orderType,
      null,
      (result) => this._updateOwnIdeas(result),
      authHeaderType,
      20
    );
    votingService.registerGetParameterResult(
      taskId,
      'points',
      (result) => this._updateOwnVoteResult(result),
      authHeaderType,
      60
    );
  }

  deregisterAll(): void {
    cashService.deregisterAllGet((result: any) => this._updateTask(result));
    cashService.deregisterAllGet((result) => this._updateInputIdeas(result));
    cashService.deregisterAllGet((result) => this._updateOwnIdeas(result));
    cashService.deregisterAllGet((result) => this._updateOwnVoteResult(result));
    cashService.deregisterAllGet((result) =>
      this._updateInputVoteResult(result)
    );
  }

  _updateTask(task: Task): void {
    if (!this.task && task.parameter.input.length > 0) {
      this.inputTaskId = task.parameter.input[0].view.id;
      cashService.deregisterAllGet((result) =>
        this._updateInputVoteResult(result)
      );
      if (this.inputTaskId) {
        votingService.registerGetParameterResult(
          this.inputTaskId,
          'points',
          (result) => this._updateInputVoteResult(result),
          this.authHeaderType,
          60
        );
      }
    }
    this.task = task;
    if (task.modules.length === 1) this.module = task.modules[0];
    else {
      this.module = task.modules.find((t) => t.name === 'missionmap');
    }
    //todo use inputModule
    //todo input can pass over several modules
  }

  _updateInputIdeas(ideas: Idea[]): void {
    this.inputIdeas = ideas.filter((item) => item.parameter.shareData);
    this.ideas = [...this.inputIdeas, ...this.ownIdeas] as Idea[];
  }

  _updateOwnIdeas(ideas: Idea[]): void {
    this.ownIdeas = ideas;
    this.ideas = [...this.inputIdeas, ...this.ownIdeas] as Idea[];
  }

  _updateOwnVoteResult(votes: VoteParameterResult[]): void {
    this.ownVoteResult = votes;
    this.voteResult = [
      ...this.inputVoteResult,
      ...this.ownVoteResult,
    ] as VoteParameterResult[];
    this.calculateDecidedIdeas();
  }

  _updateInputVoteResult(votes: VoteParameterResult[]): void {
    this.inputVoteResult = votes;
    this.voteResult = [
      ...this.inputVoteResult,
      ...this.ownVoteResult,
    ] as VoteParameterResult[];
    this.calculateDecidedIdeas();
  }

  calculateDecidedIdeas(): void {
    this.decidedInputIdeas = progress.calculateDecidedIdeasFromResult(
      this.inputVoteResult,
      this.inputIdeas
    );
    this.decidedIdeas = progress.calculateDecidedIdeasFromResult(
      this.ownVoteResult,
      this.ideas
    );
  }

  getProgressFromInput(): {
    [key: string]: progress.ProgressValues;
  } {
    //todo use inputModule
    //todo input can pass over several modules
    return progress.getProgress(this.decidedInputIdeas, this.module);
  }
}
