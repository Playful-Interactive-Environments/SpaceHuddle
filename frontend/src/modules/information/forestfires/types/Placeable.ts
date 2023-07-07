export default interface Placeable {
  uuid: string;
  id: number;
  name: string;
  width: number;
  shape: 'rect' | 'circle';
  position: [number, number];
  rotation: number;
}
