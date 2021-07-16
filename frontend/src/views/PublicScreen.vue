<template>
  <div class="public-screen">
    <Header :white="true" />
    <main
      v-if="!task"
      class="
        container container--fullheight container--centered
        public-screen__error
      "
    >
      <h2>Sorry, nothing here.</h2>
      <p>Make sure that a module is activated for the public view.</p>
    </main>
    <main
      v-else
      class="public-screen__container container container--spaced"
      ref="container"
    >
      <section class="public-screen__overview">
        <div class="public-screen__overview-left">
          <span class="public-screen__overview-type">
            {{ ModuleType[task.taskType] }}
          </span>
          <h2>{{ task.name }}</h2>
          <p>
            {{ task.description }}
          </p>
        </div>
        <div class="public-screen__overview-right">
          <Timer :isActive="task.state === TaskStates.ACTIVE" />
          <img
            :src="
              require(`@/assets/illustrations/planets/${
                ModuleType[task.taskType]
              }.png`)
            "
            alt="planet"
            class="public-screen__overview-planet"
          />
        </div>
      </section>
      <section
        v-if="ideas.length === 0"
        class="container container--centered public-screen__error"
      >
        <p>No ideas yet - stay tuned.</p>
      </section>
      <section v-else class="public-screen__content">
        <IdeaCard
          v-for="(idea, index) in ideas"
          :idea="idea"
          :key="index"
          :is-deletable="false"
        />
      </section>
    </main>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

import Header from '@/components/moderator/organisms/Header.vue';
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';
import Timer from '@/components/shared/atoms/Timer.vue';

import * as sessionService from '@/services/session-service';
import * as ideaService from '@/services/idea-service';
import { Idea } from '@/services/idea-service';
import { Task } from '@/services/task-service';
import ModuleType from '@/types/ModuleType';

import { setModuleStyles } from '@/utils/moduleStyles';
import TaskStates from '@/types/TaskStates';

@Options({
  components: {
    Header,
    IdeaCard,
    Timer,
  },
})
export default class PublicScreen extends Vue {
  @Prop() readonly sessionId!: string;

  task: Task | null = null;
  ideas: Idea[] = [];
  showFallback = false;

  ideaInterval!: number;
  readonly interval = 3000;

  ModuleType = ModuleType;
  TaskStates = TaskStates;

  async mounted(): Promise<void> {
    sessionService.getPublicScreen(this.sessionId).then((queryResult) => {
      this.task = queryResult;
      if (this.task) {
        this.getIdeas().then(() => {
          if (this.task) {
            setModuleStyles(
              this.$refs.container as HTMLElement,
              ModuleType[this.task.taskType]
            );
          }
        });
      } else {
        this.showFallback = true;
      }
      this.startIdeaInterval();
    });
  }

  async getIdeas(): Promise<void> {
    if (this.task) {
      await ideaService.getIdeasForTask(this.task.id).then((ideas) => {
        this.ideas = ideas;
      });
    }
  }

  startIdeaInterval(): void {
    this.ideaInterval = setInterval(this.getIdeas, this.interval);
  }

  destroyed(): void {
    clearInterval(this.ideaInterval);
  }
}
</script>
<style lang="scss" scoped>
.public-screen {
  background: url('../assets/illustrations/stars-background_without_dust.png');
  background-size: contain;
  min-height: 100vh;

  &__container {
    padding-top: 0;
  }

  &__overview {
    display: flex;
    justify-content: space-between;
    color: white;

    &-planet {
      height: 9rem;
      margin-left: 2rem;
      margin-top: -20px;
      margin-right: -20px;
    }

    &-left {
      display: flex;
      justify-content: center;
      flex-direction: column;
      max-width: 65%;
    }

    &-right {
      display: flex;
      align-items: center;
    }

    &-type {
      text-transform: uppercase;
      letter-spacing: 1px;
      color: var(--module-color);
    }
  }

  &__content {
    margin-top: 1em;
    column-width: 20vw;
    column-gap: 1rem;
  }

  &__error {
    color: white;
  }
}
</style>
