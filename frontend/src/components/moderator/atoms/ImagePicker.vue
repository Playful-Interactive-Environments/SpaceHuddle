<template>
  <div class="stack">
    <ImageUploader
      v-model:show-modal="showUploadDialog"
      v-model="base64ImageUrl"
      v-on:imageChanged="imageChanged"
    />
    <LinkSettings
      v-model:show-modal="showLinkInput"
      v-model:link="editLink"
      v-on:imageChanged="imageChanged"
    />
    <DrawingUpload
      v-model:show-modal="showDrawingInput"
      v-model="base64ImageUrl"
      v-on:imageChanged="imageChanged"
    />
    <div class="edit stack__container stack__container__image">
      <div class="stack__content">
        <div v-if="!link && !image" class="empty">
          <font-awesome-icon icon="plus" />
          <span>{{ $t('moderator.atom.imagePicker.add') }}</span>
        </div>
        <figure class="media video" v-if="isLinkVideo(link)">
          <iframe
            :src="convertToEmbed(link)"
            height="100%"
            width="100%"
          ></iframe>
        </figure>
        <el-image
          fit="contain"
          :src="link"
          alt=""
          v-if="link && !image && !isLinkVideo(link)"
        />
        <el-image fit="contain" :src="image" alt="" v-if="image" />
      </div>
      <div
        v-if="useEditOverlay"
        class="stack__action"
        :class="{ gridOverlay: image || link }"
        v-on:transitionend="toggleActions"
        v-on:webkitTransitionEnd="toggleActions"
      >
        <span @click="editLinkInput" :class="{ gridItem: image || link }">
          <font-awesome-icon icon="link" />
        </span>
        <span @click="uploadImage" :class="{ gridItem: image || link }">
          <font-awesome-icon icon="upload" />
        </span>
        <span @click="uploadDrawing" :class="{ gridItem: image || link }">
          <font-awesome-icon icon="pencil" />
        </span>
        <span @click="deleteImage" v-if="image || link" class="gridItem">
          <font-awesome-icon icon="trash" />
        </span>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import LinkSettings from '@/components/moderator/organisms/settings/LinkSettings.vue';
import ImageUploader from '@/components/shared/organisms/ImageUploader.vue';
import DrawingUpload from '@/components/shared/organisms/DrawingUpload.vue';

@Options({
  components: {
    DrawingUpload,
    ImageUploader,
    LinkSettings,
  },
  emits: ['update:image', 'update:link', 'change'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ImagePicker extends Vue {
  @Prop({ default: null }) image!: string | null;
  @Prop({ default: null }) link!: string | null;
  @Prop({ default: true }) useEditOverlay!: boolean;

  editLink: string | null = null;
  showUploadDialog = false;
  showLinkInput = false;
  showDrawingInput = false;
  actionsActive = false;
  base64ImageUrl: string | null = null;

  toggleActions(event: TransitionEvent): void {
    if (event.propertyName == 'opacity') {
      const propertyValue = window.getComputedStyle(event.target as any)[
        event.propertyName
      ];
      this.actionsActive = propertyValue == '1';
    }
  }

  @Watch('link', { immediate: true, deep: true })
  async onLinkChanged(link: string | null): Promise<void> {
    this.editLink = link;
  }

  convertToEmbed(link: string | null) {
    if (link) {
      if (link.includes('youtube')) {
        link = link.replace('watch?v=', 'embed/');
      } else if (link.includes('vimeo')) {
        const vid = link.split('/');
        const vidNr = vid[vid.length - 1];
        link = 'https://player.vimeo.com/video/' + vidNr;
      }
    }
    return link;
  }

  isLinkVideo(link: string | null): boolean {
    if (link) {
      if (link.includes('youtube') || link.includes('vimeo')) {
        return true;
      }
    }
    return false;
  }

  @Watch('editLink', { immediate: true, deep: true })
  async onEditLinkChanged(link: string | null): Promise<void> {
    this.$emit('update:link', link);
    if (link) {
      this.$emit('update:image', null);
      this.base64ImageUrl = null;
    }
  }

  @Watch('image', { immediate: true, deep: true })
  async onImageChanged(image: string | null): Promise<void> {
    if (image) {
      this.editLink = null;
    }
    this.base64ImageUrl = image;
  }

  uploadImage(): void {
    if (this.actionsActive) {
      this.showUploadDialog = true;
    }
  }

  uploadDrawing(): void {
    if (this.actionsActive) {
      this.showDrawingInput = true;
    }
  }

  editLinkInput(): void {
    if (this.actionsActive) {
      this.showLinkInput = true;
    }
  }

  deleteImage(): void {
    if (this.actionsActive) {
      this.$emit('update:link', null);
      this.$emit('update:image', null);
      this.editLink = null;
      this.base64ImageUrl = null;
      this.$emit('change', null);
    }
  }

  imageUploadSuccess(imgDataUrl: string): void {
    this.$emit('update:link', null);
    this.$emit('update:image', imgDataUrl);
    this.base64ImageUrl = imgDataUrl;
    this.editLink = null;
    (this.$refs.upload as any).setStep(1);
  }

  imageChanged(): void {
    setTimeout(() => {
      this.$emit('change', null);
    }, 100);
  }

  @Watch('base64ImageUrl', { immediate: true })
  onBase64ImageUrlChanged(): void {
    if (this.base64ImageUrl) {
      this.$emit('update:link', null);
      this.$emit('update:image', this.base64ImageUrl);
      this.editLink = null;
    }
  }
}
</script>

<style lang="scss" scoped>
.el-image {
  width: 100%;
  height: 100%;
}

.empty {
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  color: var(--color-dark-contrast-light);
}

.video {
  width: 100%;
  height: 100%;
  iframe {
    object-fit: contain;
  }
}

.edit {
  cursor: pointer;
}

.gridOverlay {
  display: grid;
  grid-gap: 1rem;
  grid-template-columns: 1fr 1fr;
  grid-template-rows: 1fr 1fr;
}

.gridItem {
  display: flex;
  justify-content: center;
}
</style>
