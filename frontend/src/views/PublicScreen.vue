<template>
  <div class="public-screen">
    <main v-if="task" class="container container--spaced" ref="container">
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
          <Timer />
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
      <section v-if="showFallback" class="container container--centered">
        Sorry, nothing to see yet.
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

import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';
import Timer from '@/components/shared/atoms/Timer.vue';

import * as sessionService from '@/services/session-service';
import * as taskService from '@/services/task-service';
import { Idea } from '@/services/idea-service';
import { Task } from '@/services/task-service';
import ModuleType from '@/types/ModuleType';

import { setModuleStyles } from '@/utils/moduleStyles';

@Options({
  components: {
    IdeaCard,
    Timer,
  },
})
export default class PublicScreen extends Vue {
  @Prop() readonly sessionId!: string;

  task: Task | null = null;
  ideas: Idea[] = [];
  showFallback = false;

  ModuleType = ModuleType;

  async mounted(): Promise<void> {
    this.task = await sessionService.getPublicScreen(this.sessionId);
    if (this.task) {
      this.ideas = await taskService.getIdeasForTask(this.task.id);
      setModuleStyles(
        this.$refs.container as HTMLElement,
        ModuleType[this.task.taskType]
      );
    } else {
      this.showFallback = true;
    }
  }
}
</script>
<style lang="scss" scoped>
.public-screen {
  background: url('../assets/illustrations/stars-background_without_dust.png');
  background-size: contain;
  height: 100vh;

  &__overview {
    display: flex;
    justify-content: space-between;
    color: white;
    margin-top: 1rem;

    &-planet {
      height: 12rem;
      margin-left: 2rem;
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
    margin-top: 3em;
    column-width: 22vw;
    column-gap: 1rem;
  }
}
</style>
