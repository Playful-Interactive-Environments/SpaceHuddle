import { ObjectSpaceType } from '@/types/enum/ObjectSpaceType';
import * as PIXI from 'pixi.js';
import GameContainerObject from '@/types/game/GameContainerObject';
import GameContainer, {
  BackgroundMovement,
} from '@/components/shared/atoms/game/GameContainer.vue';
import { delay, until } from '@/utils/wait';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export class ObjectTransform {
  pixiObject: PIXI.Container;
  gameContainerObject: GameContainerObject;
  fixSize: [number, number] | number | null = null;
  position: [number, number] = [0, 0];
  offset: [number, number] = [0, 0];
  objectSpace: ObjectSpaceType = ObjectSpaceType.Absolute;
  readonly defaultSize = 50;
  displayWidth = this.defaultSize;
  displayHeight = this.defaultSize;

  readonly renderDelay = 100;

  constructor(
    pixiObject: PIXI.Container,
    gameContainerObject: GameContainerObject
  ) {
    this.gameContainerObject = gameContainerObject;
    this.pixiObject = pixiObject;
  }

  //eslint-disable-next-line @typescript-eslint/no-unused-vars
  destroy(options?): void {
    //
  }

  //#region properties
  get gameContainer(): GameContainer {
    return this.gameContainerObject.gameContainer;
  }

  get displayX(): number {
    return this.position[0] - this.offset[0];
  }

  get displayY(): number {
    return this.position[1] - this.offset[1];
  }

  get possibleSpace(): [number, number] {
    if (
      this.objectSpace === ObjectSpaceType.RelativeToScreen &&
      this.gameContainer
    ) {
      return [
        this.gameContainer.gameWidth,
        this.gameContainer.gameDisplayHeight,
      ];
    } else if (
      this.objectSpace === ObjectSpaceType.RelativeToBackground &&
      this.gameContainer
    ) {
      return [
        this.gameContainer.backgroundTextureSize[0],
        this.gameContainer.backgroundTextureSize[1],
      ];
    }
    return [this.gameContainer.gameWidth, this.gameContainer.gameHeight];
  }

  get inputPosition(): [number, number] {
    if (
      this.objectSpace === ObjectSpaceType.RelativeToScreen &&
      this.gameContainer
    )
      return [
        (this.position[0] / this.gameContainer.gameWidth) * 100,
        (this.position[1] / this.gameContainer.gameDisplayHeight) * 100,
      ];
    if (
      this.objectSpace === ObjectSpaceType.RelativeToBackground &&
      this.gameContainer
    )
      return [
        (this.position[0] / this.gameContainer.backgroundTextureSize[0]) * 100,
        (this.position[1] / this.gameContainer.backgroundTextureSize[1]) * 100,
      ];
    return [this.position[0], this.position[1]];
  }
  //#endregion properties

  //#region get
  getContainerWidth(): number {
    if (this.fixSize === null)
      return this.pixiObject ? this.pixiObject.width : 100;
    if (Array.isArray(this.fixSize)) return this.fixSize[0];
    return this.fixSize;
  }

  getContainerHeight(): number {
    if (this.fixSize === null)
      return this.pixiObject ? this.pixiObject.height : 100;
    if (Array.isArray(this.fixSize)) return this.fixSize[1];
    return this.fixSize;
  }

  isPositionVisible(x: number, y: number, delta = 0): boolean {
    const deltaX = this.displayWidth / 2;
    const deltaY = this.displayHeight / 2;
    if (this.gameContainer) {
      return (
        x >= -delta - deltaX &&
        x <= this.gameContainer.gameWidth + delta + deltaX &&
        y >= -delta - deltaY &&
        y <= this.gameContainer.gameDisplayHeight + delta + deltaY
      );
    }
    return false;
  }
  //#endregion get

  //#region load / unload
  async initSize(): Promise<[number, number] | null> {
    const delayTime = this.fixSize === null ? 0 : this.renderDelay;
    await delay(delayTime);
    await until(() => !!this.gameContainer);
    try {
      const containerWidth = this.getContainerWidth();
      const containerHeight = this.getContainerHeight();
      let result: [number, number] | null = null;
      if (
        this.displayWidth !== containerWidth ||
        this.displayHeight !== containerHeight
      ) {
        const scaleX = containerWidth / this.displayWidth;
        const scaleY = containerHeight / this.displayHeight;
        result = [scaleX, scaleY];
      }
      this.displayWidth = containerWidth;
      this.displayHeight = containerHeight;
      this.pixiObject.emit('size_changed', [
        this.displayWidth,
        this.displayHeight,
      ]);
      return result;
    } catch (e) {
      this.pixiObject.emit('init_error', this.pixiObject);
    }
    return null;
  }

  initOffset(offset: [number, number]): void {
    this.offset = offset;
    this.syncPosition();
  }

  initPosition(x: number, y: number): void {
    if (
      this.objectSpace === ObjectSpaceType.RelativeToScreen &&
      this.gameContainer
    ) {
      this.position = [
        (x / 100) * this.gameContainer.gameWidth,
        (y / 100) * this.gameContainer.gameDisplayHeight,
      ];
    } else if (
      this.objectSpace === ObjectSpaceType.RelativeToBackground &&
      this.gameContainer
    ) {
      this.position = [
        (x / 100) * this.gameContainer.backgroundTextureSize[0],
        (y / 100) * this.gameContainer.backgroundTextureSize[1],
      ];
    } else {
      this.position = [x, y];
    }
    this.syncPosition();
  }
  //#endregion load / unload

  //#region events
  syncPosition(): void {
    this.pixiObject.x = this.displayX;
    this.pixiObject.y = this.displayY;
  }

  updatePosition(position: [number, number]): void {
    const inputPosition = this.inputPosition;
    if (inputPosition[0] !== position[0] || inputPosition[1] !== position[1]) {
      this.initPosition(position[0], position[1]);
    }
  }

  resetEndlessPanningPosition(): void {
    if (this.gameContainer.endlessPanning) {
      const maxX = this.gameContainer.backgroundTextureSize[0];
      const maxY = this.gameContainer.backgroundTextureSize[1];
      if (this.position[0] < 0) this.position[0] = maxX + this.position[0];
      if (this.position[0] > maxX) this.position[0] = this.position[0] - maxX;
      if (this.gameContainer.backgroundMovement === BackgroundMovement.Pan) {
        if (this.position[1] < 0) this.position[1] = maxY + this.position[1];
        if (this.position[1] > maxY) this.position[1] = this.position[1] - maxY;
      }
    }
    this.syncPosition();
  }
  //#endregion events
}
