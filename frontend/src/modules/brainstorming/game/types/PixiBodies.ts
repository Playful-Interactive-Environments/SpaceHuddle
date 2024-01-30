import * as Matter from 'matter-js/build/matter.js';
import { PhysicBodies } from '@/modules/brainstorming/game/types/PhysicBodies';
import { AnimationTimeline } from '@/modules/brainstorming/game/types/AnimationTimeline';
import * as PIXI from 'pixi.js';
import * as pixiUtil from '@/utils/pixi';

// eslint-disable-next-line @typescript-eslint/no-var-requires
/* eslint-disable @typescript-eslint/no-explicit-any*/
export class PixiBodies {
  physicBodies: PhysicBodies;
  animationTimeline: AnimationTimeline;
  canvasWidth = 0;
  canvasHeight = 0;
  app: PIXI.Application;
  pixiGraphics: PIXI.Container[] = [];
  circleGradientTexture: PIXI.Texture;
  textContainer: PIXI.Container | null = null;
  constructor(
    app: PIXI.Application,
    physicBodies: PhysicBodies,
    animationTimeline: AnimationTimeline,
    canvasWidth: number,
    canvasHeight: number
  ) {
    this.physicBodies = physicBodies;
    this.animationTimeline = animationTimeline;
    this.app = app;
    this.canvasWidth = canvasWidth;
    this.canvasHeight = canvasHeight;
    this.circleGradientTexture = pixiUtil.generateCircleGradientTexture(
      256,
      app.renderer as any,
      1,
      1
    );
    this.initBodies().then(() => {
      this.textContainer = new PIXI.Container();
      this.app.stage.addChild(this.textContainer as any);
    });
  }

  async initBodies(): Promise<void> {
    for (const [
      index,
      body,
    ] of this.physicBodies.engine.world.bodies.entries()) {
      if (body.isStatic) return;
      const options = this.physicBodies.bodies[index];
      const position = Matter.Vector.create(
        Math.floor(Math.random() * this.canvasWidth),
        Math.floor(Math.random() * this.canvasHeight)
      );
      Matter.Body.setPosition(body, position);
      const container = new PIXI.Container();
      container.x = position.x;
      container.y = position.y;
      this.pixiGraphics.push(container);
      this.app.stage.addChild(container as any);
      const sprite = new PIXI.Sprite(this.circleGradientTexture);
      sprite.anchor.set(0.5);
      sprite.alpha = 0.5;
      const size = options.gradientSize as number;
      sprite.width = size * 2;
      sprite.height = size * 2;
      container.addChild(sprite as any);
      const text = new PIXI.Text(options.text as string, {
        fontFamily: 'Arial',
        fontSize: options.gradientSize as number,
        fill: ['#27133B'],
      });
      text.anchor.set(0.5);
      container.addChild(text as any);
    }
  }

  activeTextId = -1;
  initText(textId: number): void {
    if (textId === this.activeTextId || !this.textContainer) return;
    this.activeTextId = textId;
    this.textContainer.removeChildren();
    if (this.animationTimeline.texts[textId] !== undefined) {
      for (const text of this.animationTimeline.texts[textId]) {
        const pixiText = new PIXI.Text(text.text as string, {
          fontFamily: 'Arial',
          fontSize: text.animation.startSize,
          fill: [text.color.substring(0, 7)],
        });
        pixiText.anchor.set(0.5);
        this.textContainer.addChild(pixiText as any);
      }
    }
  }

  showBodies(opacity: number): void {
    this.physicBodies.engine.world.bodies.forEach((body, index) => {
      if (body.isStatic) return;
      const container = this.pixiGraphics[index];
      container.x = body.position.x;
      container.y = body.position.y;
      const options = this.physicBodies.bodies[index];
      container.alpha = options.isHidden ? 0 : opacity / 255;
    });
  }

  frame = 0;
  show(): void {
    this.frame++;
    let showText = false;

    if (this.animationTimeline.timeline.animationFrame === -1)
      this.showBodies(255);
    else {
      const frame = this.animationTimeline.getKeyframeValue(
        this.animationTimeline.timeline.animationFrame
      );
      if (frame && frame.opacity !== undefined) {
        const opacity = Math.floor(frame.opacity);
        this.physicBodies.enableEngine(opacity !== 0);
      }
      if (frame && frame.position !== undefined) {
        if (frame.position === 'random') {
          this.physicBodies.engine.world.bodies.forEach((body, index) => {
            const options = this.physicBodies.bodies[index];
            if (options.text) {
              const position = Matter.Vector.create(
                Math.floor(Math.random() * this.canvasWidth),
                Math.floor(Math.random() * this.canvasHeight)
              );
              Matter.Body.setPosition(body, position);
            }
          });
        }
      }
      const useForce = (): boolean => {
        if (frame.forceDirection !== undefined) {
          if (frame.forceDirection === 'both') return true;
          return (
            this.animationTimeline.timeline.animationDelta ===
            frame.forceDirection
          );
        }
        return this.animationTimeline.timeline.animationDelta === 1;
      };
      if (frame && frame.force !== undefined && useForce()) {
        const getRandomForceValue = (): number => {
          const value = Math.random() * 80 - 40;
          if (value >= 0 && value < 10) return value + 10;
          if (value < 0 && value > -10) return value - 10;
          return value;
        };
        let x = frame.force !== 'random' ? frame.force[0] : 0;
        let y = frame.force !== 'random' ? frame.force[1] : 0;
        this.physicBodies.engine.world.bodies.forEach((body, index) => {
          const options = this.physicBodies.bodies[index];
          if (options.text) {
            if (frame.force === 'random') {
              x = getRandomForceValue();
              y = getRandomForceValue();
            }
            Matter.Body.setVelocity(body, { x: x, y: y });
          }
        });
      }
      if (frame && frame.opacity !== undefined) {
        const opacity = Math.floor(frame.opacity);
        this.showBodies(opacity);
      }
      if (frame && frame.textProgress !== undefined) {
        const textProgress = frame.textProgress;
        const textProgressLength = 100;

        this.animateText(
          this.animationTimeline.timeline.textAnimationId,
          textProgressLength,
          textProgress
        );
        showText = true;
      }
    }
    if (this.animationTimeline.isLastKeyframe()) {
      this.animationTimeline.timeline.animationDelta = -1;
      this.animationTimeline.timeline.infoTextFrame = 0;
    }
    if (this.animationTimeline.timeline.animationFrame >= 0)
      this.animationTimeline.timeline.animationFrame +=
        this.animationTimeline.timeline.animationDelta;
    if (
      this.animationTimeline.timeline.maxRunningFrame <
      this.animationTimeline.timeline.animationFrame
    )
      this.animationTimeline.timeline.maxRunningFrame =
        this.animationTimeline.timeline.animationFrame;

    if (
      this.animationTimeline.timeline.animationDelta === -1 &&
      this.animationTimeline.timeline.maxRunningFrame > 0 &&
      this.animationTimeline.timeline.maxRunningFrame <
        this.animationTimeline.timeline.animationCompletedFrame
    ) {
      const textProgressLength = 15;
      const textProgress =
        this.animationTimeline.timeline.infoTextFrame < textProgressLength
          ? this.animationTimeline.timeline.infoTextFrame
          : textProgressLength;
      this.animateText(
        this.animationTimeline.timeline.textAnimationKeepId,
        textProgressLength,
        textProgress,
        100
      );
      showText = true;
      this.animationTimeline.timeline.infoTextFrame++;
    }

    if (
      this.animationTimeline.timeline.animationDelta === 1 &&
      this.animationTimeline.timeline.maxRunningFrame === 0 &&
      this.frame > 200
    ) {
      const textProgressLength = 15;
      const textProgress =
        this.animationTimeline.timeline.infoTextFrame < textProgressLength
          ? this.animationTimeline.timeline.infoTextFrame
          : textProgressLength;
      this.animateText(
        this.animationTimeline.timeline.textAnimationStartId,
        textProgressLength,
        textProgress,
        100
      );
      showText = true;
      this.animationTimeline.timeline.infoTextFrame++;
    }

    if (
      !showText &&
      this.textContainer &&
      this.textContainer.children.length > 0
    ) {
      this.textContainer.removeChildren();
    }
  }

  animateText(
    textId: number,
    textProgressLength: number,
    textProgress: number,
    targetOpacity = 255
  ): void {
    const interpolate = (
      start: number,
      end: number,
      step: number,
      stepCount: number
    ): number => {
      return start + (step / stepCount) * (end - start);
    };

    let opacity = interpolate(
      0,
      targetOpacity,
      textProgress,
      textProgressLength
    );
    if (opacity < 0) {
      opacity = 0;
    }
    if (opacity > targetOpacity) {
      opacity = targetOpacity;
    }
    opacity = Math.floor(opacity);

    this.initText(textId);
    if (
      this.animationTimeline.texts[textId] !== undefined &&
      this.textContainer &&
      this.animationTimeline.texts[textId].length ===
        this.textContainer.children.length
    ) {
      for (const [index, text] of this.animationTimeline.texts[
        textId
      ].entries()) {
        const textBody = this.textContainer.getChildAt(index) as PIXI.Text;
        const delta = textProgressLength / (text.animation.path.length - 1);
        const pathIndexStart = Math.floor(textProgress / delta);
        const pathIndexEnd =
          pathIndexStart < text.animation.path.length - 1
            ? pathIndexStart + 1
            : pathIndexStart;
        textBody.x = interpolate(
          text.animation.path[pathIndexStart][0],
          text.animation.path[pathIndexEnd][0],
          textProgress % delta,
          delta
        );
        textBody.y = interpolate(
          text.animation.path[pathIndexStart][1],
          text.animation.path[pathIndexEnd][1],
          textProgress % delta,
          delta
        );
        textBody.style.fontSize = interpolate(
          text.animation.startSize,
          text.size,
          textProgress,
          textProgressLength
        );
        textBody.alpha = opacity / 255;
        textBody.rotation = interpolate(
          text.animation.startAngle,
          text.angle,
          textProgress,
          textProgressLength
        );
      }
    }
  }
}
