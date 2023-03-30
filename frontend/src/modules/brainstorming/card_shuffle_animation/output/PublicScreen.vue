<template>
  <div>
    <ul
      class="list gallery-item"
      :style="{ '--card-width': `${columnWidth}px` }"
      ref="cardList"
    >
      <li
        class="shuffle-card"
        v-for="(idea, index) in ideas"
        :key="index"
        :style="{
          '--column': columnCount - (index % columnCount) - 1,
          '--row': Math.floor(index / columnCount),
          'z-index': index >= columnCount ? index + 1 : columnCount - index,
        }"
      >
        <IdeaCard
          :idea="idea"
          :is-editable="false"
          :cutLongTexts="false"
          :ignore-limited-description-length="true"
          class="public-idea"
        />
      </li>
    </ul>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as ideaService from '@/services/idea-service';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import {
  defaultFilterData,
  FilterData,
  getFilterForTask,
} from '@/components/moderator/molecules/IdeaFilter.vue';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
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
  filter: FilterData = { ...defaultFilterData };

  ideaCash!: cashService.SimplifiedCashEntry<Idea[]>;
  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.MODERATOR,
      2 * 60
    );
    this.ideaCash = ideaService.registerGetIdeasForTask(
      this.taskId,
      this.filter.orderType,
      null,
      this.updateIdeas,
      this.authHeaderTyp,
      2 * 60
    );
    this.active = true;
    this.stackCards();
  }

  updateTask(task: Task): void {
    this.filter = getFilterForTask(task);
    this.ideaCash.parameter.urlParameter = ideaService.getIdeaListParameter(
      this.filter.orderType,
      null
    );
  }

  updateIdeas(ideas: Idea[]): void {
    ideas = this.filter.orderAsc ? ideas : ideas.reverse();
    ideas = ideaService.filterIdeas(
      ideas,
      this.filter.stateFilter,
      this.filter.textFilter
    );
    this.ideas = ideas;
  }

  shuffleDelay = 150;
  stackCards(): void {
    if (!this.active) return;

    const cardList = this.$refs.cardList as HTMLElement;
    const cardCount = cardList ? cardList.children.length : 0;
    if (cardList && cardCount > 0) {
      for (let index = 0; index < cardCount; index++) {
        const child = cardList.children[index] as HTMLElement;
        const delay =
          index >= this.columnCount
            ? index * this.shuffleDelay
            : (this.columnCount - index) * this.shuffleDelay;
        setTimeout(() => {
          child.className = 'shuffle-card';
        }, delay);
      }
    }
    setTimeout(() => {
      this.spreadCards();
    }, cardCount * this.shuffleDelay);
  }

  spreadCards(): void {
    if (!this.active) return;

    const cardList = this.$refs.cardList as HTMLElement;
    const cardCount = cardList ? cardList.children.length : 0;
    if (cardList && cardCount > 0) {
      for (let index = 0; index < cardCount; index++) {
        const child = cardList.children[index] as HTMLElement;
        const delay =
          index >= this.columnCount
            ? (cardCount - index) * this.shuffleDelay
            : (cardCount - (this.columnCount - index)) * this.shuffleDelay;
        setTimeout(() => {
          child.className = 'shuffle-card spread';
        }, delay);
      }
    }
    setTimeout(() => {
      this.stackCards();
    }, cardCount * this.shuffleDelay + 10000);
  }

  columnCount = 5;
  columnWidth = 200;
  calcCardGrid(): void {
    const minCardWidth = 200;
    const windowWidth = window.innerWidth - 110;
    this.columnCount = Math.floor(windowWidth / minCardWidth);
    this.columnWidth = Math.floor(windowWidth / this.columnCount);
  }

  active = false;
  async mounted(): Promise<void> {
    this.active = true;
    window.addEventListener('resize', this.calcCardGrid);
    this.calcCardGrid();
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateIdeas);
  }

  unmounted(): void {
    this.deregisterAll();
    this.active = false;
    window.removeEventListener('resize', this.calcCardGrid);
  }
}
</script>

<style lang="scss" scoped>
.list {
  height: 652px;
  position: relative;
  list-style-type: none;
  padding-left: 0;
}

.shuffle-card {
  transition: all 1s cubic-bezier(0.68, -0.55, 0.265, 1.55);

  perspective: 1000;
  -ms-transform: perspective(1000px);
  -moz-transform: perspective(1000px);
  -ms-transform-style: preserve-3d;
  -moz-transform-style: preserve-3d;

  --card-height: 311px;

  float: left;
  width: var(--card-width);
  height: var(--card-height);

  position: absolute;
  right: 0;
  top: 0;
  padding: 0 0 15px 15px;

  &.spread {
    right: calc(var(--column) * var(--card-width));
    top: calc(var(--row) * var(--card-height));
  }
}

.el-card {
  width: 100%;
  height: 100%;
}

.el-card::v-deep(.el-card__body) {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.el-card::v-deep(.card__text) {
  flex-basis: auto;
  flex-grow: 1;
  flex-shrink: 1;
  text-align: inherit;

  display: flex;
  flex-direction: column;
  //justify-content: space-between;
  //align-items: center;
  gap: 0.5rem;
}
</style>
