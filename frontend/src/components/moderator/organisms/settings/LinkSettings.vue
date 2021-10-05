<template>
  <el-dialog
    v-model="showSettings"
    :title="$t('moderator.organism.settings.ideaSettings.link')"
    :before-close="handleClose"
  >
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
        <font-awesome-icon icon="check-circle" v-on:click="save" />
      </span>
    </span>
    <FormError
      v-if="context.$v.editLink.$error"
      :errors="context.$v.editLink.$errors"
      :isSmall="true"
    />
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
  emits: ['update:showModal', 'update:link'],
  validations: {
    editLink: {
      url,
    },
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class LinkSettings extends Vue {
  @Prop({ default: false }) showModal!: boolean;
  @Prop({ default: null }) link!: string;

  showSettings = false;
  editLink: string | null = '';

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
    this.editLink = this.link;
  }

  @Watch('showModal', { immediate: true })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showSettings = showModal;
    this.reset();
  }

  @Watch('link', { immediate: true, deep: true })
  async onLinkChanged(link: string): Promise<void> {
    if (!this.showSettings) {
      this.editLink = link;
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
    this.$emit('update:link', this.editLink);
    this.reset();
    this.showSettings = false;
    this.$emit('update:showModal', false);
  }
}
</script>

<style scoped></style>
