<template>
  <el-image
    v-if="idea?.image"
    :src="idea?.image"
    :class="cssClass"
    alt=""
    :preview-src-list="[idea?.image]"
    :hide-on-click-modal="true"
    :fit="fit"
  />
  <figure class="media video" v-else-if="isLinkVideo(idea?.link)">
    <iframe
      :src="convertToEmbed(idea?.link)"
      height="100%"
      width="100%"
      allow="fullscreen"
    ></iframe>
  </figure>
  <el-image
    v-else-if="idea?.link && !idea?.image"
    :src="idea?.link"
    :class="cssClass"
    alt=""
    :preview-src-list="allowImagePreview ? [idea?.link] : []"
    :hide-on-click-modal="true"
    :fit="fit"
  />
</template>

<script lang="ts">
/* eslint-disable @typescript-eslint/no-explicit-any*/
import { Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import * as imageUtil from '@/utils/image';

export default class IdeaMediaViewer extends Vue {
  @Prop() idea!: Idea;
  @Prop({ default: true }) allowImagePreview!: boolean;
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
