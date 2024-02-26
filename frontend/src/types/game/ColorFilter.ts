/* eslint-disable @typescript-eslint/no-explicit-any*/
import {
  AdjustmentFilter,
  ColorOverlayFilter,
  HslAdjustmentFilter,
  OutlineFilter,
} from 'pixi-filters';
import * as PIXI from 'pixi.js';

export default class ColorFilter {
  outlineWidth = 2;
  sprite: PIXI.Sprite;

  private outlineFilter: OutlineFilter | null = null;
  private colorOverlayFilter: ColorOverlayFilter | null = null;
  private saturationFilter: PIXI.Filter[] = [];

  constructor(sprite: PIXI.Sprite) {
    this.sprite = sprite;
  }

  destroy(): void {
    //
  }

  //#region properties
  private _colorOverlay: [number, number, number, number] = [0, 0, 0, 0];
  get colorOverlay(): [number, number, number, number] {
    return this._colorOverlay;
  }

  set colorOverlay(value: [number, number, number, number]) {
    this._colorOverlay = value;
    if (this._colorOverlay) {
      this.colorOverlayFilter = new ColorOverlayFilter(
        [this._colorOverlay[0], this._colorOverlay[1], this._colorOverlay[2]],
        this._colorOverlay[3]
      );
      //this.colorOverlayFilter.autoFit = false;
      //this.colorOverlayFilter.resolution = 1;
    } else this.colorOverlayFilter = null;
    this.sprite.filters = this.objectFilters;
  }

  private _saturation = 1;
  get saturation(): number {
    return this._saturation;
  }

  set saturation(value: number) {
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
    this.sprite.filters = this.objectFilters;
  }

  private _outline: number | null = null;
  get outline(): number | null {
    return this._outline;
  }

  set outline(value: number | null) {
    this._outline = value;
    if (this._outline)
      this.outlineFilter = new OutlineFilter(this.outlineWidth, this._outline);
    else this.outlineFilter = null;
    this.sprite.filters = this.objectFilters;
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

  get objectFilters(): PIXI.Filter[] | null {
    const filters: PIXI.Filter[] = [...this.filters];
    if (this.saturationFilter) filters.push(...this.saturationFilter);
    if (this.outlineFilter) filters.push(this.outlineFilter);
    if (this.colorOverlayFilter) filters.push(this.colorOverlayFilter);
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
}
