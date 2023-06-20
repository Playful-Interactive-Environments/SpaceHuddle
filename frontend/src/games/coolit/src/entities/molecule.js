import { event, game, Sprite } from 'melonjs';
import Matter from 'matter-js';
import { GameData } from '@/games/coolit/src/resources.js';
import CirclePHYS from '@/games/coolit/src/entities/circlePhys.js';
import HeatWave from '@/games/coolit/src/entities/heatWave.js';

/**
 * A physics-enabled molecule which resides in the atmosphere of the individual levels. Can be either controllable or non-controllable,
 * depending on its collision-filter. Is relevant to the main game loop.
 *
 * @param x {number} The desired position on the x-Axis.
 * @param y {number} The desired position on the y-Axis.
 * @param settings {Object} The desired image-settings according to melonJS' Sprite-Setup.
 * @param imageID {number} The desired sprite-ID from the spritesheet specified in the settings.
 * @param world {Matter.Composite} The Matter.js physics world.
 * @param bodySettings {Object} The desired physics-body settings according to MatterJS' Body-Setup.
 * @param type {string} The type of molecule to spawn.
 * @param isSpawning {boolean} OPTIONAL (Default: false). If the molecule has just been spawned or not and needs further handling (i.e. through an event).
 *
 * @extends CirclePHYS
 * @see CirclePHYS
 * @see Matter.Composite
 */
export default class Molecule extends CirclePHYS {
  // Reference to a base velocity which is used when applying heat reactions.
  velocity;

  // A switch which determines the direction of the applied heat reaction. Positive means moving to the right.
  positiveDirection = true;
  // The current passive magnitude of the applied heat reaction.
  magnitude = 0;

  // If true, heat reaction will be applied in impulse mode (stronger than usual, winds down to regular speeds).
  impulse = false;
  impulseMagnitude = this.magnitude + 0.2;
  impulseTicks = 20;

  // A reference to the elapsed time. Used as custom timer in update(dt).
  time = 0;

  // Reference to the glow overlay (Sprite).
  glow;

  // Useful flags to indicate various molecule statuses.
  isSpawning = false;
  isDespawning = false;
  outOfPlayspace = true;
  applyCorrectingForce = false;
  rescaleRenderable = false;

  // The molecules type.
  moleculeType;
  // Shows how many sinks which destroy this molecule are available.
  destructiveEntityCount = 0;

  // The screen position of the upper edge from the molecule blocking collider.
  moleculeBlockerHeight = GameData.general.targetResY - (256 * 5.75) / 2;

  constructor(
    x,
    y,
    settings,
    imageID,
    world,
    bodySettings,
    type,
    isSpawning = false
  ) {
    super(x, y, settings, imageID, world, bodySettings);
    this.isSpawning = isSpawning;
    this.createRandomVelocity();
    this.createGlowElement(x, y);
    this.moleculeType = type;
    if (!this.isSpawning) {
      this.createEventHandles();
    } else {
      event.on('moleculeSpawnComplete', (molecule) => {
        if (molecule === this) {
          this.isSpawning = false;
          Matter.Body.set(
            this.matterBody,
            GameData.physics.bodySettings.controllable
          );
          this.createEventHandles();

          GameData.physics.moleculeSinks.forEach((sink) => {
            if (sink.inViewport) {
              event.emit('allowDestruction', sink.moleculeType);
            }
          });
        }
        // eslint-disable-next-line @typescript-eslint/no-empty-function
        event.off('moleculeSpawnComplete', () => {});
      });
    }
    if (type !== 'other') {
      event.on('destroyMolecule', (bodyReference) => {
        if (this.matterBody === bodyReference) {
          Matter.Composite.remove(
            GameData.physics.engine.world,
            this.matterBody
          );
          game.world.removeChild(this, false);
          GameData.physics.controllableMolecules =
            GameData.physics.controllableMolecules.filter((obj) => {
              return obj !== this;
            });

          event.emit('updateDetectors');
        }
      });
      event.on('despawnMolecule', (bodyReference) => {
        if (this.matterBody === bodyReference) {
          Matter.Composite.remove(
            GameData.physics.engine.world,
            this.matterBody
          );
          this.isDespawning = true;
        }
      });
    }
  }

  /**
   * Part of the main setup of a molecule. Creates and saves a random base velocity which can be used to make the molecule
   * wiggle or move in a certain direction at a constant speed.
   */
  createRandomVelocity() {
    this.velocity = Matter.Vector.create(
      Math.random() * (Math.round(Math.random()) ? 1 : -1),
      Math.random() * (Math.round(Math.random()) ? 1 : -1)
    );
  }
  /**
   * Part of the main setup of a molecule. Creates a glow element which is kept in sync with the molecule. Activates
   * once a heatray hits this molecule. Has no gameplay effect and serves as visual cue only.
   *
   * @param posX {number} The desired position on the x-Axis.
   * @param posY {number} The desired position on the y-Axis.
   */
  createGlowElement(posX, posY) {
    this.glow = new Sprite(
      posX,
      posY,
      GameData.game.imagePresets.spritesheetSettings
    );
    this.glow.addAnimation('image', [
      GameData.game.imageIds.spritesheet.moleculeHalo,
    ]);
    this.glow.setCurrentAnimation('image');
    this.glow.floating = true;
    this.glow.tint.parseCSS(GameData.general.colors.bad);
    this.glow.setOpacity(0);
    game.world.addChild(this.glow, GameData.zOrder.usables + 1);
  }
  /**
   * Part of the main setup of a molecule. Creates multiple event handles which every molecule needs once it has fully
   * spawned.
   */
  createEventHandles() {
    event.on('moleculeHeatwave', (matterBody) => {
      if (this.matterBody === matterBody) {
        new HeatWave(
          this.matterBody.position.x,
          this.matterBody.position.y,
          GameData.general.colors.bad
        );
        this.impulse = true;
      }
    });
    event.on('changeMagnitude', () => {
      this.magnitude += 0.005;
      this.impulseMagnitude = this.magnitude + 0.2;
    });
    event.on('allowDestruction', (moleculeType) => {
      if (this.moleculeType === moleculeType) {
        if (this.destructiveEntityCount !== 0) {
          this.destructiveEntityCount++;
        } else {
          this.destructiveEntityCount++;
          this.matterBody.collisionFilter =
            GameData.physics.bodySettings.destructible.collisionFilter;
        }
      }
    });
    event.on('restrictDestruction', (moleculeType) => {
      if (this.moleculeType === moleculeType) {
        if (this.destructiveEntityCount > 1) {
          this.destructiveEntityCount--;
        } else {
          this.destructiveEntityCount--;
          this.returnToPlayspace(false);
        }
      }
    });
  }

  /**
   * Method which adds a one-time force to the molecule. Direction is dependent on the saved base velocity. Alternating
   * directions are achieved by switching a boolean every time it is called.
   *
   * @param magnitude {number} The magnitude to apply the force with. Should be kept in the decimals to avoid over-stimulation.
   */
  heatReaction(magnitude) {
    if (this.positiveDirection) {
      Matter.Body.applyForce(
        this.matterBody,
        this.matterBody.position,
        Matter.Vector.mult(this.velocity, magnitude)
      );
      this.positiveDirection = !this.positiveDirection;
    } else {
      Matter.Body.applyForce(
        this.matterBody,
        this.matterBody.position,
        Matter.Vector.mult(this.velocity, magnitude * -1)
      );
      this.positiveDirection = !this.positiveDirection;
    }
  }
  /**
   * Called every frame. Handles the glow visualisations on impact with a heatray and applies the heat-reaction with the current magnitude present.
   */
  applyHeatReaction() {
    if (this.impulse) {
      if (this.glow.getOpacity() === 0) {
        this.glow.setOpacity(1);
      }
      this.glow.setOpacity(this.glow.getOpacity() - 0.05);
      this.impulseTicks -= 1;
      if (this.impulseTicks <= 0) {
        this.impulse = false;
      }

      this.heatReaction(this.impulseMagnitude);
      this.impulseMagnitude -= 0.01;
    } else {
      this.glow.setOpacity(0);
      this.impulseTicks = 20;

      this.heatReaction(this.magnitude);
    }
  }

  // Set the scale of this molecule.
  /**
   * Sets the scale of this molecule. Overwrites the method from CirclePHYS to include the proper scaling of the glow
   * element. Assumes the object is a circle and as such scaling is applied the same on both axes.
   *
   * @param scale {number}
   * @param includesPhysicsBody
   *
   * @see CirclePHYS
   */
  setScale(scale, includesPhysicsBody = true) {
    super.setScale(scale, includesPhysicsBody);
    this.glow.scale(scale);
  }

  // Tell a molecule to move back to its available space.
  returnToPlayspace(rescaleRenderable = false) {
    if (this.outOfPlayspace) {
      const posOrNeg = Math.random() > 0.5 ? 1 : -1;
      Matter.Body.setVelocity(
        this.matterBody,
        new Matter.Vector.create(posOrNeg * (Math.random() * 0.25), -1)
      );
      this.applyCorrectingForce = true;
      this.rescaleRenderable = rescaleRenderable;
    }
  }

  // Called every frame. Updates the status of this molecule.
  update(dt) {
    // Keep Physics Body, Visual Layer and Glow Layer in sync.
    super.update(dt);
    this.glow.pos.x = this.pos.x;
    this.glow.pos.y = this.pos.y;

    this.outOfPlayspace = this.pos.y >= this.moleculeBlockerHeight - 128;

    // Apply heat reactions if the molecule has finished setting up after spawning.
    if (!this.isSpawning) {
      if (this.time === 0) {
        this.applyHeatReaction();
        this.time += dt;
      } else if (this.time <= 100) {
        this.time += dt;
      } else {
        this.time = 0;
      }
    }

    // Conditions for pushing a molecule back into the available playspace.
    if (this.applyCorrectingForce) {
      Matter.Body.applyForce(
        this.matterBody,
        this.matterBody.position,
        Matter.Vector.create(0, -0.0025)
      );

      if (this.pos.y < this.moleculeBlockerHeight - 128) {
        this.applyCorrectingForce = false;
        this.matterBody.collisionFilter =
          GameData.physics.bodySettings.controllable.collisionFilter;
      }
    }

    // Conditions for rescaling a molecule back to its intended size.
    if (this.rescaleRenderable) {
      this.currentTransform.val[0] += 0.0025;
      this.currentTransform.val[4] += 0.0025;
      this.glow.currentTransform.val[0] += 0.0025;
      this.glow.currentTransform.val[4] += 0.0025;
      if (
        this.currentTransform.val[0] >=
        GameData.game.molecules[this.moleculeType].maxScale
      ) {
        this.rescaleRenderable = false;
        this.currentTransform.val[0] =
          GameData.game.molecules[this.moleculeType].maxScale;
        this.currentTransform.val[4] =
          GameData.game.molecules[this.moleculeType].maxScale;
        this.glow.currentTransform.val[0] =
          GameData.game.molecules[this.moleculeType].maxScale;
        this.glow.currentTransform.val[4] =
          GameData.game.molecules[this.moleculeType].maxScale;
      }
    }

    if (
      this.isSpawning &&
      !this.applyCorrectingForce &&
      !this.rescaleRenderable
    ) {
      event.emit('moleculeSpawnComplete', this);
    }

    if (this.isDespawning) {
      this.scale(1 - 0.1);
      this.setOpacity(this.getOpacity() - 0.05);
      if (this.getOpacity() <= 0) {
        event.emit('destroyMolecule', this.matterBody);
        new HeatWave(this.pos.x, this.pos.y, GameData.general.colors.good);
      }
    }

    return true;
  }
  // Remove listeners, intervals, etc. here.
  onDestroyEvent() {
    super.onDestroyEvent();
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    event.off('changeMagnitude', () => {});
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    event.off('moleculeHeatwave', () => {});
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    event.off('allowDestruction', () => {});
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    event.off('restrictDestruction', () => {});
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    event.off('destroyMolecule', () => {});
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    event.off('moleculeSpawnComplete', () => {});
  }
}
