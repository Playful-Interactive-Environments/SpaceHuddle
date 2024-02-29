/* eslint-disable @typescript-eslint/no-explicit-any*/
import {
  AdjustmentFilter,
  ColorOverlayFilter,
  HslAdjustmentFilter,
  OutlineFilter,
} from 'pixi-filters';
import * as PIXI from 'pixi.js';
import { until } from '@/utils/wait';
import { SpriteConverter } from '@/types/game/SpriteConverter';

export default class ColorFilter {
  preRenderFilters = true;
  outlineWidth = 2;
  sprite: SpriteConverter;

  private outlineFilter: OutlineFilter | null = null;
  private preOutlineFilter: OutlineFilter | null = null;
  private colorOverlayFilter: ColorOverlayFilter | null = null;
  private saturationFilter: PIXI.Filter[] = [];
  private baseTexture: PIXI.Texture | null = null;

  constructor(sprite: SpriteConverter) {
    this.sprite = sprite;
  }

  destroy(): void {
    //
  }

  //#region properties
  private _colorOverlay: [number, number, number, number] | null = null;
  get colorOverlay(): [number, number, number, number] | null {
    return this._colorOverlay;
  }

  set colorOverlay(value: [number, number, number, number] | null) {
    if (this._colorOverlay === value) return;
    if (JSON.stringify(this._colorOverlay) === JSON.stringify(value)) return;
    this._colorOverlay = value;
    if (this._colorOverlay) {
      this.colorOverlayFilter = new ColorOverlayFilter(
        [this._colorOverlay[0], this._colorOverlay[1], this._colorOverlay[2]],
        this._colorOverlay[3]
      );
      //this.colorOverlayFilter.autoFit = false;
      //this.colorOverlayFilter.resolution = 1;
    } else this.colorOverlayFilter = null;
    this.updateFilter();
  }

  private _saturation = 1;
  get saturation(): number {
    return this._saturation;
  }

  set saturation(value: number) {
    if (this._saturation === value) return;
    this._saturation = value;
    if (this._saturation !== 1) {
      this.saturationFilter = [
        new AdjustmentFilter({
          saturation: this._saturation,
          contrast: this._saturation,
          brightness: 2 - this._saturation,
        }),
        new HslAdjustmentFilter({
          saturation: this._saturation - 1,
          colorize: true,
          hue: 180,
          alpha: 1 - this._saturation,
        }),
        //new ColorOverlayFilter(0x9fe4eb, 1 - this.saturation),
        //new PIXI.BlurFilter(1 - this.saturation),
      ];
    } else this.saturationFilter = [];
    this.updateFilter();
  }

  private _outline: number | null = null;
  get outline(): number | null {
    return this._outline;
  }

  set outline(value: number | null) {
    if (this._outline === value) return;
    this._outline = value;
    if (this._outline) {
      this.outlineFilter = new OutlineFilter(this.outlineWidth, this._outline);
      this.preOutlineFilter = new OutlineFilter(
        this.outlineRenderWidth,
        this._outline
      );
    } else {
      this.outlineFilter = null;
      this.preOutlineFilter = null;
    }
    //this.sprite.filters = this.objectFilters;
    this.updateFilter();
  }

  get outlineRenderWidth(): number {
    if (!this._outline) return 0;
    if (this.preRenderFilters) return this.outlineWidth * 10;
    return this.outlineWidth;
  }

  private _tint = '#ffffff';
  get tint(): string {
    return this._tint;
  }

  set tint(value: string) {
    this._tint = value;
    this.sprite.tint = value;
  }

  private _alpha = 1;
  get alpha(): number {
    return this._alpha;
  }

  set alpha(value: number) {
    this._alpha = value;
    this.sprite.alpha = value;
  }

  private _filters: PIXI.Filter[] = [];
  get filters(): PIXI.Filter[] {
    return this._filters;
  }

  set filters(value: PIXI.Filter[]) {
    this._filters = value;
    this.sprite.filters = this.objectFilters;
  }

  private _preFilters: PIXI.Filter[] = [];
  get preFilters(): PIXI.Filter[] {
    return this._preFilters;
  }

  set preFilters(value: PIXI.Filter[]) {
    this._preFilters = value;
    this.updateFilter();
  }

  get objectFilters(): PIXI.Filter[] | null {
    const filters: PIXI.Filter[] = [...this.filters];
    if (!this.preRenderFilters) {
      if (this.saturationFilter) filters.push(...this.saturationFilter);
      if (this.colorOverlayFilter) filters.push(this.colorOverlayFilter);
      if (this.outlineFilter) filters.push(this.outlineFilter);
    }
    const preFilter = this.preRenderObjectFilters;
    if (preFilter) {
      for (const pre of preFilter) {
        if (
          !this._preRenderedFilterList.find(
            (item) => item.constructor.name === pre.constructor.name
          )
        ) {
          if (pre === this.preOutlineFilter && this.outlineFilter)
            filters.push(this.outlineFilter);
          else filters.push(pre);
        }
      }
    }
    if (filters.length === 0) return null;
    return filters;
  }

  get preRenderObjectFilters(): PIXI.Filter[] | null {
    const filters: PIXI.Filter[] = [...this._preFilters];
    if (this.preRenderFilters) {
      if (this.saturationFilter) filters.push(...this.saturationFilter);
      if (this.colorOverlayFilter) filters.push(this.colorOverlayFilter);
      if (this.preOutlineFilter) filters.push(this.preOutlineFilter);
    }
    if (filters.length === 0) return null;
    return filters;
  }

  get parent(): any {
    return this.sprite.parent;
  }

  get children(): PIXI.DisplayObject[] {
    return this.sprite.children;
  }
  //#endregion properties

  //#region prerender
  updateFilter(baseTexture: PIXI.Texture | null = null): void {
    if (this.preRenderFilters) {
      this.preRenderData(baseTexture);
    } else {
      this.sprite.filters = this.objectFilters;
    }
  }

  private preRenderTimeStamp = 0;
  private _preRenderedFilterList: PIXI.Filter[] = [];
  async preRenderData(baseTexture: PIXI.Texture | null = null): Promise<void> {
    if (baseTexture) this.baseTexture = baseTexture;
    const filter = this.preRenderObjectFilters;
    if (filter) {
      const isAllPreRendered =
        filter.length <= this._preRenderedFilterList.length;
      if (!isAllPreRendered) this.sprite.filters = this.objectFilters;
      const preRenderTimeStamp = Date.now();
      this.preRenderTimeStamp = preRenderTimeStamp;
      await until(() => this.sprite.space.getRenderer());
      if (preRenderTimeStamp !== this.preRenderTimeStamp) {
        return;
      }
      const renderer = this.sprite.space.getRenderer();
      if (!this.baseTexture) this.baseTexture = this.sprite.texture;
      const sprite = new PIXI.Sprite(this.baseTexture);
      sprite.width = this.baseTexture.orig.width;
      sprite.height = this.baseTexture.orig.height;
      sprite.filters = filter;
      const renderDelta = this.outlineRenderWidth;
      const bounds = new PIXI.Rectangle(
        -renderDelta,
        -renderDelta,
        Math.round(sprite.width) + renderDelta * 2,
        Math.round(sprite.height) + renderDelta * 2
      );
      if (renderer) {
        this.sprite.preTexture = renderer.generateTexture(sprite, {
          region: bounds,
        });
        this._preRenderedFilterList = filter;
        if (!isAllPreRendered) this.sprite.filters = this.objectFilters;
      }
    }
  }
  //#endregion prerender
}
