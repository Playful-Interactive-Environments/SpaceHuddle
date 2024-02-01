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
  private textCallback = (
    textId: number,
    textProgressLength: number,
    textProgress: number,
    targetOpacity: number
  ) =>
    this.animateText(textId, textProgressLength, textProgress, targetOpacity);
  constructor(
    app: PIXI.Application,
    physicBodies: PhysicBodies,
    animationTimeline: AnimationTimeline,
    canvasWidth: number,
    canvasHeight: number
  ) {
    this.physicBodies = physicBodies;
    this.animationTimeline = animationTimeline;
    animationTimeline.addAnimateTextCallback(this.textCallback);
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
      if (body.isStatic) continue;
      const options = this.physicBodies.bodies[index];
      /*const position = Matter.Vector.create(
        Math.floor(Math.random() * this.canvasWidth),
        Math.floor(Math.random() * this.canvasHeight)
      );
      Matter.Body.setPosition(body, position);*/
      const container = new PIXI.Container();
      container.x = body.position.x;
      container.y = body.position.y;
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

  show(): void {
    const opacity = this.animationTimeline.bodyOpacity();
    if (opacity) {
      this.showBodies(opacity);
    }
    const showText = this.animationTimeline.getActiveInfoTextId() > -1;

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
    const list = this.animationTimeline.getTextLetterList(
      textId,
      textProgressLength,
      textProgress,
      targetOpacity
    );
    this.initText(textId);
    if (
      list.length > 0 &&
      this.textContainer &&
      list.length === this.textContainer.children.length
    ) {
      for (const [index, text] of list.entries()) {
        const textBody = this.textContainer.getChildAt(index) as PIXI.Text;
        textBody.x = text.x;
        textBody.y = text.y;
        textBody.style.fontSize = text.fontSize;
        textBody.alpha = text.alpha;
        textBody.rotation = text.rotation;
      }
    }
  }

  destroy(): void {
    this.animationTimeline.removeAnimateTextCallback(this.textCallback);
  }
}
