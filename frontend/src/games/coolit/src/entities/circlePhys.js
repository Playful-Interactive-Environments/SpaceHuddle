import { Sprite } from 'melonjs';
import Matter from 'matter-js';

class CirclePHYS extends Sprite {
  matterBody;
  matterWorld;

  constructor(x, y, settings, imageID, world, bodySettings) {
    super(x, y, settings);
    this.alwaysUpdate = true;
    this.floating = true;
    this.setImage(imageID);
    this.matterBody = new Matter.Bodies.circle(
      x,
      y,
      ~~(settings.framewidth / 2),
      bodySettings
    );
    this.matterWorld = world;
    Matter.Composite.add(world, this.matterBody);
  }

  setImage(imageID) {
    this.addAnimation(imageID.toString(), [imageID]);
    this.setCurrentAnimation(imageID.toString());
  }

  setScale(scale) {
    Matter.Body.scale(this.matterBody, scale, scale);
    this.scale(scale, scale);
  }

  update(dt) {
    super.update(dt);
    this.pos.x = this.matterBody.position.x;
    this.pos.y = this.matterBody.position.y;
    return true;
  }

  onDestroyEvent() {
    super.onDestroyEvent();
    Matter.Composite.remove(this.matterWorld, this.matterBody);
  }
}

export default CirclePHYS;
