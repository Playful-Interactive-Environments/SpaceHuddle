import gameConfig from '@/modules/playing/findit/data/gameConfig.json';
import { Idea } from '@/types/api/Idea';
import * as placeable from '@/modules/playing/findit/types/Placeable';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export function getDefaultLevelType(): string {
  if (Object.keys(gameConfig).length > 0) return Object.keys(gameConfig)[0];
  return '';
}

export function getGameConfigTypes(levelType: string): string[] {
  return Object.keys(gameConfig[levelType]).filter(
    (config) => config !== 'settings'
  );
}

export function getSettingsForLevelType(levelType: string): any {
  return gameConfig[levelType].settings;
}

export function getTypeForLevel(level: Idea): string {
  return level.parameter.type ? level.parameter.type : getDefaultLevelType();
}

export function getItemsForLevel(level: Idea): placeable.PlaceableBase[] {
  const items = level.parameter.items
    ? (level.parameter.items as placeable.PlaceableBase[])
    : (Object.values(level.parameter) as placeable.PlaceableBase[]).filter(
        (item) => Object.hasOwn(item, 'position')
      );
  const settings = getSettingsForLevel(level);
  for (const item of items) {
    if (!item.type) item.type = settings.defaultType;
  }
  return items;
}

export function getSettingsForLevel(level: Idea): any {
  return getSettingsForLevelType(getTypeForLevel(level));
}

export function getConfigForLevel(level: Idea): any {
  return gameConfig[getTypeForLevel(level)];
}
