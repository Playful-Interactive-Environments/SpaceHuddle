import { game, event, Sprite, ParticleEmitter } from 'melonjs';
import Matter from 'matter-js';
import { collisionGroups, GameData } from '@/games/coolit/src/resources';

export default class RectPHYS extends Sprite {
  matterBody;
  matterWorld;
  bodySettings;
  offsetX;
  offsetY;

  constructor(
    x,
    y,
    settings,
    imageID = undefined,
    world = undefined,
    bodySettings = undefined
  ) {
    super(x, y, settings);

    this.alwaysUpdate = true;

    if (imageID !== undefined) {
      this.setImage(imageID);
    }
    if (bodySettings !== undefined) {
      this.setBodySettings(bodySettings);
    }
    if (world !== undefined) {
      this.setPhysicsWorld(world);
      this.createPhysicsBody();
    }
  }

  createPhysicsBody(offsetX = 0, offsetY = 0) {
    this.offsetX = offsetX;
    this.offsetY = offsetY;

    if (this.matterBody) {
      Matter.Composite.remove(this.matterWorld, this.matterBody);
    }

    if (this.bodySettings === undefined) {
      this.bodySettings = {};
    }
    this.matterBody = new Matter.Bodies.rectangle(
      this.pos.x + this.offsetX * this.width,
      this.pos.y + this.offsetY * this.height,
      this.width,
      this.height,
      this.bodySettings
    );

    Matter.Composite.add(this.matterWorld, this.matterBody);
  }

  setImage(imageID) {
    this.addAnimation(imageID.toString(), [imageID]);
    this.setCurrentAnimation(imageID.toString());
  }

  setPhysicsWorld(world) {
    this.matterWorld = world;
  }

  setBodySettings(settings) {
    this.bodySettings = settings;
  }

  setScaleUniform(scale) {
    Matter.Body.scale(this.matterBody, scale, scale);
    this.scale(scale, scale);
  }
  setScale(scaleX, scaleY) {
    Matter.Body.scale(this.matterBody, scaleX, scaleY);
    this.scale(scaleX, scaleY);
  }

  update(dt) {
    super.update(dt);
    Matter.Body.setPosition(
      this.matterBody,
      Matter.Vector.create(
        this.pos.x + this.offsetX * this.width,
        this.pos.y + this.offsetY * this.height
      )
    );
    return true;
  }
}

export class Pavement extends RectPHYS {
  constructor(x, y, settings) {
    super(x, y, settings);
    this.init();
  }

  init() {
    this.setImage(GameData.game.imageIds.spritesheet.pavement);
    this.setPhysicsWorld(GameData.physics.engine.world);
    const bodySettings = {
      isStatic: true,
      collisionFilter: {
        category: collisionGroups.ABSORBING,
        mask: collisionGroups.LIGHT_RAY,
      },
    };
    this.setBodySettings(bodySettings);
    this.createPhysicsBody(0.5, 0.5);
    GameData.physics.reactiveSurfaces.push(this);
  }

  update(dt) {
    super.update(dt);
    Matter.Body.setPosition(
      this.matterBody,
      new Matter.Vector.create(
        this.pos.x + this.offsetX * this.width - game.viewport.pos.x,
        this.matterBody.position.y
      )
    );
    return true;
  }
}

export class Skyscraper extends RectPHYS {
  constructor(x, y, settings) {
    super(x, y, settings);
    this.init();
  }

  init() {
    this.setImage(GameData.game.imageIds.spritesheet.skyscraperblank);
    this.setPhysicsWorld(GameData.physics.engine.world);
    const bodySettings = {
      isStatic: true,
      collisionFilter: {
        category: collisionGroups.ADSORBING,
        mask: collisionGroups.LIGHT_RAY,
      },
    };
    this.setBodySettings(bodySettings);
    this.createPhysicsBody(0.5, 0.5);
    GameData.physics.reactiveSurfaces.push(this);
  }

  update(dt) {
    super.update(dt);
    Matter.Body.setPosition(
      this.matterBody,
      new Matter.Vector.create(
        this.pos.x + this.offsetX * this.width - game.viewport.pos.x,
        this.matterBody.position.y
      )
    );
    return true;
  }
}

export class SkyscraperGreenRoof extends RectPHYS {
  constructor(x, y, settings) {
    super(x, y, settings);
    this.init();
  }

  init() {
    this.setImage(GameData.game.imageIds.spritesheet.skyscrapergreenroof);
    this.setPhysicsWorld(GameData.physics.engine.world);
    const bodySettings = {
      isStatic: true,
      collisionFilter: {
        category: collisionGroups.ADSORBING,
        mask: collisionGroups.LIGHT_RAY,
      },
    };
    this.setBodySettings(bodySettings);
    this.createPhysicsBody(0.5, 0.5);
    GameData.physics.reactiveSurfaces.push(this);
  }

  update(dt) {
    super.update(dt);
    Matter.Body.setPosition(
      this.matterBody,
      new Matter.Vector.create(
        this.pos.x + this.offsetX * this.width - game.viewport.pos.x,
        this.matterBody.position.y
      )
    );
    return true;
  }
}

export class StorefrontBright extends RectPHYS {
  constructor(x, y, settings) {
    super(x, y, settings);
    this.init();
  }

  init() {
    this.setImage(GameData.game.imageIds.spritesheet.housewindowfront);
    this.setPhysicsWorld(GameData.physics.engine.world);
    const bodySettings = {
      isStatic: true,
      collisionFilter: {
        category: collisionGroups.ADSORBING,
        mask: collisionGroups.LIGHT_RAY,
      },
    };
    this.setBodySettings(bodySettings);
    this.createPhysicsBody(0.5, 0.5);
    GameData.physics.reactiveSurfaces.push(this);
  }

  update(dt) {
    super.update(dt);
    Matter.Body.setPosition(
      this.matterBody,
      new Matter.Vector.create(
        this.pos.x + this.offsetX * this.width - game.viewport.pos.x,
        this.matterBody.position.y
      )
    );
    return true;
  }
}

export class StorefrontDark extends RectPHYS {
  constructor(x, y, settings) {
    super(x, y, settings);
    this.init();
  }

  init() {
    this.setImage(GameData.game.imageIds.spritesheet.housestorefront);
    this.setPhysicsWorld(GameData.physics.engine.world);
    const bodySettings = {
      isStatic: true,
      collisionFilter: {
        category: collisionGroups.ABSORBING,
        mask: collisionGroups.LIGHT_RAY,
      },
    };
    this.setBodySettings(bodySettings);
    this.createPhysicsBody(0.5, 0.5);
    GameData.physics.reactiveSurfaces.push(this);
  }

  update(dt) {
    super.update(dt);
    Matter.Body.setPosition(
      this.matterBody,
      new Matter.Vector.create(
        this.pos.x + this.offsetX * this.width - game.viewport.pos.x,
        this.matterBody.position.y
      )
    );
    return true;
  }
}

export class BuildingRed extends RectPHYS {
  constructor(x, y, settings) {
    super(x, y, settings);
    this.init();
  }

  init() {
    this.setImage(GameData.game.imageIds.spritesheet.housered);
    this.setPhysicsWorld(GameData.physics.engine.world);
    const bodySettings = {
      isStatic: true,
      collisionFilter: {
        category: collisionGroups.ABSORBING,
        mask: collisionGroups.LIGHT_RAY,
      },
    };
    this.setBodySettings(bodySettings);
    this.createPhysicsBody(0.5, 0.5);
    GameData.physics.reactiveSurfaces.push(this);
  }

  update(dt) {
    super.update(dt);
    Matter.Body.setPosition(
      this.matterBody,
      new Matter.Vector.create(
        this.pos.x + this.offsetX * this.width - game.viewport.pos.x,
        this.matterBody.position.y
      )
    );
    return true;
  }
}

export class BuildingBlue extends RectPHYS {
  constructor(x, y, settings) {
    super(x, y, settings);
    this.init();
  }

  init() {
    this.setImage(GameData.game.imageIds.spritesheet.housedark);
    this.setPhysicsWorld(GameData.physics.engine.world);
    const bodySettings = {
      isStatic: true,
      collisionFilter: {
        category: collisionGroups.ABSORBING,
        mask: collisionGroups.LIGHT_RAY,
      },
    };
    this.setBodySettings(bodySettings);
    this.createPhysicsBody(0.5, 0.5);
    GameData.physics.reactiveSurfaces.push(this);
  }

  update(dt) {
    super.update(dt);
    Matter.Body.setPosition(
      this.matterBody,
      new Matter.Vector.create(
        this.pos.x + this.offsetX * this.width - game.viewport.pos.x,
        this.matterBody.position.y
      )
    );
    return true;
  }
}

export class BuildingBeige extends RectPHYS {
  constructor(x, y, settings) {
    super(x, y, settings);
    this.init();
  }

  init() {
    this.setImage(GameData.game.imageIds.spritesheet.housebeige);
    this.setPhysicsWorld(GameData.physics.engine.world);
    const bodySettings = {
      isStatic: true,
      collisionFilter: {
        category: collisionGroups.ADSORBING,
        mask: collisionGroups.LIGHT_RAY,
      },
    };
    this.setBodySettings(bodySettings);
    this.createPhysicsBody(0.5, 0.5);
    GameData.physics.reactiveSurfaces.push(this);
  }

  update(dt) {
    super.update(dt);
    Matter.Body.setPosition(
      this.matterBody,
      new Matter.Vector.create(
        this.pos.x + this.offsetX * this.width - game.viewport.pos.x,
        this.matterBody.position.y
      )
    );
    return true;
  }
}

export class BuildingApartments extends RectPHYS {
  constructor(x, y, settings) {
    super(x, y, settings);
    this.init();
  }

  init() {
    this.setImage(GameData.game.imageIds.spritesheet.housebalconies);
    this.setPhysicsWorld(GameData.physics.engine.world);
    const bodySettings = {
      isStatic: true,
      collisionFilter: {
        category: collisionGroups.ABSORBING,
        mask: collisionGroups.LIGHT_RAY,
      },
    };
    this.setBodySettings(bodySettings);
    this.createPhysicsBody(0.5, 0.5);
    GameData.physics.reactiveSurfaces.push(this);
  }

  update(dt) {
    super.update(dt);
    Matter.Body.setPosition(
      this.matterBody,
      new Matter.Vector.create(
        this.pos.x + this.offsetX * this.width - game.viewport.pos.x,
        this.matterBody.position.y
      )
    );
    return true;
  }
}

export class CarVan extends RectPHYS {
  constructor(x, y, settings) {
    super(x, y, settings);
    this.init();
  }

  init() {
    this.setImage(GameData.game.imageIds.spritesheet.carvan);
    this.setPhysicsWorld(GameData.physics.engine.world);
    const bodySettings = {
      isStatic: true,
      collisionFilter: {
        category: collisionGroups.ADSORBING,
        mask: collisionGroups.LIGHT_RAY,
      },
    };
    this.setBodySettings(bodySettings);
    this.createPhysicsBody(0.5, 0.5);
    GameData.physics.reactiveSurfaces.push(this);
  }

  update(dt) {
    super.update(dt);
    Matter.Body.setPosition(
      this.matterBody,
      new Matter.Vector.create(
        this.pos.x + this.offsetX * this.width - game.viewport.pos.x,
        this.matterBody.position.y
      )
    );
    return true;
  }
}

export class CarSedan extends RectPHYS {
  constructor(x, y, settings) {
    super(x, y, settings);
    this.init();
  }

  init() {
    this.setImage(GameData.game.imageIds.spritesheet.carsedan);
    this.setPhysicsWorld(GameData.physics.engine.world);
    const bodySettings = {
      isStatic: true,
      collisionFilter: {
        category: collisionGroups.ABSORBING,
        mask: collisionGroups.LIGHT_RAY,
      },
    };
    this.setBodySettings(bodySettings);
    this.createPhysicsBody(0.5, 0.5);
    GameData.physics.reactiveSurfaces.push(this);
  }

  update(dt) {
    super.update(dt);
    Matter.Body.setPosition(
      this.matterBody,
      new Matter.Vector.create(
        this.pos.x + this.offsetX * this.width - game.viewport.pos.x,
        this.matterBody.position.y
      )
    );
    return true;
  }
}

export class CO2Sink extends RectPHYS {
  moleculeType = 'carbondioxide';
  detector = this.createDetector();
  hasEnteredViewport = false;
  emitter = new ParticleEmitter(this.centerX, this.centerY, {
    width: this.width / 2,
    height: this.height / 2,
    tint: '#8F0',
    angle: Math.PI / 2,
    angleVariation: Math.PI * 2,
    minRotation: 0,
    maxRotation: Math.PI,
    totalParticles: 16,
    maxLife: 20,
    speed: 0.00001,
  });

  constructor(x, y, settings) {
    super(x, y, settings);
    this.init();
    event.on('updateDetectors', () => {
      this.detector = this.createDetector();
    });
  }

  init() {
    this.setImage(GameData.game.imageIds.spritesheet.co2sink);
    this.setPhysicsWorld(GameData.physics.engine.world);
    const bodySettings = {
      isStatic: true,
      collisionFilter: {
        category: collisionGroups.ABSORBING | collisionGroups.SINK,
        mask: collisionGroups.CONTROLLABLE | collisionGroups.LIGHT_RAY,
      },
    };
    this.setBodySettings(bodySettings);
    this.createPhysicsBody(0.5, 0.5);
    GameData.physics.reactiveSurfaces.push(this);
    GameData.physics.moleculeSinks.push(this);
    game.world.addChild(this.emitter, GameData.zOrder.background + 5);
  }

  createDetector() {
    const detectorBodies = [];

    GameData.physics.controllableMolecules.forEach((molecule) => {
      if (molecule.moleculeType === this.moleculeType) {
        detectorBodies.push(molecule.matterBody);
      }
    });
    GameData.physics.moleculeSinks.forEach((sink) => {
      if (sink.moleculeType === this.moleculeType) {
        detectorBodies.push(sink.matterBody);
      }
    });

    return Matter.Detector.create({ bodies: detectorBodies });
  }

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
          const molecule =
            collision.bodyA.collisionFilter.category &
            collisionGroups.CONTROLLABLE
              ? collision.bodyA
              : collision.bodyB;
          if (molecule) {
            event.emit('destroyMolecule', molecule);
            Matter.Composite.remove(GameData.physics.engine.world, molecule);
          }
        }
      });
    }
  }

  update(dt) {
    super.update(dt);

    Matter.Body.setPosition(
      this.matterBody,
      new Matter.Vector.create(
        this.pos.x + this.offsetX * this.width - game.viewport.pos.x,
        this.matterBody.position.y
      )
    );

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

  onDestroyEvent() {
    super.onDestroyEvent();
    this.emitter.stopStream();
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    event.off('updateDetectors', () => {});
  }
}
