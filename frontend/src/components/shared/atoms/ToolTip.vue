<template>
  <el-tooltip
    :show-after="showAfter"
    :placement="placement"
    :content="text"
    :effect="effect"
  >
    <slot />
  </el-tooltip>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TutorialStep extends Vue {
  @Prop({ default: 1200 }) showAfter!: number;
  @Prop({ default: 'top' }) placement!: string;
  @Prop({ default: 'tooltip' }) text!: string;
  @Prop({ default: 'dark' }) effect!: string;
}
</script>

<style lang="scss" scoped></style>
