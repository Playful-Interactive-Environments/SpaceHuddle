import { Idea } from '@/types/api/Idea';
import * as placeable from '@/types/game/Placeable';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export function getDefaultLevelType(
  gameConfig: placeable.PlaceableConfig
): string {
  if (Object.keys(gameConfig).length > 0) return Object.keys(gameConfig)[0];
  return '';
}

export function getGameConfigTypes(
  gameConfig: placeable.PlaceableConfig,
  levelType: string
): string[] {
  return Object.keys(gameConfig[levelType].categories);
}

export function getSettingsForLevelType(
  gameConfig: placeable.PlaceableConfig,
  levelType: string
): any {
  return gameConfig[levelType].settings;
}

export function getTypeForLevel(
  gameConfig: placeable.PlaceableConfig,
  level: Idea
): string {
  return level.parameter.type
    ? level.parameter.type
    : getDefaultLevelType(gameConfig);
}

export function getItemsForLevel(
  gameConfig: placeable.PlaceableConfig,
  level: Idea
): placeable.PlaceableBase[] {
  const items = level.parameter.items
    ? (level.parameter.items as placeable.PlaceableBase[])
    : (Object.values(level.parameter) as placeable.PlaceableBase[]).filter(
        (item) => Object.hasOwn(item, 'position')
      );
  const settings = getSettingsForLevel(gameConfig, level);
  for (const item of items) {
    if (!item.type) item.type = settings.defaultType;
  }
  return items;
}

export function getSettingsForLevel(
  gameConfig: placeable.PlaceableConfig,
  level: Idea
): any {
  return getSettingsForLevelType(
    gameConfig,
    getTypeForLevel(gameConfig, level)
  );
}

export function getConfigForLevel(
  gameConfig: placeable.PlaceableConfig,
  level: Idea
): any {
  return gameConfig[getTypeForLevel(gameConfig, level)];
}
