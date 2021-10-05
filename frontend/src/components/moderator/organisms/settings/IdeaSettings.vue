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
    <ImagePicker v-model:link="link" v-model:image="image" />
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
import ImagePicker from '@/components/moderator/atoms/ImagePicker.vue';
import myUpload from 'vue-image-crop-upload/upload-3.vue';

@Options({
  components: {
    FormError,
    ImagePicker,
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
