<template>
  <IdeaCard
    :idea="idea"
    v-for="(idea, index) in ideas"
    :key="index"
    @ideaDeleted="getIdeas"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import * as ideaService from '@/services/idea-service';
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';

@Options({
  components: {
    IdeaCard,
  },
})
export default class ModeratorContentComponent extends Vue {
  @Prop() readonly taskId!: string;
  ideas: Idea[] = [];
  readonly interval = 3000;
  ideaInterval!: number;

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(val: string): void {
    this.getIdeas();
  }

  async getIdeas(): Promise<void> {
    if (this.taskId) {
      await ideaService.getIdeasForTask(this.taskId).then((ideas) => {
        this.ideas = ideas;
      });
    }
  }

  async mounted(): Promise<void> {
    this.startIdeaInterval();
  }

  startIdeaInterval(): void {
    this.ideaInterval = setInterval(this.getIdeas, this.interval);
  }

  unmounted(): void {
    clearInterval(this.ideaInterval);
  }
}
</script>

<style scoped></style>
