import { Stage, game, Sprite } from 'melonjs';
import { GameData } from '@/games/coolit/src/resources.js';

/**
 * The menu stage from 'Cool It'.
 * Loads the background and shows the menu UI.
 *
 * @extends Stage
 * @see Stage
 * @see MenuUI
 */
export default class Menu extends Stage {
  onResetEvent(...args) {
    // Load the map and UI container.
    const level = new Sprite(
      game.viewport.width / 2,
      game.viewport.height / 2,
      GameData.game.imagePresets.menuSettings
    );
    level.addAnimation('image', [GameData.game.imageIds.menu.map]);
    level.setCurrentAnimation('image');
    game.world.addChild(level, GameData.zOrder.background);

    GameData.instances.menuUI.addElements();
  }
}
