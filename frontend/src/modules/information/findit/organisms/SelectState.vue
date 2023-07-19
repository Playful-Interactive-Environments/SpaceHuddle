<template>
  <div>
    <div class="link new" @click="levelSelected(null)">
      {{ $t('module.information.findit.participant.newLevel') }}
    </div>
    <div
      class="link"
      :class="{ own: isOwnLevel(idea) }"
      v-for="idea of ideas"
      :key="idea.id"
      @click="levelSelected(idea)"
    >
      {{ idea.keywords }}
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import Placeable from '@/modules/information/findit/types/Placeable';
import * as cashService from '@/services/cash-service';
import * as authService from '@/services/auth-service';
import * as ideaService from '@/services/idea-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

@Options({
  components: {},
  emits: ['selectionDone'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class SelectState extends Vue {
  @Prop() readonly taskId!: string;
  ideas: Idea[] = [];

  unmounted(): void {
    cashService.deregisterAllGet(this.updateIdeas);
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      ideaService.registerGetIdeasForTask(
        this.taskId,
        null,
        null,
        this.updateIdeas,
        EndpointAuthorisationType.PARTICIPANT,
        3
      );
    }
  }

  updateIdeas(ideas: Idea[]): void {
    this.ideas = ideas;
  }

  isOwnLevel(level: Idea): boolean {
    return level.participantId === authService.getParticipantId();
  }

  levelSelected(level: Idea | null) {
    if (!level) {
      this.$emit('selectionDone', []);
    } else {
      this.$emit('selectionDone', level.parameter as Placeable[]);
    }
  }
}
</script>

<style lang="scss" scoped>
.link {
  background-color: var(--color-primary);
  color: white;
  border-radius: var(--border-radius);
  margin: 1rem;
  padding: 1rem;
}

.new {
  background-color: var(--color-darkblue-light);
}

.own {
  background-color: var(--color-yellow);
}
</style>
