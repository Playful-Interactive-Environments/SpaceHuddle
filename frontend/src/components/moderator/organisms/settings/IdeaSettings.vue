<template>
  <el-dialog v-model="showSettings" width="80vw" :before-close="handleClose">
    <template #title>
      <span class="el-dialog__title">{{
        $t('moderator.organism.settings.ideaSettings.header')
      }}</span>
    </template>
    <label for="keywords" class="heading heading--xs">{{
      $t('moderator.organism.settings.ideaSettings.keywords')
    }}</label>
    <input
      id="keywords"
      v-model="keywords"
      class="input input--fullwidth"
      :placeholder="
        $t('moderator.organism.settings.ideaSettings.keywordsExample')
      "
      @blur="context.$v.keywords.$touch()"
    />
    <FormError
      v-if="context.$v.keywords.$error"
      :errors="context.$v.keywords.$errors"
      :isSmall="true"
    />
    <label for="description" class="heading heading--xs">{{
      $t('moderator.organism.settings.ideaSettings.description')
    }}</label>
    <textarea
      id="description"
      class="textarea textarea--fullwidth"
      :placeholder="
        $t('moderator.organism.settings.ideaSettings.descriptionExample')
      "
      ref="ideaTextfield"
      rows="4"
      contenteditable
      v-model="description"
      @blur="context.$v.description.$touch()"
    ></textarea>
    <FormError
      v-if="context.$v.description.$error"
      :errors="context.$v.description.$errors"
      :isSmall="true"
    />
    <label class="heading heading--xs">{{
      $t('moderator.organism.settings.ideaSettings.image')
    }}</label>
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
    ></my-upload>
    <el-dialog v-model="showLinkInput">
      <template #title>
        <span class="el-dialog__title layout__level">
          {{ $t('moderator.organism.settings.ideaSettings.link') }}
        </span>
      </template>
      <span class="layout__level">
        <input
          id="link"
          v-model="editLink"
          class="input input--fullwidth"
          :placeholder="
            $t('moderator.organism.settings.ideaSettings.linkExample')
          "
          @blur="context.$v.editLink.$touch()"
        />
        <span style="font-size: 2rem; margin-left: 1rem">
          <font-awesome-icon
            icon="check-circle"
            v-on:click="imageLinkSuccess"
          />
        </span>
      </span>
      <FormError
        v-if="context.$v.editLink.$error"
        :errors="context.$v.editLink.$errors"
        :isSmall="true"
      />
    </el-dialog>
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
          v-if="link"
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
            @click="image = link = null"
            v-if="image || link"
          >
            <font-awesome-icon icon="trash" />
          </span>
        </span>
      </div>
    </div>
    <template #footer>
      <button
        type="submit"
        class="btn btn--gradient btn--fullwidth"
        @click.prevent="save"
      >
        {{ $t('moderator.organism.settings.ideaSettings.submit') }}
      </button>
    </template>
  </el-dialog>
</template>

<script lang="ts">
import { Options, Vue, setup } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import useVuelidate from '@vuelidate/core';
import { maxLength, required, url } from '@vuelidate/validators';
import * as ideaService from '@/services/idea-service';
import { Idea } from '@/types/api/Idea';

import FormError from '@/components/shared/atoms/FormError.vue';
import myUpload from 'vue-image-crop-upload/upload-3.vue';

@Options({
  components: {
    FormError,
    'my-upload': myUpload,
  },
  emits: ['update:showModal'],
  validations: {
    keywords: {
      max: maxLength(36),
      required,
    },
    description: {
      max: maxLength(255),
    },
    editLink: {
      url,
    },
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class IdeaSettings extends Vue {
  @Prop({ default: false }) showModal!: boolean;
  @Prop() idea!: Idea;

  showSettings = false;
  keywords = '';
  description = '';
  image: string | null = '';
  link: string | null = '';
  editLink: string | null = '';
  showUploadDialog = false;
  showLinkInput = false;

  mounted(): void {
    this.reset();
  }

  handleClose(done: { (): void }): void {
    this.reset();
    this.context.$v.$reset();
    done();
    this.$emit('update:showModal', false);
  }

  reset(): void {
    this.keywords = this.idea.keywords;
    this.description = this.idea.description;
    this.image = this.idea.image;
    this.link = this.idea.link;
    this.editLink = '';
  }

  editLinkInput(): void {
    this.showLinkInput = true;
    this.editLink = this.link;
  }

  @Watch('showModal', { immediate: true })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showSettings = showModal;
    this.reset();
  }

  @Watch('idea', { immediate: true, deep: true })
  async onIdeaChanged(idea: Idea): Promise<void> {
    if (!this.showSettings) {
      this.keywords = idea.keywords;
      this.description = idea.description;
      this.image = idea.image;
      this.link = idea.link;
    }
  }

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

  imageUploadSuccess(imgDataUrl: string): void {
    this.image = imgDataUrl;
    this.link = null;
  }

  async imageLinkSuccess(): Promise<void> {
    await this.context.$v.$reset();
    await this.context.$v.$validate();
    if (this.context.$v.$error) return;

    this.image = null;
    this.link = this.editLink;
    this.showLinkInput = false;
  }

  async save(): Promise<void> {
    await this.context.$v.$reset();
    await this.context.$v.$validate();
    if (this.context.$v.$error) return;

    this.idea.keywords = this.keywords;
    this.idea.description = this.description;
    this.idea.image = this.image;
    this.idea.link = this.link;
    ideaService.putIdea(this.idea.id, this.idea);
    this.reset();
    this.showSettings = false;
    this.$emit('update:showModal', false);
  }
}
</script>

<style scoped></style>
