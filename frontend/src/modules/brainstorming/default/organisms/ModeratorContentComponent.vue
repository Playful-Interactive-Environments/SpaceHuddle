<template>
  <header class="brainstorming__header">
    <label for="orderType" class="heading heading--xs">{{
      $t('module.brainstorming.default.moderatorContent.sortOrder')
    }}</label>
    <select
      v-model="orderType"
      id="orderType"
      class="select select--fullwidth"
      @change="getIdeas"
    >
      <option v-for="type in SortOrderOptions" :key="type" :value="type">
        {{ $t(`enum.ideaSortOrder.${IdeaSortOrder[type]}`) }}
      </option>
    </select>
  </header>
  <Expand v-for="(item, key) in orderGroupContent" :key="key">
    <template v-slot:title>{{ key.toUpperCase() }}</template>
    <template v-slot:content>
      <main class="brainstorming__content">
        <IdeaCard
          :idea="idea"
          v-for="(idea, index) in item"
          :key="index"
          @ideaDeleted="getIdeas"
        />
      </main>
    </template>
  </Expand>

  <!--<main class="brainstorming__content">
    <IdeaCard
      :idea="idea"
      v-for="(idea, index) in ideas"
      :key="index"
      @ideaDeleted="getIdeas"
    />
  </main>-->
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import * as ideaService from '@/services/idea-service';
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import Expand from '@/components/shared/atoms/Expand.vue';

@Options({
  components: {
    IdeaCard,
    Expand,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContentComponent extends Vue {
  @Prop() readonly taskId!: string;
  ideas: Idea[] = [];
  orderGroupContent: { [name: string]: Idea[] } = {};
  readonly interval = 3000;
  ideaInterval!: any;
  orderType = this.SortOrderOptions[0];

  IdeaSortOrder = IdeaSortOrder;

  get SortOrderOptions(): Array<keyof typeof IdeaSortOrder> {
    return Object.keys(IdeaSortOrder) as Array<keyof typeof IdeaSortOrder>;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getIdeas();
  }

  async getIdeas(): Promise<void> {
    if (this.taskId) {
      await ideaService
        .getIdeasForTask(this.taskId, this.orderType)
        .then((ideas) => {
          this.orderGroupContent = {};
          this.ideas = ideas;
          ideas.forEach((ideaItem) => {
            if (ideaItem.order) {
              const orderGroup = this.orderGroupContent[ideaItem.order];
              if (!orderGroup) {
                this.orderGroupContent[ideaItem.order] = [ideaItem];
              } else {
                orderGroup.push(ideaItem);
              }
            }
          });
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

<style lang="scss" scoped>
.brainstorming {
  &__header {
    padding: 0.5rem 1rem;
    margin-bottom: 1rem;
    border-radius: var(--border-radius);
    background-color: var(--color-darkblue);
    width: 100%;
    display: table;

    > * {
      display: table-cell;
    }

    label {
      width: 20%;
    }

    .heading {
      color: white;
    }
  }

  &__content {
    width: 100%;
    column-width: 22vw;
    column-gap: 1rem;
  }
}
</style>
