import * as PIXI from 'pixi.js';
import * as PIXIParticles from '@pixi/particle-emitter';
import { until, delay } from '@/utils/wait';
import * as pixiUtil from '@/utils/pixi';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ParticlePlayer extends PIXI.ParticleContainer {
  deepCloneConfig = true;
  autoUpdate = true;

  private emitter!: PIXIParticles.Emitter;
  private emitterTimeStamp = 0;
  private particleContainerActive = true;
  private spriteSheet!: PIXI.Spritesheet;
  private isSpriteSheetLoaded = false;
  private textureToken = pixiUtil.createLoadingToken();

  constructor(maxSize = 1500, properties, batchSize = 16384, autoResize = !1) {
    super(maxSize, properties, batchSize, autoResize);
  }

  destroy(options?) {
    this.particleContainerActive = false;
    this.destroyEmitter(this.emitter, true, true);
    pixiUtil.cleanupToken(this.textureToken);
    super.destroy(options);
  }

  //#region properties
  _disabled = false;
  get disabled() {
    return this._disabled;
  }

  set disabled(value) {
    this._disabled = value;
    if (this.emitter && !this.emitter.destroyed) {
      if (!value) {
        this.emitterTimeStamp = Date.now();
        if (this.isValid()) {
          this.emitter.autoUpdate = this.autoUpdate;
          if (!this.autoUpdate) {
            delay(100).then(() => {
              if (!this.emitter.destroyed) this.emitter.emitNow();
            });
          }
        }
      } else {
        this.emitter.autoUpdate = false;
        this.emitter.cleanup();
      }
    }
  }

  _spriteSheetUrl: string | null = null;
  get spriteSheetUrl() {
    return this._spriteSheetUrl;
  }

  set spriteSheetUrl(value) {
    this._spriteSheetUrl = value;
    this.loadSpriteSheetUrl(value);
  }

  _config: PIXIParticles.EmitterConfigV3 | null = null;
  get config() {
    return this._config;
  }

  set config(value) {
    if (value && !Object.hasOwn(value, 'frequency')) {
      value.frequency = this.frequency;
    } else if (value) this._frequency = value.frequency;
    this._config = value;
    this.loadConfig();
  }

  _frequency = 0;
  get frequency() {
    //if (this.emitter) return this.emitter.frequency;
    //if (this.config) return this.config.frequency;
    return this._frequency;
  }

  set frequency(value) {
    this._frequency = value;
    if (this.emitter) {
      this.emitter.frequency = value;
    }
  }

  _defaultTexture: PIXI.Texture | PIXI.Texture[] | null = null;
  get defaultTexture() {
    return this._defaultTexture;
  }

  set defaultTexture(value) {
    const initTexture = !this._defaultTexture && !!value;
    this._defaultTexture = value;
    if (initTexture && this.config) this.loadConfig();
  }
  //#endergeion porperties

  //#region load / destroy
  async loadSpriteSheetUrl(spriteSheetUrl: string | null): Promise<void> {
    this.isSpriteSheetLoaded = false;
    if (spriteSheetUrl) {
      this.spriteSheet = await pixiUtil.loadTexture(
        spriteSheetUrl,
        this.textureToken
      );
      this.isSpriteSheetLoaded = true;
    }
  }

  async loadConfig(): Promise<void> {
    this.destroyEmitter(this.emitter, !this.autoUpdate);
    if (!this.config) return;
    let config = this.deepCloneConfig
      ? structuredClone(this.config) // JSON.parse(JSON.stringify(this.config))
      : { ...this.config };
    if (!this.deepCloneConfig) {
      config.behaviors = [...config.behaviors];
    }
    const textureConfig = config.behaviors.find(
      (item) =>
        item.type === 'textureRandom' ||
        item.type === 'textureSingle' ||
        item.type === 'animatedSingle'
    );
    let hasTexture = !!textureConfig;
    if (textureConfig) {
      const isAnimation = textureConfig.type === 'animatedSingle';
      const isSingleTexture = textureConfig.type === 'textureSingle';
      const textureList = isAnimation
        ? textureConfig.config.anim.textures
        : isSingleTexture
        ? [textureConfig.config.texture]
        : textureConfig.config.textures;
      for (let i = 0; i < textureList.length; i++) {
        const url = !isAnimation ? textureList[i] : textureList[i].texture;
        if (typeof url === 'string') {
          if (!this.spriteSheetUrl && !config.spriteSheet) {
            const texture = await pixiUtil.loadTexture(url, this.textureToken);
            if (!isAnimation) textureList[i] = texture;
            else textureList[i].texture = texture;
          } else {
            if (this.spriteSheetUrl)
              await until(() => this.isSpriteSheetLoaded);
            else if (config.spriteSheet && !this.spriteSheet) {
              await this.loadSpriteSheetUrl(config.spriteSheet);
              this.isSpriteSheetLoaded = true;
            }
            if (!isAnimation) textureList[i] = this.spriteSheet.textures[url];
            else textureList[i].texture = this.spriteSheet.textures[url];
          }
        }
      }
      if (isSingleTexture) textureConfig.config.texture = textureList[0];
    } else {
      if (!this.deepCloneConfig) {
        config = structuredClone(this.config);
      }
      if (this.defaultTexture) {
        await delay(100);
        if (Array.isArray(this.defaultTexture)) {
          config.behaviors.push({
            type: 'textureRandom',
            config: {
              textures: [...this.defaultTexture],
            },
          });
        } else {
          config.behaviors.push({
            type: 'textureSingle',
            config: {
              texture: { ...this.defaultTexture },
            },
          });
        }
        hasTexture = true;
      }
    }
    if (hasTexture && this.particleContainerActive) {
      this.emitter = new PIXIParticles.Emitter(this as any, config);
      if (!this.disabled) {
        this.emitterTimeStamp = Date.now();
        this.emitter.autoUpdate = this.autoUpdate;
        if (!this.autoUpdate) {
          await delay(100);
          if (!this.emitter.destroyed) this.emitter.emitNow();
        }
      }
    }
  }

  render(renderer) {
    super.render(renderer);
  }

  destroyQueue: PIXIParticles.Emitter[] = [];
  async destroyEmitter(
    emitter: PIXIParticles.Emitter,
    immediate = true,
    destroyAll = false
  ): Promise<void> {
    const manipulateEmitter = (
      manipulation: (emitter: PIXIParticles.Emitter) => void
    ): void => {
      if (destroyAll) {
        for (const item of this.destroyQueue) {
          manipulation(item);
        }
      } else {
        manipulation(emitter);
      }
    };

    if (emitter) {
      this.destroyQueue.push(emitter);
      manipulateEmitter(
        (emitter: PIXIParticles.Emitter) => (emitter.emit = false)
      );
      if (immediate) {
        manipulateEmitter((emitter: PIXIParticles.Emitter) => {
          emitter.autoUpdate = false;
          emitter.cleanup();
        });
        await delay(1000);
      } else {
        await until(
          () => emitter.particleCount === 0 || !this.particleContainerActive
        );

        let delayTime = 0;
        manipulateEmitter((emitter: PIXIParticles.Emitter) => {
          emitter.autoUpdate = false;
          if (emitter.particleCount > 0) {
            emitter.cleanup();
            delayTime = 1000;
          }
        });
        await delay(delayTime);
      }
      const index = this.destroyQueue.indexOf(emitter);
      if (index >= 0) {
        manipulateEmitter((emitter: PIXIParticles.Emitter) => {
          if (emitter && !emitter.destroyed) {
            if (emitter.particleCount === 0) (emitter as any)._poolFirst = null;
            emitter.destroy();
          }
        });
        if (destroyAll) {
          this.destroyQueue = [];
        } else {
          this.destroyQueue.splice(index, 1);
        }
      }
    }
  }

  isValid(): boolean {
    if (this.emitter) {
      const singleTextureConfig = this.emitter.getBehavior(
        'textureSingle'
      ) as any;
      if (singleTextureConfig) {
        return (
          !!singleTextureConfig.texture &&
          !!singleTextureConfig.texture.baseTexture &&
          singleTextureConfig.texture.baseTexture.valid
        );
      }
      const randomTextureConfig = this.emitter.getBehavior(
        'textureRandom'
      ) as any;
      if (randomTextureConfig) {
        return (
          randomTextureConfig.textures.length > 0 &&
          randomTextureConfig.textures[0].baseTexture &&
          randomTextureConfig.textures[0].baseTexture.valid
        );
      }
      const singleAnimationConfig = this.emitter.getBehavior(
        'animatedSingle'
      ) as any;
      if (singleAnimationConfig) {
        return (
          singleAnimationConfig.anim.textures.length > 0 &&
          singleAnimationConfig.anim.textures[0].baseTexture &&
          singleAnimationConfig.anim.textures[0].baseTexture.valid
        );
      }
    }
    return false;
  }
  //#endregion load / destroy
}
