<template>
  <div id="podiumVisContainer">
    <el-button class="startButton" v-if="!started" @click="startAnimation">
      <span>
        <br /><font-awesome-icon class="startIcon" :icon="['fas', 'play']" />
        <p>Start</p>
      </span>
    </el-button>
    <div id="topVotesContainer">
      <div
        v-for="(voteSet, index) in topVotes"
        :key="voteSet[0].idea.id + 'Set'"
        class="topVoteSet columnLayout"
        :style="{
          width: 100 / topVotes.length - 1 + '%',
          transition: getIdeaTransitionConfig(
            topVotes.length - this.order[index] - 1
          ),
          marginTop: started
            ? (this.order[index] / voteSets.length) * 40 + '%'
            : '100%',
          pointerEvents: ended ? 'all' : 'none',
          columnCount: (topVotes.length > 3 || voteSet.length <= 1) ? 1 : 2,
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
  animationLength = 2;

  topAmount = 5;
  topVotes: VoteResult[][] = [];

  voteSets: number[] = [];
  order: number[] = [];

  getIdeaTransitionConfig(index: number): string {
    if (this.ended) {
      return 'all ' + this.animationLength/2 + 's ease';
    } else {
      return 'all ' + this.animationLength + 's ease ' + index + 's';
    }
  }

  startAnimation(): void {
    this.started = true;
    setTimeout(() => {
      this.ended = true;
    }, (this.voteSets.length+this.animationLength) * 1000);
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
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
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
#topVotesContainer {
  position: relative;
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: flex-start;
  height: 100%;
  overflow: hidden;
  border-bottom: 0.5rem solid var(--color-evaluating);
  .topVoteSet {
    position: relative;
    background-color: var(--color-evaluating);
    border-radius: var(--border-radius-small);
    padding: 0.5rem;
    height: 100%;

    overflow-x: hidden;
  }
  .topVoteSet:hover {
    margin-top: 0 !important;
  }
  .columnLayout {
    width: 100%;
    column-gap: 1rem;
    column-fill: balance;
  }
}
</style>
<style lang="scss"></style>
