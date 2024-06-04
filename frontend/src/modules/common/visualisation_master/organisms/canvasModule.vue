<template>
  <div id="canvasArea" ref="canvasArea">
    <canvas
      id="drawing-canvas"
      :width="canvasWidth"
      :height="canvasHeight"
      @mousedown="beginDrawing"
      @mousemove="keepDrawing"
      @mouseup="stopDrawing"
      @mouseleave="stopDrawing"
    />
    <div class="controlButtons">
      <el-button type="primary" @click="changeIdeaWidth(2)">+</el-button>
      <el-button type="primary" @click="changeIdeaWidth(-2)">-</el-button>
      <el-button
        type="primary"
        @click="toggleEraser"
        :class="{ eraserToggle: eraser }"
        class="eraser"
        ><font-awesome-icon :icon="['fas', 'eraser']"
      /></el-button>
      <el-button
        type="primary"
        @click="changeLineWidth(2)"
        class="lineWidthPlus"
        ><font-awesome-icon :icon="['fas', 'pen']" /> +</el-button
      >
      <el-button type="primary" @click="changeLineWidth(-2)"
        ><font-awesome-icon :icon="['fas', 'pen']" /> -</el-button
      >
      <el-color-picker v-model="color" class="colorPicker" />
      <el-button
        type="primary"
        @click="categoryToggle = !categoryToggle"
        class="categoryToggle"
        ><font-awesome-icon :icon="['far', 'object-group']"
      /></el-button>
      <el-button type="primary" @click="sortIdeas"
        ><font-awesome-icon :icon="['fas', 'shuffle']" />
      </el-button>
      <el-button type="primary" @click="clearCanvas" class="clear"
        ><font-awesome-icon :icon="['far', 'trash-can']"
      /></el-button>
    </div>
    <IdeaCard
      v-for="idea in ideas"
      :key="idea.id"
      :id="idea.id"
      :idea="idea"
      :is-editable="false"
      class="draggable-container"
      :class="idea.orderGroup"
      :style="{
        minWidth: minIdeaWidth + 'rem',
        maxWidth: maxIdeaWidth + 'rem',
        width: ideaWidth + 'rem',
        zIndex: idea.parameter.zIndex ? idea.parameter.zIndex : 0,
        borderColor: getCategoryColor(idea),
      }"
      @mousedown="bringToFront(idea)"
    />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import { Idea } from '@/types/api/Idea';
import * as themeColors from '@/utils/themeColors';
import { nextTick } from 'vue';
import { Category } from '@/types/api/Category';

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
  @Prop({ default: [] }) readonly categories!: Category[];
  @Prop({ default: false }) readonly paused!: boolean;

  minIdeaWidth = 7;
  maxIdeaWidth = 21;
  ideaWidth = 15;

  highestZ = 0;

  categorizedIdeas: Idea[][] = [];
  categoryToggle = true;

  lineWidth = 4;
  minLineWidth = 1;
  maxLineWidth = 60;
  eraser = false;

  color = themeColors.getContrastColor();

  canvas: CanvasRenderingContext2D | null = null;
  canvasHeight = 0;
  canvasWidth = 0;
  x = 0;
  y = 0;
  isDrawing = false;

  CalcCanvasWidth(): number {
    const canvasArea = this.$refs.canvasArea as HTMLElement;
    return canvasArea ? canvasArea.offsetWidth : 0;
  }

  CalcCanvasHeight(): number {
    const canvasArea = this.$refs.canvasArea as HTMLElement;
    return canvasArea ? canvasArea.offsetHeight : 0;
  }

  async mounted() {
    await nextTick();
    this.updateCanvasDimensions();
    this.makeAllDraggable();
    this.initializePositions();
    const c = document.getElementById('drawing-canvas') as HTMLCanvasElement;
    if (c) {
      this.canvas = c.getContext('2d');
    }
  }

  updated(): void {
    this.makeAllDraggable();
  }

  unmounted(): void {
    const elements = document.getElementsByClassName('draggable-container');
    Array.from(elements as HTMLCollectionOf<HTMLElement>).forEach((el) => {
      el.onmousedown = null;
    });
  }

  @Watch('ideas', { immediate: true })
  ideasChanged(): void {
    this.initializeZIndex();
    this.getCategorizedIdeas();
  }

  initializeZIndex(): void {
    for (let i = 0; i < this.ideas.length; i++) {
      this.ideas[i].parameter.zIndex = i;
    }
  }

  updateCanvasDimensions(): void {
    this.canvasWidth = this.CalcCanvasWidth();
    this.canvasHeight = this.CalcCanvasHeight();
  }

  getCategorizedIdeas(): void {
    let orderGroups: string[] = [];
    for (const idea of this.ideas) {
      orderGroups.push(idea.orderGroup);
    }
    orderGroups = Array.from(new Set(orderGroups));
    this.categorizedIdeas = orderGroups.map((group) =>
      this.ideas.filter((idea) => idea.orderGroup === group)
    );
  }

  makeAllDraggable(): void {
    const elements = document.getElementsByClassName('draggable-container');
    Array.from(elements as HTMLCollectionOf<HTMLElement>).forEach((el) => {
      this.makeDraggable(el);
    });
  }

  makeDraggable(el: HTMLElement): void {
    el.style.position = 'absolute';
    el.style.cursor = 'move';
    el.style.userSelect = 'none';

    el.onmousedown = (e) => {
      e.preventDefault();

      const startX = e.clientX - el.offsetLeft;
      const startY = e.clientY - el.offsetTop;

      const onMouseMove = (e: MouseEvent) => {
        e.preventDefault(); // Prevent text selection
        this.moveElement(el, e.clientX - startX, e.clientY - startY);
      };

      const onMouseUp = () => {
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
      };

      document.addEventListener('mousemove', onMouseMove);
      document.addEventListener('mouseup', onMouseUp);
    };
  }

  initializePositions(): void {
    const canvasArea = this.$refs.canvasArea as HTMLElement;
    const canvasRect = canvasArea.getBoundingClientRect();
    const elements = document.getElementsByClassName('draggable-container');

    this.highestZ = this.ideas.length;
    Array.from(elements as HTMLCollectionOf<HTMLElement>).forEach((el) => {
      const elRect = el.getBoundingClientRect();
      const randomX = Math.random() * (canvasRect.width - elRect.width);
      const randomY = Math.random() * (canvasRect.height - elRect.height);
      el.style.left = `${randomX}px`;
      el.style.top = `${randomY}px`;
    });
  }

  sortIdeas(): void {
    const categoryIdeas: Idea[] = [];
    for (const cat of this.categories) {
      const ideas = this.ideas.filter(
        (idea) => idea.orderGroup === cat.keywords
      );
      let finalIdea = ideas[0];
      for (const idea of ideas) {
        if (
          idea.parameter.zIndex > finalIdea.parameter.zIndex ||
          !finalIdea.parameter.zIndex
        ) {
          finalIdea = idea;
        }
      }
      categoryIdeas.push(finalIdea);
    }
    for (const idea of categoryIdeas) {
      const ideaElement = document.getElementById(idea.id);
      if (ideaElement) {
        const elements = document.getElementsByClassName(idea.orderGroup);
        Array.from(elements as HTMLCollectionOf<HTMLElement>).forEach((el) => {
          const randomX = Math.random() * 20 - 10;
          const randomY = Math.random() * 20 - 10;
          el.style.left =
            Math.floor(Number(ideaElement.style.left.split('px')[0])) +
            randomX +
            'px';
          el.style.top =
            Math.floor(Number(ideaElement.style.top.split('px')[0])) +
            randomY +
            'px';
        });
      }
    }
  }

  moveElement(el: HTMLElement, left: number, top: number): void {
    const canvasArea = this.$refs.canvasArea as HTMLElement;
    const canvasRect = canvasArea.getBoundingClientRect();
    const elRect = el.getBoundingClientRect();

    if (left < 0) left = 0;
    if (top < 0) top = 0;
    if (left + elRect.width > canvasRect.width)
      left = canvasRect.width - elRect.width;
    if (top + elRect.height > canvasRect.height)
      top = canvasRect.height - elRect.height;

    requestAnimationFrame(() => {
      el.style.left = `${left}px`;
      el.style.top = `${top}px`;
    });
  }

  changeIdeaWidth(num: number): void {
    if (
      this.ideaWidth + num <= this.maxIdeaWidth &&
      this.ideaWidth + num >= this.minIdeaWidth
    ) {
      this.ideaWidth = this.ideaWidth + num;
    }
  }

  changeLineWidth(num: number): void {
    if (
      this.lineWidth + num <= this.maxLineWidth &&
      this.lineWidth + num >= this.minLineWidth
    ) {
      this.lineWidth = this.lineWidth + num;
    }
  }

  drawLine(x1: number, y1: number, x2: number, y2: number) {
    const ctx = this.canvas;
    if (ctx) {
      ctx.beginPath();
      if (this.eraser) {
        ctx.globalCompositeOperation = 'destination-out';
        ctx.strokeStyle = 'rgba(0,0,0,1)';
        ctx.lineWidth = this.lineWidth * 5;
      } else {
        ctx.globalCompositeOperation = 'source-over';
        ctx.strokeStyle = this.color;
        ctx.lineWidth = this.lineWidth;
      }
      ctx.moveTo(x1, y1);
      ctx.lineTo(x2, y2);
      ctx.stroke();
      ctx.closePath();
    }
  }

  toggleEraser() {
    this.eraser = !this.eraser;
  }

  beginDrawing(e: MouseEvent) {
    this.x = e.offsetX;
    this.y = e.offsetY;
    this.isDrawing = true;
  }

  keepDrawing(e: MouseEvent) {
    if (this.isDrawing) {
      this.drawLine(this.x, this.y, e.offsetX, e.offsetY);
      this.x = e.offsetX;
      this.y = e.offsetY;
    }
  }

  stopDrawing(e: MouseEvent) {
    if (this.isDrawing) {
      this.drawLine(this.x, this.y, e.offsetX, e.offsetY);
      this.x = 0;
      this.y = 0;
      this.isDrawing = false;
    }
  }

  clearCanvas() {
    if (this.canvas) {
      const canvasElement = document.getElementById(
        'drawing-canvas'
      ) as HTMLCanvasElement;
      this.canvas.clearRect(0, 0, canvasElement.width, canvasElement.height);
    }
  }

  bringToFront(idea: Idea): void {
    this.highestZ++;
    idea.parameter.zIndex = this.highestZ;
  }

  getCategoryColor(idea: Idea): string {
    if (this.categoryToggle) {
      const category = this.categories.filter(
        (cat) => idea.orderGroup === cat.keywords
      );
      if (category.length > 0) {
        return category[0].parameter.color;
      } else {
        return themeColors.getIdeaCardBorderColor();
      }
    } else {
      return themeColors.getIdeaCardBorderColor();
    }
  }
}
</script>

<style scoped lang="scss">
#canvasArea {
  position: relative;
  width: 100%;
  height: 100%;
  scrollbar-width: none;
  -ms-overflow-style: none;
  overflow: hidden;
  border: 2px solid var(--color-background-darker);
}

#canvasArea::-webkit-scrollbar {
  display: none;
}

.draggable-container {
  cursor: move;
  width: 15rem;
  user-select: none;
}

.controlButtons {
  position: relative;
  z-index: 10000;
  width: fit-content;
  display: flex;
  align-items: center;
  * {
    margin: 0.2rem;
  }
}

#drawing-canvas {
  background-color: transparent;
  position: absolute;
}

.eraserToggle {
  background-color: var(--color-evaluating-dark);
}

.eraser,
.lineWidthPlus,
.categoryToggle,
.clear,
.colorPicker {
  margin-left: 1.5rem;
}
</style>
