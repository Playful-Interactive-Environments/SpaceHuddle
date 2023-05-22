import { event, game, Sprite } from 'melonjs';
import Matter from 'matter-js';
import { collisionGroups, GameData } from '@/games/coolit/src/resources.js';
import CirclePHYS from '@/games/coolit/src/entities/circlePhys.js';
import HeatWave from '@/games/coolit/src/entities/heatWave.js';

export default class Molecule extends CirclePHYS {
  positiveDirection = true;
  velocity;
  magnitude = 0;
  impulse = false;
  impulseMagnitude = this.magnitude + 0.2;
  impulseTicks = 20;
  time = 0;
  glow;
  moleculeType;
  normalMask;
  destructibleMask =
    collisionGroups.CONTROLLABLE |
    collisionGroups.NON_CONTROLLABLE |
    collisionGroups.WORLD_BORDER |
    collisionGroups.SINK |
    collisionGroups.LIGHT_RAY;
  destructiveEntityCount = 0;

  constructor(x, y, settings, imageID, world, bodySettings, type) {
    super(x, y, settings, imageID, world, bodySettings);
    this.createRandomVelocity();
    this.createGlowElement(x, y);
    this.moleculeType = type;
    this.normalMask = this.matterBody.collisionFilter.mask;
    event.on('moleculeHeatwave', (matterBody) => {
      if (this.matterBody === matterBody) {
        new HeatWave(this.matterBody.position.x, this.matterBody.position.y);
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
          this.matterBody.collisionFilter.mask = this.destructibleMask;
        }
      }
    });
    event.on('restrictDestruction', (moleculeType) => {
      if (this.moleculeType === moleculeType) {
        if (this.destructiveEntityCount > 1) {
          this.destructiveEntityCount--;
        } else {
          this.destructiveEntityCount--;
          this.matterBody.collisionFilter.mask = this.normalMask;
        }
      }
    });
    if (type !== 'other') {
      event.on('destroyMolecule', (bodyReference) => {
        if (this.matterBody === bodyReference) {
          game.world.removeChild(this, false);
          GameData.physics.controllableMolecules =
            GameData.physics.controllableMolecules.filter((obj) => {
              return obj !== this;
            });

          event.emit('updateDetectors');
        }
      });
    }
  }

  // Creates and saves a random base velocity which is used to make the molecule wiggle.
  createRandomVelocity() {
    this.velocity = Matter.Vector.create(
      Math.random() * (Math.round(Math.random()) ? 1 : -1),
      Math.random() * (Math.round(Math.random()) ? 1 : -1)
    );
  }
  // Adds the glow sprite with opacity 0 above this molecule. Opacity will be changed upon heat differences.
  createGlowElement(posX, posY) {
    this.glow = new Sprite(posX, posY, {
      image: 'spritesheet',
      framewidth: 256,
      frameheight: 256,
    });
    this.glow.addAnimation('image', [
      GameData.game.imageIds.spritesheet.heatwave,
    ]);
    this.glow.setCurrentAnimation('image');
    this.glow.floating = true;
    this.glow.setOpacity(0);
    game.world.addChild(this.glow, GameData.zOrder.usables + 1);
  }

  // If called, adds a one-frame force (heat reaction) to the molecule in one of two directions.
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
  // Applies a heat reaction to this molecule and handles the heat-glow once an impulse was triggered. The reaction's strength and direction depends on the current magnitude and the base velocity vector.
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

  setScale(scale) {
    super.setScale(scale);
    this.glow.scale(scale);
  }

  // Updates a timer which triggers the application of heat reactions.
  update(dt) {
    super.update(dt);
    this.glow.pos.x = this.pos.x;
    this.glow.pos.y = this.pos.y;
    if (this.time === 0) {
      this.applyHeatReaction();
      this.time += dt;
    } else if (this.time <= 100) {
      this.time += dt;
    } else {
      this.time = 0;
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
  }
}
