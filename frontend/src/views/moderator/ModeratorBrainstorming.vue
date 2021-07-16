<template>
  <ModuleDetailView :sessionId="sessionId" :taskId="taskId">
    <IdeaCard
      :idea="idea"
      v-for="(idea, index) in ideas"
      :key="index"
      @ideaDeleted="getIdeas"
    />
  </ModuleDetailView>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { Idea } from '@/services/idea-service';
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';
import * as ideaService from '@/services/idea-service';
import ModuleDetailView from '@/components/moderator/organisms/ModuleDetailView.vue';

@Options({
  components: {
    IdeaCard,
    ModuleDetailView,
  },
})
export default class ModeratorBrainstorming extends Vue {
  @Prop({ default: '' }) readonly sessionId!: string;
  @Prop({ default: '' }) readonly taskId!: string;

  ideas: Idea[] = [];
  ideaInterval!: number;
  readonly interval = 3000;
  errors: string[] = [];

  async mounted(): Promise<void> {
    await this.getIdeas();
    this.startIdeaInterval();
  }

  destroyed(): void {
    clearInterval(this.ideaInterval);
  }

  async getIdeas(): Promise<void> {
    ideaService.getIdeasForTask(this.taskId).then((ideas) => {
      this.ideas = ideas;
    });
  }

  startIdeaInterval(): void {
    this.ideaInterval = setInterval(this.getIdeas, this.interval);
  }
}
</script>

<style lang="scss" scoped>
</style>
