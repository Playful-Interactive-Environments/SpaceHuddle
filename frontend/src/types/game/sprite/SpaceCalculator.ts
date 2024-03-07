import { SpaceObject } from '@/types/game/sprite/SpaceObject';
import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import * as PIXI from 'pixi.js';
import { ObjectSpaceType } from '@/types/enum/ObjectSpaceType';
import GameContainerObject, {
  GameContainerObjectType,
  IGameContainerObject,
} from '@/types/game/GameContainerObject';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class SpaceCalculator
  extends GameContainerObject
  implements SpaceObject
{
  private readonly defaultSize = 50;

  constructor(pixiObject: PIXI.Container & IGameContainerObject) {
    super(pixiObject, GameContainerObjectType.SPACE_OBJECT);
    this.calcDisplayWidth();
    this.calcDisplayHeight();
    this.calcDisplayX();
    this.calcDisplayY();
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

  private _objectSpace: ObjectSpaceType = ObjectSpaceType.Absolute;
  get objectSpace(): ObjectSpaceType {
    return this._objectSpace;
  }

  set objectSpace(value: ObjectSpaceType) {
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
  //#endregion properties

  //#region calculate
  calcDisplayWidth(): number {
    let value = this.defaultSize;
    if (this.width) value = this.width;
    else if (this.height && this.aspectRation)
      value = this.height * this.aspectRation;

    if (
      this.objectSpace === ObjectSpaceType.RelativeToScreen &&
      this.gameContainer
    ) {
      value = (value / 100) * this.gameContainer.gameWidth;
    } else if (
      this.objectSpace === ObjectSpaceType.RelativeToBackground &&
      this.gameContainer
    ) {
      value = (value / 100) * this.gameContainer.backgroundTextureSize[0];
    }
    if (this.pixiObject) this.pixiObject.width = value;
    return value;
  }

  calcDisplayHeight(): number {
    let value = this.defaultSize;
    if (this.height) value = this.height;
    else if (this.width && this.aspectRation)
      value = this.width / this.aspectRation;

    if (
      this.objectSpace == ObjectSpaceType.RelativeToScreen &&
      this.gameContainer
    ) {
      value = (value / 100) * this.gameContainer.gameWidth;
    } else if (
      this.objectSpace == ObjectSpaceType.RelativeToBackground &&
      this.gameContainer
    ) {
      value = (value / 100) * this.gameContainer.backgroundTextureSize[0];
    }
    if (this.pixiObject) this.pixiObject.height = value;
    return value;
  }

  calcDisplayX(): number {
    let value = this.x;
    if (
      this.objectSpace == ObjectSpaceType.RelativeToScreen &&
      this.gameContainer
    ) {
      value = (this.x / 100) * this.gameContainer.gameWidth;
    } else if (
      this.objectSpace == ObjectSpaceType.RelativeToBackground &&
      this.gameContainer
    ) {
      value = (this.x / 100) * this.gameContainer.backgroundTextureSize[0];
    }
    if (this.pixiObject) this.pixiObject.x = value;
    return value;
  }

  calcDisplayY(): number {
    let value = this.y;
    if (
      this.objectSpace === ObjectSpaceType.RelativeToScreen &&
      this.gameContainer
    ) {
      value = (this.y / 100) * this.gameContainer.gameWidth;
    } else if (
      this.objectSpace === ObjectSpaceType.RelativeToBackground &&
      this.gameContainer
    ) {
      value = (this.y / 100) * this.gameContainer.backgroundTextureSize[0];
    }
    if (this.pixiObject) this.pixiObject.x = value;
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
}
