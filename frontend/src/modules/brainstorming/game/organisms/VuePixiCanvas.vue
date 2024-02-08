<template>
  <div ref="container">
    <Application
      ref="pixi"
      :width="containerWidth"
      :height="containerHeight"
      :backgroundAlpha="0"
      backgroundColor="#ffffff"
    >
      <container>
        <container
          v-if="circleGradientTexture && bodyList.length > 0 && showBodies"
        >
          <container
            v-for="body of bodyList"
            :key="body.id"
            :x="body.position.x"
            :y="body.position.y"
            :alpha="body.alpha"
          >
            <sprite
              :texture="circleGradientTexture"
              :anchor="0.5"
              :width="body.size * 2"
              :height="body.size * 2"
            />
            <text
              :style="{
                fontFamily: 'Arial',
                fontSize: body.size,
                fill: '#27133B',
              }"
              :anchor="0.5"
            >
              {{ body.text }}
            </text>
          </container>
        </container>
        <container v-if="textList.length > 0">
          <text
            v-for="text of textList"
            :key="text.id"
            :anchor="0.5"
            :x="text.x"
            :y="text.y"
            :rotation="text.rotation"
            :alpha="text.alpha"
            :style="{
              fontFamily: 'Arial',
              fontSize: text.fontSize,
              fill: text.color.substring(0, 7),
            }"
          >
            {{ text.text }}
          </text>
        </container>
      </container>
    </Application>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { registerDomElement, unregisterDomElement } from '@/vunit';
import { PhysicBodies } from '@/modules/brainstorming/game/types/PhysicBodies';
import {
  AnimationTimeline,
  TextAnimationData,
} from '@/modules/brainstorming/game/types/AnimationTimeline';
import * as PIXI from 'pixi.js';
import { Application } from 'vue3-pixi';
import * as pixiUtil from '@/utils/pixi';
import { delay, until } from '@/utils/wait';

interface BodyData {
  id: number;
  position: { x: number; y: number };
  size: number;
  text: string;
  body: Matter.Body;
  options: { [key: string]: string | number | boolean };
  alpha: number;
}

@Options({
  components: {
    Application,
  },
  emits: [],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PixiCanvas extends Vue {
  @Prop() readonly physicBodies!: PhysicBodies;
  @Prop() readonly animationTimeline!: AnimationTimeline;
  app!: PIXI.Application;
  circleGradientTexture: PIXI.Texture | null = null;
  containerWidth = 100;
  containerHeight = 100;
  bodyList: BodyData[] = [];
  textList: TextAnimationData[] = [];
  showBodies = true;

  calcBodyList(): BodyData[] {
    const list: BodyData[] = [];
    for (const [
      index,
      body,
    ] of this.physicBodies.engine.world.bodies.entries()) {
      if (body.isStatic) continue;
      const options = this.physicBodies.bodies[index];
      list.push({
        id: index,
        position: { ...body.position },
        size: options.gradientSize as number,
        text: options.text as string,
        body: body,
        options: options,
        alpha: 255,
      });
    }
    return list;
  }

  domKey = '';
  updateCallback = () => this.updateFrame();
  async mounted(): Promise<void> {
    const container = this.$refs.container as HTMLElement;
    this.domKey = registerDomElement(
      container,
      (targetWidth, targetHeight) => {
        this.containerWidth = targetWidth;
        this.containerHeight = targetHeight;
        this.physicBodies.addEvent('afterUpdate', this.update_drawing);
        this.bodyList = this.calcBodyList();
      },
      100,
      false
    );
    until(() => this.$refs.pixi).then(async () => {
      await delay(100);
      const pixi = this.$refs.pixi as typeof Application;
      if (pixi) {
        this.app = pixi.app;
        this.circleGradientTexture = pixiUtil.generateCircleGradientTexture(
          256,
          this.app.renderer as any,
          1,
          1
        );
      }
    });
    this.animationTimeline.addAnimationUpdatedCallback(() =>
      this.updateFrame()
    );
  }

  async unmounted(): Promise<void> {
    unregisterDomElement(this.domKey);
    this.animationTimeline.removeAnimationUpdatedCallback(this.updateCallback);
  }

  updateTime = Date.now();
  async update_drawing(): Promise<void> {
    const updateTime = Date.now();
    const deltaTime = updateTime - this.updateTime;
    this.updateTime = updateTime;
    let opacity = 255;
    if (this.animationTimeline.timeline.animationFrame !== -1) {
      const frame = this.animationTimeline.getActiveKeyframeValue();
      if (frame && frame.opacity !== undefined) {
        opacity = Math.floor(frame.opacity);
      }
    }
    for (const item of this.bodyList) {
      item.position.x = item.body.position.x;
      item.position.y = item.body.position.y;
      const options = item.options;
      item.alpha = options.isHidden ? 0 : opacity / 255;
    }
    //console.log(this.bodyList);
    console.log('afterPhysicUpdate', deltaTime, Date.now() - updateTime);
  }

  updateFrame(): void {
    if (this.animationTimeline.getActiveInfoTextId() > -1)
      this.textList = this.animationTimeline.getActiveTextLetterList();
    else this.textList = [];
    this.showBodies = this.animationTimeline.bodyOpacity() > 0;
  }
}
</script>
