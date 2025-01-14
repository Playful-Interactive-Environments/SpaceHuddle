import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import * as ideaService from '@/services/idea-service';
import * as viewService from '@/services/view-service';
import { Idea } from '@/types/api/Idea';
import * as votingService from '@/services/voting-service';
import { Vote, VoteParameterResult } from '@/types/api/Vote';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export class CombinedInputManager {
  taskId: string;
  task!: Task;
  orderType: string | null;
  ideas: Idea[] = [];
  votes: Vote[] = [];
  votingResult: VoteParameterResult[] = [];

  callbackUpdateIdeas: (() => void) | null = null;
  callbackUpdateVotes: (() => void) | null = null;

  private _ideasCash!: cashService.SimplifiedCashEntry<Idea[]>;
  private _inputIdeasCash!: cashService.SimplifiedCashEntry<Idea[]>;
  private _votesCash!: cashService.SimplifiedCashEntry<Vote[]>;
  private _inputVotesCash!: cashService.SimplifiedCashEntry<Vote[]>;
  private _voteParameterCash!: cashService.SimplifiedCashEntry<
    VoteParameterResult[]
  >;
  private _inputVoteParameterCash!: cashService.SimplifiedCashEntry<
    VoteParameterResult[]
  >;

  private readonly _authHeaderType: EndpointAuthorisationType;
  private readonly _sumVoteParameter: string | null;

  constructor(
    taskId: string,
    orderType: string | null = null,
    authHeaderType = EndpointAuthorisationType.MODERATOR,
    determineVotes = false,
    sumVoteParameter: string | null = null
  ) {
    this.taskId = taskId;
    this.orderType = orderType;
    this._authHeaderType = authHeaderType;
    this._sumVoteParameter = sumVoteParameter;
    this.deregisterAll();
    this._ideasCash = ideaService.registerGetIdeasForTask(
      this.taskId,
      this.orderType,
      null,
      this.callUpdateIdeas,
      authHeaderType,
      20
    );
    this._inputIdeasCash = viewService.registerGetInputIdeas(
      this.taskId,
      this.orderType,
      null,
      this.callUpdateInputIdeas,
      authHeaderType,
      60 * 60
    );
    if (determineVotes) {
      taskService.registerGetTaskById(
        this.taskId,
        this.callUpdateTask,
        authHeaderType,
        60 * 60
      );
      this._votesCash = votingService.registerGetVotes(
        this.taskId,
        this.callUpdateVotes,
        authHeaderType,
        20
      );
      if (authHeaderType === EndpointAuthorisationType.PARTICIPANT) {
        this._voteParameterCash = votingService.registerGetParameterResult(
          this.taskId,
          'points',
          this.callUpdateParameterResults,
          authHeaderType,
          20
        );
      }
    }
  }

  private readonly callUpdateTask = (result: any) => this._updateTask(result);
  private readonly callUpdateIdeas = (result: any) => this._updateIdeas(result);
  private readonly callUpdateInputIdeas = (result: any) =>
    this._updateInputIdeas(result);
  private readonly callUpdateVotes = (result: any) => this._updateVotes(result);
  private readonly callUpdateParameterResults = (result: any) =>
    this._updateParameterResults(result);
  private readonly callUpdateInputVotes = (result: any) =>
    this._updateInputVotes(result);
  private readonly callUpdateInputParameterResults = (result: any) =>
    this._updateInputParameterResults(result);

  deregisterAll(): void {
    cashService.deregisterAllGet(this.callUpdateTask);
    cashService.deregisterAllGet(this.callUpdateIdeas);
    cashService.deregisterAllGet(this.callUpdateInputIdeas);
    cashService.deregisterAllGet(this.callUpdateVotes);
    cashService.deregisterAllGet(this.callUpdateParameterResults);
    cashService.deregisterAllGet(this.callUpdateInputVotes);
    cashService.deregisterAllGet(this.callUpdateInputParameterResults);
  }

  private _updateTask(task: Task): void {
    this.task = task;
    cashService.deregisterAllGet(this.callUpdateInputVotes);
    if (task.parameter.input.length > 0) {
      const inputTaskId = task.parameter.input[0].view.id;
      this._inputVotesCash = votingService.registerGetVotes(
        inputTaskId,
        this.callUpdateInputVotes,
        this._authHeaderType,
        60 * 60
      );
      if (this._authHeaderType === EndpointAuthorisationType.PARTICIPANT) {
        this._inputVoteParameterCash = votingService.registerGetParameterResult(
          inputTaskId,
          'points',
          this.callUpdateInputParameterResults,
          this._authHeaderType,
          60 * 60
        );
      }
    }
  }

  private _ownIdeas: Idea[] = [];
  private _updateIdeas(ideas: Idea[]): void {
    this._ownIdeas = ideas;
    this._updateIdeaList();
  }

  private _inputIdeas: Idea[] = [];
  private _updateInputIdeas(ideas: Idea[]): void {
    this._inputIdeas = ideas;
    this._updateIdeaList();
  }

  private _updateIdeaList(informCallback = true): void {
    this.ideas = [...this._ownIdeas, ...this._inputIdeas].sort((a, b) => {
      if (a.orderGroup === b.orderGroup) {
        if (a.orderText === b.orderText) return 0;
        if (b.orderText < a.orderText) return -1;
        return 0;
      } else {
        if (b.orderGroup < a.orderGroup) return 1;
        return -1;
      }
    });
    if (informCallback && this.callbackUpdateIdeas) this.callbackUpdateIdeas();
  }

  currentVotes: Vote[] = [];
  private _updateVotes(votes: Vote[]): void {
    this.currentVotes = votes;
    this._updateVoteList();
  }

  inputVotes: Vote[] = [];
  private _updateInputVotes(votes: Vote[]): void {
    this.inputVotes = votes;
    this._updateVoteList();
  }

  currentVoteResults: VoteParameterResult[] = [];
  private _updateParameterResults(votes: VoteParameterResult[]): void {
    this.currentVoteResults = votes;
    this._updateResultList();
  }

  inputVoteResults: VoteParameterResult[] = [];
  private _updateInputParameterResults(votes: VoteParameterResult[]): void {
    this.inputVoteResults = votes;
    this._updateResultList();
  }

  private _updateResultList(): void {
    const votingResult: VoteParameterResult[] = [];
    const list = [...this.currentVoteResults, ...this.inputVoteResults];
    const ideaList = list
      .map((item) => item.ideaId)
      .filter(
        (value, index, self) =>
          self.findIndex((item) => item === value) === index
      );
    for (const ideaId of ideaList) {
      const votes = list.filter(
        (item) => item.ideaId === ideaId && item.sum > 0
      );
      const count = votes.reduce((sum, item) => sum + item.count, 0);
      const sum = votes.reduce((sum, item) => sum + item.sum, 0);
      votingResult.push({
        ideaId: ideaId,
        sum: sum,
        count: count,
        avg: sum / count,
        details: undefined,
      });
    }
    this.votingResult = votingResult;
    if (this.callbackUpdateVotes) this.callbackUpdateVotes();
  }

  private _updateVoteList(): void {
    this.votes = [...this.currentVotes, ...this.inputVotes];
    if (
      this._sumVoteParameter &&
      this._authHeaderType !== EndpointAuthorisationType.PARTICIPANT
    ) {
      const sum: { [key: string]: { sum: number; count: number } } = {};
      for (const vote of this.votes) {
        if (!sum[vote.ideaId]) {
          sum[vote.ideaId] = { sum: 0, count: 0 };
        }
        if (
          vote.parameter &&
          Object.hasOwn(vote.parameter, this._sumVoteParameter) &&
          vote.parameter[this._sumVoteParameter] > 0
        ) {
          sum[vote.ideaId].sum += vote.parameter[this._sumVoteParameter];
          sum[vote.ideaId].count += 1;
        }
      }
      const votingResult: VoteParameterResult[] = [];
      for (const ideaId of Object.keys(sum)) {
        votingResult.push({
          ideaId: ideaId,
          sum: sum[ideaId].sum,
          count: sum[ideaId].count,
          avg: sum[ideaId].count > 0 ? sum[ideaId].sum / sum[ideaId].count : 0,
          details: undefined,
        });
      }
      this.votingResult = votingResult;
    }
    if (this.callbackUpdateVotes) this.callbackUpdateVotes();
  }

  setOrderType(orderType: string, refreshData = false): void {
    this.orderType = orderType;
    this._inputIdeasCash.parameter.urlParameter =
      ideaService.getIdeaListParameter(this.orderType, null);
    this._ideasCash.parameter.urlParameter = ideaService.getIdeaListParameter(
      this.orderType,
      null
    );
    if (refreshData) {
      this._inputIdeasCash.refreshData(false);
      this._ideasCash.refreshData(false);
    }
  }

  async refreshIdeas(): Promise<void> {
    //await this._inputIdeasCash.refreshData();
    await this._ideasCash.refreshData(false);
  }

  async refreshVotes(): Promise<void> {
    //await this._inputVotesCash.refreshData();
    //await this._inputVoteParameterCash.refreshData(false);
    await this._votesCash.refreshData(false);
    await this._voteParameterCash.refreshData(false);
  }

  isCurrentIdea(ideaId: string): boolean {
    return this._ownIdeas.find((item) => item.id === ideaId) !== undefined;
  }

  isCurrentVote(voteId: string): boolean {
    return this.currentVotes.find((item) => item.id === voteId) !== undefined;
  }

  addIdea(idea: Idea): void {
    this._ownIdeas.push(idea);
    this._updateIdeaList(false);
  }

  hasInput(): boolean {
    return this.task && this.task.parameter.input.length > 0;
  }
}
