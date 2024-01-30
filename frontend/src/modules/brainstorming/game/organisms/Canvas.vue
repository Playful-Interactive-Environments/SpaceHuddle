<template>
  <canvas ref="canvas" id="canvas" width="560" height="360" />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/layout/ParticipantModuleDefaultContainer.vue';
import { CanvasBodies } from '@/modules/brainstorming/game/types/CanvasBodies';
import { registerDomElement, unregisterDomElement } from '@/vunit';
import { PhysicBodies } from '@/modules/brainstorming/game/types/PhysicBodies';
import { AnimationTimeline } from '@/modules/brainstorming/game/types/AnimationTimeline';

@Options({
  components: {
    ParticipantModuleDefaultContainer,
  },
  emits: [],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Canvas extends Vue {
  @Prop() readonly physicBodies!: PhysicBodies;
  @Prop() readonly animationTimeline!: AnimationTimeline;
  vueCanvas!: CanvasRenderingContext2D;
  readonly drawingIntervalTime = 100;
  drawingInterval!: any;
  bodies!: CanvasBodies;

  get vueCanvasWidth(): number {
    if (this.$refs.canvas) {
      return (this.$refs.canvas as HTMLCanvasElement).width;
    }
    return 0;
  }

  get vueCanvasHeight(): number {
    if (this.$refs.canvas) {
      return (this.$refs.canvas as HTMLCanvasElement).height;
    }
    return 0;
  }

  domKey = '';
  async mounted(): Promise<void> {
    const canvas = this.$refs.canvas as HTMLCanvasElement;
    this.domKey = registerDomElement(
      canvas,
      (targetWidth, targetHeight) => {
        canvas.width = targetWidth;
        canvas.height = targetHeight;
        if (!this.vueCanvas) {
          const context = canvas.getContext('2d');
          if (context) this.vueCanvas = context;
          this.bodies = new CanvasBodies(
            this.vueCanvas,
            this.physicBodies,
            this.animationTimeline,
            this.vueCanvasWidth,
            this.vueCanvasHeight
          );
          this.drawingInterval = setInterval(() => {
            this.update_drawing();
          }, this.drawingIntervalTime);
        }
      },
      100,
      false,
      () => {
        canvas.width = 100;
        canvas.height = 100;
      }
    );
  }

  async unmounted(): Promise<void> {
    clearInterval(this.drawingInterval);
    unregisterDomElement(this.domKey);
  }

  async update_drawing(): Promise<void> {
    this.bodies.show();
  }
}
</script>
