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

export class PhysicBodies {
  containerWidth = 0;
  containerHeight = 0;
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
  constructor(width: number, height: number, mouseContainer: HTMLElement) {
    this.engine = Matter.Engine.create();
    this.runner = Matter.Runner.create();
    Matter.Runner.run(this.runner, this.engine);

    this.containerWidth = width;
    this.containerHeight = height;
    this.clearBodies();
    this.setGravity(0, 1, 0);

    // add mouse control
    const mouse = Matter.Mouse.create(mouseContainer);
    this.mouseConstraint = Matter.MouseConstraint.create(this.engine, {
      mouse: mouse,
      constraint: {
        stiffness: 0.2,
      },
    });
    Matter.Composite.add(this.engine.world, this.mouseConstraint);
  }

  getKeyframeValue(keyframe: number): Keyframe {
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

  isLastKeyframe(): boolean {
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
    textId = 0,
    animationPathCount = 8
  ): void {
    const path: [number, number][] = [];
    for (let i = 0; i < animationPathCount; i++) {
      path.push([
        Math.floor(Math.random() * this.containerWidth),
        Math.floor(Math.random() * this.containerHeight),
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

  protected readonly pressFactor = 3;
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

  enableEngine(value: boolean): void {
    if (value) {
      if (!this.runner.enabled) this.completeAnimation();
    } else {
      if (this.runner.enabled) this.runner.enabled = false;
    }
  }
}
