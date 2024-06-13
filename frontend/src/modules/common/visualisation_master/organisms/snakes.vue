<template>
  <div id="snakeArea" ref="snakeArea">
    <div
      class="snake-segment"
      v-for="(idea, index) in ideas"
      :key="idea.id"
      :style="{
        transform: getPosition(index),
        width: snakeSegmentSize + 'rem',
        height: snakeSegmentSize + 'rem',
        zIndex: 100 + ideas.length - index,
        transition: getIdeaTransitionConfig(),
      }"
    >
      <IdeaCard :idea="idea" :is-editable="false" class="snakeIdea" />
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import { Idea } from '@/types/api/Idea';

@Options({
  components: {
    IdeaCard,
  },
})
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: 0 }) readonly timeModifier!: number;
  @Prop({ default: false }) readonly timerEnded!: boolean;
  @Prop({ default: [] }) readonly ideas!: Idea[];
  @Prop({ default: false }) readonly paused!: boolean;

  snakeSegmentSize = 12;
  snakePositions: Array<{ x: number; y: number }> = [];
  moveInterval: number | undefined = undefined;

  animationDuration = 3;

  headSwitch = false;

  categorizedIdeas: Idea[][] = [];
  mounted(): void {
    this.initSnakePos();
    clearInterval(this.moveInterval);
    this.moveInterval = setInterval(
      this.moveSnake,
      (this.animationDuration * 1000) / this.timeModifier
    );
  }

  unmounted(): void {
    if (this.moveInterval) {
      clearInterval(this.moveInterval);
    }
  }

  @Watch('ideas', { immediate: true })
  ideasChanged(): void {
    this.initSnakePos();
    this.getCategorizedIdeas();
  }

  @Watch('timeModifier', { immediate: true })
  timeModifierChanged(): void {
    clearInterval(this.moveInterval);
    this.moveInterval = setInterval(
      this.moveSnake,
      (this.animationDuration * 1000) / this.timeModifier
    );
  }

  getCategorizedIdeas(): void {
    let orderGroups: string[] = [];
    for (const idea of this.ideas) {
      orderGroups.push(idea.orderGroup);
    }
    orderGroups = Array.from(new Set(orderGroups));
    for (const group of orderGroups) {
      this.categorizedIdeas.push(
        this.ideas.filter((idea) => idea.orderGroup === group)
      );
    }
  }

  getIdeaTransitionConfig(): string {
    return `transform ${
      (this.animationDuration * 1000) / this.timeModifier
    }ms linear, width 0.5s ease, height 0.5s ease`;
  }

  moveSnake(i = -1): void {
    if (!this.paused) {
      let randomNumber = i;
      if (i === -1) {
        randomNumber = Math.floor(Math.random() * 4);
      }
      let headSegment = this.snakePositions[0];
      if (this.headSwitch) {
        headSegment = this.snakePositions[this.snakePositions.length - 1];
      }

      const newHeadPosition = { x: headSegment.x, y: headSegment.y };

      switch (randomNumber) {
        case 0:
          newHeadPosition.y -= this.snakeSegmentSize;
          break;
        case 1:
          newHeadPosition.y += this.snakeSegmentSize;
          break;
        case 2:
          newHeadPosition.x -= this.snakeSegmentSize;
          break;
        case 3:
          newHeadPosition.x += this.snakeSegmentSize;
          break;
      }

      // Get the boundaries of the snake area using $refs

      // Check for collision with itself
      const isCollision = this.snakePositions.some(
        (segment) =>
          segment.x === newHeadPosition.x && segment.y === newHeadPosition.y
      );

      // Check for boundaries
      const isOutOfBounds = this.checkOutOfBounds(newHeadPosition);

      if (!isCollision && !isOutOfBounds) {
        if (this.headSwitch) {
          this.snakePositions.push(newHeadPosition);
          this.snakePositions.shift();
        } else {
          this.snakePositions.unshift(newHeadPosition);
          this.snakePositions.pop();
        }
      } else {
        if (i <= 3) {
          this.moveSnake(i + 1);
        } else if (i > 3) {
          this.headSwitch = !this.headSwitch;
          if (this.headSwitch) {
            if (
              this.checkOutOfBounds(
                this.snakePositions[this.snakePositions.length - 1]
              )
            ) {
              this.initSnakePos();
            } else {
              console.log(this.headSwitch);
              this.moveSnake(-1);
            }
          } else {
            if (this.checkOutOfBounds(this.snakePositions[0])) {
              this.initSnakePos();
            } else {
              console.log(this.headSwitch);
              this.moveSnake(-1);
            }
          }
        }
        // Optionally, handle collision or out-of-bounds (e.g., change direction, stop game, etc.)
      }
    }
  }

  checkOutOfBounds(pos: any) {
    const snakeArea = this.$refs.snakeArea as HTMLElement;
    const boundaryWidth = snakeArea.clientWidth;
    const boundaryHeight = snakeArea.clientHeight;

    return (
      this.convertRemToPixels(pos.x) < 0 ||
      this.convertRemToPixels(pos.y) < 0 ||
      this.convertRemToPixels(pos.x) +
        this.convertRemToPixels(this.snakeSegmentSize) >
        boundaryWidth ||
      this.convertRemToPixels(pos.y) +
        this.convertRemToPixels(this.snakeSegmentSize) >
        boundaryHeight
    );
  }

  convertRemToPixels(rem: number) {
    return (
      rem * parseFloat(getComputedStyle(document.documentElement).fontSize)
    );
  }

  initSnakePos(): void {
    this.snakePositions = [];
    for (let i = 0; i < this.ideas.length; i++) {
      this.snakePositions.push({ x: this.snakeSegmentSize * i, y: 0 });
    }
  }

  getPosition(index: number): string {
    if (this.snakePositions.length !== 0) {
      return `translate(${this.snakePositions[index].x}rem, ${this.snakePositions[index].y}rem)`;
    }
    return '';
  }
}
</script>

<style scoped lang="scss">
#snakeArea {
  position: relative;
  width: 100%;
  height: 100%;
  scrollbar-width: none; /* Hide scrollbar for Firefox */
  -ms-overflow-style: none;
}

#snakeArea::-webkit-scrollbar {
  display: none;
}

.snake-segment {
  position: absolute;

  .snakeIdea {
    width: 100%;
    height: 100%;
    overflow-y: scroll;
    scrollbar-width: none; /* Hide scrollbar for Firefox */
    -ms-overflow-style: none;
  }
  .snakeIdea::-webkit-scrollbar {
    display: none;
  }
}

.snake-segment:hover {
  z-index: 10000 !important;
  width: 20rem !important;
  height: 25rem !important;
}
</style>
