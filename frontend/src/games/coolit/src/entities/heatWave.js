import { Sprite, game } from 'melonjs';
import { GameData } from '@/games/coolit/src/resources.js';

export default class HeatWave extends Sprite {
  incrementScale = 0.1;
  incrementOpacity = 0.0075;

  constructor(x, y) {
    super(x, y, {
      image: 'spritesheet',
      framewidth: 256,
      frameheight: 256,
    });
    this.addAnimation('image', [GameData.game.imageIds.spritesheet.heatwave]);
    this.setCurrentAnimation('image');
    this.scale(0.2);
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
