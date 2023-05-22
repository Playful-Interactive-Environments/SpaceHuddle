import { Stage, level, game, event, Sprite, pool } from 'melonjs';
import Matter from 'matter-js';
import { GameData, collisionGroups } from '@/games/coolit/src/resources.js';
import Molecule from '@/games/coolit/src/entities/molecule.js';
import LightRay from '@/games/coolit/src/entities/lightRay.js';
import RectPHYS from '@/games/coolit/src/entities/rectPhys.js';

/**
 * Useful resource for restricting physics-movement on mouse interactions. Relevant for a fix regarding the ability to
 * force-drag objects through one another or outside the meant game-space. Implementation-Attempt will be made once
 * the game is more complete.
 *
 * https://stackoverflow.com/questions/59321773/prevent-force-dragging-bodies-through-other-bodies-with-matterjs
 */

export default class Play extends Stage {
  saveData;

  imageSettings;
  spawnInterval;
  countdown;
  time;
  hasGameStarted;
  moveBack;
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

  // Matter.js Setup (Physics). Basic physics engine initialization and enabling of mouse interactions.
  matterSetup() {
    GameData.physics.engine = Matter.Engine.create();
    GameData.physics.engine.gravity.scale = GameData.physics.gravityScale;
    this.addMatterControls();
    Matter.Runner.run(Matter.Runner.create(), GameData.physics.engine);
  }
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

  // Adding the game content to the scene. Borders, Interactables, Background, etc.
  addBorders() {
    const bodySettings = {
      isStatic: true,
      collisionFilter: {
        category: collisionGroups.WORLD_BORDER,
        mask:
          collisionGroups.CONTROLLABLE |
          collisionGroups.NON_CONTROLLABLE |
          collisionGroups.LIGHT_RAY |
          collisionGroups.HEAT_RAY,
      },
    };

    // Top
    const borderTop = new RectPHYS(
      game.viewport.width / 2,
      -75 + 5,
      this.imageSettings,
      GameData.game.imageIds.spritesheet.empty,
      GameData.physics.engine.world,
      {
        isStatic: true,
        collisionFilter: {
          category: collisionGroups.WORLD_BORDER,
          mask: collisionGroups.CONTROLLABLE | collisionGroups.NON_CONTROLLABLE,
        },
      }
    );
    borderTop.setScale(5, 1);
    borderTop.floating = true;
    game.world.addChild(borderTop, GameData.zOrder.background + 5);

    // Right
    const borderRight = new RectPHYS(
      game.viewport.width + this.imageSettings.framewidth / 2 - 5,
      game.viewport.height / 2,
      this.imageSettings,
      GameData.game.imageIds.spritesheet.empty,
      GameData.physics.engine.world,
      bodySettings
    );
    borderRight.setScale(1, 8);
    borderRight.floating = true;
    game.world.addChild(borderRight, GameData.zOrder.background + 5);

    // Bottom Moleculeblocker
    const borderBottomMolecules = new RectPHYS(
      game.viewport.width / 2,
      game.viewport.height,
      this.imageSettings,
      GameData.game.imageIds.spritesheet.empty,
      GameData.physics.engine.world,
      {
        isStatic: true,
        collisionFilter: {
          category: collisionGroups.WORLD,
          mask: collisionGroups.CONTROLLABLE | collisionGroups.NON_CONTROLLABLE,
        },
      }
    );
    borderBottomMolecules.setScale(5, 5.75);
    borderBottomMolecules.setOpacity(0.15);
    borderBottomMolecules.floating = true;
    game.world.addChild(borderBottomMolecules, GameData.zOrder.background + 5);

    // Bottom
    const borderBottom = new RectPHYS(
      game.viewport.width / 2,
      game.viewport.height + this.imageSettings.frameheight / 2 - 5,
      this.imageSettings,
      GameData.game.imageIds.spritesheet.empty,
      GameData.physics.engine.world,
      bodySettings
    );
    borderBottom.setScale(5, 1);
    borderBottom.floating = true;
    game.world.addChild(borderBottom, GameData.zOrder.background + 5);

    // Left
    const borderLeft = new RectPHYS(
      -(this.imageSettings.framewidth / 2) + 5,
      game.viewport.height / 2,
      this.imageSettings,
      GameData.game.imageIds.spritesheet.empty,
      GameData.physics.engine.world,
      bodySettings
    );
    borderLeft.setScale(1, 8);
    borderLeft.floating = true;
    game.world.addChild(borderLeft, GameData.zOrder.background + 5);
  }
  addMatterControllables(levelID, matterWorld) {
    const bodySettings = {
      restitution: 1,
      collisionFilter: {
        category: collisionGroups.CONTROLLABLE,
        mask:
          collisionGroups.CONTROLLABLE |
          collisionGroups.NON_CONTROLLABLE |
          collisionGroups.WORLD |
          collisionGroups.WORLD_BORDER |
          collisionGroups.LIGHT_RAY,
      },
    };

    const moleculeSet = GameData.game.levels[levelID].molecules;

    moleculeSet.forEach((molecule) => {
      switch (molecule) {
        case 'watervapor':
          // White
          for (
            let i = 0;
            i <
            GameData.game.difficulty[GameData.game.levels[levelID].difficulty]
              .controllables *
              2;
            i++
          ) {
            const molecule = new Molecule(
              game.viewport.width * Math.random(),
              (game.viewport.height / 2) * Math.random(),
              this.imageSettings,
              GameData.game.imageIds.spritesheet.watervapor,
              matterWorld,
              bodySettings,
              'watervapor'
            );
            molecule.setScale(0.25);
            GameData.physics.controllableMolecules.push(molecule);
            game.world.addChild(molecule, GameData.zOrder.usables);
          }
          break;
        case 'nitrousoxide':
          // Red
          for (
            let i = 0;
            i <
            GameData.game.difficulty[GameData.game.levels[levelID].difficulty]
              .controllables *
              1;
            i++
          ) {
            const molecule = new Molecule(
              game.viewport.width * Math.random(),
              (game.viewport.height / 2) * Math.random(),
              this.imageSettings,
              GameData.game.imageIds.spritesheet.nitrousoxide,
              matterWorld,
              bodySettings,
              'nitrousoxide'
            );
            molecule.setScale(0.5);
            GameData.physics.controllableMolecules.push(molecule);
            game.world.addChild(molecule, GameData.zOrder.usables);
          }
          break;
        case 'carbondioxide':
          // Black
          for (
            let i = 0;
            i <
            GameData.game.difficulty[GameData.game.levels[levelID].difficulty]
              .controllables *
              2;
            i++
          ) {
            const molecule = new Molecule(
              game.viewport.width * Math.random(),
              (game.viewport.height / 2) * Math.random(),
              this.imageSettings,
              GameData.game.imageIds.spritesheet.carbondioxide,
              matterWorld,
              bodySettings,
              'carbondioxide'
            );
            molecule.setScale(0.33);
            GameData.physics.controllableMolecules.push(molecule);
            game.world.addChild(molecule, GameData.zOrder.usables);
          }
          break;
        case 'ozone':
          // Blue
          for (
            let i = 0;
            i <
            GameData.game.difficulty[GameData.game.levels[levelID].difficulty]
              .controllables *
              1;
            i++
          ) {
            const molecule = new Molecule(
              game.viewport.width * Math.random(),
              (game.viewport.height / 2) * Math.random(),
              this.imageSettings,
              GameData.game.imageIds.spritesheet.ozone,
              matterWorld,
              bodySettings,
              'ozone'
            );
            molecule.setScale(0.33);
            GameData.physics.controllableMolecules.push(molecule);
            game.world.addChild(molecule, GameData.zOrder.usables);
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
            const molecule = new Molecule(
              game.viewport.width * Math.random(),
              (game.viewport.height / 2) * Math.random(),
              this.imageSettings,
              GameData.game.imageIds.spritesheet.methane,
              matterWorld,
              bodySettings,
              'methane'
            );
            molecule.setScale(0.25);
            GameData.physics.controllableMolecules.push(molecule);
            game.world.addChild(molecule, GameData.zOrder.usables);
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
            const molecule = new Molecule(
              game.viewport.width * Math.random(),
              (game.viewport.height / 2) * Math.random(),
              this.imageSettings,
              GameData.game.imageIds.spritesheet.fluorinatedgases,
              matterWorld,
              bodySettings,
              'fluorinatedgases'
            );
            molecule.setScale(0.5);
            GameData.physics.controllableMolecules.push(molecule);
            game.world.addChild(molecule, GameData.zOrder.usables);
          }
          break;
      }
    });
  }
  addMatterNonControllables(amount, matterWorld) {
    const bodySettings = {
      restitution: 1,
      collisionFilter: {
        category: collisionGroups.NON_CONTROLLABLE,
        mask:
          collisionGroups.NON_CONTROLLABLE |
          collisionGroups.CONTROLLABLE |
          collisionGroups.WORLD |
          collisionGroups.WORLD_BORDER,
      },
    };

    for (let i = 0; i < amount; i += 1) {
      const molecule = new Molecule(
        game.viewport.width * Math.random(),
        (game.viewport.height / 2) * Math.random(),
        this.imageSettings,
        GameData.game.imageIds.spritesheet.other,
        matterWorld,
        bodySettings,
        'other'
      );
      molecule.setScale(0.2);
      molecule.setOpacity(0.33);
      game.world.addChild(molecule, GameData.zOrder.background + 1);
    }
  }
  addBackground() {
    // Sky
    const sky = new Sprite(
      game.viewport.width / 2,
      game.viewport.height / 2,
      this.imageSettings
    );
    sky.scale(5, 9);
    sky.floating = true;
    sky.addAnimation('image', [GameData.game.imageIds.spritesheet.sky]);
    sky.setCurrentAnimation('image');
    game.world.addChild(sky, GameData.zOrder.background);
  }

  // Callbacks to start, end and reset the game state.
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
          this.imageSettings,
          GameData.game.imageIds.spritesheet.fluorinatedgases,
          GameData.physics.engine,
          {
            restitution: 1,
            friction: 0,
            airFriction: 0,
            staticFriction: 0,
            mass: 0,
            velocity: new Matter.Vector.create(0, 1),
            collisionFilter: {
              category: collisionGroups.LIGHT_RAY,
              mask:
                collisionGroups.WORLD_BORDER |
                collisionGroups.WORLD |
                collisionGroups.ADSORBING |
                collisionGroups.ABSORBING,
            },
          },
          GameData.physics.reactiveSurfaces,
          GameData.physics.controllableMolecules
        );
        lightray.setOpacity(0.5);
        lightray.setScale(0.15);
        this.lightrays.push(lightray);
        game.world.addChild(lightray, GameData.zOrder.background + 11);
        event.emit('lightraySpawned', lightray);
      } else {
        this.allRaysSpawned = true;
        clearInterval(this.spawnInterval);
      }
    }, 2500);
  }
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
  resetVariables() {
    this.saveData = GameData.instances.saveManager.getSaveData();

    this.imageSettings = {
      image: 'spritesheet',
      framewidth: 256,
      frameheight: 256,
    };
    this.countdown = 3;
    this.spawnInterval = null;
    this.time = 0;
    this.hasGameStarted = false;
    this.moveBack = false;
    this.scrollSpeed = 2;
    this.allRaysSpawned = false;
    this.moleculesHit = 0;

    this.lightrays = [];
    GameData.physics.engine = null;
    GameData.physics.reactiveSurfaces = [];
    GameData.physics.controllableMolecules = [];
    GameData.physics.moleculeSinks = [];
  }

  // Called upon switching to this stage (or whenever reset() is called). Initialization should happen here.
  onResetEvent(...args) {
    this.resetVariables();

    this.matterSetup();

    level.load('sidescrollerTEST');

    this.addBorders();
    this.addBackground();
    this.addMatterNonControllables(
      GameData.game.difficulty[GameData.game.levels[args[1]].difficulty]
        .noncontrollables,
      GameData.physics.engine.world
    );
    this.addMatterControllables(args[1], GameData.physics.engine.world);

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

    event.emit('updateDetectors');

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
      this.time += dt;
      if (this.time >= 1000 && this.countdown > 0) {
        this.countdown--;
        this.time = 0;
      }
      if (this.time >= 1000 && this.countdown === 0) {
        this.hasGameStarted = true;
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
