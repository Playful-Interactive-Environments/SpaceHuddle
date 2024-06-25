<template>
  <div class="canvasModule">
    <div class="controlButtons">
      <el-button
        type="primary"
        @click="templateSelectionVisible = !templateSelectionVisible"
        ><font-awesome-icon :icon="['fas', 'image']"
      /></el-button>
      <el-button
        type="primary"
        class="buttonSpacing"
        @click="changeIdeaWidth(2)"
        >+</el-button
      >
      <el-button type="primary" @click="changeIdeaWidth(-2)">-</el-button>
      <el-button
        type="primary"
        @click="toggleEraser"
        :class="{ eraserToggle: eraser }"
        class="eraser buttonSpacing"
        ><font-awesome-icon :icon="['fas', 'eraser']"
      /></el-button>
      <el-button
        type="primary"
        @click="changeLineWidth(2)"
        class="lineWidthPlus buttonSpacing"
        ><font-awesome-icon :icon="['fas', 'pen']" /> +</el-button
      >
      <el-button type="primary" @click="changeLineWidth(-2)"
        ><font-awesome-icon :icon="['fas', 'pen']" /> -</el-button
      >
      <p id="lineWidthIndicator">{{ lineWidth }}</p>
      <el-color-picker
        v-model="color"
        class="colorPicker buttonSpacing"
        @activeChange="colorChanged"
      />
      <el-button
        type="primary"
        @click="categoryToggle = !categoryToggle"
        class="categoryToggle buttonSpacing"
        v-if="taskType === 'CATEGORISATION'"
        ><font-awesome-icon :icon="['far', 'object-group']"
      /></el-button>
      <el-button
        type="primary"
        @click="sortIdeas"
        v-if="taskType === 'CATEGORISATION'"
        ><font-awesome-icon :icon="['far', 'object-group']"
      /></el-button>
      <el-button
        type="primary"
        @click="randomizePositions"
        class="shuffle buttonSpacing"
        ><font-awesome-icon :icon="['fas', 'shuffle']" />
      </el-button>
      <el-button type="primary" @click="clearCanvas" class="clear"
        ><font-awesome-icon :icon="['far', 'trash-can']"
      /></el-button>
      <el-button type="primary" @click="undo" class="buttonSpacing"
        ><font-awesome-icon :icon="['fas', 'rotate-left']"
      /></el-button>
      <el-button type="primary" @click="redo"
        ><font-awesome-icon :icon="['fas', 'rotate-right']"
      /></el-button>
      <div
        class="imageSelection"
        v-if="templateSelectionVisible"
        @mouseleave="templateSelectionVisible = false"
      >
        <el-button
          v-for="image in backgroundTemplates"
          class="templateImageButton"
          :key="image"
          @click="drawImageOnCanvas(image)"
          :style="{
            backgroundImage: 'url(' + image + ')',
            backgroundSize: 'cover',
            backgroundPosition: '50% 50%',
          }"
        ></el-button>
      </div>
    </div>
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
      <IdeaCard
        v-for="idea in ideas"
        :key="idea.id"
        :id="idea.id"
        :idea="idea"
        :is-editable="false"
        class="draggable-container"
        :class="idea.orderGroup"
        :allow-image-preview="!isDragging"
        :style="{
          minWidth: minIdeaWidth + 'rem',
          maxWidth: maxIdeaWidth + 'rem',
          width: ideaWidth + 'rem',
          zIndex: idea.parameter.zIndex ? idea.parameter.zIndex : 0,
          borderColor: getCategoryColor(idea),
          borderBottom: getBottomBorder(idea),
        }"
        @mousedown="bringToFront(idea)"
      />
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import { Idea } from '@/types/api/Idea';
import * as themeColors from '@/utils/themeColors';
import { Category } from '@/types/api/Category';
import { Task } from '@/types/api/Task';
import * as moduleService from '@/services/module-service';
import { Module } from '@/types/api/Module';

@Options({
  components: {
    IdeaCard,
  },
})
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly task!: Task;
  @Prop({ default: 0 }) readonly timeModifier!: number;
  @Prop({ default: false }) readonly timerEnded!: boolean;
  @Prop({ default: [] }) readonly ideas!: Idea[];
  @Prop({ default: [] }) readonly categories!: Category[];
  @Prop({ default: false }) readonly paused!: boolean;
  @Prop({ default: null }) readonly taskType!: string | null;

  module: Module | undefined = undefined;

  minIdeaWidth = 7;
  maxIdeaWidth = 21;
  ideaWidth = 15;

  isDragging = false;

  highestZ = 0;

  categorizedIdeas: Idea[][] = [];
  categoryToggle = true;

  lineWidth = 4;
  minLineWidth = 1;
  maxLineWidth = 80;
  eraser = false;

  color = themeColors.getEvaluatingColor();

  canvas: CanvasRenderingContext2D | null = null;
  canvasHeight = 0;
  canvasWidth = 0;
  paddingAdjustment = 10;

  x = 0;
  y = 0;
  isDrawing = false;

  backgroundTemplates: string[] = [
    '/assets/animations/canvas/quadrants.png',
    '/assets/animations/canvas/timeline.png',
    '/assets/animations/canvas/columns.png',
    '/assets/animations/canvas/rows.png',
  ];
  templateSelectionVisible = false;

  currentIdeaPositions: any[] = [];
  currentCanvasState = '';

  undoStates: any[] = [];
  redoStates: any[] = [];

  undoSteps = 10;

  CalcCanvasWidth(): number {
    const canvasArea = this.$refs.canvasArea as HTMLElement;
    return canvasArea ? canvasArea.offsetWidth : 0;
  }

  CalcCanvasHeight(): number {
    const canvasArea = this.$refs.canvasArea as HTMLElement;
    return canvasArea ? canvasArea.offsetHeight : 0;
  }

  async mounted() {
    this.makeAllDraggable();
  }

  updated(): void {
    this.makeAllDraggable();
    if (!this.loaded) {
      this.initializeZIndex();
      this.onIdeasLoaded();
    }
  }

  colorChanged(color: string) {
    this.color = color;
  }

  beforeUnmount(): void {
    const elements = document.getElementsByClassName('draggable-container');
    Array.from(elements as HTMLCollectionOf<HTMLElement>).forEach((el) => {
      el.onmousedown = null;
    });
  }

  @Watch('ideas', { immediate: true })
  ideasChanged(): void {
    this.getCategorizedIdeas();
  }

  loaded = false;
  onIdeasLoaded(): void {
    if (!this.loaded && this.ideas.length > 0) {
      this.module = this.task.modules.find(
        (module) => module.name === 'visualisation_master'
      );
      this.initializePositions();
      this.updateCanvasDimensions();
      this.initializeCanvas();
      this.loaded = true;
    }
  }

  initializeCanvas(): void {
    const c = document.getElementById('drawing-canvas') as HTMLCanvasElement;
    if (c) {
      this.canvas = c.getContext('2d');
      if (this.module) {
        const img = new Image();
        img.src = this.module.parameter.canvas.canvasImage;
        img.onload = () => {
          if (this.canvas) {
            this.canvas.drawImage(img, 0, 0);
          }
        };
        this.ideaWidth = this.module.parameter.canvas.ideaSize;
      }
    } else {
      setTimeout(() => {
        this.initializeCanvas();
      }, 1000);
    }
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
      this.isDragging = false;
      e.preventDefault();

      const startX = e.clientX - el.offsetLeft;
      const startY = e.clientY - el.offsetTop;

      const onMouseMove = (e: MouseEvent) => {
        this.isDragging = true;
        e.preventDefault(); // Prevent text selection
        this.moveElement(el, e.clientX - startX, e.clientY - startY);
      };

      const onMouseUp = () => {
        this.putIdeaPositionUndoStep(this.currentIdeaPositions);

        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);

        const idea = this.ideas.find((idea) => idea.id === el.id);
        if (idea) {
          this.saveIdeaPosition(idea.id, el.style.left, el.style.top);
          if (this.module) {
            moduleService.putModule(this.module);
          }
        }
      };

      document.addEventListener('mousemove', onMouseMove);
      document.addEventListener('mouseup', onMouseUp);
    };
  }

  initializePositions(): void {
    const elements = document.getElementsByClassName('draggable-container');

    this.highestZ = this.ideas.length;
    Array.from(elements as HTMLCollectionOf<HTMLElement>).forEach(
      (el, index) => {
        if (
          this.module &&
          this.module.parameter.canvasPositions &&
          this.module.parameter.canvasPositions.length > 0
        ) {
          const ideaPositionObject = this.module.parameter.canvasPositions.find(
            (pos) => pos.ideaId === this.ideas[index].id
          );
          if (ideaPositionObject) {
            el.style.left = ideaPositionObject.x;
            el.style.top = ideaPositionObject.y;
          }
        }
      }
    );
  }

  randomizePositions(): void {
    this.putIdeaPositionUndoStep(this.currentIdeaPositions);

    const canvasArea = this.$refs.canvasArea as HTMLElement;
    const canvasRect = canvasArea.getBoundingClientRect();
    const elements = document.getElementsByClassName('draggable-container');

    this.highestZ = this.ideas.length;
    Array.from(elements as HTMLCollectionOf<HTMLElement>).forEach(
      (el, index) => {
        const elRect = el.getBoundingClientRect();
        const randomX = Math.random() * (canvasRect.width - elRect.width);
        const randomY = Math.random() * (canvasRect.height - elRect.height);
        el.style.left = `${randomX}px`;
        el.style.top = `${randomY}px`;
        this.saveIdeaPosition(
          this.ideas[index].id,
          el.style.left,
          el.style.top
        );
      }
    );
    if (this.module) {
      moduleService.putModule(this.module);
    }
  }

  sortIdeas(): void {
    this.putIdeaPositionUndoStep(this.currentIdeaPositions);

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
          const canvasArea = this.$refs.canvasArea as HTMLElement;
          const canvasRect = canvasArea.getBoundingClientRect();
          const elRect = el.getBoundingClientRect();

          const randomX = Math.random() * 20 - 10;
          const randomY = Math.random() * 20 - 10;

          let left =
            Math.floor(Number(ideaElement.style.left.split('px')[0])) + randomX;

          let top =
            Math.floor(Number(ideaElement.style.top.split('px')[0])) + randomY;

          if (left < 0) left = 0;
          if (top < 0) top = 0;
          if (left + elRect.width > canvasRect.width)
            left = canvasRect.width - elRect.width - this.paddingAdjustment;
          if (top + elRect.height > canvasRect.height)
            top = canvasRect.height - elRect.height - this.paddingAdjustment;

          el.style.left = left + 'px';
          el.style.top = top + 'px';

          const idea = this.ideas.find((idea) => idea.id === el.id);
          if (idea) {
            this.saveIdeaPosition(idea.id, el.style.left, el.style.top);
          }
        });
      }
    }
    if (this.module) {
      moduleService.putModule(this.module);
    }
  }

  moveElement(el: HTMLElement, left: number, top: number): void {
    const canvasArea = this.$refs.canvasArea as HTMLElement;
    const canvasRect = canvasArea.getBoundingClientRect();
    const elRect = el.getBoundingClientRect();

    if (left < 0) left = 0;
    if (top < 0) top = 0;
    if (left + elRect.width > canvasRect.width)
      left = canvasRect.width - elRect.width - this.paddingAdjustment;
    if (top + elRect.height > canvasRect.height)
      top = canvasRect.height - elRect.height - this.paddingAdjustment;

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
      this.putCanvasUndoStep(this.currentCanvasState);

      this.drawLine(this.x, this.y, e.offsetX, e.offsetY);
      this.x = 0;
      this.y = 0;
      this.isDrawing = false;
      this.saveCanvas();
    }
  }

  clearCanvas() {
    if (this.canvas) {
      this.putCanvasUndoStep(this.currentCanvasState);

      const canvasElement = document.getElementById(
        'drawing-canvas'
      ) as HTMLCanvasElement;
      this.canvas.clearRect(0, 0, canvasElement.width, canvasElement.height);
      this.saveCanvas();
    }
  }

  drawImageOnCanvas(imageSrc: string) {
    this.putCanvasUndoStep(this.currentCanvasState);

    const img = new Image();
    img.src = imageSrc;
    img.onload = () => {
      if (!this.canvas) return;

      const canvas = this.canvas.canvas;
      const canvasWidth = canvas.width;
      const canvasHeight = canvas.height;

      // Calculate aspect ratios
      const imgAspectRatio = img.width / img.height;
      const canvasAspectRatio = canvasWidth / canvasHeight;

      let renderWidth, renderHeight, offsetX, offsetY;

      // Determine scaling factor and dimensions
      if (imgAspectRatio > canvasAspectRatio) {
        renderWidth = canvasHeight * imgAspectRatio;
        renderHeight = canvasHeight;
        offsetX = (canvasWidth - renderWidth) / 2;
        offsetY = 0;
      } else {
        renderWidth = canvasWidth;
        renderHeight = canvasWidth / imgAspectRatio;
        offsetX = 0;
        offsetY = (canvasHeight - renderHeight) / 2;
      }

      this.clearCanvas();
      this.canvas.drawImage(img, offsetX, offsetY, renderWidth, renderHeight);
      this.saveCanvas();
    };
  }

  bringToFront(idea: Idea): void {
    this.highestZ++;
    idea.parameter.zIndex = this.highestZ;
  }

  getCategoryColor(idea: Idea): string {
    if (this.categoryToggle) {
      const category = this.categories.find(
        (cat) => idea.orderGroup === cat.keywords
      );
      return category
        ? category.parameter.color
        : themeColors.getIdeaCardBorderColor();
    }
    return themeColors.getIdeaCardBorderColor();
  }

  getBottomBorder(idea: Idea): string {
    if (this.categoryToggle) {
      const category = this.categories.find(
        (cat) => idea.orderGroup === cat.keywords
      );
      return category ? `5px solid ${category.parameter.color}` : '';
    }
    return '';
  }

  saveIdeaPosition(id: string, x: string, y: string) {
    if (
      this.module &&
      this.module.parameter.canvasPositions &&
      this.module.parameter.canvasPositions.length > 0
    ) {
      const index = this.module.parameter.canvasPositions.findIndex(
        (item) => item.ideaId === id
      );
      if (index > -1) {
        this.module.parameter.canvasPositions[index] = {
          ideaId: id,
          x: x,
          y: y,
        };
      } else {
        this.module.parameter.canvasPositions.push({
          ideaId: id,
          x: x,
          y: y,
        });
      }
      this.currentIdeaPositions = [...this.module.parameter.canvasPositions];
    } else if (this.module) {
      this.module.parameter.canvasPositions = [
        {
          ideaId: id,
          x: x,
          y: y,
        },
      ];
      this.currentIdeaPositions = [...this.module.parameter.canvasPositions];
    }
  }

  saveCanvas(): void {
    const c = document.getElementById('drawing-canvas') as HTMLCanvasElement;
    if (c) {
      const dataUrl = c.toDataURL();
      if (this.module) {
        this.module.parameter.canvas = {
          canvasImage: dataUrl,
          ideaSize: this.ideaWidth,
        };
        this.currentCanvasState = dataUrl;
        moduleService.putModule(this.module);
      }
    }
  }

  putIdeaPositionUndoStep(canvasPositions: object): void {
    if (this.undoStates.length >= this.undoSteps) {
      this.undoStates.shift();
    }
    this.undoStates.push(canvasPositions);
  }

  putCanvasUndoStep(dataUrl: string): void {
    if (this.undoStates.length >= this.undoSteps) {
      this.undoStates.shift();
    }
    this.undoStates.push(dataUrl);
  }

  putIdeaPositionRedoStep(canvasPositions: object): void {
    if (this.redoStates.length >= this.undoSteps) {
      this.redoStates.shift();
    }
    this.redoStates.push(canvasPositions);
  }

  putCanvasRedoStep(dataUrl: string): void {
    if (this.redoStates.length >= this.undoSteps) {
      this.redoStates.shift();
    }
    this.redoStates.push(dataUrl);
  }

  undo(): void {
    if (this.undoStates.length > 0) {
      const undoState = this.undoStates.pop();
      if (typeof undoState === 'string') {
        this.setCanvas(undoState);
        this.putCanvasRedoStep(this.currentCanvasState);
      } else {
        this.putIdeaPositionRedoStep(this.currentIdeaPositions);
        this.setIdeaPositions(undoState);
      }
    }
  }

  redo(): void {
    if (this.redoStates.length > 0) {
      const redoState = this.redoStates.pop();
      if (typeof redoState === 'string') {
        this.setCanvas(redoState);
        this.putCanvasUndoStep(this.currentCanvasState);
      } else {
        this.putIdeaPositionUndoStep(this.currentIdeaPositions);
        this.setIdeaPositions(redoState);
      }
    }
  }

  setIdeaPositions(canvasPositions: any): void {
    const elements = document.getElementsByClassName('draggable-container');

    this.highestZ = this.ideas.length;

    Array.from(elements as HTMLCollectionOf<HTMLElement>).forEach(
      (el, index) => {
        if (canvasPositions && canvasPositions.length > 0) {
          const ideaPositionObject = canvasPositions.find(
            (pos) => pos.ideaId === this.ideas[index].id
          );
          if (ideaPositionObject) {
            el.style.left = ideaPositionObject.x;
            el.style.top = ideaPositionObject.y;

            const idea = this.ideas.find((idea) => idea.id === el.id);
            if (idea) {
              this.saveIdeaPosition(idea.id, el.style.left, el.style.top);
              if (this.module) {
                moduleService.putModule(this.module);
              }
            }
          }
        }
      }
    );
  }

  setCanvas(dataUrl: string) {
    if (dataUrl) {
      const img = new Image();
      img.src = dataUrl;
      img.onload = () => {
        if (this.canvas) {
          this.canvas.clearRect(0, 0, this.canvasWidth, this.canvasHeight);
          this.canvas.drawImage(img, 0, 0);
        }
      };
      this.saveCanvas();
    }
  }
}
</script>

<style scoped lang="scss">
.canvasModule {
  position: relative;
  width: 100%;
  height: 100%;
}

#canvasArea {
  position: relative;
  width: 100%;
  height: 100%;
  scrollbar-width: none;
  -ms-overflow-style: none;
  border: 4px solid var(--color-background-dark);
  border-radius: var(--border-radius-small);
  overflow: hidden;
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
  position: absolute;
  width: fit-content;
  display: flex;
  align-items: center;
  background-color: var(--color-background-dark);
  border-radius: var(--border-radius-small) var(--border-radius-small) 0 0;
  padding: 0.2rem;
  height: 3.5rem;
  top: -3.5rem;
  right: 1rem;
  * {
    margin: 0.2rem;
    padding-left: 0.3rem;
    padding-right: 0.3rem;
  }
}

#drawing-canvas {
  background-color: transparent;
  position: absolute;
}

.eraserToggle {
  background-color: var(--color-evaluating-dark);
}

.categoryToggle {
  background: linear-gradient(
    45deg,
    rgba(255, 0, 0, 1) 0%,
    rgba(255, 154, 0, 1) 10%,
    rgba(208, 222, 33, 1) 20%,
    rgba(79, 220, 74, 1) 30%,
    rgba(63, 218, 216, 1) 40%,
    rgba(47, 201, 226, 1) 50%,
    rgba(28, 127, 238, 1) 60%,
    rgba(95, 21, 242, 1) 70%,
    rgba(186, 12, 248, 1) 80%,
    rgba(251, 7, 217, 1) 90%,
    rgba(255, 0, 0, 1) 100%
  );
}

#lineWidthIndicator {
  margin: 0;
  font-weight: var(--font-weight-bold);
  font-size: var(--font-size-large);
  width: 2rem;
}

.buttonSpacing {
  margin-left: 1rem;
}

.imageSelection {
  position: absolute;
  top: 70%;
  right: 70%;
  background-color: var(--color-background-dark);
  border-radius: var(--border-radius);
  border: 4px solid var(--color-background);
  width: 100%;
  height: 6rem;
  z-index: 100000;
  display: flex;
  flex-direction: row;
  justify-content: space-evenly;
  align-items: center;
  .templateImageButton {
    height: 85%;
    width: 24%;
    overflow: hidden;
    background-color: var(--color-background);
  }
  .templateImageButton:hover {
    background-color: var(--color-background-darker);
  }
}
</style>
