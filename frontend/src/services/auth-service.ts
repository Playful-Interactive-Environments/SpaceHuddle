import jwt_decode from 'jwt-decode';
import app from '@/main';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as tutorialService from '@/services/tutorial-service';
import { Tutorial } from '@/types/api/Tutorial';
import { EventType } from '@/types/enum/EventType';
import { Emitter } from 'mitt';

const JWT_KEY = 'jwt';
const JWT_KEY_MODERATOR = 'jwt-moderator';
const JWT_KEY_PARTICIPANT = 'jwt-participant';
const BROWSER_KEYS = 'keys';
const LAST_BROWSER_KEY = 'key';
const USER_KEY = 'user';
const tutorialSteps: Tutorial[] = [];

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const getTutorialSteps = async (): Promise<Tutorial[]> => {
  if (tutorialSteps.length == 0) {
    await tutorialService.getList().then((list) => {
      list.forEach((data) => {
        if (
          !tutorialSteps.find(
            (step) => step.step === data.step && step.type === data.type
          )
        )
          tutorialSteps.push(data);
      });
    });
  }
  return tutorialSteps;
};

export const reactivateTutorial = async (
  type: string,
  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  eventBus: Emitter<Record<EventType, unknown>>
): Promise<void> => {
  let index = tutorialSteps.findIndex((step) => step.type === type);
  while (index >= 0) {
    tutorialSteps.splice(index, 1);
    index = tutorialSteps.findIndex((step) => step.type === type);
  }
  eventBus.emit(EventType.CHANGE_TUTORIAL, tutorialSteps);
};

// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
export const addTutorialStep = (
  data: Tutorial,
  eventBus: Emitter<Record<EventType, unknown>>
): void => {
  if (
    !tutorialSteps.find(
      (step) => step.step === data.step && step.type === data.type
    )
  )
    tutorialSteps.push(data);
  tutorialService.postStep(data);
  eventBus.emit(EventType.CHANGE_TUTORIAL, tutorialSteps);
};

export const isAuthenticated = (): boolean => {
  const jwtFromStorage = getAccessTokenModerator();
  const jwtFromStorageParticipant = getAccessTokenParticipant();
  return !!jwtFromStorage || !!jwtFromStorageParticipant;
};

const authorisationHasProperty = (
  propertyName: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): boolean => {
  const jwtFromStorage = getAccessToken(authHeaderType);
  if (jwtFromStorage != null) {
    const decoded = jwt_decode(jwtFromStorage) as any;
    if (decoded && propertyName in decoded) {
      return true;
    }
  }
  return false;
};

export const isParticipant = (): boolean => {
  return authorisationHasProperty(
    'participantId',
    EndpointAuthorisationType.PARTICIPANT
  );
};

export const isUser = (): boolean => {
  return authorisationHasProperty(
    'userId',
    EndpointAuthorisationType.MODERATOR
  );
};

export const getAccessToken = (
  authHeaderType = EndpointAuthorisationType.MODERATOR
): string | null => {
  switch (authHeaderType) {
    case EndpointAuthorisationType.MODERATOR:
      return getAccessTokenModerator();
    case EndpointAuthorisationType.PARTICIPANT:
      return getAccessTokenParticipant();
  }
  return app.config.globalProperties.$cookies.get(JWT_KEY);
};

export const setAccessToken = (jwt: string): void => {
  app.config.globalProperties.$cookies.set(JWT_KEY, 'Bearer ' + jwt);
};

export const setAccessTokenModerator = (jwt: string): void => {
  app.config.globalProperties.$cookies.set(JWT_KEY_MODERATOR, 'Bearer ' + jwt);
  tutorialService.getList().then((list) => {
    tutorialSteps.push(...list);
  });
};

export const getAccessTokenModerator = (): string | null => {
  return app.config.globalProperties.$cookies.get(JWT_KEY_MODERATOR);
};

export const setAccessTokenParticipant = (jwt: string): void => {
  app.config.globalProperties.$cookies.set(
    JWT_KEY_PARTICIPANT,
    'Bearer ' + jwt
  );
};

export const getAccessTokenParticipant = (): string | null => {
  return app.config.globalProperties.$cookies.get(JWT_KEY_PARTICIPANT);
};

export const removeAccessToken = (): void => {
  //app.config.globalProperties.$cookies.remove(JWT_KEY_MODERATOR);
  //app.config.globalProperties.$cookies.remove(JWT_KEY_PARTICIPANT);
};

export const removeAccessTokenModerator = (): void => {
  app.config.globalProperties.$cookies.remove(JWT_KEY_MODERATOR);
};

export const setBrowserKey = (key: string): void => {
  app.config.globalProperties.$cookies.set(LAST_BROWSER_KEY, key);
  const keys = getBrowserKeys();
  if (!keys.includes(key)) keys.push(key);
  app.config.globalProperties.$cookies.set(BROWSER_KEYS, JSON.stringify(keys));
};

export const getBrowserKeys = (): string[] => {
  let keys = eval(app.config.globalProperties.$cookies.get(BROWSER_KEYS));
  if (!keys || !Array.isArray(keys) || keys.length === 0) {
    const key = getLastBrowserKey();
    if (!key) keys = [];
    else keys = [key];
  }
  return keys;
};

export const getLastBrowserKey = (): string | null => {
  return app.config.globalProperties.$cookies.get(LAST_BROWSER_KEY);
};

export const setUserData = (email: string): void => {
  app.config.globalProperties.$cookies.set(USER_KEY, email);
};

export const getUserData = (): string | null => {
  return app.config.globalProperties.$cookies.get(USER_KEY);
};
