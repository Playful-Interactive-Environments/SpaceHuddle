<template>
  <Application
    ref="pixi"
    :width="width"
    :height="height"
    :backgroundColor="backgroundColor"
  >
    <slot></slot>
    <container v-if="texture">
      <sprite
        v-if="!isAnimation && !Array.isArray(texture)"
        :texture="texture"
        :width="objectWidth * scaleFactor"
        :height="objectHeight * scaleFactor"
        :tint="tint"
        :anchor="0.5"
        :x="width / 2"
        :y="height / 2"
      />
      <animated-sprite
        v-else-if="isAnimationLoaded"
        :textures="animationSequence"
        :animation-speed="animationSpeed"
        :width="objectWidth * scaleFactor"
        :height="objectHeight * scaleFactor"
        :anchor="0.5"
        :tint="tint"
        :x="width / 2"
        :y="height / 2"
        @render="renderAnimation"
        @frame-change="animationFrameChanged"
      />
    </container>
    <slot name="overlay"></slot>
  </Application>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Application } from 'vue3-pixi';
import * as PIXI from 'pixi.js';
import * as pixiUtil from '@/utils/pixi';

@Options({
  components: { Application },
  emits: ['update:renderer', 'initRenderer'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class SpriteCanvas extends Vue {
  @Prop({ default: 100 }) readonly width!: number;
  @Prop({ default: 100 }) readonly height!: number;
  @Prop({ default: 1 }) readonly scaleFactor!: number;
  @Prop({ default: 1 }) readonly aspectRation!: number;
  @Prop({ default: '#f4f4f4' }) readonly backgroundColor!: string;
  @Prop({ default: '#ffffff' }) tint!: string;
  @Prop() texture!: string | PIXI.Texture | PIXI.Texture[] | string[];
  @Prop({ default: 0.1 }) animationSpeed!: number;
  @Prop() renderer!: PIXI.Renderer;
  spritesheet!: PIXI.Spritesheet;
  app: PIXI.Application | null = null;
  loadedTexture = '';
  textureToken = pixiUtil.createLoadingToken();

  isAnimation = false;
  isAnimationLoaded = false;
  animationSequence: (string | PIXI.Texture)[] = [];

  get aspect(): number {
    if (this.texture instanceof PIXI.Texture)
      return pixiUtil.getTextureAspect(this.texture);
    if (
      this.animationSequence.length > 0 &&
      this.animationSequence[0] instanceof PIXI.Texture
    ) {
      return pixiUtil.getTextureAspect(
        this.animationSequence[this.activeFrame] as PIXI.Texture
      );
    }
    return this.aspectRation;
  }

  mounted(): void {
    setTimeout(async () => {
      const pixi = this.$refs.pixi as typeof Application;
      if (pixi) {
        this.app = pixi.app;
        if (pixi.app) {
          this.$emit('update:renderer', pixi.app.renderer);
          this.$emit('initRenderer', pixi.app.renderer);
        }
      }
    }, 100);
  }

  @Watch('texture', { immediate: true })
  onTextureChanged(): void {
    if (this.loadedTexture === this.texture) return;
    this.unloadAnimation();
    if (this.texture) {
      if (Array.isArray(this.texture)) {
        this.isAnimation = true;
        this.animationSequence = this.texture;
        this.isAnimationLoaded = true;
        this.startAnimation();
      } else if (
        typeof this.texture === 'string' &&
        this.texture.endsWith('.json')
      ) {
        this.isAnimation = true;
        this.loadedTexture = this.texture;
        pixiUtil
          .loadTexture(this.texture, this.eventBus, this.textureToken)
          .then((sheet) => {
            this.spritesheet = sheet;
            this.animationSequence = Object.values(sheet.textures);
            this.isAnimationLoaded = true;
            this.startAnimation();
          });
      } else {
        this.isAnimationLoaded = false;
        this.animationSequence = [];
        this.isAnimation = false;
      }
    }
  }

  get objectWidthUnScaled(): number {
    let width = this.width;
    if (this.aspect <= 1) width = this.height * this.aspect;
    return width;
  }

  get objectHeightUnScaled(): number {
    let height = this.height;
    if (this.aspect > 1) height = this.width / this.aspect;
    return height;
  }

  get objectWidth(): number {
    const width = this.objectWidthUnScaled;
    const height = this.objectHeightUnScaled;
    if (height <= this.height && width <= this.width) return width;
    if (height > this.height) {
      const resizeFactor = this.height / height;
      return width * resizeFactor;
    }
    return this.width;
  }

  get objectHeight(): number {
    const width = this.objectWidthUnScaled;
    const height = this.objectHeightUnScaled;
    if (width <= this.width && height <= this.height) return height;
    if (width > this.width) {
      const resizeFactor = this.width / width;
      return height * resizeFactor;
    }
    return this.height;
  }

  unmounted(): void {
    this.unloadAnimation();
    pixiUtil.cleanupToken(this.textureToken);
  }

  unloadAnimation(): void {
    if (this.animatedSprite) {
      this.animatedSprite.stop();
      this.animatedSprite = null;
    }
    if (this.loadedTexture) {
      this.isAnimationLoaded = false;
      this.animationSequence = [];
      this.isAnimation = false;
      this.loadedTexture = '';
      this.activeFrame = 0;
    }
  }

  startAnimation(): void {
    if (this.animatedSprite && this.isAnimationLoaded) {
      this.animatedSprite.play();
    }
  }

  animatedSprite: PIXI.AnimatedSprite | null = null;
  renderAnimation(el: PIXI.AnimatedSprite): void {
    this.animatedSprite = el;
    this.startAnimation();
  }

  activeFrame = 0;
  animationFrameChanged(index: number): void {
    this.activeFrame = index;
    if (
      this.animatedSprite &&
      (this.animatedSprite.width !== this.objectWidth ||
        this.animatedSprite.height !== this.objectHeight)
    ) {
      this.animatedSprite.width = this.objectWidth;
      this.animatedSprite.height = this.objectHeight;
    }
  }
}
</script>

<style scoped lang="scss"></style>
