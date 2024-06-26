import { apiExecutePost } from '@/services/api';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Tutorial } from '@/types/api/Tutorial';
import { Emitter } from 'mitt';
import { EventType } from '@/types/enum/EventType';
import * as cashService from '@/services/cash-service';

/* eslint-disable @typescript-eslint/no-explicit-any*/
const tutorialUrl = `/tutorial_steps/`;

export const registerGetList = (
  callback: (result: Tutorial[]) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 60 * 24
): cashService.SimplifiedCashEntry<Tutorial[]> => {
  return cashService.registerSimplifiedGet<Tutorial[]>(
    tutorialUrl,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds,
    async (items: Tutorial[]) => {
      const tutorialSteps: Tutorial[] = [];
      items.forEach((data) => {
        if (
          !tutorialSteps.find(
            (step) => step.step === data.step && step.type === data.type
          )
        )
          tutorialSteps.push(data);
      });
      return tutorialSteps;
    }
  );
};

export const deregisterGetList = (
  callback: (result: Tutorial[]) => void
): void => {
  cashService.deregisterGet(tutorialUrl, callback);
};

export const postStep = async (
  data: Tutorial,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Tutorial> => {
  return await apiExecutePost<Tutorial>(
    `/tutorial_step/`,
    data,
    authHeaderType
  );
};

export const reactivateTutorial = async (
  type: string,
  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  eventBus: Emitter<Record<EventType, unknown>>
): Promise<void> => {
  const tutorialSteps = cashService.getCash(tutorialUrl);
  if (tutorialSteps) {
    let index = tutorialSteps.findIndex((step) => step.type === type);
    while (index >= 0) {
      tutorialSteps.splice(index, 1);
      index = tutorialSteps.findIndex((step) => step.type === type);
    }
    eventBus.emit(EventType.CHANGE_TUTORIAL, tutorialSteps);
    cashService.refreshCallback(tutorialUrl);
  } else cashService.refreshCash(tutorialUrl);
};

export const addTutorialStep = async (
  data: Tutorial,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  eventBus: Emitter<Record<EventType, unknown>>
): Promise<Tutorial[]> => {
  await postStep(data, authHeaderType);
  const tutorialSteps = cashService.getCash(tutorialUrl) as Tutorial[];
  if (tutorialSteps) {
    tutorialSteps.push(data);
    cashService.refreshCallback(tutorialUrl);
  } else cashService.refreshCash(tutorialUrl);
  eventBus.emit(EventType.CHANGE_TUTORIAL, tutorialSteps);
  return tutorialSteps;
};
