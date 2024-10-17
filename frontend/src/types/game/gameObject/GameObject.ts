import * as PIXI from 'pixi.js';
import { toDegrees, toRadians } from '@/utils/angle';
import ObjectPhysics from '@/types/game/gameObject/ObjectPhysics';
import GameContainerObject, {
  GameContainerObjectType,
  IGameContainerObject,
} from '@/types/game/GameContainerObject';
import { ObjectTransform } from '@/types/game/gameObject/ObjectTransform';
import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import { v4 as uuidv4 } from 'uuid';

export interface IGameObjectSource {
  gameObject: GameObject | null;
}

export interface ConditionalVelocity {
  velocity: { x: number; y: number };
  condition: (object: GameObject) => boolean;
}

export enum FastObjectBehaviour {
  none = 'none',
  circle = 'circle',
  bounce = 'bounce',
}

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class GameObject
  extends PIXI.Container
  implements IGameContainerObject
{
  readonly uuid = uuidv4();
  gameContainerObject: GameContainerObject;
  transformation: ObjectTransform;
  physcics: ObjectPhysics;
  shape: 'rect' | 'circle' | 'polygon' = 'rect';
  polygonShape: [number, number][] = [];
  colliderDelta = 0;
  showBounds = false;
  options: {
    [key: string]: string | number | boolean | object;
  } = {};
  poolingKey = 'gameObject';
  isPartOfEngin = false;
  moveWithBackground = true;

  //#region load / unload
  constructor() {
    super();
    this.gameContainerObject = new GameContainerObject(
      this,
      GameContainerObjectType.GAME_OBJECT
    );
    this.transformation = new ObjectTransform(this, this.gameContainerObject);
    this.physcics = new ObjectPhysics(this, this.transformation);
    this._scale = super.scale;
    this.addEventListener('added', this.isAdded);
  }

  private _isDestroyed = false;
  destroy(options?) {
    if (!this._isDestroyed) {
      this._isDestroyed = true;
      this.removeEventListener('added', this.isAdded);
      if (!options) options = {};
      options.children = true;
      this.physcics.gameObjectReleased();
      this.gameContainerObject.destroy(options);
      this.physcics.destroy(options);
      this.transformation.destroy(options);
      super.destroy(options);
    }
  }

  setGameContainer(gameContainer: GameContainer): void {
    this.gameContainerObject.setGameContainer(gameContainer);
    this.initPosition();
    //this.physcics.manageEngin();
  }

  initPosition(): void {
    this.physcics.initPosition(this._posX, this._posY);
  }
  //#endregion load / unload

  //#region props
  _posX = 0;
  get posX() {
    return this._posX;
  }
  set posX(value) {
    this._posX = value;
    const inputPosition = this.transformation.inputPosition;
    if (inputPosition[0] !== this.posX || inputPosition[1] !== this.posY) {
      this.initPosition();
    }
  }
  _posY = 0;
  get posY() {
    return this._posY;
  }
  set posY(value) {
    this._posY = value;
    const inputPosition = this.transformation.inputPosition;
    if (inputPosition[0] !== this.posX || inputPosition[1] !== this.posY) {
      this.initPosition();
    }
  }

  get x(): number {
    if (this.transform) return this.transform.position.x;
    return this.transformation.position[0];
  }

  set x(value: number) {
    this.transform.position.x = value;
  }

  get y(): number {
    if (this.transform) return this.transform.position.y;
    return this.transformation.position[1];
  }

  set y(value: number) {
    this.transform.position.y = value;
  }

  _angle = 0;
  get angle() {
    if (this.transform) return 360 - toDegrees(this.rotation);
    return this._angle;
  }
  set angle(value) {
    this._angle = value;
    this.rotation = toRadians(360 - value);
    this.physcics.updateRotation();
  }

  _scale: PIXI.ObservablePoint;
  get scale() {
    if (this.transform) return super.scale;
    return this._scale;
  }
  set scale(value) {
    this._scale = value;
    super.scale = value;
    this.physcics.updateScale();
  }

  get disabled() {
    return this.physcics.hasDisabled;
  }
  set disabled(value) {
    this.physcics.updateDisabled(value);
  }

  _isActive = true;
  get isActive() {
    return this._isActive;
  }
  set isActive(value) {
    this._isActive = value;
    this.physcics.manageEngin();
    //this.isVisibleInContainer = this.isVisible();
  }

  _source: IGameObjectSource | null = null;
  get source() {
    return this._source;
  }
  set source(value) {
    if (value) value.gameObject = this;
    this._source = value;
  }

  get clickable() {
    return this.physcics.clickable;
  }

  set clickable(value) {
    this.physcics.clickable = value;
  }

  get sleepIfNotVisible() {
    return this.physcics.sleepIfNotVisible;
  }

  set sleepIfNotVisible(value) {
    this.physcics.sleepIfNotVisible = value;
  }

  get isStatic() {
    return this.physcics.isStatic;
  }

  set isStatic(value) {
    this.physcics.isStatic = value;
  }

  get usePhysic() {
    return this.physcics.usePhysic;
  }

  set usePhysic(value) {
    this.physcics.usePhysic = value;
  }

  get keepInside() {
    return this.physcics.keepInside;
  }

  set keepInside(value) {
    this.physcics.keepInside = value;
  }

  get affectedByForce() {
    return this.physcics.affectedByForce;
  }

  set affectedByForce(value) {
    this.physcics.affectedByForce = value;
  }

  get fastObjectBehaviour() {
    return this.physcics.fastObjectBehaviour;
  }

  set fastObjectBehaviour(value) {
    this.physcics.fastObjectBehaviour = value;
  }

  get collisionHandler() {
    return this.physcics.collisionHandler;
  }

  set collisionHandler(value) {
    this.physcics.collisionHandler = value;
  }

  get objectAnchor() {
    return this.physcics.anchor;
  }

  set objectAnchor(value) {
    this.physcics.anchor = value;
  }

  get zIndex() {
    return super.zIndex;
  }

  set zIndex(value) {
    if (super.zIndex !== value) {
      super.zIndex = value;
      this.physcics.updateZIndex();
      if (this.parent) {
        this.parent.sortChildren();
      }
    }
  }

  get conditionalVelocity() {
    return this.physcics.conditionalVelocity;
  }

  set conditionalVelocity(value) {
    this.physcics.conditionalVelocity = value;
  }

  get fixSize() {
    return this.transformation.fixSize;
  }

  set fixSize(value) {
    this.transformation.fixSize = value;
  }

  get objectSpace() {
    return this.transformation.objectSpace;
  }
  set objectSpace(value) {
    this.transformation.objectSpace = value;
  }

  _highlighted = false;
  get highlighted() {
    return this._highlighted;
  }

  set highlighted(value) {
    this._highlighted = value;
    this.emit('highlighted_changed', value, this);
  }

  get colliderScaleFactor(): number {
    return this.physcics.colliderScaleFactor;
  }

  set colliderScaleFactor(value: number) {
    this.physcics.colliderScaleFactor = value;
  }
  //#endregion props

  //#region events
  //eslint-disable-next-line @typescript-eslint/no-unused-vars
  isAdded(container: any): void {
    if (this.parent) {
      this.parent.sortChildren();
    }
  }
  //#endregion events

  //#region trigger
  triggerStartTime: number | null = null;
  triggerPauseStartTime: null | number = null;
  _triggerDelay: number | null = null;
  get triggerDelay() {
    return this._triggerDelay;
  }

  set triggerDelay(value) {
    this._triggerDelay = value;
    this.startTriggerListener();
  }

  _triggerDelayPause = false;
  get triggerDelayPause() {
    return this._triggerDelayPause;
  }

  set triggerDelayPause(value) {
    this._triggerDelayPause = value;
    if (value) {
      this.triggerPauseStartTime = Date.now();
    } else {
      if (this.triggerStartTime && this.triggerPauseStartTime) {
        const delta = Date.now() - this.triggerPauseStartTime;
        this.triggerStartTime += delta;
      }
      this.triggerPauseStartTime = null;
    }
  }

  startTriggerListener(): void {
    if (this.triggerDelay) this.triggerStartTime = Date.now();
    else this.triggerStartTime = null;
  }

  checkTrigger(): boolean {
    if (
      !this.triggerDelayPause &&
      !this.triggerPauseStartTime &&
      this.triggerDelay &&
      this.triggerStartTime &&
      this.triggerStartTime + this.triggerDelay * 1000 < Date.now()
    ) {
      this.startTriggerListener();
      this.emit('handle_trigger', this);
      return true;
    }
    return false;
  }
  //#endregion trigger
}
