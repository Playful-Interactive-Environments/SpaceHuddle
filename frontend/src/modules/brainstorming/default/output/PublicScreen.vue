<template>
  <section v-if="ideas.length === 0" class="centered public-screen__error">
    <p>{{ $t('module.brainstorming.default.publicScreen.noIdeas') }}</p>
  </section>
  <div v-else class="public-screen__content">
    <section class="layout__columns">
      <IdeaCard
        v-for="(idea, index) in oldIdeas"
        :idea="idea"
        :key="index"
        :is-editable="false"
      />
    </section>
    <section class="layout__columns">
      <IdeaCard
        v-for="(idea, index) in newIdeas"
        :idea="idea"
        :key="index"
        :is-editable="false"
      />
    </section>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as ideaService from '@/services/idea-service';
import * as taskService from '@/services/task-service';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import IdeaStates from '@/types/enum/IdeaStates';

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
  readonly intervalTimeNew = 5000;
  intervalNew!: any;

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getIdeas();
  }

  get newIdeas(): Idea[] {
    return this.ideas
      .filter((idea) => idea.state === IdeaStates.NEW.toUpperCase())
      .slice(0, 3);
  }

  get oldIdeas(): Idea[] {
    return this.ideas.filter(
      (idea) => idea.state !== IdeaStates.NEW.toUpperCase()
    );
  }

  async getIdeas(): Promise<void> {
    if (this.taskId) {
      let orderType = IdeaSortOrder.TIMESTAMP;

      await taskService.getTaskById(this.taskId).then((task) => {
        if (task.parameter && task.parameter.orderType)
          orderType = task.parameter.orderType;
      });

      await ideaService
        .getIdeasForTask(this.taskId, orderType)
        .then((ideas) => {
          if (orderType.toUpperCase() == IdeaSortOrder.TIMESTAMP.toUpperCase())
            this.ideas = ideas.reverse();
          else this.ideas = ideas;
        });
    }
  }

  changeState(): void {
    const ideas = this.newIdeas;
    if (ideas.length > 0) {
      ideas[0].state = IdeaStates.HANDLED.toUpperCase();
      ideaService.putIdea(ideas[0].id, ideas[0]);
    }
  }

  async mounted(): Promise<void> {
    this.startIdeaInterval();
  }

  startIdeaInterval(): void {
    this.interval = setInterval(this.getIdeas, this.intervalTime);
    this.intervalNew = setInterval(this.changeState, this.intervalTimeNew);
  }

  unmounted(): void {
    clearInterval(this.interval);
    clearInterval(this.intervalNew);
  }
}
</script>

<style lang="scss" scoped>
.public-screen__content {
  display: grid;
  grid-template-columns: 80% 20%;
}
</style>
