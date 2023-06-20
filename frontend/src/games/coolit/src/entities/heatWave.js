import { Sprite, game } from 'melonjs';
import { GameData } from '@/games/coolit/src/resources.js';

/**
 * Spawns a heatwave visualisation at the given location. Starts at the location given and expands from it until it is
 * not visible anymore, as it fades out the further it travels.
 *
 * @param x {number} The desired position on the x-Axis.
 * @param y {number} The desired position on the y-Axis.
 * @param tint {string} The desired tint in CSS-Format (i.e. '#ffffff').
 *
 * @extends Sprite
 * @see Sprite
 */
export default class HeatWave extends Sprite {
  incrementScale = 0.1;
  incrementOpacity = 0.0075;

  constructor(x, y, tint) {
    super(x, y, GameData.game.imagePresets.spritesheetSettings);
    this.addAnimation('image', [
      GameData.game.imageIds.spritesheet.moleculeHalo,
    ]);
    this.setCurrentAnimation('image');
    this.scale(0.2);
    this.tint.parseCSS(tint);

    this.floating = true;
    this.setOpacity(0.5);
    game.world.addChild(this, GameData.zOrder.background + 1);
  }

  update(dt) {
    super.update(dt);
    this.scale(1 + this.incrementScale);
    this.setOpacity(this.getOpacity() - this.incrementOpacity);
    if (this.getOpacity() <= 0) {
      game.world.removeChild(this);
    }
    return true;
  }
}
