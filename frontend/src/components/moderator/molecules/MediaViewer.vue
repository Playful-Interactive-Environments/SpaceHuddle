<template>
  <figure class="media video" v-if="isLinkVideo(src)">
    <iframe
      :src="convertToEmbed(src)"
      height="100%"
      width="100%"
      allow="fullscreen"
    ></iframe>
  </figure>
  <el-image
    v-else-if="src"
    :src="src"
    :class="cssClass"
    alt=""
    :preview-src-list="[src]"
    :hide-on-click-modal="true"
    :fit="fit"
  />
</template>

<script lang="ts">
/* eslint-disable @typescript-eslint/no-explicit-any*/
import { Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import * as imageUtil from '@/utils/image';

export default class MediaViewer extends Vue {
  @Prop() src!: string;
  @Prop() cssClass!: string;
  @Prop({ default: '' }) fit!:
    | ''
    | 'fill'
    | 'contain'
    | 'cover'
    | 'none'
    | 'scale-down';

  convertToEmbed = imageUtil.convertToEmbed;
  isLinkVideo = imageUtil.isLinkVideo;
}
</script>

<style scoped lang="scss">
.video {
  width: 100%;
  height: 100%;
  iframe {
    object-fit: contain;
  }
}
</style>
