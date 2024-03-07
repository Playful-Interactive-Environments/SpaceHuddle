import * as PIXI from 'pixi.js';
import SpaceCalculator, {
  SpaceContainer,
} from '@/types/game/sprite/SpaceCalculator';
import ColorFilter from '@/types/game/sprite/ColorFilter';
import { v4 as uuidv4 } from 'uuid';

export class SpriteConverter extends PIXI.Sprite implements SpaceContainer {
  uuid: string;
  space: SpaceCalculator;
  colorFilter: ColorFilter;

  constructor(texture?: PIXI.Texture) {
    super(texture);
    this.space = new SpaceCalculator(this);
    this.colorFilter = new ColorFilter(this);
    this.uuid = uuidv4();
  }

  destroy(options?) {
    this.space.destroy();
    super.destroy(options);
  }

  //#region properties
  get spaceX() {
    return this.space.x;
  }
  set spaceX(value) {
    this.space.x = value;
  }

  get spaceY() {
    return this.space.y;
  }
  set spaceY(value) {
    this.space.y = value;
  }

  get spaceWidth() {
    return this.space.width;
  }
  set spaceWidth(value) {
    this.space.width = value;
  }

  get spaceHeight() {
    return this.space.height;
  }
  set spaceHeight(value) {
    this.space.height = value;
  }

  get objectSpace() {
    return this.space.objectSpace;
  }
  set objectSpace(value) {
    this.space.objectSpace = value;
  }

  get aspectRation() {
    return this.space.aspectRation;
  }
  set aspectRation(value) {
    this.space.aspectRation = value;
  }

  get preRenderFilters() {
    return this.colorFilter.preRenderFilters;
  }

  set preRenderFilters(value) {
    if (this.colorFilter.preRenderFilters !== value) {
      this.colorFilter.preRenderFilters = value;
      this.colorFilter.preRenderData();
      this.filters = this.colorFilter.objectFilters;
    }
  }

  get colorOverlay() {
    return this.colorFilter.colorOverlay;
  }
  set colorOverlay(value) {
    this.colorFilter.colorOverlay = value;
  }

  get outline() {
    return this.colorFilter.outline;
  }
  set outline(value) {
    this.colorFilter.outline = value;
  }

  get outlineWidth() {
    return this.colorFilter.outlineWidth;
  }
  set outlineWidth(value) {
    this.colorFilter.outlineWidth = value;
  }

  get saturation() {
    return this.colorFilter.saturation;
  }
  set saturation(value) {
    this.colorFilter.saturation = value;
  }

  get customFilters() {
    return this.colorFilter.filters;
  }
  set customFilters(value) {
    this.colorFilter.filters = value;
  }

  get preFilters() {
    return this.colorFilter.preFilters;
  }

  set preFilters(value) {
    this.colorFilter.preFilters = value;
  }

  get preTint() {
    return this.colorFilter.preTint;
  }

  set preTint(value) {
    if (this.colorFilter.preTint !== value) {
      this.colorFilter.preTint = value;
      if (value) this.tint = value;
    }
  }

  get texture() {
    return super.texture;
  }

  set texture(value) {
    if (super.texture === value) return;
    super.texture = value;
    if (this.colorFilter) this.colorFilter.updateFilter(value);
  }

  set preTexture(value) {
    super.texture = value;
  }
  //#endregion properties
}
