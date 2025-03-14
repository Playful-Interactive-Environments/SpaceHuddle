<template>
  <div id="tombolaVisContainer">
    <el-button class="startButton" v-if="!started" @click="startAnimation()">
      <span>
        <br /><font-awesome-icon class="startIcon" :icon="['fas', 'play']" />
        <p>Start</p>
      </span>
    </el-button>
    <div class="numbers" v-if="this.votes.length > 0">
      <div
        class="num"
        v-for="num in this.bestIdeaCount"
        :key="'ratingNum' + num"
        :id="'ratingNum' + num"
        :style="{
          width: 100 / this.bestIdeaCount + '%',
        }"
      >
        <p>
          {{
            Math.round((this.votes[num - 1].ratingSum + Number.EPSILON) * 100) /
            100
          }}
        </p>
      </div>
    </div>
    <div class="tombolaIdeas" :style="{ opacity: loaded ? 100 : 0 }">
      <IdeaCard
        v-for="vote in votes"
        :key="vote.idea.id"
        :id="vote.idea.id"
        :idea="vote.idea"
        :is-editable="false"
        class="ideaItem"
      />
    </div>
    <div
      v-if="this.votes.slice(bestIdeaCount).length > 0"
      id="revealOtherVotes"
      :style="{
        opacity: ended ? 1 : 0,
        pointerEvents: ended ? 'all' : 'none',
        display: ended ? 'flex' : 'none',
      }"
    >
      <a href="#otherVotesContainer" class="revealButton">
        Other ideas
        <br /><font-awesome-icon :icon="['fas', 'chevron-down']" />
      </a>
    </div>
    <div
      v-if="this.votes.slice(bestIdeaCount).length > 0"
      id="otherVotesContainer"
      class="columnLayout"
      :style="{
        opacity: ended ? 1 : 0,
        pointerEvents: ended ? 'all' : 'none',
        display: ended ? 'block' : 'none',
      }"
    >
      <IdeaCard
        v-for="vote in this.votes.slice(bestIdeaCount)"
        :key="vote.idea.id"
        :idea="vote.idea"
        :is-editable="false"
      />
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Bar } from 'vue-chartjs';
import { VoteResult } from '@/types/api/Vote';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';

@Options({
  components: {
    IdeaCard,
    Bar,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: 0 }) readonly timeModifier!: number;
  @Prop({ default: false }) readonly timerEnded!: boolean;
  @Prop({ default: [] }) readonly votes!: VoteResult[];

  loaded = false;

  started = false;
  ended = false;
  displayNumbers = false;

  animationLength = 2;

  intervalPositions = -1;

  bestIdeaCount = 5;

  mounted(): void {
    this.setRandomPosition();
    this.intervalPositions = setInterval(
      this.setRandomPosition,
      (this.animationLength / 1.5) * 1000
    );
    setTimeout(() => {
      this.loaded = true;
    }, (this.animationLength / 1.5) * 1000);
  }
  getIdeaTransitionConfig(): string {
    return 'all ' + this.animationLength + 's ease';
  }

  setRandomPosition(): void {
    const IdeaCardsHTML = Array.from(
      document.getElementsByClassName(
        'ideaItem'
      ) as HTMLCollectionOf<HTMLElement>
    );
    for (const ideaHTML of IdeaCardsHTML) {
      ideaHTML.style.top = Math.random() * 80 + '%';
      ideaHTML.style.left = Math.random() * 80 + '%';
      ideaHTML.style.zIndex = Math.floor(Math.random() * 100) + '';
    }
  }

  opacityCountdown(index: number): void {
    const idea = document.getElementById(this.votes[index].idea.id + '');
    if (index >= this.bestIdeaCount) {
      setTimeout(() => {
        if (idea) {
          idea.style.opacity = '0';
          idea.style.pointerEvents = 'none';
        }
        this.opacityCountdown(index - 1);
      }, 500);
    } else {
      this.ended = true;
    }
  }

  positionCountdown(index: number): void {
    const idea = document.getElementById(this.votes[index].idea.id + '');
    const num = document.getElementById('ratingNum' + (index + 1));
    if (index >= 0) {
      setTimeout(() => {
        if (idea) {
          idea.style.top = '0';
          idea.style.left = (100 / this.bestIdeaCount) * index + '%';
          idea.style.width = 100 / this.bestIdeaCount - 2 + '%';
          idea.style.margin = '0 1%';
          idea.style.zIndex = '';
          if (num) {
            num.style.opacity = '100';
          }
        }
        if (index > 0) {
          this.positionCountdown(index - 1);
        }
      }, 500);
    }
  }

  startAnimation(): void {
    this.started = true;
    this.opacityCountdown(this.votes.length - 1);
  }

  @Watch('ended', { immediate: true })
  onEndedChanged(): void {
    if (this.ended) {
      setTimeout(() => {
        clearInterval(this.intervalPositions);
        this.positionCountdown(this.bestIdeaCount - 1);
        this.displayNumbers = true;
      }, this.animationLength * 1000);
    }
  }

  @Watch('votes', { immediate: true })
  onVotesChanged(): void {
    if (this.bestIdeaCount > this.votes.length && this.votes.length != 0) {
      this.bestIdeaCount = this.votes.length;
    }
  }

  clearAllIntervals(): void {
    clearInterval(this.intervalPositions);
  }
  unmounted(): void {
    this.clearAllIntervals();
  }
}
</script>

<style scoped lang="scss">
#tombolaVisContainer {
  position: relative;
  width: 100%;
  height: 100%;
}

.tombolaIdeas {
  position: relative;
  width: 100%;
  height: calc(100% - var(--font-size-xxxxlarge));
  overflow: hidden;
  transition: opacity 1s ease;
  .ideaItem {
    width: 15%;
    min-width: 10rem;
    max-width: 20rem;
    transition: all 2s ease;
    position: absolute;
  }
}

.numbers {
  position: relative;
  display: flex;
  flex-direction: row;
  justify-content: space-evenly;
  align-items: center;
  width: 100%;
  height: fit-content;
  .num {
    font-size: var(--font-size-xxxxlarge);
    font-weight: var(--font-weight-bold);
    text-align: center;
    transition: opacity 1s ease;
    opacity: 0;
  }
}

.startButton {
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  z-index: 1000000;
  text-align: center;
  color: var(--color-dark-contrast);
  background: var(--color-background);
  padding: 2rem;
  border-radius: var(--border-radius);
  height: fit-content;
  width: fit-content;
  p,
  .startIcon {
    display: block;
    width: 100%;
    text-align: center;
  }
  p {
    font-size: var(--font-size-xxxlarge);
  }
  .startIcon {
    font-size: var(--font-size-xxxxlarge);
  }
}

#revealOtherVotes {
  width: 100%;
  height: 10%;
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  margin: -2rem 1rem 1rem;

  .revealButton {
    z-index: 1000000;
    text-align: center;
    color: var(--color-dark-contrast-light);
    background: transparent;
    p,
    .startIcon {
      display: block;
      width: 100%;
      text-align: center;
    }
    p {
      font-size: var(--el-font-size-medium);
    }
    .startIcon {
      font-size: var(--font-size-xxxxlarge);
    }
  }
}

#otherVotesContainer {
  width: 100%;
  column-width: 15rem;
  column-gap: 1rem;
  column-fill: balance;
}

#otherVotesContainer,
#revealOtherVotes {
  transition: all 1s ease;
}
</style>
<style lang="scss"></style>
