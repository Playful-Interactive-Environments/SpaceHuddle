import { OSRM } from '@routingjs/osrm';
import { Client, RoutingJSAPIError } from '@routingjs/core';
import options from '@routingjs/core/dist/es/options';
import axios from 'axios';
import axiosRetry, { isNetworkOrIdempotentRequestError } from 'axios-retry';

export interface OSRMWayPoint {
  hint?: string;
  distance?: number;
  name: string;
  location: number[];
}

const handleOSRMError = (error) => {
  let _a, _b, _c, _d;
  const props = {
    statusCode:
      (_a = error.response) === null || _a === void 0 ? void 0 : _a.status,
    status:
      (_b = error.response) === null || _b === void 0 ? void 0 : _b.statusText,
    errorCode:
      (_c = error.response) === null || _c === void 0 ? void 0 : _c.data.code,
    errorMessage:
      (_d = error.response) === null || _d === void 0
        ? void 0
        : _d.data.message,
  };
  throw new RoutingJSAPIError(error.message, props);
};

/* eslint-disable @typescript-eslint/no-explicit-any*/
class CustomClient<
  ResponseType,
  GetParams extends Record<string | number, any> | undefined = undefined,
  PostParams extends Record<string | number, any> | undefined = undefined
> extends Client<ResponseType, GetParams, PostParams> {
  constructor(
    baseURL,
    userAgent = options.defaultUserAgent,
    timeout = options.defaultTimeout,
    retryOverQueryLimit = false,
    headers,
    maxRetries = options.defaultMaxRetries,
    additionalAxiosOpts
  ) {
    super(
      baseURL,
      userAgent,
      timeout,
      retryOverQueryLimit,
      headers,
      maxRetries,
      additionalAxiosOpts
    );
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    this.headers = headers;
    const source = userAgent ? { 'User-Agent': userAgent } : {};
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    this.headers = Object.assign(
      Object.assign(Object.assign({}, options.defaultHeaders, source)),
      this.headers
    );
    this.axiosOptions = Object.assign(
      { headers: this.headers, timeout },
      additionalAxiosOpts
    );
    this.axiosInstance = axios.create(this.axiosOptions);
    const retryOpts = {
      retries: maxRetries,
      retryCondition: retryOverQueryLimit
        ? (error) => {
            let _a;
            return (
              isNetworkOrIdempotentRequestError(error) ||
              ((_a = error.response) === null || _a === void 0
                ? void 0
                : _a.status) == 429
            );
          }
        : undefined,
      retryDelay: axiosRetry.exponentialDelay,
      onRetry: (number, error) => {
        let _a, _b;
        return console.log(
          `Request failed with status code ${
            (_a = error.response) === null || _a === void 0 ? void 0 : _a.status
          }: ${
            (_b = error.response) === null || _b === void 0
              ? void 0
              : _b.statusText
          }. Retry number ${number}.`
        );
      },
    };
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    axiosRetry(this.axiosInstance, retryOpts);
  }
}

export class OsrmCustom extends OSRM {
  constructor(profile = 'bike', clientArgs) {
    super(clientArgs);
    const {
      apiKey,
      baseUrl,
      userAgent,
      headers,
      timeout,
      retryOverQueryLimit,
      maxRetries,
      axiosOpts,
    } = clientArgs || {};
    this.apiKey = apiKey; // TODO: add to requests
    //const defaultURL = 'http://router.project-osrm.org';;
    const defaultURL = `https://routing.openstreetmap.de/routed-${profile}`;
    this.client =
      new CustomClient(
        baseUrl || defaultURL,
        userAgent,
        timeout,
        retryOverQueryLimit,
        headers,
        maxRetries,
        axiosOpts
      ) || {};
  }

  async nearest(
    locations,
    profile = 'driving',
    number = 1,
    directionsOpts = {},
    dryRun = false
  ): Promise<any> {
    const coords = `${locations[1]},${locations[0]}`;
    const params = OSRM.getDirectionParams(directionsOpts);
    return this.client
      .request({
        endpoint: `/nearest/v1/${profile}/${coords}?number=${number}`,
        getParams: params,
        dryRun,
      })
      .then((res: any) => {
        return res;
      })
      .catch(handleOSRMError);
  }
}
