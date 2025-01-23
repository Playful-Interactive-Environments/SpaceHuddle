<template>
  <el-tooltip
    :show-after="showAfter"
    :placement="placement"
    :content="text"
    :effect="effect"
    :enterable="false"
    :disabled="disabled"
    :trigger="trigger"
  >
    <template #content><p v-if="text.length > 0">{{text}}</p><slot name="content"></slot></template>
    <slot />
  </el-tooltip>
</template>

<script lang="ts">
import { Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TutorialStep extends Vue {
  @Prop({ default: 1200 }) showAfter!: number;
  @Prop({ default: 'top' }) placement!: string;
  @Prop({ default: '' }) text!: string;
  @Prop({ default: 'dark' }) effect!: string;
  @Prop({ default: 'hover' }) trigger!: string;
  @Prop({ default: false }) disabled!: boolean;
}
</script>

<style lang="scss" scoped></style>
