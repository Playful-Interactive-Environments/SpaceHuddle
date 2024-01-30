<template>
  <div class="canvas-container" ref="container">
    <PhysicsContainer
      v-if="mode !== CanvasMode.GameEngine && animationTimeline"
      :canvas-mode="mode"
      :animation-timeline="animationTimeline"
      :gravity="gravity"
    />
    <div
      class="text-animation text-animation-center"
      v-if="randomIdea"
      ref="textAnimation"
    >
      <span v-for="(char, index) in randomIdea.keywords" :key="index">
        {{ char }}
      </span>
    </div>
    <div
      class="noInteraction text-animation text-animation-bottom"
      ref="textAnimationKeepShaking"
    >
      <span
        v-for="(char, index) in $t(
          'module.brainstorming.game.participant.keepShaking'
        )"
        :key="index"
      >
        {{ char }}
      </span>
    </div>
    <div
      class="noInteraction text-animation text-animation-bottom text-animation-small"
      ref="textAnimationStartShaking"
    >
      <span
        v-for="(char, index) in $t(
          'module.brainstorming.game.participant.startShaking'
        )"
        :key="index"
      >
        {{ char }}
      </span>
    </div>
    <div
      class="noInteraction text-animation text-animation-center"
      ref="textAnimationNoInspiration"
    >
      <span
        v-for="(char, index) in $t(
          'module.brainstorming.game.participant.noInspiration'
        )"
        :key="index"
      >
        {{ char }}
      </span>
    </div>
    <div class="noInteraction overlay disable-text-selection">
      <span class="awesome-icon link" v-on:click="isShaking()">
        <font-awesome-icon :icon="['fac', 'shake']" />
      </span>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/layout/ParticipantModuleDefaultContainer.vue';
import * as moduleService from '@/services/module-service';
import { Idea } from '@/types/api/Idea';
import { Module } from '@/types/api/Module';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { PhysicBodies } from '@/modules/brainstorming/game/types/PhysicBodies';
import { AnimationTimeline } from '@/modules/brainstorming/game/types/AnimationTimeline';
import Canvas from '@/modules/brainstorming/game/organisms/Canvas.vue';
import NoSleep from 'nosleep.js';
import * as viewService from '@/services/view-service';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import * as ideaService from '@/services/idea-service';
import * as cashService from '@/services/cash-service';
import { registerDomElement, unregisterDomElement } from '@/vunit';
import { CanvasMode } from './ModeratorConfig.vue';
import { isMobile } from '@/utils/dom';
import PhysicsContainer from '@/modules/brainstorming/game/organisms/PhysicsContainer.vue';

// eslint-disable-next-line @typescript-eslint/no-var-requires
const o9n = require('o9n');

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const orientations: string[][] = [
  ['landscape left', 'landscape right'], // device x axis points up/down
  ['portrait', 'portrait upside down'], // device y axis points up/down
  ['display up', 'display down'], // device z axis points up/down
];

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const rad: number = Math.PI / 180;

export enum TextType {
  Inspiration = 0,
  StartShaking = 1,
  KeepShaking = 2,
}

@Options({
  components: {
    PhysicsContainer,
    ParticipantModuleDefaultContainer,
    Canvas,
  },
  emits: ['update:useFullSize', 'update:backgroundClass'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  @Prop({ default: false }) readonly useFullSize!: boolean;
  @Prop({ default: '' }) readonly backgroundClass!: string;
  task: Task | null = null;
  module: Module | null = null;

  randomIdea: Idea | null = null;

  mode: CanvasMode = CanvasMode.Canvas;
  CanvasMode = CanvasMode;

  physicBodies: PhysicBodies | null = null;
  animationTimeline: AnimationTimeline | null = null;
  gravity: [number, number, number] = [0, 1, 0];
  shakeEvent: any;
  noSleep!: NoSleep;

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.PARTICIPANT,
      60 * 60
    );
    viewService.registerGetInputIdeas(
      this.taskId,
      IdeaSortOrder.TIMESTAMP,
      null,
      this.updateInputIdeas,
      EndpointAuthorisationType.PARTICIPANT,
      30
    );
    ideaService.registerGetIdeasForTask(
      this.taskId,
      IdeaSortOrder.TIMESTAMP,
      null,
      this.updateTaskIdeas,
      EndpointAuthorisationType.PARTICIPANT,
      30
    );
  }

  updateTask(task: Task): void {
    this.task = task;
  }

  inputIdeas: Idea[] = [];
  updateInputIdeas(ideas: Idea[]): void {
    this.inputIdeas = ideas;
    this.updateIdeas();
  }

  taskIdeas: Idea[] = [];
  updateTaskIdeas(ideas: Idea[]): void {
    this.taskIdeas = ideas;
    this.updateIdeas();
  }

  changeText(): void {
    if (this.randomIdea) {
      this.setBodyText(
        this.$refs.textAnimation,
        TextType.Inspiration,
        '#FFFFFFFF'
      );
    } else {
      this.setBodyText(
        this.$refs.textAnimationNoInspiration,
        TextType.Inspiration,
        '#FFFFFFFF'
      );
    }
  }

  setBodyText(
    // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
    textSpan: any,
    textId: number,
    color: string,
    animationPathCount = 8,
    textSize = 48
  ): void {
    const top = textSpan.parentNode.getBoundingClientRect().top;
    if (textSpan && this.animationTimeline) {
      this.animationTimeline.clearTexts(textId);
      const textAnimation = textSpan as any;
      const rectAnimation = textAnimation.getBoundingClientRect();
      for (const span of textAnimation.childNodes) {
        if (span.tagName === 'SPAN') {
          const rect = span.getBoundingClientRect();
          this.animationTimeline.addText(
            span.innerHTML,
            (rect.left + rect.right) / 2 - rectAnimation.x,
            rect.top + rect.height / 2 - top,
            textSize,
            color,
            0,
            textId,
            animationPathCount
          );
        }
      }
    }
  }

  containerWidth = 100;
  containerHeight = 100;

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

  domKey = '';
  async mounted(): Promise<void> {
    if (isMobile()) await this.requestFullscreen();
    this.$emit('update:useFullSize', true);
    this.$emit('update:backgroundClass', 'star-background');
    await o9n.orientation
      .lock('portrait-primary')
      // eslint-disable-next-line @typescript-eslint/no-empty-function
      .catch(function () {});
    this.domKey = registerDomElement(
      this.$refs.container as HTMLElement,
      (targetWidth, targetHeight) => {
        this.containerWidth = targetWidth;
        this.containerHeight = targetHeight;
        this.setupAnimationTimeline();
        window.addEventListener('deviceorientation', this.onOrientationChange);
        this.setupShaking();
      },
      0
    );

    this.noSleep = new NoSleep();
    this.noSleep.enable();
  }

  setupShaking(): void {
    // eslint-disable-next-line @typescript-eslint/no-var-requires
    const Shake = require('shake.js');
    this.shakeEvent = new Shake({ threshold: 10, timeout: 500 });
    this.shakeEvent.start();
    window.addEventListener('shake', this.isShaking, false);
  }

  shakingStartTime = 0;
  lastShakingTime = 0;
  maxShakingDelay = 4 * 1000;
  isShaking(): void {
    const shakingTime = Date.now();
    /*const animationInterval = 1000;
    const actualShakingForce = (): number => {
      const actualTime = Date.now();
      const directionCount = Math.floor(
        (actualTime - this.shakingStartTime) / animationInterval
      );
      return directionCount % 2 === 0 ? 20 : -10;
    };
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const animateShaking = (): void => {
      if (this.lastShakingTime === shakingTime && this.physicBodies) {
        this.physicBodies.addShakingForce(actualShakingForce());
        setTimeout(() => {
          const animationTime = Date.now();
          if (shakingTime + this.maxShakingDelay > animationTime) {
            // eslint-disable-next-line @typescript-eslint/no-unused-vars
            animateShaking();
          }
        }, animationInterval);
      }
    };*/

    this.lastShakingTime = shakingTime;

    if (
      this.animationTimeline &&
      this.animationTimeline.startAnimation(
        50,
        TextType.Inspiration,
        TextType.StartShaking,
        TextType.KeepShaking
      )
    ) {
      this.shakingStartTime = shakingTime;
      this.chooseRandomIdea();
    }
  }

  setupAnimationTimeline(): void {
    this.animationTimeline = new AnimationTimeline(
      this.containerWidth,
      this.containerHeight
    );
    this.setBodyText(
      this.$refs.textAnimationStartShaking,
      TextType.StartShaking,
      '#FFFFFF44',
      1,
      24
    );
    this.setBodyText(
      this.$refs.textAnimationKeepShaking,
      TextType.KeepShaking,
      '#FFFFFF44',
      1
    );
  }

  onOrientationChange(ev: DeviceOrientationEvent): void {
    const vec = this.deviceOrientationEventToVector(ev);
    this.gravity = [-vec[0], vec[1], vec[1]];
  }

  deviceOrientationEventToVector(
    ev: DeviceOrientationEvent
  ): [number, number, number] {
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
    if (this.moduleId) {
      moduleService.registerGetModuleById(
        this.moduleId,
        this.updateModule,
        EndpointAuthorisationType.PARTICIPANT,
        60 * 60
      );
    }
  }

  updateModule(module: Module): void {
    this.module = module;
    if (module.parameter.mode) this.mode = module.parameter.mode;
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateModule);
    cashService.deregisterAllGet(this.updateInputIdeas);
    cashService.deregisterAllGet(this.updateIdeas);
    cashService.deregisterAllGet(this.updateTaskIdeas);
  }

  async unmounted(): Promise<void> {
    await this.exitFullscreen();
    if (this.noSleep) this.noSleep.disable();
    window.removeEventListener('shake', this.isShaking, false);
    window.removeEventListener('deviceorientation', this.onOrientationChange);
    this.shakeEvent.stop();
    this.deregisterAll();
    unregisterDomElement(this.domKey);
  }

  allIdeas: Idea[] = [];
  updateIdeas(): void {
    this.allIdeas = viewService.customizeView(
      [...this.inputIdeas, ...this.taskIdeas],
      null,
      (this as any).$t,
      [],
      '',
      this.task ? this.task.parameter.input.length : 1
    );
    this.chooseRandomIdea();
  }

  chooseRandomIdea(): void {
    const randomIndex = Math.floor(Math.random() * this.allIdeas.length);
    this.randomIdea = this.allIdeas[randomIndex];
    setTimeout(() => {
      this.changeText();
    }, 100);
  }
}
</script>

<style lang="scss">
.star-background {
  background: var(--color-dark-contrast);
  background-image: url('~@/assets/illustrations/background.png');
  background-attachment: fixed;
  background-repeat: no-repeat;
  background-size: cover;
}
</style>

<style lang="scss" scoped>
.canvas-container {
  color: #fff;
  flex-grow: 1;
  top: 0;
  width: 100%;
  position: relative;
}

.text-animation {
  margin: 0;
  position: absolute;
  left: 50%;
  -ms-transform: translate(-50%, -50%);
  transform: translate(-50%, -50%);
  width: 100%;
  z-index: -1;
  text-align: center;
  font-family: Arial, sans-serif;
  font-size: 48px;
  padding: 0 5rem;

  &-center {
    top: 50%;
  }

  &-bottom {
    bottom: 15%;
  }

  &-small {
    font-size: 24px;
  }
}

.overlay {
  margin: 0;
  position: absolute;
  bottom: 5%;
  width: 100%;
  z-index: 10;
  text-align: center;
  font-family: Arial, sans-serif;
  font-size: 12px;
  white-space: pre-line;
}

.noInteraction {
  pointer-events: none;
}

.link {
  pointer-events: auto;
}

html,
body {
  height: 100%;
}

body {
  overflow: hidden;
}

.awesome-icon {
  font-size: 72pt;
  color: #ffffff77;
}
</style>
