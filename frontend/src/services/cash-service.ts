/* eslint-disable @typescript-eslint/no-explicit-any*/

import { apiExecuteGetHandled } from '@/services/api';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { delay, until } from '@/utils/wait';

//eslint-disable-next-line @typescript-eslint/no-unused-vars
interface ModificationData {
  lastModification: number;
  callTimestamp: number;
  rowCount: number;
}

interface DataQuery<TOut> {
  executeCall(): Promise<TOut>;
}

class GetCall<TIn = any, TOut = any> implements DataQuery<TOut> {
  url: string;
  urlParameter: string;
  empty: TIn | any;
  authHeaderType: EndpointAuthorisationType;
  dataPreparation: ((data: TIn) => Promise<TOut>) | null;
  callCount = 0;

  constructor(
    url: string,
    empty: TIn | any = {},
    authHeaderType = EndpointAuthorisationType.MODERATOR,
    dataPreparation: ((data: TIn) => Promise<TOut>) | null
  ) {
    const urlParts = url.split('?');
    this.url = urlParts[0];
    if (!this.url.endsWith('/')) this.url = `${this.url}/`;
    this.urlParameter = urlParts.length > 1 ? urlParts[1] : '';
    this.empty = empty;
    this.authHeaderType = authHeaderType;
    this.dataPreparation = dataPreparation;
  }

  async executeCall(): Promise<TOut> {
    const url =
      this.urlParameter.length === 0
        ? this.url
        : `${this.url}?${this.urlParameter}`;
    const result = await apiExecuteGetHandled<TIn>(
      url,
      this.empty,
      this.authHeaderType
    );
    this.callCount++;
    if (this.dataPreparation) return await this.dataPreparation(result);
    return result as TOut;
  }

  /*async requestModificationTimestamp(): Promise<ModificationData> {
    const { data: result } = await getApiEndpoint(
      this.authHeaderType
    ).get<ModificationData>(
      this.urlParameter.length === 0
        ? `${this.url}lastModification/`
        : `${this.url}lastModification/?${this.urlParameter}`
    );
    return result;
  }*/
}

//eslint-disable-next-line @typescript-eslint/no-unused-vars
class UnionCall<TIn = any, TOut = any> implements DataQuery<TOut[]> {
  dbCallList: GetCall<TIn, TOut[]>[];

  constructor(callList: GetCall<TIn, TOut[]>[]) {
    this.dbCallList = callList;
  }

  async executeCall(): Promise<TOut[]> {
    const result: TOut[] = [];
    const callList: Promise<TOut | any>[] = [];
    for (const dbCall of this.dbCallList) {
      callList.push(
        dbCall.executeCall().then((data) => {
          result.push(...data);
        })
      );
    }
    await Promise.all(callList);
    return result;
  }
}

class Callback<T = any> {
  callback: (result: T, ...args) => void;
  args: any[] | null = null;
  maxDelaySeconds: number;
  initRecallDone = false;

  constructor(
    callback: (result: T, ...args) => void,
    maxDelaySeconds: number,
    args: any[] | null = null
  ) {
    this.callback = callback;
    this.args = args;
    this.maxDelaySeconds = maxDelaySeconds;
  }

  execute(newData: T, initRecall = false): void {
    if (this.initRecallDone || initRecall) {
      this.initRecallDone = true;
      if (this.args) {
        this.callback(newData, ...this.args);
      } else {
        this.callback(newData);
      }
    }
  }
}

export class CashEntry<TIn = any, TOut = any> {
  lastModification: number;
  callTimestamp: number;
  rowCount: number;
  data: TOut | any | null;
  isRefreshing: boolean;
  valid: boolean;
  parameter: GetCall<TIn, TOut>;
  callbackList: Callback[];
  inactive = true;
  refreshCount = 0;

  get maxDelaySeconds(): number {
    if (this.callbackList.length > 0) {
      let seconds = this.callbackList[0].maxDelaySeconds;
      for (const callback of this.callbackList) {
        if (callback.maxDelaySeconds < seconds)
          seconds = callback.maxDelaySeconds;
      }
      return seconds;
    }
    return 1;
  }

  constructor(
    url: string,
    empty: TIn | any = {},
    authHeaderType = EndpointAuthorisationType.MODERATOR,
    dataPreparation: ((data: TIn) => Promise<TOut>) | null = null
  ) {
    this.callbackList = [];
    this.lastModification = -2;
    this.callTimestamp = -1;
    this.rowCount = 0;
    this.data = null;
    this.isRefreshing = true;
    this.valid = false;
    this.parameter = new GetCall<TIn, TOut>(
      url,
      empty,
      authHeaderType,
      dataPreparation
    );
    this.executeCall();
  }

  addCallback(
    callback: (result: TOut, ...args) => void,
    maxDelaySeconds = 5 * 60,
    args: any[] | null = null
  ): Callback<TOut> {
    const index = this.callbackList.findIndex(
      (call) => call.callback === callback
    );
    if (index === -1) {
      const callClass = new Callback(callback, maxDelaySeconds, args);
      this.callbackList.push(callClass);
      this.changeLoop();
      return callClass;
    }
    return this.callbackList[index];
  }

  removeCallback(callback: (result: TOut, ...args) => void): boolean {
    const index = this.callbackList.findIndex(
      (call) => call.callback === callback
    );
    if (index !== -1) {
      this.callbackList.splice(index, 1);
      return true;
    }
    return false;
  }

  updateCallback(
    callback: (result: TOut, ...args) => void,
    maxDelaySeconds = 5 * 60
  ): void {
    const index = this.callbackList.findIndex(
      (call) => call.callback === callback
    );
    if (index !== -1) {
      this.callbackList[index].maxDelaySeconds = maxDelaySeconds;
    }
    this.changeLoop();
  }

  /*async isCashValid(): Promise<boolean> {
    //await until(() => !this.isRefreshing);
    if (!this.valid) return false;
    const timestamp = Date.now();
    if (this.callTimestamp >= timestamp - this.maxDelaySeconds) {
      return true;
    }
    const modificationData: ModificationData =
      await this.parameter.requestModificationTimestamp();
    return (
      this.lastModification === modificationData.lastModification &&
      this.rowCount === modificationData.rowCount
    );
  }*/

  async getData(): Promise<TOut> {
    const timestamp = Date.now();
    await until(() => !this.isRefreshing);
    if (this.data !== undefined) {
      const delta =
        this.callTimestamp > -1
          ? (timestamp - this.callTimestamp) / 1000
          : this.maxDelaySeconds * 10;
      if (delta < this.maxDelaySeconds && this.valid) {
        return this.data;
      }
      /*this.isRefreshing = true;
      if (await this.isCashValid()) {
        return this.data;
      }*/
    }
    const valid = await this.executeCall();
    if (!valid) {
      await until(() => !this.isRefreshing);
    }
    return this.data;
  }

  private async executeCall(): Promise<boolean> {
    this.isRefreshing = true;
    const refreshCount = this.refreshCount + 1;
    this.refreshCount = refreshCount;
    await delay(500);
    if (this.refreshCount !== refreshCount) {
      return false;
    }
    this.callTimestamp = Date.now();
    this.data = await this.parameter.executeCall();
    if (Array.isArray(this.data)) this.rowCount = this.data.length;
    else this.rowCount = 1;
    this.isRefreshing = false;
    this.valid = true;
    return true;
  }

  async refreshData(): Promise<TOut> {
    const oldData = JSON.stringify(this.data);
    if (await this.executeCall()) {
      if (JSON.stringify(this.data) != oldData) {
        for (const callback of this.callbackList) {
          callback.execute(this.data);
        }
      }
    }
    return this.data;
  }

  update(): void {
    if (this.callbackList.length > 0) {
      const oldData = JSON.stringify(this.data);
      this.getData().then((newData) => {
        if (JSON.stringify(newData) !== oldData) {
          for (const callback of this.callbackList) {
            callback.execute(newData);
          }
        }
        this.loop();
      });
    } else {
      this.inactive = true;
    }
  }

  private activeLoop: number | null = null;
  private activeLoopStart = 0;
  private activeLoopDelay = 0;
  private loop(): void {
    this.breakLoop();
    this.inactive = false;
    this.activeLoopStart = Date.now();
    this.activeLoopDelay = this.maxDelaySeconds * 1000;
    this.activeLoop = setTimeout(() => {
      this.activeLoop = null;
      this.update();
    }, this.activeLoopDelay);
  }

  private breakLoop(): void {
    if (this.activeLoop !== null) {
      clearTimeout(this.activeLoop);
      this.activeLoop = null;
    }
  }

  private changeLoop(): void {
    const remindingTime = Math.ceil(
      (this.activeLoopStart + this.activeLoopDelay - Date.now()) / 1000
    );
    if (
      (this.activeLoop !== null && remindingTime > this.maxDelaySeconds) ||
      (this.activeLoop === null && this.inactive)
    ) {
      this.loop();
    }
  }
}

export class SimplifiedCashEntry<T = any> extends CashEntry<T, T> {}

const dataCash: { [key: string]: CashEntry<any, any> } = {};

export function registerSimplifiedGet<T = any>(
  url: string,
  callback: (result: T, ...args) => void,
  empty: T | any = {},
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5,
  dataPreparation: ((data: T) => Promise<T>) | null = null,
  args: any[] | null = null,
  customKeyPrefix = ''
): SimplifiedCashEntry<T> {
  return registerGet<T, T>(
    url,
    callback,
    empty,
    authHeaderType,
    maxDelaySeconds,
    dataPreparation,
    args,
    customKeyPrefix
  );
}

export function registerGet<TIn = any, TOut = any>(
  url: string,
  callback: (result: TOut, ...args) => void,
  empty: TIn | any = {},
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5,
  dataPreparation: ((data: TIn) => Promise<TOut>) | null = null,
  args: any[] | null = null,
  customKeyPrefix = ''
): CashEntry<TIn, TOut> {
  const key = `${customKeyPrefix}${url.split('?')[0]}`;
  if (!(key in dataCash)) {
    dataCash[key] = new CashEntry<TIn, TOut>(
      url,
      empty,
      authHeaderType,
      dataPreparation
    );
  }
  //const refreshCount = dataCash[key].refreshCount;
  const callClass = dataCash[key].addCallback(callback, maxDelaySeconds, args);
  dataCash[key].getData().then((result) => {
    /*if (dataCash[key]?.refreshCount === refreshCount) {
      callClass.execute(result);
    }*/
    callClass.execute(result, true);
  });
  return dataCash[key];
}

/*export function registerBatchGet<TIn = any, TOut = any>(
  key: string,
  cashes: CashEntry<TIn, TOut>[]
): CashEntry<TIn, TOut> {
}*/

export function deregisterGet(
  url: string,
  callback: (result: any, ...args) => void
): void {
  const key = url.split('?')[0];
  if (key in dataCash) {
    dataCash[key].removeCallback(callback);
    if (dataCash[key].callbackList.length === 0) {
      setTimeout(() => delete dataCash[key], 2000);
    }
  }
}

export function deregisterAllGet(
  callback: (result: any, ...args) => void
): void {
  for (const key of Object.keys(dataCash)) {
    let hasCallback = dataCash[key].removeCallback(callback);
    while (hasCallback) hasCallback = dataCash[key].removeCallback(callback);
    if (dataCash[key].callbackList.length === 0) {
      setTimeout(() => delete dataCash[key], 2000);
    }
  }
}

export function refreshCash(url: string): void {
  const key = url.split('?')[0];
  if (key in dataCash) {
    dataCash[key].refreshData();
  }
}

export function getCash(url: string): any {
  const key = url.split('?')[0];
  if (key in dataCash) {
    return dataCash[key].data;
  }
  return null;
}

export function getCallStatistic(): { [key: string]: number } {
  const result: { [key: string]: number } = {};
  for (const key of Object.keys(dataCash)) {
    const call = dataCash[key].parameter;
    result[call.url] = call.callCount;
  }
  return result;
}
