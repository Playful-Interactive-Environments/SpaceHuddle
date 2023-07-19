import * as PIXI from 'pixi.js';

export default interface Placeable {
  uuid: string;
  id: number;
  type: string;
  name: string;
  texture: string | PIXI.Texture;
  width: number;
  shape: 'rect' | 'circle';
  position: [number, number];
  rotation: number;
  scale: number;
}
