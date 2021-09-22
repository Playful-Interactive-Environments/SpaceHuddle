<template>
  <el-dialog v-model="showDialog" :before-close="handleClose">
    <template #title>
      <span class="el-dialog__title">{{
        $t('module.categorisation.default.create.header')
      }}</span>
      <br />
      <br />
      <p>
        {{ $t('module.categorisation.default.create.info') }}
      </p>
    </template>

    <form class="category-create__form">
      <label for="title" class="heading heading--xs">{{
        $t('module.categorisation.default.create.title')
      }}</label>
      <input
        id="title"
        v-model="title"
        class="input input--fullwidth"
        :placeholder="$t('module.categorisation.default.create.titleExample')"
        @blur="context.$v.title.$touch()"
      />
      <FormError
        v-if="context.$v.title.$error"
        :errors="context.$v.title.$errors"
        :isSmall="true"
      />
      <el-color-picker v-model="color"></el-color-picker>
    </form>

    <template #footer>
      <button
        type="submit"
        class="btn btn--gradient btn--fullwidth"
        @click.prevent="saveCategory"
      >
        {{ $t('module.categorisation.default.create.submit') }}
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

import * as categorisationService from '@/services/categorisation-service';

import {
  getErrorMessage,
  addError,
  clearErrors,
} from '@/services/exception-service';

@Options({
  components: {
    FormError,
  },
  validations: {
    title: {
      required,
      max: maxLength(255),
    },
  },
  emits: ['categoryCreated', 'update:categoryId', 'update:showModal'],
})
export default class ModalCategoryCreate extends Vue {
  @Prop({ default: false }) showModal!: boolean;
  @Prop({ required: true }) taskId!: string;
  @Prop({ required: false }) categoryId!: string | null;

  defaultColor = '#1d2948';
  title = '';
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
    this.color = this.defaultColor;
    this.$emit('update:categoryId', null);
  }

  async saveCategory(): Promise<void> {
    clearErrors(this.errors);
    await this.context.$v.$validate();
    if (this.context.$v.$error) return;

    if (!this.categoryId) {
      categorisationService
        .postCategory(this.taskId, {
          keywords: this.title,
          parameter: { color: this.color },
        })
        .then(
          () => {
            this.$emit('update:showModal', false);
            this.$emit('categoryCreated');
            this.resetForm();
            this.context.$v.$reset();
          },
          (error) => {
            addError(this.errors, getErrorMessage(error));
          }
        );
    } else {
      categorisationService
        .putCategory(this.categoryId, {
          keywords: this.title,
          parameter: { color: this.color },
        })
        .then(
          () => {
            this.$emit('update:showModal', false);
            this.$emit('update:categoryId', null);
            this.$emit('categoryCreated');
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
.category-create {
  display: flex;
  flex-direction: column;
  padding: 0 1rem;
  line-height: 1.2;

  &__form {
    display: flex;
    flex-direction: column;

    input,
    textarea {
      margin: 0 0 0.2rem;
    }
  }

  &__topic {
    margin-top: 1rem;
  }
}
</style>
