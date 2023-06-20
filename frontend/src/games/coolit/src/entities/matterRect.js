import { game, event, Renderable, ParticleEmitter } from 'melonjs';
import Matter from 'matter-js';
import { collisionGroups, GameData } from '@/games/coolit/src/resources.js';
import RectPHYS from '@/games/coolit/src/entities/rectPhys.js';
import { utils } from '@/games/coolit/src/utils/utils.js';

/**
 * Provides a base, physics enabled class to be extended from (based upon melonJS' Renderable instead of Sprite). Collider has rectangle format.
 * Its primary use is the easy implementation of physics collision in the Tiled Map Editor. Classes which inherit this as their
 * superclass should follow that mindset.
 *
 * Similar to the function of RectPhys with the difference being that this one extends from Renderable, thus not having
 * the ability to display an image/sprite and not providing the functionalities that come along with those traits.
 *
 * Necessary custom properties in Tiled are:
 *
 * (string) bodySettingsID = someStringID (GameData.physics.bodySettings)
 *
 * @param x The x-coordinate of this object.
 * @param y The y-coordinate of this object.
 * @param settings If used with Tiled and Object-Pooling: A TMXObject. If used manually: An object containing at least width, height and Matter.Body.settings.
 *
 * @extends Renderable
 * @see Renderable
 * @see RectPhys
 */
export default class MatterRect extends Renderable {
  // A reference to this class' physics body. Instantiated in constructor.
  matterBody;

  // Offset values in case the anchor points of Renderable and Matter.Bodies.rectangle differ.
  offsetX;
  offsetY;

  constructor(x, y, settings) {
    super(x, y, settings.width, settings.height);

    // Create the physics body. Add it to the Matter.js world space.
    this.matterBody = new Matter.Bodies.rectangle(
      x,
      y,
      settings.width,
      settings.height,
      GameData.physics.bodySettings[settings.bodySettingsID]
    );
    Matter.Composite.add(GameData.physics.engine.world, this.matterBody);

    // Update this gameobject even outside of the canvas.
    this.alwaysUpdate = true;

    this.offsetX = this.anchorPoint.x * settings.width;
    this.offsetY = this.anchorPoint.y * settings.height;
  }

  // Called every frame.
  update(dt) {
    super.update(dt);
    if (!this.floating) {
      Matter.Body.setPosition(
        this.matterBody,
        Matter.Vector.create(
          this.pos.x + this.offsetX - game.viewport.pos.x,
          this.pos.y + this.offsetY
        )
      );
    }

    return true;
  }
  // Called when destroying this object.
  onDestroyEvent() {
    super.onDestroyEvent();

    // Remove the physics body from the world as well!
    if (this.matterBody) {
      Matter.Composite.remove(GameData.physics.engine.world, this.matterBody);
    }
  }
}

/**
 * An extension of MatterRect which provides the additional functionality of destroying/removing
 * molecules from the game world when a collision occurs with said molecule. Every MoleculeSink needs a compatible
 * molecule type which it is able to destroy. Supply this molecule type as custom parameter in Tiled.
 *
 * Necessary custom properties in Tiled are:
 *
 * (string) bodySettingsID = someStringID (GameData.physics.bodySettings)
 *
 * (string) moleculeType = someStringID (GameData.game.molecules)
 *
 * @param x The x-coordinate of this object.
 * @param y The y-coordinate of this object.
 * @param settings If used with Tiled and Object-Pooling: A TMXObject. If used manually: An object containing at least width, height and Matter.Body.settings.
 *
 * @extends MatterRect
 * @see MatterRect
 */
export class MoleculeSink extends MatterRect {
  moleculeType = '';
  detector = undefined;
  hasEnteredViewport = false;
  emitter = undefined;

  constructor(x, y, settings) {
    super(x, y, settings);
    GameData.physics.moleculeSinks.push(this);

    this.moleculeType = settings.moleculeType;
    this.detector = this.createDetector();
    this.emitter = new ParticleEmitter(this.centerX, this.centerY, {
      width: settings.width * 0.75,
      height: settings.height * 0.75,
      tint: GameData.game.molecules[settings.moleculeType].tint,
      angle: utils.math.degToRad(145),
      angleVariation: utils.math.degToRad(5),
      gravity: -0.01,
      totalParticles: 32,
      maxLife: 10,
      speed: 1,
    });
    game.world.addChild(this.emitter, GameData.zOrder.background + 5);

    GameData.physics.reactiveSurfaces.push(this);
    event.emit('updateDetectors');

    event.on('updateDetectors', () => {
      this.detector = this.createDetector();
    });
  }

  /**
   * Creates a Matter.Detector only supplying the relevant physics bodies for the current state of the game.
   *
   * @returns { detector } A Detector object which can be used to check for collisions between certain physics bodies.
   * @see Matter.Detector
   */
  createDetector() {
    const bodies = [];
    GameData.physics.controllableMolecules.forEach((molecule) => {
      if (molecule.moleculeType === this.moleculeType) {
        bodies.push(molecule.matterBody);
      }
    });
    bodies.push(this.matterBody);

    return Matter.Detector.create({ bodies: bodies });
  }

  /**
   * If this MoleculeSink is currently visible in the viewport, the detector is utilized to check for any collisions
   * between compatible molecules. If a collision is detected, the affected molecule will be destroyed.
   */
  checkForMoleculeCollision() {
    const currentCols = Matter.Detector.collisions(this.detector);

    if (currentCols) {
      currentCols.forEach((collision) => {
        if (
          (collision.bodyA.collisionFilter.category &
            collisionGroups.CONTROLLABLE &&
            collision.bodyB.collisionFilter.category & collisionGroups.SINK) ||
          (collision.bodyA.collisionFilter.category & collisionGroups.SINK &&
            collision.bodyB.collisionFilter.category &
              collisionGroups.CONTROLLABLE)
        ) {
          const moleculeBody =
            collision.bodyA.collisionFilter.category &
            collisionGroups.CONTROLLABLE
              ? collision.bodyA
              : collision.bodyB;
          if (moleculeBody) {
            moleculeBody.collisionFilter =
              GameData.physics.bodySettings.unavailable.collisionFilter;
            event.emit('despawnMolecule', moleculeBody);
          }
        }
      });
    }
  }

  // Called every frame.
  update(dt) {
    super.update(dt);
    if (!this.hasEnteredViewport && this.inViewport) {
      event.emit('allowDestruction', this.moleculeType);
      this.emitter.streamParticles();
      this.hasEnteredViewport = true;
    }
    if (this.hasEnteredViewport && !this.inViewport) {
      event.emit('restrictDestruction', this.moleculeType);
      this.emitter.stopStream();
      this.hasEnteredViewport = false;
    }

    if (this.hasEnteredViewport) {
      this.checkForMoleculeCollision();
    }

    return true;
  }
  // Called when destroying this object.
  onDestroyEvent() {
    super.onDestroyEvent();
    this.emitter.stopStream();
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    event.off('updateDetectors', () => {});
  }
}

/**
 * An extension of MatterRect which provides the expected rectangular shape collider in addition to the
 * functionality of being reactive towards Lightrays and Heatrays. This trait makes this object very important in
 * level design!
 *
 * Necessary custom properties in Tiled are:
 *
 * (string) bodySettingsID = someStringID (GameData.physics.bodySettings)
 *
 * @param x The x-coordinate of this object.
 * @param y The y-coordinate of this object.
 * @param settings If used with Tiled and Object-Pooling: A TMXObject. If used manually: An object containing at least width, height and Matter.Body.settings.
 *
 * @extends MatterRect
 * @see MatterRect
 */
export class ColliderRect extends MatterRect {
  constructor(x, y, settings) {
    super(x, y, settings);
    GameData.physics.reactiveSurfaces.push(this);
    event.emit('updateDetectors');
  }
}
