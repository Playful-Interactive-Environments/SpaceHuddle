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
    :alpha="alpha"
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
import { SpaceObject } from '@/types/game/sprite/SpaceObject';
import { EventType } from '@/types/enum/EventType';
import {
  OutlineFilter,
  ColorOverlayFilter,
  AdjustmentFilter,
  HslAdjustmentFilter,
} from 'pixi-filters';

@Options({
  components: {},
  emits: [],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class CustomSprite extends Vue implements SpaceObject {
  @Prop({ default: 0 }) customX!: number;
  @Prop({ default: 0 }) customY!: number;
  @Prop({ default: ObjectSpace.Absolute }) objectSpace!: ObjectSpace;
  @Prop({ default: undefined }) customWidth!: number | undefined;
  @Prop({ default: undefined }) customHeight!: number | undefined;
  @Prop({ default: 1 }) aspectRation!: number;
  @Prop({ default: 0 }) anchor!: number | [number, number];
  @Prop({ default: '#ffffff' }) tint!: string;
  @Prop({ default: 1 }) alpha!: number;
  @Prop({ default: [0, 0, 0, 0] }) colorOverlay!: [
    number,
    number,
    number,
    number
  ];
  @Prop({ default: null }) outline!: number | null;
  @Prop({ default: 2 }) outlineWidth!: number;
  @Prop({ default: 1 }) saturation!: number;
  @Prop() texture!: string | PIXI.Texture;
  @Prop({ default: [] }) customFilters!: any[];
  gameContainer!: GameContainer;

  readonly defaultSize = 50;
  displayWidth = this.defaultSize;
  displayHeight = this.defaultSize;
  displayX = 0;
  displayY = 0;
  outlineFilter: OutlineFilter | null = null;
  colorOverlayFilter: ColorOverlayFilter | null = null;
  saturationFilter: PIXI.Filter[] = [];

  get objectFilters(): any[] {
    const filters: any[] = [...this.customFilters];
    if (this.saturationFilter) filters.push(...this.saturationFilter);
    if (this.outlineFilter) filters.push(this.outlineFilter);
    if (this.colorOverlayFilter) filters.push(this.colorOverlayFilter);
    return filters;
  }

  calcDisplayWidth(): number {
    let value = this.defaultSize;
    if (this.customWidth) value = this.customWidth;
    else if (this.customHeight && this.aspectRation)
      value = this.customHeight * this.aspectRation;

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
    if (this.customHeight) value = this.customHeight;
    else if (this.customWidth && this.aspectRation)
      value = this.customWidth / this.aspectRation;

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
      return (this.customX / 100) * this.gameContainer.gameWidth;
    }
    if (
      this.objectSpace == ObjectSpace.RelativeToBackground &&
      this.gameContainer
    ) {
      return (this.customX / 100) * this.gameContainer.backgroundTextureSize[0];
    }
    return this.customX;
  }

  calcDisplayY(): number {
    if (
      this.objectSpace === ObjectSpace.RelativeToScreen &&
      this.gameContainer
    ) {
      return (this.customY / 100) * this.gameContainer.gameWidth;
    }
    if (
      this.objectSpace === ObjectSpace.RelativeToBackground &&
      this.gameContainer
    ) {
      return (this.customY / 100) * this.gameContainer.backgroundTextureSize[0];
    }
    return this.customY;
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

  @Watch('saturation', { immediate: true })
  onSaturationChanged(): void {
    if (this.saturation !== 1) {
      this.saturationFilter = [
        new AdjustmentFilter({
          saturation: this.saturation,
          contrast: this.saturation,
          brightness: 2 - this.saturation,
        }),
        new HslAdjustmentFilter({
          saturation: this.saturation - 1,
          colorize: true,
          hue: 180,
          alpha: 1 - this.saturation,
        }),
        //new ColorOverlayFilter(0x9fe4eb, 1 - this.saturation),
        //new PIXI.BlurFilter(1 - this.saturation),
      ];
    } else this.saturationFilter = [];
  }

  mounted(): void {
    this.eventBus.emit(EventType.REGISTER_CUSTOM_OBJECT, { data: this });
  }

  unmounted(): void {
    this.gameContainer.deregisterSpaceObject(this);
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
    if (this.$parent && Object.hasOwn(this.$parent, 'updatedColliderSize'))
      (this.$parent as any).updatedColliderSize();
  }

  @Watch('outline', { immediate: true })
  onOutlineChanged(): void {
    if (this.outline)
      this.outlineFilter = new OutlineFilter(this.outlineWidth, this.outline);
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
