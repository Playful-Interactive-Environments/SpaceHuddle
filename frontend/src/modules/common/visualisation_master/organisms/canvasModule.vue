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
        @click="toggleTextInputMode"
        class="buttonSpacing"
        :class="{ textInputToggle: textInputMode }"
      >
        <font-awesome-icon :icon="['fas', 'font']" />
      </el-button>
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
      <div
        v-for="idea in ideas"
        :key="idea.id"
        :id="idea.id"
        class="draggable-container"
        :class="idea.orderGroup"
        :style="{
          minWidth: minIdeaWidth + 'rem',
          maxWidth: maxIdeaWidth + 'rem',
          width: getIdeaSize(idea.id),
          zIndex: idea.parameter.zIndex ? idea.parameter.zIndex : 0,
          borderColor: getCategoryColor(idea),
          borderBottom: getBottomBorder(idea),
        }"
        @mouseenter="ideaSizeButtonHover = idea.id"
        @mouseleave="ideaSizeButtonHover = null"
      >
        <IdeaCard
          :idea="idea"
          :is-editable="false"
          :allow-image-preview="!isDragging"
          class="idea-container"
          @mousedown="bringToFront(idea)"
        />
        <div
          class="sizeButton-container"
          :style="{
            opacity: getSizeButtonsVisible(idea.id) ? 1 : 0,
            pointerEvents: getSizeButtonsVisible(idea.id) ? 'all' : 'none',
          }"
          :id="'sizeButtons' + idea.id"
        >
          <el-button class="sizeButton" @click="changeIdeaSize(idea.id, 2)"
            >+</el-button
          >
          <el-button class="sizeButton" @click="changeIdeaSize(idea.id, -2)"
            >-</el-button
          >
        </div>
      </div>
      <div ref="textContainer" class="text-container"></div>
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
/* eslint-disable @typescript-eslint/no-explicit-any*/
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
  ideaWidth = 11;

  isDragging = false;

  highestZ = 0;

  categorizedIdeas: Idea[][] = [];
  categoryToggle = true;

  ideaSizeButtonHover: string | null = null;

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
  currentTextPositions: any[] = [];

  undoStates: any[] = [];
  redoStates: any[] = [];

  undoSteps = 20;

  textInputMode = false;
  textInputElement: HTMLInputElement | null = null;

  ideaSizes: any[] = [];

  CalcCanvasWidth(): number {
    const canvasArea = this.$refs.canvasArea as HTMLElement;
    return canvasArea ? canvasArea.offsetWidth : 0;
  }

  CalcCanvasHeight(): number {
    const canvasArea = this.$refs.canvasArea as HTMLElement;
    return canvasArea ? canvasArea.offsetHeight : 0;
  }

  getSizeButtonsVisible(buttonID: string): boolean {
    if (this.ideaSizeButtonHover) {
      return this.ideaSizeButtonHover === buttonID;
    } else {
      return false;
    }
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
      this.initializeTextPositions();
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
        if (
          this.module.parameter.canvas &&
          this.module.parameter.canvas.canvasImage
        ) {
          img.src = this.module.parameter.canvas.canvasImage;
        } else {
          img.src = '';
        }
        img.onload = () => {
          if (this.canvas) {
            this.canvas.drawImage(img, 0, 0);
          }
        };
        if (this.module.parameter.ideaSizes) {
          this.ideaSizes = this.module.parameter.ideaSizes;
        } else {
          this.ideaSizes = [];
        }
        this.currentCanvasState = img.src;
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

    const textElements = document.getElementsByClassName('textElement');
    Array.from(textElements as HTMLCollectionOf<HTMLElement>).forEach((el) => {
      this.makeTextDraggable(el);
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

  makeTextDraggable(el: HTMLElement): void {
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
        this.putTextUndoStep(this.currentTextPositions);

        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);

        this.saveTextPosition(el.cloneNode(true) as HTMLElement);
        if (this.module) {
          moduleService.putModule(this.module);
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
    if (this.module) {
      if (this.module.parameter.canvasPositions) {
        this.currentIdeaPositions = [...this.module.parameter.canvasPositions];
      } else {
        this.currentIdeaPositions = [];
      }
    }
  }

  initializeTextPositions(): void {
    if (
      this.module &&
      this.module.parameter.textPositions &&
      this.module.parameter.textPositions.length > 0
    ) {
      const textContainer = this.$refs.textContainer as HTMLElement;

      this.module.parameter.textPositions.forEach((data) => {
        const pElement = document.createElement('p');
        pElement.textContent = data.text;
        pElement.style.position = data.style.position;
        pElement.style.left = data.style.left;
        pElement.style.top = data.style.top;
        pElement.style.fontSize = data.style.fontSize;
        pElement.style.color = data.style.color;
        pElement.style.transition = data.style.transition;
        pElement.className = 'textElement';
        pElement.id = data.id;

        textContainer.appendChild(pElement);
      });
    }

    if (this.module) {
      if (this.module.parameter.textPositions) {
        this.currentTextPositions = [...this.module.parameter.textPositions];
      } else {
        this.currentTextPositions = [];
      }
    }
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

  changeIdeaSize(id: string, num: number): void {
    const sizeEntry = this.ideaSizes.find((size) => size.id === id);
    if (sizeEntry) {
      if (
        sizeEntry.size + num <= this.maxIdeaWidth &&
        sizeEntry.size + num >= this.minIdeaWidth
      ) {
        sizeEntry.size += num;
      }
    } else {
      this.ideaSizes.push({ id: id, size: this.ideaWidth + num });
    }
    this.saveIdeaSize();
  }

  getIdeaSize(id: string): string {
    const sizeEntry = this.ideaSizes.find((size) => size.id === id);
    if (sizeEntry) {
      return sizeEntry.size + 'rem';
    }
    return this.ideaWidth + 'rem';
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

      const ctx = this.canvas;
      if (ctx) {
        ctx.globalCompositeOperation = 'source-over';
      }
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

  toggleTextInputMode() {
    this.textInputMode = !this.textInputMode;
    const canvasArea = this.$refs.canvasArea as HTMLElement;
    if (this.textInputMode) {
      canvasArea.addEventListener('click', this.handleCanvasClick);
    } else {
      canvasArea.removeEventListener('click', this.handleCanvasClick);
      this.removeTextInputElement();
    }
  }

  handleCanvasClick(e: MouseEvent) {
    if (this.textInputMode) {
      const rect = (
        this.$refs.canvasArea as HTMLElement
      ).getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      this.createTextInputElement(x, y);
    }
  }

  createTextInputElement(x: number, y: number) {
    const canvasArea = this.$refs.canvasArea as HTMLElement;
    const canvasRect = canvasArea.getBoundingClientRect();

    this.removeTextInputElement();
    this.textInputElement = document.createElement('input');
    this.textInputElement.type = 'text';
    this.textInputElement.className = 'textInput';
    this.textInputElement.style.position = 'absolute';
    this.textInputElement.style.zIndex = '1000';
    this.textInputElement.style.fontSize = '24px';
    this.textInputElement.style.backgroundColor = 'transparent';
    this.textInputElement.style.border = 'none';
    this.textInputElement.style.width = '5rem';

    this.textInputElement.addEventListener(
      'keydown',
      this.handleTextInputKeyDown
    );
    (this.$refs.canvasArea as HTMLElement).appendChild(this.textInputElement);

    const elRect = this.textInputElement.getBoundingClientRect();

    if (x < 0) x = 0;
    if (y < 0) y = 0;
    if (x + elRect.width > canvasRect.width)
      x = canvasRect.width - elRect.width - this.paddingAdjustment;
    if (y + elRect.height > canvasRect.height)
      y = canvasRect.height - elRect.height - this.paddingAdjustment;

    this.textInputElement.style.left = `${x}px`;
    this.textInputElement.style.top = `${y}px`;

    this.textInputElement.focus();
  }

  handleTextInputKeyDown(e: KeyboardEvent) {
    if (e.key === 'Enter' && this.textInputElement) {
      const text = this.textInputElement.value;
      const x = parseInt(this.textInputElement.style.left, 10);
      const y = parseInt(this.textInputElement.style.top, 10);
      this.renderTextOnCanvas(text, x, y);
      this.removeTextInputElement();
      this.textInputMode = false;
    }
  }

  removeTextInputElement() {
    if (this.textInputElement) {
      this.textInputElement.removeEventListener(
        'keydown',
        this.handleTextInputKeyDown
      );
      (this.$refs.canvasArea as HTMLElement).removeChild(this.textInputElement);
      this.textInputElement = null;
    }
  }

  renderTextOnCanvas(text: string, x: number, y: number) {
    this.putTextUndoStep(this.currentTextPositions);

    const pElement = document.createElement('p');
    pElement.textContent = text;
    pElement.style.position = 'absolute';
    pElement.style.left = `${x}px`;
    pElement.style.top = `${y}px`;
    pElement.style.fontSize = '16pt';
    pElement.style.transition = 'all 0.15s ease';
    pElement.className = 'textElement';
    pElement.style.color = this.color;
    pElement.id = text + Math.floor(Math.random() * 9999);

    const textContainer = this.$refs.textContainer as HTMLElement;
    textContainer.appendChild(pElement);

    this.saveTextPosition(pElement.cloneNode(true) as HTMLElement);
    if (this.module) {
      moduleService.putModule(this.module);
    }
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

  saveIdeaSize(): void {
    if (this.module) {
      this.module.parameter.ideaSizes = this.ideaSizes;
    }
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

  saveTextPosition(el: HTMLElement) {
    if (
      this.module &&
      this.module.parameter.textPositions &&
      this.module.parameter.textPositions.length > 0
    ) {
      const index = this.module.parameter.textPositions.findIndex(
        (item) => item.id === el.id
      );
      if (index > -1) {
        this.module.parameter.textPositions[index] = {
          style: el.style,
          id: el.id,
          text: el.innerText,
        };
      } else {
        this.module.parameter.textPositions.push({
          style: el.style,
          id: el.id,
          text: el.innerText,
        });
      }
      this.currentTextPositions = [...this.module.parameter.textPositions];
    } else if (this.module) {
      this.module.parameter.textPositions = [
        {
          style: el.style,
          id: el.id,
          text: el.innerText,
        },
      ];
      this.currentTextPositions = [...this.module.parameter.textPositions];
    }
  }

  saveTextDeletion(el: HTMLElement): void {
    if (
      this.module &&
      this.module.parameter.textPositions &&
      this.module.parameter.textPositions.length > 0
    ) {
      const index = this.module.parameter.textPositions.findIndex(
        (item) => item.id === el.id
      );
      if (index > -1) {
        this.module.parameter.textPositions.splice(index, 1);
      }
      this.currentTextPositions = [...this.module.parameter.textPositions];
    }
  }

  saveCanvas(): void {
    const c = document.getElementById('drawing-canvas') as HTMLCanvasElement;
    if (c) {
      const dataUrl = c.toDataURL();
      if (this.module) {
        this.module.parameter.canvas = {
          canvasImage: dataUrl,
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

  putTextUndoStep(textPositions: object): void {
    if (this.undoStates.length >= this.undoSteps) {
      this.undoStates.shift();
    }
    this.undoStates.push(textPositions);
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

  putTextRedoStep(textPositions: object): void {
    if (this.redoStates.length >= this.undoSteps) {
      this.redoStates.shift();
    }
    this.redoStates.push(textPositions);
  }

  undo(): void {
    if (this.undoStates.length > 0) {
      const undoState = this.undoStates.pop();
      if (typeof undoState === 'string') {
        this.setCanvas(undoState);
        this.putCanvasRedoStep(this.currentCanvasState);
      } else if (undoState[0].style) {
        this.putTextRedoStep(this.currentTextPositions);
        this.setText(undoState);
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
      } else if (redoState[0].style) {
        this.putTextUndoStep(this.currentTextPositions);
        this.setText(redoState);
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

  setCanvas(dataUrl: string): void {
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

  setText(textPositions: any): void {
    const elements = document.getElementsByClassName('textElement');

    Array.from(elements as HTMLCollectionOf<HTMLElement>).forEach((el) => {
      const data = textPositions.find((data) => data.id === el.id);

      if (data) {
        el.style.left = data.style.left;
        el.style.top = data.style.top;
        this.saveTextPosition(el.cloneNode(true) as HTMLElement);
      } else {
        this.saveTextDeletion(el);
        el.remove();
      }
      if (this.module) {
        moduleService.putModule(this.module);
      }
    });

    /*textPositions.forEach((data) => {
      const pElement = document.getElementById(data.id);
      if (pElement) {
        pElement.style.left = data.style.left;
        pElement.style.top = data.style.top;
        this.saveTextPosition(pElement.cloneNode(true) as HTMLElement);
      }
    });*/
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
  padding: 0;
  border: 1px solid var(--el-border-color-light);
  border-radius: var(--border-radius-xs);
  transition: all 0.15s ease;
  .idea-container {
    margin: 0;
  }
  .sizeButton-container {
    position: absolute;
    top: -1.5rem;
    display: flex;
    justify-content: flex-start;
    align-items: flex-start;
    padding: 0;
    transition: opacity 0.2s ease;
    width: 7rem;
    .sizeButton {
      position: relative;
      background-color: transparent;
      padding: 0.3rem !important;
      margin: 0;
      width: 1.5rem;
      height: 1.5rem;
      min-height: 1rem !important;
      font-size: var(--font-size-xlarge);
    }
  }
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
  z-index: 10000;
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

.eraserToggle,
.textInputToggle {
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
