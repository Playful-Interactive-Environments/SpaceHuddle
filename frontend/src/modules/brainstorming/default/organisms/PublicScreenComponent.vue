<template>
  <section
    v-if="ideas.length === 0"
    class="container container--centered public-screen__error"
  >
    <p>{{ $t('module.brainstorming.default.publicScreen.noIdeas') }}</p>
  </section>
  <section v-else class="public-screen__content">
    <IdeaCard
      v-for="(idea, index) in ideas"
      :idea="idea"
      :key="index"
      :is-deletable="false"
    />
  </section>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';
import * as ideaService from '@/services/idea-service';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';

@Options({
  components: {
    IdeaCard,
  },
})
export default class PublicScreenComponent extends Vue {
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
