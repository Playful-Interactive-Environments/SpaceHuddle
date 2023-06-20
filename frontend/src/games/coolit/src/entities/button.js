import { game, Sprite, UISpriteElement } from 'melonjs';
import { GameData } from '@/games/coolit/src/resources';

/**
 * Allows the creation of a simple button element which can hold any sprite of your choice. This button can be
 * used with melonJS as it extends upon melonJS' UISpriteElement.
 *
 * @param x {number} - Position on the x-axis.
 * @param y {number} - Position on the y-axis.
 * @param z {number} - The desired z-Order of this object.
 * @param settings {Object} - Sprite-Settings according to melonJS.
 * @param imageID {number} - The numerical ID of the image on a spritesheet. Spritesheet IDs are usually read 'left to right, top to bottom' and start at 0.
 * @param callback {function} - The callback function to be invoked when the button is clicked.
 * @param isImportant {boolean} - OPTIONAL (Default: false). A flag which allows to set this button to important upon creation. If true, it shows the 'important' overlay on the button.
 *
 * @extends UISpriteElement
 * @see UISpriteElement
 */
class Button extends UISpriteElement {
  // eslint-disable-next-line @typescript-eslint/no-empty-function
  callbackFunc = () => {};
  imageID = GameData.game.imageIds.spritesheet.debug;
  exclamationSpriteRef = undefined;
  isImportant = false;

  constructor(x, y, z, settings, imageID, callback, isImportant = false) {
    super(x, y, settings);

    this.imageID = imageID;
    this.isImportant = isImportant;

    this.exclamationSpriteRef = new Sprite(x, y, settings);
    this.exclamationSpriteRef.addAnimation('image', [
      GameData.game.imageIds.spritesheet.important,
    ]);
    this.exclamationSpriteRef.setCurrentAnimation('image');
    this.exclamationSpriteRef.setOpacity(0);
    game.world.addChild(this.exclamationSpriteRef, z + 1);

    this.setCallback(callback);
    this.setImage(imageID);
    this.setOpacity(0.75);
  }

  /**
   * Sets the image of this Button. Currently only accepts a single sprite ID.
   *
   * @param imageID {number} - The numerical ID of the image on a spritesheet. Spritesheet IDs are usually read 'left to right, top to bottom' and start at 0.
   */
  // Sets the image of the button. Expects a single frame ID from a spritesheet.
  // TODO: Expand to allow animations/multiple IDs?
  setImage(imageID) {
    this.addAnimation(imageID.toString(), [imageID]);
    this.setCurrentAnimation(imageID.toString());
  }

  /**
   * Sets the on-click callback of this Button.
   *
   * @param func {function} - The function to invoke once clicked.
   */
  setCallback(func) {
    this.callbackFunc = func;
  }

  /**
   * Set the scale of the underlying sprite AND the exclamation overlay.
   *
   * @param x {number} - The desired scaling factor for the x-axis.
   * @param y {number | undefined} - The desired scaling factor for the y-axis. If omitted, y is equal to x.
   * @returns {Button} - A reference to this button for method chaining.
   */
  setScale(x, y = x) {
    this.scale(x, y);
    this.exclamationSpriteRef.scale(x, y);
    return this;
  }

  // Event callbacks for Hover-States and Click-Events.
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  onOver(event) {
    if (this.isClickable) {
      this.setOpacity(1.0);
    }
  }
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  onOut(event) {
    if (this.isClickable) {
      this.setOpacity(0.75);
    }
  }
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  onClick(event) {
    this.callbackFunc();
    return false;
  }

  // Game-Loop callbacks.
  update(dt) {
    super.update(dt);
    if (this.isImportant) {
      this.exclamationSpriteRef.setOpacity(1);
    } else {
      this.exclamationSpriteRef.setOpacity(0);
    }

    return true;
  }
}

export default Button;
