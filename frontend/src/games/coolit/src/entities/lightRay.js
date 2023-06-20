import { game, event, Sprite, Vector2d } from 'melonjs';
import Matter from 'matter-js';
import CirclePHYS from '@/games/coolit/src/entities/circlePhys.js';
import { collisionGroups, GameData } from '@/games/coolit/src/resources.js';

/**
 * A lightray object which enters the atmosphere and bounces off the ground - returning to the sky as heat (infrared).
 * This is part of the main game-loop of 'Cool It'.
 *
 * This object moves at a constant speed, draws a trail behind it and collides with other game-relevant objects.
 *
 * @param x {number} The desired position on the x-Axis.
 * @param y {number} The desired position on the y-Axis.
 * @param settings {Object} The desired image-settings according to melonJS' Sprite-Setup.
 * @param imageID {number} The desired sprite-ID from the spritesheet specified in the settings.
 * @param engine {Matter.Engine} The Matter.js physics engine instance.
 * @param bodySettings {Object} The desired physics-body settings according to MatterJS' Body-Setup.
 * @param reactiveSurfaces {any[]} The physics-enabled surface objects which the lightrays should interact with.
 * @param controllableMolecules {Molecule[]} The physics-enabled molecules in the atmosphere which should interact
 * with the lighrays.
 *
 * @extends CirclePHYS
 * @see CirclePHYS
 * @see Matter.Engine
 * @see Molecule
 */
class LightRay extends CirclePHYS {
  // Matter.js
  engine;
  detectors = [];

  // General setup
  trailElements = [];
  currentTrailColor = GameData.general.colors.light;
  maxTrailLength = 50;
  maxTrailScale = 0.035;
  trailSpaceCounter = 0;
  targetTrailSpacing = 2;

  // Physics behaviour
  time;
  bodySettings;
  directionOfTravel;
  directionOfTravelPerpendicular;
  constantSpeed;
  waveAmplitude;
  waveIntensity;
  hasCollidedWithFloor;
  hasChangedCollisionMasks;

  constructor(
    x,
    y,
    settings,
    imageID,
    engine,
    bodySettings,
    reactiveSurfaces,
    controllableMolecules
  ) {
    super(x, y, settings, imageID, engine.world, bodySettings);

    // Matter.js
    this.engine = engine;
    this.detectors = this.createDetectors(
      reactiveSurfaces,
      controllableMolecules
    );

    // General setup
    this.bodySettings = bodySettings;

    // Physics behaviour
    this.time = 0;
    const posOrNeg = Math.random() > 0.5 ? 1 : -1;
    this.directionOfTravel = new Matter.Vector.create(
      posOrNeg * (Math.random() * 0.25),
      1
    );
    Matter.Body.setVelocity(this.matterBody, this.directionOfTravel);
    this.directionOfTravelPerpendicular = Matter.Vector.normalise(
      Matter.Vector.perp(this.directionOfTravel)
    );
    Matter.Vector.normalise(this.directionOfTravelPerpendicular);
    this.constantSpeed = 5;
    this.waveAmplitude = 10;
    this.waveIntensity = 0.05;
    this.hasCollidedWithFloor = false;
    this.hasChangedCollisionMasks = false;

    event.on('updateDetectors', () => {
      this.detectors = this.createDetectors(
        GameData.physics.reactiveSurfaces,
        GameData.physics.controllableMolecules
      );
    });
  }

  /**
   * Matter.js Detectors are needed to access current collisions in the scene. Every lightray has its own detector
   * for collisions. Upon initial spawn of a lightray, only collisions with the floor are considered. After the initial
   * impact the detector switches to detect collisions between the emitted heatray and molecules in the atmosphere.
   *
   * @param surfaces {any[]} The physics-enabled surface objects which the lightrays should interact with.
   * @param molecules {Molecule[]} The physics-enabled molecules in the atmosphere which should interact
   * @returns {detector[]} An array of detectors. One for this lightray and ground objects [0], another for this lightray
   * and molecules in the atmosphere [1].
   *
   * @see Molecule
   * @see Matter.Detector
   */
  createDetectors(surfaces, molecules) {
    const lightAndFloor = [];
    const lightAndMolecules = [];

    lightAndFloor.push(this.matterBody);
    lightAndMolecules.push(this.matterBody);

    surfaces.forEach((surface) => {
      lightAndFloor.push(surface.matterBody);
    });

    molecules.forEach((molecule) => {
      lightAndMolecules.push(molecule.matterBody);
    });

    return [
      Matter.Detector.create({ bodies: lightAndFloor }),
      Matter.Detector.create({ bodies: lightAndMolecules }),
    ];
  }

  /**
   * A check which reads the current collisions of the ground-detector, every frame.
   */
  checkForImpact() {
    const currentCols = Matter.Detector.collisions(this.detectors[0]);

    if (currentCols) {
      currentCols.forEach((collision) => {
        if (
          (collision.bodyA.collisionFilter.category &
            collisionGroups.ADSORBING &&
            collision.bodyB.collisionFilter.category &
              collisionGroups.LIGHT_RAY) ||
          (collision.bodyA.collisionFilter.category &
            collisionGroups.LIGHT_RAY &&
            collision.bodyB.collisionFilter.category &
              collisionGroups.ADSORBING)
        ) {
          this.contactAdsorbing();
        } else if (
          (collision.bodyA.collisionFilter.category &
            collisionGroups.ABSORBING &&
            collision.bodyB.collisionFilter.category &
              collisionGroups.LIGHT_RAY) ||
          (collision.bodyA.collisionFilter.category &
            collisionGroups.LIGHT_RAY &&
            collision.bodyB.collisionFilter.category &
              collisionGroups.ABSORBING)
        ) {
          this.contactAbsorbing();
        }
      });
    }
  }

  /**
   * A check which reads the current collisions of the molecule-detector, every frame.
   */
  checkForMoleculeCollision() {
    const currentCols = Matter.Detector.collisions(this.detectors[1]);

    if (currentCols) {
      currentCols.forEach((collision) => {
        if (
          (collision.bodyA.collisionFilter.category &
            collisionGroups.CONTROLLABLE &&
            collision.bodyB.collisionFilter.category &
              collisionGroups.HEAT_RAY) ||
          (collision.bodyA.collisionFilter.category &
            collisionGroups.HEAT_RAY &&
            collision.bodyB.collisionFilter.category &
              collisionGroups.CONTROLLABLE)
        ) {
          const molecule =
            collision.bodyA.collisionFilter.category &
            collisionGroups.CONTROLLABLE
              ? collision.bodyA
              : collision.bodyB;
          event.emit('moleculeHeatwave', molecule);
          event.emit('changeMagnitude');
          event.emit('destroyLightray', this, true);
        }
      });
    }
  }

  /**
   * A check which fires an event if the lightray has left the viewport. Called every frame.
   */
  checkViewportVisibility() {
    if (!this.inViewport) {
      event.emit('destroyLightray', this, false);
    }
  }

  /**
   * Method which changes the lightrays properties if it hit an ADsorbing surface.
   */
  contactAdsorbing() {
    this.hasCollidedWithFloor = true;
    this.setOpacity(0.85);
    this.setScale(1.25);
    this.reflect();
  }

  /**
   * Method which changes the lightrays properties if it hit an ABsorbing surface.
   */
  contactAbsorbing() {
    this.hasCollidedWithFloor = true;
    this.setOpacity(0.85);
    this.setScale(0.75);
    this.reflect();
  }

  /**
   * Convert the lightray into a heatray and send it back towards the atmosphere.
   */
  reflect() {
    this.tint.parseCSS(GameData.general.colors.bad);
    this.currentTrailColor = GameData.general.colors.bad;
    const posOrNeg = Math.random() > 0.5 ? 1 : -1;
    this.waveIntensity = 0.01;
    Matter.Body.setVelocity(
      this.matterBody,
      new Matter.Vector.create(posOrNeg * (Math.random() * 0.5), -1)
    );
  }

  /**
   * Keeps track and updates the trail of the lightray. Called every frame.
   */
  updateTrail() {
    // Update the state of the trail (add or add + remove).
    const sprite = new Sprite(
      this.pos.x,
      this.pos.y,
      GameData.game.imagePresets.spritesheetSettings
    );
    sprite.floating = true;
    sprite.scale(this.maxTrailScale);
    sprite.tint.parseCSS(this.currentTrailColor);
    sprite.setOpacity(this.getOpacity());
    sprite.addAnimation('image', [GameData.game.imageIds.spritesheet.molecule]);
    sprite.setCurrentAnimation('image');

    if (this.trailElements.length !== this.maxTrailLength) {
      this.trailElements.unshift(sprite);
      game.world.addChild(sprite, this.pos.z);
    } else {
      this.trailElements.unshift(sprite);
      game.world.removeChild(this.trailElements.pop());
      game.world.addChild(sprite, this.pos.z);
    }

    // Apply visual effects to the trail.
    for (let i = 0; i < this.trailElements.length; i++) {
      this.trailElements[i].setOpacity(
        this.getOpacity() - i * (1 / this.maxTrailLength)
      );
    }
  }

  // Called every frame. Sets the constant velocity and calls collision and visibility checks.
  update(dt) {
    this.time += dt;
    const offset2d = Matter.Vector.mult(
      this.directionOfTravelPerpendicular,
      this.waveAmplitude * Math.sin(this.time * this.waveIntensity)
    );
    // Align physic body with visual body (see CirclePHYS) but apply a visual offset to simulate a waveform.
    this.pos.x = this.matterBody.position.x;
    this.pos.y = this.matterBody.position.y;
    this.pos.add(new Vector2d(offset2d.x, offset2d.y));

    // Set the current constant speed.
    Matter.Body.setSpeed(this.matterBody, this.constantSpeed);

    // Update the trail.
    if (this.trailSpaceCounter === this.targetTrailSpacing) {
      this.updateTrail();
      this.trailSpaceCounter = 0;
    } else {
      this.trailSpaceCounter++;
    }

    // Check for any collisions and change the lightwave into a heatwave upon ground collision.
    if (!this.hasCollidedWithFloor) {
      this.checkForImpact();
    } else {
      if (!this.hasChangedCollisionMasks) {
        this.matterBody.collisionFilter.category =
          GameData.physics.bodySettings.heatray.collisionFilter.category;
        this.matterBody.collisionFilter.mask =
          GameData.physics.bodySettings.heatray.collisionFilter.mask;
        this.hasChangedCollisionMasks = true;
      }
      this.checkForMoleculeCollision();
      this.checkViewportVisibility();
    }

    return true;
  }
  onDestroyEvent() {
    super.onDestroyEvent();
    this.trailElements.forEach((sprite) => {
      game.world.removeChild(sprite);
    });
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    event.off('updateDetectors', () => {});
  }
}

export default LightRay;
