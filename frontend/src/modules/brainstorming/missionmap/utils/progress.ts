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
      const ideaValues: { [key: string]: number } = {
        ...result[result.length - 1],
      };
      /*let addedInfluence = 0;
      const influencedValues: string[] = [];
      for (const electricityName in gameConfigMoveIt.electricity) {
        const influence = progressValue[electricityName];
        if (influence) {
          ideaValues[electricityName] += influence;
          addedInfluence += influence;
          influencedValues.push(electricityName);
        }
      }*/
      if (
        progressValue.influence ===
        ElectricityInfluence.INCREASE_ELECTRICITY_DEMAND
      ) {
        ideaValues.gas += progressValue.value as number;
        const increaseFactor = 100 / (100 + (progressValue.value as number));
        if (progressValue.value !== 0) {
          for (const electricityName of Object.keys(
            gameConfigMoveIt.electricity
          )) {
            ideaValues[electricityName] *= increaseFactor;
          }
        }
      } else {
        const influencedValues: string[] = [];
        let addedInfluence = 0;
        if (progressValue.type) {
          influencedValues.push(progressValue.type as string);
          if (!isNaN(ideaValues[progressValue.type]))
            (ideaValues[progressValue.type] as number) +=
              progressValue.value as number;
          addedInfluence = progressValue.value as number;
        } else {
          for (const electricityName in gameConfigMoveIt.electricity) {
            const influence = progressValue[electricityName] as number;
            if (influence) {
              ideaValues[electricityName] += influence;
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
                if (ideaValues[electricityName] > addedInfluence) {
                  ideaValues[electricityName] -= addedInfluence;
                  break;
                }
                addedInfluence -= ideaValues[electricityName];
                ideaValues[electricityName] = 0;
              } else {
                ideaValues[electricityName] -= addedInfluence;
                break;
              }
            }
          }
        }
      }
      result.push(ideaValues);
    }
  }
  return result;
}

export function getElectricityProgress(
  decidedIdeas: Idea[],
  module: Module | null
): { [key: string]: ProgressValues } {
  const result: { [key: string]: ProgressValues } = {};
  for (const electricityName in gameConfigMoveIt.electricity) {
    const electricityValue =
      gameConfigMoveIt.electricity[electricityName].value;
    result[electricityName] = {
      origin: electricityValue,
      progress: electricityValue,
    };
  }
  if (module && module.parameter.effectElectricity) {
    for (const idea of decidedIdeas) {
      if (
        idea.parameter.electricity.influence ===
        ElectricityInfluence.INCREASE_ELECTRICITY_DEMAND
      ) {
        result.gas.progress += idea.parameter.electricity.value as number;
        const increaseFactor =
          100 / (100 + (idea.parameter.electricity.value as number));
        if (idea.parameter.electricity.value !== 0) {
          for (const electricityName of Object.keys(
            gameConfigMoveIt.electricity
          )) {
            result[electricityName].progress *= increaseFactor;
          }
        }
      } else {
        const influencedValues: string[] = [];
        let addedInfluence = 0;
        if (idea.parameter.electricity.type) {
          influencedValues.push(idea.parameter.electricity.type as string);
          result[idea.parameter.electricity.type].progress += idea.parameter
            .electricity.value as number;
          addedInfluence = idea.parameter.electricity.value as number;
        } else {
          for (const electricityName in gameConfigMoveIt.electricity) {
            const influence = idea.parameter.electricity[electricityName];
            result[electricityName].progress += influence;
            addedInfluence += influence;
            influencedValues.push(electricityName);
          }
        }
        if (addedInfluence !== 0) {
          for (const electricityName of Object.keys(
            gameConfigMoveIt.electricity
          ).reverse()) {
            if (!influencedValues.includes(electricityName)) {
              if (addedInfluence > 0) {
                if (result[electricityName].progress > addedInfluence) {
                  result[electricityName].progress -= addedInfluence;
                  break;
                }
                addedInfluence -= result[electricityName].progress;
                result[electricityName].progress = 0;
              } else {
                result[electricityName].progress -= addedInfluence;
                break;
              }
            }
          }
        }
      }
      /*let addedInfluence = 0;
      for (const electricityName in gameConfigMoveIt.electricity) {
        const influence = idea.parameter.electricity[electricityName];
        result[electricityName].progress += influence;
        addedInfluence += influence;
      }
      if (addedInfluence > 0) {
        for (const electricityName of Object.keys(
          gameConfigMoveIt.electricity
        ).reverse()) {
          if (result[electricityName].progress > addedInfluence) {
            result[electricityName].progress -= addedInfluence;
            break;
          }
          addedInfluence -= result[electricityName].progress;
          result[electricityName].progress = 0;
        }
      } else if (addedInfluence < 0) {
        const electricityName = Object.keys(
          gameConfigMoveIt.electricity
        ).reverse()[0];
        result[electricityName].progress -= addedInfluence;
      }*/
    }
  }
  return result;
}
