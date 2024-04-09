import { Idea } from '@/types/api/Idea';
import gameConfig from '@/modules/brainstorming/missionmap/data/gameConfig.json';
import { until } from '@/utils/wait';
import { defaultCenter } from '@/utils/map';
import gameConfigMoveIt from '@/modules/playing/moveit/data/gameConfig.json';
import { Module } from '@/types/api/Module';
import { ElectricityInfluence } from '@/modules/brainstorming/missionmap/types/ElectricityInfluence';

export async function setEmptyParameterIfNotExists(
  idea: Idea,
  getModule: () => Module | null | undefined,
  shareData = true
): Promise<void> {
  if (!idea.parameter) idea.parameter = {};
  if (!idea.parameter.points) {
    idea.parameter.points = 500;
  }
  if (!('shareData' in idea.parameter)) {
    idea.parameter.shareData = shareData;
  }
  if (!idea.parameter.influenceAreas) {
    idea.parameter.influenceAreas = {};
    for (const parameter of Object.keys(gameConfig.parameter)) {
      idea.parameter.influenceAreas[parameter] = 0;
    }
  }
  await until(() => !!getModule());
  const module = getModule();
  if (!idea.parameter.position) {
    if (module && module.parameter.mapCenter) {
      idea.parameter.position = module.parameter.mapCenter;
    } else idea.parameter.position = [...defaultCenter] as [number, number];
  }
  if (
    module &&
    module.parameter.effectElectricity &&
    !idea.parameter.electricity
  ) {
    idea.parameter.electricity = {
      influence: ElectricityInfluence.INCREASE_ELECTRICITY_DEMAND,
      type: Object.keys(gameConfigMoveIt.electricity)[0],
      value: 0,
    };
    /*for (const parameter of Object.keys(gameConfigMoveIt.electricity)) {
      idea.parameter.electricity[parameter] = 0;
    }*/
  }
  if (!idea.parameter.minParticipants) {
    if (module && module.parameter.minParticipants) {
      idea.parameter.minParticipants = module.parameter.minParticipants;
    } else idea.parameter.minParticipants = 3;
  }
  if (!idea.parameter.minPoints) {
    if (module && module.parameter.minPoints) {
      idea.parameter.minPoints = module.parameter.minPoints;
    } else idea.parameter.minPoints = 100;
  }
  if (!idea.parameter.maxPoints) {
    if (module && module.parameter.maxPoints) {
      idea.parameter.maxPoints = module.parameter.maxPoints;
    } else idea.parameter.maxPoints = 1000;
  }
  if (!idea.parameter.explanationList) {
    if (module && module.parameter.explanationList) {
      idea.parameter.explanationList = [...module.parameter.explanationList];
    } else idea.parameter.explanationList = ['', '', ''];
  }
}
