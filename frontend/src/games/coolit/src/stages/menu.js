import { Stage, game, Sprite } from 'melonjs';
import { GameData } from '@/games/coolit/src/resources.js';
export default class Menu extends Stage {
  // Called when the game invokes a reset() on this Stage, i.e. upon changing to this stage after loading.
  // Do basic scene setups here (Backgrounds, Entities, UI, ...).
  onResetEvent(...args) {
    // Load the map and UI container.
    const level = new Sprite(
      game.viewport.width / 2,
      game.viewport.height / 2,
      {
        image: 'menu',
        framewidth: 1080,
        frameheight: 1920,
      }
    );
    level.addAnimation('image', [GameData.game.imageIds.menu.map]);
    level.setCurrentAnimation('image');
    game.world.addChild(level, GameData.zOrder.background);

    GameData.instances.menuUI.addElements();
  }
}
