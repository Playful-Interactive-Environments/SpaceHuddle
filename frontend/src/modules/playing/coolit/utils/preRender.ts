import * as PIXI from 'pixi.js';
import * as pixiUtil from '@/utils/pixi';
import Color from 'colorjs.io';
import * as themeColors from '@/utils/themeColors';

export const minTemperature = -40;
export const maxTemperature = 60;
export const lowerTemperatureLimit = -20;
export const upperTemperatureLimit = 40;

/* eslint-disable @typescript-eslint/no-explicit-any*/
const minTempColor = new Color('#0000FF').to('srgb');
const maxTempColor = new Color('#FF00FF').to('srgb');
export const temperatureGradient = minTempColor.range(maxTempColor, {
  space: 'lch',
  hue: 'decreasing',
  outputSpace: 'srgb',
});

export function getColorForTemperature(temperature: number): any {
  return temperatureGradient(temperature);
}

export function getTemperatureGradientTexture(
  renderer: PIXI.Renderer
): PIXI.Texture {
  const colorStops: string[] = [];
  for (let i = 0; i < 20; i++) {
    colorStops.push(
      temperatureGradient(i / 20).toString({
        format: 'hex',
        collapse: false,
      })
    );
  }
  return pixiUtil.generateLinearGradientTexture(
    1024,
    10,
    renderer,
    colorStops,
    '#ffffff',
    pixiUtil.GradientDirection.LeftRight
  );
}

export function getTemperatureRange(temperature: number): number {
  return (temperature - minTemperature) / (maxTemperature - minTemperature);
}

export function generateTemperatureScale(
  renderer: PIXI.Renderer,
  width: number,
  textScaleFactor: number,
  $t: (key: string) => string
): PIXI.Texture {
  const highlightColorCold = themeColors.getContrastColor(); // '#ff0000';
  const highlightColorHot = themeColors.getRedColor(); // '#ff0000';
  const temperatureGradientTexture = getTemperatureGradientTexture(renderer);
  const temperatureHeight = 10;
  const temperatureScaleHeight = 15;
  const markerWidth = 4;
  const container = new PIXI.Container();
  const graphic = new PIXI.Graphics();
  //bar arctic
  graphic.beginFill(highlightColorCold);
  graphic.drawRect(
    0,
    0,
    getTemperatureRange(lowerTemperatureLimit) * width,
    temperatureScaleHeight
  );
  graphic.endFill();
  //bar tropic
  graphic.beginFill(highlightColorHot);
  graphic.drawRect(
    getTemperatureRange(upperTemperatureLimit) * width,
    0,
    (1 - getTemperatureRange(upperTemperatureLimit)) * width,
    temperatureScaleHeight
  );
  graphic.endFill();
  //temperature scale
  const textureScale = width / temperatureGradientTexture.width;
  const matrix: PIXI.Matrix = new PIXI.Matrix();
  matrix.scale(textureScale, textureScale);
  graphic.beginTextureFill({
    texture: temperatureGradientTexture,
    matrix: matrix,
  });
  graphic.drawRect(0, 0, width, temperatureHeight);
  graphic.endFill();
  //bar info arctic
  graphic.beginFill(highlightColorCold);
  graphic.drawRect(
    getTemperatureRange(lowerTemperatureLimit) * width - markerWidth / 2,
    0,
    markerWidth,
    temperatureScaleHeight
  );
  graphic.endFill();

  //bar info tropic
  graphic.beginFill(highlightColorHot);
  graphic.drawRect(
    getTemperatureRange(upperTemperatureLimit) * width - markerWidth / 2,
    0,
    markerWidth,
    temperatureScaleHeight
  );
  graphic.endFill();
  container.addChild(graphic as any);

  const lowerTemperatureLimitText = new PIXI.Text(
    `${lowerTemperatureLimit}°C`,
    new PIXI.TextStyle({
      fontFamily: 'Arial',
      fontSize: 24,
      fill: highlightColorCold,
    })
  );
  lowerTemperatureLimitText.x =
    getTemperatureRange(lowerTemperatureLimit) * width;
  lowerTemperatureLimitText.y = 15;
  lowerTemperatureLimitText.scale.set(textScaleFactor, textScaleFactor);
  lowerTemperatureLimitText.anchor.set(0.5, 0);
  container.addChild(lowerTemperatureLimitText as any);

  const lowerTemperatureInfoText = new PIXI.Text(
    $t('module.playing.coolit.participant.temperatureScale.lowerLimit'),
    new PIXI.TextStyle({
      fontFamily: 'Arial',
      fontSize: 18,
      fill: highlightColorCold,
    })
  );
  lowerTemperatureInfoText.x = 5;
  lowerTemperatureInfoText.y = 15;
  lowerTemperatureInfoText.scale.set(textScaleFactor, textScaleFactor);
  lowerTemperatureInfoText.anchor.set(0, 0);
  container.addChild(lowerTemperatureInfoText as any);

  const upperTemperatureLimitText = new PIXI.Text(
    `${upperTemperatureLimit}°C`,
    new PIXI.TextStyle({
      fontFamily: 'Arial',
      fontSize: 24,
      fill: highlightColorHot,
    })
  );
  upperTemperatureLimitText.x =
    getTemperatureRange(upperTemperatureLimit) * width;
  upperTemperatureLimitText.y = 15;
  upperTemperatureLimitText.scale.set(textScaleFactor, textScaleFactor);
  upperTemperatureLimitText.anchor.set(0.5, 0);
  container.addChild(upperTemperatureLimitText as any);

  const upperTemperatureInfoText = new PIXI.Text(
    $t('module.playing.coolit.participant.temperatureScale.upperLimit'),
    new PIXI.TextStyle({
      fontFamily: 'Arial',
      fontSize: 18,
      fill: highlightColorHot,
    })
  );
  upperTemperatureInfoText.x = width - 5;
  upperTemperatureInfoText.y = 15;
  upperTemperatureInfoText.scale.set(textScaleFactor, textScaleFactor);
  upperTemperatureInfoText.anchor.set(1, 0);
  container.addChild(upperTemperatureInfoText as any);

  return renderer.generateTexture(container);
}

export function generateTemperatureScaleResult(
  renderer: PIXI.Renderer,
  width: number,
  textScaleFactor: number
): PIXI.Texture {
  const temperatureScale = Array.from(
    { length: (maxTemperature - minTemperature) / 10 - 1 },
    (x, i) => i * 10 + minTemperature + 10
  );
  const highlightColor = themeColors.getContrastColor(); // '#ff0000';
  const temperatureGradientTexture = getTemperatureGradientTexture(renderer);
  const temperatureHeight = 10;
  const temperatureScaleHeight = 15;
  const markerWidth = 4;
  const container = new PIXI.Container();
  const graphic = new PIXI.Graphics();
  //temperature scale
  const textureScale = width / temperatureGradientTexture.width;
  const matrix: PIXI.Matrix = new PIXI.Matrix();
  matrix.scale(textureScale, textureScale);
  graphic.beginTextureFill({
    texture: temperatureGradientTexture,
    matrix: matrix,
  });
  graphic.drawRect(0, 0, width, temperatureHeight);
  graphic.endFill();

  //bar info
  for (const temperature of temperatureScale) {
    graphic.beginFill(highlightColor);
    graphic.drawRect(
      getTemperatureRange(temperature) * width - markerWidth / 2,
      0,
      markerWidth,
      temperatureScaleHeight
    );
    graphic.endFill();

    const text = new PIXI.Text(
      `${temperature}°C`,
      new PIXI.TextStyle({
        fontFamily: 'Arial',
        fontSize: 24,
        fill: highlightColor,
      })
    );
    text.x = getTemperatureRange(temperature) * width;
    text.y = 15;
    text.scale.set(textScaleFactor, textScaleFactor);
    text.anchor.set(0.5, 0);
    container.addChild(text as any);
  }
  container.addChild(graphic as any);

  return renderer.generateTexture(container);
}
