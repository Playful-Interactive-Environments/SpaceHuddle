<template>
  <div id="strataModuleContainer">
    <el-button class="startButton" v-if="!releaseIdeas" @click="startAnimation">
      <span>
        <br /><font-awesome-icon class="startIcon" :icon="['fas', 'play']" />
        <p>Start</p>
      </span>
    </el-button>
    <div
      id="strataContainer"
      :style="{
        animation: releaseIdeas
          ? `strataContainerScroll ${this.animationLength}s linear forwards`
          : '',
      }"
    >
      <div class="startingLine">
        <img
          src="@/assets/illustrations/Ecopolis/publicScreen.png"
          alt="Starting Line"
        />
      </div>
      <div v-if="votes[0]" id="lineContainer">
        <div
          v-for="index in 11"
          :key="index"
          :style="{
            top: index != 11 ? 100 - (index / 10) * 100 + '%' : '100%',
          }"
          class="horizontalLine"
        >
          <p>
            {{
              index != 11
                ? Math.round(
                    ((votes[0].ratingSum / 10) * index + Number.EPSILON) * 100
                  ) / 100
                : 0
            }}
          </p>
          <hr />
        </div>
      </div>
      <div id="ideaContainer">
        <div
          v-for="(vote, index) in votes"
          :key="vote.idea.id"
          class="ideaItemContainer"
          :style="{
            top: getIdeaPositionY(vote),
            left: releaseIdeas
              ? columns[(index + columnRandomOffset) % columns.length] + '%'
              : columns[(index + columnRandomOffset) % columns.length] +
                Math.random() * 3 -
                1.5 +
                '%',
            zIndex: animationOver ? 100 - index : Math.random() * 100,
            transition: getIdeaTransitionConfig(vote),
          }"
        >
          <p class="score" :style="{ opacity: animationOver ? 1 : 0 }">
            {{ vote.ratingSum }}
          </p>
          <IdeaCard
            :idea="vote.idea"
            :is-editable="false"
            :portrait="!(vote.idea.image || vote.idea.link)"
            class="ideaItem"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import {Prop, Watch} from 'vue-property-decorator';
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

  columns = [60, 0, 80, 20, 40];
  columnRandomOffset = Math.floor(Math.random() * 5);

  releaseIdeas = false;
  animationOver = false;
  animationLength = 15;

  getIdeaPositionY(vote: VoteResult): string {
    if (this.releaseIdeas) {
      return 100 - (vote.ratingSum / this.votes[0].ratingSum) * 100 + '%';
    } else {
      return '100%';
    }
  }

  getIdeaTransitionConfig(vote): string {
    return (
      'all ' +
      (vote.ratingSum / this.votes[0].ratingSum - Math.random() * 0.15 + 0.1) *
        this.animationLength +
      's linear'
    );
  }

  startAnimation(): void {
    this.releaseIdeas = true;
    setTimeout(() => {
      this.animationOver = true;
    }, (this.animationLength + 0.15 + 0.075) * 1000);
  }
}
</script>

<style scoped lang="scss">
#strataModuleContainer {
  position: relative;
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
  color: white;
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

#strataContainer {
  position: relative;
  width: calc(100% + (var(--side-padding) * 2));
  margin-left: calc(var(--side-padding) * -1);
  height: 500%;
  background: rgb(7, 0, 133);
  background: linear-gradient(
    180deg,
    #1a313b 0.75%,
    #1a313b 20.75%,
    #205269 20.75%,
    #205269 40.75%,
    #348d93 40.75%,
    #348d93 60.75%,
    #64c5cc 60.75%,
    #64c5cc 80.75%,
    #a1d6da 80.75%,
    #a1d6da 100.75%
  );
  transform: translateY(-86%);

  #ideaContainer {
    position: relative;
    width: calc(100% - (var(--side-padding) * 2));
    margin-left: var(--side-padding);
    height: 100%;
  }
  #lineContainer {
    position: absolute;
    width: 100%;
    height: 100%;
    .horizontalLine {
      margin-top: 1.5%;
      position: absolute;
      width: 100%;
      hr {
        display: block;
        color: white;
        width: 100%;
        margin: 0;
      }
      p {
        position: absolute;
        font-size: var(--font-size-xxlarge);
        color: white;
        font-weight: var(--font-weight-bold);
        margin-left: 1rem;
      }
    }
  }
  .ideaItemContainer {
    margin: 1.5%;
    position: absolute;
    width: 17%;
    min-width: 10rem;
    top: 100%;
    .ideaItem {
      box-shadow: var(--color-dark-contrast) 0.2rem 0.2rem 0.4rem;
    }
    .score {
      position: absolute;
      right: -1.25rem;
      top: 0;
      font-size: var(--font-size-xlarge);
      color: white;
      font-weight: var(--font-weight-bold);
      text-align: left;
      width: 1rem;
      transition: opacity 1s ease;
    }
  }
  #startButton {
    position: absolute;
    z-index: 100000;
  }

  .ideaItem:hover {
    z-index: 10000 !important;
  }

  .startingLine {
    position: absolute;
    top: 99.5%;
    width: 100%;
    background-color: #a1d6da;
    img {
      width: 100%;
    }
  }
}
</style>
<style lang="scss">
@keyframes strataContainerScroll {
  0% {
    transform: translateY(-86%);
  }
  100% {
    transform: translateY(0);
  }
}
</style>
