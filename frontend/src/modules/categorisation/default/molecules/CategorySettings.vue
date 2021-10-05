<template>
  <el-dialog v-model="showDialog" :before-close="handleClose">
    <template #title>
      <span class="el-dialog__title">{{
        $t('module.categorisation.default.settings.header')
      }}</span>
      <br />
      <br />
      <p>
        {{ $t('module.categorisation.default.settings.info') }}
      </p>
    </template>

    <form>
      <label for="title" class="heading heading--xs">{{
        $t('module.categorisation.default.settings.title')
      }}</label>
      <input
        id="title"
        v-model="title"
        class="input input--fullwidth"
        :placeholder="$t('module.categorisation.default.settings.titleExample')"
        @blur="context.$v.title.$touch()"
      />
      <FormError
        v-if="context.$v.title.$error"
        :errors="context.$v.title.$errors"
        :isSmall="true"
      />
      <label for="description" class="heading heading--xs">{{
        $t('module.categorisation.default.settings.description')
      }}</label>
      <textarea
        id="description"
        class="textarea textarea--fullwidth"
        :placeholder="
          $t('module.categorisation.default.settings.descriptionExample')
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
        $t('module.categorisation.default.settings.image')
      }}</label>
      <ImagePicker v-model:link="link" v-model:image="image" />
      <label class="heading heading--xs">{{
        $t('module.categorisation.default.settings.color')
      }}</label>
      <el-color-picker v-model="color"></el-color-picker>
    </form>

    <template #footer>
      <button
        type="submit"
        class="btn btn--gradient btn--fullwidth"
        @click.prevent="saveCategory"
      >
        {{ $t('module.categorisation.default.settings.submit') }}
      </button>
    </template>
  </el-dialog>
</template>

<script lang="ts">
import { Options, setup, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import useVuelidate from '@vuelidate/core';
import { maxLength, required } from '@vuelidate/validators';

import FormError from '@/components/shared/atoms/FormError.vue';
import ImagePicker from '@/components/moderator/atoms/ImagePicker.vue';

import * as categorisationService from '@/services/categorisation-service';

import {
  getErrorMessage,
  addError,
  clearErrors,
} from '@/services/exception-service';

@Options({
  components: {
    FormError,
    ImagePicker,
  },
  validations: {
    title: {
      required,
      max: maxLength(255),
    },
    description: {
      max: maxLength(255),
    },
  },
  emits: ['categoryCreated', 'update:categoryId', 'update:showModal'],
})
export default class CategorySettings extends Vue {
  @Prop({ default: false }) showModal!: boolean;
  @Prop({ required: false }) taskId!: string | null;
  @Prop({ required: false }) categoryId!: string | null;

  defaultColor = '#1d2948';
  title = '';
  description = '';
  image: string | null = null;
  link: string | null = null;
  color = this.defaultColor;
  errors: string[] = [];

  showDialog = false;
  @Watch('showModal', { immediate: false, flush: 'post' })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showDialog = showModal;
  }

  @Watch('categoryId', { immediate: true })
  onCategoryIdChanged(id: string): void {
    if (id) {
      categorisationService.getCategoryById(id).then((category) => {
        this.title = category.keywords;
        this.description = category.description;
        this.image = category.image;
        this.link = category.link;
        if (category.parameter && category.parameter.color)
          this.color = category.parameter.color;
      });
    }
  }

  handleClose(done: { (): void }): void {
    this.resetForm();
    this.context.$v.$reset();
    done();
    this.$emit('update:showModal', false);
  }

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

  resetForm(): void {
    this.title = '';
    this.description = '';
    this.image = null;
    this.link = null;
    this.color = this.defaultColor;
    this.$emit('update:categoryId', null);
  }

  async saveCategory(): Promise<void> {
    clearErrors(this.errors);
    await this.context.$v.$validate();
    if (this.context.$v.$error) return;

    if (!this.categoryId) {
      if (this.taskId) {
        categorisationService
          .postCategory(this.taskId, {
            keywords: this.title,
            description: this.description,
            image: this.image,
            link: this.link,
            parameter: { color: this.color },
          })
          .then(
            (category) => {
              this.$emit('update:showModal', false);
              this.$emit('categoryCreated', category);
              this.resetForm();
              this.context.$v.$reset();
            },
            (error) => {
              addError(this.errors, getErrorMessage(error));
            }
          );
      }
    } else {
      categorisationService
        .putCategory(this.categoryId, {
          keywords: this.title,
          description: this.description,
          image: this.image,
          link: this.link,
          parameter: { color: this.color },
        })
        .then(
          (category) => {
            this.$emit('update:showModal', false);
            this.$emit('update:categoryId', null);
            this.$emit('categoryCreated', category);
            this.resetForm();
            this.context.$v.$reset();
          },
          (error) => {
            addError(this.errors, getErrorMessage(error));
          }
        );
    }
  }
}
</script>

<style lang="scss" scoped>
</style>
