import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import * as PIXI from 'pixi.js';

export enum GameContainerObjectType {
  GAME_OBJECT = 'game_object',
  SPACE_OBJECT = 'space_object',
}

export interface SpaceContainer {
  space: GameContainerObject;
}

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class GameContainerObject {
  pixiObject: PIXI.Container & SpaceContainer;

  protected gameContainer!: GameContainer;
  readonly gameContainerObjectType: GameContainerObjectType;

  constructor(
    pixiObject: PIXI.Container & SpaceContainer,
    gameContainerObjectType: GameContainerObjectType
  ) {
    this.gameContainerObjectType = gameContainerObjectType;
    this.pixiObject = pixiObject;
    this.pixiObject.space = this;
    this.pixiObject.addEventListener('added', this.isAdded);
  }

  destroy(): void {
    if (this.gameContainer) {
      switch (this.gameContainerObjectType) {
        case GameContainerObjectType.GAME_OBJECT:
          this.gameContainer.deregisterGameObject(this as any);
          break;
        case GameContainerObjectType.SPACE_OBJECT:
          this.gameContainer.deregisterSpaceObject(this as any);
          break;
      }
    }
    this.pixiObject.removeEventListener('added', this.isAdded);
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
      const source = container.source;
      if (source && Object.hasOwn(source, 'updatedColliderSize')) {
        source.updatedColliderSize();
      }
      let parent = container;
      while (parent.parent) {
        parent = parent.parent;
        if (Object.hasOwn(parent, 'gameContainer')) break;
      }
      const gameContainer = (parent as any).gameContainer as GameContainer;
      if (gameContainer) {
        const spaceContainer = this as any as SpaceContainer;
        console.log('isAdded', container, this, spaceContainer, spaceContainer.space.gameContainerObjectType);
        switch (spaceContainer.space.gameContainerObjectType) {
          case GameContainerObjectType.GAME_OBJECT:
            this.gameContainer.registerGameObject(spaceContainer.space as any);
            break;
          case GameContainerObjectType.SPACE_OBJECT:
            this.gameContainer.registerSpaceObject(spaceContainer.space as any);
            break;
        }
      }
    }, 100);
  }
  //#endregion events
}
