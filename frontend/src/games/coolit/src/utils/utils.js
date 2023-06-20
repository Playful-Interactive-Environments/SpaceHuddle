import { game, Sprite, BitmapText } from 'melonjs';
import { GameData } from '@/games/coolit/src/resources.js';
import Button from '@/games/coolit/src/entities/button.js';

/**
 * Utility functions which might be needed throughout the game.
 *
 * Includes, but is not limited to:
 *    Mathematical conversions (i.e. radians to degrees or vice versa)
 *    Basic element creation (i.e. dialog backgrounds for UI)
 */
export const utils = {
  math: {
    /**
     * Converts the angle notation from degrees to radians.
     *
     * @param degrees {number} - The angle in degrees.
     *
     * @returns {number} - The corresponding radians value.
     */
    degToRad: function (degrees) {
      return degrees * (Math.PI / 180);
    },

    /**
     * Converts the angle notation from radians to degrees.
     *
     * @param radians {number} - The angle in radians.
     *
     * @returns {number} - The corresponding degree value.
     */
    radToDeg: function (radians) {
      return radians * (180 / Math.PI);
    },
  },
  ui: {
    /**
     * Creates a standard issue dialog backdrop. A fixed size box (small, medium or large) on top of a fade-out layer to set it off from the background.
     *
     * @param size {string} - The size preset of the dialog background. Accepts either 'small', 'medium' or 'large'.
     *
     * @returns {Sprite} - The created Sprite object which can be further modified or added to the game world.
     * @see Sprite
     */
    createDialogBackground: function (size) {
      const backdrop = new Sprite(
        game.viewport.width / 2,
        game.viewport.height / 2,
        GameData.game.imagePresets.uiSettings
      );
      backdrop.addAnimation('small', [
        GameData.game.imageIds.dialogboxes.small,
      ]);
      backdrop.addAnimation('medium', [
        GameData.game.imageIds.dialogboxes.medium,
      ]);
      backdrop.addAnimation('large', [
        GameData.game.imageIds.dialogboxes.large,
      ]);

      switch (size) {
        case 'small':
          backdrop.setCurrentAnimation('small');
          break;
        case 'medium':
          backdrop.setCurrentAnimation('medium');
          break;
        case 'large':
          backdrop.setCurrentAnimation('large');
          break;
        default:
          backdrop.setCurrentAnimation('small');
          break;
      }

      return backdrop;
    },

    /**
     * Creates a close button for dialog boxes. Depending on the size given, it will fit small, medium or large dialog windows.
     *
     * @param size {string} - The size preset which defines the location of this button. Accepts either 'small', 'medium' or 'large' to fit the corresponding dialog-box sizes.
     * @param callback {function} - The function to be invoked when clicking on this button.
     * @param zOrder {number} - The desired z layer for this button. Higher values are drawn on top of lower values.
     *
     * @returns {Sprite} - The created Button object which can be further modified or added to the game world.
     * @see Sprite
     */
    createDialogCloseButton: function (size, callback, zOrder) {
      const closeButton = new Button(
        925,
        235,
        zOrder,
        GameData.game.imagePresets.spritesheetSettings,
        GameData.game.imageIds.spritesheet.cross,
        callback
      ).scale(0.33);

      switch (size) {
        case 'small':
          closeButton.pos.y = 657;
          return closeButton;
        case 'medium':
          closeButton.pos.y = 446;
          return closeButton;
        case 'large':
          return closeButton;
        default:
          return closeButton;
      }
    },

    /**
     * Creates a confirmation button for dialog boxes. Depending on the size given, it will fit small, medium or large dialog windows.
     *
     * @param size {string} - The size preset which defines the location of this button. Accepts either 'small', 'medium' or 'large' to fit the corresponding dialog-box sizes.
     * @param callback {function} - The function to be invoked when clicking on this button.
     * @param zOrder {number} - The desired z layer for this button. Higher values are drawn on top of lower values.
     *
     * @returns {Sprite} - The created Button object which can be further modified or added to the game world.
     * @see Sprite
     */
    createDialogConfirmButton: function (size, callback, zOrder) {
      const confirmButton = new Button(
        325,
        1622,
        zOrder,
        GameData.game.imagePresets.spritesheetSettings,
        GameData.game.imageIds.spritesheet.confirm,
        callback
      ).scale(1.33);

      switch (size) {
        case 'small':
          confirmButton.pos.y = 1200;
          return confirmButton;
        case 'medium':
          confirmButton.pos.y = 1411;
          return confirmButton;
        case 'large':
          return confirmButton;
        default:
          return confirmButton;
      }
    },

    /**
     * Creates an abort button for dialog boxes. Depending on the size given, it will fit small, medium or large dialog windows.
     *
     * @param size {string} - The size preset which defines the location of this button. Accepts either 'small', 'medium' or 'large' to fit the corresponding dialog-box sizes.
     * @param callback {function} - The function to be invoked when clicking on this button.
     * @param zOrder {number} - The desired z layer for this button. Higher values are drawn on top of lower values.
     *
     * @returns {Sprite} - The created Button object which can be further modified or added to the game world.
     * @see Sprite
     */
    createDialogAbortButton: function (size, callback, zOrder) {
      const abortButton = new Button(
        750,
        1622,
        zOrder,
        GameData.game.imagePresets.spritesheetSettings,
        GameData.game.imageIds.spritesheet.abort,
        callback
      ).scale(1.33);

      switch (size) {
        case 'small':
          abortButton.pos.y = 1200;
          return abortButton;
        case 'medium':
          abortButton.pos.y = 1411;
          return abortButton;
        case 'large':
          return abortButton;
        default:
          return abortButton;
      }
    },

    /**
     * A text area which fits most dialog boxes.
     *
     * @param x {number} - The position on the x-axis.
     * @param y {number} - The position on the y-axis.
     * @param font {string} - OPTIONAL (Default: 'WorkSans-Regular'). The desired font. Case-sensitive. Needs to be the same ID as an imported font resource in resources.js.
     * @param text {string} - OPTIONAL (Default: ''). The text to be displayed.
     * @param size {number} - OPTIONAL (Default: 0.5). The size / scale of this text.
     * @param alignment {string} - OPTIONAL (Default: 'center'). The text alignment. Accepts 'left', 'right', 'center'.
     * @param wordWrapWidth {number} - OPTIONAL (Default: 775). How wide the text is allowed to be drawn. Creates a line-break if overflow is imminent and the word-length allows for it.
     *
     * @returns {BitmapText} - The created BitmapText object which can be further modified or added to the game world.
     * @see {BitmapText}
     */
    createTextArea: function (
      x,
      y,
      font = GameData.game.fonts.regular,
      size = 0.5,
      alignment = 'center',
      wordWrapWidth = 775,
      text = ''
    ) {
      return new BitmapText(x, y, {
        font: font,
        size: size,
        lineHeight: 1.4,
        textAlign: alignment,
        text: text,
        wordWrapWidth: wordWrapWidth,
      });
    },
  },
};
