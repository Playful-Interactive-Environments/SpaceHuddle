import { CustomObject } from '@/types/game/CustomObject';
import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import * as PIXI from 'pixi.js';
import app from '@/main';
import { EventType } from '@/types/enum/EventType';
import { ObjectSpace } from '@/types/enum/ObjectSpace';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class SpaceCalculator implements CustomObject {
  private readonly defaultSize = 50;
  displayWidth = this.defaultSize;
  displayHeight = this.defaultSize;
  pixiObject: PIXI.Container;

  private gameContainer!: GameContainer;
  private eventBus = app.config.globalProperties.eventBus;

  constructor(pixiObject: PIXI.Container) {
    this.pixiObject = pixiObject;
    this.pixiObject.addEventListener('added', this.isAdded);
    this.calcDisplayWidth();
    this.calcDisplayHeight();
    this.calcDisplayX();
    this.calcDisplayY();
    this.eventBus.emit(EventType.REGISTER_CUSTOM_OBJECT, { data: this });
  }

  destroy(): void {
    this.gameContainer.deregisterCustomObject(this);
    this.pixiObject.removeEventListener('added', this.isAdded);
  }

  //#region properties
  private _width: number | undefined = undefined;
  get width(): number | undefined {
    return this._width;
  }

  set width(value: number | undefined) {
    this._width = value;
    this.calcDisplayWidth();
  }

  private _height: number | undefined = undefined;
  get height(): number | undefined {
    return this._height;
  }

  set height(value: number | undefined) {
    this._height = value;
    this.calcDisplayHeight();
  }

  private _objectSpace: ObjectSpace = ObjectSpace.Absolute;
  get objectSpace(): ObjectSpace {
    return this._objectSpace;
  }

  set objectSpace(value: ObjectSpace) {
    this._objectSpace = value;
    this.calcDisplayWidth();
    this.calcDisplayHeight();
    this.calcDisplayX();
    this.calcDisplayY();
  }

  private _aspectRation = 1;
  get aspectRation(): number {
    return this._aspectRation;
  }

  set aspectRation(value: number) {
    this._aspectRation = value;
    this.calcDisplayWidth();
    this.calcDisplayHeight();
  }

  private _x = 0;
  get x(): number {
    return this._x;
  }

  set x(value: number) {
    this._x = value;
    this.calcDisplayX();
  }

  private _y = 0;
  get y(): number {
    return this._y;
  }

  set y(value: number) {
    this._y = value;
    this.calcDisplayY();
  }

  get parent(): any {
    return this.pixiObject.parent;
  }

  get children(): PIXI.DisplayObject[] {
    return this.pixiObject.children;
  }
  //#endregion properties

  //#region calculate
  calcDisplayWidth(): number {
    let value = this.defaultSize;
    if (this.width) value = this.width;
    else if (this.height && this.aspectRation)
      value = this.height * this.aspectRation;

    if (
      this.objectSpace === ObjectSpace.RelativeToScreen &&
      this.gameContainer
    ) {
      value = (value / 100) * this.gameContainer.gameWidth;
    } else if (
      this.objectSpace === ObjectSpace.RelativeToBackground &&
      this.gameContainer
    ) {
      value = (value / 100) * this.gameContainer.backgroundTextureSize[0];
    }
    this.pixiObject.width = value;
    this.displayWidth = value;
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
      value = (value / 100) * this.gameContainer.gameWidth;
    } else if (
      this.objectSpace == ObjectSpace.RelativeToBackground &&
      this.gameContainer
    ) {
      value = (value / 100) * this.gameContainer.backgroundTextureSize[0];
    }
    this.pixiObject.height = value;
    this.displayHeight = value;
    return value;
  }

  calcDisplayX(): number {
    let value = this.x;
    if (
      this.objectSpace == ObjectSpace.RelativeToScreen &&
      this.gameContainer
    ) {
      value = (this.x / 100) * this.gameContainer.gameWidth;
    } else if (
      this.objectSpace == ObjectSpace.RelativeToBackground &&
      this.gameContainer
    ) {
      value = (this.x / 100) * this.gameContainer.backgroundTextureSize[0];
    }
    this.pixiObject.x = value;
    return value;
  }

  calcDisplayY(): number {
    let value = this.y;
    if (
      this.objectSpace === ObjectSpace.RelativeToScreen &&
      this.gameContainer
    ) {
      value = (this.y / 100) * this.gameContainer.gameWidth;
    } else if (
      this.objectSpace === ObjectSpace.RelativeToBackground &&
      this.gameContainer
    ) {
      value = (this.y / 100) * this.gameContainer.backgroundTextureSize[0];
    }
    this.pixiObject.x = value;
    return value;
  }

  setGameContainer(gameContainer: GameContainer): void {
    this.gameContainer = gameContainer;
    this.calculateRelativePosition();
  }

  calculateRelativePosition(): void {
    this.calcDisplayWidth();
    this.calcDisplayHeight();
    this.calcDisplayX();
    this.calcDisplayY();
  }
  //#endregion calculate

  //#region events
  isAdded(container: any): void {
    const source = container.source;
    if (source && Object.hasOwn(source, 'updatedColliderSize')) {
      source.updatedColliderSize();
    }
  }
  //#endregion events
}
