<template>
  <div
    class="gameArea"
    :style="{ height: height }"
    v-if="playStateType === PlayStateType.play"
  >
    <div
      class="opponentHand"
      :style="{ backgroundImage: 'url(' + gameConfig.gameValues.opponentHand + ')' }"
    ></div>
    <div class="categories" id="opponent"></div>
    <div id="activeCards"></div>
    <div class="categories" id="own">
      <div
        v-for="cat in categoryPoints"
        :key="cat[0]"
        :id="cat[0]"
        class="categoryContainer"
      >
        <span
          class="circle categoryItem"
          :class="{ fullCircle: cat[1] >= 2 }"
        ></span>
        <span
          class="circle categoryItem"
          :class="{ fullCircle: cat[1] >= 1 }"
        ></span>
        <font-awesome-icon
          :icon="gameConfig.categories[cat[0]].settings.icon"
          class="categoryIcon categoryItem"
        />
      </div>
    </div>
    <div class="CO2BarContainer">
      <div class="CO2BarBackground">
        <p>CO²: {{ pointsSpent }}/{{ maxCost }}</p>
        <div
          class="CO2Bar"
          :style="{ width: (pointsSpent / maxCost) * 100 + '%' }"
          :class="{ hidden: pointsSpent / maxCost <= 0 }"
        ></div>
      </div>
    </div>
    <TransitionGroup name="hand" class="hand" tag="div">
      <div
        v-for="card in cardHand"
        :key="card[7]"
        :id="card[7]"
        class="cardContainer"
        :style="{ backgroundImage: 'url(' + gameConfig.gameValues.cardBackground + ')' }"
        @click="activeCardChanged(card)"
      >
        <ul class="cardStats">
          <li class="cardCost">
            {{ card[0] }}
            <hr />
          </li>
          <li>
            {{ card[1].split(' ')[0] }}<span>{{ card[1].split(' ')[1] }}</span>
          </li>
          <li>
            {{ card[2].split(' ')[0] }}<span>{{ card[2].split(' ')[1] }}</span>
          </li>
          <li>
            {{ card[3].split(' ')[0] }}<span>{{ card[3].split(' ')[1] }}</span>
          </li>
          <li>
            {{ card[4].split(' ')[0] }}<span>{{ card[4].split(' ')[1] }}</span>
          </li>
          <li>
            {{ card[5].split(' ')[0] }}<span>{{ card[5].split(' ')[1] }}</span>
          </li>
        </ul>
        <img :src="getCardSprite(card)" alt="{{ card[7] }}" class="cardImage" />
        <font-awesome-icon
          :icon="gameConfig.categories[card[6]].settings.icon"
          class="categoryCardIcon"
        />
      </div>
      <button id="cardSelectButton" @click="cardPlayed(activeCard)">
        Play card!
      </button>
    </TransitionGroup>
  </div>
  <div
    class="gameArea result"
    :style="{ height: height }"
    v-if="playStateType === PlayStateType.win"
  >
    <span>{{ $t('module.playing.shopit.participant.win') }}</span>
    <span v-if="reason === 'category'">{{
      $t('module.playing.shopit.participant.winCategories')
    }}</span>
    <span v-if="reason === 'points'">{{
      $t('module.playing.shopit.participant.winPoints')
    }}</span>
  </div>
  <div
    class="gameArea result"
    :style="{ height: height }"
    v-if="playStateType === PlayStateType.lost"
  >
    <span>{{ $t('module.playing.shopit.participant.lost') }}</span>
    <span v-if="reason === 'category'">{{
      $t('module.playing.shopit.participant.lostCategories')
    }}</span>
    <span v-if="reason === 'points'">{{
      $t('module.playing.shopit.participant.lostPoints')
    }}</span>
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

  cardSpriteFolder = gameConfig.gameValues.spriteFolder;

  activeCard: any[] = [];
  cards = this.shuffle(this.parseCards(gameConfig));
  cardHand: any[] = [];
  testCard = this.cards[9];

  categoryPoints: any[] = [];

  maxCost = 130;
  pointsSpent = 0;
  reason = '';

  clearPlayState(): void {
    this.levelType = '';
    this.totalCount = 0;
    this.collectedCount = 0;
    this.startTime = Date.now();
    this.cards = this.shuffle(this.parseCards(gameConfig));
  }

  playstateChange(outcome, reason) {
    switch (outcome) {
      case 'lost':
        this.playStateType = PlayStateType.lost;
        break;
      case 'win':
        this.playStateType = PlayStateType.win;
        break;
    }
    this.reason = reason;
  }

  get backgroundColor(): string {
    return themeColors.getBackgroundColor();
  }

  mounted(): void {
    tutorialService.registerGetList(this.updateTutorial, this.authHeaderTyp);
    this.categorySetup();
    this.initialCardPull();
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

  categorySetup() {
    const data = this.gameConfig.categories;
    for (const category in data) {
      this.categoryPoints.push([category, 0]);
    }
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
    console.log(
      'Test Card: ' +
        this.testCard[7] +
        ', Cost: ' +
        this.testCard[0] +
        ', Category: ' +
        this.testCard[6]
    );
  }

  cardPlayed(card) {
    const container = document.getElementById('activeCards');
    const element = document.getElementById(card[7]);
    const button = document.getElementById('cardSelectButton');
    if (element && container) {
      element.classList.remove('cardContainer');
      element.classList.add('cardPlayed');
      if (button) {
        button.setAttribute('disabled', '');
      }
      element.classList.remove('cardContainerActive');
      container.append(element.cloneNode(true));
      const index = this.cardHand.indexOf(card);
      this.cardHand.splice(index, 1);
    }
    setTimeout(() => {
      this.compareCards(this.testCard, card);
    }, 2000);
  }

  drawNewCard() {
    if (this.cards.length > 0) {
      const card = this.cards.pop();
      this.cardHand.unshift(card);
    }

    this.testCard = this.cards[Math.floor(Math.random() * this.cards.length)];
    console.log(
      'Test Card: ' +
        this.testCard[7] +
        ', Cost: ' +
        this.testCard[0] +
        ', Category: ' +
        this.testCard[6]
    );
  }

  activeCardChanged(card) {
    let element = document.getElementById(this.activeCard[7]);
    if (element) {
      element.classList.remove('cardContainerActive');
    }
    this.activeCard = card;
    element = document.getElementById(this.activeCard[7]);
    if (element) {
      if (!element.classList.contains('cardPlayed')) {
        element.classList.add('cardContainerActive');
      }
    }
  }

  compareCards(card, card2) {
    //card = Card that was there first. Wins in case of category mismatch
    //Compares the cost + category of the cards and decides the winner
    let winningCard;
    if (card && card2) {
      if (card[6] == card2[6]) {
        if (card[0] > card2[0]) {
          this.pointsSpent += card[0] + card2[0];
          winningCard = card;
        } else {
          this.pointsSpent += card[0] + card2[0];
          winningCard = card2;
        }
      } else {
        winningCard = card;
        this.pointsSpent += winningCard[0];
      }
    }
    if (this.pointsSpent >= this.maxCost) {
      this.playstateChange('lost', 'points');
    }
    console.log('Winner: ' + winningCard[7]);
    //Check if winning card is the own or the opponents
    //Adds points to winners category
    for (let i = 0; i < this.categoryPoints.length; i++) {
      if (this.categoryPoints[i][0] == winningCard[6]) {
        this.categoryPoints[i][1] += 1;
      }
    }
    console.log(this.categoryPoints.every((row) => row[1] >= 2));
    if (this.categoryPoints.every((row) => row[1] >= 2)) {
      console.log(this.categoryPoints);
      this.playstateChange('win', 'category');
    }
    //removes cards from play
    /*const index = this.cardHand.indexOf(card2);
    this.cardHand.splice(index, 1);*/

    const cardsToDelete = document.getElementsByClassName('cardPlayed');
    while (cardsToDelete[0]) {
      if (cardsToDelete[0].parentNode) {
        cardsToDelete[0].parentNode.removeChild(cardsToDelete[0]);
      }
    }
    //draw new cards
    setTimeout(() => {
      this.drawNewCard();
    }, 500);
    //reactivate Button
    const button = document.getElementById('cardSelectButton');
    if (button) {
      button.removeAttribute('disabled');
    }
  }
}
</script>

<style scoped lang="scss">
.hidden {
  opacity: 0 !important;
  transition: opacity 0.3s ease;
}

.gameArea {
  background-color: var(--color-background);
  height: calc(100%);
  width: 100%;
  position: relative;
  background-size: cover;
  overflow: hidden;
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

.opponentHand {
  height: 13%;
  width: 100%;
  background-repeat: no-repeat;
  background-position: top center;
  background-size: auto 60%;
}

.categories#opponent {
  height: 8%;
  width: 100%;
  background-color: var(--color-brown);
  outline: 8px solid var(--color-brown-light);
}

.categories#own {
  height: 8%;
  width: 100%;
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: var(--color-brown);
  outline: 0.5rem solid var(--color-brown-light);
}

.categoryContainer {
  position: relative;
  height: 100%;
  display: flex;
  justify-content: center;
  flex-direction: column;
  align-items: center;
  width: 15%;
}

.categoryItem {
  margin: 0.1rem;
}

.categoryIcon {
  color: var(--color-background);
}

.circle {
  width: 1rem;
  height: 1rem;
  border-radius: 100%;
  border: 3px solid var(--color-background);
  display: inline-block;
}

.fullCircle {
  background-color: var(--color-background);
}

#activeCards {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 35%;
  width: 100%;
  background-color: var(--color-brown-xlight);
  z-index: 10;
}

.cardPlayed {
  position: relative;
  aspect-ratio: 1904/2564;
  background-size: cover;
  padding: 2%;
  height: 70%;
  z-index: 1;
  transition: 0.3s;
}

.CO2BarContainer {
  position: relative;
  width: 100%;
  height: 6%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding-top: 0.5rem;
}

.CO2BarBackground {
  width: 80%;
  height: 60%;
  background-color: var(--color-brown);
  border-radius: var(--border-radius);
  border: 2px solid var(--color-brown-light);
  p {
    position: absolute;
    font-size: var(--font-size);
    font-weight: var(--font-weight-semibold);
    font-family: var(--font-family);
    color: var(--color-background);
    margin-left: 0.4rem;
  }
}

.CO2Bar {
  height: 100%;
  max-width: 100%;
  background-color: var(--color-evaluating);
  border-radius: var(--border-radius);
  border: 2px solid var(--color-evaluating-light);
  opacity: 100%;
  transition: 0.3s ease;
}

.hand {
  position: absolute;
  height: 30%;
  width: 100%;
  display: flex;
  justify-content: center;
  bottom: 0;
}

.hand-move, /* apply transition to moving elements */
.hand-enter-active,
.hand-leave-active {
  transition: all 0.3s ease;
}

.hand-enter-from,
.hand-leave-to {
  opacity: 0;
  transform: translateY(-8rem);
}

/* ensure leaving items are taken out of layout flow so that moving
   animations can be calculated correctly. */
.hand-leave-active {
  position: absolute;
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

hr {
  background-color: var(--color-brown);
  margin: -2pt 0 4pt 0;
  width: 60%;
}

.cardStats {
  z-index: 10;
  color: var(--color-brown);
  font-size: var(--font-size-small);
  font-weight: var(--font-weight-bold);
  font-family: var(--font-family);

  li {
    margin-top: -2pt;

    span {
      margin-left: 1pt;
      font-weight: var(--font-weight-semibold);
      font-size: var(--font-size-xxsmall);
    }
  }
}

.cardCost {
  font-size: var(--font-size-xlarge);
  font-weight: var(--font-weight-bold);
}

.categoryCardIcon {
  color: var(--color-brown-light);
  position: absolute;
  bottom: 0;
  left: 0;
  margin: 5%;
}

.cardImage {
  z-index: 0;
  position: absolute;
  width: 70%;
  bottom: 0;
  right: 0;
  margin: 2.7%;
}

#cardSelectButton {
  border-radius: var(--border-radius-small);
  border: 2px solid var(--color-dark-contrast);
  background-color: var(--color-background);
  font-weight: var(--font-weight-semibold);
  position: absolute;
  bottom: 0.7rem;
  width: 6rem;
  height: 1.5rem;
}
</style>
