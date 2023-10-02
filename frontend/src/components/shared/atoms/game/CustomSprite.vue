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
    :filters="objectFilters"
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
import { OutlineFilter, ColorOverlayFilter } from 'pixi-filters';

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
  @Prop({ default: [0, 0, 0, 0] }) colorOverlay!: [
    number,
    number,
    number,
    number
  ];
  @Prop({ default: null }) outline!: number | null;
  @Prop() texture!: string | PIXI.Texture;
  @Prop({ default: [] }) filters!: any[];
  gameContainer!: GameContainer;

  readonly defaultSize = 50;
  displayWidth = this.defaultSize;
  displayHeight = this.defaultSize;
  displayX = 0;
  displayY = 0;
  outlineFilter: OutlineFilter | null = null;
  colorOverlayFilter: ColorOverlayFilter | null = null;

  get objectFilters(): any[] {
    const filters: any[] = [...this.filters];
    if (this.outlineFilter) filters.push(this.outlineFilter);
    if (this.colorOverlayFilter) filters.push(this.colorOverlayFilter);
    return filters;
  }

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

  @Watch('colorOverlay', { immediate: true })
  onColorOverlayChanged(): void {
    if (this.colorOverlay) {
      this.colorOverlayFilter = new ColorOverlayFilter(
        [this.colorOverlay[0], this.colorOverlay[1], this.colorOverlay[2]],
        this.colorOverlay[3]
      );
    } else this.colorOverlayFilter = null;
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
    if (this.$parent) (this.$parent as any).updatedColliderSize();
  }

  @Watch('outline', { immediate: true })
  onOutlineChanged(): void {
    if (this.outline) this.outlineFilter = new OutlineFilter(2, this.outline);
    else this.outlineFilter = null;
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
