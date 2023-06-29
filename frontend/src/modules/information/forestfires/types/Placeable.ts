export default interface Placeable {
  uuid: string;
  id: number;
  name: string;
  size: number;
  group: number;
  position: [number, number];
}

export const PlaceableList: Placeable[] = [];
