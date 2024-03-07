import * as PIXI from 'pixi.js';
import SpaceCalculator from '@/types/game/sprite/SpaceCalculator';
import { IGameContainerObject } from '@/types/game/GameContainerObject';
import ColorFilter from '@/types/game/sprite/ColorFilter';
import { v4 as uuidv4 } from 'uuid';
import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';

export class SpriteConverter
  extends PIXI.Sprite
  implements IGameContainerObject
{
  uuid: string;
  gameContainerObject: SpaceCalculator;
  colorFilter: ColorFilter;

  constructor(texture?: PIXI.Texture) {
    super(texture);
    this.gameContainerObject = new SpaceCalculator(this);
    this.colorFilter = new ColorFilter(this);
    this.uuid = uuidv4();
  }

  private _isDestroyed = false;
  destroy(options?) {
    if (!this._isDestroyed) {
      this._isDestroyed = true;
      this.gameContainerObject.destroy(options);
      super.destroy(options);
    }
  }

  setGameContainer(gameContainer: GameContainer): void {
    this.gameContainerObject.setGameContainer(gameContainer);
  }

  //#region properties
  get spaceX() {
    return this.gameContainerObject.x;
  }
  set spaceX(value) {
    this.gameContainerObject.x = value;
  }

  get spaceY() {
    return this.gameContainerObject.y;
  }
  set spaceY(value) {
    this.gameContainerObject.y = value;
  }

  get spaceWidth() {
    return this.gameContainerObject.width;
  }
  set spaceWidth(value) {
    this.gameContainerObject.width = value;
  }

  get spaceHeight() {
    return this.gameContainerObject.height;
  }
  set spaceHeight(value) {
    this.gameContainerObject.height = value;
  }

  get objectSpace() {
    return this.gameContainerObject.objectSpace;
  }
  set objectSpace(value) {
    this.gameContainerObject.objectSpace = value;
  }

  get aspectRation() {
    return this.gameContainerObject.aspectRation;
  }
  set aspectRation(value) {
    this.gameContainerObject.aspectRation = value;
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
