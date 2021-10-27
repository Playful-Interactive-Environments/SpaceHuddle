<template>
  <el-carousel height="60vh" :interval="15000">
    <el-carousel-item v-for="(idea, index) in ideas" :key="index">
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

@Options({
  components: {
    IdeaCard,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  ideas: Idea[] = [];
  readonly intervalTime = 10000;
  interval!: any;

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getIdeas();
  }

  async getIdeas(): Promise<void> {
    if (this.taskId) {
      await ideaService
        .getIdeasForTask(this.taskId, IdeaSortOrder.ALPHABETICAL)
        .then((ideas) => {
          this.ideas = ideas;
        });
    }
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
.public-idea {
  max-width: 20rem;
}

.gallery-item {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
}
</style>
