<template>
  <el-carousel
    v-if="ideas.length > 0"
    height="60vh"
    type="card"
    :initial-index="0"
    :interval="7000"
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

@Options({
  components: {
    IdeaCard,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  ideas: Idea[] = [];
  galleryIndex = 0;
  readonly intervalTime = 10000;
  interval!: any;

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getIdeas();
  }

  async getIdeas(): Promise<void> {
    if (this.taskId) {
      await ideaService
        .getIdeasForTask(
          this.taskId,
          IdeaSortOrder.TIMESTAMP,
          null,
          this.authHeaderTyp
        )
        .then((ideas) => {
          ideas = ideas.reverse();
          if (this.ideas.length == 0) {
            this.ideas = ideas;
          } else {
            const newIdees: Idea[] = ideas.filter(
              (idea) => !this.ideas.some((old) => old.id == idea.id)
            );
            this.ideas.splice(this.galleryIndex + 2, 0, ...newIdees);
            let deleteIndex = 0;
            while (deleteIndex > -1) {
              deleteIndex = this.ideas.findIndex(
                (old) => !ideas.some((idea) => idea.id == old.id)
              );
              if (deleteIndex > -1) {
                this.ideas.splice(deleteIndex, 1);
              }
            }
          }
        });
    }
  }

  galleryIndexChanged(newIndex: number): void {
    this.galleryIndex = newIndex;
  }

  async mounted(): Promise<void> {
    this.startIdeaInterval();
  }

  startIdeaInterval(): void {
    this.interval = setInterval(this.getIdeas, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }
}
</script>

<style lang="scss" scoped>
.el-carousel::v-deep {
  .el-carousel {
    &__item {
      display: flex;
      justify-content: center;
      align-items: center;
    }
  }
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

.el-carousel::v-deep {
  .el-carousel__mask {
    background-color: unset;
  }
}

.el-card {
  width: 100%;
  height: 100%;
}

.el-card::v-deep {
  .el-card__body {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }

  .card__text {
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
}
</style>
