<template>
  <el-dialog
    v-model="showSettings"
    width="calc(var(--app-width) * 0.8)"
    :before-close="handleClose"
  >
    <template #title>
      <span class="el-dialog__title">{{
        $t('shared.organism.imageUploader.header')
      }}</span>
    </template>
    <el-upload
      v-if="!uploadData"
      action="#"
      drag
      :before-upload="beforeUpload"
      :http-request="uploadFile"
      accept=".png,.jpg"
      class="el-upload"
    >
      <font-awesome-icon icon="upload" />
      <div class="el-upload__text">
        {{ $t('shared.organism.imageUploader.uploadText') }}
      </div>
      <template #tip>
        <div class="el-upload__tip">
          {{ $t('shared.organism.imageUploader.uploadTip') }}
        </div>
      </template>
    </el-upload>
    <div v-else :no-border="true" class="image-upload">
      <div class="image-upload__cropper-wrapper">
        <cropper
          ref="cropper"
          :src="uploadData.url"
          class="image-upload__cropper"
          check-orientation
          :canvas="{
            minWidth: 0,
            maxWidth: 512,
          }"
          :auto-zoom="true"
          :default-size="defaultSize"
        />
        <div
          class="image-upload__reset-button"
          title="Reset Image"
          @click="reset()"
        >
          <font-awesome-icon icon="rotate-right" />
        </div>
        <div class="image-upload__file-type" v-if="uploadData.type">
          {{ uploadData.type }}
        </div>
      </div>
    </div>
    <template #footer>
      <el-button class="deactivate" v-on:click="saveChanges" v-if="uploadData">
        {{ $t('shared.organism.imageUploader.save') }}
      </el-button>
    </template>
  </el-dialog>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { ElMessage } from 'element-plus';
import { getBase64ImageType, isValidFileType, UploadData } from '@/utils/file';
import { ElFile } from 'element-plus/es/components/upload/src/upload.type';
import { Cropper } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';
import 'vue-advanced-cropper/dist/theme.classic.css';

@Options({
  components: { Cropper },
  emits: ['update:showModal', 'imageChanged'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ImageUploader extends Vue {
  @Prop({ default: null }) modelValue!: string | null;
  @Prop({ default: false }) showModal!: boolean;

  showSettings = false;
  uploadData: UploadData | null = null;

  @Watch('showModal', { immediate: true })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    //this.uploadData = null;
    this.showSettings = showModal;
  }

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (!this.modelValue) {
      this.uploadData = null;
    } else {
      this.uploadData = {
        url: this.modelValue,
        name: 'upload',
        type: getBase64ImageType(this.modelValue),
      };
    }
  }

  mounted(): void {
    this.reset();
  }

  handleClose(done: { (): void }): void {
    this.reset();
    done();
    this.$emit('update:showModal', false);
  }

  reset(): void {
    this.uploadData = null;
  }

  saveChanges(): void {
    if (this.uploadData) {
      const { canvas } = (this.$refs.cropper as any)?.getResult();
      const base64 = canvas?.toDataURL(this.uploadData.type);
      this.$emit('update:modelValue', base64);
      this.$emit('update:showModal', false);
      this.$emit('imageChanged', null);
      this.reset();
    }
  }

  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  defaultSize({ imageSize, visibleArea }) {
    return {
      width: (visibleArea || imageSize).width,
      height: (visibleArea || imageSize).height,
    };
  }

  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  uploadFile(res: any): boolean {
    const url = URL.createObjectURL(res.file);
    this.uploadData = {
      name: res.file.name,
      url: url,
      type: res.file.type,
    };
    return true;
  }

  beforeUpload(file: ElFile): boolean {
    if (isValidFileType(file.name)) {
      return true;
    }
    ElMessage.error(
      (this as any).$t('shared.organism.imageUploader.wrongType')
    );
    return false;
  }
}
</script>

<style lang="scss" scoped>
.el-upload::v-deep {
  --image-size: 122px;
  text-align: left;
  width: 100%;

  .el-upload {
    width: 100%;
  }

  .el-upload-dragger {
    width: unset;
    height: unset;
    padding: 2rem;
  }
}

.vue-advanced-cropper::v-deep {
  .vue-advanced-cropper__background,
  .vue-advanced-cropper__foreground {
    background: white;
  }

  .vue-simple-handler {
    background: var(--color-primary);
  }

  .vue-simple-line {
    border-color: var(--color-primary);
    border-style: dashed;
    border-width: 0.5;
  }
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
  &__reset-button {
    position: absolute;
    right: 20px;
    bottom: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 42px;
    width: 42px;
    color: white;
    background: var(--color-primary);
    transition: background 0.5s;
    &:hover {
      background: var(--color-primary);
    }
  }
  &__buttons-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 17px;
  }
  &__button {
    border: none;
    outline: solid transparent;
    color: white;
    font-size: 16px;
    padding: 10px 20px;
    background: var(--color-primary);
    cursor: pointer;
    transition: background 0.5s;
    margin: 0 16px;
    border-radius: 5px;
    &:hover,
    &:focus {
      background: var(--color-primary);
    }
    input {
      display: none;
    }
  }
  &__file-type {
    position: absolute;
    top: 20px;
    left: 20px;
    background: var(--color-primary);
    border-radius: 5px;
    padding: 0px 10px;
    padding-bottom: 2px;
    font-size: 12px;
    color: white;
  }
}
</style>
