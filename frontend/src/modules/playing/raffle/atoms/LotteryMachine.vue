<template>
  <div
    v-if="diameter"
    class="scene"
    :style="{
      '--diameter': `${diameter}px`,
    }"
  >
    <div class="lottery-machine">
      <div
        class="lottery-right-door"
        :class="
          machineState === MachineState.OPEN ||
          machineState === MachineState.MOVE
            ? 'animation'
            : ''
        "
      ></div>
      <div
        class="lottery-left-door"
        :class="
          machineState === MachineState.OPEN ||
          machineState === MachineState.MOVE
            ? 'animation'
            : ''
        "
      ></div>
      <div
        class="lottery-lift"
        :class="machineState === MachineState.LIFT_UP ? 'animation' : ''"
      ></div>
      <div class="lottery-ball" :class="ballAnimation">
        <font-awesome-icon
          v-if="
            (winner && machineState === MachineState.OPEN) ||
            machineState === MachineState.MOVE
          "
          ref="avatar"
          class="avatar"
          :icon="winner.symbol"
          :style="{ color: winner.color }"
        ></font-awesome-icon>
      </div>
      <Application
        class="lottery-machine-canvas"
        :backgroundColor="playingColor"
        :width="diameter"
        :height="diameter"
      >
        <container
          v-for="ball of displayBalls"
          :key="ball.avatar?.id"
          :x="ball.body.position.x"
          :y="ball.body.position.y"
        >
          <Graphics @render="(graphic) => drawBall(graphic, ball)" />
        </container>
      </Application>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Avatar } from '@/types/api/Participant';
import { Application } from 'vue3-pixi';
import * as PIXI from 'pixi.js';
import Matter from 'matter-js';
import { delay } from '@/utils/wait';
import * as themeColors from '@/utils/themeColors';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

interface Ball {
  avatar: Avatar | null;
  body: Matter.Body | null;
  x: number;
  y: number;
}

enum MachineState {
  WAIT = 'wait',
  SHAKE = 'shake',
  LIFT_UP = 'lift_up',
  LIFT_DOWN = 'lift_down',
  OPEN = 'open',
  MOVE = 'move',
}

@Options({
  components: {
    FontAwesomeIcon,
    Application,
  },
  emits: ['update:modelValue', 'lotteryDone'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class LotteryMachine extends Vue {
  @Prop() readonly options!: Avatar[];
  @Prop({ default: 300 }) readonly diameter!: number;
  @Prop({ default: false }) readonly modelValue!: boolean;
  balls: Ball[] = [];
  staticBalls: Ball[] = [];
  engine: Matter.Engine = Matter.Engine.create();
  runner: Matter.Runner = Matter.Runner.create();
  machineState: MachineState = MachineState.WAIT;
  winner: Avatar | null = null;
  readonly maxBalls = 50;

  MachineState = MachineState;

  get playingColor(): string {
    return themeColors.getPlayingColor('-light');
  }

  get ballRadius(): number {
    return this.diameter / 20;
  }

  get ballAnimation(): string {
    switch (this.machineState) {
      case MachineState.LIFT_UP:
        return 'animation-up';
      case MachineState.LIFT_DOWN:
        return 'animation-up animation-roll';
      case MachineState.OPEN:
        return 'no-transition';
      case MachineState.MOVE:
        return 'no-transition';
    }
    return 'no-transition';
  }

  get displayBalls(): Ball[] {
    const pool = this.balls.filter(
      (item) => item.avatar?.id !== this.winner?.id
    );
    if (pool.length > this.maxBalls) return pool.slice(0, this.maxBalls);
    return pool;
  }

  isInside(body: Matter.Body): boolean {
    const radiusBounds = this.diameter / 2;
    const radiusBoundsSquare = radiusBounds * radiusBounds;
    const x = body.position.x - radiusBounds;
    const y = body.position.y - radiusBounds;
    const radiusSquare = x * x + y * y;
    return radiusSquare <= radiusBoundsSquare;
  }

  mounted(): void {
    const radiusBounds = this.diameter / 2;
    for (let i = 0; i < 36; i++) {
      const alpha = i * 10;
      const radians = (alpha * Math.PI) / 180;
      const x =
        Math.sin(radians) * (radiusBounds + this.ballRadius) + radiusBounds;
      const y =
        Math.cos(radians) * (radiusBounds + this.ballRadius) + radiusBounds;
      this.staticBalls.push({
        avatar: null,
        body: Matter.Bodies.circle(x, y, this.ballRadius, { isStatic: true }),
        x: x,
        y: y,
      });
    }
    Matter.Composite.add(
      this.engine.world,
      this.staticBalls.map((item) => item.body) as Matter.Body[]
    );
    Matter.Runner.run(this.runner, this.engine);
  }

  @Watch('modelValue', { immediate: true })
  onModelValueChanged(): void {
    if (this.modelValue) {
      this.shakeBalls();
    }
  }

  async shakeBalls(): Promise<void> {
    this.machineState = MachineState.SHAKE;
    const time = 3000;
    const pause = 100;
    for (let i = 0; i < time / pause; i++) {
      for (const ball of this.displayBalls) {
        if (ball.body) {
          const forceMagnitude = 0.003;
          Matter.Body.applyForce(ball.body, ball.body.position, {
            x:
              (forceMagnitude + Matter.Common.random() * forceMagnitude) *
              Matter.Common.choose([1, -1]),
            y: -forceMagnitude + Matter.Common.random() * -forceMagnitude,
          });
        }
      }
      await delay(pause);
    }
    await delay(1000);

    for (const ball of this.displayBalls) {
      if (ball.body) {
        if (!this.isInside(ball.body)) {
          Matter.Body.setPosition(ball.body, {
            x: this.diameter / 2,
            y: this.diameter / 2,
          });
        }
      }
    }

    let winnerBall: Ball | null = null;
    if (this.balls.length > 0) {
      const index = Math.floor(Math.random() * this.balls.length);
      winnerBall = this.balls[index];
      this.winner = winnerBall.avatar;
      if (index < this.maxBalls) {
        if (winnerBall.body)
          Matter.Composite.remove(this.engine.world, winnerBall.body);
        if (this.displayBalls.length === this.maxBalls) {
          const addBall = this.displayBalls[this.maxBalls - 1];
          addBall.body = Matter.Bodies.circle(
            addBall.x,
            addBall.y,
            this.ballRadius
          );
          Matter.Composite.add(this.engine.world, addBall.body);
        }
      }
    }

    this.liftBallUp()
      .then(() => this.liftBallDown())
      .then(() => this.openDoors())
      .then(() => this.moveBallOut())
      .then(() => this.notifyWinner());
  }

  @Watch('options.length', { immediate: true })
  onOptionsChanged(): void {
    Matter.Composite.remove(
      this.engine.world,
      this.balls
        .filter((item) => item.body)
        .map((item) => item.body) as Matter.Body[]
    );
    this.balls = this.options.map((item, index) => {
      const oldBall = this.balls.find((ball) => ball.avatar?.id === item.id);
      const alpha = Math.random() * Math.PI * 2;
      const radius = Math.random() * (this.diameter / 2 - this.ballRadius);
      const x = oldBall?.body
        ? oldBall.body.position.x
        : oldBall
        ? oldBall.x
        : Math.sin(alpha) * radius + this.diameter / 2;
      const y = oldBall?.body
        ? oldBall.body.position.y
        : oldBall
        ? oldBall.y
        : Math.cos(alpha) * radius + this.diameter / 2;

      return {
        avatar: item,
        body:
          index < this.maxBalls
            ? Matter.Bodies.circle(x, y, this.ballRadius)
            : null,
        x: x,
        y: y,
      };
    });

    Matter.Composite.add(
      this.engine.world,
      this.displayBalls
        .filter((item) => item.body)
        .map((item) => item.body) as Matter.Body[]
    );
  }

  drawBall(graphics: PIXI.Graphics, ball: Ball): void {
    graphics.clear();
    const color = ball.avatar ? ball.avatar.color : '#ff0000';
    const radius = this.ballRadius;
    graphics.beginFill(color);
    graphics.drawCircle(graphics.x, graphics.y, radius);
    graphics.endFill();
    if (ball.avatar) {
      graphics.beginFill('#ffffff');
      graphics.drawCircle(
        graphics.x + radius / 4,
        graphics.y - radius / 4,
        radius / 4
      );
      graphics.endFill();
    }
  }

  liftBallUp(): Promise<void> {
    return new Promise((resolve) => {
      this.machineState = MachineState.LIFT_UP;
      setTimeout(resolve, 1000);
    });
  }

  liftBallDown(): Promise<void> {
    return new Promise((resolve) => {
      this.machineState = MachineState.LIFT_DOWN;
      setTimeout(resolve, 1500);
    });
  }

  openDoors(): Promise<void> {
    return new Promise((resolve) => {
      this.machineState = MachineState.OPEN;
      setTimeout(resolve, 1000);
    });
  }

  moveBallOut(): Promise<void> {
    return new Promise((resolve) => {
      this.machineState = MachineState.MOVE;
      setTimeout(resolve, 1000);
    });
  }

  notifyWinner(): void {
    this.machineState = MachineState.WAIT;
    this.$emit('update:modelValue', false);
    this.$emit('lotteryDone', this.winner);
  }
}
</script>

<style lang="scss" scoped>
.scene {
  padding: 7px;
}

.lottery-machine {
  position: relative;
  border-radius: 50%;
  width: calc(var(--diameter) * 1);
  height: calc(var(--diameter) * 1.25);
}
.lottery-machine-canvas {
  position: absolute;
  top: calc(var(--diameter) * 0.08);
  width: var(--diameter);
  height: var(--heigth);
  border: 7px solid var(--color-playing-dark);
  border-radius: inherit;
  box-sizing: border-box;
  transition: opacity 0.7s;
}

.lottery-machine .lottery-right-door,
.lottery-machine .lottery-left-door {
  background-color: var(--color-playing);
  border-radius: 0 var(--border-radius-xs) var(--border-radius-xs) 0;
  border: var(--color-playing-dark) solid 2px;
  position: absolute;
  width: calc(var(--diameter) / 5);
  height: calc(var(--diameter) / 10);
  left: calc(var(--diameter) * 0.5);
  bottom: 0;
  z-index: 4;
  transition: left 0.75s;
}
.lottery-machine .lottery-right-door.animation,
.lottery-machine .lottery-left-door.animation {
  left: calc(var(--diameter) * 0.6);
}
.lottery-machine .lottery-left-door {
  border-radius: var(--border-radius-xs) 0 0 var(--border-radius-xs);
  left: calc(var(--diameter) * 0.3);
}
.lottery-machine .lottery-left-door.animation {
  left: calc(var(--diameter) * 0.2);
}
.lottery-machine .lottery-lift {
  z-index: 3;
  position: absolute;
  background-color: var(--color-playing);
  width: calc(var(--diameter) / 10);
  height: 0;
  left: calc(var(--diameter) * 0.45);
  bottom: 0;
  box-sizing: border-box;
  border: 1px solid var(--color-playing-dark);
  border-radius: 2px;
  transition: height 1s;
}
.lottery-machine .lottery-lift.animation {
  height: calc(var(--diameter) * 1.18);
}
.lottery-machine .lottery-ball {
  background-color: white;
  z-index: 2;
  width: calc(var(--diameter) / 10);
  height: calc(var(--diameter) / 10);
  line-height: 1.375;
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 12px;
  border-radius: 50%;
  position: absolute;
  left: calc(var(--diameter) * 0.45);
  bottom: 0;
  transform-origin: 0 0;
  transform: rotate(0);
  transition: bottom 1s, transform 0.75s ease-in;
}
.lottery-machine .lottery-ball.animation-up {
  bottom: calc(var(--diameter) * 1.175);
}
.lottery-machine .lottery-ball.animation-roll {
  transform-origin: calc(var(--diameter) / 20) calc(var(--diameter) * 0.6);
  transform: rotate(180deg);
}
.lottery-machine .lottery-ball.no-transition {
  transition: none;
}
</style>
