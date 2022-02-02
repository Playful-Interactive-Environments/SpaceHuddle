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
    <div class="overlay">
      <div class="icon link" v-on:click="isShaking()">
        <font-awesome-icon :icon="['fac', 'shake']" />
      </div>
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
import NoSleep from 'nosleep.js';

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
  noSleep!: NoSleep;

  changeText(): void {
    if (this.randomIdea && this.$refs.textAnimation) {
      this.bodies.clearTexts();
      //this.bodies.startAnimation(50);
      const textAnimation = this.$refs.textAnimation as any;
      const rectAnimation = textAnimation.getBoundingClientRect();
      textAnimation.childNodes.forEach((span) => {
        if (span.tagName === 'SPAN') {
          const rect = span.getBoundingClientRect();
          this.bodies.addText(
            span.innerHTML,
            (rect.left + rect.right) / 2 - rectAnimation.x,
            rect.top + rect.height / 2,
            48,
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

  changeFullScreen = false;
  isInFullScreen(): boolean {
    const doc = document as any;
    return (
      !!document.fullscreenElement ||
      !!doc.webkitFullscreenElement ||
      !!doc.mozFullScreenElement ||
      !!doc.msFullscreenElement
    );
  }

  async requestFullscreen(): Promise<void> {
    if (!this.isInFullScreen()) {
      this.changeFullScreen = true;
      const docElm = document.documentElement as any;
      if (docElm.requestFullscreen) {
        await docElm.requestFullscreen();
      } else if (docElm.mozRequestFullScreen) {
        await docElm.mozRequestFullScreen();
      } else if (docElm.webkitRequestFullScreen) {
        await docElm.webkitRequestFullScreen();
      } else if (docElm.msRequestFullscreen) {
        await docElm.msRequestFullscreen();
      }
    }
  }

  async exitFullscreen(): Promise<void> {
    if (this.changeFullScreen && this.isInFullScreen()) {
      const doc = document as any;
      if (doc.exitFullscreen) {
        await doc.exitFullscreen();
      } else if (doc.webkitExitFullscreen) {
        await doc.webkitExitFullscreen();
      } else if (doc.mozCancelFullScreen) {
        await doc.mozCancelFullScreen();
      } else if (doc.msExitFullscreen) {
        await doc.msExitFullscreen();
      }
    }
  }

  async mounted(): Promise<void> {
    await this.requestFullscreen();
    window.screen.orientation
      .lock('portrait-primary')
      // eslint-disable-next-line @typescript-eslint/no-empty-function
      .catch(function () {});
    setTimeout(() => {
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
      this.startInterval();

      this.setupPhysics();
      this.drawingInterval = setInterval(() => {
        this.update_drawing();
      }, this.drawingIntervalTime);
      this.setupShaking();
    }, 100);
    this.noSleep = new NoSleep();
    this.noSleep.enable();
    //await this.getTaskIdeas();

    window.addEventListener('mousedown', this.mousedown);
    window.addEventListener('mouseup', this.mouseup);
    window.addEventListener('touchstart', this.mousedown);
    window.addEventListener('touchend', this.mouseup);
  }

  private mousedown(): void {
    this.bodies.pressBody();
  }

  private mouseup(): void {
    this.bodies.releaseBody();
  }

  setupShaking(): void {
    // eslint-disable-next-line @typescript-eslint/no-var-requires
    const Shake = require('shake.js');
    this.shakeEvent = new Shake({ threshold: 15, timeout: 1000 });
    this.shakeEvent.start();
    window.addEventListener('shake', this.isShaking, false);
  }

  shakingStartTime = 0;
  lastShakingTime = 0;
  maxShakingDelay = 4 * 1000;
  isShaking(): void {
    const animationInterval = 1000;
    const shakingTime = Date.now();
    const actualShakingForce = (): number => {
      const actualTime = Date.now();
      const directionCount = Math.floor(
        (actualTime - this.shakingStartTime) / animationInterval
      );
      return directionCount % 2 === 0 ? 20 : -10;
    };
    const animateShaking = (): void => {
      if (this.lastShakingTime === shakingTime) {
        this.bodies.addShakingForce(actualShakingForce());
        setTimeout(() => {
          const animationTime = Date.now();
          if (shakingTime + this.maxShakingDelay > animationTime) {
            animateShaking();
          }
        }, animationInterval);
      }
    };

    this.lastShakingTime = shakingTime;

    if (this.bodies.startAnimation(50)) {
      this.shakingStartTime = shakingTime;
      this.getTaskIdeas();
    }
    animateShaking();
  }

  setupPhysics(): void {
    if (this.$refs.canvas) {
      this.bodies = new CanvasBodies(
        this.vueCanvas,
        this.vueCanvasWidth,
        this.vueCanvasHeight,
        this.$refs.canvas as HTMLCanvasElement
      );
      const letterCount = 26;
      const circleCount = 100;
      const fillFactor = 1.5;
      const areaPerCircle =
        (this.vueCanvasWidth * this.vueCanvasHeight) / circleCount / fillFactor;
      const maxRadius = Math.sqrt(areaPerCircle / Math.PI);
      const minRadius = maxRadius / 2;
      for (let i = 0; i < circleCount; i++) {
        const r = Math.floor(Math.random() * (maxRadius - minRadius) + minRadius);
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
  }

  onOrientationChange(ev: DeviceOrientationEvent): void {
    const vec = this.deviceOrientationEventToVector(ev);
    this.bodies.setGravity(-vec[0], vec[1], vec[1]);
  }

  deviceOrientationEventToVector(ev: DeviceOrientationEvent): [number, number, number] {
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
      return q.conjugate().rotateVector([0, 0, 1]) as [number, number, number];
    }
    return [0, 0, 0];
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
    //this.interval = setInterval(this.getTaskIdeas, this.intervalTime);
  }

  async unmounted(): Promise<void> {
    await this.exitFullscreen();
    this.noSleep.disable();
    clearInterval(this.interval);
    clearInterval(this.drawingInterval);
    window.removeEventListener('shake', this.isShaking, false);
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
        setTimeout(() => {
          this.changeText();
        }, 100);
      });
  }

  draw(e: MouseEvent): void {
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
  margin: 0;
  position: absolute;
  top: 50%;
  left: 50%;
  -ms-transform: translate(-50%, -50%);
  transform: translate(-50%, -50%);
  width: 100%;
  z-index: -1;
  text-align: center;
  font-family: Arial, sans-serif;
  font-size: 48px;
  padding: 0 5rem;
}

.overlay {
  margin: 0;
  position: absolute;
  bottom: 10%;
  width: 100%;
  z-index: 10;
  text-align: center;
  font-family: Arial, sans-serif;
  font-size: 12px;
  white-space: pre-line;
}

html,
body {
  height: 100%;
}

body {
  overflow: hidden;
}

.icon {
  font-size: 72pt;
  color: #ffffff77;
}
</style>
