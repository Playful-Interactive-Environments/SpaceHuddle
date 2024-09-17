import * as Matter from 'matter-js/build/matter';
import * as PIXI from 'pixi.js';
import GameContainer, {
  BackgroundMovement,
} from '@/components/shared/atoms/game/GameContainer.vue';
import { ObjectTransform } from '@/types/game/gameObject/ObjectTransform';
import { GrayscaleFilter } from 'pixi-filters';
import {
  ConditionalVelocity,
  FastObjectBehaviour,
} from '@/types/game/gameObject/GameObject';
import { CollisionHandler } from '@/types/game/CollisionHandler';
import * as matterUtil from '@/utils/matter';
import { delay } from '@/utils/wait';

export const bounceCategory = 1 << 31;

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ObjectPhysics {
  pixiObject: PIXI.Container;
  transform: ObjectTransform;
  bodyId = -1;
  clickable = true;
  sleepIfNotVisible = false;
  isStatic = false;
  usePhysic = true;
  keepInside = true;
  affectedByForce = true;
  fastObjectBehaviour: FastObjectBehaviour = FastObjectBehaviour.none;
  collisionHandler: CollisionHandler | null = null;
  anchor: number | [number, number] = 0;
  conditionalVelocity: ConditionalVelocity | null = null;

  isAddedCall: (container) => void;

  constructor(pixiObject: PIXI.Container, transform: ObjectTransform) {
    this.transform = transform;
    this.pixiObject = pixiObject;
    const copy = this as any;
    this.isAddedCall = (container) => copy.isAdded(container);
    this.pixiObject.addEventListener('added', this.isAddedCall);
  }

  //eslint-disable-next-line @typescript-eslint/no-unused-vars
  destroy(options?): void {
    this.pixiObject.removeEventListener('added', this.isAddedCall);
    this.body = null;
  }

  //#region props
  private _colliderScaleFactor = 1;
  get colliderScaleFactor(): number {
    return this._colliderScaleFactor;
  }

  set colliderScaleFactor(value: number) {
    this._colliderScaleFactor = value;
    this.updateScale();
  }

  private get gameContainer(): GameContainer {
    return this.transform.gameContainer;
  }

  get body(): Matter.Body | null {
    if (this.bodyId === -1) return null;
    if (this.gameContainer) {
      return this.gameContainer.getBodyForId(this.bodyId);
    }
    return null;
  }

  set body(value: Matter.Body | null) {
    if (value) this.bodyId = value.id;
    else this.bodyId = -1;
    if (this.bodyId > -1) {
      this._appliedScaleFactor = this.pixiObject.scale.x;
      this.updatePivot();
      this.updateRotation();
      this.updateScale();
      if (this.clickable) {
        this.manageEngin();
      }
      this.pixiObject.emit('initialised', this.pixiObject);
    }
  }
  //#endregion props

  //#region get / set / update
  isVisible(delta = 0): boolean {
    if (!this.body) return false;
    const x = this.body.position.x;
    const y = this.body.position.y;
    let isVisible = this.transform.isPositionVisible(x, y, delta);
    if (this.gameContainer.endlessPanning && !isVisible) {
      isVisible =
        isVisible ||
        this.transform.isPositionVisible(
          x - this.gameContainer.backgroundTextureSize[0],
          y,
          delta
        );
      if (
        !isVisible &&
        this.gameContainer.backgroundMovement === BackgroundMovement.Pan
      ) {
        isVisible =
          isVisible ||
          this.transform.isPositionVisible(
            x,
            y - this.gameContainer.backgroundTextureSize[1],
            delta
          );
        isVisible =
          isVisible ||
          this.transform.isPositionVisible(
            x - this.gameContainer.backgroundTextureSize[0],
            y - this.gameContainer.backgroundTextureSize[1],
            delta
          );
      }
    }
    return isVisible;
  }

  getVelocityAmount(): number {
    return (
      Math.pow(this.body.velocity.x, 2) + Math.pow(this.body.velocity.y, 2)
    );
  }

  updateRotation(): void {
    if (this.body && !isNaN(this.pixiObject.rotation)) {
      Matter.Body.setAngle(this.body, this.pixiObject.rotation);
      //if (this.boundsGraphic) this.drawBorder();
    }
  }

  _appliedScaleFactor = 1;
  updateScale(): void {
    const scaleFactor = this.pixiObject.scale.x * this._colliderScaleFactor;
    if (this.body && scaleFactor !== this._appliedScaleFactor) {
      const scale = (1 / this._appliedScaleFactor) * scaleFactor;
      Matter.Body.scale(this.body, scale, scale);
      this._appliedScaleFactor = scaleFactor;
      //if (this.boundsGraphic) this.drawBorder();
    }
  }

  collisionCategory = 0b0001;
  collisionMask = 0xffffffff; //0b11111111111111111111111111111111
  hasDisabled = false;
  updateDisabled(value: boolean): void {
    if (value) {
      this.collisionCategory = this.body.collisionFilter.category;
      this.collisionMask = this.body.collisionFilter.mask;
      this.body.collisionFilter.category = 0b0010;
      this.body.collisionFilter.mask = 0b11111111111111111111111111111110;
      this.pixiObject.filters = [new GrayscaleFilter()];
      this.hasDisabled = true;
    } else if (this.hasDisabled) {
      if (this.body) {
        this.body.collisionFilter.category = this.collisionCategory;
        this.body.collisionFilter.mask = this.collisionMask;
      }
      this.pixiObject.filters = null;
    }
  }

  updatedColliderSize(): void {
    const updateBody = (): void => {
      try {
        const containerWidth = this.transform.getContainerWidth();
        if (
          this.body &&
          this.pixiObject &&
          containerWidth !== this.transform.displayWidth
        ) {
          const scale = containerWidth / this.transform.displayWidth;
          Matter.Body.scale(this.body, scale, scale);
          this.transform.displayWidth = containerWidth;
          this.transform.displayHeight = this.transform.getContainerHeight();
          this.pixiObject.emit('size_changed', [
            this.transform.displayWidth,
            this.transform.displayHeight,
          ]);
          //if (this.boundsGraphic) this.drawBorder();
        }
      } catch (e) {
        //
      }
    };

    if (this.body && this.pixiObject) {
      updateBody();
    } else {
      setTimeout(() => {
        updateBody();
      }, 1000);
    }
  }

  async updatePivot(delta = 100, alwaysUpdate = false): Promise<void> {
    await matterUtil.updatePivot(this.body, this.anchor, delta, alwaysUpdate);
  }

  updateZIndex(): void {
    if (this.bodyId > -1) {
      (this.body as any).zIndex = this.pixiObject.zIndex;
      if (this.gameContainer) this.gameContainer.sortBodies();
    }
  }
  //#endregion get / set / update

  //#region events
  //eslint-disable-next-line @typescript-eslint/no-unused-vars
  async isAdded(container: any): Promise<void> {
    this.transform.initSize().then((resize) => {
      if (this.body && resize) {
        Matter.Body.scale(this.body, resize[0], resize[1]);
        this.pixiObject.removeEventListener('added', this.isAddedCall);
      }
    });
  }
  //#endregion events

  //#region load / unload
  updateOffset(
    offset: [number, number],
    offsetCircle: [number, number] | null = null
  ): void {
    const testOffset = (offsetX: number, offsetY: number): boolean => {
      const newPosition = {
        x: this.transform.position[0] - offsetX,
        y: this.transform.position[1] - offsetY,
      };
      if (this.transform.isPositionVisible(newPosition.x, newPosition.y, 0)) {
        this.transform.offset = [offsetX, offsetY];
        Matter.Body.setPosition(this.body, newPosition);
        return true;
      }
      return false;
    };

    this.transform.offset = offset;
    if (this.body) {
      if (Array.isArray(offsetCircle)) {
        if (!testOffset(offset[0], offset[1])) {
          if (!testOffset(offsetCircle[0], offset[1])) {
            if (!testOffset(offset[0], offsetCircle[1])) {
              if (!testOffset(offsetCircle[0], offsetCircle[1])) {
                Matter.Body.setPosition(this.body, {
                  x: this.transform.displayX,
                  y: this.transform.displayY,
                });
              }
            }
          }
        }
      } else {
        Matter.Body.setPosition(this.body, {
          x: this.transform.displayX,
          y: this.transform.displayY,
        });
      }
      //if (this.boundsGraphic) this.drawBorder();
    }
    this.transform.syncPosition();
  }

  initPosition(x: number, y: number): void {
    this.transform.initPosition(x, y);
    if (this.body) {
      Matter.Body.setPosition(this.body, {
        x: this.transform.displayX,
        y: this.transform.displayY,
      });
      //if (this.space.boundsGraphic) this.space.drawBorder();
    }
  }

  manageEngin(): void {
    if (!this.clickable || !this.body) return;
    if (this.sleepIfNotVisible) {
      const isVisible = this.isVisible(this.transform.displayWidth * 3);
      this.body.isStatic = !isVisible || this.isStatic;
      //Matter.Sleeping.set(this.body, !isVisible);
    }
  }

  assignPoolBody(body: Matter.Body): void {
    if (this.pixiObject) {
      Matter.Body.setPosition(body, {
        x: this.pixiObject.x,
        y: this.pixiObject.y,
      });
    }
    this.body = body;
    if (this.clickable) {
      this.manageEngin();
    }
    this.pixiObject.emit('initialised', this.pixiObject);
  }
  //#endregion load / unload

  //#region interaction
  clickTime = 0;
  gameObjectClicked(): void {
    if (this.hasDisabled) {
      return;
    }
    (this.pixiObject as any).highlighted = true;
    if (this.body && !this.isStatic && !this.usePhysic) {
      this.body.isStatic = false;
    }
    this.pixiObject.emit('hold', this.pixiObject);
    if (this.gameContainer) {
      this.clickTime = Date.now();
      this.gameContainer.$emit('update:selectedObject', this.pixiObject);
      this.gameContainer.activeObject = this.pixiObject as any;
    }
  }

  async gameObjectReleased(): Promise<void> {
    (this.pixiObject as any).highlighted = false;
    if (this.body && !this.isStatic && !this.usePhysic) {
      this.body.isStatic = true;
    } else if (!this.isStatic && !this.usePhysic) {
      setTimeout(() => {
        if (this.body) this.body.isStatic = true;
      }, 100);
    }
    this.pixiObject.emit('release', this.pixiObject);
    if (
      this.gameContainer &&
      this.gameContainer.activeObject === (this.pixiObject as any)
    ) {
      const clickTimeDelta = Date.now() - this.clickTime;
      const releaseDelay =
        this.gameContainer.minClickTimeDelta + 10 - clickTimeDelta;
      if (releaseDelay > 0) await delay(releaseDelay);
      this.gameContainer.activeObject = null;
    }
  }
  //#endregion interaction

  //#region pooling
  isSleeping = false;
  sleepTime = Date.now();
  readyForReuse(): boolean {
    return this.isSleeping && Date.now() > this.sleepTime && !!this.body;
  }

  moveToPool(minSleepTime = 500): void {
    if (!this.body) return;
    this.sleepTime = Date.now() + minSleepTime;
    (this.pixiObject as any).highlighted = false;
    this.isSleeping = true;
    this.body.isStatic = true;
    matterUtil.resetBody(this.body, this.gameContainer.mouseConstraint);
    //Matter.Sleeping.set(this.body, true);
    Matter.Body.setPosition(this.body, { x: -10000, y: -10000 });
    this.transform.position = [
      this.body.position.x + this.transform.offset[0],
      this.body.position.y + this.transform.offset[1],
    ];
    this.transform.syncPosition();
  }

  activateFromPool(position: [number, number]): void {
    if (!this.body) return;
    this.isSleeping = false;
    Matter.Body.setPosition(this.body, { x: position[0], y: position[1] });
    //Matter.Sleeping.set(this.body, false);
    this.body.isStatic = this.isStatic;
  }
  //#endregion pooling

  //#region matter update
  wasVisible = false;
  wasAtBorder = false;
  beforePhysicUpdate(): void {
    if (
      !this.pixiObject.destroyed &&
      this.body &&
      !this.body.isStatic &&
      !this.body.isSleeping
    ) {
      let isVisible = this.isVisible(-this.transform.displayWidth / 2);
      const isAtBorder =
        this.isVisible(this.transform.displayWidth / 2) && !isVisible;
      if (
        this.fastObjectBehaviour === FastObjectBehaviour.bounce &&
        this.gameContainer.mouseConstraint?.body?.id !== this.bodyId
      ) {
        const combinedMask = this.body.collisionFilter.mask | bounceCategory;
        if (this.wasVisible || isVisible) {
          const velocityAmount = this.getVelocityAmount();
          if (velocityAmount > 10) {
            if (!isVisible && this.body.collisionFilter.mask !== combinedMask) {
              const delta = this.transform.displayWidth / 2;
              const pos: [number, number] = [
                this.body.position.x,
                this.body.position.y,
              ];
              if (pos[0] < delta) pos[0] = delta;
              else if (pos[0] > this.gameContainer.gameWidth - delta)
                pos[0] = this.gameContainer.gameWidth - delta;
              if (pos[1] < delta) pos[1] = delta;
              else if (pos[1] > this.gameContainer.gameDisplayHeight - delta)
                pos[1] = this.gameContainer.gameDisplayHeight - delta;
              Matter.Body.setPosition(this.body, { x: pos[0], y: pos[1] });
              isVisible = true;
            }
            if (this.wasVisible || isVisible)
              this.body.collisionFilter.mask = combinedMask;
          } else this.body.collisionFilter.mask = combinedMask ^ bounceCategory;
        } else {
          this.body.collisionFilter.mask = combinedMask ^ bounceCategory;
          if (this.wasAtBorder) {
            const velocityAmount = this.getVelocityAmount();
            if (velocityAmount > 10)
              Matter.Body.setVelocity(this.body, { x: 0, y: 0 });
          }
        }
      }
      this.wasVisible = isVisible;
      this.wasAtBorder = isAtBorder;
    }
  }

  isVisibleInContainer = true;
  afterPhysicUpdate(): void {
    if (
      !this.pixiObject.destroyed &&
      this.body &&
      !this.body.isStatic &&
      !this.body.isSleeping
    ) {
      const hasPositionUpdate =
        this.body.position.x + this.transform.offset[0] !==
          this.transform.position[0] ||
        this.body.position.y + this.transform.offset[1] !==
          this.transform.position[1];
      if (hasPositionUpdate) {
        const isVisible = this.isVisible();
        if (this.isVisibleInContainer !== isVisible) {
          this.isVisibleInContainer = isVisible;
          this.pixiObject.emit('visibility_changed', isVisible);
        }
        if (this.gameContainer) {
          const possibleSpace = this.transform.possibleSpace;
          const maxRight = possibleSpace[0];
          const minLeft = 0;
          const outsideRight =
            this.body.position.x + this.transform.offset[0] > maxRight;
          const outsideLeft =
            this.body.position.x + this.transform.offset[0] < minLeft;
          const outsideBottom =
            this.body.position.y + this.transform.offset[1] > possibleSpace[1];
          const outsideTop =
            this.body.position.y + this.transform.offset[1] < 0;
          if (outsideRight || outsideLeft || outsideBottom || outsideTop) {
            this.pixiObject.emit('outside_drawing_space', this.pixiObject, {
              right: outsideRight,
              left: outsideLeft,
              bottom: outsideBottom,
              top: outsideTop,
            });
            if (
              this.keepInside &&
              !this.isStatic &&
              !this.isSleeping &&
              !this.body?.isStatic
            ) {
              const pos: [number, number] = [
                outsideLeft
                  ? -this.transform.offset[0]
                  : outsideRight
                  ? possibleSpace[0] - this.transform.offset[0]
                  : this.body.position.x,
                outsideTop
                  ? -this.transform.offset[1]
                  : outsideBottom
                  ? possibleSpace[1] - this.transform.offset[1]
                  : this.body.position.y,
              ];
              if (this.gameContainer.endlessPanning) {
                /*if (outsideLeft)
                  pos[0] = this.body.position.x + possibleSpace[0];
                else if (outsideRight)
                  pos[0] = this.body.position.x - possibleSpace[0];*/
                if (!this.gameContainer.useDefaultBoundsX)
                  pos[0] = this.body.position.x;
                if (!this.gameContainer.useDefaultBoundsY)
                  pos[1] = this.body.position.y;
              }
              Matter.Body.setPosition(this.body, { x: pos[0], y: pos[1] });
              Matter.Body.setVelocity(this.body, { x: 0, y: 0 });
              Matter.Body.setAngularVelocity(this.body, 0);
              Matter.Body.setSpeed(this.body, 0);
            }
          }
        }
        if (
          this.fastObjectBehaviour === FastObjectBehaviour.circle &&
          this.gameContainer.mouseConstraint?.body?.id !== this.bodyId
        ) {
          const velocityAmount = this.getVelocityAmount();
          if (velocityAmount > 10) {
            const delta = 10;
            const pos: [number, number] = [
              this.body.position.x,
              this.body.position.y,
            ];
            if (pos[0] < -delta) pos[0] = this.gameContainer.gameWidth;
            else if (pos[0] > this.gameContainer.gameWidth + delta) pos[0] = 0;
            if (pos[1] < -delta) pos[1] = this.gameContainer.gameDisplayHeight;
            else if (pos[1] > this.gameContainer.gameDisplayHeight + delta)
              pos[1] = 0;
            Matter.Body.setPosition(this.body, { x: pos[0], y: pos[1] });
          }
        }
        const oldPosition = [...this.transform.position];
        this.transform.position = [
          this.body.position.x + this.transform.offset[0],
          this.body.position.y + this.transform.offset[1],
        ];
        this.pixiObject.rotation = this.body.angle;
        const inputPosition = this.transform.inputPosition;
        const minChange = 0.1;
        if (
          Math.abs(this.transform.position[0] - oldPosition[0]) > minChange ||
          Math.abs(this.transform.position[1] - oldPosition[1]) > minChange
        ) {
          this.pixiObject.emit('position_changed', inputPosition);
        }
      }
      if (
        this.conditionalVelocity &&
        this.gameContainer.mouseConstraint?.body?.id !== this.bodyId
      ) {
        const velocityAmount = this.getVelocityAmount();
        if (velocityAmount < 5) {
          if (this.conditionalVelocity.condition(this.pixiObject as any)) {
            Matter.Body.setVelocity(
              this.body,
              this.conditionalVelocity.velocity
            );
          }
        }
      }
    }
    //if (this.boundsGraphic) this.drawBorder();
    if (this.sleepIfNotVisible) this.manageEngin();

    this.transform.resetEndlessPanningPosition();
    this.transform.syncPosition();
  }
  //#endregion matter update

  //#region collision

  notifyCollision(): void {
    this.pixiObject.emit('notify_collision', this.pixiObject);
  }

  handleCollision(
    collisionObject: any | null,
    hitPoint: [number, number],
    hitPointScreen: [number, number],
    objectBody: Matter.Body,
    collisionBody: Matter.Body
  ): boolean {
    let deleteFlag = false;
    if (this.collisionHandler) {
      deleteFlag = this.collisionHandler.handleCollision(
        this.pixiObject as any,
        collisionObject,
        hitPoint,
        hitPointScreen,
        objectBody,
        collisionBody
      );
    }

    this.pixiObject.emit(
      'collision',
      this.pixiObject,
      collisionObject,
      objectBody,
      collisionBody,
      hitPoint,
      hitPointScreen
    );
    return deleteFlag;
  }
  //#endregion collision

  //#region draw bounding box
  boundsWidth: number | null = null;
  boundsHeight: number | null = null;
  boundsGraphic: PIXI.Graphics | null = null;
  drawBorder(inputGraphics: PIXI.Graphics | null = null): void {
    const graphics = inputGraphics ?? this.boundsGraphic;
    if (inputGraphics) this.boundsGraphic = inputGraphics;
    if (graphics && graphics.geometry && this.body) {
      graphics.clear();
      graphics.lineStyle(2, '#ff0000');
      const path = this.body.vertices.map((item) => {
        return {
          x: item.x - this.body.position.x,
          y: item.y - this.body.position.y,
        };
      });
      graphics.drawPolygon(path);
      this.boundsWidth = graphics.width;
      this.boundsHeight = graphics.height;
    }
  }
  //#endregion draw bounding box
}
