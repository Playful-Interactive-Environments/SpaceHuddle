<template>
  <ParticipantModuleDefaultContainer :task-id="taskId">
    <template v-slot:planet>
      <img
        src="@/assets/illustrations/planets/brainstorming.png"
        alt="planet"
        class="module-container__planet"
      />
    </template>
    <div class="container2--centered center">
      <span v-if="randomIdea">{{ randomIdea.keywords }}</span>
      <br />
      <span v-if="randomIdea">{{ randomIdea.description }}</span>
    </div>
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/ParticipantModuleDefaultContainer.vue';
import * as ideaService from '@/services/idea-service';
import { Idea } from '@/types/api/Idea.ts';

@Options({
  components: {
    ParticipantModuleDefaultContainer,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ParticipantView extends Vue {
  @Prop() readonly taskId!: string;
  readonly intervalTime = 10000;
  interval!: any;

  randomIdea: Idea | null = null;

  async mounted(): Promise<void> {
    await this.getTaskIdeas();
    this.startIdeaInterval();
  }

  startIdeaInterval(): void {
    this.interval = setInterval(this.getTaskIdeas, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }

  async getTaskIdeas(): Promise<void> {
    ideaService.getIdeasForTask(this.taskId).then((queryResult) => {
      const randomIndex = Math.floor(Math.random() * queryResult.length);
      this.randomIdea = queryResult[randomIndex];
    });
  }
}
</script>

<style lang="scss" scoped>
.center {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 200px;
}
</style>
