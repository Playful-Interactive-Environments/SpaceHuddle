import Polygon from 'polygon';
import Vec2 from 'vec2';

export function containsPoint(
  polygonPoints: [number, number][],
  x: number,
  y: number
): boolean {
  const p = new Polygon(
    polygonPoints.map((point) => new Vec2(point[0], point[1]))
  );
  return p.containsPoint(new Vec2(x, y));
}

export function closestPoint(
  polygonPoints: [number, number][],
  x: number,
  y: number
): { point: [number, number]; distance: number } {
  const p = new Polygon(
    polygonPoints.map((point) => new Vec2(point[0], point[1]))
  );
  const input = new Vec2(x, y);
  const output = p.closestPointTo(new Vec2(x, y));
  return {
    point: [output.x, output.y],
    distance: output.distance(input),
  };
}

export function shrinkPolygon(
  polygonPoints: [number, number][],
  amount: number,
  offset: [number, number] = [0, 0]
): [number, number][] {
  const p = new Polygon(
    polygonPoints.map((point) => new Vec2(point[0], point[1]))
  );
  const newP = p.offset(amount).translate(new Vec2(offset[0], offset[1]));
  return newP.toArray().map((item) => [item[0], item[1]]);
}
