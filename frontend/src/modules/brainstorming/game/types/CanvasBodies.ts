import { isNumber } from 'chart.js/helpers';
import * as Matter from 'matter-js/build/matter.js';
import { PhysicBodies } from '@/modules/brainstorming/game/types/PhysicBodies';

// eslint-disable-next-line @typescript-eslint/no-var-requires

export class CanvasBodies {
  physicBodies: PhysicBodies;
  canvasWidth = 0;
  canvasHeight = 0;
  ctx!: CanvasRenderingContext2D;
  constructor(
    ctx: CanvasRenderingContext2D,
    physicBodies: PhysicBodies,
    canvasWidth: number,
    canvasHeight: number
  ) {
    this.physicBodies = physicBodies;
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

  frame = 0;
  show(): void {
    this.frame++;

    this.clearCanvas();
    if (this.physicBodies.animationTimeline.animationFrame === -1)
      this.showBodies(255);
    else {
      const frame = this.physicBodies.getKeyframeValue(
        this.physicBodies.animationTimeline.animationFrame
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
            this.physicBodies.animationTimeline.animationDelta ===
            frame.forceDirection
          );
        }
        return this.physicBodies.animationTimeline.animationDelta === 1;
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
          this.physicBodies.animationTimeline.textAnimationId,
          textProgressLength,
          textProgress
        );
      }
    }
    if (this.physicBodies.isLastKeyframe()) {
      this.physicBodies.animationTimeline.animationDelta = -1;
      this.physicBodies.animationTimeline.infoTextFrame = 0;
    }
    if (this.physicBodies.animationTimeline.animationFrame >= 0)
      this.physicBodies.animationTimeline.animationFrame +=
        this.physicBodies.animationTimeline.animationDelta;
    if (
      this.physicBodies.animationTimeline.maxRunningFrame <
      this.physicBodies.animationTimeline.animationFrame
    )
      this.physicBodies.animationTimeline.maxRunningFrame =
        this.physicBodies.animationTimeline.animationFrame;

    if (
      this.physicBodies.animationTimeline.animationDelta === -1 &&
      this.physicBodies.animationTimeline.maxRunningFrame > 0 &&
      this.physicBodies.animationTimeline.maxRunningFrame <
        this.physicBodies.animationTimeline.animationCompletedFrame
    ) {
      const textProgressLength = 15;
      const textProgress =
        this.physicBodies.animationTimeline.infoTextFrame < textProgressLength
          ? this.physicBodies.animationTimeline.infoTextFrame
          : textProgressLength;
      this.animateText(
        this.physicBodies.animationTimeline.textAnimationKeepId,
        textProgressLength,
        textProgress,
        100
      );
      this.physicBodies.animationTimeline.infoTextFrame++;
    }

    if (
      this.physicBodies.animationTimeline.animationDelta === 1 &&
      this.physicBodies.animationTimeline.maxRunningFrame === 0 &&
      this.frame > 200
    ) {
      const textProgressLength = 15;
      const textProgress =
        this.physicBodies.animationTimeline.infoTextFrame < textProgressLength
          ? this.physicBodies.animationTimeline.infoTextFrame
          : textProgressLength;
      this.animateText(
        this.physicBodies.animationTimeline.textAnimationStartId,
        textProgressLength,
        textProgress,
        100
      );
      this.physicBodies.animationTimeline.infoTextFrame++;
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
    let hexOpacity = opacity.toString(16);
    if (hexOpacity.length === 1) hexOpacity = `0${hexOpacity}`;

    if (this.physicBodies.texts[textId] !== undefined) {
      this.physicBodies.texts[textId].forEach((text) => {
        const delta = textProgressLength / (text.animation.path.length - 1);
        const pathIndexStart = Math.floor(textProgress / delta);
        const pathIndexEnd =
          pathIndexStart < text.animation.path.length - 1
            ? pathIndexStart + 1
            : pathIndexStart;
        this.showText(
          text.text,
          interpolate(
            text.animation.path[pathIndexStart][0],
            text.animation.path[pathIndexEnd][0],
            textProgress % delta,
            delta
          ),
          interpolate(
            text.animation.path[pathIndexStart][1],
            text.animation.path[pathIndexEnd][1],
            textProgress % delta,
            delta
          ),
          interpolate(
            text.animation.startSize,
            text.size,
            textProgress,
            textProgressLength
          ),
          `${text.color.substr(0, 7)}${hexOpacity}`,
          interpolate(
            text.animation.startAngle,
            text.angle,
            textProgress,
            textProgressLength
          )
        );
      });
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
}
