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
  useForce?: boolean;
}
export interface TextAnimationData {
  id: number;
  text: string;
  x: number;
  y: number;
  fontSize: number;
  alpha: number;
  color: string;
  rotation: number;
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

  readonly animationIntervalTime = 100;
  animationInterval: number;
  private animateTextCallbackList: ((
    textId: number,
    textProgressLength: number,
    textProgress: number,
    targetOpacity: number
  ) => void)[] = [];
  private animationUpdatedCallbackList: (() => void)[] = [];

  constructor(width: number, height: number) {
    this.containerWidth = width;
    this.containerHeight = height;
    this.animationInterval = setInterval(
      () => this.updateFrame(),
      this.animationIntervalTime
    );
  }

  destroy(): void {
    clearInterval(this.animationInterval);
  }

  addAnimateTextCallback(
    callback: (
      textId: number,
      textProgressLength: number,
      textProgress: number,
      targetOpacity: number
    ) => void
  ): void {
    this.animateTextCallbackList.push(callback);
  }

  removeAnimateTextCallback(
    callback: (
      textId: number,
      textProgressLength: number,
      textProgress: number,
      targetOpacity: number
    ) => void
  ): void {
    const index = this.animateTextCallbackList.indexOf(callback);
    if (index > -1) {
      this.animateTextCallbackList.splice(index, 1);
    }
  }

  addAnimationUpdatedCallback(callback: () => void): void {
    this.animationUpdatedCallbackList.push(callback);
  }

  removeAnimationUpdatedCallback(callback: () => void): void {
    const index = this.animationUpdatedCallbackList.indexOf(callback);
    if (index > -1) {
      this.animationUpdatedCallbackList.splice(index, 1);
    }
  }

  getActiveKeyframeValue(): Keyframe {
    return this.getKeyframeValue(this.timeline.animationFrame);
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
    const useForce = (): boolean => {
      if (frame.forceDirection !== undefined) {
        if (frame.forceDirection === 'both') return true;
        return this.timeline.animationDelta === frame.forceDirection;
      }
      return this.timeline.animationDelta === 1;
    };
    frame.useForce = useForce();
    return frame;
  }

  hasActiveTimeline(): boolean {
    return this.timeline.animationFrame > -1;
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

  bodyOpacity(): number {
    if (!this.hasActiveTimeline()) {
      return 255;
    } else {
      const frame = this.getActiveKeyframeValue();
      if (frame && frame.opacity !== undefined) {
        return Math.floor(frame.opacity);
      }
    }
    return 0;
  }

  frame = 0;
  updateFrame(): boolean {
    this.frame++;
    let showText = false;
    if (this.isLastKeyframe()) {
      this.timeline.animationDelta = -1;
      this.timeline.infoTextFrame = 0;
    }
    if (this.timeline.animationFrame >= 0)
      this.timeline.animationFrame += this.timeline.animationDelta;
    if (this.timeline.maxRunningFrame < this.timeline.animationFrame)
      this.timeline.maxRunningFrame = this.timeline.animationFrame;

    const textId = this.getActiveInfoTextId();
    if (textId > -1) {
      const textProgressLength = this.getInfoTextProgressLength();
      const textProgress = this.getInfoTextProgress();
      for (const animateText of this.animateTextCallbackList) {
        animateText(textId, textProgressLength, textProgress, 100);
      }
      showText = true;
      this.timeline.infoTextFrame++;
    }
    for (const callback of this.animationUpdatedCallbackList) {
      callback();
    }
    return showText;
  }

  getActiveInfoTextId(): number {
    let textId = -1;
    if (this.timeline.animationFrame !== -1) {
      const frame = this.getActiveKeyframeValue();
      if (frame && frame.textProgress !== undefined) {
        textId = this.timeline.textAnimationId;
      }
    }
    if (
      this.timeline.animationDelta === -1 &&
      this.timeline.maxRunningFrame > 0 &&
      this.timeline.maxRunningFrame < this.timeline.animationCompletedFrame
    ) {
      textId = this.timeline.textAnimationKeepId;
    }

    if (
      this.timeline.animationDelta === 1 &&
      this.timeline.maxRunningFrame === 0 &&
      this.frame > 200
    ) {
      textId = this.timeline.textAnimationStartId;
    }
    return textId;
  }

  showInfoText(): boolean {
    return (
      (this.timeline.animationDelta === -1 &&
        this.timeline.maxRunningFrame > 0 &&
        this.timeline.maxRunningFrame <
          this.timeline.animationCompletedFrame) ||
      (this.timeline.animationDelta === 1 &&
        this.timeline.maxRunningFrame === 0 &&
        this.frame > 200)
    );
  }

  getInfoTextProgressLength(): number {
    return this.showInfoText() ? 15 : 100;
  }

  getInfoTextProgress(): number {
    if (this.showInfoText()) {
      const textProgressLength = 15;
      return this.timeline.infoTextFrame < textProgressLength
        ? this.timeline.infoTextFrame
        : textProgressLength;
    }
    if (this.timeline.animationFrame !== -1) {
      const frame = this.getActiveKeyframeValue();
      if (frame && frame.textProgress !== undefined) {
        return frame.textProgress;
      }
    }
    return 0;
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

  getActiveTextLetterList(): TextAnimationData[] {
    return this.getTextLetterList(
      this.getActiveInfoTextId(),
      this.getInfoTextProgressLength(),
      this.getInfoTextProgress(),
      100
    );
  }

  getTextLetterList(
    textId: number,
    textProgressLength: number,
    textProgress: number,
    targetOpacity = 255
  ): TextAnimationData[] {
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
    const list: TextAnimationData[] = [];
    if (this.texts[textId] !== undefined) {
      let index = -1;
      for (const text of this.texts[textId]) {
        index++;
        const delta = textProgressLength / (text.animation.path.length - 1);
        const pathIndexStart = Math.floor(textProgress / delta);
        const pathIndexEnd =
          pathIndexStart < text.animation.path.length - 1
            ? pathIndexStart + 1
            : pathIndexStart;
        list.push({
          id: index,
          text: text.text,
          x: interpolate(
            text.animation.path[pathIndexStart][0],
            text.animation.path[pathIndexEnd][0],
            textProgress % delta,
            delta
          ),
          y: interpolate(
            text.animation.path[pathIndexStart][1],
            text.animation.path[pathIndexEnd][1],
            textProgress % delta,
            delta
          ),
          fontSize: interpolate(
            text.animation.startSize,
            text.size,
            textProgress,
            textProgressLength
          ),
          alpha: opacity / 255,
          color: `${text.color.substring(0, 7)}${hexOpacity}`,
          rotation: interpolate(
            text.animation.startAngle,
            text.angle,
            textProgress,
            textProgressLength
          ),
        });
      }
    }
    return list;
  }
}
