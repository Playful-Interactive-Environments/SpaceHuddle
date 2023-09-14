<template>
  <sprite
    ref="customSprite"
    :texture="texture"
    :anchor="anchor"
    :width="displayWidth"
    :height="displayHeight"
    :tint="tint"
    :x="displayX"
    :y="displayY"
  >
    <slot></slot>
  </sprite>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { ObjectSpace } from '@/types/enum/ObjectSpace';
import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import * as PIXI from 'pixi.js';
import { CustomObject } from '@/types/game/CustomObject';
import { EventType } from '@/types/enum/EventType';
import { OutlineFilter } from '@pixi/filter-outline';

@Options({
  components: {},
  emits: [],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class CustomSprite extends Vue implements CustomObject {
  @Prop({ default: 0 }) x!: number;
  @Prop({ default: 0 }) y!: number;
  @Prop({ default: ObjectSpace.Absolute }) objectSpace!: ObjectSpace;
  @Prop({ default: undefined }) width!: number | undefined;
  @Prop({ default: undefined }) height!: number | undefined;
  @Prop({ default: 1 }) aspectRation!: number;
  @Prop({ default: 0 }) anchor!: number | [number, number];
  @Prop({ default: '#ffffff' }) tint!: string;
  @Prop({ default: null }) outline!: number | null;
  @Prop() texture!: string | PIXI.Texture;
  gameContainer!: GameContainer;

  readonly defaultSize = 50;
  displayWidth = this.defaultSize;
  displayHeight = this.defaultSize;
  displayX = 0;
  displayY = 0;

  calcDisplayWidth(): number {
    let value = this.defaultSize;
    if (this.width) value = this.width;
    else if (this.height && this.aspectRation)
      value = this.height * this.aspectRation;

    if (
      this.objectSpace === ObjectSpace.RelativeToScreen &&
      this.gameContainer
    ) {
      return (value / 100) * this.gameContainer.gameWidth;
    }
    if (
      this.objectSpace === ObjectSpace.RelativeToBackground &&
      this.gameContainer
    ) {
      return (value / 100) * this.gameContainer.backgroundTextureSize[0];
    }
    return value;
  }

  calcDisplayHeight(): number {
    let value = this.defaultSize;
    if (this.height) value = this.height;
    else if (this.width && this.aspectRation)
      value = this.width / this.aspectRation;

    if (
      this.objectSpace == ObjectSpace.RelativeToScreen &&
      this.gameContainer
    ) {
      return (value / 100) * this.gameContainer.gameWidth;
    }
    if (
      this.objectSpace == ObjectSpace.RelativeToBackground &&
      this.gameContainer
    ) {
      return (value / 100) * this.gameContainer.backgroundTextureSize[0];
    }
    return value;
  }

  calcDisplayX(): number {
    if (
      this.objectSpace == ObjectSpace.RelativeToScreen &&
      this.gameContainer
    ) {
      return (this.x / 100) * this.gameContainer.gameWidth;
    }
    if (
      this.objectSpace == ObjectSpace.RelativeToBackground &&
      this.gameContainer
    ) {
      return (this.x / 100) * this.gameContainer.backgroundTextureSize[0];
    }
    return this.x;
  }

  calcDisplayY(): number {
    if (
      this.objectSpace === ObjectSpace.RelativeToScreen &&
      this.gameContainer
    ) {
      return (this.y / 100) * this.gameContainer.gameWidth;
    }
    if (
      this.objectSpace === ObjectSpace.RelativeToBackground &&
      this.gameContainer
    ) {
      return (this.y / 100) * this.gameContainer.backgroundTextureSize[0];
    }
    return this.y;
  }

  mounted(): void {
    const container = document.getElementById('gameContainer');
    if (container) {
      const registerCustomObject = new CustomEvent(
        EventType.REGISTER_CUSTOM_OBJECT,
        {
          detail: {
            data: this,
          },
        }
      );
      container.dispatchEvent(registerCustomObject);
    }
  }

  unmounted(): void {
    this.gameContainer.deregisterCustomObject(this);
  }

  setGameContainer(gameContainer: GameContainer): void {
    this.gameContainer = gameContainer;
    this.calculateRelativePosition();
  }

  calculateRelativePosition(): void {
    this.displayWidth = this.calcDisplayWidth();
    this.displayHeight = this.calcDisplayHeight();
    this.displayX = this.calcDisplayX();
    this.displayY = this.calcDisplayY();
  }

  @Watch('outline', { immediate: true })
  onOutlineChanged(): void {
    const customSprite = this.$refs.customSprite as any;
    if (customSprite) {
      if (this.outline)
        customSprite.filters = [new OutlineFilter(2, this.outline)];
      else customSprite.filters = [];
    }
  }

  @Watch('width', { immediate: true })
  @Watch('height', { immediate: true })
  @Watch('aspectRation', { immediate: true })
  @Watch('objectSpace', { immediate: true })
  onSizeChanged(): void {
    this.displayWidth = this.calcDisplayWidth();
    this.displayHeight = this.calcDisplayHeight();
  }

  @Watch('x', { immediate: true })
  @Watch('y', { immediate: true })
  @Watch('objectSpace', { immediate: true })
  onPositionChanged(): void {
    this.displayX = this.calcDisplayX();
    this.displayY = this.calcDisplayY();
  }
}
</script>

<style lang="scss" scoped></style>
