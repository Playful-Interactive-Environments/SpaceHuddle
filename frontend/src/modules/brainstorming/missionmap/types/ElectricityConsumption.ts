import * as themeColors from '@/utils/themeColors';

//https://oesterreichsenergie.at/fakten/energiegrafiken/detailseite/stromverbrauch-in-oesterreich-ab-1970
export const ElectricityConsumption = {
  industry: {
    value: 28,
    color: themeColors.getEvaluatingColor(),
  },
  traffic: {
    value: 3.4,
    color: themeColors.getInformingColor(),
  },
  services: {
    value: 12,
    color: themeColors.getStructuringColor(),
  },
  household: {
    value: 19,
    color: themeColors.getPlayingColor(),
  },
  agriculture: {
    value: 1.3,
    color: themeColors.getBrainstormingColor(),
  },
};
