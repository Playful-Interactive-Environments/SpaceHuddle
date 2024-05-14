import { ParticleState } from '@/modules/playing/moveit/organisms/CleanUpParticles.vue';

export function particleStateSum(particleState: {
  [key: string]: ParticleState;
}): ParticleState {
  let totalCount = 0;
  let collectedCount = 0;
  for (const particleName of Object.keys(particleState)) {
    totalCount += particleState[particleName].totalCount;
    collectedCount += particleState[particleName].collectedCount;
  }
  return {
    collectedCount: collectedCount,
    totalCount: totalCount,
    timelineOutside: [],
    timelineCollected: [],
    timelineInput: [],
  };
}

export function successStatus(particleState: {
  [key: string]: ParticleState;
}): number {
  const sum = particleStateSum(particleState);
  if (sum.totalCount > 0) return (sum.collectedCount / sum.totalCount) * 100;
  return 0;
}

export function successStatusText(particleState: {
  [key: string]: ParticleState;
}): string {
  const value = successStatus(particleState);
  if (value > 90) {
    return 'veryGood';
  }
  if (value > 70) {
    return 'good';
  }
  if (value > 50) {
    return 'notBad';
  }
  return 'improvable';
}

export function successRate(particleState: {
  [key: string]: ParticleState;
}): number {
  const value = successStatus(particleState);
  if (value > 90) {
    return 3;
  }
  if (value > 70) {
    return 2;
  }
  if (value > 50) {
    return 1;
  }
  return 0;
}
