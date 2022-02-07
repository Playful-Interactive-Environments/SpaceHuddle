import { isNumber } from 'chart.js/helpers';
import * as Matter from 'matter-js/build/matter.js';

// eslint-disable-next-line @typescript-eslint/no-var-requires

export interface Keyframe {
  keyframe: number;
  position?: 'random';
  opacity?: number;
  textProgress?: number;
  force?: [number, number] | 'random';
  forceDirection?: 1 | -1 | 'both';
  source?: Keyframe;
  target?: Keyframe;
}

export class CanvasBodies {
  ctx!: CanvasRenderingContext2D;
  canvasWidth = 0;
  canvasHeight = 0;
  bodies: { [key: string]: string | number | boolean }[] = [];
  texts: {
    text: string;
    x: number;
    y: number;
    size: number;
    color: string;
    angle: number;
    animation: {
      path: [number, number][];
      startSize: number;
      startAngle: number;
    };
  }[][] = [];
  animationTimeline: {
    animationFrame: number;
    infoTextFrame: number;
    animationDelta: number;
    animationEnd: number;
    maxRunningFrame: number;
    animationCompletedFrame: number;
    textAnimationId: number;
    textAnimationStartId: number;
    textAnimationKeepId: number;
  } = {
    animationFrame: -1,
    infoTextFrame: 0,
    animationDelta: 1,
    animationEnd: 0,
    maxRunningFrame: 0,
    animationCompletedFrame: 100,
    textAnimationId: 0,
    textAnimationStartId: 1,
    textAnimationKeepId: 2,
  };

  animationKeyframes: Keyframe[] = [
    { keyframe: 0, opacity: 255, force: 'random' },
    { keyframe: 5, force: 'random' },
    { keyframe: 10, force: 'random' },
    { keyframe: 15, force: 'random' },
    { keyframe: 20, textProgress: 0, force: 'random' },
    { keyframe: 25, force: 'random' },
    { keyframe: 30, force: 'random' },
    { keyframe: 35, force: 'random' },
    {
      keyframe: 40,
      force: 'random',
      forceDirection: 'both',
      position: 'random',
      opacity: 0,
    },
    { keyframe: 100, textProgress: 100 },
    { keyframe: 150, opacity: 0, textProgress: 100 },
  ];

  mouseConstraint!: typeof Matter.MouseConstraint;

  engine!: typeof Matter.Engine;
  runner!: typeof Matter.Runner;
  constructor(
    ctx: CanvasRenderingContext2D,
    canvasWidth: number,
    canvasHeight: number,
    canvas: HTMLCanvasElement
  ) {
    this.engine = Matter.Engine.create();
    this.runner = Matter.Runner.create();
    Matter.Runner.run(this.runner, this.engine);

    this.ctx = ctx;
    this.canvasWidth = canvasWidth;
    this.canvasHeight = canvasHeight;
    this.clearBodies();
    this.setGravity(0, 1, 0);

    // add mouse control
    const mouse = Matter.Mouse.create(canvas);
    this.mouseConstraint = Matter.MouseConstraint.create(this.engine, {
      mouse: mouse,
      constraint: {
        stiffness: 0.2,
      },
    });
    Matter.Composite.add(this.engine.world, this.mouseConstraint);
  }

  private getKeyframeValue(keyframe: number): Keyframe {
    const animationKeyframes = this.animationKeyframes.sort((a, b) =>
      a.keyframe > b.keyframe ? 1 : 0
    );
    const frame: Keyframe = {
      keyframe: keyframe,
    };
    const findTarget = (
      predicate: (value: Keyframe, index: number, obj: Keyframe[]) => boolean
    ): Keyframe | undefined => {
      const list = animationKeyframes
        .filter(
          (frame, index, obj) =>
            keyframe <= frame.keyframe && predicate(frame, index, obj)
        )
        .sort((a, b) => (a.keyframe > b.keyframe ? 1 : 0));

      if (list.length > 0) return list[0];
    };
    const findSource = (
      predicate: (value: Keyframe, index: number, obj: Keyframe[]) => boolean
    ): Keyframe | undefined => {
      const list = animationKeyframes
        .filter(
          (frame, index, obj) =>
            keyframe > frame.keyframe && predicate(frame, index, obj)
        )
        .sort((a, b) => (a.keyframe > b.keyframe ? 1 : 0));

      if (list.length > 0) return list[list.length - 1];
    };
    const setProperty = (
      propertyName: string,
      source: Keyframe | undefined,
      target: Keyframe | undefined
    ): void => {
      if (source && target) {
        const delta =
          (target[propertyName] - source[propertyName]) /
          (target.keyframe - source.keyframe);
        frame[propertyName] =
          source[propertyName] + delta * (keyframe - source.keyframe);
      } else if (target && target.keyframe === keyframe) {
        frame[propertyName] = target[propertyName];
      }
      frame.source = source;
      frame.target = target;
    };
    const setFrameProperty = (propertyName: string): void => {
      const propertyFrame = animationKeyframes.find(
        (frame) =>
          keyframe === frame.keyframe && frame[propertyName] !== undefined
      );
      if (propertyFrame) frame[propertyName] = propertyFrame[propertyName];
    };

    setProperty(
      'opacity',
      findSource((frame) => frame.opacity !== undefined),
      findTarget((frame) => frame.opacity !== undefined)
    );
    setProperty(
      'textProgress',
      findSource((frame) => frame.textProgress !== undefined),
      findTarget((frame) => frame.textProgress !== undefined)
    );
    setFrameProperty('force');
    setFrameProperty('forceDirection');
    setFrameProperty('position');
    return frame;
  }

  private isLastKeyframe(): boolean {
    return (
      this.animationKeyframes.filter(
        (frame) => frame.keyframe > this.animationTimeline.animationFrame
      ).length === 0 ||
      this.animationTimeline.animationFrame >=
        this.animationTimeline.animationEnd
    );
  }

  readonly defaultGravityScale = 0.0005;
  setGravity(x: number, y: number, z: number): void {
    this.engine.gravity = {
      x: x,
      y: y,
      scale: this.defaultGravityScale * (1 - z),
    };
  }

  startAnimation(
    runtime = 20,
    textAnimationId = 0,
    textAnimationStartId = 1,
    textAnimationKeepId = 2
  ): boolean {
    this.animationTimeline.textAnimationId = textAnimationId;
    this.animationTimeline.textAnimationStartId = textAnimationStartId;
    this.animationTimeline.textAnimationKeepId = textAnimationKeepId;
    let returnValue = false;
    if (this.animationTimeline.animationFrame == -1) {
      this.animationTimeline.animationFrame = 0;
      this.animationTimeline.maxRunningFrame = 0;
      returnValue = true;
    }
    this.animationTimeline.animationDelta = 1;
    this.animationTimeline.animationEnd =
      this.animationTimeline.animationFrame + runtime;
    return returnValue;
  }

  addShakingForce(force = 10): void {
    this.engine.world.bodies.forEach((body, index) => {
      const options = this.bodies[index];
      if (options.text) {
        Matter.Body.setVelocity(body, {
          x: -this.engine.gravity.x * force,
          y: -this.engine.gravity.y * force,
        });
      }
    });
  }

  clearBodies(): void {
    Matter.Composite.clear(this.engine.world);
    this.bodies = [];
  }

  clearCanvas(): void {
    this.ctx.clearRect(0, 0, this.canvasWidth, this.canvasHeight);
  }

  addRect(
    x: number,
    y: number,
    width: number,
    height: number,
    options: { [key: string]: string | number | boolean } = {}
  ): void {
    const body = Matter.Bodies.rectangle(x, y, width, height, options);
    Matter.Composite.add(this.engine.world, body);
    this.bodies.push(options);
  }

  addCircle(
    x: number,
    y: number,
    radius: number,
    options: { [key: string]: string | number | boolean } = {}
  ): void {
    const body = Matter.Bodies.circle(x, y, radius, options);
    Matter.Composite.add(this.engine.world, body);
    this.bodies.push(options);
  }

  addText(
    text: string,
    x: number,
    y: number,
    size = 24,
    color = '#FFFFFFFF',
    angle = 0,
    textId = 0
  ): void {
    const path: [number, number][] = [];
    for (let i = 0; i < 8; i++) {
      path.push([
        Math.floor(Math.random() * this.canvasWidth),
        Math.floor(Math.random() * this.canvasHeight),
      ]);
    }
    path.push([x, y]);
    const animation = {
      path: path,
      startSize: size / Math.floor(Math.random() * 5 + 2),
      startAngle: Math.floor(Math.random() * 360),
    };
    if (this.texts[textId] === undefined) this.texts[textId] = [];
    this.texts[textId].push({
      text: text,
      x: x,
      y: y,
      size: size,
      color: color,
      angle: angle,
      animation: animation,
    });
  }

  private readonly pressFactor = 3;
  pressBody(): void {
    setTimeout(() => {
      const body = this.mouseConstraint.body;
      if (body && body.circleRadius === body.gradientSize) {
        Matter.Body.scale(body, this.pressFactor, this.pressFactor);
      }
    }, 100);
  }

  releaseBody(): void {
    const body = this.mouseConstraint.body;
    setTimeout(() => {
      if (body && body.circleRadius > body.gradientSize) {
        const pressFactor = body.circleRadius / body.gradientSize;
        Matter.Body.scale(body, 1 / pressFactor, 1 / pressFactor);
      }
    }, 500);
  }

  clearTexts(textId = 0): void {
    this.texts[textId] = [];
  }

  completeAnimation(): void {
    this.runner.enabled = true;

    this.engine.world.bodies.forEach((body, index) => {
      const options = this.bodies[index];
      if (options.text) {
        Matter.Body.setVelocity(body, { x: 0, y: 0 });
      }
    });
  }

  showBodies(opacity: number): void {
    let gradOpacity = Math.floor(opacity / 2).toString(16);
    if (gradOpacity.length === 1) gradOpacity = `0${gradOpacity}`;
    let hexOpacity = opacity.toString(16);
    if (hexOpacity.length === 1) hexOpacity = `0${hexOpacity}`;
    this.engine.world.bodies.forEach((body, index) => {
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

      const options = this.bodies[index];
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
    });
  }

  private enableEngine(value: boolean): void {
    if (value) {
      if (!this.runner.enabled) this.completeAnimation();
    } else {
      if (this.runner.enabled) this.runner.enabled = false;
    }
  }

  frame = 0;
  show(): void {
    this.frame++;
    const interpolate = (
      start: number,
      end: number,
      step: number,
      stepCount: number
    ): number => {
      return start + (step / stepCount) * (end - start);
    };

    this.clearCanvas();
    if (this.animationTimeline.animationFrame === -1) this.showBodies(255);
    else {
      const frame = this.getKeyframeValue(
        this.animationTimeline.animationFrame
      );
      if (frame && frame.opacity !== undefined) {
        const opacity = Math.floor(frame.opacity);
        this.enableEngine(frame.opacity !== 0);
      }
      if (frame && frame.position !== undefined) {
        if (frame.position === 'random') {
          this.engine.world.bodies.forEach((body, index) => {
            const options = this.bodies[index];
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
          return this.animationTimeline.animationDelta === frame.forceDirection;
        }
        return this.animationTimeline.animationDelta === 1;
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
        this.engine.world.bodies.forEach((body, index) => {
          const options = this.bodies[index];
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
        let opacity = interpolate(0, 255, textProgress, textProgressLength);
        if (opacity < 0 || opacity > 255) {
          opacity = 0;
        }
        opacity = Math.floor(opacity);
        let hexOpacity = opacity.toString(16);
        if (hexOpacity.length === 1) hexOpacity = `0${hexOpacity}`;

        this.animateText(
          this.animationTimeline.textAnimationId,
          textProgressLength,
          textProgress,
          hexOpacity
        );
      }
    }
    if (this.isLastKeyframe()) {
      this.animationTimeline.animationDelta = -1;
      this.animationTimeline.infoTextFrame = 0;
    }
    if (this.animationTimeline.animationFrame >= 0)
      this.animationTimeline.animationFrame +=
        this.animationTimeline.animationDelta;
    if (
      this.animationTimeline.maxRunningFrame <
      this.animationTimeline.animationFrame
    )
      this.animationTimeline.maxRunningFrame =
        this.animationTimeline.animationFrame;

    if (
      this.animationTimeline.animationDelta === -1 &&
      this.animationTimeline.maxRunningFrame > 0 &&
      this.animationTimeline.maxRunningFrame <
        this.animationTimeline.animationCompletedFrame
    ) {
      const textProgressLength = 20;
      const textProgress =
        this.animationTimeline.infoTextFrame < textProgressLength
          ? this.animationTimeline.infoTextFrame
          : textProgressLength;
      this.animateText(
        this.animationTimeline.textAnimationKeepId,
        textProgressLength,
        textProgress,
        '55'
      );
      this.animationTimeline.infoTextFrame++;
    }

    if (
      this.animationTimeline.animationDelta === 1 &&
      this.animationTimeline.maxRunningFrame === 0 &&
      this.frame > 200
    ) {
      const textProgressLength = 20;
      const textProgress =
        this.animationTimeline.infoTextFrame < textProgressLength
          ? this.animationTimeline.infoTextFrame
          : textProgressLength;
      this.animateText(
        this.animationTimeline.textAnimationStartId,
        textProgressLength,
        textProgress,
        '55'
      );
      this.animationTimeline.infoTextFrame++;
    }
  }

  animateText(
    textId: number,
    textProgressLength: number,
    textProgress: number,
    hexOpacity: string
  ): void {
    const interpolate = (
      start: number,
      end: number,
      step: number,
      stepCount: number
    ): number => {
      return start + (step / stepCount) * (end - start);
    };

    this.texts[textId].forEach((text) => {
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
