<template>
  <el-carousel
    ref="carousel"
    v-if="gameHeight"
    :autoplay="false"
    arrow="always"
    :height="`${gameHeight}px`"
    trigger="click"
    :loop="false"
    @change="carouselChanged"
  >
    <el-carousel-item v-for="keyword of moduleInfoEntryDataList" :key="keyword">
      <img
        :style="{ height: `${(gameHeight / 3) * 2}px` }"
        :src="`${imageDirectory}\\${keyword}.${fileExtension}`"
        :alt="keyword"
      />
      <div>
        {{ $t(`${translationPath}.${keyword}`) }}
      </div>
      <div>
        <el-button type="primary" @click="next">
          {{ $t('participant.molecules.moduleInfo.next') }}
        </el-button>
      </div>
    </el-carousel-item>
  </el-carousel>
</template>

<script lang="ts">
import { Vue, Options } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

export interface ModuleInfoEntryData {
  imageUrl: string;
  title: string;
  text: string;
}

@Options({
  components: {},
  emits: ['infoRead'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModuleInfo extends Vue {
  @Prop({ default: [] })
  readonly moduleInfoEntryDataList!: string[];
  @Prop({ default: '' }) readonly translationPath!: string;
  @Prop({ default: '' }) readonly imageDirectory!: string;
  @Prop({ default: 'jpg' }) readonly fileExtension!: string;
  gameHeight = 0;

  mounted(): void {
    this.gameHeight = this.$el.parentElement.offsetHeight;
  }

  activeTabIndex = 0;
  carouselChanged(index: number): void {
    this.activeTabIndex = index;
  }

  next(): void {
    if (this.activeTabIndex + 1 === this.moduleInfoEntryDataList.length) {
      this.$emit('infoRead');
    } else {
      (this.$refs.carousel as any).next();
    }
  }
}
</script>

<style scoped lang="scss">
.el-carousel__item div {
  padding: 2rem;
}

img {
  width: 100%;
  object-fit: contain;
}
</style>
