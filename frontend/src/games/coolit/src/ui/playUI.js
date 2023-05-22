import {
  Container,
  game,
  state,
  event,
  Sprite,
  BitmapText,
  Vector2d,
} from 'melonjs';
import { GameData } from '@/games/coolit/src/resources.js';
import Button from '@/games/coolit/src/entities/button.js';

export default class PlayUI extends Container {
  // Current game status.
  saveData;
  levelID;

  // Base image settings.
  imageSettings;
  imageSettingsAPLeftMid;

  // UI Content
  finalText;
  finalTrivia;
  starRating;
  confirmText;

  // Victory/Defeat Condition checks.
  baseHeatIncrement;
  hasNotIncreased;
  hasExceededThreshold;
  hasReachedMax;

  // Temperature Gauge / Heat Bar stat trackers.
  heatBar;
  heatBarOpacity;
  isIncreasing;
  currentHeatScaleFactor;
  minScale;

  // Attributes which hold intervals.
  fadeoutInt;
  heatDrainInt;
  starRatingInt;

  // Arrays which hold the different UI elements for general manipulation such as activating/deactivating buttons.
  summaryButtons;
  summaryElements;
  thermElements;

  constructor() {
    super();
    this.floating = true;
    this.isPersistent = true;

    // Initialize attributes which are always the same, even across levels.
    this.imageSettings = {
      image: 'spritesheet',
      framewidth: 256,
      frameheight: 256,
    };
    this.imageSettingsAPLeftMid = {
      image: 'spritesheet',
      framewidth: 256,
      frameheight: 256,
      anchorPoint: new Vector2d(0, 0.5),
    };
    this.baseHeatIncrement = 1 / 5;

    // Set up event listeners.
    event.on('increaseHeat', () => {
      this.hasNotIncreased = false;

      if (!this.hasExceededThreshold) {
        if (
          this.currentHeatScaleFactor + this.baseHeatIncrement >
          GameData.game.difficulty[
            GameData.game.levels[this.levelID].difficulty
          ].twoStarThreshold
        ) {
          this.hasExceededThreshold = true;
        }
      }

      if (this.currentHeatScaleFactor + this.baseHeatIncrement >= 1) {
        this.currentHeatScaleFactor = 1;
        this.addHeatBarStatus(true);
        this.hasReachedMax = true;
        event.emit('checkVictoryCondition');
      } else {
        this.isIncreasing = true;
        this.currentHeatScaleFactor += this.baseHeatIncrement;
        this.addHeatBarStatus(true);
        event.emit('checkVictoryCondition');
      }
    });
  }

  // Adds all UI elements of this container.
  addElements(levelID) {
    this.saveData = GameData.instances.saveManager.getSaveData();
    this.levelID = levelID;

    this.finalText = null;
    this.finalTrivia = null;
    this.starRating = null;

    this.hasNotIncreased = true;
    this.hasExceededThreshold = false;
    this.hasReachedMax = false;

    this.heatBar = null;
    this.heatBarOpacity = 0.5;
    this.isIncreasing = false;

    this.fadeoutInt = null;
    this.heatDrainInt = null;
    this.starRatingInt = null;

    this.thermElements = [];
    this.summaryButtons = [];
    this.summaryElements = [];

    this.addHeatBar();
    this.fadeOutHeatBar();
    this.addSummary();
    this.hideSummary();

    event.once('start', () => this.heatManagement());
    event.once('end', () => {
      if (this.fadeoutInt) {
        clearInterval(this.fadeoutInt);
        this.fadeoutInt = null;
      }
      if (this.heatDrainInt) {
        clearInterval(this.heatDrainInt);
        this.heatDrainInt = null;
      }
    });
  }

  // Removes all UI Elements of this container.
  removeElements() {
    this.thermElements.forEach((element) => {
      this.removeChild(element);
    });
    this.thermElements = [];

    this.summaryButtons.forEach((button) => {
      this.removeChild(button);
    });
    this.summaryButtons = [];

    this.summaryElements.forEach((element) => {
      this.removeChild(element);
    });
    this.summaryElements = [];
  }

  // Heat Bar + (Temporary) Status
  addHeatBar() {
    // BG
    const neutralBackground = new Sprite(0, 0, this.imageSettingsAPLeftMid);
    neutralBackground.addAnimation('neutral', [
      GameData.game.imageIds.spritesheet.neutral,
    ]);
    neutralBackground.setCurrentAnimation('neutral');
    neutralBackground.scale(
      GameData.general.targetResX / this.imageSettingsAPLeftMid.framewidth,
      1
    );
    this.thermElements.push(
      this.addChild(neutralBackground, GameData.zOrder.ui)
    );

    this.addHeatBarStatus();
  }
  addHeatBarStatus(flashing = false) {
    if (this.heatBar) {
      this.removeChild(this.heatBar);
      this.thermElements = this.thermElements.filter((element) => {
        return this.heatBar !== element;
      });
      this.heatBar = null;
    }

    this.heatBar = new Sprite(14, 0, this.imageSettingsAPLeftMid);
    this.heatBar.setOpacity(this.heatBarOpacity);
    this.heatBar.addAnimation('heat', [
      GameData.game.imageIds.spritesheet.abort,
    ]);
    this.heatBar.setCurrentAnimation('heat');
    this.heatBar.scale(4 * this.currentHeatScaleFactor, 0.75);
    this.thermElements.push(
      this.addChild(this.heatBar, GameData.zOrder.ui + 1)
    );
    this.fadeOutHeatBar(flashing);
  }
  heatManagement() {
    this.heatDrainInt = setInterval(() => {
      if (!this.isIncreasing) {
        if (this.currentHeatScaleFactor - 0.01 <= this.minScale) {
          this.currentHeatScaleFactor = this.minScale;
        } else {
          this.currentHeatScaleFactor -= 0.01;
        }

        this.addHeatBarStatus();
      }
    }, 1000);
  }
  fadeOutHeatBar(flashing = false) {
    if (this.fadeoutInt) {
      clearInterval(this.fadeoutInt);
      this.fadeoutInt = null;
    }

    if (this.heatBar) {
      if (flashing) {
        this.heatBarOpacity = 1;
      }

      this.fadeoutInt = setInterval(() => {
        this.heatBarOpacity -= 0.1;

        this.heatBar.setOpacity(this.heatBarOpacity);

        if (this.heatBarOpacity <= 0.5) {
          clearInterval(this.fadeoutInt);
          this.heatBarOpacity = 0.5;
          this.fadeoutInt = null;
          this.isIncreasing = false;
        }
      }, 100);
    }
  }

  // Game summary and callbacks. Content depends on if the game was a victory or defeat.
  addSummary() {
    // Backdrop + Dialog image
    this.summaryElements.push(this.createDialogBackdrop());

    // TextArea (victory or defeat message)
    this.finalText = this.createTextArea(
      game.viewport.width / 2,
      game.viewport.height * 0.4
    );
    this.summaryElements.push(this.finalText);

    // TextArea (random trivia)
    this.finalTrivia = this.createTextArea(
      game.viewport.width / 2,
      game.viewport.height / 2
    );
    this.summaryElements.push(this.finalTrivia);

    // Stars
    this.starRating = this.addChild(
      new Sprite(game.viewport.width / 2, 600, this.imageSettings),
      GameData.zOrder.ui + 4
    ).scale(2);
    this.starRating.addAnimation('noneofthree', [
      GameData.game.imageIds.spritesheet.none,
    ]);
    this.starRating.addAnimation('oneofthree', [
      GameData.game.imageIds.spritesheet.one,
    ]);
    this.starRating.addAnimation('twoofthree', [
      GameData.game.imageIds.spritesheet.two,
    ]);
    this.starRating.addAnimation('threeofthree', [
      GameData.game.imageIds.spritesheet.three,
    ]);
    this.starRating.setCurrentAnimation('noneofthree');
    this.summaryElements.push(this.starRating);

    // TextArea (Confirm)
    this.confirmText = this.createTextArea(
      game.viewport.width / 2,
      game.viewport.height * 0.67,
      1
    );
    this.summaryElements.push(this.confirmText);

    // Confirm-Button
    this.summaryButtons.push(
      this.createConfirmButton(() => {
        if (this.starRatingInt) {
          clearInterval(this.starRatingInt);
          this.starRatingInt = null;
        }
        this.removeElements();
        state.change(state.MENU, false);
      })
    );
  }
  showSummary(isVictory) {
    this.summaryButtons.forEach((button) => {
      button.isClickable = true;
      button.setOpacity(0.75);
    });
    this.summaryElements.forEach((element) => {
      element.setOpacity(1);
    });
    this.confirmText.setText(GameData.general.locale.play.confirm);

    if (isVictory) {
      // Conditions for 1, 2 or 3 Stars. Conditions may be made dependent on difficulty or similar.
      // 1 Star: Reached upon Victory
      // 2 Star: If the level was completed with the heat not exceeding the threshold.
      // 3 Star: If the level was completed without causing any heat increase.
      let starsReached = 1;
      if (!this.hasExceededThreshold) {
        starsReached += 1;
      }
      if (this.hasNotIncreased) {
        starsReached += 1;
      }

      // Save the reached status, if better, regardless if the user wants to re-try or not.
      if (starsReached > this.saveData.levels[this.levelID].stars) {
        // Total Stars
        this.saveData.totalStars +=
          starsReached - this.saveData.levels[this.levelID].stars;

        // New levels unlocked?
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        Object.entries(GameData.game.levels).forEach(([key, value]) => {
          if (this.saveData.totalStars >= value.requirement) {
            this.saveData.levels[key].unlocked = true;
          }
        });

        // New molecules unlocked?
        GameData.game.levels[this.levelID].molecules.forEach((moleculeID) => {
          if (!this.saveData.molecules[moleculeID].unlocked) {
            this.saveData.molecules[moleculeID].unlocked = true;
            this.saveData.molecules[moleculeID].justUnlocked = true;
          }
        });

        // Write the new star amount and save changes.
        this.saveData.levels[this.levelID].stars = starsReached;
        GameData.instances.saveManager.save(this.saveData);
      }

      // "Animate" star addition.
      let currentStar = 0;
      this.starRatingInt = setInterval(() => {
        switch (currentStar) {
          case 0:
            this.starRating.setCurrentAnimation('noneofthree');
            break;
          case 1:
            this.starRating.setCurrentAnimation('oneofthree');
            break;
          case 2:
            this.starRating.setCurrentAnimation('twoofthree');
            break;
          case 3:
            this.starRating.setCurrentAnimation('threeofthree');
            break;
          default:
            this.starRating.setCurrentAnimation('noneofthree');
            break;
        }

        if (currentStar < starsReached) {
          currentStar += 1;
        } else {
          clearInterval(this.starRatingInt);
        }
      }, 500);

      // Set the text of the dialog box.
      switch (starsReached) {
        case 1:
          this.finalText.setText(GameData.general.locale.play.victory.oneStar);
          this.finalTrivia.setText(
            GameData.general.locale.play.victory.randomTriviaLight[
              ~~(
                Math.random() *
                Object.entries(
                  GameData.general.locale.play.victory.randomTriviaLight
                ).length
              ) + 1
            ]
          );
          break;
        case 2:
          this.finalText.setText(GameData.general.locale.play.victory.twoStar);
          this.finalTrivia.setText(
            GameData.general.locale.play.victory.randomTriviaMedium[
              ~~(
                Math.random() *
                Object.entries(
                  GameData.general.locale.play.victory.randomTriviaMedium
                ).length
              ) + 1
            ]
          );
          break;
        case 3:
          this.finalText.setText(
            GameData.general.locale.play.victory.threeStar
          );
          this.finalTrivia.setText(
            GameData.general.locale.play.victory.randomTriviaHeavy[
              ~~(
                Math.random() *
                Object.entries(
                  GameData.general.locale.play.victory.randomTriviaHeavy
                ).length
              ) + 1
            ]
          );
          break;
      }
    } else {
      // Set the text of the dialog box.
      this.finalText.setText(GameData.general.locale.play.defeat.noStar);
      this.finalTrivia.setText(
        GameData.general.locale.play.defeat.randomTriviaBad[
          ~~(
            Math.random() *
            Object.entries(GameData.general.locale.play.defeat.randomTriviaBad)
              .length
          ) + 1
        ]
      );
    }
  }
  hideSummary() {
    this.summaryButtons.forEach((button) => {
      button.isClickable = false;
      button.setOpacity(0);
    });
    this.summaryElements.forEach((element) => {
      element.setOpacity(0);
    });
  }

  // A function which creates the dialog window including a fadeout.
  createDialogBackdrop() {
    const backdrop = new Sprite(
      game.viewport.width / 2,
      game.viewport.height / 2,
      {
        image: 'dialogboxes',
        framewidth: 1080,
        frameheight: 1920,
      }
    );
    backdrop.addAnimation('image', [GameData.game.imageIds.dialogboxes.medium]);
    backdrop.setCurrentAnimation('image');

    return this.addChild(backdrop, GameData.zOrder.ui + 3);
  }
  // A function which creates a 'Confirm' button for dialog windows.
  createConfirmButton(callback) {
    return this.addChild(
      new Button(
        game.viewport.width / 2,
        game.viewport.height * 0.7,
        this.imageSettings,
        GameData.game.imageIds.spritesheet.confirm,
        callback
      ).scale(1.5),
      GameData.zOrder.ui + 4
    );
  }
  // A function which creates a textarea. Users can not interact with it.
  createTextArea(x, y, size = 0.5, text = '') {
    return this.addChild(
      new BitmapText(x, y, {
        font: 'WorkSans-Bold',
        size: size,
        lineHeight: 1.4,
        textAlign: 'center',
        text: text,
        wordWrapWidth: 600,
      }),
      GameData.zOrder.ui + 5
    );
  }

  // Clear event listeners here.
  onDestroyEvent() {
    super.onDestroyEvent();
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    event.off('increaseHeat', () => {});
  }
}
