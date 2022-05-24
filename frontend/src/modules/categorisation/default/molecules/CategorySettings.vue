<template>
  <ValidationForm
    :form-data="formData"
    :use-default-submit="false"
    v-on:submitDataValid="save"
    v-on:reset="reset"
  >
    <el-dialog
      v-model="showDialog"
      :before-close="handleClose"
      width="calc(var(--app-width) * 0.8)"
    >
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
      <el-form-item
        :label="$t('module.categorisation.default.settings.title')"
        prop="title"
        :rules="[
          defaultFormRules.ruleRequired,
          defaultFormRules.ruleToLong(36),
        ]"
      >
        <el-input
          v-model="formData.title"
          :placeholder="
            $t('module.categorisation.default.settings.titleExample')
          "
        />
      </el-form-item>
      <el-form-item
        :label="$t('module.categorisation.default.settings.description')"
        prop="description"
        :rules="[defaultFormRules.ruleToLong(255)]"
      >
        <el-input
          type="textarea"
          v-model="formData.description"
          rows="4"
          :placeholder="
            $t('module.categorisation.default.settings.descriptionExample')
          "
        />
      </el-form-item>
      <div class="columns is-mobile">
        <el-form-item
          :label="$t('module.categorisation.default.settings.image')"
          prop="image"
          class="column"
        >
          <ImagePicker
            v-model:link="formData.link"
            v-model:image="formData.image"
          />
        </el-form-item>
        <el-form-item
          :label="$t('module.categorisation.default.settings.color')"
          prop="color"
          :rules="[defaultFormRules.ruleRequired]"
          class="column"
        >
          <el-color-picker v-model="formData.color" />
        </el-form-item>
      </div>
      <template #footer>
        <FromSubmitItem
          :form-state-message="formData.stateMessage"
          submit-label-key="module.categorisation.default.settings.submit"
        />
      </template>
    </el-dialog>
  </ValidationForm>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ImagePicker from '@/components/moderator/atoms/ImagePicker.vue';
import * as categorisationService from '@/services/categorisation-service';
import { getSingleTranslatedErrorMessage } from '@/services/exception-service';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import { ValidationData } from '@/types/ui/ValidationRule';
import ValidationForm, {
  ValidationFormCall,
} from '@/components/shared/molecules/ValidationForm.vue';
import FromSubmitItem from '@/components/shared/molecules/FromSubmitItem.vue';
import { Idea } from '@/types/api/Idea';

@Options({
  components: {
    ValidationForm,
    FromSubmitItem,
    ImagePicker,
  },
  emits: ['categoryCreated', 'update:categoryId', 'update:showModal'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class CategorySettings extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  @Prop({ default: false }) showModal!: boolean;
  @Prop({ required: false }) taskId!: string | null;
  @Prop({ required: false }) categoryId!: string | null;
  @Prop({ default: 0 }) order!: number;
  @Prop({ default: [] }) addIdeas!: Idea[];

  defaultColor = '#1d2948';
  formData: ValidationData = {
    title: '',
    description: '',
    image: null,
    link: null,
    color: this.defaultColor,
    order: this.order,
  };

  mounted(): void {
    //this.reset();
  }

  showDialog = false;
  @Watch('showModal', { immediate: false, flush: 'post' })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showDialog = showModal;
  }

  @Watch('categoryId', { immediate: true })
  onCategoryIdChanged(id: string): void {
    if (id) {
      categorisationService.getCategoryById(id).then((category) => {
        this.formData.title = category.keywords;
        this.formData.description = category.description;
        this.formData.image = category.image;
        this.formData.link = category.link;
        this.formData.order = category.order;
        if (category.parameter && category.parameter.color)
          this.formData.color = category.parameter.color;
      });
    }
  }

  handleClose(done: { (): void }): void {
    //this.reset();
    done();
    this.$emit('update:showModal', false);
  }

  reset(): void {
    this.formData.title = '';
    this.formData.description = '';
    this.formData.image = null;
    this.formData.link = null;
    this.formData.color = this.defaultColor;
    this.formData.order = this.order;
    this.$emit('update:categoryId', null);
    this.formData.call = ValidationFormCall.CLEAR_VALIDATE;
  }

  async save(): Promise<void> {
    if (!this.categoryId) {
      if (this.taskId) {
        categorisationService
          .postCategory(this.taskId, {
            keywords: this.formData.title,
            description: this.formData.description,
            image: this.formData.image,
            link: this.formData.link,
            parameter: { color: this.formData.color },
            order: this.order,
          })
          .then(
            (category) => {
              if (this.addIdeas.length > 0) {
                categorisationService.addIdeasToCategory(
                  category.id,
                  this.addIdeas.map((idea) => idea.id)
                );
              }

              this.$emit('update:showModal', false);
              this.$emit('categoryCreated', category);
              this.reset();
            },
            (error) => {
              this.formData.stateMessage =
                getSingleTranslatedErrorMessage(error);
            }
          );
      }
    } else {
      categorisationService
        .putCategory({
          id: this.categoryId,
          keywords: this.formData.title,
          description: this.formData.description,
          image: this.formData.image,
          link: this.formData.link,
          parameter: { color: this.formData.color },
          order: this.formData.order,
        })
        .then(
          (category) => {
            this.$emit('update:showModal', false);
            this.$emit('update:categoryId', null);
            this.$emit('categoryCreated', category);
            //this.reset();
          },
          (error) => {
            this.formData.stateMessage = getSingleTranslatedErrorMessage(error);
          }
        );
    }
  }
}
</script>

<style lang="scss" scoped></style>
