<template>
  <div ref="container">
    <Canvas
      v-if="canvasMode === CanvasMode.Canvas && physicBodies"
      :physic-bodies="physicBodies"
      :animation-timeline="animationTimeline"
    />
    <PixiCanvas
      v-else-if="canvasMode === CanvasMode.Pixi && physicBodies"
      :physic-bodies="physicBodies"
      :animation-timeline="animationTimeline"
    />
    <VuePixiCanvas
      v-else-if="canvasMode === CanvasMode.PixiVue && physicBodies"
      :physic-bodies="physicBodies"
      :animation-timeline="animationTimeline"
    />
    <VuePixiCanvasWrapped
      v-else-if="canvasMode === CanvasMode.PixiVueWrapped && physicBodies"
      :physic-bodies="physicBodies"
      :animation-timeline="animationTimeline"
    />
    <div class="frameInfo">{{ Math.round(1000 / frameDelta) }}fps</div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import Canvas from '@/modules/brainstorming/game/organisms/Canvas.vue';
import { PhysicBodies } from '@/modules/brainstorming/game/types/PhysicBodies';
import { AnimationTimeline } from '@/modules/brainstorming/game/types/AnimationTimeline';
import { CanvasMode } from '@/modules/brainstorming/game/output/ModeratorConfig.vue';
import { registerDomElement, unregisterDomElement } from '@/vunit';
import PixiCanvas from '@/modules/brainstorming/game/organisms/PixiCanvas.vue';
import VuePixiCanvas from '@/modules/brainstorming/game/organisms/VuePixiCanvas.vue';
import VuePixiCanvasWrapped from '@/modules/brainstorming/game/organisms/VuePixiCanvasWrapped.vue';

@Options({
  components: {
    VuePixiCanvasWrapped,
    VuePixiCanvas,
    PixiCanvas,
    Canvas,
  },
  emits: [],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PhysicsContainer extends Vue {
  @Prop({ default: CanvasMode.None }) readonly canvasMode!: CanvasMode;
  @Prop() readonly animationTimeline!: AnimationTimeline;
  @Prop({ default: [0, -1, 0] }) readonly gravity!: [number, number, number];
  physicBodies: PhysicBodies | null = null;
  CanvasMode = CanvasMode;

  containerWidth = 100;
  containerHeight = 100;

  domKey = '';
  async mounted(): Promise<void> {
    this.domKey = registerDomElement(
      this.$refs.container as HTMLElement,
      (targetWidth, targetHeight) => {
        this.containerWidth = targetWidth;
        this.containerHeight = targetHeight;
        this.setupPhysics();
      },
      0
    );

    window.addEventListener('mousedown', this.mousedown);
    window.addEventListener('mouseup', this.mouseup);
    window.addEventListener('touchstart', this.mousedown);
    window.addEventListener('touchend', this.mouseup);
  }

  async unmounted(): Promise<void> {
    unregisterDomElement(this.domKey);
    window.removeEventListener('mousedown', this.mousedown);
    window.removeEventListener('mouseup', this.mouseup);
    window.removeEventListener('touchstart', this.mousedown);
    window.removeEventListener('touchend', this.mouseup);
    if (this.physicBodies) this.physicBodies.destroy();
  }

  private mousedown(): void {
    if (this.physicBodies) this.physicBodies.pressBody();
  }

  private mouseup(): void {
    if (this.physicBodies) this.physicBodies.releaseBody();
  }

  setupPhysics(): void {
    this.physicBodies = new PhysicBodies(
      this.containerWidth,
      this.containerHeight,
      this.$refs.container as HTMLElement,
      this.animationTimeline
    );
    const letterCount = 26;
    const circleCount = 100;
    const fillFactor = 1.5;
    const areaPerCircle =
      (this.containerWidth * this.containerHeight) / circleCount / fillFactor;
    const maxRadius = Math.sqrt(areaPerCircle / Math.PI);
    const minRadius = maxRadius / 2;
    for (let i = 0; i < circleCount; i++) {
      const r = Math.floor(Math.random() * (maxRadius - minRadius) + minRadius);
      const x = Math.floor(Math.random() * (this.containerWidth - r * 2) + r);
      const y = Math.floor(Math.random() * (this.containerHeight - r * 2) + r);
      const a = 'A';
      const text = String.fromCharCode(a.charCodeAt(0) + (i % letterCount));
      this.physicBodies.addCircle(x, y, r, { text: text, gradientSize: r });
    }
    const borderSize = 1;
    this.physicBodies.addRect(
      this.containerWidth / 2,
      this.containerHeight - borderSize / 2,
      this.containerWidth,
      borderSize,
      { isStatic: true, isHidden: true }
    );
    this.physicBodies.addRect(
      this.containerWidth / 2,
      borderSize / 2,
      this.containerWidth,
      borderSize,
      { isStatic: true, isHidden: true }
    );
    this.physicBodies.addRect(
      borderSize / 2,
      this.containerHeight / 2,
      borderSize,
      this.containerHeight,
      { isStatic: true, isHidden: true }
    );
    this.physicBodies.addRect(
      this.containerWidth - borderSize / 2,
      this.containerHeight / 2,
      borderSize,
      this.containerHeight,
      { isStatic: true, isHidden: true }
    );
    this.physicBodies.addEvent('afterUpdate', this.calcFPS);
  }

  @Watch('gravity', { immediate: true })
  onGravityChanged(): void {
    if (this.physicBodies)
      this.physicBodies.setGravity(
        this.gravity[0],
        this.gravity[1],
        this.gravity[2]
      );
  }

  updateTime = Date.now();
  frameDelta = 0;
  frameDeltaList: number[] = [];
  calcFPS(): void {
    const updateTime = Date.now();
    const deltaTime = updateTime - this.updateTime;
    this.updateTime = updateTime;
    this.frameDeltaList.splice(0, 0, deltaTime);
    if (this.frameDeltaList.length > 30) this.frameDeltaList.length = 30;
    this.frameDelta =
      this.frameDeltaList.reduce((sum, a) => sum + a, 0) /
      this.frameDeltaList.length;
  }
}
</script>

<style lang="scss" scoped>
.frameInfo {
  pointer-events: none;
  bottom: 1rem;
  left: 1rem;
  font-size: var(--font-size-xxxlarge);
  position: absolute;
  z-index: 100;
}
</style>
