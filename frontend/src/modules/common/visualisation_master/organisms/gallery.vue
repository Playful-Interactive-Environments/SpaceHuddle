<template>
  <div ref="gallery" class="gallery">
    <el-carousel
      v-if="ideas.length > 0"
      :height="`${this.contentHeight}px`"
      type="card"
      arrow="always"
      :initial-index="0"
      :interval="paused ? 0 : 7000 / timeModifier"
      v-on:change="galleryIndexChanged"
      trigger="click"
    >
      <el-carousel-item
        v-for="(idea, index) in ideas"
        :key="index"
        :name="index.toString()"
      >
        <div class="gallery-item">
          <IdeaCard
            :idea="idea"
            :is-editable="false"
            class="public-idea"
            :portrait="portrait"
          />
        </div>
      </el-carousel-item>
    </el-carousel>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import { Prop } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

@Options({
  components: {
    IdeaCard,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: 0 }) readonly timeModifier!: number;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  @Prop({ default: [] }) readonly ideas!: Idea[];
  @Prop({ default: false }) readonly paused!: boolean;
  galleryIndex = 0;

  contentHeight = 100;

  get portrait(): boolean {
    return this.contentHeight > 200;
  }

  mounted(): void {
    const gallery = this.$refs.gallery as HTMLElement;
    if (gallery && gallery) {
      this.contentHeight = gallery.clientHeight;
    }
  }

  galleryIndexChanged(newIndex: number): void {
    this.galleryIndex = newIndex;
  }
}
</script>

<style lang="scss" scoped>
.gallery {
  height: 100%;
  width: 100%;
}

.el-carousel::v-deep(.el-carousel__item) {
  display: flex;
  justify-content: center;
  align-items: center;
}

.public-idea {
  max-width: 20rem;
}

.public-idea.landscape {
  max-width: unset;
}

.gallery-item {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
  //width: 20rem;
  max-height: 500px;
}

.el-carousel::v-deep(.el-carousel__mask) {
  background-color: unset;
}

.el-card {
  width: 100%;
  height: 100%;
}

.el-card::v-deep(.el-card__body) {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.el-card.landscape::v-deep(.el-card__body) {
  flex-direction: row;
}

.el-card::v-deep(.card__text) {
  flex-basis: auto;
  flex-grow: 1;
  flex-shrink: 1;
  text-align: inherit;

  display: flex;
  flex-direction: column;
  //justify-content: center;
  align-items: center;
  gap: 0.5rem;
  overflow-y: auto;
}
</style>
