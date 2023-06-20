import { Sprite } from 'melonjs';
import Matter from 'matter-js';

/**
 * A physics-enabled object which combines the sprite visualisation from melonJS and physics capabilities from Matter.js.
 * Assumes the basic shape of a rectangle of any size and is thus fit for most assets/sprites in this game.
 *
 * NOT suitable for the Tiled Map Editor. See MatterRect if physics are needed from a level design perspective!
 *
 * If the optional parameters are omitted, the physics capabilities will have to be set up manually at a later stage
 * with the corresponding methods!
 *
 * @param x {number} The desired position on the x-Axis.
 * @param y {number} The desired position on the y-Axis.
 * @param settings {Object} The desired image-settings according to melonJS' Sprite-Setup.
 * @param imageID {number} OPTIONAL (Default: undefined). The desired sprite-ID from the spritesheet specified in the settings.
 * @param world {Matter.Composite} OPTIONAL (Default: undefined). The Matter.js physics world.
 * @param bodySettings {Object} OPTIONAL (Default: undefined). The desired physics-body settings according to MatterJS' Body-Setup.
 *
 * @extends Sprite
 * @see Sprite
 * @see MatterRect
 * @see Matter.Composite
 */
export default class RectPHYS extends Sprite {
  matterBody;
  matterWorld;
  bodySettings;
  offsetX;
  offsetY;

  constructor(
    x,
    y,
    settings,
    imageID = undefined,
    world = undefined,
    bodySettings = undefined
  ) {
    super(x, y, settings);

    this.alwaysUpdate = true;

    if (imageID !== undefined) {
      this.setImage(imageID);
    }
    if (bodySettings !== undefined) {
      this.setBodySettings(bodySettings);
    }
    if (world !== undefined) {
      this.setPhysicsWorld(world);
      this.createPhysicsBody();
    }
  }

  /**
   * Creates a physics body for this object and adds it to the physics world. If no body-settings have been supplied
   * beforehand, the body will be set up with Matter.js default settings.
   *
   * @param offsetX {number} OPTIONAL (Default: 0). An offset on the x-Axis for the synchronisation of the physics body
   * and sprite renderable.
   * @param offsetY {number} OPTIONAL (Default: 0). An offset on the y-Axis for the synchronisation of the physics body
   * and sprite renderable.
   */
  createPhysicsBody(offsetX = 0, offsetY = 0) {
    this.offsetX = offsetX;
    this.offsetY = offsetY;

    if (this.matterBody) {
      Matter.Composite.remove(this.matterWorld, this.matterBody);
    }

    if (this.bodySettings === undefined) {
      this.bodySettings = {};
    }
    this.matterBody = new Matter.Bodies.rectangle(
      this.pos.x + this.offsetX * this.width,
      this.pos.y + this.offsetY * this.height,
      this.width,
      this.height,
      this.bodySettings
    );

    Matter.Composite.add(this.matterWorld, this.matterBody);
  }

  /**
   * Sets the sprite image for this object.
   *
   * @param imageID {number} The desired sprite-ID from the spritesheet specified in the settings.
   */
  setImage(imageID) {
    this.addAnimation(imageID.toString(), [imageID]);
    this.setCurrentAnimation(imageID.toString());
  }

  /**
   * Sets the active physics world for this object.
   *
   * @param world {Matter.Composite} The Matter.js physics world.
   *
   * @see Matter.Composite
   */
  setPhysicsWorld(world) {
    this.matterWorld = world;
  }

  /**
   * Sets the physics body settings for this object.
   *
   * @param settings {Object} The desired physics-body settings according to MatterJS' Body-Setup.
   */
  setBodySettings(settings) {
    this.bodySettings = settings;
  }

  /**
   * Scale this object with the same factor on both axes.
   *
   * @param scale {number} The desired scale factor for this object.
   */
  setScaleUniform(scale) {
    Matter.Body.scale(this.matterBody, scale, scale);
    this.scale(scale, scale);
  }

  /**
   * Scale this object with individual scale factors for each axis.
   *
   * @param scaleX {number} The desired scale factor for the x-Axis.
   * @param scaleY {number} The desired scale factor for the y-Axis.
   */
  setScale(scaleX, scaleY) {
    Matter.Body.scale(this.matterBody, scaleX, scaleY);
    this.scale(scaleX, scaleY);
  }

  update(dt) {
    super.update(dt);
    Matter.Body.setPosition(
      this.matterBody,
      Matter.Vector.create(
        this.pos.x + this.offsetX * this.width,
        this.pos.y + this.offsetY * this.height
      )
    );
    return true;
  }
}
