<template>
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
  <div class="el-upload-list--picture-card">
    <div class="el-upload-list__item">
      <img
        class="el-upload-list__item-thumbnail"
        :src="image"
        alt=""
        v-if="image"
      />
      <img
        class="el-upload-list__item-thumbnail"
        :src="link"
        alt=""
        v-if="link && !image"
      />
      <span class="el-upload-list__item-actions">
        <span class="el-upload-list__item-delete" @click="editLinkInput">
          <font-awesome-icon icon="share-alt" />
        </span>
        <span
          class="el-upload-list__item-delete"
          @click="showUploadDialog = true"
        >
          <font-awesome-icon icon="upload" />
        </span>
        <span
          class="el-upload-list__item-delete"
          @click="deleteImage"
          v-if="image || link"
        >
          <font-awesome-icon icon="trash" />
        </span>
      </span>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue, setup } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import useVuelidate from '@vuelidate/core';
import { url } from '@vuelidate/validators';

import FormError from '@/components/shared/atoms/FormError.vue';
import LinkSettings from '@/components/moderator/organisms/settings/LinkSettings.vue';
import myUpload from 'vue-image-crop-upload/upload-3.vue';

@Options({
  components: {
    FormError,
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

  @Watch('link', { immediate: true, deep: true })
  async onLinkChanged(link: string | null): Promise<void> {
    if (link) {
      this.editLink = link;
    }
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

  editLinkInput(): void {
    this.showLinkInput = true;
  }

  deleteImage(): void {
    this.$emit('update:link', null);
    this.$emit('update:image', null);
  }

  imageUploadSuccess(imgDataUrl: string): void {
    this.$emit('update:link', null);
    this.$emit('update:image', imgDataUrl);
    this.editLink = null;
    (this.$refs.upload as any).setStep(1);
  }
}
</script>

<style scoped></style>
