<template>
  <el-carousel
    v-if="ideas.length > 0"
    height="calc(var(--app-height) * 0.6)"
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
        <IdeaCard :idea="idea" :is-editable="false" class="public-idea" />
      </div>
    </el-carousel-item>
  </el-carousel>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as ideaService from '@/services/idea-service';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';

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

  useIdeas: Idea[] = [];

  updateIdeas(): void {
    if (this.useIdeas.length == 0) {
      this.useIdeas = this.ideas;
    } else {
      const newIdees: Idea[] = this.ideas.filter(
        (idea) => !this.useIdeas.some((old) => old.id == idea.id)
      );
      this.useIdeas.splice(this.galleryIndex + 2, 0, ...newIdees);
      let deleteIndex = 0;
      while (deleteIndex > -1) {
        deleteIndex = this.useIdeas.findIndex(
          (old) => !this.ideas.some((idea) => idea.id == old.id)
        );
        if (deleteIndex > -1) {
          this.useIdeas.splice(deleteIndex, 1);
        }
      }
    }
  }

  galleryIndexChanged(newIndex: number): void {
    this.galleryIndex = newIndex;
  }
}
</script>

<style lang="scss" scoped>
.el-carousel::v-deep(.el-carousel__item) {
  display: flex;
  justify-content: center;
  align-items: center;
}

.public-idea {
  max-width: 20rem;
}

.gallery-item {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
  width: 20rem;
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

.el-card::v-deep(.card__text) {
  flex-basis: auto;
  flex-grow: 1;
  flex-shrink: 1;
  text-align: inherit;

  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
}
</style>
