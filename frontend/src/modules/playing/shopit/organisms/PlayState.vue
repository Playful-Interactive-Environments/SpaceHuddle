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
        <p class="CO2BarText">CO²: {{ pointsSpentOpponent }}/{{ maxCost }}</p>
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
      key="activeCard"
    >
      <div class="waitingTexts" key="activeCard">
        <p
          v-show="
            cardsPlayed.length === 0 &&
            player === playersTurn &&
            game.parameter.playerNum > 1
          "
          class="waiting yourTurn"
        >
          {{ $t('module.playing.shopit.participant.waiting.yourTurn') }}
        </p>
        <p
          v-show="
            (cardsPlayed.length === 0 && player !== playersTurn) ||
            (!initialButtonState && game.parameter.playerNum <= 1)
          "
          class="waiting"
        >
          {{ $t('module.playing.shopit.participant.waiting.opponent') }}
        </p>
        <p
          v-show="!initialButtonState && game.parameter.playerNum <= 1"
          class="waiting"
        >
          {{
            $t('module.playing.shopit.participant.waiting.joinID') +
            game.keywords
          }}
        </p>
      </div>
      <div
        v-for="card in cardsPlayed"
        :key="card.name"
        :id="card.name"
        class="cardPlayed"
        :style="{
          backgroundImage: 'url(' + gameConfig.gameValues.cardBackground + ')',
        }"
      >
        <Card
          :cost="card.cost"
          :CO2="card.CO2"
          :energy="card.energy"
          :lifetime="card.lifetime"
          :water="card.water"
          :money="card.money"
          :category="card.category"
          :condition="card.condition"
          :cardName="card.name"
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
        <p class="CO2BarText">CO²: {{ pointsSpent }}/{{ maxCost }}</p>
        <div
          class="CO2Bar"
          :style="{ width: (pointsSpent / maxCost) * 100 + '%' }"
          :class="{ hidden: pointsSpent / maxCost <= 0 }"
        ></div>
      </div>
    </div>
    <TransitionGroup name="hand" class="hand" tag="div" key="hand">
      <div
        v-for="card in cardHand"
        :key="card.name"
        :id="card.name"
        class="cardContainer"
        :style="{
          backgroundImage: 'url(' + gameConfig.gameValues.cardBackground + ')',
        }"
        @click="activeCardChanged(card, card.name)"
      >
        <button
          v-if="card === activeCard && !buttonDisabled"
          id="cardSelectButton"
          @click="cardPlayed(activeCard)"
        >
          {{ $t('module.playing.shopit.participant.cardPlayButton') }}
        </button>
        <Card
          :cost="card.cost"
          :CO2="card.CO2"
          :energy="card.energy"
          :lifetime="card.lifetime"
          :water="card.water"
          :money="card.money"
          :category="card.category"
          :condition="card.condition"
          :cardName="card.name"
        />
      </div>
    </TransitionGroup>
  </div>
  <div class="resultArea" :style="{ height: height }">
    <div class="result" v-if="playStateType === PlayStateType.win">
      <h2 class="heading heading--medium">
        {{ $t('module.playing.shopit.participant.win') }}
      </h2>
      <p v-if="reason === 'category'">
        {{ $t('module.playing.shopit.participant.winCategories') }}
      </p>
      <p v-if="reason === 'points'">
        {{ $t('module.playing.shopit.participant.winPoints') }}
      </p>
      <p class="marginTop">
        {{ $t('module.playing.shopit.participant.endCards') }}
      </p>
      <div class="endCards">
        <div
          v-for="card in endCardsOverview"
          :key="card.infoKey"
          :id="card.infoKey"
          class="endCard"
          :style="{
            backgroundImage:
              'url(' + gameConfig.gameValues.cardBackground + ')',
          }"
          @click="activeCardChanged(card, card.infoKey, true)"
        >
          <Card
            :cost="card.cost"
            :CO2="card.CO2"
            :energy="card.energy"
            :lifetime="card.lifetime"
            :water="card.water"
            :money="card.money"
            :category="card.category"
            :condition="card.condition"
            :cardName="card.name"
          />
          <p class="CardDescription">{{ card.infoKey }}</p>
        </div>
      </div>
      <div class="infoText">
        <!--      <p class="marginTop" v-show="this.activeCardId !== ''">
        {{
          $t(
              'module.playing.shopit.participant.endCardTexts.' +
              this.activeCard.category
          )
        }}
      </p>-->
        <p class="marginTop">
          {{
            $t(
              'module.playing.shopit.participant.' +
                this.activeCard.category +
                'EndCardTexts.header'
            )
          }}
        </p>
        <ul class="marginTop">
          <li v-for="text in getTips(this.activeCard.category)" :key="text">
            {{ text }}
          </li>
        </ul>
        <p class="marginTop">
          {{
            $t(
              'module.playing.shopit.participant.' +
                this.activeCard.category +
                'EndCardTexts.description'
            )
          }}
        </p>
        <p class="marginTop">
          {{
            $t(
              'module.playing.shopit.participant.' +
                this.activeCard.category +
                'EndCardTexts.shop-it-tip'
            )
          }}
        </p>
      </div>
      <el-button class="el-button--submit returnButton" @click="finished">
        {{ $t('module.playing.shopit.participant.returnToMenu') }}
      </el-button>
    </div>
    <div class="result" v-if="playStateType === PlayStateType.lost">
      <h2 class="heading heading--medium">
        {{ $t('module.playing.shopit.participant.lost') }}
      </h2>
      <p v-if="reason === 'category'">
        {{ $t('module.playing.shopit.participant.lostCategories') }}
      </p>
      <p v-if="reason === 'points'">
        {{ $t('module.playing.shopit.participant.lostPoints') }}
      </p>
      <p class="marginTop">
        {{ $t('module.playing.shopit.participant.endCards') }}
      </p>
      <div class="endCards">
        <div
          v-for="card in endCardsOverview"
          :key="card.infoKey"
          :id="card.infoKey"
          class="endCard"
          :style="{
            backgroundImage:
              'url(' + gameConfig.gameValues.cardBackground + ')',
          }"
          @click="activeCardChanged(card, card.infoKey, true)"
        >
          <Card
            :cost="card.cost"
            :CO2="card.CO2"
            :energy="card.energy"
            :lifetime="card.lifetime"
            :water="card.water"
            :money="card.money"
            :category="card.category"
            :condition="card.condition"
            :cardName="card.name"
          />
          <p class="CardDescription">{{ card.infoKey }}</p>
        </div>
      </div>
      <div class="infoText">
        <!--      <p class="marginTop" v-show="this.activeCardId !== ''">
        {{
          $t(
              'module.playing.shopit.participant.endCardTexts.' +
              this.activeCard.category
          )
        }}
      </p>-->
        <p class="marginTop">
          {{
            $t(
              'module.playing.shopit.participant.' +
                this.activeCard.category +
                'EndCardTexts.header'
            )
          }}
        </p>
        <ul class="marginTop">
          <li v-for="text in getTips(this.activeCard.category)" :key="text">
            {{ text }}
          </li>
        </ul>
        <p class="marginTop">
          {{
            $t(
              'module.playing.shopit.participant.' +
                this.activeCard.category +
                'EndCardTexts.description'
            )
          }}
        </p>
        <p class="marginTop">
          {{
            $t(
              'module.playing.shopit.participant.' +
                this.activeCard.category +
                'EndCardTexts.shop-it-tip'
            )
          }}
        </p>
      </div>
      <el-button class="el-button--submit returnButton" @click="finished">
        {{ $t('module.playing.shopit.participant.returnToMenu') }}
      </el-button>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { ObjectSpaceType } from '@/types/enum/ObjectSpaceType';
import { until } from '@/utils/wait';
import * as tutorialService from '@/services/tutorial-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Tutorial } from '@/types/api/Tutorial';
import * as cashService from '@/services/cash-service';
import * as themeColors from '@/utils/themeColors';
import gameConfig from '@/modules/playing/shopit/data/gameConfig.json';
import { Idea } from '@/types/api/Idea';
import * as ideaService from '@/services/idea-service';
import Card from '@/modules/playing/shopit/organisms/Card.vue';

/* eslint-disable @typescript-eslint/no-explicit-any*/
const tutorialType = 'shop-it-object';

enum PlayStateType {
  play,
  win,
  lost,
}

@Options({
  computed: {
    ObjectSpace() {
      return ObjectSpaceType;
    },
  },
  components: {
    Card,
  },
  emits: ['playFinished', 'PlayerLeft'],
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
  activeCardId = '';
  cardsPlayed: any[] = [];
  ownCardPlayed = '';
  ownCardsPlayed: any[] = [];

  endCardsOverview: any[] = [];

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

  updateCardsPlayed = true;
  update = true;
  buttonDisabled = false;

  initialButtonState = false;

  win = false;
  gameEnded = false;

  getTips(category = '') {
    const tipsArray: string[] = [];
    for (let i = 1; i <= 3; i++) {
      tipsArray.push(
        this.$t(
          'module.playing.shopit.participant.' +
            category +
            'EndCardTexts.tips.' +
            i
        )
      );
    }
    return tipsArray;
  }

  getCardsFromGame() {
    return this.game.parameter.cards;
  }

  preloadAllSprites(cards) {
    for (let i = 0; i < cards.length; i++) {
      this.preloadImage(this.cardSpriteFolder + cards[i].name + '.png');
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
    this.maxCost = 125;
    this.pointsSpent = 0;
    this.pointsSpentOpponent = 0;
    this.reason = '';

    this.playersTurn = 0;
    this.imageArray = [];

    this.initialButtonState = false;

    this.win = false;
    this.gameEnded = false;
  }

  playStateChange(outcome, reason) {
    switch (outcome) {
      case 'lost':
        this.win = false;
        this.gameEnded = true;
        this.endCardsOverview = this.calculateMostExpensiveCards();
        this.buttonDisabled = false;
        this.playStateType = PlayStateType.lost;
        break;
      case 'win':
        this.win = true;
        this.gameEnded = true;
        this.endCardsOverview = this.calculateMostExpensiveCards();
        this.buttonDisabled = false;
        this.playStateType = PlayStateType.win;
        break;
    }
    this.reason = reason;
    this.activeCardChanged(
      this.endCardsOverview[0],
      this.endCardsOverview[0].infoKey
    );
  }

  calculateMostExpensiveCards() {
    const cards: any = [];

    const clothing = this.ownCardsPlayed.filter(
      (card) => card.category === 'clothing'
    );
    const electronics = this.ownCardsPlayed.filter(
      (card) => card.category === 'electronics'
    );
    const food = this.ownCardsPlayed.filter((card) => card.category === 'food');

    let clothingItem: any = [];
    let electronicsItem: any = [];
    let foodItem: any = [];
    clothingItem.cost = 0;
    electronicsItem.cost = 0;
    foodItem.cost = 0;

    for (const card of clothing) {
      if (clothingItem.cost < card.cost) {
        clothingItem = card;
      }
    }
    for (const card of electronics) {
      if (electronicsItem.cost < card.cost) {
        electronicsItem = card;
      }
    }
    for (const card of food) {
      if (foodItem.cost < card.cost) {
        foodItem = card;
      }
    }

    if (clothingItem) {
      clothingItem.infoKey = this.$t(
        'module.playing.shopit.participant.cardCategories.clothing'
      );
    }

    if (electronicsItem) {
      electronicsItem.infoKey = this.$t(
        'module.playing.shopit.participant.cardCategories.electronics'
      );
    }

    if (foodItem) {
      foodItem.infoKey = this.$t(
        'module.playing.shopit.participant.cardCategories.food'
      );
    }

    cards.push(clothingItem);
    cards.push(electronicsItem);
    cards.push(foodItem);

    return cards;
  }

  get backgroundColor(): string {
    return themeColors.getBackgroundColor();
  }

  async setup() {
    this.buttonDisabled = true;
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
    if (this.update) {
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
      if (this.cards.length >= game.parameter.cards.length) {
        this.cards = game.parameter.cards.map((x) => x);
        if (this.updateCardsPlayed) {
          this.cardsPlayed = game.parameter.cardsPlayed.map((x) => x);
        }
      }
      if (this.game.parameter.playerNum === 2 && !this.initialButtonState) {
        this.buttonDisabled = false;
        this.initialButtonState = true;
      }
      if (
        this.player === 2 &&
        this.game.parameter.playerNum <= 1 &&
        this.initialButtonState &&
        !this.gameEnded
      ) {
        this.deregisterAll();
        this.clearPlayState();
        this.game.parameter.playerNum -= 1;
        ideaService.putIdea(this.game, EndpointAuthorisationType.PARTICIPANT);
        this.$emit('playerLeft');
      } else if (
        this.player === 1 &&
        this.game.parameter.playerNum <= 1 &&
        this.initialButtonState &&
        !this.gameEnded
      ) {
        this.$emit('playerLeft');
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

  async unmounted() {
    this.game.parameter.playerNum -= 1;
    await ideaService.putIdea(this.game, EndpointAuthorisationType.PARTICIPANT);
    if (this.player === 1) {
      await until(() => this.game.parameter.playerNum <= 0);
      await ideaService.deleteIdea(
        this.game.id,
        EndpointAuthorisationType.PARTICIPANT,
        false
      );
      this.deregisterAll();
      this.finished();
    } else {
      this.deregisterAll();
      this.finished();
    }
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
        const card = {
          cost: itemValues[0],
          CO2: itemValues[1],
          energy: itemValues[2],
          lifetime: itemValues[3],
          water: itemValues[4],
          money: itemValues[5],
          category: itemValues[6],
          condition: itemValues[7],
          name: itemKey,
          infoKey: '',
        };
        cardArray.push(card);
      }
    }
    return cardArray;
  }

  getCardSprite(card) {
    return this.cardSpriteFolder + card.name + '.png';
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

  async reShuffle() {
    if (this.player === 1) {
      this.cards = this.shuffle(this.parseCards(gameConfig));
      this.maxCards = 3;
      this.game.parameter.cards = this.cards.map((x) => x);
      this.updateCards();
      await until(() => this.game.parameter.player2Hand.length > 2);
      this.initialCardPull();
    } else {
      this.maxCards = 3;
      await until(() => this.game.parameter.cards.length >= 2);
      this.cards = this.game.parameter.cards.map((x) => x);
      this.initialCardPull();
    }
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
    this.activeCardId = '';
    const activeCards = document.getElementsByClassName('cardContainerActive');
    if (activeCards[0]) {
      activeCards[0].classList.remove('cardContainerActive');
    }
    let continuePlay = true;
    if (this.playersTurn !== this.player) {
      //Checking category for category zugzwang (if you have the category an opponent played, you HAVE to play that card)
      //Only relevant if not playing first
      if (this.cardsPlayed[0]) {
        this.opponentCard = JSON.parse(JSON.stringify(this.cardsPlayed[0]));
        const card2 = JSON.parse(JSON.stringify(this.cardsPlayed[0]));
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
    if (continuePlay && card.cost > 0) {
      this.buttonDisabled = true;
      //remove from hand and add to play
      for (let i = 0; i < this.cardHand.length; i++) {
        if (card.name === this.cardHand[i].name) {
          this.cardHand.splice(i, 1);
        }
      }
      /*const index = this.cardHand.indexOf(card);
      this.cardHand.splice(index, 1);*/
      this.cardsPlayed.push(card);
      const copyCard = JSON.parse(JSON.stringify(card));
      this.ownCardPlayed = copyCard.name;
      this.ownCardsPlayed.push(copyCard);

      await this.updateCards();

      //remove wrong category icon highlight
      for (let i = 0; i < this.categoryIconChanged.length; i++) {
        this.categoryIconChanged[i].classList.remove(
          'categoryCardIconHighlighted'
        );
      }
      //Wait for opponent card
      await until(() => this.cardsPlayed.length === 2);
      if (this.playersTurn === this.player) {
        this.opponentCard = JSON.parse(JSON.stringify(this.cardsPlayed[1]));
      }
      this.updateCardsPlayed = false;

      //Compare the cards and choose a winner
      if (this.playersTurn === this.player) {
        this.compareCards(card, this.opponentCard);
      } else {
        this.compareCards(this.opponentCard, card);
      }

      //draw new cards and continue the play
      setTimeout(async () => {
        this.checkWinConditions();
        this.cardsPlayed = [];
        this.game.parameter.cardsPlayed = [];
        if (this.cards.length <= 1) {
          this.cards = this.cards.concat(
            this.shuffle(this.parseCards(gameConfig))
          );
        }
        this.drawNewCard();
        await until(
          () =>
            this.game.parameter.player1Hand.length > this.maxCards - 1 &&
            this.game.parameter.player2Hand.length > this.maxCards - 1
        );

        this.updateCardsPlayed = true;
        this.playersTurn = this.playersTurn === 1 ? 2 : 1;
        this.buttonDisabled = false;
      }, 1000);
    }
  }

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
    this.update = false;
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
    this.update = true;
  }

  checkCategories(card, card2) {
    return card.category === card2.category;
  }

  checkAllCardCategories(card2) {
    const boolArray: boolean[] = [];
    for (let i = 0; i < this.cardHand.length; i++) {
      if (this.cardHand[i].category == card2.category) {
        boolArray.push(true);
      } else {
        boolArray.push(false);
      }
    }
    return boolArray;
  }

  activeCardChanged(card, id, scroll = false) {
    if (!this.buttonDisabled) {
      let element = document.getElementById(this.activeCardId);
      if (element) {
        element.classList.remove('cardContainerActive');
      }
      this.activeCard = card;
      this.activeCardId = id;
      element = document.getElementById(id);
      if (element) {
        if (!element.classList.contains('cardPlayed')) {
          element.classList.add('cardContainerActive');
        }
        if (scroll) {
          element.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
          });
        }
      }
    }
  }

  compareCards(card, card2) {
    //card = Card that was there first. Wins in case of category mismatch
    //Compares the cost + category of the cards and decides the winner
    let winningCard;
    if (card && card2) {
      if (card.category === card2.category) {
        //If the categories fit
        if (card.cost > card2.cost) {
          if (this.playersTurn === this.player) {
            this.pointsSpent += card.cost + card2.cost;
            winningCard = card;
          } else {
            this.pointsSpentOpponent += card.cost + card2.cost;
            winningCard = card;
          }
        } else {
          if (this.playersTurn === this.player) {
            this.pointsSpentOpponent += card.cost + card2.cost;
            winningCard = card2;
          } else {
            this.pointsSpent += card.cost + card2.cost;
            winningCard = card2;
          }
        }
      } else {
        //If the categories do not fit
        winningCard = card;
        if (this.playersTurn === this.player) {
          this.pointsSpent += winningCard.cost;
        } else {
          this.pointsSpentOpponent += winningCard.cost;
        }
      }
    }
    //if too many points spent either win or lose
    /*if (
      this.pointsSpent >= this.maxCost &&
      this.playStateType === PlayStateType.play
    ) {
      this.playStateChange('lost', 'points');
    }
    if (
      this.pointsSpentOpponent >= this.maxCost &&
      this.playStateType === PlayStateType.play
    ) {
      this.playStateChange('win', 'points');
    }*/

    //Check if winning card is the own or the opponents
    //Adds points to winners category
    for (let i = 0; i < this.categoryPoints.length; i++) {
      if (this.categoryPoints[i][0] == winningCard.category) {
        if (this.ownCardPlayed === winningCard.name) {
          this.categoryPoints[i][1] += 1;
        } else {
          this.categoryPointsOpponent[i][1] += 1;
        }
      }
    }

    //Check the points, if all categories filled give win or lose condition
    /*if (
      this.categoryPoints.every((row) => row[1] >= 2) &&
      this.playStateType === PlayStateType.play
    ) {
      this.playStateChange('win', 'category');
    }
    if (
      this.categoryPointsOpponent.every((row) => row[1] >= 2) &&
      this.playStateType === PlayStateType.play
    ) {
      this.playStateChange('lost', 'category');
    }*/
    this.cardWin(winningCard);

    //reactivate Button
    return winningCard;
  }

  checkWinConditions() {
    if (
      this.pointsSpent >= this.maxCost &&
      this.playStateType === PlayStateType.play
    ) {
      this.playStateChange('lost', 'points');
    } else if (
      this.pointsSpentOpponent >= this.maxCost &&
      this.playStateType === PlayStateType.play
    ) {
      this.playStateChange('win', 'points');
    } else if (
      this.categoryPoints.every((row) => row[1] >= 2) &&
      this.playStateType === PlayStateType.play
    ) {
      this.playStateChange('win', 'category');
    } else if (
      this.categoryPointsOpponent.every((row) => row[1] >= 2) &&
      this.playStateType === PlayStateType.play
    ) {
      this.playStateChange('lost', 'category');
    }
  }

  cardWin(winningCard) {
    const element = document.getElementById(winningCard.name);
    if (element) {
      element.classList.add('cardWin');
    }
    return true;
  }

  finished() {
    const cardsPlayed = this.ownCardsPlayed.map((x) => x);
    const pointsSpent = this.pointsSpent;
    let co2 = 0;
    let electricity = 0;
    let lifetime = 0;
    let water = 0;
    let money = 0;
    for (let i = 0; i < this.ownCardsPlayed.length; i++) {
      co2 += this.ownCardsPlayed[i].CO2;
      electricity += this.ownCardsPlayed[i].energy;
      lifetime += this.ownCardsPlayed[i].lifetime;
      water += this.ownCardsPlayed[i].water;
      money += this.ownCardsPlayed[i].money;
    }

    if (!this.win) {
      this.reason = 'lost';
    }

    this.$emit(
      'playFinished',
      this.win,
      this.reason,
      cardsPlayed,
      pointsSpent,
      co2,
      electricity,
      lifetime,
      water,
      money
    );
    this.clearPlayState();
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

.CO2BarText {
  position: relative;
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
  align-items: center;
  height: 30%;
  width: 100%;
  z-index: 50;
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
  /*filter: drop-shadow(var(--color-dark-contrast) -0.4rem 0.2rem 0.2rem);*/
  box-shadow: var(--color-dark-contrast) -0.2rem 0.2rem 0.4rem;
  transition: 0.3s;
}

.cardContainerActive {
  z-index: 2;
  transform: translateY(-1rem);
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
  top: -0.5rem;
  right: -0.2rem;
  width: 6rem;
  height: 1.5rem;
  z-index: 100;
  filter: drop-shadow(var(--color-dark-contrast) -0.1rem 0.2rem 0.2rem);
}

.waiting {
  color: var(--color-background);
}

.yourTurn {
  padding: 0.3rem 0.7rem;
  border-radius: var(--border-radius);
  opacity: 100%;
  transition: 0.3s;
  animation: appear 1s, yourTurnAnimation 3s infinite ease-in-out;
}

@keyframes yourTurnAnimation {
  0% {
    transform: scale(100%);
    background-color: transparent;
  }
  10% {
    transform: scale(100%);
    background-color: transparent;
  }
  50% {
    transform: scale(115%);
    background-color: var(--color-brainstorming);
  }
  60% {
    transform: scale(115%);
    background-color: var(--color-brainstorming);
  }
  1000% {
    transform: scale(100%);
    background-color: transparent;
  }
}

@keyframes appear {
  0% {
    opacity: 0;
  }
  10% {
    opacity: 0;
  }
  100% {
    opacity: 100%;
  }
}

.waitingTexts {
  text-align: center;
}

p.gameKey {
  font-size: var(--font-size-xsmall);
  margin-left: 0.6rem;
  margin-top: 0.4rem;
  position: absolute;
}

.resultArea {
  position: relative;
  overflow-y: scroll;
  height: 100%;
}

.result {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  font-size: var(--font-size-default);
  text-align: center;
  padding-top: 2rem;
  background-color: var(--color-background);
  overflow-y: scroll !important;

  span {
    width: 100%;
    text-align: center;
  }
  .infoText {
    height: fit-content;
    text-align: left;
    ul {
      list-style-type: circle;
      padding-left: 3rem;
    }
  }
}

.endCards {
  position: relative;
  display: flex;
  justify-content: flex-start;
  align-items: center;
  height: 20rem;
  width: 100%;
  z-index: 10;
  overflow-x: scroll;
  overflow-y: hidden;
  -ms-overflow-style: none; /* IE and Edge */
  scrollbar-width: none; /* Firefox */
  background-color: var(--color-brown-xlight);
  outline: 0.5rem solid var(--color-brown);
  margin: 1rem 0;
}

.endCards::-webkit-scrollbar {
  display: none;
}

.endCard {
  text-align: left;
  position: relative;
  aspect-ratio: 1904/2564;
  background-size: cover;
  padding: 2%;
  height: 78%;
  z-index: 1;
  transition: 0.3s;
  margin: 1rem;
}

.CardDescription {
  position: absolute;
  bottom: -12%;
  right: 0;
  left: 0;
  text-align: center;
  font-weight: var(--font-weight-semibold);
  color: var(--color-brown);
}

.marginTop {
  margin-top: 2rem;
  padding: 0 1rem;
}

.returnButton {
  margin: 2rem;
}

.infoText {
  height: 2rem;
}

.joinID {
  font-weight: var(--font-weight-semibold);
  font-size: var(--font-size-large);
}
</style>
