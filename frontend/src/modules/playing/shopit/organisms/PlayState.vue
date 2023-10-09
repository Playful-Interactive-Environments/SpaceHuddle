<template>
  <div
    class="gameArea"
    :style="{ height: height, backgroundImage: 'url(' + background + ')' }"
    v-if="playStateType === PlayStateType.play"
  >
    <div class="hand">
      <div
        v-for="card in cardHand"
        :key="card[7]"
        :id="card[7]"
        class="cardContainer"
        :style="{ backgroundImage: 'url(' + cardBackground + ')' }"
        @click="activeCardChanged(card)"
      >
        <ul class="cardStats">
          <li class="cardCost">{{ card[0] }}</li>
          <li>{{ card[1] }}</li>
          <li>{{ card[2] }}</li>
          <li>{{ card[3] }}</li>
          <li>{{ card[4] }}</li>
        </ul>
        <img :src="getCardSprite(card)" alt="{{ card[7] }}" class="cardImage" />
        <font-awesome-icon
          :icon="gameConfig.categories[card[6]].settings.icon"
          class="categoryIcon"
        />
      </div>
    </div>
  </div>
  <div
    class="gameArea result"
    :style="{ height: height }"
    v-if="playStateType === PlayStateType.win"
  >
    <span>{{ $t('module.playing.shopit.participant.win') }}</span>
  </div>
  <div
    class="gameArea result"
    :style="{ height: height }"
    v-if="playStateType === PlayStateType.lost"
  >
    <span>{{ $t('module.playing.shopit.participant.lost') }}</span>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import GameContainer, {
  BackgroundPosition,
} from '@/components/shared/atoms/game/GameContainer.vue';
import { ObjectSpace } from '@/types/enum/ObjectSpace';
import { until } from '@/utils/wait';
import * as tutorialService from '@/services/tutorial-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Tutorial } from '@/types/api/Tutorial';
import * as cashService from '@/services/cash-service';
import { ElMessage } from 'element-plus';
import * as themeColors from '@/utils/themeColors';
import gameConfig from '@/modules/playing/shopit/data/gameConfig.json';
import { Idea } from '@/types/api/Idea';
import * as configParameter from '@/utils/game/configParameter';

/* eslint-disable @typescript-eslint/no-explicit-any*/
const tutorialType = 'shop-it-object';

enum PlayStateType {
  play,
  win,
  lost,
}

export interface PlayStateResult {
  stars: number;
  time: number;
  collected: number;
  total: number;
}

@Options({
  computed: {
    ObjectSpace() {
      return ObjectSpace;
    },
    BackgroundPosition() {
      return BackgroundPosition;
    },
  },
  components: {},
  emits: ['playFinished'],
})
export default class PlayState extends Vue {
  @Prop() readonly taskId!: string;
  //@Prop({ default: null }) readonly level!: Idea | null;
  @Prop({ default: '100%' }) readonly height!: string;
  @Prop({ default: EndpointAuthorisationType.PARTICIPANT })
  authHeaderTyp!: EndpointAuthorisationType;
  gameWidth = 0;
  gameHeight = 0;
  showToolbox = false;
  tutorialSteps: Tutorial[] = [];
  levelType = '';
  gameConfig = gameConfig;

  totalCount = 0;
  collectedCount = 0;
  startTime = Date.now();

  playStateType = PlayStateType.play;
  PlayStateType = PlayStateType;

  background = gameConfig.gameValues.background;
  cardBackground = gameConfig.gameValues.cardBackground;
  cardSpriteFolder = gameConfig.gameValues.spriteFolder;

  activeCard: any[] = [];

  cards = this.shuffle(this.parseCards(gameConfig));
  cardHand: any[] = [];

  clearPlayState(): void {
    this.levelType = '';
    this.totalCount = 0;
    this.collectedCount = 0;
    this.startTime = Date.now();
    this.cards = this.shuffle(this.parseCards(gameConfig));
  }

  get backgroundColor(): string {
    return themeColors.getBackgroundColor();
  }

  mounted(): void {
    tutorialService.registerGetList(this.updateTutorial, this.authHeaderTyp);
    this.initialCardPull();
    console.log(this.cardHand);
    console.log(this.cards);
    /*this.eventBus.off(EventType.CHANGE_TUTORIAL);
    this.eventBus.on(EventType.CHANGE_TUTORIAL, async (steps) => {
      this.updateTutorial(steps as Tutorial[]);
    });*/
  }

  updateTutorial(steps: Tutorial[]): void {
    this.tutorialSteps = steps.filter((step) => step.type === tutorialType);
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTutorial);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  parseCards(cards) {
    const cardArray: any[] = [];
    const data = cards.categories;
    for (const category in data) {
      const categoryItems = data[category].items;
      for (const itemKey in categoryItems) {
        const item = categoryItems[itemKey];
        const itemValues = Object.values(item);
        itemValues.push(itemKey);
        cardArray.push(itemValues);
      }
    }
    return cardArray;
  }

  getCardSprite(card) {
    return this.cardSpriteFolder + card[7] + '.png';
  }

  shuffle(cards) {
    let currentIndex = cards.length;
    let randomIndex;

    while (currentIndex > 0) {
      randomIndex = Math.floor(Math.random() * currentIndex);
      currentIndex--;

      [cards[currentIndex], cards[randomIndex]] = [
        cards[randomIndex],
        cards[currentIndex],
      ];
    }
    return cards;
  }

  initialCardPull() {
    for (let i = 0; i < 3; i++) {
      const card = this.cards.pop();
      this.cardHand.push(card);
    }
  }

  cardPlayed(card) {
    const index = this.cardHand.indexOf(card);
    this.cardHand.splice(index, 1);
    console.log(this.cardHand);
  }

  drawNewCard() {
    const card = this.cards.pop();
    this.cardHand.push(card);
  }

  activeCardChanged(card) {
    let element = document.getElementById(this.activeCard[7]);
    if (element) {
      element.classList.remove('cardContainerActive');
    }
    this.activeCard = card;
    console.log(card[7]);
    element = document.getElementById(this.activeCard[7]);
    if (element) {
      element.classList.add('cardContainerActive');
    }
  }
}
</script>

<style scoped lang="scss">
.gameArea {
  height: calc(100%);
  width: 100%;
  position: relative;
  background-size: cover;
}

.custom-renderer-wrapper {
  height: 100%;
}

.overlay-bottom {
  pointer-events: none;
  position: absolute;
  z-index: 100;
  bottom: 1rem;
  right: 1rem;
  left: 1rem;
  text-align: center;
  font-size: var(--font-size-xxlarge);
  color: white;
}

.result {
  font-size: var(--font-size-xxlarge);
  display: flex;
  align-items: center;

  span {
    width: 100%;
    text-align: center;
  }
}

.hand {
  position: absolute;
  height: 30%;
  width: 100%;
  display: flex;
  justify-content: center;
  border: 1px solid red;
  bottom: 0;
  align-items: center;
}

.cardContainer {
  position: relative;
  aspect-ratio: 1904/2564;
  background-size: cover;
  z-index: 1;
  height: 80%;
  padding: 2%;
  margin-left: -10%;
  left: 5%;
  filter: drop-shadow(var(--color-dark-contrast) -0.4rem 0.2rem 0.2rem);
  transition: 0.3s;
}

.cardContainerActive {
  z-index: 2;
  transform: translateY(-1rem);
  transition: 0.3s;
}

.cardStats {
  z-index: 10;
  color: var(--color-dark-contrast);
  font-size: var(--font-size-small);
  font-weight: var(--font-weight-semibold);
  font-family: var(--font-family);
}

.cardCost {
  font-size: var(--font-size-xlarge);
  font-weight: var(--font-weight-bold);
}

.categoryIcon {
  position: absolute;
  bottom: 0;
  left: 0;
  margin: 5%;
}

.cardImage {
  z-index: 1;
  position: absolute;
  width: 65%;
  bottom: 0;
  right: 0;
  margin: 2.7%;
}
</style>
