import { Stage, level, game, event, Sprite } from 'melonjs';
import Matter from 'matter-js';
import { GameData, collisionGroups } from '@/games/coolit/src/resources.js';
import Molecule from '@/games/coolit/src/entities/molecule.js';
import LightRay from '@/games/coolit/src/entities/lightRay.js';
import RectPHYS from '@/games/coolit/src/entities/rectPhys.js';
import EmissionEvent from '@/games/coolit/src/entities/emissionEvent.js';

/* TODO
 * Useful resource for restricting physics-movement on mouse interactions. Relevant for a fix regarding the ability to
 * force-drag objects through one another or outside the meant game-space. Implementation-Attempt will be made once
 * the game is more complete, since this will take up a lot of time.
 *
 * https://stackoverflow.com/questions/59321773/prevent-force-dragging-bodies-through-other-bodies-with-matterjs
 */

/**
 * The meat of the game. Everything that should happen once a level has been started will pass through this stage at least once.
 * Sets up crucial event handlers and manages the game state.
 *
 * @extends Stage
 * @see Stage
 */
export default class Play extends Stage {
  saveData;

  spawnInterval;
  countdown;
  time;
  hasGameStarted;
  moveBack;
  eventCooldown;
  scrollSpeed;
  allRaysSpawned;
  moleculesHit;

  lightrays;

  constructor() {
    super();
    event.on('destroyLightray', (context, hasHitMolecule) => {
      Matter.Composite.remove(
        GameData.physics.engine.world,
        context.matterBody
      );
      game.world.removeChild(context, false);
      this.lightrays = this.lightrays.filter((lightray) => {
        return context !== lightray;
      });
      if (hasHitMolecule) {
        this.moleculesHit++;
        event.emit('increaseHeat', this.moleculesHit);
        event.emit('changeMagnitude');
      } else {
        event.emit('checkVictoryCondition');
      }
    });
    event.on('checkVictoryCondition', () => {
      if (
        GameData.instances.playUI.hasReachedMax ||
        (this.allRaysSpawned && this.lightrays.length === 0)
      ) {
        event.emit('end');
      }
    });
  }

  /**
   * Part of the main stage setup. Physics initialization once the stage has fully loaded. Creates a Matter.js physics
   * world and sets necessary physics parameters in the engine.
   *
   * @see Matter.Engine
   * @see Matter.Runner
   */
  matterSetup() {
    GameData.physics.engine = Matter.Engine.create();
    GameData.physics.engine.gravity.scale = GameData.physics.gravityScale;
    Matter.Runner.run(Matter.Runner.create(), GameData.physics.engine);
  }
  /**
   * Part of the main stage setup. Controls setup. Some physics-enabled objects need to be moved by pointer-events. This
   * setup handles that problem.
   *
   * @see Matter.Mouse
   * @see Matter.MouseConstraint
   */
  addMatterControls() {
    const mouse = Matter.Mouse.create(game.renderer.getCanvas());
    const mouseConstraint = Matter.MouseConstraint.create(
      GameData.physics.engine,
      {
        mouse: mouse,
        collisionFilter: {
          mask: collisionGroups.CONTROLLABLE,
        },
        constraint: {
          stiffness: GameData.physics.mouseStiffness,
        },
      }
    );
    Matter.Composite.add(GameData.physics.engine.world, mouseConstraint);
  }

  /**
   * Adds borders to the viewport to restrict physics-enabled bodies to accidentally move out of bounds.
   *
   * @see RectPHYS
   */
  addBorders() {
    // Top
    const borderTop = new RectPHYS(
      game.viewport.width / 2,
      -75 + 5,
      GameData.game.imagePresets.spritesheetSettings,
      GameData.game.imageIds.spritesheet.empty,
      GameData.physics.engine.world,
      GameData.physics.bodySettings.worldBorder
    );
    borderTop.setScale(5, 1);
    borderTop.floating = true;
    game.world.addChild(borderTop, GameData.zOrder.background + 5);

    // Right
    const borderRight = new RectPHYS(
      game.viewport.width +
        GameData.game.imagePresets.spritesheetSettings.framewidth / 2 -
        5,
      game.viewport.height / 2,
      GameData.game.imagePresets.spritesheetSettings,
      GameData.game.imageIds.spritesheet.empty,
      GameData.physics.engine.world,
      GameData.physics.bodySettings.worldBorder
    );
    borderRight.setScale(1, 8);
    borderRight.floating = true;
    game.world.addChild(borderRight, GameData.zOrder.background + 5);

    // Bottom Moleculeblocker
    const borderBottomMolecules = new RectPHYS(
      game.viewport.width / 2,
      game.viewport.height,
      GameData.game.imagePresets.spritesheetSettings,
      GameData.game.imageIds.spritesheet.empty,
      GameData.physics.engine.world,
      GameData.physics.bodySettings.world
    );
    borderBottomMolecules.setScale(5, 5.75);
    borderBottomMolecules.setOpacity(0.15);
    borderBottomMolecules.floating = true;
    game.world.addChild(borderBottomMolecules, GameData.zOrder.background + 5);

    // Bottom
    const borderBottom = new RectPHYS(
      game.viewport.width / 2,
      game.viewport.height +
        GameData.game.imagePresets.spritesheetSettings.frameheight / 2 -
        5,
      GameData.game.imagePresets.spritesheetSettings,
      GameData.game.imageIds.spritesheet.empty,
      GameData.physics.engine.world,
      GameData.physics.bodySettings.worldBorder
    );
    borderBottom.setScale(5, 1);
    borderBottom.floating = true;
    game.world.addChild(borderBottom, GameData.zOrder.background + 5);

    // Left
    const borderLeft = new RectPHYS(
      -(GameData.game.imagePresets.spritesheetSettings.framewidth / 2) + 5,
      game.viewport.height / 2,
      GameData.game.imagePresets.spritesheetSettings,
      GameData.game.imageIds.spritesheet.empty,
      GameData.physics.engine.world,
      GameData.physics.bodySettings.worldBorder
    );
    borderLeft.setScale(1, 8);
    borderLeft.floating = true;
    game.world.addChild(borderLeft, GameData.zOrder.background + 5);
  }
  /**
   * Adds the controllable molecules to the scene. These are game loop critical objects.
   *
   * @param levelID {string} The level ID of the currently loaded level. Is required to load the correct set of molecules.
   *
   * @see Molecule
   */
  addMatterControllables(levelID) {
    const moleculeSet = GameData.game.levels[levelID].molecules;

    moleculeSet.forEach((moleculeID) => {
      switch (moleculeID) {
        case 'watervapor':
          for (
            let i = 0;
            i <
            GameData.game.difficulty[GameData.game.levels[levelID].difficulty]
              .controllables *
              2;
            i++
          ) {
            game.world.addChild(
              this.createMolecule(
                moleculeID,
                GameData.physics.bodySettings.controllable
              ),
              GameData.zOrder.usables
            );
          }
          break;
        case 'nitrousoxide':
          for (
            let i = 0;
            i <
            GameData.game.difficulty[GameData.game.levels[levelID].difficulty]
              .controllables *
              1;
            i++
          ) {
            game.world.addChild(
              this.createMolecule(
                moleculeID,
                GameData.physics.bodySettings.controllable
              ),
              GameData.zOrder.usables
            );
          }
          break;
        case 'carbondioxide':
          for (
            let i = 0;
            i <
            GameData.game.difficulty[GameData.game.levels[levelID].difficulty]
              .controllables *
              2;
            i++
          ) {
            game.world.addChild(
              this.createMolecule(
                moleculeID,
                GameData.physics.bodySettings.controllable
              ),
              GameData.zOrder.usables
            );
          }
          break;
        case 'ozone':
          for (
            let i = 0;
            i <
            GameData.game.difficulty[GameData.game.levels[levelID].difficulty]
              .controllables *
              1;
            i++
          ) {
            game.world.addChild(
              this.createMolecule(
                moleculeID,
                GameData.physics.bodySettings.controllable
              ),
              GameData.zOrder.usables
            );
          }
          break;
        case 'methane':
          // Green
          for (
            let i = 0;
            i <
            GameData.game.difficulty[GameData.game.levels[levelID].difficulty]
              .controllables *
              1.5;
            i++
          ) {
            game.world.addChild(
              this.createMolecule(
                moleculeID,
                GameData.physics.bodySettings.controllable
              ),
              GameData.zOrder.usables
            );
          }
          break;
        case 'fluorinatedgases':
          // Yellow
          for (
            let i = 0;
            i <
            GameData.game.difficulty[GameData.game.levels[levelID].difficulty]
              .controllables *
              1;
            i++
          ) {
            game.world.addChild(
              this.createMolecule(
                moleculeID,
                GameData.physics.bodySettings.controllable
              ),
              GameData.zOrder.usables
            );
          }
          break;
      }
    });
  }
  /**
   * Adds some non-controllable molecules to the scene which obstruct movement of controllable molecules and cause
   * occasional chaos in the atmosphere.
   *
   * @param amount {number} The amount of non-controllables to spawn.
   *
   * @see Molecule
   */
  addMatterNonControllables(amount) {
    for (let i = 0; i < amount; i += 1) {
      const molecule = this.createMolecule(
        'oxygen',
        GameData.physics.bodySettings.noncontrollable
      );
      molecule.setOpacity(0.25);
      game.world.addChild(molecule, GameData.zOrder.usables);
    }
  }
  /**
   * Adds a static background which moves with the viewport and always stays the same, i.e. the sky.
   */
  addBackground() {
    // Sky
    const sky = new Sprite(
      game.viewport.width / 2,
      game.viewport.height / 2,
      GameData.game.imagePresets.spritesheetSettings
    );
    sky.scale(5, 9);
    sky.floating = true;
    sky.addAnimation('image', [GameData.game.imageIds.spritesheet.levelSky]);
    sky.setCurrentAnimation('image');
    game.world.addChild(sky, GameData.zOrder.background);
  }

  /**
   * Callback once the countdown has finished and the game should start. Requires the level ID to apply current difficulty
   * settings.
   *
   * @param levelID {string} The level ID of the currently loaded level. Is required to load the correct difficulty.
   */
  startGame(levelID) {
    let amountSpawned = 0;
    this.spawnInterval = setInterval(() => {
      if (
        amountSpawned <
        GameData.game.difficulty[GameData.game.levels[levelID].difficulty]
          .lightrays
      ) {
        amountSpawned++;
        const lightray = new LightRay(
          (game.viewport.width - 200) * Math.random() + 100,
          45,
          GameData.game.imagePresets.spritesheetSettings,
          GameData.game.imageIds.spritesheet.molecule,
          GameData.physics.engine,
          GameData.physics.bodySettings.lightray,
          GameData.physics.reactiveSurfaces,
          GameData.physics.controllableMolecules
        );
        lightray.setOpacity(0.5);
        lightray.setScale(0.15);
        lightray.tint.parseCSS(GameData.general.colors.light);
        this.lightrays.push(lightray);
        game.world.addChild(lightray, GameData.zOrder.background + 11);
        event.emit('lightraySpawned', lightray);
      } else {
        this.allRaysSpawned = true;
        clearInterval(this.spawnInterval);
      }
    }, 2500);
  }
  /**
   * Callback once the game has concluded, i.e. either a victory or defeat was achieved. Requires the level ID to
   * correctly handle saving mechanisms.
   *
   * @param levelID {string} The level ID of the currently loaded level. Is required to correctly handle saving mechanisms.
   */
  endGame(levelID) {
    if (this.spawnInterval) {
      clearInterval(this.spawnInterval);
      this.lightrays.forEach((lightray) => {
        game.world.removeChild(lightray);
      });
    }

    if (GameData.instances.playUI.hasReachedMax) {
      GameData.instances.playUI.showSummary(false, levelID);
    } else {
      GameData.instances.playUI.showSummary(true, levelID);
    }
  }
  /**
   * Part of the main stage setup. Resets the game state and all its important values.
   */
  resetVariables() {
    this.saveData = GameData.instances.saveManager.getSaveData();

    this.countdown = 5;
    this.spawnInterval = null;
    this.time = 0;
    this.hasGameStarted = false;
    this.moveBack = false;
    this.eventCooldown = 10000;
    this.scrollSpeed = 2;
    this.allRaysSpawned = false;
    this.moleculesHit = 0;

    this.lightrays = [];

    GameData.physics.engine = null;
    GameData.physics.reactiveSurfaces = [];
    GameData.physics.controllableMolecules = [];
    GameData.physics.moleculeSinks = [];
  }

  /**
   * Creates a molecule with unique settings depending on the given molecule ID.
   *
   * @param moleculeID {string} The type of molecule to create.
   * @param bodySettings {Object} The desired physics-body settings according to MatterJS' Body-Setup.
   * @returns {Molecule} A Molecule object.
   * @see Molecule
   */
  createMolecule(moleculeID, bodySettings) {
    const molecule = new Molecule(
      game.viewport.width * Math.random(),
      (game.viewport.height / 2) * Math.random(),
      GameData.game.imagePresets.spritesheetSettings,
      GameData.game.imageIds.spritesheet.molecule,
      GameData.physics.engine.world,
      bodySettings,
      moleculeID
    );

    if (moleculeID === 'other') {
      molecule.setOpacity(0.33);
    }

    molecule.setScale(GameData.game.molecules[moleculeID].maxScale);
    molecule.tint.parseCSS(GameData.game.molecules[moleculeID].tint);
    GameData.physics.controllableMolecules.push(molecule);

    return molecule;
  }

  // Called upon switching to this stage (or whenever reset() is called). Initialization should happen here.
  onResetEvent(...args) {
    this.resetVariables();

    this.matterSetup();

    this.addBorders();
    level.load('sidescrollerTEST');
    this.addBackground();

    this.addMatterNonControllables(
      GameData.game.difficulty[GameData.game.levels[args[1]].difficulty]
        .noncontrollables
    );
    this.addMatterControllables(args[1]);
    event.emit('updateDetectors');

    // Add the UI
    GameData.instances.playUI.minScale =
      GameData.game.difficulty[
        GameData.game.levels[args[1]].difficulty
      ].minTempScale;
    GameData.instances.playUI.currentHeatScaleFactor =
      GameData.game.difficulty[
        GameData.game.levels[args[1]].difficulty
      ].startTempScale;
    GameData.instances.playUI.addElements(args[1]);

    // Add important game events here.
    event.once('start', () => {
      this.startGame(args[1]);
    });
    event.once('end', () => {
      this.endGame(args[1]);
    });
  }
  // Called once every frame. Returns true if the object is dirty (needs to be rendered after the update), false otherwise.
  update(dt) {
    super.update(dt);

    if (!this.hasGameStarted) {
      event.emit('countdownStart', this.countdown);
      this.time += dt;
      if (this.time >= 1000 && this.countdown > 0) {
        this.countdown--;
        event.emit('countdownUpdate', this.countdown);
        this.time = 0;
      }
      if (this.time >= 1000 && this.countdown === 0) {
        event.emit('countdownUpdate', this.countdown);
        this.hasGameStarted = true;
        this.addMatterControls();
        this.time = 0;
      }
    } else {
      event.emit('start');
      if (this.moveBack) {
        game.viewport.move(-this.scrollSpeed, 0);
        if (game.viewport.pos.x <= 0) {
          this.moveBack = false;
        }
      } else {
        game.viewport.move(this.scrollSpeed, 0);
        if (game.viewport.pos.x >= 3840 - GameData.general.targetResX) {
          this.moveBack = true;
        }
      }

      this.time += dt;
      if (this.time >= this.eventCooldown) {
        new EmissionEvent('carbondioxide', this.moveBack);
        this.time = 0;
      }
    }
    return true;
  }
  // Remove event listeners, timers, etc.
  onDestroyEvent(...args) {
    super.onDestroyEvent(args);
    Matter.Composite.clear(GameData.physics.engine.world, false);
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    event.off('start', () => {});
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    event.off('end', () => {});
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    event.off('destroyLightray', () => {});
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    event.off('checkVictoryCondition', () => {});
  }
}
