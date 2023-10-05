import * as Matter from 'matter-js/build/matter';

export function createPolygonBody(
  options: {
    [key: string]: string | number | boolean | object;
  },
  x: number,
  y: number,
  width: number,
  height: number,
  shape: [number, number][],
  updateCentroid = true
): Matter.Body {
  const calculateCentroid = (shape: [number, number][]): [number, number] => {
    const path = shape.map((item) => {
      return { x: item[0], y: item[1] };
    });
    const relativeBody = Matter.Bodies.fromVertices(0, 0, [path]);
    const minX = Math.min(...relativeBody.vertices.map((item) => item.x));
    const minY = Math.min(...relativeBody.vertices.map((item) => item.y));

    const minShapeX = Math.min(...shape.map((item) => item[0]));
    const minShapeY = Math.min(...shape.map((item) => item[1]));

    const deltaX = minX + 50 - minShapeX;
    const deltaY = minY + 50 - minShapeY;
    return [50 - deltaX, 50 - deltaY];
  };
  const path = shape.map((item) => {
    return { x: (item[0] / 100) * width, y: (item[1] / 100) * height };
  });
  const body = Matter.Bodies.fromVertices(x, y, [path], options);
  if (updateCentroid) {
    const centroid = calculateCentroid(shape);
    const deltaX = (width * (50 - centroid[0])) / 100;
    const deltaY = (height * (50 - centroid[1])) / 100;
    Matter.Body.setCentre(body, { x: deltaX, y: deltaY }, true);
    Matter.Body.setPosition(body, { x: x, y: y });
  }
  return body;
}
