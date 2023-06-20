import { Sprite } from 'melonjs';
import Matter from 'matter-js';

/**
 * Defines an object which combines the sprite-visualisations from melonJS with a circular physics-body from Matter.js.
 * Provides base functionality for image setup as well as re-scaling and synchronizing both the melonJS gameobject and
 * the Matter.js physics-body.
 *
 * Currently only allows for static images as visualization.
 *
 * @param x {number} The desired position on the x-Axis.
 * @param y {number} The desired position on the y-Axis.
 * @param settings {Object} The desired image-settings according to melonJS' Sprite-Setup.
 * @param imageID {number} The desired sprite-ID from the spritesheet specified in the settings.
 * @param world {Matter.Composite} The Matter.js physics world.
 * @param bodySettings {Object} The desired physics-body settings according to MatterJS' Body-Setup.
 *
 * @extends Sprite
 * @see Sprite
 * @see Matter.Composite
 */
class CirclePHYS extends Sprite {
  matterBody;
  matterWorld;
  imageID;

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

  /**
   * Sets the current sprite to be displayed.
   *
   * @param imageID {number} The desired sprite-ID from the spritesheet specified in the settings.
   */
  setImage(imageID) {
    this.imageID = imageID;
    this.addAnimation(imageID.toString(), [imageID]);
    this.setCurrentAnimation(imageID.toString());
  }

  /**
   * Sets the scale of this object. Assumes the shape of a circle and thus re-scales both axes with the same scale given.
   *
   * @param scale {number} The desired scale factor (1 -> keep the object as it is. 0.9 -> 10% smaller. 1.1 -> 10% bigger).
   * @param includesPhysicsBody {boolean} False if the physics body should NOT be scaled with its visual representation. True otherwise (default).
   */
  setScale(scale, includesPhysicsBody = true) {
    if (includesPhysicsBody) {
      Matter.Body.scale(this.matterBody, scale, scale);
    }
    this.scale(scale);
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
