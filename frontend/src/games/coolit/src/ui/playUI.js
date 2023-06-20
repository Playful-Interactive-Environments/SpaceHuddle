import { Container, game, state, event, Sprite } from 'melonjs';
import { GameData } from '@/games/coolit/src/resources.js';
import { utils } from '@/games/coolit/src/utils/utils.js';
import Button from '@/games/coolit/src/entities/button.js';

/**
 * A class handling everything necessary to allow UI interactions in-game. Should be initialized once and saved
 * as an instance available throughout the entire game.
 *
 * @extends Container
 * @see Container
 */
export default class PlayUI extends Container {
  // Current game status.
  saveData;
  levelID;

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

  countdownInfo;
  countdownStatus;

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
  countdownElements;
  summaryButtons;
  summaryElements;
  thermElements;

  constructor() {
    super();
    this.floating = true;
    this.isPersistent = true;
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

  /**
   * Add all necessary elements to this UI container and show only the necessary ones (all others are hidden until needed).
   *
   * @param levelID {string} The level ID of the currently loaded level.
   */
  addElements(levelID) {
    this.saveData = GameData.instances.saveManager.getSaveData();
    this.levelID = levelID;

    this.finalText = null;
    this.finalTrivia = null;
    this.starRating = null;

    this.hasNotIncreased = true;
    this.hasExceededThreshold = false;
    this.hasReachedMax = false;

    this.countdownInfo = null;
    this.countdownStatus = null;

    this.heatBar = null;
    this.heatBarOpacity = 0.5;
    this.isIncreasing = false;

    this.fadeoutInt = null;
    this.heatDrainInt = null;
    this.starRatingInt = null;

    this.countdownElements = [];
    this.thermElements = [];
    this.summaryButtons = [];
    this.summaryElements = [];

    this.addCountdown();
    this.hideCountdown();
    this.addHeatBar();
    this.fadeOutHeatBar();
    this.addSummary();
    this.hideSummary();

    event.once('countdownStart', (initialTime) => {
      this.countdownStatus.setText(initialTime.toString());
      this.pulse(this.countdownStatus);
      this.showCountdown();
      event.on('countdownUpdate', (timeLeft) => {
        if (timeLeft <= 0) {
          this.countdownStatus.setText(timeLeft.toString());
          // eslint-disable-next-line @typescript-eslint/no-empty-function
          event.off('countdownUpdate', () => {});
          this.countdownInfo.setText('');
          this.countdownStatus.setText(GameData.general.locale.play.start);
          this.pulse(this.countdownStatus, () => {
            this.countdownStatus.setText('');
            this.hideCountdown();
          });
        } else {
          this.countdownStatus.setText(timeLeft.toString());
          this.pulse(this.countdownStatus);
        }
      });
    });
    event.once('start', () => this.heatManagement());
    event.once('end', () => this.clearIntervals());
  }

  /**
   * Remove all elements from this UI container and reset the containing lists.
   */
  removeElements() {
    this.countdownElements.forEach((element) => {
      this.removeChild(element);
    });
    this.countdownElements = [];

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

  /**
   * Create and add the game countdown.
   */
  addCountdown() {
    // Background Fadeout.
    this.countdownElements.push(
      this.addChild(this.createCountdownFadeout(), GameData.zOrder.ui)
    );

    // Info Text-Area.
    this.countdownInfo = this.addChild(
      utils.ui.createTextArea(
        game.viewport.width / 2,
        game.viewport.height / 4,
        GameData.game.fonts.bold,
        0.75
      ),
      GameData.zOrder.ui + 1
    );

    // Status Text-Area
    this.countdownStatus = this.addChild(
      utils.ui.createTextArea(
        game.viewport.width / 2,
        game.viewport.height / 2,
        GameData.game.fonts.bold,
        3
      ),
      GameData.zOrder.ui + 1
    );
    this.countdownStatus.textBaseline = 'middle';
  }

  /**
   * Show the game countdown.
   */
  showCountdown() {
    this.countdownInfo.setText(GameData.general.locale.play.info);
    this.countdownElements.forEach((element) => {
      element.setOpacity(1);
    });
  }

  /**
   * Hide the game countdown.
   */
  hideCountdown() {
    this.countdownElements.forEach((element) => {
      element.setOpacity(0);
    });
  }

  /**
   * Creates and adds a heat-bar background and subdivides it into regions, also adding some temperature labels to
   * those regions.
   */
  addHeatBar() {
    // BG
    const neutralBackground = new Sprite(
      0,
      0,
      GameData.game.imagePresets.spritesheetSettings
    );
    neutralBackground.anchorPoint.x = 0;
    neutralBackground.anchorPoint.y = 0.5;
    neutralBackground.addAnimation('neutral', [
      GameData.game.imageIds.spritesheet.neutral,
    ]);
    neutralBackground.setCurrentAnimation('neutral');
    neutralBackground.scale(
      GameData.general.targetResX /
        GameData.game.imagePresets.spritesheetSettings.framewidth,
      2
    );
    this.thermElements.push(
      this.addChild(neutralBackground, GameData.zOrder.ui)
    );

    const degrees = [20, 25, 30];
    // Note: Heatbar is 1024px wide. 5°C = 256px when the span is 20°C. Outer bars have been omitted.
    for (let i = 0; i <= 2; i++) {
      const posX = 256 + i * 256;
      const bar = new Sprite(
        posX + 14,
        0,
        GameData.game.imagePresets.spritesheetSettings
      );
      bar.addAnimation('image', [GameData.game.imageIds.spritesheet.neutral]);
      bar.setCurrentAnimation('image');
      bar.rotate(utils.math.degToRad(90));
      bar.scale(0.5, 0.1);
      bar.tint.parseCSS(GameData.general.colors.white);
      bar.floating = true;
      this.thermElements.push(this.addChild(bar, GameData.zOrder.ui + 2));

      const temp = utils.ui.createTextArea(
        posX + 14,
        60,
        GameData.game.fonts.bold,
        0.5,
        'center',
        775,
        degrees[i] + '°C'
      );
      temp.fillStyle.parseCSS(GameData.general.colors.white);
      this.thermElements.push(this.addChild(temp, GameData.zOrder.ui + 2));
    }

    this.addHeatBarStatus();
  }

  /**
   * Creates and adds a red heat-bar on top of the heat-bar background which indicates the current heat status of the
   * environment in-game.
   *
   * @param flashing {boolean} True if the heat-bar should flash for a short amount of time to indicate a change in value.
   */
  addHeatBarStatus(flashing = false) {
    if (this.heatBar) {
      this.removeChild(this.heatBar);
      this.thermElements = this.thermElements.filter((element) => {
        return this.heatBar !== element;
      });
      this.heatBar = null;
    }

    this.heatBar = new Sprite(
      14,
      0,
      GameData.game.imagePresets.spritesheetSettings
    );
    this.heatBar.anchorPoint.x = 0;
    this.heatBar.anchorPoint.y = 0.5;
    this.heatBar.setOpacity(this.heatBarOpacity);
    this.heatBar.addAnimation('heat', [
      GameData.game.imageIds.spritesheet.abort,
    ]);
    this.heatBar.setCurrentAnimation('heat');
    this.heatBar.scale(4 * this.currentHeatScaleFactor, 1);
    this.thermElements.push(
      this.addChild(this.heatBar, GameData.zOrder.ui + 1)
    );
    this.fadeOutHeatBar(flashing);
  }

  /**
   * Creates an interval subtracting a certain value from the heat-bar.
   */
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

  /**
   * Method to slowly fade the heat-bar to a lower opacity value.
   *
   * @param flashing {boolean} True if the heat-bar should flash for a short amount of time to indicate a change in value.
   */
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

  /**
   * Creates and adds an empty game summary preset which will be filled with content once the game has reached its end
   * either by victory or defeat.
   */
  addSummary() {
    // Backdrop + Dialog image
    this.summaryElements.push(
      this.addChild(
        utils.ui.createDialogBackground('medium'),
        GameData.zOrder.ui + 3
      )
    );

    // TextArea (victory or defeat message)
    this.finalText = utils.ui.createTextArea(
      game.viewport.width / 2,
      game.viewport.height * 0.4,
      GameData.game.fonts.bold,
      0.5,
      'center',
      600
    );
    this.summaryElements.push(
      this.addChild(this.finalText, GameData.zOrder.ui + 5)
    );

    // TextArea (random trivia)
    this.finalTrivia = utils.ui.createTextArea(
      game.viewport.width / 2,
      game.viewport.height / 2,
      GameData.game.fonts.bold,
      0.5,
      'center',
      600
    );
    this.summaryElements.push(
      this.addChild(this.finalTrivia, GameData.zOrder.ui + 5)
    );

    // Stars
    this.starRating = this.addChild(
      new Sprite(
        game.viewport.width / 2,
        600,
        GameData.game.imagePresets.spritesheetSettings
      ),
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
    this.confirmText = utils.ui.createTextArea(
      game.viewport.width / 2,
      game.viewport.height * 0.67,
      GameData.game.fonts.bold,
      1,
      'center',
      600
    );
    this.summaryElements.push(
      this.addChild(this.confirmText, GameData.zOrder.ui + 5)
    );

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

  /**
   * Once victory or defeat is achieved the summary is shown. A simple animation shows the star progress and some trivia
   * gives information about the change in climate. If the user achieved a better result than before the game saves the
   * new status to the current save data.
   *
   * @param isVictory {boolean} True if the game was a victory, False otherwise.
   */
  showSummary(isVictory) {
    this.clearIntervals();
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

  /**
   * Hides the game summary.
   */
  hideSummary() {
    this.summaryButtons.forEach((button) => {
      button.isClickable = false;
      button.setOpacity(0);
    });
    this.summaryElements.forEach((element) => {
      element.setOpacity(0);
    });
  }

  /**
   * Clears all created intervals if called. Useful to handle early user intervention (i.e. instantly going back to the
   * menu before watching the animation, etc.)
   */
  clearIntervals() {
    if (this.fadeoutInt) {
      clearInterval(this.fadeoutInt);
      this.fadeoutInt = null;
    }
    if (this.heatDrainInt) {
      clearInterval(this.heatDrainInt);
      this.heatDrainInt = null;
    }
  }

  /**
   * Utility method which allows a Renderable object to pulse once. Scales the object by 50% and gradually reverts the
   * scale back to the original. Achieved by directly modifying the transformation matrix of the Renderable.
   *
   * @param renderable {Renderable} The melonJS Renderable object to pulse.
   * @param callback {function} OPTIONAL (Default: undefined) A callback which is triggered once the pulse is complete.
   *
   * @see Renderable
   */
  // eslint-disable-next-line @typescript-eslint/no-empty-function
  pulse(renderable, callback = undefined) {
    const pulseIncrement = 1 / 60;

    renderable.currentTransform.val[0] = 1.5;
    renderable.currentTransform.val[4] = 1.5;

    const pulseInterval = setInterval(() => {
      if (renderable.currentTransform.val[0] >= 1) {
        renderable.currentTransform.val[0] -= pulseIncrement;
        renderable.currentTransform.val[4] -= pulseIncrement;
      } else {
        renderable.currentTransform.val[0] = 1;
        renderable.currentTransform.val[4] = 1;
        clearInterval(pulseInterval);
      }
    }, pulseIncrement);

    if (callback !== undefined) {
      callback();
    }
  }

  /**
   * Creates and adds a fadeout to make the countdown more visible.
   *
   * @returns {Sprite} Returns a melonJS Sprite for method chaining.
   */
  createCountdownFadeout() {
    const bgFade = new Sprite(
      game.viewport.width / 2,
      game.viewport.height / 2,
      GameData.game.imagePresets.uiSettings
    );
    bgFade.addAnimation('image', [3]);
    bgFade.setCurrentAnimation('image');

    return bgFade;
  }

  /**
   * Creates and adds a confirm-button.
   *
   * @param callback {function} The function to be called once the button is clicked.
   * @returns {Button} Returns a Button object for method chaining.
   *
   * @see {Button}
   */
  createConfirmButton(callback) {
    return this.addChild(
      new Button(
        game.viewport.width / 2,
        game.viewport.height * 0.7,
        GameData.zOrder.ui + 4,
        GameData.game.imagePresets.spritesheetSettings,
        GameData.game.imageIds.spritesheet.confirm,
        callback
      ).scale(1.5),
      GameData.zOrder.ui + 4
    );
  }

  // Clear event listeners here.
  onDestroyEvent() {
    super.onDestroyEvent();
    this.clearIntervals();
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    event.off('increaseHeat', () => {});
  }
}
