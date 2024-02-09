<template>
  <GameContainer
    v-if="canvasMode === CanvasMode.GameEngine"
    v-model:width="gameWidth"
    v-model:height="gameHeight"
    :backgroundAlpha="0"
    @initRenderer="initRenderer"
    :gravity="gravity"
    :wind-force="windForce"
  >
    <template v-slot:default>
      <container v-if="gameWidth && circleGradientTexture && showBodies">
        <GameObject
          v-for="body of bodyList"
          :key="body.id"
          :id="body.id"
          v-model:x="body.position.x"
          v-model:y="body.position.y"
          :fix-size="body.size * 2"
          :anchor="0.5"
          type="circle"
          :source="body"
          :move-with-background="false"
          @click="click"
          @release="release"
        >
          <sprite
            :texture="circleGradientTexture"
            :anchor="0.5"
            :width="body.size * 2"
            :height="body.size * 2"
            :alpha="alpha"
          />
          <text
            :style="{
              fontFamily: 'Arial',
              fontSize: body.size,
              fill: '#27133B',
            }"
            :anchor="0.5"
            :alpha="alpha"
          >
            {{ body.text }}
          </text>
        </GameObject>
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
    </template>
  </GameContainer>
  <!--
    :body-list="bodyList.length === circleCount ? bodyList : null"
  -->
  <GameContainerLite
    v-if="canvasMode === CanvasMode.GameEngineLite"
    v-model:width="gameWidth"
    v-model:height="gameHeight"
    :backgroundAlpha="0"
    @initRenderer="initRenderer"
    :gravity="gravity"
    :wind-force="windForce"
  >
    <template v-slot:default>
      <container v-if="gameWidth && circleGradientTexture && showBodies">
        <GameObjectLite
          v-for="body of bodyList"
          :key="body.id"
          :x="body.position.x"
          :y="body.position.y"
          :body-data="body"
          @click="click"
          @release="release"
        >
          <sprite
            :texture="circleGradientTexture"
            :anchor="0.5"
            :width="body.size * 2"
            :height="body.size * 2"
            :alpha="alpha"
          />
          <text
            :style="{
              fontFamily: 'Arial',
              fontSize: body.size,
              fill: '#27133B',
            }"
            :anchor="0.5"
            :alpha="alpha"
          >
            {{ body.text }}
          </text>
        </GameObjectLite>
        <!--<GameObjectLite
          v-for="body of bodyList"
          :key="body.id"
          :id="body.id"
          :x="body.position.x"
          :y="body.position.y"
          :fix-size="body.size * 2"
          :anchor="0.5"
          type="circle"
          :source="body"
          :move-with-background="false"
        >
          <sprite
            :texture="circleGradientTexture"
            :anchor="0.5"
            :width="body.size * 2"
            :height="body.size * 2"
            :alpha="alpha"
          />
          <text
            :style="{
              fontFamily: 'Arial',
              fontSize: body.size,
              fill: '#27133B',
            }"
            :anchor="0.5"
            :alpha="alpha"
          >
            {{ body.text }}
          </text>
        </GameObjectLite>-->
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
    </template>
  </GameContainerLite>
  <GameContainerLite2
    v-if="canvasMode === CanvasMode.GameEngineLite2"
    v-model:width="gameWidth"
    v-model:height="gameHeight"
    :backgroundAlpha="0"
    @initRenderer="initRenderer"
    :gravity="gravity"
    :wind-force="windForce"
  >
    <template v-slot:default>
      <container v-if="gameWidth && circleGradientTexture && showBodies">
        <GameObjectLite2
          v-for="body of bodyList"
          :key="body.id"
          :x="body.position.x"
          :y="body.position.y"
          :body-data="body"
          @click="click"
          @release="release"
        >
          <sprite
            :texture="circleGradientTexture"
            :anchor="0.5"
            :width="body.size * 2"
            :height="body.size * 2"
            :alpha="alpha"
          />
          <text
            :style="{
              fontFamily: 'Arial',
              fontSize: body.size,
              fill: '#27133B',
            }"
            :anchor="0.5"
            :alpha="alpha"
          >
            {{ body.text }}
          </text>
        </GameObjectLite2>
        <!--<GameObjectLite2
          v-for="body of bodyList"
          :key="body.id"
          :id="body.id"
          :x="body.position.x"
          :y="body.position.y"
          :fix-size="body.size * 2"
          :anchor="0.5"
          type="circle"
          :source="body"
          :move-with-background="false"
        >
          <sprite
            :texture="circleGradientTexture"
            :anchor="0.5"
            :width="body.size * 2"
            :height="body.size * 2"
            :alpha="alpha"
          />
          <text
            :style="{
              fontFamily: 'Arial',
              fontSize: body.size,
              fill: '#27133B',
            }"
            :anchor="0.5"
            :alpha="alpha"
          >
            {{ body.text }}
          </text>
        </GameObjectLite>-->
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
    </template>
  </GameContainerLite2>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import {
  AnimationTimeline,
  TextAnimationData,
} from '@/modules/brainstorming/game/types/AnimationTimeline';
import * as PIXI from 'pixi.js';
import * as pixiUtil from '@/utils/pixi';
import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import GameContainerLite from '@/components/shared/atoms/game/GameContainerLite.vue';
import GameContainerLite2 from '@/components/shared/atoms/game/GameContainerLite2.vue';
import GameObjectLite from '@/components/shared/atoms/game/GameObjectLite.vue';
import GameObjectLite2 from '@/components/shared/atoms/game/GameObjectLite2.vue';
import { CanvasMode } from '@/modules/brainstorming/game/output/ModeratorConfig.vue';
import * as Matter from 'matter-js/build/matter';

interface BodyData {
  id: number;
  position: { x: number; y: number };
  size: number;
  text: string;
  body: Matter.Body | null;
  options: { [key: string]: string | number | boolean };
  alpha: number;
}

@Options({
  components: {
    GameObject,
    GameContainer,
    GameObjectLite,
    GameObjectLite2,
    GameContainerLite,
    GameContainerLite2,
  },
  emits: [],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PixiCanvas extends Vue {
  @Prop({ default: CanvasMode.None }) readonly canvasMode!: CanvasMode;
  @Prop() readonly animationTimeline!: AnimationTimeline;
  @Prop({ default: [0, -1, 0] }) readonly gravity!: [number, number, number];
  circleGradientTexture: PIXI.Texture | null = null;
  gameWidth = 0;
  gameHeight = 0;
  textList: TextAnimationData[] = [];
  bodyList: BodyData[] = [];
  showBodies = true;
  windForce = 0;
  CanvasMode = CanvasMode;

  updateCallback = () => this.updateFrame();
  async mounted(): Promise<void> {
    this.animationTimeline.addAnimationUpdatedCallback(() =>
      this.updateFrame()
    );
  }

  async unmounted(): Promise<void> {
    this.animationTimeline.removeAnimationUpdatedCallback(this.updateCallback);
  }

  initRenderer(renderer: PIXI.Renderer): void {
    this.circleGradientTexture = pixiUtil.generateCircleGradientTexture(
      256,
      renderer,
      1,
      1
    );
    this.setupBodies();
  }

  circleCount = 100;
  setupBodies(): void {
    const letterCount = 26;
    const fillFactor = 1.5;
    const areaPerCircle =
      (this.gameWidth * this.gameHeight) / this.circleCount / fillFactor;
    const maxRadius = Math.sqrt(areaPerCircle / Math.PI);
    const minRadius = maxRadius / 2;
    for (let i = 0; i < this.circleCount; i++) {
      const r = Math.floor(Math.random() * (maxRadius - minRadius) + minRadius);
      const x = Math.floor(Math.random() * (this.gameWidth - r * 2) + r);
      const y = Math.floor(Math.random() * (this.gameHeight - r * 2) + r);
      const a = 'A';
      const text = String.fromCharCode(a.charCodeAt(0) + (i % letterCount));
      this.bodyList.push({
        id: i,
        position: { x: x, y: y },
        size: r,
        text: text,
        body: null,
        options: {},
        alpha: 1,
      });
    }
  }

  alpha = 1;
  updateFrame(): void {
    if (this.animationTimeline.getActiveInfoTextId() > -1)
      this.textList = this.animationTimeline.getActiveTextLetterList();
    else this.textList = [];
    this.alpha = this.animationTimeline.bodyOpacity() / 255;
    this.showBodies = this.alpha > 0;
    const frame = this.animationTimeline.getActiveKeyframeValue();
    if (frame && frame.useForce && this.alpha < 1) {
      this.windForce = (1 - this.alpha) * 50 + 100;
    } else this.windForce = 0;
  }

  private readonly pressFactor = 3;
  click(gameObject: GameObject): void {
    if (gameObject && gameObject.body) {
      Matter.Body.scale(gameObject.body, this.pressFactor, this.pressFactor);
    }
  }

  release(gameObject: GameObject): void {
    if (gameObject && gameObject.body) {
      Matter.Body.scale(
        gameObject.body,
        1 / this.pressFactor,
        1 / this.pressFactor
      );
    }
  }
}
</script>
