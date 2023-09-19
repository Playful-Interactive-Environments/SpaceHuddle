<template>
  <particle-container
    v-if="config && particleContainerActive && !disabled"
    :x="x"
    :y="y"
    @render="renderContainer($event)"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as PIXI from 'pixi.js';
import * as PIXIParticles from '@pixi/particle-emitter';
import { until, delay } from '@/utils/wait';
import * as pixiUtil from '@/utils/pixi';

@Options({
  components: {},
  emits: [],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class CustomParticleContainer extends Vue {
  @Prop({ default: 0 }) x!: number;
  @Prop({ default: 0 }) y!: number;
  @Prop({ default: false }) disabled!: boolean;
  @Prop({ default: null }) spriteSheetUrl!: string | null;
  @Prop({ default: null }) config!: PIXIParticles.EmitterConfigV3 | null;
  emitter!: PIXIParticles.Emitter;
  container!: PIXI.ParticleContainer;
  particleContainerActive = true;
  spriteSheet!: PIXI.Spritesheet;
  isSpriteSheetLoaded = false;

  unmounted(): void {
    this.particleContainerActive = false;
    this.destroyEmitter(this.emitter, true, true);
  }

  @Watch('spriteSheetUrl', { immediate: true })
  async onSpriteSheetChanged(spriteSheetUrl: string): Promise<void> {
    this.isSpriteSheetLoaded = false;
    if (spriteSheetUrl) {
      this.spriteSheet = await pixiUtil.loadTexture(spriteSheetUrl);
      this.isSpriteSheetLoaded = true;
    }
  }

  @Watch('config', { immediate: true })
  async onConfigChanged(): Promise<void> {
    this.destroyEmitter(this.emitter, false);
    if (!this.config) return;
    const config = JSON.parse(JSON.stringify(this.config));
    const textureConfig = config.behaviors.find(
      (item) => item.type === 'textureRandom' || item.type === 'animatedSingle'
    );
    if (textureConfig) {
      const isAnimation = textureConfig.type === 'animatedSingle';
      const textureList = !isAnimation
        ? textureConfig.config.textures
        : textureConfig.config.anim.textures;
      for (let i = 0; i < textureList.length; i++) {
        const url = !isAnimation ? textureList[i] : textureList[i].texture;
        if (typeof url === 'string') {
          if (!this.spriteSheetUrl && !config.spriteSheet) {
            if (!isAnimation) textureList[i] = await pixiUtil.loadTexture(url);
            else textureList[i].texture = await pixiUtil.loadTexture(url);
          } else {
            if (this.spriteSheetUrl) await until(this.isSpriteSheetLoaded);
            else if (config.spriteSheet) {
              await this.onSpriteSheetChanged(config.spriteSheet);
              this.isSpriteSheetLoaded = true;
            }
            if (!isAnimation) textureList[i] = this.spriteSheet.textures[url];
            else textureList[i].texture = this.spriteSheet.textures[url];
          }
        }
      }
    }
    this.emitter = new PIXIParticles.Emitter(this.container, config);
    this.emitter.autoUpdate = true;
  }

  renderContainer(container: PIXI.ParticleContainer): void {
    this.container = container;
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
}
</script>

<style lang="scss" scoped></style>
