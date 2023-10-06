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

export function calculateHitPoint(
  obstacle: Matter.Body,
  hitObject: Matter.Body
): [number, number] {
  return [
    hitObject.position.x - obstacle.bounds.min.x,
    hitObject.position.y - obstacle.bounds.min.y,
  ];
}

export function getVisibleBodyBounds(
  body: Matter.Body,
  gameWidth: number,
  gameHeight: number
): Matter.Bounds {
  const screenMin = [0, 0];
  const screenMax = [gameWidth, gameHeight];
  const minX =
    body.bounds.min.x < screenMin[0] ? screenMin[0] : body.bounds.min.x;
  const maxX =
    body.bounds.max.x > screenMax[0] ? screenMax[0] : body.bounds.max.x;
  const minY =
    body.bounds.min.y < screenMin[1] ? screenMin[1] : body.bounds.min.y;
  const maxY =
    body.bounds.min.y < screenMax[1] ? screenMax[1] : body.bounds.min.y;
  return {
    min: {
      x: minX,
      y: minY,
    },
    max: {
      x: maxX,
      y: maxY,
    },
  };
}

export function calculateVisibleHitPoint(
  obstacle: Matter.Body,
  hitObject: Matter.Body,
  gameWidth: number,
  gameHeight: number
): [number, number] {
  const boundsObstacle = getVisibleBodyBounds(obstacle, gameWidth, gameHeight);
  return [
    hitObject.position.x - boundsObstacle.min.x,
    hitObject.position.y - boundsObstacle.min.y,
  ];
}
