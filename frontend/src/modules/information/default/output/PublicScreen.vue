<template>
  <section
    ref="content"
    class="layout__columns"
    :style="{ '--column-width': portrait ? '10rem' : '20rem' }"
  >
    <IdeaCard
      v-for="(idea, index) in ideas"
      :idea="idea"
      :key="index"
      :is-editable="false"
      :show-state="false"
      :portrait="portrait"
      :fix-height="fixCardHeight"
    />
  </section>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as ideaService from '@/services/idea-service';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';

@Options({
  components: {
    IdeaCard,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  ideas: Idea[] = [];
  contentHeight = 100;

  get portrait(): boolean {
    return this.contentHeight > 200;
  }

  get fixCardHeight(): string | null {
    if (this.portrait) return null;
    return `${this.contentHeight * 0.95}px`;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    ideaService.registerGetIdeasForTask(
      this.taskId,
      IdeaSortOrder.ORDER,
      null,
      this.updateIdeas,
      this.authHeaderTyp,
      2 * 60
    );
  }

  updateIdeas(ideas: Idea[]): void {
    this.ideas = ideas;
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateIdeas);
  }

  mounted(): void {
    const content = this.$refs.content as HTMLElement;
    if (content) {
      this.contentHeight = content.clientHeight;
    }
  }

  unmounted(): void {
    this.deregisterAll();
  }
}
</script>

<style lang="scss" scoped>
.layout__columns {
  height: 100%;
  column-width: var(--column-width);
}
</style>
