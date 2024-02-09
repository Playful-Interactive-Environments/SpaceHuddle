import * as Matter from 'matter-js/build/matter.js';
import {
  AnimationTimeline,
  Keyframe,
} from '@/modules/brainstorming/game/types/AnimationTimeline';

// eslint-disable-next-line @typescript-eslint/no-var-requires

export class PhysicBodies {
  animationTimeline: AnimationTimeline;
  containerWidth = 0;
  containerHeight = 0;
  bodies: { [key: string]: string | number | boolean }[] = [];

  mouseConstraint!: typeof Matter.MouseConstraint;

  engine!: typeof Matter.Engine;
  runner!: typeof Matter.Runner;
  private eventList: { name: string; callback: () => void }[] = [];
  private updateCallback = () => this.updateAnimation();
  constructor(
    width: number,
    height: number,
    mouseContainer: HTMLElement,
    animationTimeline: AnimationTimeline
  ) {
    this.animationTimeline = animationTimeline;
    animationTimeline.addAnimationUpdatedCallback(this.updateCallback);
    this.engine = Matter.Engine.create();
    this.runner = Matter.Runner.create();
    Matter.Runner.run(this.runner, this.engine);

    this.containerWidth = width;
    this.containerHeight = height;
    //this.clearBodies();
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

  addEvent(name: string, callback: () => void): void {
    this.eventList.push({ name: name, callback: callback });
    Matter.Events.on(this.engine, name, callback);
  }

  readonly defaultGravityScale = 0.0005;
  setGravity(x: number, y: number, z: number): void {
    const scaleFactor = 1;
    this.engine.gravity = {
      x: x * scaleFactor,
      y: y * scaleFactor,
      scale: this.defaultGravityScale * (1 - z) * scaleFactor,
    };
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

  setRandomPosition(): void {
    this.engine.world.bodies.forEach((body, index) => {
      const options = this.bodies[index];
      if (options.text) {
        const position = Matter.Vector.create(
          Math.floor(Math.random() * this.containerWidth),
          Math.floor(Math.random() * this.containerHeight)
        );
        Matter.Body.setPosition(body, position);
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

  updateAnimation(): Keyframe {
    const frame = this.animationTimeline.getActiveKeyframeValue();
    if (frame && frame.opacity !== undefined) {
      const opacity = Math.floor(frame.opacity);
      this.enableEngine(opacity !== 0);
    }
    if (frame && frame.position !== undefined) {
      if (frame.position === 'random') {
        this.setRandomPosition();
      }
    }
    if (frame && frame.force !== undefined && frame.useForce) {
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
    this.engine.world.bodies.forEach((body) => {
      if (body.position.x < 0) body.position.x = 0;
      if (body.position.x > this.containerWidth)
        body.position.x = this.containerWidth;
      if (body.position.y < 0) body.position.y = 0;
      if (body.position.y > this.containerHeight)
        body.position.y = this.containerHeight;
    });
    return frame;
  }

  destroy(): void {
    for (const event of this.eventList) {
      Matter.Events.off(this.engine, event.name, event.callback);
    }
    this.animationTimeline.removeAnimationUpdatedCallback(this.updateCallback);
  }
}
