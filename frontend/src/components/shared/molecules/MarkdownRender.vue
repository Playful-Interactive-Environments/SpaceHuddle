<template>
  <vue-markdown class="markdown" :source="source" :options="{ html: true }" />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import VueMarkdown from 'vue-markdown-render';

@Options({
  components: {
    VueMarkdown,
  },
  emits: ['update:modelValue'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class MarkdownRender extends Vue {
  @Prop({ default: '' }) source!: string;
}
</script>

<style lang="scss" scoped></style>
