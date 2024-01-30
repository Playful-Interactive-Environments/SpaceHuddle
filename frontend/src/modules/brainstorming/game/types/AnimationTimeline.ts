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
export class AnimationTimeline {
  timeline: {
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

  keyframes: Keyframe[] = [
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
  containerWidth = 0;
  containerHeight = 0;

  constructor(width: number, height: number) {
    this.containerWidth = width;
    this.containerHeight = height;
  }

  getKeyframeValue(keyframe: number): Keyframe {
    const animationKeyframes = this.keyframes.sort((a, b) =>
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
      this.keyframes.filter(
        (frame) => frame.keyframe > this.timeline.animationFrame
      ).length === 0 ||
      this.timeline.animationFrame >= this.timeline.animationEnd
    );
  }

  startAnimation(
    runtime = 20,
    textAnimationId = 0,
    textAnimationStartId = 1,
    textAnimationKeepId = 2
  ): boolean {
    this.timeline.textAnimationId = textAnimationId;
    this.timeline.textAnimationStartId = textAnimationStartId;
    this.timeline.textAnimationKeepId = textAnimationKeepId;
    let returnValue = false;
    if (this.timeline.animationFrame == -1) {
      this.timeline.animationFrame = 0;
      this.timeline.maxRunningFrame = 0;
      returnValue = true;
    }
    this.timeline.animationDelta = 1;
    this.timeline.animationEnd = this.timeline.animationFrame + runtime;
    return returnValue;
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

  clearTexts(textId = 0): void {
    this.texts[textId] = [];
  }
}
