import { Vote, VoteParameterResult } from '@/types/api/Vote';
import { Idea } from '@/types/api/Idea';
import gameConfig from '@/modules/brainstorming/missionmap/data/gameConfig.json';
import gameConfigMoveIt from '@/modules/playing/moveit/data/gameConfig.json';
import { Module } from '@/types/api/Module';
import { ElectricityInfluence } from '@/modules/brainstorming/missionmap/types/ElectricityInfluence';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export function calculateDecidedIdeasFromResult(
  voteResults: VoteParameterResult[],
  ideas: Idea[]
): Idea[] {
  if (voteResults.length > 0) {
    const decidedIdeas: Idea[] = [];
    for (const idea of ideas) {
      const vote = voteResults.find((vote) => vote.ideaId === idea.id);
      if (vote) {
        if (vote.sum >= idea.parameter.points) {
          decidedIdeas.push(idea);
        }
      }
    }
    return decidedIdeas;
  }
  return [];
}

export function sortDecidedIdeasFromResult(
  voteResults: VoteParameterResult[],
  ideas: Idea[]
): Idea[] {
  if (voteResults.length > 0) {
    const decidedIdeas: { idea: Idea; percent: number }[] = [];
    for (const idea of ideas) {
      const vote = voteResults.find((vote) => vote.ideaId === idea.id);
      if (vote) {
        decidedIdeas.push({
          idea: idea,
          percent: vote.sum / idea.parameter.points,
        });
      }
    }
    return decidedIdeas
      .sort((a, b) => b.percent - a.percent)
      .map((item) => item.idea);
  }
  return [];
}

export function calculateDecidedIdeasFromVotes(
  votes: Vote[],
  ideas: Idea[]
): Idea[] {
  if (votes.length > 0) {
    const decidedIdeas: Idea[] = [];
    for (const idea of ideas) {
      const ideaVotes = votes.filter((vote) => vote.ideaId === idea.id);
      let sum = 0;
      for (const vote of ideaVotes) {
        sum += vote.parameter.points;
      }
      if (sum >= idea.parameter.points) {
        decidedIdeas.push(idea);
      }
    }
    return decidedIdeas;
  }
  return [];
}

export function calculateDecidedIdeasFromVotesSorted(
  votes: Vote[],
  ideas: Idea[]
): Idea[] {
  if (votes.length > 0) {
    const decidedIdeas: { idea: Idea; timestamp: number }[] = [];
    for (const idea of ideas) {
      const ideaVotes = votes.filter((vote) => vote.ideaId === idea.id);
      let sum = 0;
      let lastVote = 0;
      for (const vote of ideaVotes) {
        const timestamp = new Date(vote.timestamp).getTime();
        sum += vote.parameter.points;
        if (lastVote < timestamp) lastVote = timestamp;
      }
      if (sum >= idea.parameter.points) {
        decidedIdeas.push({ idea: idea, timestamp: lastVote });
      }
    }
    return decidedIdeas
      .sort((a, b) => a.timestamp - b.timestamp)
      .map((item) => item.idea);
  }
  return [];
}

export interface ProgressValues {
  origin: number;
  progress: number;
}

export function getProgress(
  decidedIdeas: Idea[],
  module: Module | null
): { [key: string]: ProgressValues } {
  const result: { [key: string]: ProgressValues } = {};
  if (module) {
    for (const parameterName in gameConfig.parameter) {
      const origin = module.parameter[parameterName];
      result[parameterName] = {
        origin: origin,
        progress: origin,
      };
      for (const idea of decidedIdeas) {
        const influence = idea.parameter.influenceAreas[parameterName];
        result[parameterName].progress += influence;
      }
    }
  }
  return result;
}

export function getElectricityDevelopment(
  influence: ElectricityInfluence,
  type: string | null,
  value: number,
  initValues: { [key: string]: number } | null = null,
  progressValue: { [key: string]: number } = {}
): { [key: string]: number } {
  if (!initValues) {
    initValues = {};
    for (const electricityName in gameConfigMoveIt.electricity) {
      initValues[electricityName] =
        gameConfigMoveIt.electricity[electricityName].value;
    }
  }
  const result: { [key: string]: number } = { ...initValues };
  if (influence === ElectricityInfluence.INCREASE_ELECTRICITY_DEMAND) {
    result.gas += value;
    if (result.gas < 0) {
      let remainingValue = result.gas;
      result.gas = 0;
      for (const electricityName of Object.keys(
        gameConfigMoveIt.electricity
      ).reverse()) {
        result[electricityName] += remainingValue;
        if (result[electricityName] < 0) {
          remainingValue = result[electricityName];
          result[electricityName] = 0;
        } else {
          break;
        }
      }
    }
    const increaseFactor = 100 / (100 + value);
    if (value !== 0) {
      for (const electricityName of Object.keys(gameConfigMoveIt.electricity)) {
        result[electricityName] *= increaseFactor;
      }
    }
  } else {
    const influencedValues: string[] = [];
    let addedInfluence = 0;
    if (type) {
      influencedValues.push(type);
      if (!isNaN(result[type])) result[type] += value;
      addedInfluence = value;
    } else {
      for (const electricityName in gameConfigMoveIt.electricity) {
        const influence = progressValue[electricityName];
        if (influence) {
          result[electricityName] += influence;
          addedInfluence += influence;
          influencedValues.push(electricityName);
        }
      }
    }
    if (addedInfluence !== 0) {
      for (const electricityName of Object.keys(
        gameConfigMoveIt.electricity
      ).reverse()) {
        if (!influencedValues.includes(electricityName)) {
          if (addedInfluence > 0) {
            if (result[electricityName] > addedInfluence) {
              result[electricityName] -= addedInfluence;
              break;
            }
            addedInfluence -= result[electricityName];
            result[electricityName] = 0;
          } else {
            result[electricityName] -= addedInfluence;
            break;
          }
        }
      }
    }
  }
  return result;
}

export function getElectricityProgressSteps(
  inputProgress: { [key: string]: number | string }[],
  module: Module | null
): { [key: string]: number }[] {
  const result: { [key: string]: number }[] = [];
  const startValues: { [key: string]: number } = {};
  for (const electricityName in gameConfigMoveIt.electricity) {
    startValues[electricityName] =
      gameConfigMoveIt.electricity[electricityName].value;
  }
  result.push(startValues);
  if (module && module.parameter.effectElectricity) {
    for (const progressValue of inputProgress) {
      result.push(
        getElectricityDevelopment(
          progressValue.influence as any,
          progressValue.type as string,
          progressValue.value as number,
          { ...result[result.length - 1] },
          progressValue as any
        )
      );
    }
  }
  return result;
}

export function getElectricityProgress(
  decidedIdeas: Idea[],
  module: Module | null
): { [key: string]: ProgressValues } {
  let resultData: { [key: string]: number } = {};
  for (const electricityName in gameConfigMoveIt.electricity) {
    resultData[electricityName] =
      gameConfigMoveIt.electricity[electricityName].value;
  }
  if (module && module.parameter.effectElectricity) {
    for (const idea of decidedIdeas) {
      resultData = getElectricityDevelopment(
        idea.parameter.electricity.influence as any,
        idea.parameter.electricity.type as string,
        idea.parameter.electricity.value as number,
        { ...resultData },
        idea.parameter.electricity as any
      );
    }
  }
  const result: { [key: string]: ProgressValues } = {};
  for (const electricityName in gameConfigMoveIt.electricity) {
    result[electricityName] = {
      origin: gameConfigMoveIt.electricity[electricityName].value,
      progress: resultData[electricityName],
    };
  }
  return result;
}
