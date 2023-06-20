import { Sprite, game, event, Vector2d, ParticleEmitter } from 'melonjs';
import { GameData } from '@/games/coolit/src/resources.js';
import { utils } from '@/games/coolit/src/utils/utils.js';
import Molecule from '@/games/coolit/src/entities/molecule.js';

/**
 * An in-game event where a car passes by and emits a molecule of the type 'carbondioxide'.
 * Moves a sprite from one side of the viewport to the other and spawns a molecule in-between after reaching a certain
 * threshold.
 *
 * @param moleculeType {string} The type of molecule to spawn.
 * @param rightToLeft {boolean} OPTIONAL (Default: false). If this event should move from the right side of the viewport to the left or not.
 *
 * @extends Sprite
 * @see Sprite
 */
export default class EmissionEvent extends Sprite {
  moleculeType = '';
  emitter = null;
  time = 0;
  spawnTime = 1500;
  hasSpawnedMolecule = false;
  threshold = Math.random() * (game.viewport.width - 512) + 256;
  speed = 3;
  newMolecule = null;
  attentionFlash = null;
  rightToLeft = false;

  constructor(moleculeType, rightToLeft = false) {
    super(0, game.viewport.height, {
      image: 'spritesheet',
      framewidth: 256,
      frameheight: 256,
      anchorPoint: new Vector2d(1, 1),
    });
    if (rightToLeft) {
      this.pos.x = game.viewport.width;
    }
    this.addAnimation('image', [GameData.game.imageIds.spritesheet.carSedan]);
    this.setCurrentAnimation('image');
    this.scale(0.5);
    this.floating = true;
    this.alwaysUpdate = true;

    this.moleculeType = moleculeType;
    this.rightToLeft = rightToLeft;

    if (rightToLeft) {
      this.scale(-1, 1);
      this.emitter = new ParticleEmitter(
        this.pos.x + 256 * 0.5 + 5,
        this.pos.y - 5,
        {
          width: 5,
          height: 5,
          tint: '#666',
          angle: utils.math.degToRad(35),
          angleVariation: utils.math.degToRad(5),
          gravity: -0.01,
          totalParticles: 32,
          maxLife: 10,
          speed: 1,
        }
      );
    } else {
      this.emitter = new ParticleEmitter(
        this.pos.x - 256 * 0.5 + 5,
        this.pos.y - 5,
        {
          width: 5,
          height: 5,
          tint: '#666',
          angle: utils.math.degToRad(145),
          angleVariation: utils.math.degToRad(5),
          gravity: -0.01,
          totalParticles: 32,
          maxLife: 10,
          speed: 1,
        }
      );
    }
    this.emitter.floating = true;
    this.emitter.alwaysUpdate = true;
    this.emitter.scale(0.5);
    this.emitter.streamParticles();

    game.world.addChild(this.emitter, GameData.zOrder.background + 5);
    game.world.addChild(this, GameData.zOrder.background + 6);
  }

  update(dt) {
    super.update(dt);
    if (!this.rightToLeft) {
      this.pos.x += this.speed;
      this.emitter.pos.x += this.speed;

      if (this.pos.x >= this.threshold && !this.hasSpawnedMolecule) {
        this.speed = 0;
        this.time += dt;

        if (this.attentionFlash === null) {
          this.attentionFlash = new Sprite(this.pos.x - 28, this.pos.y + 72, {
            image: 'spritesheet',
            framewidth: 256,
            frameheight: 256,
            anchorPoint: new Vector2d(1, 1),
          });
          this.attentionFlash.addAnimation('image', [
            GameData.game.imageIds.spritesheet.important,
          ]);
          this.attentionFlash.setCurrentAnimation('image');
          this.attentionFlash.floating = true;
          game.world.addChild(
            this.attentionFlash,
            GameData.zOrder.background + 7
          );
          // eslint-disable-next-line @typescript-eslint/no-empty-function
          this.attentionFlash.flicker(this.spawnTime, () => {});
        }

        if (this.time >= this.spawnTime) {
          this.newMolecule = new Molecule(
            this.pos.x - 256 * 0.5,
            this.pos.y - 10,
            {
              image: 'spritesheet',
              framewidth: 256,
              frameheight: 256,
            },
            GameData.game.imageIds.spritesheet.molecule,
            GameData.physics.engine.world,
            GameData.physics.bodySettings.unavailable,
            this.moleculeType,
            true
          );
          this.newMolecule.floating = true;
          this.newMolecule.alwaysUpdate = true;
          this.newMolecule.tint.parseCSS(
            GameData.game.molecules[this.moleculeType].tint
          );
          this.newMolecule.setScale(
            GameData.game.molecules[this.moleculeType].maxScale
          );
          this.newMolecule.setScale(0.1, false);
          GameData.physics.controllableMolecules.push(this.newMolecule);
          event.emit('updateDetectors');
          game.world.addChild(this.newMolecule, GameData.zOrder.usables);
          this.hasSpawnedMolecule = true;
          this.speed = 3;
          this.newMolecule.returnToPlayspace(true);
          if (this.attentionFlash !== null) {
            game.world.removeChild(this.attentionFlash);
          }
        }
      }

      if (this.pos.x >= GameData.general.targetResX + this.width) {
        this.emitter.stopStream();
        game.world.removeChild(this.emitter, false);
        game.world.removeChild(this, false);
      }

      return true;
    } else {
      this.pos.x -= this.speed;
      this.emitter.pos.x -= this.speed;

      if (this.pos.x <= this.threshold && !this.hasSpawnedMolecule) {
        this.speed = 0;
        this.time += dt;
        if (this.time >= this.spawnTime) {
          this.newMolecule = new Molecule(
            this.pos.x + 256 * 0.5,
            this.pos.y - 10,
            {
              image: 'spritesheet',
              framewidth: 256,
              frameheight: 256,
            },
            GameData.game.imageIds.spritesheet.molecule,
            GameData.physics.engine.world,
            GameData.physics.bodySettings.unavailable,
            this.moleculeType,
            true
          );
          this.newMolecule.floating = true;
          this.newMolecule.alwaysUpdate = true;
          this.newMolecule.tint.parseCSS(
            GameData.game.molecules[this.moleculeType].tint
          );
          this.newMolecule.setScale(
            GameData.game.molecules[this.moleculeType].maxScale
          );
          this.newMolecule.setScale(0.1, false);
          GameData.physics.controllableMolecules.push(this.newMolecule);
          event.emit('updateDetectors');
          game.world.addChild(this.newMolecule, GameData.zOrder.usables);
          this.hasSpawnedMolecule = true;
          this.speed = 3;
          this.newMolecule.returnToPlayspace(true);
        }
      }

      if (this.pos.x <= 0 - this.width) {
        this.emitter.stopStream();
        game.world.removeChild(this.emitter, false);
        game.world.removeChild(this, false);
      }

      return true;
    }
  }
}
