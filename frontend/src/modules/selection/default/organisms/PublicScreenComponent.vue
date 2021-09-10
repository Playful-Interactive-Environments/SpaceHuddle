<template>
  <section
    v-if="ideas.length === 0"
    class="container2 container2--centered public-screen__error"
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
import * as selectService from '@/services/selection-service';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';

@Options({
  components: {
    IdeaCard,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreenComponent extends Vue {
  @Prop() readonly taskId!: string;
  task: Task | null = null;
  ideas: Idea[] = [];
  readonly intervalTime = 3000;
  interval!: any;

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getIdeas();
  }

  async getTask(): Promise<void> {
    if (this.taskId) {
      await taskService.getTaskById(this.taskId).then((task) => {
        this.task = task;
      });
    }
  }

  async getIdeas(): Promise<void> {
    if (this.taskId) {
      if (!this.task) await this.getTask();
      await selectService
        .getIdeasForSelection(this.task?.parameter.selectionId)
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
.public-screen {
  &__content {
    margin-top: 1em;
    column-width: 20vw;
    column-gap: 1rem;
  }
}
</style>
