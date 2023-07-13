<template>
  <Application
    ref="pixi"
    :width="width"
    :height="height"
    :backgroundColor="backgroundColor"
  >
    <container>
      <sprite
        v-if="!isAnimation"
        :texture="texture"
        :width="objectWidth"
        :height="objectHeight"
        :anchor="0.5"
        :x="width / 2"
        :y="height / 2"
      />
      <animated-sprite
        v-else-if="isAnimationLoaded"
        :textures="animationSequence"
        playing
        :animation-speed="animationSpeed"
        :width="objectWidth"
        :height="objectHeight"
        :anchor="0.5"
        :x="width / 2"
        :y="height / 2"
      />
    </container>
  </Application>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Application } from 'vue3-pixi';
import * as PIXI from 'pixi.js';

@Options({
  components: { Application },
  emits: [],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class SpriteCanvas extends Vue {
  @Prop({ default: 100 }) readonly width!: number;
  @Prop({ default: 100 }) readonly height!: number;
  @Prop({ default: 1 }) readonly aspectRation!: number;
  @Prop({ default: '#f4f4f4' }) readonly backgroundColor!: string;
  @Prop() texture!: string | PIXI.Texture;
  @Prop({ default: 0.1 }) animationSpeed!: number;
  spritesheet!: PIXI.Spritesheet;

  isAnimation = false;
  isAnimationLoaded = false;
  animationSequence: string[] = [];

  @Watch('texture', { immediate: true })
  onTextureChanged(): void {
    if (this.texture) {
      if (typeof this.texture === 'string' && this.texture.endsWith('.json')) {
        this.isAnimation = true;
        PIXI.Assets.load(this.texture).then((sheet) => {
          this.spritesheet = sheet;
          console.log(this.spritesheet.data.frames);
          this.isAnimationLoaded = true;
        });
      }
    }
  }

  get objectWidth(): number {
    if (this.aspectRation > 1) return this.width;
    return this.height * this.aspectRation;
  }

  get objectHeight(): number {
    if (this.aspectRation < 1) return this.height;
    return this.width / this.aspectRation;
  }
}
</script>

<style scoped lang="scss"></style>
