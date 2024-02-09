<template>
  <container
    :x="position[0]"
    :y="position[1]"
    :rotation="rotationValue"
    :scale="scale"
    @render="containerLoad"
  >
    <slot></slot>
  </container>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as PIXI from 'pixi.js';
import { CollisionHandler } from '@/types/game/CollisionHandler';
import GameContainer from '@/components/shared/atoms/game/GameContainerLite2.vue';
import { delay, until } from '@/utils/wait';
import { EventType } from '@/types/enum/EventType';
import { v4 as uuidv4 } from 'uuid';

const logStartCalls = false;
const logEndCalls = false;

@Options({
  components: {},
  emits: [
    'update:x',
    'update:y',
    'update:id',
    'update:rotation',
    'destroyObject',
    'collision',
    'click',
    'release',
    'update:highlighted',
    'positionChanged',
    'initError',
  ],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class GameObject extends Vue {
  //#region props
  @Prop({ default: 100 }) renderDelay!: number;
  @Prop({ default: 0 }) id!: number;
  @Prop({ default: 0 }) x!: number;
  @Prop({ default: 0 }) y!: number;
  @Prop({ default: null }) fixSize!: [number, number] | number | null;
  @Prop({ default: 0 }) rotation!: number;
  @Prop({ default: 1 }) scale!: number;
  @Prop({ default: 'rect' }) readonly type!: 'rect' | 'circle' | 'polygon';
  @Prop({ default: [] }) readonly polygonShape!: [number, number][];
  @Prop({ default: 0 }) readonly colliderDelta!: number;
  @Prop({ default: {} }) readonly options!: {
    [key: string]: string | number | boolean | object;
  };
  @Prop({ default: false }) readonly isStatic!: boolean;
  @Prop({ default: true }) readonly clickable!: boolean;
  @Prop() readonly collisionHandler!: CollisionHandler;
  @Prop() readonly source!: any;
  @Prop({ default: true }) usePhysic!: boolean;
  @Prop({ default: true }) keepInside!: boolean;
  @Prop({ default: true }) affectedByForce!: boolean;
  @Prop({ default: false }) highlighted!: boolean;
  @Prop({ default: 0 }) anchor!: number | [number, number];
  @Prop({ default: 0 }) zIndex!: number;
  //#endregion props

  //#region variables
  position: [number, number] = [0, 0];
  rotationValue = 0;
  containerSize: PIXI.Container | null = null;
  containerPosition: PIXI.Container | null = null;
  gameContainer!: GameContainer;
  readonly defaultSize = 50;
  displayWidth = this.defaultSize;
  displayHeight = this.defaultSize;
  destroyed = false;
  loadingFinished = false;
  isPartOfEngin = false;
  uuid = uuidv4();
  //#endregion variables

  //#region get / set
  getContainerWidth(): number {
    //if (logStartCalls) console.log('getContainerWidth');
    if (this.fixSize === null)
      return this.containerSize ? this.containerSize.width : 100;
    if (Array.isArray(this.fixSize)) return this.fixSize[0];
    return this.fixSize;
  }

  getContainerHeight(): number {
    //if (logStartCalls) console.log('getContainerHeight');
    if (this.fixSize === null)
      return this.containerSize ? this.containerSize.height : 100;
    if (Array.isArray(this.fixSize)) return this.fixSize[1];
    return this.fixSize;
  }
  //#endregion get / set

  //#region load / unload
  async mounted(): Promise<void> {
    if (logStartCalls) console.log('mounted');
    this.eventBus.emit(EventType.REGISTER_GAME_OBJECT, {
      gameObject: this,
    });
    if (logEndCalls) console.log('mounted');
  }

  unmounted(): void {
    if (logStartCalls) console.log('unmounted');
    this.kill();
    if (logEndCalls) console.log('unmounted');
  }

  setGameContainer(gameContainer: GameContainer): void {
    if (logStartCalls) console.log('setGameContainer');
    this.gameContainer = gameContainer;
    this.initPosition();
    this.manageEngin();
    this.$emit('initialised', this);
    if (logEndCalls) console.log('setGameContainer');
  }

  kill(): void {
    if (logStartCalls) console.log('kill');
    this.destroyed = true;
    if (this.gameContainer) {
      this.gameContainer.deregisterGameObject(this);
    }
    setTimeout(() => {
      if (this.containerPosition) {
        const parent = this.containerPosition.parent;
        if (parent) {
          parent.removeChild(this.containerPosition as any);
        }
        this.containerPosition.destroy({ children: true });
      }
    }, 100);
    if (logEndCalls) console.log('kill');
  }
  //#endregion load / unload

  //#region init body
  setupBodyId(id: number): void {
    this.$emit('update:id', id);
    if (this.clickable) {
      this.manageEngin();
    }
  }

  index = 0;
  async containerLoad(container: PIXI.Container): Promise<void> {
    if (logStartCalls) console.log('containerLoad', this.containerSize);
    if (this.containerSize) return;

    this.containerSize = container;
    this.containerPosition = container;
    const delayTime = this.fixSize === null ? 0 : this.renderDelay;
    await delay(delayTime);
    await until(() => !!this.gameContainer);
    try {
      this.displayWidth = this.getContainerWidth();
      this.displayHeight = this.getContainerHeight();
    } catch (e) {
      this.$emit('initError', this);
    }
    if (logEndCalls) console.log('containerLoad');
  }
  //#endregion init body

  //#region engine
  addBodyToEngine(): void {
    /*if (logStartCalls) console.log('addBodyToEngine');
    if (this.gameContainer) {
      this.gameContainer.addGameObjectToEngin(this);
    }
    if (logEndCalls) console.log('addBodyToEngine');*/
  }

  manageEngin(): void {
    if (logStartCalls) console.log('manageEngin');
    if (!this.clickable) return;
    if (!this.isPartOfEngin) {
      this.addBodyToEngine();
    }
    if (logEndCalls) console.log('manageEngin');
  }
  //#endregion engine

  //#region position / rotation / scale
  initPosition(x: number | null = null, y: number | null = null): void {
    if (logStartCalls) console.log('initPosition');
    if (x === null) x = this.x;
    if (y === null) y = this.y;
    this.position = [x, y];
    if (logEndCalls) console.log('initPosition');
  }

  @Watch('x', { immediate: true })
  @Watch('y', { immediate: true })
  onModelValueChanged(): void {
    if (logStartCalls) console.log('onModelValueChanged');
    if (this.position[0] !== this.x || this.position[1] !== this.y) {
      this.initPosition();
    }
    if (logEndCalls) console.log('onModelValueChanged');
  }
  //#endregion position / rotation / scale

  notifyCollision() {
    //
  }
}
</script>

<style scoped lang="scss"></style>
