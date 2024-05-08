<template>
  <MdEditor
    v-model="value"
    language="en-US"
    noUploadImg
    class="markdown"
    inputBoxWitdh="70%"
    :placeholder="placeholder"
    :toolbars="[
      'bold',
      'underline',
      'italic',
      'strikeThrough',
      '-',
      'title',
      'sub',
      'sup',
      'unorderedList',
      'orderedList',
      '-',
      'link',
      'image',
      'table',
      '-',
      'revoke',
      'next',
    ]"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { MdEditor } from 'md-editor-v3';

@Options({
  components: {
    MdEditor,
  },
  emits: ['update:modelValue'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class MarkdownEditor extends Vue {
  @Prop({ default: '' }) modelValue!: string;
  @Prop({ default: '' }) placeholder!: string;

  value = '';

  @Watch('modelValue', { immediate: true })
  onModelValueChanged(): void {
    this.value = this.modelValue;
  }

  @Watch('value', { immediate: true })
  onValueChanged(): void {
    this.$emit('update:modelValue', this.value);
  }
}
</script>

<style lang="scss" scoped>
.md-editor {
  height: 20rem;
}
</style>
