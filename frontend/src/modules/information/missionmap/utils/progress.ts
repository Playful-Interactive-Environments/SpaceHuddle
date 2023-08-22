import { Vote, VoteParameterResult } from '@/types/api/Vote';
import { Idea } from '@/types/api/Idea';
import gameConfig from '@/modules/information/missionmap/data/gameConfig.json';
import gameConfigMoveIt from '@/modules/information/moveit/data/gameConfig.json';
import { Module } from '@/types/api/Module';

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
  module: Module | undefined
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

export function getElectricityProgress(
  decidedIdeas: Idea[],
  module: Module | undefined
): { [key: string]: ProgressValues } {
  const result: { [key: string]: ProgressValues } = {};
  for (const electricityName in gameConfigMoveIt.electricity) {
    const electricityValue =
      gameConfigMoveIt.electricity[electricityName].value;
    result[electricityName] = {
      origin: electricityValue,
      progress: electricityValue,
    };
    if (module && module.parameter.effectElectricity) {
      for (const idea of decidedIdeas) {
        const influence = idea.parameter.electricity[electricityName];
        result[electricityName].progress += influence;
      }
    }
  }
  console.log(result, decidedIdeas);
  return result;
}
