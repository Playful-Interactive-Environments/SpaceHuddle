import * as Matter from 'matter-js/build/matter';
import { delay } from '@/utils/wait';

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

export function resetBody(
  body: Matter.Body,
  mouseConstraint: Matter.MouseConstraint | null = null
): void {
  if (mouseConstraint && mouseConstraint.body?.id === body.id) {
    mouseConstraint.body = null;
    Matter.Mouse.clearSourceEvents(mouseConstraint.mouse);
  }
  Matter.Body.setVelocity(body, { x: 0, y: 0 });
  Matter.Body.setAngularVelocity(body, 0);
  Matter.Body.setAngularSpeed(body, 0);
  Matter.Body.setSpeed(body, 0);
  body.constraintImpulse.x = 0;
  body.constraintImpulse.y = 0;
  body.constraintImpulse.angle = 0;
  body.force.x = 0;
  body.force.y = 0;
}

export async function updatePivot(
  body: Matter.Body,
  anchor: number | [number, number] = 0,
  delta = 100,
  alwaysUpdate = false
): Promise<void> {
  if (anchor || alwaysUpdate) {
    await delay(delta);
    if (!body) return;
    const xValues = body.vertices.map((item) => item.x).sort((a, b) => a - b);
    const minX = xValues[0];
    const maxX = xValues[xValues.length - 1];
    const yValues = body.vertices.map((item) => item.y).sort((a, b) => a - b);
    const minY = yValues[0];
    const maxY = yValues[xValues.length - 1];
    const width = maxX - minX;
    const height = maxY - minY;
    const anchorX = Array.isArray(anchor) ? anchor[0] : anchor;
    const anchorY = Array.isArray(anchor) ? anchor[1] : anchor;
    const deltaX = width * anchorX;
    const deltaY = height * anchorY;
    const position = [body.position.x, body.position.y];
    Matter.Body.setCentre(body, { x: deltaX + minX, y: deltaY + minY }, false);
    Matter.Body.setPosition(body, { x: position[0], y: position[1] });
  }
}
