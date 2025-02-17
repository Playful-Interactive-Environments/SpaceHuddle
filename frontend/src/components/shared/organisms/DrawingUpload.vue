<template>
  <el-dialog
    v-model="showSettings"
    width="calc(var(--app-width) * 0.8)"
    :before-close="handleClose"
    class="drawing-dialog"
  >
    <template #header>
      <span class="el-dialog__title">{{
        $t('shared.organism.drawingUploader.header')
      }}</span>
    </template>
    <vue-drawing-canvas
      v-if="!crop"
      class="drawing-canvas"
      ref="VueCanvasDrawing"
      v-model:image="uploadData"
      :width="canvasWidth"
      :height="canvasHeight"
      :color="color"
      :lineWidth="lineWidth"
      :eraser="eraser"
      :background-image="base64BackgroundImageUrl"
      background-color="#ffffff"
    />
    <div v-else :no-border="true" class="image-upload">
      <div class="image-upload__cropper-wrapper">
        <cropper
          ref="cropper"
          :src="uploadData"
          class="image-upload__cropper"
          check-orientation
          :canvas="{
            minWidth: 0,
            maxWidth: 512,
          }"
          :auto-zoom="true"
          :default-size="defaultSize"
        />
      </div>
    </div>
    <template #footer>
      <div class="level">
        <div class="level-left" v-if="!crop">
          <el-button circle type="primary" v-on:click="reset">
            <font-awesome-icon icon="trash" />
          </el-button>
          <el-button circle type="primary" v-on:click="undo">
            <font-awesome-icon icon="rotate-left" />
          </el-button>
          <el-button circle type="primary" v-on:click="redo">
            <font-awesome-icon icon="rotate-right" />
          </el-button>
          <el-button
            circle
            type="primary"
            v-on:click="setEraser"
            v-if="!eraser"
          >
            <font-awesome-icon icon="eraser" />
          </el-button>
          <el-button circle type="primary" v-on:click="setPen" v-if="eraser">
            <font-awesome-icon icon="pen" />
          </el-button>
          <el-color-picker v-model="color" />
          <el-button
            circle
            type="primary"
            v-on:click="showBackgroundUploadDialog = true"
          >
            <font-awesome-icon icon="image" />
          </el-button>
          <el-button
            v-if="base64BackgroundImageUrl"
            circle
            type="primary"
            v-on:click="deleteBackgroundImage"
          >
            <font-awesome-icon :icon="['fac', 'no_image']" />
          </el-button>
        </div>
        <div class="level-left" v-else></div>
        <div class="level-right">
          <el-button
            class="deactivate"
            v-on:click="saveChanges"
            v-if="uploadData"
          >
            {{ $t('shared.organism.drawingUploader.save') }}
          </el-button>
        </div>
      </div>
    </template>
  </el-dialog>
  <ImageUploader
    v-model:show-modal="showBackgroundUploadDialog"
    v-model="base64BackgroundImageUrl"
    v-on:imageChanged="backgroundImageChanged"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import 'vue-advanced-cropper/dist/style.css';
import 'vue-advanced-cropper/dist/theme.classic.css';
import VueDrawingCanvas from 'vue-drawing-canvas';
import { Cropper } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';
import 'vue-advanced-cropper/dist/theme.classic.css';
import ImageUploader from '@/components/shared/organisms/ImageUploader.vue';
import * as themeColors from '@/utils/themeColors';

@Options({
  components: { VueDrawingCanvas, Cropper, ImageUploader },
  emits: ['update:modelValue', 'update:showModal', 'imageChanged'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class DrawingUpload extends Vue {
  @Prop({ default: null }) modelValue!: string | null;
  @Prop({ default: false }) showModal!: boolean;

  showSettings = false;
  uploadData: string | null = null;
  color = themeColors.getContrastColor();
  eraser = false;
  lineWidth = 1;
  crop = false;
  showBackgroundUploadDialog = false;
  base64BackgroundImageUrl: string | null = null;
  backgroundAspect = 1;

  get defaultCanvasWidth(): number {
    const screenWidth = window.innerWidth;
    const max = 1024;
    const border = 100;
    if (screenWidth > max + border) return max;
    return screenWidth - border;
  }

  get defaultCanvasHeight(): number {
    const screenHeight = window.innerHeight;
    return (screenHeight / 5) * 2;
  }

  get defaultAspect(): number {
    return this.defaultCanvasWidth / this.defaultCanvasHeight;
  }

  get canvasWidth(): number {
    if (this.base64BackgroundImageUrl) {
      if (this.defaultAspect > this.backgroundAspect)
        return this.canvasHeight * this.backgroundAspect;
    }
    return this.defaultCanvasWidth;
  }

  get canvasHeight(): number {
    if (this.base64BackgroundImageUrl) {
      if (this.defaultAspect < this.backgroundAspect)
        return this.canvasWidth / this.backgroundAspect;
    }
    return this.defaultCanvasHeight;
  }

  @Watch('showModal', { immediate: true })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.base64BackgroundImageUrl = '';
    this.reset();
    this.showSettings = showModal;
  }

  mounted(): void {
    this.base64BackgroundImageUrl = '';
    this.reset();
  }

  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  defaultSize({ imageSize, visibleArea }) {
    return {
      width: (visibleArea || imageSize).width,
      height: (visibleArea || imageSize).height,
    };
  }

  handleClose(done: { (): void }): void {
    this.reset();
    done();
    this.$emit('update:showModal', false);
  }

  reset(): void {
    this.crop = false;
    this.setPen();
    this.color = themeColors.getContrastColor();
    (this.$refs.VueCanvasDrawing as any)?.reset();
    this.uploadData = null;
  }

  setEraser(): void {
    this.lineWidth = 10;
    this.eraser = true;
  }

  setPen(): void {
    this.eraser = false;
    this.lineWidth = 1;
  }

  undo(): void {
    (this.$refs.VueCanvasDrawing as any)?.undo();
  }

  redo(): void {
    (this.$refs.VueCanvasDrawing as any)?.redo();
  }

  saveChanges(): void {
    if (this.uploadData) {
      if (!this.crop) {
        this.crop = true;
      } else {
        const result = (this.$refs.cropper as any)?.getResult();
        const base64 = result.canvas?.toDataURL('png');
        this.$emit('update:modelValue', base64);
        this.$emit('update:showModal', false);
        this.$emit('imageChanged', null);
        this.reset();
      }
    }
  }

  backgroundImageChanged(aspect: number): void {
    this.backgroundAspect = aspect;
    (this.$refs.VueCanvasDrawing as any)?.redraw();
  }

  deleteBackgroundImage(): void {
    this.base64BackgroundImageUrl = null;
    const drawingCanvas = this.$refs.VueCanvasDrawing as any;
    if (drawingCanvas) {
      drawingCanvas.redraw();
    }
  }
}
</script>

<style lang="scss" scoped>
.is-circle {
  width: 40px;
  height: 40px;
  margin-right: 0.5rem;
}

.vue-advanced-cropper::v-deep(.vue-advanced-cropper__background),
.vue-advanced-cropper::v-deep(.vue-advanced-cropper__foreground) {
  background: white;
}

.vue-advanced-cropper::v-deep(.vue-simple-handler) {
  background: var(--color-primary);
}

.vue-advanced-cropper::v-deep(.vue-simple-line) {
  border-color: var(--color-primary);
  border-style: dashed;
  border-width: 0.5px;
}

.image-upload {
  max-width: 75vw;
  overflow: hidden;
  margin-top: 20px;
  margin-bottom: 20px;
  user-select: none;
  &__cropper {
    border: solid 1px #eee;
    min-height: 100px;
    max-height: calc(var(--app-height) / 2 - 3rem);
    width: 100%;
  }
  &__cropper-wrapper {
    position: relative;
  }
  &__buttons-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 17px;
  }
}

.drawing-canvas {
  background-color: white;
}
</style>

<style lang="scss">
.drawing-dialog {
  .el-dialog__body {
    display: flex;
    justify-content: center;
  }
}
</style>
