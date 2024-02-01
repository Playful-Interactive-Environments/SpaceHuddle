import { isNumber } from 'chart.js/helpers';
import * as Matter from 'matter-js/build/matter.js';
import { PhysicBodies } from '@/modules/brainstorming/game/types/PhysicBodies';
import {
  AnimationTimeline,
  TextAnimationData,
} from '@/modules/brainstorming/game/types/AnimationTimeline';

// eslint-disable-next-line @typescript-eslint/no-var-requires

export class CanvasBodies {
  physicBodies: PhysicBodies;
  animationTimeline: AnimationTimeline;
  canvasWidth = 0;
  canvasHeight = 0;
  ctx!: CanvasRenderingContext2D;
  private textCallback = (
    textId: number,
    textProgressLength: number,
    textProgress: number,
    targetOpacity: number
  ) =>
    this.animateText(textId, textProgressLength, textProgress, targetOpacity);
  constructor(
    ctx: CanvasRenderingContext2D,
    physicBodies: PhysicBodies,
    animationTimeline: AnimationTimeline,
    canvasWidth: number,
    canvasHeight: number
  ) {
    this.physicBodies = physicBodies;
    this.animationTimeline = animationTimeline;
    animationTimeline.addAnimateTextCallback(this.textCallback);
    this.ctx = ctx;
    this.canvasWidth = canvasWidth;
    this.canvasHeight = canvasHeight;
  }

  clearCanvas(): void {
    this.ctx.clearRect(0, 0, this.canvasWidth, this.canvasHeight);
  }

  showBodies(opacity: number): void {
    let gradOpacity = Math.floor(opacity / 2).toString(16);
    if (gradOpacity.length === 1) gradOpacity = `0${gradOpacity}`;
    let hexOpacity = opacity.toString(16);
    if (hexOpacity.length === 1) hexOpacity = `0${hexOpacity}`;
    this.physicBodies.engine.world.bodies.forEach((body, index) => {
      if (
        body.position.x < 0 ||
        body.position.x > this.canvasWidth ||
        body.position.y < 0 ||
        body.position.y > this.canvasHeight
      ) {
        const position = Matter.Vector.create(
          Math.floor(Math.random() * this.canvasWidth),
          Math.floor(Math.random() * this.canvasHeight)
        );
        Matter.Body.setPosition(body, position);
      }

      const options = this.physicBodies.bodies[index];
      if (!options.isHidden) {
        this.ctx.beginPath();
        const start = body.vertices[0];
        this.ctx.moveTo(start.x, start.y);
        for (let i = 1; i < body.vertices.length; i += 1) {
          const to = body.vertices[i];
          this.ctx.lineTo(to.x, to.y);
        }
        this.ctx.closePath();

        if (options.gradientSize && isNumber(options.gradientSize)) {
          // Create gradient
          const grd = this.ctx.createRadialGradient(
            body.position.x,
            body.position.y,
            1,
            body.position.x,
            body.position.y,
            options.gradientSize as number
          );
          grd.addColorStop(0, `#FFFFFF${gradOpacity}`);
          grd.addColorStop(1, '#FFFFFF00');

          this.ctx.fillStyle = grd;
        } else {
          this.ctx.fillStyle = `#FFFFFF${hexOpacity}`;
        }

        this.ctx.fill();
        if (options.text) {
          this.showText(
            options.text as string,
            body.position.x,
            body.position.y,
            options.gradientSize as number,
            `#27133B${hexOpacity}`,
            body.angle
          );
        }
      }
    });
  }

  show(): void {
    this.clearCanvas();
    const opacity = this.animationTimeline.bodyOpacity();
    if (opacity) {
      this.showBodies(opacity);
    }
    this.animateActiveText();
  }

  activeAnimatedText: TextAnimationData[] = [];
  animateText(
    textId: number,
    textProgressLength: number,
    textProgress: number,
    targetOpacity = 255
  ): void {
    this.activeAnimatedText = this.animationTimeline.getTextLetterList(
      textId,
      textProgressLength,
      textProgress,
      targetOpacity
    );
  }

  animateActiveText(): void {
    if (this.activeAnimatedText.length > 0) {
      for (const text of this.activeAnimatedText) {
        this.showText(
          text.text,
          text.x,
          text.y,
          text.fontSize,
          text.color,
          text.rotation
        );
      }
    }
  }

  showText(
    text: string,
    x: number,
    y: number,
    size = 24,
    color = '#27133BFF',
    angle = 0
  ): void {
    this.ctx.font = `${size}px Arial`;
    this.ctx.textAlign = 'center';
    this.ctx.textBaseline = 'middle';
    const textPosX = x;
    const textPosY = y;
    this.ctx.translate(textPosX, textPosY);
    this.ctx.rotate(angle);
    this.ctx.fillStyle = color;
    this.ctx.fillText(text, 0, 0);
    this.ctx.rotate(-angle);
    this.ctx.translate(-textPosX, -textPosY);
  }

  destroy(): void {
    this.animationTimeline.removeAnimateTextCallback(this.textCallback);
  }
}
