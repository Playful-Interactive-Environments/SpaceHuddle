import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import * as PIXI from 'pixi.js';
import GameObject from '@/types/game/gameObject/GameObject';

export enum GameContainerObjectType {
  GAME_OBJECT = 'game_object',
  SPACE_OBJECT = 'space_object',
}

export interface IGameContainerObject {
  gameContainerObject: GameContainerObject;
  setGameContainer(gameContainer: GameContainer): void;
}

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class GameContainerObject {
  pixiObject: PIXI.Container & IGameContainerObject;

  gameContainer!: GameContainer;
  readonly gameContainerObjectType: GameContainerObjectType;

  isAddedCall: (container) => void;
  isRemovedCall: (container) => void;

  constructor(
    pixiObject: PIXI.Container & IGameContainerObject,
    gameContainerObjectType: GameContainerObjectType
  ) {
    this.gameContainerObjectType = gameContainerObjectType;
    this.pixiObject = pixiObject;
    this.pixiObject.gameContainerObject = this;
    const space = this as any;
    this.isAddedCall = (container) => space.isAdded(container);
    this.pixiObject.addEventListener('added', this.isAddedCall);
    this.isRemovedCall = (container) => space.isRemoved(container);
    this.pixiObject.addEventListener('removed', this.isRemovedCall);
  }

  //eslint-disable-next-line @typescript-eslint/no-unused-vars
  destroy(options?): void {
    if (this.gameContainer) {
      switch (this.gameContainerObjectType) {
        case GameContainerObjectType.GAME_OBJECT:
          this.gameContainer.deregisterGameObject(this.pixiObject as any);
          break;
        case GameContainerObjectType.SPACE_OBJECT:
          this.gameContainer.deregisterSpaceObject(this as any);
          break;
      }
    }
    this.pixiObject.removeEventListener('added', this.isAddedCall);
    this.pixiObject.removeEventListener('removed', this.isRemovedCall);
  }

  //#region properties
  getRenderer(): PIXI.IRenderer | null {
    if (this.gameContainer && this.gameContainer.app)
      return this.gameContainer.app.renderer;
    return null;
  }

  get parent(): any {
    return this.pixiObject.parent;
  }

  get children(): PIXI.DisplayObject[] {
    return this.pixiObject.children;
  }
  //#endregion properties

  //#region calculate
  setGameContainer(gameContainer: GameContainer): void {
    this.gameContainer = gameContainer;
  }
  //#endregion calculate

  //#region events
  isAdded(container: any): void {
    if (this.gameContainer) return;
    setTimeout(() => {
      if (container instanceof GameObject) {
        const gameObject = container as GameObject;
        gameObject.physcics.updatedColliderSize();
      }
      let parent = container;
      while (parent.parent) {
        parent = parent.parent;
        if (Object.hasOwn(parent, 'gameContainer')) break;
      }
      const gameContainer = (parent as any).gameContainer as GameContainer;
      if (gameContainer) {
        this.pixiObject.setGameContainer(gameContainer);
        switch (this.gameContainerObjectType) {
          case GameContainerObjectType.GAME_OBJECT:
            gameContainer.registerGameObject(this.pixiObject as any);
            break;
          case GameContainerObjectType.SPACE_OBJECT:
            gameContainer.registerSpaceObject(this as any);
            break;
        }
        this.pixiObject.removeEventListener('added', this.isAddedCall);
      }
    }, 100);
  }

  //eslint-disable-next-line @typescript-eslint/no-unused-vars
  isRemoved(container: any): void {
    this.pixiObject.destroy({ children: true });
  }
  //#endregion events
}
