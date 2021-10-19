<template>
  <div class="stack">
    <my-upload
      id="upload"
      @crop-success="imageUploadSuccess"
      v-model="showUploadDialog"
      :width="512"
      :height="512"
      img-format="png"
      langType="en"
      :no-square="true"
      :no-circle="true"
      :no-rotate="false"
      :with-credentials="true"
      ref="upload"
    ></my-upload>
    <LinkSettings v-model:show-modal="showLinkInput" v-model:link="editLink" />
    <div class="edit stack__container stack__container__image">
      <div class="stack__content">
        <div v-if="!link && !image" class="empty">
          <font-awesome-icon icon="plus" />
          <span>{{ $t('moderator.atom.imagePicker.add') }}</span>
        </div>
        <el-image fit="cover" :src="image" alt="" v-if="image" />
        <el-image fit="cover" :src="link" alt="" v-if="link && !image" />
      </div>
      <span
        class="stack__action"
        v-on:transitionend="toggleActions"
        v-on:webkitTransitionEnd="toggleActions"
      >
        <span @click="editLinkInput">
          <font-awesome-icon icon="share-alt" />
        </span>
        <span @click="uploadImage">
          <font-awesome-icon icon="upload" />
        </span>
        <span @click="deleteImage" v-if="image || link">
          <font-awesome-icon icon="trash" />
        </span>
      </span>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import LinkSettings from '@/components/moderator/organisms/settings/LinkSettings.vue';
import myUpload from 'vue-image-crop-upload/upload-3.vue';

@Options({
  components: {
    LinkSettings,
    'my-upload': myUpload,
  },
  emits: ['update:image', 'update:link'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ImagePicker extends Vue {
  @Prop({ default: null }) image!: string | null;
  @Prop({ default: null }) link!: string | null;

  editLink: string | null = null;
  showUploadDialog = false;
  showLinkInput = false;
  actionsActive = false;

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

  @Watch('editLink', { immediate: true, deep: true })
  async onEditLinkChanged(link: string | null): Promise<void> {
    this.$emit('update:link', link);
    if (link) {
      this.$emit('update:image', null);
    }
  }

  @Watch('image', { immediate: true, deep: true })
  async onImageChanged(image: string | null): Promise<void> {
    if (image) {
      this.editLink = null;
    }
  }

  uploadImage(): void {
    if (this.actionsActive) {
      this.showUploadDialog = true;
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
    }
  }

  imageUploadSuccess(imgDataUrl: string): void {
    this.$emit('update:link', null);
    this.$emit('update:image', imgDataUrl);
    this.editLink = null;
    (this.$refs.upload as any).setStep(1);
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
  color: var(--color-darkblue-light);
}

.edit {
  cursor: pointer;
}
</style>
