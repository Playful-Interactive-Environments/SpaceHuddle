import { Container, game, state, event, Sprite, BitmapText } from 'melonjs';
import { GameData } from '@/games/coolit/src/resources.js';
import de from '@/games/coolit/locales/de.json';
import en from '@/games/coolit/locales/en.json';
import Button from '@/games/coolit/src/entities/button.js';

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
  imageSettings;
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
    this.imageSettings = {
      image: 'spritesheet',
      framewidth: 256,
      frameheight: 256,
    };
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

  // Adds all UI Elements of this container.
  addElements() {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    Object.entries(this.saveData.molecules).forEach(([key, value]) => {
      if (value.justUnlocked) {
        this.newMoleculeUnlocked = true;
      }
    });
    this.addMoleculeInfoButton();
    this.addSettingsButton();
    this.addLevelPins();
    this.addTotalStarCount();

    this.addMoleculeInfo();
    this.hideMoleculeInfo(false);

    this.addSettings();
    this.hideSettings(false);

    this.addLevelInfo();
    this.hideLevelInfo(false);
  }

  // Removes all UI Elements of this container.
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

  // Molecule Info Button + Callbacks.
  addMoleculeInfoButton() {
    this.moleculeInfo = new Button(
      game.viewport.width - 100,
      game.viewport.height - 100,
      this.imageSettings,
      GameData.game.imageIds.spritesheet.moleculeinfo,
      () => {
        if (this.newMoleculeUnlocked) {
          this.newMoleculeUnlocked = false;
        }
        this.showMoleculeInfo();
      },
      GameData.game.imageIds.spritesheet.moleculeInfoExcl
    ).scale(0.5);
    if (this.newMoleculeUnlocked) {
      this.moleculeInfo.showExclamationMark();
    }
    this.basicMenuButtons.push(
      this.addChild(this.moleculeInfo, GameData.zOrder.ui)
    );
  }
  addMoleculeInfo() {
    // Backdrop + Dialog image
    this.moleculeInfoElements.push(
      this.createDialogBackdrop('large', GameData.zOrder.ui + 1)
    );

    // Close-Button
    this.moleculeInfoButtons.push(
      this.createDialogCloseButton(
        'large',
        () => {
          GameData.instances.saveManager.save(this.saveData);
          this.hideMoleculeInfo();
        },
        GameData.zOrder.ui + 2
      )
    );

    // Image
    this.moleculeImage = new Sprite(540, 400, this.imageSettings);

    // TODO Assets/Indices to-be-replaced with actual molecule structure sprites/animations and not bubble placeholders.
    this.moleculeImage.addAnimation('empty', [
      GameData.game.imageIds.spritesheet.empty,
    ]);
    this.moleculeImage.addAnimation('watervapor', [
      GameData.game.imageIds.spritesheet.watervapor,
    ]);
    this.moleculeImage.addAnimation('carbondioxide', [
      GameData.game.imageIds.spritesheet.carbondioxide,
    ]);
    this.moleculeImage.addAnimation('methane', [
      GameData.game.imageIds.spritesheet.methane,
    ]);
    this.moleculeImage.addAnimation('nitrousoxide', [
      GameData.game.imageIds.spritesheet.nitrousoxide,
    ]);
    this.moleculeImage.addAnimation('ozone', [
      GameData.game.imageIds.spritesheet.ozone,
    ]);
    this.moleculeImage.addAnimation('fluorinatedgases', [
      GameData.game.imageIds.spritesheet.fluorinatedgases,
    ]);
    this.moleculeImage.addAnimation('oxygen', [
      GameData.game.imageIds.spritesheet.other,
    ]);
    this.moleculeImage.addAnimation('nitrogen', [
      GameData.game.imageIds.spritesheet.other,
    ]);
    this.moleculeImage.addAnimation('argon', [
      GameData.game.imageIds.spritesheet.other,
    ]);
    this.moleculeImage.addAnimation('helium', [
      GameData.game.imageIds.spritesheet.other,
    ]);

    this.moleculeImage.setCurrentAnimation('empty');
    this.moleculeInfoElements.push(
      this.addChild(this.moleculeImage, GameData.zOrder.ui + 2)
    );

    // TextArea (Title)
    this.moleculeTitle = this.createTextAreaBold(
      540,
      625,
      GameData.zOrder.ui + 2,
      0.75
    );
    this.moleculeTitle.setText('');
    this.moleculeInfoElements.push(this.moleculeTitle);

    // TextArea (Type)
    this.moleculeType = this.createTextAreaRegular(
      540,
      680,
      GameData.zOrder.ui + 2,
      0.33
    );
    this.moleculeType.setText('');
    this.moleculeInfoElements.push(this.moleculeType);

    // TextArea (Description)
    this.moleculeDesc = this.createTextAreaSemiBold(
      540,
      750,
      GameData.zOrder.ui + 2,
      0.45
    );
    this.moleculeDesc.setText('');
    this.moleculeInfoElements.push(this.moleculeDesc);

    // TextArea (Pros)
    this.moleculePros = this.createTextAreaRegular(
      540,
      825,
      GameData.zOrder.ui + 2
    );
    this.moleculePros.setText('');
    this.moleculeInfoElements.push(this.moleculePros);

    // TextArea (Cons)
    this.moleculeCons = this.createTextAreaRegular(
      540,
      1100,
      GameData.zOrder.ui + 2
    );
    this.moleculeCons.setText('');
    this.moleculeInfoElements.push(this.moleculeCons);

    // TextArea (Source)
    this.moleculeSource = this.createTextAreaRegular(
      540,
      1325,
      GameData.zOrder.ui + 2,
      0.25
    );
    this.moleculeSource.setText('');
    this.moleculeInfoElements.push(this.moleculeSource);

    // Molecule Buttons (all available/colored and non-available/silhouette molecules)
    let posX = game.viewport.width * 0.22;
    let posY = game.viewport.height * 0.75;
    let rowCount = 0;
    Object.entries(this.saveData.molecules).forEach(([key, value]) => {
      // TODO: Once every molecule has its own sprite this won't be needed anymore.
      // TODO: If molecules are one sprite with different tints then it could be expanded to a switch statement.
      let imageID = key;
      if (
        key === 'oxygen' ||
        key === 'nitrogen' ||
        key === 'argon' ||
        key === 'helium'
      ) {
        imageID = 'other';
      }

      if (value.unlocked) {
        const button = new Button(
          posX,
          posY,
          this.imageSettings,
          GameData.game.imageIds.spritesheet[imageID],
          () => {
            if (this.saveData.molecules[key].justUnlocked) {
              this.saveData.molecules[key].justUnlocked = false;
            }
            this.moleculeImage.setCurrentAnimation(key);
            this.moleculeImage.tint.setColor(255, 255, 255);
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
          },
          GameData.game.imageIds.spritesheet[imageID + 'Excl']
        ).scale(0.5);
        if (this.saveData.molecules[key].justUnlocked) {
          button.showExclamationMark();
        }
        this.moleculeInfoButtons.push(
          this.addChild(button, GameData.zOrder.ui + 2)
        );
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
    Object.entries(this.saveData.molecules).forEach(([key, value]) => {
      let imageID = key;
      if (
        key === 'oxygen' ||
        key === 'nitrogen' ||
        key === 'argon' ||
        key === 'helium'
      ) {
        imageID = 'other';
      }

      if (!value.unlocked) {
        const button = new Button(
          posX,
          posY,
          this.imageSettings,
          GameData.game.imageIds.spritesheet[imageID],
          () => {
            this.moleculeImage.setCurrentAnimation(key);
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
        ).scale(0.5);
        button.tint.setColor(0, 0, 0);
        button.isClickable = false;
        this.moleculeInfoButtons.push(
          this.addChild(button, GameData.zOrder.ui + 2)
        );
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
  showMoleculeInfo() {
    // Disable other buttons in background
    this.toggleBasicMenuButtons();
    this.moleculeInfoButtons.forEach((button) => {
      button.isClickable = true;
      button.setOpacity(0.75);
    });
    this.moleculeInfoElements.forEach((element) => {
      element.setOpacity(1);
      if (element === this.moleculeDesc) {
        element.setText(GameData.general.locale.menu.molecule_info.none);
      }
    });
  }
  hideMoleculeInfo(toggleButtons = true) {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    Object.entries(this.saveData.molecules).forEach(([key, value]) => {
      if (value.justUnlocked) {
        this.newMoleculeUnlocked = true;
        this.moleculeInfo.showExclamationMark();
      }
    });

    // Disable other buttons in background
    if (toggleButtons) {
      this.toggleBasicMenuButtons();
    }
    this.moleculeInfoButtons.forEach((button) => {
      button.isClickable = false;
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

  // Close Button + Callbacks.
  addSettingsButton() {
    const settingsButton = this.addChild(
      new Button(
        100,
        game.viewport.height - 100,
        this.imageSettings,
        GameData.game.imageIds.spritesheet.settings,
        () => {
          this.showSettings();
        }
      ).scale(0.5),
      GameData.zOrder.ui
    );
    this.basicMenuButtons.push(settingsButton);
  }
  addSettings() {
    // Backdrop + Dialog image
    this.settingsElements.push(
      this.createDialogBackdrop('small', GameData.zOrder.ui + 1)
    );

    // Dialog Close Button
    this.settingsButtons.push(
      this.createDialogCloseButton(
        'small',
        () => this.hideSettings(),
        GameData.zOrder.ui + 2
      )
    );

    // Reset the save-game to its default values after user-confirmation.
    this.settingsButtons.push(
      this.addChild(
        new Button(
          game.viewport.width / 2,
          game.viewport.height * 0.35,
          this.imageSettings,
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
        GameData.zOrder.ui + 2
      )
    );

    // Text for Reset Button
    this.resetButtonText = this.createTextAreaBold(
      game.viewport.width / 2,
      game.viewport.height * 0.35 - 40,
      GameData.zOrder.ui + 3,
      0.75
    );
    this.settingsElements.push(this.resetButtonText);

    // Quit the game and return to module selection.
    // TODO: It would be wise to disable/remove this button in case the game is hosted outside of spaceHuddle (Standalone)!
    this.settingsButtons.push(
      this.addChild(
        new Button(
          game.viewport.width / 2,
          game.viewport.height * 0.5,
          this.imageSettings,
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
        ).scale(1.5, 1),
        GameData.zOrder.ui + 2
      )
    );

    // Text for Quit Button
    this.quitButtonText = this.createTextAreaBold(
      game.viewport.width / 2,
      game.viewport.height * 0.5 - 40,
      GameData.zOrder.ui + 3,
      0.75
    );
    this.settingsElements.push(this.quitButtonText);

    // Language of the game. Defaults to browser language. Once the user interacts with it, it is saved to ensure consistency across sessions.
    let imageID;
    if (GameData.general.locale === de) {
      imageID = GameData.game.imageIds.spritesheet.toggleleft;
    } else {
      imageID = GameData.game.imageIds.spritesheet.toggleright;
    }
    const localeToggle = new Button(
      game.viewport.width / 2,
      game.viewport.height / 2 + 250,
      this.imageSettings,
      imageID,
      () => {
        if (GameData.general.locale === de) {
          GameData.general.locale = en;
          this.saveData.chosenLocale = en;
          localeToggle.setImage(GameData.game.imageIds.spritesheet.toggleright);
        } else {
          GameData.general.locale = de;
          this.saveData.chosenLocale = de;
          localeToggle.setImage(GameData.game.imageIds.spritesheet.toggleleft);
        }
      }
    );
    this.settingsButtons.push(
      this.addChild(localeToggle, GameData.zOrder.ui + 2)
    );

    // 'DE'-Text
    this.settingsElements.push(
      this.createTextAreaBold(
        game.viewport.width / 2 + 200,
        game.viewport.height / 2 + 250 - 40,
        GameData.zOrder.ui + 2,
        1,
        'center',
        'DE'
      )
    );

    // 'EN'-Text
    this.settingsElements.push(
      this.createTextAreaBold(
        game.viewport.width / 2 - 200,
        game.viewport.height / 2 + 250 - 40,
        GameData.zOrder.ui + 2,
        1,
        'center',
        'EN'
      )
    );
  }
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

  // Level Pin Buttons + Callbacks. These Buttons and their links depend on the current save data in the SaveManager.
  addLevelPins() {
    Object.entries(this.saveData.levels).forEach(([key, value]) => {
      if (value.unlocked) {
        const levelPin = this.addChild(
          new Button(
            GameData.general.targetResX *
              GameData.game.levels[key].position.factorX,
            GameData.general.targetResY *
              GameData.game.levels[key].position.factorY,
            this.imageSettings,
            value.stars + GameData.game.imageIds.spritesheet.pinnone,
            () => {
              this.showLevelInfo(key);
            }
          ).scale(0.5),
          GameData.zOrder.ui
        );
        this.basicMenuButtons.push(levelPin);
      }
    });
  }
  addLevelInfo() {
    // Backdrop + Dialog image
    this.levelInfoElements.push(
      this.createDialogBackdrop('large', GameData.zOrder.ui + 1)
    );

    // Close-Button
    this.levelInfoButtons.push(
      this.createDialogCloseButton(
        'large',
        () => this.hideLevelInfo(),
        GameData.zOrder.ui + 2
      )
    );

    // TextArea (Title)
    this.levelTitle = this.createTextAreaBold(
      540,
      700,
      GameData.zOrder.ui + 2,
      0.75
    );
    this.levelInfoElements.push(this.levelTitle);

    // TextArea (Description)
    this.levelDesc = this.createTextAreaRegular(
      540,
      775,
      GameData.zOrder.ui + 2,
      0.45
    );
    this.levelInfoElements.push(this.levelDesc);

    // Icon (Difficulty)
    const iconDifficulty = new Sprite(210, 1025, this.imageSettings);
    iconDifficulty.addAnimation('image', [
      GameData.game.imageIds.spritesheet.icondifficulty,
    ]);
    iconDifficulty.setCurrentAnimation('image');
    iconDifficulty.scale(0.25);
    this.levelInfoElements.push(
      this.addChild(iconDifficulty, GameData.zOrder.ui + 2)
    );
    // TextArea (Difficulty)
    this.levelDifficulty = this.createTextAreaSemiBold(
      265,
      1000,
      GameData.zOrder.ui + 2,
      0.5,
      'left'
    );
    this.levelInfoElements.push(this.levelDifficulty);

    // Icon (Temp)
    const iconTemp = new Sprite(610, 1025, this.imageSettings);
    iconTemp.addAnimation('image', [
      GameData.game.imageIds.spritesheet.icontemperature,
    ]);
    iconTemp.setCurrentAnimation('image');
    iconTemp.scale(0.25);
    this.levelInfoElements.push(
      this.addChild(iconTemp, GameData.zOrder.ui + 2)
    );
    // TextArea (Average Temp.)
    this.levelTemperature = this.createTextAreaSemiBold(
      665,
      1000,
      GameData.zOrder.ui + 2,
      0.5,
      'left'
    );
    this.levelInfoElements.push(this.levelTemperature);

    // Icon (Type)
    const iconType = new Sprite(210, 1125, this.imageSettings);
    iconType.addAnimation('image', [
      GameData.game.imageIds.spritesheet.iconclimatetype,
    ]);
    iconType.setCurrentAnimation('image');
    iconType.scale(0.25);
    this.levelInfoElements.push(
      this.addChild(iconType, GameData.zOrder.ui + 2)
    );
    // TextArea (Climate Type)
    this.levelType = this.createTextAreaSemiBold(
      265,
      1100,
      GameData.zOrder.ui + 2,
      0.5,
      'left'
    );
    this.levelInfoElements.push(this.levelType);

    // Icon (Molecules)
    const iconMolecule = new Sprite(610, 1125, this.imageSettings);
    iconMolecule.addAnimation('image', [
      GameData.game.imageIds.spritesheet.iconmolecules,
    ]);
    iconMolecule.setCurrentAnimation('image');
    iconMolecule.scale(0.25);
    this.levelInfoElements.push(
      this.addChild(iconMolecule, GameData.zOrder.ui + 2)
    );
    Object.entries(GameData.game.levels).forEach(([key, value]) => {
      this.createMoleculeSet(key, value.molecules, GameData.zOrder.ui + 2);
    });
    this.levelInfoElements.push(this.moleculeSets);

    // Play-Button
    this.playButton = this.addChild(
      new Button(
        540,
        1600,
        this.imageSettings,
        GameData.game.imageIds.spritesheet.confirm,
        // eslint-disable-next-line @typescript-eslint/no-empty-function
        () => {}
      ).scale(1.33),
      GameData.zOrder.ui + 2
    );
    this.levelInfoButtons.push(this.playButton);

    // Start-Text
    const textStart = this.createTextAreaSemiBold(
      540,
      1550,
      GameData.zOrder.ui + 3,
      1
    );
    textStart.setText(GameData.general.locale.menu.level_info.start);
    this.levelInfoElements.push(textStart);
  }
  addTotalStarCount() {
    const starCountBG = new Sprite(
      game.viewport.width - 128 - 50,
      100,
      this.imageSettings
    );
    starCountBG.addAnimation('image', [
      GameData.game.imageIds.spritesheet.neutral,
    ]);
    starCountBG.setCurrentAnimation('image');
    starCountBG.scale(0.75);
    this.basicMenuElements.push(this.addChild(starCountBG, GameData.zOrder.ui));

    const star = new Sprite(
      game.viewport.width - 128 - 100,
      95,
      this.imageSettings
    );
    star.addAnimation('image', [GameData.game.imageIds.spritesheet.singlestar]);
    star.setCurrentAnimation('image');
    star.scale(0.2);
    this.basicMenuElements.push(this.addChild(star, GameData.zOrder.ui + 1));

    this.totalStarCount = this.createTextAreaBold(
      game.viewport.width - 150,
      75,
      GameData.zOrder.ui + 1,
      0.5,
      'center',
      this.saveData.totalStars.toString() +
        ' / ' +
        (Object.entries(this.saveData.levels).length * 3).toString()
    );
    this.basicMenuElements.push(this.totalStarCount);
  }
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

  // A toggle to enable/disable all basic (non-popup/non-dialog) menu buttons.
  toggleBasicMenuButtons() {
    this.basicMenuButtons.forEach((button) => {
      button.isClickable = !button.isClickable;
    });
  }
  showConfirmDialog(question, confirm, abort, action) {
    this.dialogBackdrop = this.createDialogBackdrop(
      'small',
      GameData.zOrder.ui + 22
    );
    this.dialogQuestion = this.createTextAreaSemiBold(
      game.viewport.width / 2,
      game.viewport.height * 0.45,
      GameData.zOrder.ui + 23,
      0.5,
      'center',
      question
    );
    this.confirmButton = this.createDialogConfirmButton(
      'small',
      action,
      GameData.zOrder.ui + 23
    );
    this.confirmText = this.createTextAreaBold(
      this.confirmButton.pos.x,
      this.confirmButton.pos.y - 40,
      GameData.zOrder.ui + 24,
      0.5,
      'center',
      confirm
    );
    this.abortButton = this.createDialogAbortButton(
      'small',
      () => {
        this.hideConfirmDialog();
      },
      GameData.zOrder.ui + 23
    );
    this.abortText = this.createTextAreaBold(
      this.abortButton.pos.x,
      this.abortButton.pos.y - 40,
      GameData.zOrder.ui + 24,
      0.5,
      'center',
      abort
    );
  }
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

  // A function which creates the dialog window including a fadeout.
  createDialogBackdrop(size, zOrder) {
    const backdrop = new Sprite(
      game.viewport.width / 2,
      game.viewport.height / 2,
      {
        image: 'dialogboxes',
        framewidth: 1080,
        frameheight: 1920,
      }
    );
    backdrop.addAnimation('small', [GameData.game.imageIds.dialogboxes.small]);
    backdrop.addAnimation('medium', [
      GameData.game.imageIds.dialogboxes.medium,
    ]);
    backdrop.addAnimation('large', [GameData.game.imageIds.dialogboxes.large]);

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

    return this.addChild(backdrop, zOrder);
  }
  // A function which creates a close button for dialog windows.
  createDialogCloseButton(size, callback, zOrder) {
    const closeButton = new Button(
      925,
      235,
      this.imageSettings,
      GameData.game.imageIds.spritesheet.cross,
      callback
    ).scale(0.33);

    switch (size) {
      case 'small':
        closeButton.pos.y = 657;
        return this.addChild(closeButton, zOrder);
      case 'medium':
        closeButton.pos.y = 446;
        return this.addChild(closeButton, zOrder);
      case 'large':
        return this.addChild(closeButton, zOrder);
      default:
        return this.addChild(closeButton, zOrder);
    }
  }
  // A function which creates a confirm button for dialog windows.
  createDialogConfirmButton(size, callback, zOrder) {
    const confirmButton = new Button(
      325,
      1622,
      this.imageSettings,
      GameData.game.imageIds.spritesheet.confirm,
      callback
    ).scale(1.33);

    switch (size) {
      case 'small':
        confirmButton.pos.y = 1200;
        return this.addChild(confirmButton, zOrder);
      case 'medium':
        confirmButton.pos.y = 1411;
        return this.addChild(confirmButton, zOrder);
      case 'large':
        return this.addChild(confirmButton, zOrder);
      default:
        return this.addChild(confirmButton, zOrder);
    }
  }
  // A function which create an abort button for dialog windows.
  createDialogAbortButton(size, callback, zOrder) {
    const abortButton = new Button(
      750,
      1622,
      this.imageSettings,
      GameData.game.imageIds.spritesheet.abort,
      callback
    ).scale(1.33);

    switch (size) {
      case 'small':
        abortButton.pos.y = 1200;
        return this.addChild(abortButton, zOrder);
      case 'medium':
        abortButton.pos.y = 1411;
        return this.addChild(abortButton, zOrder);
      case 'large':
        return this.addChild(abortButton, zOrder);
      default:
        return this.addChild(abortButton, zOrder);
    }
  }
  // Creates the relevant molecule set for the given level ID.
  createMoleculeSet(key, moleculeIDs, zOrder) {
    this.moleculeSets[key] = [];

    const increment = 65;
    let moleculeCount = 0;

    moleculeIDs.forEach((moleculeID) => {
      const sprite = new Sprite(
        690 + increment * moleculeCount,
        1125,
        this.imageSettings
      );
      sprite.addAnimation('image', [
        GameData.game.imageIds.spritesheet[moleculeID],
      ]);
      sprite.setCurrentAnimation('image');
      sprite.scale(0.2);
      this.moleculeSets[key].push(this.addChild(sprite, zOrder));
      moleculeCount++;
    });
  }
  // Functions which create a textarea. Users can not interact with it.
  createTextAreaRegular(
    x,
    y,
    zOrder,
    size = 0.5,
    alignment = 'center',
    text = ''
  ) {
    return this.addChild(
      new BitmapText(x, y, {
        font: 'WorkSans-Regular',
        size: size,
        lineHeight: 1.4,
        textAlign: alignment,
        text: text,
        wordWrapWidth: 775,
      }),
      zOrder
    );
  }
  createTextAreaSemiBold(
    x,
    y,
    zOrder,
    size = 0.5,
    alignment = 'center',
    text = ''
  ) {
    return this.addChild(
      new BitmapText(x, y, {
        font: 'WorkSans-SemiBold',
        size: size,
        lineHeight: 1.4,
        textAlign: alignment,
        text: text,
        wordWrapWidth: 775,
      }),
      zOrder
    );
  }
  createTextAreaBold(
    x,
    y,
    zOrder,
    size = 0.5,
    alignment = 'center',
    text = ''
  ) {
    return this.addChild(
      new BitmapText(x, y, {
        font: 'WorkSans-Bold',
        size: size,
        lineHeight: 1.4,
        textAlign: alignment,
        text: text,
        wordWrapWidth: 775,
      }),
      zOrder
    );
  }

  onDestroyEvent() {
    super.onDestroyEvent();
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    event.off('newMolecule', () => {});
  }
}
