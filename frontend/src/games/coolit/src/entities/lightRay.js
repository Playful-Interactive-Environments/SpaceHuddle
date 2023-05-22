import { event, Vector2d } from 'melonjs';
import Matter from 'matter-js';
import CirclePHYS from '@/games/coolit/src/entities/circlePhys.js';
import { collisionGroups, GameData } from '@/games/coolit/src/resources.js';

class LightRay extends CirclePHYS {
  // Matter.js
  engine;
  detectors = [];

  // General setup
  bodySettings;

  // Physics behaviour
  time;
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

  // Every LightRay has its own Detector for collisions. LightRays check for collision with the floor at first,
  // then switch to molecule and visibility detection.
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
  checkForImpact() {
    const currentCols = Matter.Detector.collisions(this.detectors[0]);

    if (currentCols) {
      currentCols.forEach((collision) => {
        if (
          collision.bodyA.collisionFilter.category &
            collisionGroups.ADSORBING &&
          collision.bodyB.collisionFilter.category & collisionGroups.LIGHT_RAY
        ) {
          this.contactAdsorbing();
        } else if (
          collision.bodyA.collisionFilter.category &
            collisionGroups.ABSORBING &&
          collision.bodyB.collisionFilter.category & collisionGroups.LIGHT_RAY
        ) {
          this.contactAbsorbing();
        }
      });
    }
  }
  checkForMoleculeCollision() {
    const currentCols = Matter.Detector.collisions(this.detectors[1]);

    if (currentCols) {
      currentCols.forEach((collision) => {
        if (
          (collision.bodyA.collisionFilter.category ===
            collisionGroups.CONTROLLABLE &&
            collision.bodyB.collisionFilter.category ===
              collisionGroups.LIGHT_RAY) ||
          (collision.bodyA.collisionFilter.category ===
            collisionGroups.LIGHT_RAY &&
            collision.bodyB.collisionFilter.category ===
              collisionGroups.CONTROLLABLE)
        ) {
          const molecule =
            collision.bodyA.collisionFilter.category ===
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
  checkViewportVisibility() {
    if (!this.inViewport) {
      event.emit('destroyLightray', this, false);
    }
  }

  // Contact handlers once the LightRay hits the floor for the first time.
  contactAdsorbing() {
    this.hasCollidedWithFloor = true;
    this.setOpacity(0.85);
    this.setScale(1.25);
    this.setImage(GameData.game.imageIds.spritesheet.nitrousoxide);
    const posOrNeg = Math.random() > 0.5 ? 1 : -1;
    this.waveIntensity = 0.01;
    Matter.Body.setVelocity(
      this.matterBody,
      new Matter.Vector.create(posOrNeg * (Math.random() * 0.5), -1)
    );
  }
  contactAbsorbing() {
    this.hasCollidedWithFloor = true;
    this.setOpacity(0.85);
    this.setScale(0.75);
    this.setImage(GameData.game.imageIds.spritesheet.nitrousoxide);
    const posOrNeg = Math.random() > 0.5 ? 1 : -1;
    this.waveIntensity = 0.01;
    Matter.Body.setVelocity(
      this.matterBody,
      new Matter.Vector.create(posOrNeg * (Math.random() * 0.5), -1)
    );
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

    // Check for any collisions and change the lightwave into a heatwave upon ground collision.
    if (!this.hasCollidedWithFloor) {
      this.checkForImpact();
      return true;
    } else {
      if (!this.hasChangedCollisionMasks) {
        Matter.Body.set(this.matterBody, {
          collisionFilter: {
            category: collisionGroups.LIGHT_RAY,
            mask:
              collisionGroups.CONTROLLABLE |
              collisionGroups.ABSORBING |
              collisionGroups.ADSORBING,
          },
        });
        this.hasChangedCollisionMasks = true;
      }
      this.checkForMoleculeCollision();
      this.checkViewportVisibility();
      return true;
    }
  }
  onDestroyEvent() {
    super.onDestroyEvent();
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    event.off('updateDetectors', () => {});
  }
}

export default LightRay;
