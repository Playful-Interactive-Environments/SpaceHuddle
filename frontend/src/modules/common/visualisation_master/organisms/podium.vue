<template>
  <div id="podiumVisContainer">
    <div class="buttonContainer" v-if="!ended">
      <el-button
        class="startButton"
        v-if="!started && this.topVotes[0]"
        @click="startAnimation"
      >
        <span>
          <br /><font-awesome-icon class="startIcon" :icon="['fas', 'play']" />
          <p>Start full animation</p>
        </span>
      </el-button>
      <el-button
        class="startButton"
        v-if="!started && this.topVotes[0]"
        @click="startModeratedAnimation"
      >
        <span>
          <br /><font-awesome-icon class="startIcon" :icon="['fas', 'play']" />
          <p>Start moderated flow</p>
        </span>
      </el-button>
      <el-button
        class="nextButton"
        v-if="started && moderatedFlow && moderatedIndex > 0"
        @click="moderatedIndex -= 1"
      >
        <font-awesome-icon class="startIcon" :icon="['fas', 'play']" />
        <p>Next</p>
      </el-button>
    </div>
    <div id="topVotesContainer">
      <div
        v-for="(voteSet, index) in topVotes"
        :key="voteSet[0].idea.id + 'Set'"
        class="topVoteSet"
        :style="{
          width: 100 / topVotes.length - 1 + '%',
          transition: getIdeaTransitionConfig(
            topVotes.length - this.order[index] - 1
          ),
          marginTop: getTopMargin(index),
          pointerEvents: ended ? 'all' : 'none',
        }"
        @transitionend="
          moderatedIndex =
            !ended && !moderatedFlow ? moderatedIndex - 0.5 : moderatedIndex
        "
      >
        <div
          class="columnLayout"
          :style="{
            columnCount: topVotes.length > 3 || voteSet.length <= 1 ? 1 : 2,
          }"
        >
          <IdeaCard
            v-for="vote in voteSet"
            :key="vote.idea.id"
            :idea="vote.idea"
            :is-editable="false"
            class="topVote"
          />
        </div>
      </div>
    </div>
    <div
      v-if="
        this.votes.filter(
          (d) => d.ratingSum < this.voteSets[this.voteSets.length - 1]
        ).length > 0
      "
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
      v-if="
        this.votes.filter(
          (d) => d.ratingSum < this.voteSets[this.voteSets.length - 1]
        ).length > 0
      "
      id="otherVotesContainer"
      class="columnLayout"
      :style="{
        opacity: ended ? 1 : 0,
        pointerEvents: ended ? 'all' : 'none',
        display: ended ? 'block' : 'none',
      }"
    >
      <IdeaCard
        v-for="vote in this.votes.filter(
          (d) => d.ratingSum < this.voteSets[this.voteSets.length - 1]
        )"
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
  chartData: any = {
    labels: [],
    datasets: [],
  };

  started = false;
  ended = false;
  animationLength = 1.5;

  topAmount = 5;
  topVotes: VoteResult[][] = [];

  voteSets: number[] = [];
  order: number[] = [];
  revealedVoteIndexes: number[] = [];
  moderatedFlow = false;
  moderatedIndex = this.topAmount;

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  getIdeaTransitionConfig(index: number): string {
    if (this.ended) {
      return 'all ' + this.animationLength / 2 + 's ease';
    } else {
      return 'all ' + this.animationLength + 's ease';
    }
  }

  startAnimation(): void {
    this.started = true;
    this.moderatedIndex -= 1;
  }

  startModeratedAnimation(): void {
    this.started = true;
    this.moderatedFlow = true;
  }

  getTopMargin(index: number): string {
    if (this.order[index] > this.moderatedIndex) {
      if (this.moderatedIndex <= 0) {
        setTimeout(() => {
          this.ended = true;
        }, 500);
      }
      return (this.order[index] / this.voteSets.length) * 40 + '%';
    } else if (this.order[index] === this.moderatedIndex) {
      return '0%';
    } else {
      return '100%';
    }
  }

  @Watch('votes', { immediate: true })
  onVotesChanged(): void {
    if (this.votes) {
      const votes = this.votes;
      this.voteSets = [];
      this.topVotes = [];
      this.order = [];
      for (let i = 0; i < votes.length; i++) {
        this.voteSets.push(votes[i].ratingSum);
      }
      this.voteSets = Array.from(new Set(this.voteSets)).splice(0, 5);
      for (let i = 0; i < this.voteSets.length; i++) {
        if (i % 2 === 0) {
          this.order.push(i);
          this.topVotes.push(
            votes.filter((vote) => vote.ratingSum === this.voteSets[i])
          );
        } else {
          this.order.unshift(i);
          this.topVotes.unshift(
            votes.filter((vote) => vote.ratingSum === this.voteSets[i])
          );
        }
      }
      if (!this.started && !this.ended) {
        this.moderatedIndex = this.topVotes.length;
      }
    }
  }
}
</script>

<style scoped lang="scss">
#podiumVisContainer {
  position: static;
  width: 100%;
  height: 100%;
}
.startButton {
  z-index: 1000000;
  text-align: center;
  color: var(--color-dark-contrast);
  background: transparent;

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

.nextButton {
  z-index: 1000000;
  text-align: center;
  color: var(--color-dark-contrast);
  background: transparent;
  position: absolute;
  top: 0;
  left: 2rem;
  p {
    font-size: var(--font-size-xlarge);
    margin-left: 0.5rem;
  }
  .startIcon {
    font-size: var(--font-size-xxlarge);
  }
}

.buttonContainer {
  position: absolute;
  left: 0;
  right: 0;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-evenly;
  width: 100%;
  height: 80%;
}

#topVotesContainer {
  position: relative;
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: flex-start;
  height: 95%;
  overflow: hidden;
  border-bottom: 0.5rem solid var(--color-evaluating);
  .topVoteSet {
    position: relative;
    background-color: var(--color-evaluating);
    border-radius: var(--border-radius-small) var(--border-radius-small) 0 0;
    padding: 0.5rem;
    height: 100%;
    overflow-y: scroll;
    scrollbar-width: none; /* Hide scrollbar for Firefox */
    -ms-overflow-style: none;
  }
  .topVoteSet::-webkit-scrollbar {
    display: none;
  }
  .topVoteSet:hover {
    margin-top: 0 !important;
  }
  .columnLayout {
    width: 100%;
    min-height: 100%;
    column-gap: 1rem;
    column-fill: balance;
    margin: 0;
  }
  .crown {
    position: absolute;
    transform: rotate(10deg);
    width: 5rem;
    z-index: 100000;
    top: -2rem;
    right: -1rem;
  }
}
#revealOtherVotes {
  width: 100%;
  height: 10%;
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 1rem;
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
