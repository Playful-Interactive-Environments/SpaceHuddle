import * as PIXI from 'pixi.js';
import { GradientFactory } from '@pixi-essentials/gradients';
import { delay, until } from '@/utils/wait';
import { EventType } from '@/types/enum/EventType';
import { v4 as uuidv4 } from 'uuid';
import app from '@/main';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export function drawCircleWithGradient(
  circle: PIXI.Graphics,
  renderer: PIXI.Renderer,
  radius = 10,
  offset = 0.5,
  opacity = 1,
  color = '#ffffff'
): void {
  if ((circle as any).radius) radius = (circle as any).radius;
  if ((circle as any).color) color = (circle as any).color;
  const renderTexture = PIXI.RenderTexture.create({
    width: radius * 2,
    height: radius * 2,
  });
  if (!(renderer.renderTexture as any).renderer) return;
  let gradOpacity = Math.floor(opacity * 255).toString(16);
  if (gradOpacity.length === 1) gradOpacity = `0${gradOpacity}`;
  GradientFactory.createRadialGradient(renderer, renderTexture, {
    x0: radius,
    y0: radius,
    r0: 0,
    x1: radius,
    y1: radius,
    r1: radius,
    colorStops: [
      { color: `#FFFFFF${gradOpacity}`, offset: 1 - offset },
      { color: '#FFFFFF00', offset: 1 },
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

export function generateCircleOutlineTexture(
  radius: number,
  thickness: number,
  renderer: PIXI.Renderer,
  opacity = 1
): PIXI.Texture {
  const circle = new PIXI.Graphics();
  circle.lineStyle(thickness, '#ffffff', opacity, 0);
  circle.drawCircle(0, 0, radius);
  const bounds = new PIXI.Rectangle(-radius, -radius, radius * 2, radius * 2);
  return renderer.generateTexture(circle, {
    region: bounds,
  });
}

export function generateCircleGradientTexture(
  radius: number,
  renderer: PIXI.Renderer,
  offset = 0.5,
  opacity = 1
): PIXI.Texture {
  const circle = new PIXI.Graphics();
  drawCircleWithGradient(circle, renderer, radius, offset, opacity);
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

const tokenUrls: { [key: string]: string[] } = {};
export function createLoadingToken(): string {
  const token = uuidv4();
  tokenUrls[token] = [];
  return token;
}

export function cleanupToken(token: string): void {
  for (const url of tokenUrls[token]) {
    unloadTexture(url, token);
  }
  delete tokenUrls[token];
}

enum TextureState {
  loading = 'loading',
  loaded = 'loaded',
  unloading = 'unloading',
}
const textureState: { [url: string]: { state: TextureState; count: number } } =
  {};
export async function loadTexture(
  url: string,
  token: string | null = null
): Promise<any> {
  const eventBus = app.config.globalProperties.eventBus;
  if (token && !tokenUrls[token].includes(url)) tokenUrls[token].push(url);
  if (textureState[url]) {
    textureState[url].count++;
    await until(
      () =>
        textureState[url].state !== TextureState.loading &&
        textureState[url].state !== TextureState.unloading
    );
  } else {
    textureState[url] = {
      count: 1,
      state: TextureState.loading,
    };
  }
  if (PIXI.Cache.has(url)) {
    const texture = url.endsWith('.json')
      ? PIXI.Cache.get(url)
      : PIXI.Assets.get(url);
    await delay(100);
    const isValid = !Object.hasOwn(texture, 'valid') || texture.valid;
    const hasBaseTexture =
      !Object.hasOwn(texture, 'baseTexture') || !!texture.baseTexture?.resource;
    if (isValid && hasBaseTexture) return texture;
    else {
      await unloadTexture(url);
      textureState[url] = {
        count: 1,
        state: TextureState.loading,
      };
    }
  }

  textureState[url].state = TextureState.loading;
  if (eventBus) eventBus.emit(EventType.TEXTURES_LOADING_START);
  try {
    const texture = await PIXI.Assets.load(url);
    textureState[url].state = TextureState.loaded;
    if (isLoadingFinished()) {
      if (eventBus) eventBus.emit(EventType.ALL_TEXTURES_LOADED);
    }
    return texture;
  } catch (e) {
    delete textureState[url];
    if (isLoadingFinished()) {
      if (eventBus) eventBus.emit(EventType.ALL_TEXTURES_LOADED);
    }
    return null;
  }
}

export async function unloadTexture(
  url: string | null,
  token: string | null = null
): Promise<void> {
  if (
    url &&
    PIXI.Cache.has(url) &&
    textureState[url] &&
    textureState[url].state !== TextureState.unloading
  ) {
    textureState[url].count--;
    if (token) {
      await delay(1000);
      const otherTokens = Object.keys(tokenUrls).filter(
        (item) => item !== token
      );
      for (const otherToken of otherTokens) {
        if (tokenUrls[otherToken].includes(url)) return;
      }
    }
    if (textureState[url]) {
      textureState[url].state = TextureState.unloading;
      await PIXI.Assets.unload(url);
      delete textureState[url];
    }
  }
}

export function isLoadingFinished(): boolean {
  for (const texture of Object.values(textureState)) {
    if (texture.state === TextureState.loading) return false;
  }
  return true;
}

export async function convertTextureToBase64(
  texture: PIXI.Texture,
  renderer: PIXI.Renderer | null = null
): Promise<string> {
  const graphics = new PIXI.Graphics()
    .beginTextureFill({
      texture: texture,
    })
    .drawRect(0, 0, texture.width, texture.height);
  if (!renderer || !renderer.extract || !(renderer.extract as any).renderer)
    renderer = fallbackRenderer;
  return await renderer.extract.base64(graphics as PIXI.DisplayObject);
}

const fallbackRenderer = new PIXI.Renderer();
export async function convertSpritesheetToBase64(
  sheet: PIXI.Spritesheet,
  result: { [key: string]: string } = {},
  renderer: PIXI.Renderer | null = null
): Promise<{ [key: string]: string }> {
  if (!renderer) renderer = fallbackRenderer;
  for (const textureKey of Object.keys(sheet.textures)) {
    if (sheet.textures) {
      const texture = sheet.textures[textureKey];
      result[textureKey] = await convertTextureToBase64(texture, renderer);
    }
  }
  return result;
}
