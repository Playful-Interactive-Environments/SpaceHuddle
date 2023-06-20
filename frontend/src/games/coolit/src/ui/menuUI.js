import { Container, game, state, event, Sprite, BitmapText } from 'melonjs';
import { GameData } from '@/games/coolit/src/resources.js';
import { utils } from '@/games/coolit/src/utils/utils.js';
import de from '@/games/coolit/locales/de.json';
import en from '@/games/coolit/locales/en.json';
import Button from '@/games/coolit/src/entities/button.js';

/**
 * A class handling everything necessary to allow UI interactions in the main menu. Should be initialized once and saved
 * as an instance available throughout the entire game.
 *
 * @extends Container
 * @see Container
 */
export default class MenuUI extends Container {
  saveData;

  levelTitle;
  levelDesc;
  levelDifficulty;
  levelTemperature;
  levelType;

  moleculeImage;
  moleculeTitle;
  moleculeType;
  moleculeDesc;
  moleculePros;
  moleculeCons;
  moleculeSource;

  resetButtonText;
  quitButtonText;
  dialogBackdrop;
  dialogQuestion;
  confirmButton;
  confirmText;
  abortText;
  abortButton;

  moleculeSets;
  playButton;
  totalStarCount;
  moleculeInfo;
  newMoleculeUnlocked;

  // Arrays which hold the different UI elements for general manipulation such as activating/deactivating buttons.
  basicMenuButtons;
  basicMenuElements;
  moleculeInfoButtons;
  moleculeInfoElements;
  settingsButtons;
  settingsElements;
  levelInfoButtons;
  levelInfoElements;

  constructor() {
    super();
    this.floating = true;
    this.isPersistent = true;

    this.saveData = GameData.instances.saveManager.getSaveData();
    this.moleculeSets = {};
    this.newMoleculeUnlocked = false;

    this.basicMenuButtons = [];
    this.basicMenuElements = [];
    this.moleculeInfoButtons = [];
    this.moleculeInfoElements = [];
    this.settingsButtons = [];
    this.settingsElements = [];
    this.levelInfoButtons = [];
    this.levelInfoElements = [];

    event.on('newMolecule', () => {
      this.newMoleculeUnlocked = true;
    });
  }

  /**
   * Add all necessary elements to this UI container and show only the necessary ones (all others are hidden until needed).
   */
  addElements() {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    Object.entries(this.saveData.molecules).forEach(([key, value]) => {
      if (value.justUnlocked) {
        this.newMoleculeUnlocked = true;
      }
    });
    this.addMoleculeInfoButton(GameData.zOrder.ui);
    this.addSettingsButton(GameData.zOrder.ui);
    this.addLevelPins(GameData.zOrder.ui);
    this.addTotalStarCount(GameData.zOrder.ui);

    this.addMoleculeInfo(GameData.zOrder.ui + 5);
    this.hideMoleculeInfo(false);

    this.addSettings(GameData.zOrder.ui + 5);
    this.hideSettings(false);

    this.addLevelInfo(GameData.zOrder.ui + 5);
    this.hideLevelInfo(false);
  }

  /**
   * Remove all elements from this UI container and reset the containing lists.
   */
  removeElements() {
    this.basicMenuButtons.forEach((button) => {
      this.removeChild(button);
    });
    this.basicMenuButtons = [];

    this.moleculeInfoButtons.forEach((button) => {
      this.removeChild(button);
    });
    this.moleculeInfoButtons = [];

    this.settingsButtons.forEach((button) => {
      this.removeChild(button);
    });
    this.settingsButtons = [];

    this.basicMenuElements.forEach((element) => {
      this.removeChild(element);
    });
    this.basicMenuElements = [];

    this.moleculeInfoElements.forEach((element) => {
      this.removeChild(element);
    });
    this.moleculeInfoElements = [];

    this.levelInfoButtons.forEach((button) => {
      this.removeChild(button);
    });
    this.levelInfoButtons = [];

    this.levelInfoElements.forEach((element) => {
      if (element === this.moleculeSets) {
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        Object.entries(element).forEach(([key, value]) => {
          value.forEach((sprite) => {
            this.removeChild(sprite);
          });
        });
        this.moleculeSets = {};
      } else {
        this.removeChild(element);
      }
    });
    this.levelInfoElements = [];

    this.settingsElements.forEach((element) => {
      this.removeChild(element);
    });
    this.settingsElements = [];
  }

  /**
   * Creates and adds a basic menu button. Upon clicking on it, it shows all necessary information and some trivia about the
   * molecules which appear in-game.
   *
   * @param zOrder {number} The desired z-Order of this element.
   */
  addMoleculeInfoButton(zOrder) {
    this.moleculeInfo = new Button(
      game.viewport.width - 100,
      game.viewport.height - 100,
      zOrder,
      GameData.game.imagePresets.spritesheetSettings,
      GameData.game.imageIds.spritesheet.moleculeInfo,
      () => {
        this.showMoleculeInfo();
      }
    ).setScale(0.5);

    if (this.newMoleculeUnlocked) {
      this.moleculeInfo.isImportant = true;
    }

    this.basicMenuButtons.push(this.addChild(this.moleculeInfo, zOrder));
  }

  /**
   * Creates and adds the molecule info which should be shown upon clicking on the molecule info button. Content
   * is loaded according to the currently stored save data.
   *
   * @param zOrder {number} The desired z-Order of these elements.
   */
  addMoleculeInfo(zOrder) {
    // Backdrop + Dialog image
    this.moleculeInfoElements.push(
      this.addChild(utils.ui.createDialogBackground('large'), zOrder)
    );

    // Close-Button
    this.moleculeInfoButtons.push(
      this.addChild(
        utils.ui.createDialogCloseButton(
          'large',
          () => {
            GameData.instances.saveManager.save(this.saveData);
            this.hideMoleculeInfo();
          },
          zOrder + 1
        ),
        zOrder + 1
      )
    );

    // Image
    this.moleculeImage = new Sprite(
      540,
      400,
      GameData.game.imagePresets.spritesheetSettings
    );

    // TODO Assets/Indices to-be-replaced with actual molecule structure sprites/animations and not bubble placeholders.
    this.moleculeImage.addAnimation('empty', [
      GameData.game.imageIds.spritesheet.empty,
    ]);
    this.moleculeImage.addAnimation('molecule', [
      GameData.game.imageIds.spritesheet.molecule,
    ]);

    this.moleculeImage.setCurrentAnimation('empty');
    this.moleculeInfoElements.push(
      this.addChild(this.moleculeImage, zOrder + 1)
    );

    // TextArea (Title)
    this.moleculeTitle = this.addChild(
      utils.ui.createTextArea(540, 625, GameData.game.fonts.bold, 0.75),
      zOrder + 1
    );
    this.moleculeTitle.setText('');
    this.moleculeInfoElements.push(this.moleculeTitle);

    // TextArea (Type)
    this.moleculeType = this.addChild(
      utils.ui.createTextArea(540, 680, GameData.game.fonts.regular, 0.33),
      zOrder + 1
    );
    this.moleculeType.setText('');
    this.moleculeInfoElements.push(this.moleculeType);

    // TextArea (Description)
    this.moleculeDesc = this.addChild(
      utils.ui.createTextArea(540, 750, GameData.game.fonts.semibold, 0.45),
      zOrder + 1
    );
    this.moleculeDesc.setText('');
    this.moleculeInfoElements.push(this.moleculeDesc);

    // TextArea (Pros)
    this.moleculePros = this.addChild(
      utils.ui.createTextArea(540, 825, GameData.game.fonts.regular),
      zOrder + 1
    );
    this.moleculePros.setText('');
    this.moleculeInfoElements.push(this.moleculePros);

    // TextArea (Cons)
    this.moleculeCons = this.addChild(
      utils.ui.createTextArea(540, 1100, GameData.game.fonts.regular),
      zOrder + 1
    );
    this.moleculeCons.setText('');
    this.moleculeInfoElements.push(this.moleculeCons);

    // TextArea (Source)
    this.moleculeSource = this.addChild(
      utils.ui.createTextArea(540, 1325, GameData.game.fonts.regular, 0.25),
      zOrder + 1
    );
    this.moleculeSource.setText('');
    this.moleculeInfoElements.push(this.moleculeSource);

    let posX = game.viewport.width * 0.22;
    let posY = game.viewport.height * 0.75;
    let rowCount = 0;

    // Molecule Buttons (all available/colored molecules)
    Object.entries(this.saveData.molecules).forEach(([key, value]) => {
      if (value.unlocked) {
        const button = new Button(
          posX,
          posY,
          zOrder + 1,
          GameData.game.imagePresets.spritesheetSettings,
          GameData.game.imageIds.spritesheet.molecule,
          () => {
            if (value.justUnlocked) {
              value.justUnlocked = false;
              button.isImportant = false;
              let counter = 0;
              Object.entries(this.saveData.molecules).forEach(
                // eslint-disable-next-line @typescript-eslint/no-unused-vars
                ([key, value]) => {
                  if (value.justUnlocked === true) {
                    counter++;
                  }
                }
              );
              if (counter === 0) {
                this.moleculeInfo.isImportant = false;
              }
            }
            this.moleculeImage.setCurrentAnimation('molecule');
            this.moleculeImage.tint.parseCSS(GameData.game.molecules[key].tint);
            this.moleculeTitle.setText(
              GameData.general.locale.menu.molecule_info[key].title
            );
            this.moleculeType.setText(
              GameData.general.locale.menu.molecule_info[key].type
            );
            this.moleculeDesc.setText(
              GameData.general.locale.menu.molecule_info[key].description
            );
            this.moleculePros.setText(
              GameData.general.locale.menu.molecule_info[key].pros
            );
            this.moleculeCons.setText(
              GameData.general.locale.menu.molecule_info[key].cons
            );
            this.moleculeSource.setText(
              GameData.general.locale.menu.molecule_info[key].source
            );
          }
        ).setScale(0.5);
        button.name = key;
        button.tint.parseCSS(GameData.game.molecules[key].tint);
        this.moleculeInfoButtons.push(this.addChild(button, zOrder + 1));
        rowCount++;

        if (rowCount >= 5) {
          rowCount = 0;
          posX = game.viewport.width * 0.22;
          posY += 150;
        } else {
          posX += 150;
        }
      }
    });
    // Molecule Buttons (all non-available/silhouette molecules)
    Object.entries(this.saveData.molecules).forEach(([key, value]) => {
      if (!value.unlocked) {
        const button = new Button(
          posX,
          posY,
          zOrder + 1,
          GameData.game.imagePresets.spritesheetSettings,
          GameData.game.imageIds.spritesheet.molecule,
          () => {
            this.moleculeImage.setCurrentAnimation('molecule');
            this.moleculeImage.tint.parseCSS(GameData.game.molecules[key].tint);
            this.moleculeImage.tint.setColor(0, 0, 0);
            this.moleculeTitle.setText('');
            this.moleculeType.setText('');
            this.moleculeDesc.setText(
              GameData.general.locale.menu.molecule_info.not_discovered
            );
            this.moleculePros.setText('');
            this.moleculeCons.setText('');
            this.moleculeSource.setText('');
          }
        ).setScale(0.5);
        button.name = key;
        button.tint.setColor(0, 0, 0);
        this.moleculeInfoButtons.push(this.addChild(button, zOrder + 1));
        rowCount++;

        if (rowCount >= 5) {
          rowCount = 0;
          posX = game.viewport.width * 0.22;
          posY += 150;
        } else {
          posX += 150;
        }
      }
    });
  }

  /**
   * Shows the molecule info elements.
   */
  showMoleculeInfo() {
    // Disable other buttons in background
    this.toggleBasicMenuButtons();
    this.moleculeInfoButtons.forEach((button) => {
      button.isClickable = true;
      button.setOpacity(0.75);
      if (button.name !== '') {
        if (this.saveData.molecules[button.name].justUnlocked) {
          button.isImportant = true;
        }
      }
    });
    this.moleculeInfoElements.forEach((element) => {
      element.setOpacity(1);
      if (element === this.moleculeDesc) {
        element.setText(GameData.general.locale.menu.molecule_info.none);
      }
    });
  }

  /**
   * Hides the molecule info elements.
   *
   * @param toggleButtons {boolean} OPTIONAL (Default: true). True if the basic menu buttons (settings, molecule info, level pins) should be
   * re-enabled again, false otherwise. Assumes showMoleculeInfo() has been called before (which deactivates these buttons).
   *
   * @see showMoleculeInfo
   */
  hideMoleculeInfo(toggleButtons = true) {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    Object.entries(this.saveData.molecules).forEach(([key, value]) => {
      if (value.justUnlocked) {
        this.newMoleculeUnlocked = true;
        this.moleculeInfo.isImportant = true;
      }
    });

    // Disable other buttons in background
    if (toggleButtons) {
      this.toggleBasicMenuButtons();
    }
    this.moleculeInfoButtons.forEach((button) => {
      button.isClickable = false;
      button.isImportant = false;
      button.setOpacity(0);
    });
    this.moleculeInfoElements.forEach((element) => {
      element.setOpacity(0);
      if (element instanceof BitmapText) {
        if (element === this.moleculeDesc) {
          element.setText(GameData.general.locale.menu.molecule_info.none);
        } else {
          element.setText('');
        }
      }
    });
    this.moleculeImage.setCurrentAnimation('empty');
  }

  /**
   * Creates and adds a basic menu button. Upon clicking on it, it shows all available settings for the game
   * (i.e. a save reset, the quit-button, etc.). Buttons within this settings view usually open a dialog window to
   * confirm your choice.
   *
   * @param zOrder {number} The desired z-Order of this element.
   */
  addSettingsButton(zOrder) {
    const settingsButton = this.addChild(
      new Button(
        100,
        game.viewport.height - 100,
        zOrder,
        GameData.game.imagePresets.spritesheetSettings,
        GameData.game.imageIds.spritesheet.settings,
        () => {
          this.showSettings();
        }
      ).scale(0.5),
      zOrder
    );
    this.basicMenuButtons.push(settingsButton);
  }

  /**
   * Creates and adds the settings view with all the necessary sub-functions and available options. Currently includes:
   *
   * A save-data reset button.
   *
   * A quit button.
   *
   * A switch between DE and EN localization.
   *
   * @param zOrder {number} The desired z-Order of these elements.
   */
  addSettings(zOrder) {
    // Backdrop + Dialog image
    this.settingsElements.push(
      this.addChild(utils.ui.createDialogBackground('small'), zOrder)
    );

    // Dialog Close Button
    this.settingsButtons.push(
      this.addChild(
        utils.ui.createDialogCloseButton(
          'small',
          () => this.hideSettings(),
          zOrder + 1
        ),
        zOrder + 1
      )
    );

    // Reset save-game button and mechanic.
    this.settingsButtons.push(
      this.addChild(
        new Button(
          game.viewport.width / 2,
          game.viewport.height * 0.35,
          zOrder + 1,
          GameData.game.imagePresets.spritesheetSettings,
          GameData.game.imageIds.spritesheet.neutral,
          () => {
            this.showConfirmDialog(
              GameData.general.locale.menu.settings.reset.question,
              GameData.general.locale.menu.settings.reset.confirm,
              GameData.general.locale.menu.settings.reset.abort,
              () => {
                GameData.instances.saveManager.resetSave();
                this.saveData = GameData.instances.saveManager.getSaveData();
                this.hideConfirmDialog();
                this.removeElements();
                this.addElements();
                this.showSettings();
              }
            );
          }
        ).scale(1.5, 1),
        zOrder + 1
      )
    );

    // Text for Reset Button
    this.resetButtonText = this.addChild(
      utils.ui.createTextArea(
        game.viewport.width / 2,
        game.viewport.height * 0.35 - 40,
        GameData.game.fonts.bold,
        0.75
      ),
      zOrder + 2
    );
    this.settingsElements.push(this.resetButtonText);

    // Quit the game and return to module selection.
    // TODO: It would be wise to disable/remove this button in case the game is hosted outside of spaceHuddle (Standalone)!
    // TODO: Does not cause any errors but users might get confused if the button doesn't do anything.
    this.settingsButtons.push(
      this.addChild(
        new Button(
          game.viewport.width / 2,
          game.viewport.height * 0.5,
          zOrder + 1,
          GameData.game.imagePresets.spritesheetSettings,
          GameData.game.imageIds.spritesheet.abort,
          () => {
            this.showConfirmDialog(
              GameData.general.locale.menu.settings.quit.question,
              GameData.general.locale.menu.settings.quit.confirm,
              GameData.general.locale.menu.settings.quit.abort,
              () => {
                event.emit('closeGame');
              }
            );
          }
        ).setScale(1.5, 1),
        zOrder + 1
      )
    );

    // Text for Quit Button
    this.quitButtonText = this.addChild(
      utils.ui.createTextArea(
        game.viewport.width / 2,
        game.viewport.height * 0.5 - 40,
        GameData.game.fonts.bold,
        0.75
      ),
      zOrder + 2
    );
    this.settingsElements.push(this.quitButtonText);

    // Language of the game. Defaults to browser language. Once the user interacts with it, it is saved to ensure consistency across sessions.
    let imageID;
    if (GameData.general.locale === de) {
      imageID = GameData.game.imageIds.spritesheet.toggleRight;
    } else {
      imageID = GameData.game.imageIds.spritesheet.toggleLeft;
    }
    const localeToggle = new Button(
      game.viewport.width / 2,
      game.viewport.height / 2 + 250,
      zOrder + 1,
      GameData.game.imagePresets.spritesheetSettings,
      imageID,
      () => {
        if (GameData.general.locale === de) {
          GameData.general.locale = en;
          this.saveData.chosenLocale = en;
          localeToggle.setImage(GameData.game.imageIds.spritesheet.toggleLeft);
          this.hideSettings();
          this.showSettings();
        } else {
          GameData.general.locale = de;
          this.saveData.chosenLocale = de;
          localeToggle.setImage(GameData.game.imageIds.spritesheet.toggleRight);
          this.hideSettings();
          this.showSettings();
        }
      }
    );
    this.settingsButtons.push(this.addChild(localeToggle, zOrder + 1));

    // 'DE'-Text
    this.settingsElements.push(
      this.addChild(
        utils.ui.createTextArea(
          game.viewport.width / 2 + 200,
          game.viewport.height / 2 + 250 - 40,
          GameData.game.fonts.bold,
          1,
          'center',
          775,
          'DE'
        ),
        zOrder + 1
      )
    );

    // 'EN'-Text
    this.settingsElements.push(
      this.addChild(
        utils.ui.createTextArea(
          game.viewport.width / 2 - 200,
          game.viewport.height / 2 + 250 - 40,
          GameData.game.fonts.bold,
          1,
          'center',
          775,
          'EN'
        ),
        zOrder + 1
      )
    );
  }

  /**
   * Shows the settings view.
   */
  showSettings() {
    // Disable other buttons in background
    this.toggleBasicMenuButtons();
    this.settingsButtons.forEach((button) => {
      button.isClickable = true;
      button.setOpacity(0.75);
    });
    this.settingsElements.forEach((element) => {
      if (element === this.resetButtonText) {
        element.setText(GameData.general.locale.menu.settings.reset.button);
      }
      if (element === this.quitButtonText) {
        element.setText(GameData.general.locale.menu.settings.quit.button);
      }
      element.setOpacity(1);
    });
  }

  /**
   * Hides the settings view.
   *
   * @param toggleButtons {boolean} OPTIONAL (Default: true). True if the basic menu buttons (settings, molecule info, level pins) should be
   * re-enabled again, false otherwise. Assumes showSettings() has been called before (which deactivates these buttons).
   *
   * @see showSettings
   */
  hideSettings(toggleButtons = true) {
    // Disable other buttons in background
    if (toggleButtons) {
      this.toggleBasicMenuButtons();
    }
    this.settingsButtons.forEach((button) => {
      button.isClickable = false;
      button.setOpacity(0);
    });
    this.settingsElements.forEach((element) => {
      element.setOpacity(0);
    });
  }

  /**
   * Creates and adds basic menu buttons depending on the amount of levels available to the player. Upon clicking one of
   * them, it shows the necessary information for the level which has been selected, ranging from a verbal description to
   * more detailed statistics about the current state of the climate and occurring molecules within the level.
   *
   * @param zOrder {number} The desired z-Order of these elements.
   */
  addLevelPins(zOrder) {
    Object.entries(this.saveData.levels).forEach(([key, value]) => {
      if (value.unlocked) {
        const levelPin = this.addChild(
          new Button(
            GameData.general.targetResX *
              GameData.game.levels[key].position.factorX,
            GameData.general.targetResY *
              GameData.game.levels[key].position.factorY,
            zOrder,
            GameData.game.imagePresets.spritesheetSettings,
            value.stars + GameData.game.imageIds.spritesheet.pinNone,
            () => {
              this.showLevelInfo(key);
            }
          ).scale(0.5),
          zOrder
        );
        this.basicMenuButtons.push(levelPin);
      }
    });
  }

  /**
   * Creates and adds the level information for each available level. Content is created by taking the current save data
   * into account (i.e. current star rating, etc.).
   *
   * @param zOrder {number} The desired z-Order of these elements.
   */
  addLevelInfo(zOrder) {
    // Backdrop + Dialog image
    this.levelInfoElements.push(
      this.addChild(utils.ui.createDialogBackground('large'), zOrder)
    );

    // Close-Button
    this.levelInfoButtons.push(
      this.addChild(
        utils.ui.createDialogCloseButton(
          'large',
          () => this.hideLevelInfo(),
          zOrder + 1
        ),
        zOrder + 1
      )
    );

    // TextArea (Title)
    this.levelTitle = this.addChild(
      utils.ui.createTextArea(540, 700, GameData.game.fonts.bold, 0.75),
      zOrder + 1
    );
    this.levelInfoElements.push(this.levelTitle);

    // TextArea (Description)
    this.levelDesc = this.addChild(
      utils.ui.createTextArea(540, 775, GameData.game.fonts.regular, 0.45),
      zOrder + 1
    );
    this.levelInfoElements.push(this.levelDesc);

    // Icon (Difficulty)
    const iconDifficulty = new Sprite(
      210,
      1025,
      GameData.game.imagePresets.spritesheetSettings
    );
    iconDifficulty.addAnimation('image', [
      GameData.game.imageIds.spritesheet.iconDifficulty,
    ]);
    iconDifficulty.setCurrentAnimation('image');
    iconDifficulty.scale(0.25);
    this.levelInfoElements.push(this.addChild(iconDifficulty, zOrder + 1));

    // TextArea (Difficulty)
    this.levelDifficulty = this.addChild(
      utils.ui.createTextArea(
        265,
        1000,
        GameData.game.fonts.semibold,
        0.5,
        'left'
      ),
      zOrder + 1
    );
    this.levelInfoElements.push(this.levelDifficulty);

    // Icon (Temp)
    const iconTemp = new Sprite(
      610,
      1025,
      GameData.game.imagePresets.spritesheetSettings
    );
    iconTemp.addAnimation('image', [
      GameData.game.imageIds.spritesheet.iconTemperature,
    ]);
    iconTemp.setCurrentAnimation('image');
    iconTemp.scale(0.25);
    this.levelInfoElements.push(this.addChild(iconTemp, zOrder + 1));

    // TextArea (Average Temp.)
    this.levelTemperature = this.addChild(
      utils.ui.createTextArea(
        665,
        1000,
        GameData.game.fonts.semibold,
        0.5,
        'left'
      ),
      zOrder + 1
    );
    this.levelInfoElements.push(this.levelTemperature);

    // Icon (Type)
    const iconType = new Sprite(
      210,
      1125,
      GameData.game.imagePresets.spritesheetSettings
    );
    iconType.addAnimation('image', [
      GameData.game.imageIds.spritesheet.iconClimateType,
    ]);
    iconType.setCurrentAnimation('image');
    iconType.scale(0.25);
    this.levelInfoElements.push(this.addChild(iconType, zOrder + 1));

    // TextArea (Climate Type)
    this.levelType = this.addChild(
      utils.ui.createTextArea(
        265,
        1100,
        GameData.game.fonts.semibold,
        0.5,
        'left'
      ),
      zOrder + 1
    );
    this.levelInfoElements.push(this.levelType);

    // Icon (Molecules)
    const iconMolecule = new Sprite(
      610,
      1125,
      GameData.game.imagePresets.spritesheetSettings
    );
    iconMolecule.addAnimation('image', [
      GameData.game.imageIds.spritesheet.iconMolecules,
    ]);
    iconMolecule.setCurrentAnimation('image');
    iconMolecule.scale(0.25);
    this.levelInfoElements.push(this.addChild(iconMolecule, zOrder + 1));
    Object.entries(GameData.game.levels).forEach(([key, value]) => {
      this.createMoleculeSet(key, value.molecules, zOrder + 1);
    });
    this.levelInfoElements.push(this.moleculeSets);

    // Play-Button
    this.playButton = this.addChild(
      new Button(
        540,
        1600,
        zOrder + 1,
        GameData.game.imagePresets.spritesheetSettings,
        GameData.game.imageIds.spritesheet.confirm,
        // eslint-disable-next-line @typescript-eslint/no-empty-function
        () => {}
      ).scale(1.33),
      zOrder + 1
    );
    this.levelInfoButtons.push(this.playButton);

    // Start-Text
    const textStart = this.addChild(
      utils.ui.createTextArea(540, 1550, GameData.game.fonts.semibold, 1),
      zOrder + 2
    );
    textStart.setText(GameData.general.locale.menu.level_info.start);
    this.levelInfoElements.push(textStart);
  }

  /**
   * Creates and adds the basic menu element showing the current total star count achieved. This element is non-interactable
   * and only informative.
   *
   * @param zOrder {number} The desired z-Order of this element.
   */
  addTotalStarCount(zOrder) {
    const starCountBG = new Sprite(
      game.viewport.width - 128 - 50,
      100,
      GameData.game.imagePresets.spritesheetSettings
    );
    starCountBG.addAnimation('image', [
      GameData.game.imageIds.spritesheet.neutral,
    ]);
    starCountBG.setCurrentAnimation('image');
    starCountBG.scale(0.75);
    this.basicMenuElements.push(this.addChild(starCountBG, zOrder));

    const star = new Sprite(
      game.viewport.width - 128 - 100,
      95,
      GameData.game.imagePresets.spritesheetSettings
    );
    star.addAnimation('image', [GameData.game.imageIds.spritesheet.singleStar]);
    star.setCurrentAnimation('image');
    star.scale(0.2);
    this.basicMenuElements.push(this.addChild(star, zOrder + 1));

    this.totalStarCount = this.addChild(
      utils.ui.createTextArea(
        game.viewport.width - 150,
        75,
        GameData.game.fonts.bold,
        0.5,
        'center',
        775,
        this.saveData.totalStars.toString() +
          ' / ' +
          (Object.entries(this.saveData.levels).length * 3).toString()
      ),
      zOrder + 1
    );
    this.basicMenuElements.push(this.totalStarCount);
  }

  /**
   * Shows level information depending on the level selected.
   *
   * @param levelID {string} The level ID to allow showing the correct information about the selected level.
   */
  showLevelInfo(levelID) {
    // Disable other buttons in background
    this.toggleBasicMenuButtons();
    this.levelInfoButtons.forEach((button) => {
      button.isClickable = true;
      button.setOpacity(0.75);
      if (button === this.playButton) {
        button.setCallback(() => {
          this.removeElements();
          state.change(state.PLAY, false, levelID);
        });
      }
    });
    this.levelInfoElements.forEach((element) => {
      switch (element) {
        case this.levelTitle:
          element.setText(
            GameData.general.locale.menu.level_info[levelID].title
          );
          break;
        case this.levelDesc:
          element.setText(
            GameData.general.locale.menu.level_info[levelID].description
          );
          break;
        case this.levelDifficulty:
          element.setText(
            GameData.general.locale.menu.level_info[levelID].difficulty
          );
          break;
        case this.levelTemperature:
          element.setText(
            GameData.general.locale.menu.level_info[levelID].averageTemperature
          );
          break;
        case this.levelType:
          element.setText(
            GameData.general.locale.menu.level_info[levelID].climateType
          );
          break;
        case this.moleculeSets:
          element[levelID].forEach((sprite) => {
            sprite.setOpacity(1);
          });
          break;
        default:
          element.setOpacity(1);
          break;
      }
    });
  }

  /**
   * Hides the level information.
   *
   * @param toggleButtons {boolean} OPTIONAL (Default: true). True if the basic menu buttons (settings, molecule info, level pins) should be
   * re-enabled again, false otherwise. Assumes showLevelInfo() has been called before (which deactivates these buttons).
   *
   * @see showLevelInfo
   */
  hideLevelInfo(toggleButtons = true) {
    // Disable other buttons in background
    if (toggleButtons) {
      this.toggleBasicMenuButtons();
    }
    this.levelInfoButtons.forEach((button) => {
      button.isClickable = false;
      button.setOpacity(0);
    });
    this.levelInfoElements.forEach((element) => {
      switch (element) {
        case this.levelTitle:
          element.setText('');
          break;
        case this.levelDesc:
          element.setText('');
          break;
        case this.levelDifficulty:
          element.setText('');
          break;
        case this.levelTemperature:
          element.setText('');
          break;
        case this.levelType:
          element.setText('');
          break;
        case this.moleculeSets:
          // eslint-disable-next-line @typescript-eslint/no-unused-vars
          Object.entries(this.moleculeSets).forEach(([key, value]) => {
            value.forEach((sprite) => {
              sprite.setOpacity(0);
            });
          });
          break;
        default:
          element.setOpacity(0);
          break;
      }
    });
  }

  /**
   * Toggles the basic menu buttons to a deactivated or activated state to avoid accidentally clicking them once a
   * sub-menu has opened.
   */
  toggleBasicMenuButtons() {
    this.basicMenuButtons.forEach((button) => {
      button.isClickable = !button.isClickable;
    });
  }

  /**
   * Creates, adds and shows a confirmation dialog with a confirm- and abort-button. Use this dialog to double-check if a user really
   * wants to perform a certain action, i.e. quit the game or reset the save data.
   *
   * @param question {string} The question the dialog should ask.
   * @param confirm {string} The text on the confirm button.
   * @param abort {string} The text on the abort button.
   * @param action {function} The action to be taken once the confirm button has been hit.
   */
  showConfirmDialog(question, confirm, abort, action) {
    this.dialogBackdrop = this.addChild(
      utils.ui.createDialogBackground('small'),
      GameData.zOrder.ui + 22
    );
    this.dialogQuestion = this.addChild(
      utils.ui.createTextArea(
        game.viewport.width / 2,
        game.viewport.height * 0.45,
        GameData.game.fonts.semibold,
        0.5,
        'center',
        775,
        question
      ),
      GameData.zOrder.ui + 23
    );
    this.confirmButton = this.addChild(
      utils.ui.createDialogConfirmButton(
        'small',
        action,
        GameData.zOrder.ui + 23
      ),
      GameData.zOrder.ui + 23
    );
    this.confirmText = this.addChild(
      utils.ui.createTextArea(
        this.confirmButton.pos.x,
        this.confirmButton.pos.y - 40,
        GameData.game.fonts.bold,
        0.5,
        'center',
        775,
        confirm
      ),
      GameData.zOrder.ui + 24
    );
    this.abortButton = this.addChild(
      utils.ui.createDialogAbortButton(
        'small',
        () => {
          this.hideConfirmDialog();
        },
        GameData.zOrder.ui + 23
      ),
      GameData.zOrder.ui + 23
    );
    this.abortText = this.addChild(
      utils.ui.createTextArea(
        this.abortButton.pos.x,
        this.abortButton.pos.y - 40,
        GameData.game.fonts.bold,
        0.5,
        'center',
        775,
        abort
      ),
      GameData.zOrder.ui + 24
    );
  }

  /**
   * Hides the confirm dialog.
   */
  hideConfirmDialog() {
    this.removeChild(this.dialogBackdrop);
    this.dialogBackdrop = null;
    this.removeChild(this.dialogQuestion);
    this.dialogQuestion = null;
    this.removeChild(this.confirmButton);
    this.confirmButton = null;
    this.removeChild(this.confirmText);
    this.confirmText = null;
    this.removeChild(this.abortText);
    this.abortText = null;
    this.removeChild(this.abortButton);
    this.abortButton = null;
  }

  /**
   * Creates a molecule set depending on the defined (level-specific) molecules in resources.js.
   * A molecule set is a group of molecule-types which can appear in a level.
   * This method creates sprites in such a way that the individual molecules of the given level appear from left to right, one after another.
   *
   * @param levelID {string} The molecule set from this level. Needs to be a level name as defined in either the save-manager or resources.js.
   * @param moleculeIDs {string[]} An array holing all possible molecule types which can apper in the given level.
   * @param zOrder {number} The desired z-Order of the created objects.
   */
  createMoleculeSet(levelID, moleculeIDs, zOrder) {
    this.moleculeSets[levelID] = [];

    const increment = 65;
    let moleculeCount = 0;

    moleculeIDs.forEach((moleculeID) => {
      const sprite = new Sprite(
        690 + increment * moleculeCount,
        1125,
        GameData.game.imagePresets.spritesheetSettings
      );
      sprite.addAnimation('image', [
        GameData.game.imageIds.spritesheet.molecule,
      ]);
      sprite.setCurrentAnimation('image');
      sprite.tint.parseCSS(GameData.game.molecules[moleculeID].tint);
      sprite.scale(0.2);
      this.moleculeSets[levelID].push(this.addChild(sprite, zOrder + 1));
      moleculeCount++;
    });
  }

  onDestroyEvent() {
    super.onDestroyEvent();
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    event.off('newMolecule', () => {});
  }
}
