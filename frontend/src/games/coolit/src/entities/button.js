import { UISpriteElement } from 'melonjs';

class Button extends UISpriteElement {
  // eslint-disable-next-line @typescript-eslint/no-empty-function
  callbackFunc = () => {};
  hasNewInfo;
  imageIDBasic;
  imageIDNewInfo;

  constructor(x, y, settings, imageID, callback, imageIDNewInfo = null) {
    super(x, y, settings);
    this.hasNewInfo = false;
    this.imageIDBasic = imageID;
    this.imageIDNewInfo = imageIDNewInfo;
    this.setCallback(callback);
    this.setImage(imageID);
    this.setOpacity(0.75);
  }

  // Sets the image of the button. Expects a single frame ID from a spritesheet.
  setImage(imageID) {
    this.addAnimation(imageID.toString(), [imageID]);
    this.setCurrentAnimation(imageID.toString());
  }

  // Sets the button image to an informative one.
  showExclamationMark() {
    if (this.imageIDNewInfo) {
      this.hasNewInfo = true;
      this.addAnimation(this.imageIDNewInfo.toString(), [this.imageIDNewInfo]);
      this.setCurrentAnimation(this.imageIDNewInfo.toString());
    }
  }

  // Sets the callback for the on-Click event.
  setCallback(func) {
    this.callbackFunc = func;
  }

  // Event callbacks for Hover-States and Click-Events.
  onOver(event) {
    if (this.isClickable) {
      this.setOpacity(1.0);
    }
  }

  onOut(event) {
    if (this.isClickable) {
      this.setOpacity(0.75);
    }
  }

  onClick(event) {
    if (this.hasNewInfo) {
      this.hasNewInfo = false;
      this.setImage(this.imageIDBasic);
    }
    this.callbackFunc();
    return false;
  }
}

export default Button;
