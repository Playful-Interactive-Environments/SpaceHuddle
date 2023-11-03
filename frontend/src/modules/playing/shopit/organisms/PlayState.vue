<template>
  <div
    class="gameArea"
    :style="{ height: height }"
    v-if="playStateType === PlayStateType.play"
  >
    <div
      class="opponentHand"
      :style="{
        backgroundImage: 'url(' + gameConfig.gameValues.opponentHand + ')',
      }"
    ></div>
    <div class="CO2BarContainer" id="barOpponent">
      <div class="CO2BarBackground">
        <p>CO²: {{ pointsSpentOpponent }}/{{ maxCost }}</p>
        <div
          class="CO2Bar"
          :style="{ width: (pointsSpentOpponent / maxCost) * 100 + '%' }"
          :class="{ hidden: pointsSpentOpponent / maxCost <= 0 }"
        ></div>
      </div>
    </div>
    <div class="categories" id="opponent">
      <div
        v-for="cat in categoryPointsOpponent"
        :key="cat[0]"
        :id="cat[0]"
        class="categoryContainer"
      >
        <font-awesome-icon
          :icon="gameConfig.categories[cat[0]].settings.icon"
          class="categoryIcon categoryItem"
        />
        <span
          class="circle categoryItem"
          :class="{ fullCircle: cat[1] >= 2 }"
        ></span>
        <span
          class="circle categoryItem"
          :class="{ fullCircle: cat[1] >= 1 }"
        ></span>
      </div>
    </div>
    <TransitionGroup
      name="activeCards"
      class="activeCards"
      tag="div"
      id="activeCards"
    >
      <p v-if="cardsPlayed.length === 0 && player === playersTurn" class="waiting">waiting for your turn...</p>
      <p v-if="cardsPlayed.length === 0 && player !== playersTurn" class="waiting">waiting for opponent...</p>
      <div
        v-for="card in cardsPlayed"
        :key="card[7]"
        :id="card[7]"
        class="cardPlayed"
        :style="{
          backgroundImage: 'url(' + gameConfig.gameValues.cardBackground + ')',
        }"
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
    </TransitionGroup>
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
    <div class="CO2BarContainer" id="barOwn">
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
        :style="{
          backgroundImage: 'url(' + gameConfig.gameValues.cardBackground + ')',
        }"
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
    <p>
      {{ game.keywords }}<span v-if="player === playersTurn">Your Turn</span>
    </p>
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
import {Options, Vue} from 'vue-class-component';
import {Prop} from 'vue-property-decorator';
import {ObjectSpace} from '@/types/enum/ObjectSpace';
import {until} from '@/utils/wait';
import * as tutorialService from '@/services/tutorial-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import {Tutorial} from '@/types/api/Tutorial';
import * as cashService from '@/services/cash-service';
import * as themeColors from '@/utils/themeColors';
import gameConfig from '@/modules/playing/shopit/data/gameConfig.json';
import {Idea} from '@/types/api/Idea';
import * as ideaService from '@/services/idea-service';

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
  },
  components: {},
  emits: ['playFinished'],
})
export default class PlayState extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: null }) readonly game!: Idea;
  @Prop({ default: '100%' }) readonly height!: string;
  @Prop({ default: 0 }) readonly player!: number;
  @Prop({ default: EndpointAuthorisationType.PARTICIPANT })
  authHeaderTyp!: EndpointAuthorisationType;

  gameWidth = 0;
  gameHeight = 0;
  showToolbox = false;
  tutorialSteps: Tutorial[] = [];
  levelType = '';
  gameConfig = gameConfig;

  startTime = Date.now();

  playStateType = PlayStateType.play;
  PlayStateType = PlayStateType;

  cardSpriteFolder = gameConfig.gameValues.spriteFolder;

  setupDone = false;

  activeCard: any[] = [];
  cardsPlayed: any[] = [];
  ownCardPlayed = '';

  cards: any[] = [];
  cardHand: any[] = [];
  cardHandOpponent: any[] = [];
  opponentCard = this.cards[9];

  categoryPointsOpponent: any[] = [];
  categoryPoints: any[] = [];

  maxCards = 3;
  maxCost = 125;
  pointsSpent = 0;
  pointsSpentOpponent = 0;
  reason = '';

  playersTurn = 0;
  imageArray: any[] = [];

  initialButtonState = false;

  getCardsFromGame() {
    return this.game.parameter.cards;
  }

  preloadAllSprites(cards) {
    for (let i = 0; i < cards.length; i++) {
      this.preloadImage(this.cardSpriteFolder + cards[i][7] + '.png');
    }
  }

  preloadImage(url) {
    const img = new Image();
    img.src = url;
    this.imageArray.push(img);
  }

  clearPlayState(): void {
    this.playStateType = PlayStateType.play;
    this.PlayStateType = PlayStateType;

    this.cardSpriteFolder = gameConfig.gameValues.spriteFolder;

    this.setupDone = false;

    this.activeCard = [];
    this.cardsPlayed = [];
    this.ownCardPlayed = '';

    this.cards = [];
    this.cardHand = [];
    this.cardHandOpponent = [];
    this.opponentCard = [];

    this.categoryPointsOpponent = [];
    this.categoryPoints = [];

    this.maxCards = 3;
    this.maxCost = 280;
    this.pointsSpent = 0;
    this.pointsSpentOpponent = 0;
    this.reason = '';

    this.playersTurn = 0;
    this.imageArray = [];

    this.initialButtonState = false;
  }

  playStateChange(outcome, reason) {
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

  async setup() {
    const button = document.getElementById('cardSelectButton');
    if (button) {
      button.setAttribute('disabled', '');
    }
    this.cards = this.game.parameter.cards;
    this.playersTurn = this.game.parameter.playersTurn;
    this.categorySetup();
    this.initialCardPull();
    this.preloadAllSprites(this.cards);
  }

  mounted(): void {
    this.updateGame();
    tutorialService.registerGetList(this.updateTutorial, this.authHeaderTyp);
    this.setup();
  }

  async updateGame() {
    ideaService.registerGetIdea(
      this.game.id,
      this.updatingGame,
      EndpointAuthorisationType.PARTICIPANT,
      2
    );
  }

  updatingGame(game: Idea): void {
    switch (this.player) {
      case 1:
        this.cardHand = game.parameter.player1Hand.map((x) => x);
        this.cardHandOpponent = game.parameter.player2Hand.map((x) => x);
        break;
      case 2:
        this.cardHand = game.parameter.player2Hand.map((x) => x);
        this.cardHandOpponent = game.parameter.player1Hand.map((x) => x);
        break;
    }
    this.game.parameter = game.parameter;
    this.cards = game.parameter.cards.map((x) => x);
    this.cardsPlayed = game.parameter.cardsPlayed.map((x) => x);
    console.log(this.cards.length + ' ');
    if (this.game.parameter.playerNum === 2 && !this.initialButtonState) {
      const button = document.getElementById('cardSelectButton');
      if (button) {
        button.removeAttribute('disabled');
        this.initialButtonState = true;
      }
    }
  }

  updateTutorial(steps: Tutorial[]): void {
    this.tutorialSteps = steps.filter((step) => step.type === tutorialType);
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTutorial);
    cashService.deregisterAllGet(this.updatingGame);
  }

  unmounted(): void {
    this.deregisterAll();
    this.clearPlayState();
    this.game.parameter.playerNum -= 1;
    ideaService.putIdea(this.game, EndpointAuthorisationType.PARTICIPANT);
  }

  categorySetup() {
    const data = this.gameConfig.categories;
    for (const category in data) {
      this.categoryPoints.push([category, 0]);
      this.categoryPointsOpponent.push([category, 0]);
    }
  }

  //parse the json gameconfig and convert it to card items
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

  reShuffle() {
    if (this.player === 1) {
      this.cards = this.shuffle(this.parseCards(gameConfig));
      this.maxCards = 3;
      this.updateCards();
    }
    this.initialCardPull();
  }

  initialCardPull() {
    for (let i = 0; i < this.maxCards; i++) {
      if (this.player === 1) {
        const card = this.cards.pop();
        this.cardHand.unshift(card);
      } else {
        const card = this.cards.shift();
        this.cardHand.unshift(card);
      }
    }
    this.updateCards();
  }

  categoryIconChanged: any[] = [];
  async cardPlayed(card) {
    //clear active card to avoid replaying already played card
    this.activeCard = [];
    const activeCards = document.getElementsByClassName('cardContainerActive');
    if (activeCards[0]) {
      activeCards[0].classList.remove('cardContainerActive');
    }
    let continuePlay = true;
    if (this.playersTurn !== this.player) {
      //Checking category for category zugzwang (if you have the category an opponent played, you HAVE to play that card)
      //Only relevant if not playing first
      if (this.cardsPlayed[0]) {
        this.opponentCard = this.cardsPlayed[0];
        const card2 = this.cardsPlayed[0];
        const boolArray: boolean[] = this.checkAllCardCategories(card2);
        if (boolArray.every((en) => !en)) {
          continuePlay = true;
        } else {
          continuePlay = this.checkCategories(card, card2);
          //Highlighting card categories that do not fit (in case one has a playable card)
          if (!continuePlay) {
            const element = document.getElementsByClassName('cardContainer');
            for (let i = 0; i < this.cardHand.length; i++) {
              if (!boolArray[i] && element[i]) {
                const cat = element[i].querySelectorAll('.categoryCardIcon');
                if (cat[0]) {
                  this.categoryIconChanged.push(cat[0]);
                  cat[0].classList.add('categoryCardIconHighlighted');
                }
              }
            }
          }
        }
      } else {
        continuePlay = false;
      }
    }
    //If everything goes right: continue the play
    if (continuePlay && card[0] > 0) {
      const button = document.getElementById('cardSelectButton');
      //deactivate the button to prevent more cards being played
      if (button) {
        button.setAttribute('disabled', '');
      }
      //remove from hand and add to play
      for (let i = 0; i < this.cardHand.length; i++) {
        if (card[7] === this.cardHand[i][7]) {
          this.cardHand.splice(i, 1);
        }
      }
      /*const index = this.cardHand.indexOf(card);
      this.cardHand.splice(index, 1);*/
      this.cardsPlayed.push(card);
      this.ownCardPlayed = card[7];

      await this.updateCards();

      //remove wrong category icon highlight
      for (let i = 0; i < this.categoryIconChanged.length; i++) {
        this.categoryIconChanged[i].classList.remove(
          'categoryCardIconHighlighted'
        );
      }

      //Wait for opponent card
      if (this.playersTurn === this.player) {
        await until(() => this.cardsPlayed.length === 2);
        this.opponentCard = this.cardsPlayed[1];
      }

      //Compare the cards and choose a winner
      if (this.playersTurn === this.player) {
        this.compareCards(card, this.opponentCard, this.playersTurn);
        console.log('done 1');
      } else {
        this.compareCards(this.opponentCard, card, this.playersTurn);
        console.log('done 2');
      }

      setTimeout(async () => {
        console.log("Card length: " + this.cards.length);
        this.cardsPlayed = [];
        if (this.cards.length > 1) {
          this.drawNewCard();
          await until(
            () =>
              this.game.parameter.player1Hand.length > this.maxCards - 1 &&
              this.game.parameter.player2Hand.length > this.maxCards - 1 &&
              this.debug("waiting for cards")
          );
        } else {
          this.maxCards -= 1;
        }
        console.log("Hand Counts: " + this.game.parameter.player1Hand.length + ", " + this.game.parameter.player1Hand.length + ", Card length: " + this.cards.length);
        if (
          this.game.parameter.player1Hand.length === 0 &&
          this.game.parameter.player2Hand.length === 0 &&
          this.cards.length <= 1
        ) {
          console.log("Reshuffle");
          this.reShuffle();
        }

        this.playersTurn = this.playersTurn === 1 ? 2 : 1;
        if (button) {
          button.removeAttribute('disabled');
        }
      }, 1000);
    }
  }
  debug(text) {
    console.log(text);
    return true;
  }

  /*async drawNewCard() {
    if (this.playersTurn === this.player) {
      if (this.cards.length > 0 && this.cardHand.length < this.maxCards) {
        const card = this.cards.pop();
        this.cardHand.unshift(card);
        this.updateCards();
        console.log("Player: " + this.player + " is drawing new cards");
        return true;
      } else {
        return false;
      }
    } else {
      await until(
        () =>
          this.game.parameter.player1Hand.length > this.maxCards - 1 ||
          this.game.parameter.player2Hand.length > this.maxCards - 1
      );
      if (this.cards.length > 0 && this.cardHand.length < this.maxCards) {
        const card = this.cards.shift();
        this.cardHand.unshift(card);
        this.updateCards();
        console.log("Player: " + this.player + " is drawing new cards");
        return true;
      } else {
        return false;
      }
    }
  }*/

  drawNewCard() {
    if (this.player === this.playersTurn && this.cards.length > 0) {
      let card = this.cards.pop();
      this.cardHand.unshift(card);
      card = this.cards.pop();
      if (this.playersTurn === 1) {
        this.game.parameter.player2Hand.unshift(card);
      } else {
        this.game.parameter.player1Hand.unshift(card);
      }
      return this.updateCards();
    }
  }

  updateCards() {
    switch (this.player) {
      case 1:
        this.game.parameter.player1Hand = this.cardHand.map((x) => x);
        break;
      case 2:
        this.game.parameter.player2Hand = this.cardHand.map((x) => x);
        break;
    }
    this.game.parameter.cardsPlayed = this.cardsPlayed.map((x) => x);
    this.game.parameter.cards = this.cards.map((x) => x);
    ideaService.putIdea(this.game, EndpointAuthorisationType.PARTICIPANT);
  }

  checkCategories(card, card2) {
    return card[6] === card2[6];
  }

  checkAllCardCategories(card2) {
    const boolArray: boolean[] = [];
    for (let i = 0; i < this.cardHand.length; i++) {
      if (this.cardHand[i][6] == card2[6]) {
        boolArray.push(true);
      } else {
        boolArray.push(false);
      }
    }
    return boolArray;
  }

  activeCardChanged(card) {
    if (this.cardHand.length > this.maxCards - 1) {
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
  }

  compareCards(card, card2, playedFirst) {
    //card = Card that was there first. Wins in case of category mismatch
    //Compares the cost + category of the cards and decides the winner
    let winningCard;
    if (card && card2) {
      if (card[6] === card2[6]) {
        //If the categories fit
        if (card[0] > card2[0]) {
          if (this.playersTurn === this.player) {
            this.pointsSpent += card[0] + card2[0];
            winningCard = card;
          } else {
            this.pointsSpentOpponent += card[0] + card2[0];
            winningCard = card;
          }
        } else {
          if (this.playersTurn === this.player) {
            this.pointsSpentOpponent += card[0] + card2[0];
            winningCard = card2;
          } else {
            this.pointsSpent += card[0] + card2[0];
            winningCard = card2;
          }
        }
      } else {
        //If the categories do not fit
        winningCard = card;
        if (this.playersTurn === this.player) {
          this.pointsSpent += winningCard[0];
        } else {
          this.pointsSpentOpponent += winningCard[0];
        }
      }
    }
    //if too many points spent either win or lose
    if (this.pointsSpent >= this.maxCost) {
      this.playStateChange('lost', 'points');
    }
    if (this.pointsSpentOpponent >= this.maxCost) {
      this.playStateChange('win', 'points');
    }

    //Check if winning card is the own or the opponents
    //Adds points to winners category
    for (let i = 0; i < this.categoryPoints.length; i++) {
      if (this.categoryPoints[i][0] == winningCard[6]) {
        if (this.ownCardPlayed === winningCard[7]) {
          this.categoryPoints[i][1] += 1;
        } else {
          this.categoryPointsOpponent[i][1] += 1;
        }
      }
    }

    //Check the points, if all categories filled give win or lose condition
    if (this.categoryPoints.every((row) => row[1] >= 2)) {
      this.playStateChange('win', 'category');
    }
    if (this.categoryPointsOpponent.every((row) => row[1] >= 2)) {
      this.playStateChange('lost', 'category');
    }

    this.cardWin(winningCard);

    //draw new cards
    //this.drawNewCard();
    //reactivate Button
    return winningCard;
  }

  cardWin(winningCard) {
    const element = document.getElementById(winningCard[7]);
    if (element) {
      element.classList.add('cardWin');
    }
    return true;
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
  height: 7%;
  width: 100%;
  background-repeat: no-repeat;
  background-position: top center;
  background-size: auto 100%;
}

.categories {
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
  height: 32%;
  width: 100%;
  background-color: var(--color-brown-xlight);
  z-index: 10;
}

.cardPlayed {
  position: relative;
  aspect-ratio: 1904/2564;
  background-size: cover;
  padding: 2%;
  height: 78%;
  z-index: 1;
  transition: 0.3s;
  margin: 1rem;
}

.cardWin {
  z-index: 100;
  transform: scale(120%);
  transition: 0.5s;
}

.CO2BarContainer {
  position: relative;
  width: 100%;
  height: 6%;
  display: flex;
  align-items: center;
  justify-content: center;
}

#barOwn {
  padding-top: 0.5rem;
}

#barOpponent {
  padding-bottom: 0.5rem;
}

.CO2BarBackground {
  position: relative;
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
    z-index: 5;
  }
}

.CO2Bar {
  position: absolute;
  height: 100%;
  background-color: var(--color-evaluating);
  border-radius: var(--border-radius);
  border: 2px solid var(--color-evaluating-light);
  opacity: 100%;
  transition: 0.3s ease;
  z-index: 2;
}

#barOwn {
  max-width: 100%;
}

#barOpponent {
  max-width: 100%;
}

.hand {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  height: 30%;
  width: 100%;
}

.hand-move,
.activeCards-move,
.hand-enter-active,
.hand-leave-active {
  transition: all 0.3s ease;
}

.hand-enter-from,
.hand-leave-to {
  opacity: 0;
  transform: translateY(-8rem);
}

.activeCards-enter-from,
.activeCards-leave-to {
  opacity: 0;
}

.activeCards-leave-active,
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
  position: relative;
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
  transition: 0.3s;
}

.categoryCardIconHighlighted {
  color: var(--color-evaluating);
  transform: scale(200%);
  bottom: 0.5rem;
  left: 0.5rem;
  animation: shake 0.3s;
}

@keyframes shake {
  0% {
    transform: translateX(0) scale(100%);
  }
  25% {
    transform: translateX(5px) scale(125%);
  }
  50% {
    transform: translateX(-5px) scale(150%);
  }
  75% {
    transform: translateX(5px) scale(175%);
  }
  100% {
    transform: translateX(0) scale(200%);
  }
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

.waiting {
  color: var(--color-background);

}
</style>
