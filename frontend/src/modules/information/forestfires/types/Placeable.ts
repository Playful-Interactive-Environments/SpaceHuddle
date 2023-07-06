export default interface Placeable {
  uuid: string;
  id: number;
  name: string;
  width: number;
  group: number;
  position: [number, number];
}
