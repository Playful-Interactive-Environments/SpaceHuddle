import * as PIXI from 'pixi.js';
import { GradientFactory } from '@pixi-essentials/gradients';
import { until } from '@/utils/wait';
import { Emitter } from 'mitt';
import { EventType } from '@/types/enum/EventType';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export function drawCircleWithGradient(
  circle: PIXI.Graphics,
  renderer: PIXI.Renderer
): void {
  const radius = (circle as any).radius;
  const renderTexture = PIXI.RenderTexture.create({
    width: radius * 2,
    height: radius * 2,
  });
  if (!(renderer.renderTexture as any).renderer) return;
  GradientFactory.createRadialGradient(renderer, renderTexture, {
    x0: radius,
    y0: radius,
    r0: 0,
    x1: radius,
    y1: radius,
    r1: radius,
    colorStops: [
      { color: '#ffffffff', offset: 0.5 },
      { color: '#ffffff00', offset: 1 },
    ],
  });
  const matrix: PIXI.Matrix = new PIXI.Matrix();
  matrix.translate(-radius, -radius);
  circle.clear();
  circle.beginTextureFill({
    texture: renderTexture,
    color: (circle as any).color,
    alpha: 0.9,
    matrix: matrix,
  });
  circle.drawCircle(0, 0, radius);
  circle.endFill();
}

export enum GradientDirection {
  TopBottom,
  BottomTop,
  LeftRight,
  RightLeft,
}

export function drawRectWithGradient(
  rect: PIXI.Graphics,
  renderer: PIXI.Renderer,
  width: number,
  height: number,
  color: string,
  direction = GradientDirection.TopBottom
): void {
  const renderTexture = PIXI.RenderTexture.create({
    width: width,
    height: height,
  });
  let x0 = 0;
  let y0 = 0;
  let x1 = 0;
  let y1 = 0;
  switch (direction) {
    case GradientDirection.BottomTop:
      y0 = height;
      break;
    case GradientDirection.TopBottom:
      y1 = height;
      break;
    case GradientDirection.LeftRight:
      x1 = width;
      break;
    case GradientDirection.RightLeft:
      x0 = width;
      break;
  }
  if (!(renderer.renderTexture as any).renderer) return;
  GradientFactory.createLinearGradient(renderer, renderTexture, {
    x0: x0,
    y0: y0,
    x1: x1,
    y1: y1,
    colorStops: [
      { color: '#ffffff00', offset: 0 },
      { color: '#ffffffff', offset: 1 },
    ],
  });
  rect.clear();
  rect.beginTextureFill({
    texture: renderTexture,
    color: color,
    alpha: 0.9,
  });
  rect.drawRect(0, 0, width, height);
  rect.endFill();
}

export function getSpriteAspect(
  spritesheet: PIXI.Spritesheet,
  spriteName: string
): number {
  if (spritesheet?.data?.frames && spriteName in spritesheet.data.frames) {
    const h = spritesheet.data.frames[spriteName].sourceSize?.h;
    const w = spritesheet.data.frames[spriteName].sourceSize?.w;
    if (h && w) return w / h;
  }
  return 1;
}

export function getTextureAspect(texture: PIXI.Texture | null): number {
  if (texture) {
    const h = texture.orig.height;
    const w = texture.orig.width;
    if (h && w) return w / h;
  }
  return 1;
}

export function getSpriteNames(spritesheet: PIXI.Spritesheet): string[] {
  if (spritesheet?.data?.frames) {
    return Object.keys(spritesheet.data.frames);
  }
  return [];
}

enum TextureState {
  loading = 'loading',
  loaded = 'loaded',
  unloading = 'unloading',
}
const textureState: { [url: string]: TextureState } = {};
export async function loadTexture(
  url: string,
  eventBus: Emitter<Record<EventType, unknown>>
): Promise<any> {
  if (textureState[url]) {
    await until(
      () =>
        textureState[url] !== TextureState.loading &&
        textureState[url] !== TextureState.unloading
    );
  }
  if (PIXI.Cache.has(url)) return PIXI.Cache.get(url);
  else {
    textureState[url] = TextureState.loading;
    eventBus.emit(EventType.TEXTURES_LOADING_START);
    const texture = await PIXI.Assets.load(url);
    textureState[url] = TextureState.loaded;
    if (isLoadingFinished()) {
      eventBus.emit(EventType.ALL_TEXTURES_LOADED);
    }
    return texture;
  }
}

export function unloadTexture(url: string | null): void {
  if (url && PIXI.Cache.has(url)) {
    textureState[url] = TextureState.unloading;
    PIXI.Assets.unload(url);
    delete textureState[url];
  }
}

export function isLoadingFinished(): boolean {
  for (const texture of Object.values(textureState)) {
    if (texture === TextureState.loading) return false;
  }
  return true;
}
