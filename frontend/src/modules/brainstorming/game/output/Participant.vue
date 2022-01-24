<template>
  <div class="canvas-container">
    <canvas
      ref="canvas"
      id="canvas"
      width="560"
      height="360"
      v-on:mousemove="draw"
    />
    <div class="text-animation" v-if="randomIdea" ref="textAnimation">
      <span v-for="(char, index) in randomIdea.keywords" :key="index">
        {{ char }}
      </span>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/ParticipantModuleDefaultContainer.vue';
import * as ideaService from '@/services/idea-service';
import * as moduleService from '@/services/module-service';
import { Idea } from '@/types/api/Idea.ts';
import { Module } from '@/types/api/Module';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { CanvasBodies } from '@/types/ui/CanvasBodies';

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const orientations: string[][] = [
  ['landscape left', 'landscape right'], // device x axis points up/down
  ['portrait', 'portrait upside down'], // device y axis points up/down
  ['display up', 'display down'], // device z axis points up/down
];

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const rad: number = Math.PI / 180;

@Options({
  components: {
    ParticipantModuleDefaultContainer,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  module: Module | null = null;
  readonly intervalTime = 100000;
  interval!: any;

  randomIdea: Idea | null = null;

  vueCanvas!: CanvasRenderingContext2D;
  readonly drawingIntervalTime = 100;
  drawingInterval!: any;
  mousePos: any = null;
  bodies!: CanvasBodies;
  shakeEvent: any;

  changeText(): void {
    if (this.randomIdea && this.$refs.textAnimation) {
      this.bodies.clearTexts();
      this.bodies.startAnimation();
      const textAnimation = this.$refs.textAnimation as any;
      const rectAnimation = textAnimation.getBoundingClientRect();
      textAnimation.childNodes.forEach((span) => {
        if (span.tagName === 'SPAN') {
          const rect = span.getBoundingClientRect();
          this.bodies.addText(
            span.innerHTML,
            (rect.left + rect.right) / 2 - rectAnimation.x,
            (rect.top + rect.bottom) / 2 - rectAnimation.y,
            70,
            '#FFFFFFFF'
          );
        }
      });
    }
  }

  get vueCanvasWidth(): number {
    if (this.$refs.canvas) {
      return (this.$refs.canvas as any).width;
    }
    return 0;
  }

  get vueCanvasHeight(): number {
    if (this.$refs.canvas) {
      return (this.$refs.canvas as any).height;
    }
    return 0;
  }

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  async mounted(): Promise<void> {
    if (this.$refs.canvas) {
      (this.$refs.canvas as any).width = (
        this.$refs.canvas as any
      ).parentElement.offsetWidth;
      (this.$refs.canvas as any).height = (
        this.$refs.canvas as any
      ).parentElement.offsetHeight;
      this.vueCanvas = (this.$refs.canvas as any).getContext('2d');
    }
    window.addEventListener('deviceorientation', this.onOrientationChange);
    await this.getTaskIdeas();
    this.startInterval();

    this.setupPhysics();
    this.drawingInterval = setInterval(() => {
      this.update_drawing();
    }, this.drawingIntervalTime);
    this.setupShaking();
  }

  setupShaking(): void {
    // eslint-disable-next-line @typescript-eslint/no-var-requires
    const Shake = require('shake.js');
    this.shakeEvent = new Shake({ threshold: 15 });
    this.shakeEvent.start();
    window.addEventListener('shake', this.isShaking, false);
  }

  isShaking(): void {
    this.getTaskIdeas();
    //this.changeText();
  }

  setupPhysics(): void {
    this.bodies = new CanvasBodies(
      this.vueCanvas,
      this.vueCanvasWidth,
      this.vueCanvasHeight
    );
    const letterCount = 26;
    for (let i = 0; i < 100; i++) {
      const r = Math.floor(Math.random() * 15 + 15);
      const x = Math.floor(Math.random() * (this.vueCanvasWidth - r * 2) + r);
      const y = Math.floor(Math.random() * (this.vueCanvasHeight - r * 2) + r);
      const a = 'A';
      const text = String.fromCharCode(a.charCodeAt(0) + (i % letterCount));
      this.bodies.addCircle(x, y, r, { text: text, gradientSize: r });
    }
    const borderSize = 1;
    this.bodies.addRect(
      this.vueCanvasWidth / 2,
      this.vueCanvasHeight - borderSize / 2,
      this.vueCanvasWidth,
      borderSize,
      { isStatic: true }
    );
    this.bodies.addRect(
      this.vueCanvasWidth / 2,
      borderSize / 2,
      this.vueCanvasWidth,
      borderSize,
      { isStatic: true }
    );
    this.bodies.addRect(
      borderSize / 2,
      this.vueCanvasHeight / 2,
      borderSize,
      this.vueCanvasHeight,
      { isStatic: true }
    );
    this.bodies.addRect(
      this.vueCanvasWidth - borderSize / 2,
      this.vueCanvasHeight / 2,
      borderSize,
      this.vueCanvasHeight,
      { isStatic: true }
    );
  }

  onOrientationChange(ev: DeviceOrientationEvent): void {
    if (ev.alpha && ev.beta && ev.gamma) {
      // eslint-disable-next-line @typescript-eslint/no-var-requires
      const Quaternion = require('quaternion');
      const q = Quaternion.fromEuler(
        ev.alpha * rad,
        ev.beta * rad,
        ev.gamma * rad,
        'ZXY'
      );
      // transform an upward-pointing vector to device coordinates
      const vec = q.conjugate().rotateVector([0, 0, 1]);
      this.bodies.setGravity(vec[0], vec[1]);
    }
  }

  @Watch('moduleId', { immediate: true })
  onModuleIdChanged(): void {
    this.getModule();
  }

  async getModule(): Promise<void> {
    if (this.moduleId) {
      await moduleService
        .getModuleById(this.moduleId, EndpointAuthorisationType.PARTICIPANT)
        .then((module) => {
          this.module = module;
        });
    }
  }

  startInterval(): void {
    this.interval = setInterval(this.getTaskIdeas, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
    clearInterval(this.drawingInterval);
    this.shakeEvent.stop();
  }

  async getTaskIdeas(): Promise<void> {
    ideaService
      .getIdeasForTask(
        this.taskId,
        null,
        null,
        EndpointAuthorisationType.PARTICIPANT
      )
      .then((queryResult) => {
        const randomIndex = Math.floor(Math.random() * queryResult.length);
        this.randomIdea = queryResult[randomIndex];
      });

    this.changeText();
  }

  draw(e: any): void {
    this.mousePos = [e.offsetX, e.offsetY];
  }

  async update_drawing(): Promise<void> {
    this.bodies.show();
  }
}
</script>

<style lang="scss" scoped>
.canvas-container {
  color: #fff;
  background: var(--color-darkblue);
  background-image: url('~@/assets/illustrations/background.png');
  background-attachment: fixed;
  background-repeat: no-repeat;
  background-size: cover;
  height: 100vh;
  width: 100%;
  position: relative;
}

.text-animation {
  position: absolute;
  height: 100vh;
  width: 100%;
  top: 0;
  z-index: -1;
  text-align: center;
  vertical-align: middle;
  line-height: 100vh;
  font-family: Arial, sans-serif;
  font-size: 70px;
}

html,
body {
  height: 100%;
}

body {
  overflow: hidden;
}
</style>
