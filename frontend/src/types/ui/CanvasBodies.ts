// eslint-disable-next-line @typescript-eslint/no-var-requires
const Matter = require('@/../node_modules/matter-js/build/matter.js');

// module aliases
const Engine = Matter.Engine,
  Runner = Matter.Runner,
  Bodies = Matter.Bodies,
  Body = Matter.Body,
  Composite = Matter.Composite;

const engine = Engine.create();
const runner = Runner.create();
Runner.run(runner, engine);

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
      startX: number;
      startY: number;
      startSize: number;
      startAngle: number;
    };
  }[] = [];
  animationTimeline: {
    fadeAnimationLength: number;
    animationFrame: number;
    fadeInAnimationStart: number;
    fadeOutAnimationStart: number;
    startOpacity: number;
    targetOpacity: number;
  } = {
    fadeAnimationLength: 10,
    animationFrame: -1,
    fadeInAnimationStart: 0,
    fadeOutAnimationStart: 80,
    startOpacity: 0,
    targetOpacity: 255,
  };

  constructor(
    ctx: CanvasRenderingContext2D,
    canvasWidth: number,
    canvasHeight: number
  ) {
    this.ctx = ctx;
    this.canvasWidth = canvasWidth;
    this.canvasHeight = canvasHeight;
    this.clearBodies();
  }

  readonly defaultGravityScale = 0.00005;
  setGravity(x: number, y: number): void {
    engine.gravity = {
      x: x,
      y: y,
      scale: this.defaultGravityScale,
    };
  }

  startAnimation(): void {
    this.animationTimeline.animationFrame = 0;
    const force = engine.gravity.scale === this.defaultGravityScale ? 10 : 20;
    engine.world.bodies.forEach((body, index) => {
      const options = this.bodies[index];
      if (options.text) {
        Body.setVelocity(body, {
          x: -engine.gravity.x * force,
          y: -engine.gravity.y * force,
        });
      }
    });
  }

  clearBodies(): void {
    Composite.clear(engine.world);
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
    const body = Bodies.rectangle(x, y, width, height, options);
    Composite.add(engine.world, body);
    this.bodies.push(options);
  }

  addCircle(
    x: number,
    y: number,
    radius: number,
    options: { [key: string]: string | number | boolean } = {}
  ): void {
    const body = Bodies.circle(x, y, radius, options);
    Composite.add(engine.world, body);
    this.bodies.push(options);
  }

  addText(
    text: string,
    x: number,
    y: number,
    size = 24,
    color = '#FFFFFFFF',
    angle = 0
  ): void {
    const animation = {
      startX: Math.floor(Math.random() * this.canvasWidth),
      startY: Math.floor(Math.random() * this.canvasHeight),
      startSize: size / 3,
      startOpacity: 0,
      targetOpacity: 255,
      startAngle: angle,
      animationLength: 15,
      animationFrame: 0,
      fadeOutAnimationStart: 80,
    };
    this.texts.push({
      text: text,
      x: x,
      y: y,
      size: size,
      color: color,
      angle: angle,
      animation: animation,
    });
  }

  clearTexts(): void {
    this.texts = [];
  }

  private fadeInAnimationIsRunning(): boolean {
    return (
      this.animationTimeline.animationFrame >=
        this.animationTimeline.fadeInAnimationStart &&
      this.animationTimeline.animationFrame <=
        this.animationTimeline.fadeInAnimationStart +
          this.animationTimeline.fadeAnimationLength
    );
  }

  private fadeOutAnimationIsRunning(): boolean {
    return (
      this.animationTimeline.animationFrame >=
        this.animationTimeline.fadeOutAnimationStart &&
      this.animationTimeline.animationFrame <=
        this.animationTimeline.fadeOutAnimationStart +
          this.animationTimeline.fadeAnimationLength
    );
  }

  private animationTextIsActive(): boolean {
    return (
      this.animationTimeline.animationFrame >=
        this.animationTimeline.fadeInAnimationStart &&
      this.animationTimeline.animationFrame <=
        this.animationTimeline.fadeOutAnimationStart +
          this.animationTimeline.fadeAnimationLength
    );
  }

  private interpolationFrame(): number {
    if (this.fadeInAnimationIsRunning())
      return this.animationTimeline.animationFrame;
    if (this.fadeOutAnimationIsRunning())
      return (
        this.animationTimeline.fadeAnimationLength -
        this.animationTimeline.animationFrame +
        this.animationTimeline.fadeOutAnimationStart
      );
    return -1;
  }

  completeAnimation(): void {
    runner.enabled = true;

    engine.world.bodies.forEach((body, index) => {
      const options = this.bodies[index];
      if (options.text) {
        Body.setVelocity(body, { x: 0, y: 0 });
      }
    });
  }

  showBodies(opacity: number): void {
    let gradOpacity = Math.floor(opacity / 2).toString(16);
    if (gradOpacity.length === 1) gradOpacity = `0${gradOpacity}`;
    let hexOpacity = opacity.toString(16);
    if (hexOpacity.length === 1) hexOpacity = `0${hexOpacity}`;
    engine.world.bodies.forEach((body, index) => {
      const options = this.bodies[index];
      this.ctx.beginPath();
      const start = body.vertices[0];
      this.ctx.moveTo(start.x, start.y);
      for (let i = 1; i < body.vertices.length; i += 1) {
        const to = body.vertices[i];
        this.ctx.lineTo(to.x, to.y);
      }
      this.ctx.closePath();

      if (options.gradientSize) {
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

  show(): void {
    const interpolate = (
      start: number,
      end: number,
      step: number,
      stepCount: number
    ): number => {
      return start + (step / stepCount) * (end - start);
    };
    const interpolationFrame = this.interpolationFrame();

    this.clearCanvas();
    if (!this.animationTextIsActive()) this.showBodies(255);
    else {
      if (
        this.fadeOutAnimationIsRunning() &&
        interpolationFrame === this.animationTimeline.fadeAnimationLength
      ) {
        this.completeAnimation();
      }
      let opacity = interpolate(
        this.animationTimeline.startOpacity,
        this.animationTimeline.targetOpacity,
        interpolationFrame,
        this.animationTimeline.fadeAnimationLength
      );
      if (opacity < 0 || opacity > 255) {
        opacity = 0;
      }
      opacity = Math.floor(opacity);
      if (this.fadeInAnimationIsRunning() || this.fadeOutAnimationIsRunning()) {
        this.showBodies(255 - opacity);
        let hexOpacity = opacity.toString(16);
        if (hexOpacity.length === 1) hexOpacity = `0${hexOpacity}`;
        this.texts.forEach((text) => {
          this.showText(
            text.text,
            interpolate(
              text.animation.startX,
              text.x,
              interpolationFrame,
              this.animationTimeline.fadeAnimationLength
            ),
            interpolate(
              text.animation.startY,
              text.y,
              interpolationFrame,
              this.animationTimeline.fadeAnimationLength
            ),
            interpolate(
              text.animation.startSize,
              text.size,
              interpolationFrame,
              this.animationTimeline.fadeAnimationLength
            ),
            `${text.color.substr(0, 7)}${hexOpacity}`,
            text.angle
          );
        });
      } else {
        if (runner.enabled) runner.enabled = false;
        this.texts.forEach((text) => {
          this.showText(
            text.text,
            text.x,
            text.y,
            text.size,
            text.color,
            text.angle
          );
        });
      }
    }
    if (this.animationTimeline.animationFrame >= 0)
      this.animationTimeline.animationFrame++;
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
