import randomColor from 'randomcolor';
import Color from 'colorjs.io';
import DeltaE from 'delta-e';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export function getRandomColorList(count: number): string[] {
  if (count <= 0) return [];
  const uniqueColors: { L: number; A: number; B: number }[] = [];
  const returnColors: string[] = [];
  for (let i = 0; i < count; i++) {
    let tryCount = 0;
    while (i === returnColors.length) {
      tryCount++;
      const colorHex = randomColor({ luminosity: 'dark' });
      const colorLab = new Color(colorHex).to('lab') as any;
      const color = { L: colorLab.l, A: colorLab.a, B: colorLab.b };
      let isUnique = true;
      for (const uniqueColor of uniqueColors) {
        isUnique = DeltaE.getDeltaE00(uniqueColor, color) > 35;
        if (!isUnique) break;
      }
      if (isUnique || tryCount > 50) {
        uniqueColors.push(color);
        returnColors.push(colorHex);
      }
    }
  }
  return returnColors;
}

export function getRainbowColorList(count: number): string[] {
  if (count <= 0) return [];
  const returnColors: string[] = [];
  for (let i = 0; i < count; i++) {
    const color = new Color(Color.spaces.hwb, [(360 / count) * i, 10, 0]).to(
      'sRGB'
    );
    returnColors.push(color.toString({ format: 'hex' }));
  }
  return returnColors;
}
