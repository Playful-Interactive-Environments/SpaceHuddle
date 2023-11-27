import * as PIXI from 'pixi.js';
import { GradientFactory } from '@pixi-essentials/gradients';
import { until } from '@/utils/wait';
import { Emitter } from 'mitt';
import { EventType } from '@/types/enum/EventType';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export function drawCircleWithGradient(
  circle: PIXI.Graphics,
  renderer: PIXI.Renderer,
  radius = 10,
  color = '#ffffff'
): void {
  if ((circle as any).radius) radius = (circle as any).radius;
  if ((circle as any).color) color = (circle as any).color;
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
    color: color,
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
  width = 10,
  height = 10,
  colorStops = ['#ffffff00', '#ffffffff'],
  tintColor = '#ffffff',
  direction = GradientDirection.TopBottom
): void {
  if ((rect as any).width) width = (rect as any).width;
  if ((rect as any).height) height = (rect as any).height;
  if ((rect as any).color) tintColor = (rect as any).color;
  if ((rect as any).direction) direction = (rect as any).direction;
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
  const colorStopsList = colorStops.map((color, index) => {
    return {
      color: color,
      offset: index / colorStops.length,
    };
  });
  GradientFactory.createLinearGradient(renderer, renderTexture, {
    x0: x0,
    y0: y0,
    x1: x1,
    y1: y1,
    colorStops: colorStopsList,
  });
  rect.clear();
  rect.beginTextureFill({
    texture: renderTexture,
    color: tintColor,
    alpha: 0.9,
  });
  rect.drawRect(0, 0, width, height);
  rect.endFill();
}

export function generateCircleGradientTexture(
  radius: number,
  renderer: PIXI.Renderer
): PIXI.Texture {
  const circle = new PIXI.Graphics();
  drawCircleWithGradient(circle, renderer, radius);
  const bounds = new PIXI.Rectangle(-radius, -radius, radius * 2, radius * 2);
  return renderer.generateTexture(circle, {
    region: bounds,
    scaleMode: PIXI.SCALE_MODES.LINEAR,
  });
}

export function generateLinearGradientTexture(
  width: number,
  height: number,
  renderer: PIXI.Renderer,
  colorStops = ['#ffffff00', '#ffffffff'],
  tintColor = '#ffffff',
  direction = GradientDirection.TopBottom
): PIXI.Texture {
  const rect = new PIXI.Graphics();
  drawRectWithGradient(
    rect,
    renderer,
    width,
    height,
    colorStops,
    tintColor,
    direction
  );

  const bounds = new PIXI.Rectangle(0, 0, width, height);
  return renderer.generateTexture(rect, {
    region: bounds,
    scaleMode: PIXI.SCALE_MODES.LINEAR,
  });
}

export function generateStackedTexture(
  textures: PIXI.Texture[],
  renderer: PIXI.Renderer,
  percentagePerSubLevel = 100
): PIXI.Texture {
  const graphic = new PIXI.Graphics();
  const width = textures[0].width,
    height = textures[0].height;
  for (let i = 0; i < textures.length; i++) {
    const texture = textures[i];
    const matrix: PIXI.Matrix = new PIXI.Matrix();
    const textureScale = width / texture.width;
    const layerScale = textureScale * Math.pow(percentagePerSubLevel / 100, i);
    const layerWidth = texture.width * layerScale;
    const layerHeight = texture.height * layerScale;
    matrix.scale(layerScale, layerScale);
    matrix.translate(-layerWidth / 2, -layerHeight / 2);
    graphic.beginTextureFill({
      texture: texture,
      alpha: 1,
      matrix: matrix,
    });
    graphic.drawRect(
      -layerWidth / 2,
      -layerHeight / 2,
      layerWidth,
      layerHeight
    );
    graphic.endFill();
  }
  const bounds = new PIXI.Rectangle(-width / 2, -height / 2, width, height);
  return renderer.generateTexture(graphic, {
    region: bounds,
    scaleMode: PIXI.SCALE_MODES.LINEAR,
  });
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
  if (PIXI.Cache.has(url)) {
    return PIXI.Cache.get(url);
  } else {
    textureState[url] = TextureState.loading;
    if (eventBus) eventBus.emit(EventType.TEXTURES_LOADING_START);
    try {
      const texture = await PIXI.Assets.load(url);
      textureState[url] = TextureState.loaded;
      if (isLoadingFinished()) {
        if (eventBus) eventBus.emit(EventType.ALL_TEXTURES_LOADED);
      }
      return texture;
    } catch (e) {
      delete textureState[url];
      return null;
    }
  }
}

export async function unloadTexture(url: string | null): Promise<void> {
  if (
    url &&
    PIXI.Cache.has(url) &&
    textureState[url] &&
    textureState[url] !== TextureState.unloading
  ) {
    textureState[url] = TextureState.unloading;
    PIXI.Assets.unload(url).then(() => {
      delete textureState[url];
    });
  }
}

export function isLoadingFinished(): boolean {
  for (const texture of Object.values(textureState)) {
    if (texture === TextureState.loading) return false;
  }
  return true;
}
