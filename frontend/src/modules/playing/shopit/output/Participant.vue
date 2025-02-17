<template>
  <div ref="gameContainer" class="gameSpace">
    <module-info
      v-if="module && module.parameter.showTutorial != 0"
      translation-path="module.playing.shopit.participant.tutorial"
      image-directory="/assets/games/shopit/tutorial"
      :module-info-entry-data-list="tutorialList"
      :active="gameState === GameState.Info"
      @infoRead="infoRead"
      @tutorialNotShown="() => (tutorialNotShown = true)"
      :info-type="`shop-it-${gameStep}`"
      :showTutorialOnlyOnce="
        module.parameter.showTutorial === 1 && !reloadTutorial
      "
    />
    <TutorialGame
      v-if="gameStep === GameStep.Tut && gameState === GameState.Game"
      :cost="tutorialCard.cost"
      :CO2="tutorialCard.CO2"
      :energy="tutorialCard.energy"
      :lifetime="tutorialCard.lifetime"
      :water="tutorialCard.water"
      :money="tutorialCard.money"
      :category="tutorialCard.category"
      :condition="tutorialCard.condition"
      :cardName="tutorialCard.name"
      @playFinished="gameStep = GameStep.Join"
    />
    <JoinState
      v-if="gameStep === GameStep.Join && gameState === GameState.Game"
      :task-id="taskId"
      :open-high-score="levelDone"
      @selectionDone="selectionDone"
    />
    <SingleplayerState
      v-if="gameStep === GameStep.Singleplayer && gameState === GameState.Game"
      :taskId="taskId"
      @playFinished="playFinished"
    />
    <PlayState
      v-if="gameStep === GameStep.Play && gameState === GameState.Game"
      :taskId="taskId"
      :game="gameIdeaInstance"
      :player="player"
      @playFinished="playFinished"
      @playerLeft="playerLeft"
    />
    <div class="playerLeftScreen" v-if="gameState === GameState.Left">
      <h2 class="heading heading--medium">
        {{ $t('module.playing.shopit.participant.playerLeft.left') }}
      </h2>
      <p>
        {{ $t('module.playing.shopit.participant.playerLeft.leftText') }}
      </p>
      <el-button class="el-button--submit returnButton" @click="clearAndReset">
        {{ $t('module.playing.shopit.participant.returnToMenu') }}
      </el-button>
    </div>
    <el-button
      v-if="gameStep === GameStep.Join && gameState === GameState.Game"
      class="tutorialButton"
      @click="
        () => {
          gameState = GameState.Info;
          reloadTutorial = true;
        }
      "
    >
      <font-awesome-icon :icon="['fas', 'lightbulb']" />&nbsp;
      {{ $t('module.playing.shopit.participant.tutorial.menu') }}
    </el-button>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import ModuleInfo, {
  ModuleInfoEntryData,
} from '@/components/participant/molecules/ModuleInfo.vue';
import * as moduleService from '@/services/module-service';
import { Module } from '@/types/api/Module';
import PlayState from '@/modules/playing/shopit/organisms/PlayState.vue';
import JoinState from '@/modules/playing/shopit/organisms/JoinState.vue';
import SingleplayerState from '@/modules/playing/shopit/organisms/SingleplayerState.vue';
import TutorialGame from '@/modules/playing/shopit/organisms/TutorialGame.vue';
import { Idea } from '@/types/api/Idea';
import * as ideaService from '@/services/idea-service';
import gameConfig from '@/modules/playing/shopit/data/gameConfig.json';
import { until, delay } from '@/utils/wait';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import { registerDomElement, unregisterDomElement } from '@/vunit';
import TaskParticipantIterationStatesType from '@/types/enum/TaskParticipantIterationStatesType';
import { Vote } from '@/types/api/Vote';
import * as votingService from '@/services/voting-service';
import EndpointType from '@/types/enum/EndpointType';

export enum GameStep {
  Join = 'join',
  Play = 'play',
  Tut = 'tut',
  Singleplayer = 'singleplayer',
}

enum GameState {
  Info = 'info',
  Game = 'game',
  Left = 'left',
}

@Options({
  components: {
    TutorialGame,
    ModuleInfo,
    PlayState,
    SingleplayerState,
    JoinState,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  @Prop({ default: false }) readonly useFullSize!: boolean;
  @Prop({ default: '' }) readonly backgroundClass!: string;
  module: Module | null = null;
  loading = true;
  levelDone = false;
  highscore: Vote | null = null;

  sizeCalculated = false;
  tutorialNotShown = false;

  gameStep = GameStep.Join;
  GameStep = GameStep;
  gameState = GameState.Info;
  GameState = GameState;

  cards = this.shuffle(this.parseCards(gameConfig));
  tutorialCard =
    this.cards[Math.floor(Math.random() * (this.cards.length - 1))];

  gameIdeaInstance: Idea | null = null;
  joinID = 0;
  hostID = 0;
  player = 0;
  checkedAvailability = false;
  reloadTutorial = false;

  trackingManager!: TrackingManager;
  inputTaskId = '';

  clearAndReset() {
    this.gameStep = GameStep.Join;
    this.GameStep = GameStep;
    this.gameState = GameState.Game;
    this.GameState = GameState;

    this.cards = this.shuffle(this.parseCards(gameConfig));

    this.joinID = 0;
    this.player = 0;
    this.hostID = 0;
    this.checkedAvailability = false;
    this.gameIdeaInstance = null;
  }

  get tutorialList(): (string | ModuleInfoEntryData)[] {
    switch (this.gameStep) {
      case GameStep.Join:
        return [
          { key: 'rules1', texture: 'tut1.gif' },
          { key: 'rules2', texture: 'tut2.png' },
          { key: 'pointCalc', texture: 'PointCalc.png' },
          { key: 'tutGame', texture: 'tutGame.gif' },
        ];
      case GameStep.Play:
        return [{ key: 'play', texture: 'clothes.png' }];
    }
    return [];
  }

  domKey = '';
  mounted(): void {
    this.domKey = registerDomElement(
      this.$refs.gameContainer as HTMLElement,
      () => {
        document.body.style.overflowY = 'hidden';
        this.sizeCalculated = true;
      },
      500
    );

    votingService.registerGetVotes(
      this.taskId,
      this.updateHighScore,
      EndpointAuthorisationType.PARTICIPANT,
      60 * 60
    );
  }

  infoRead(): void {
    if (this.gameStep === GameStep.Join) {
      this.gameStep =
        this.reloadTutorial || !this.tutorialNotShown
          ? GameStep.Tut
          : GameStep.Join;
      this.gameState = GameState.Game;
    }
  }

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  @Watch('gameState', { immediate: true })
  onGameStateChanged(): void {
    if (this.gameState === GameState.Info) {
      this.tutorialNotShown = false;
    }

    if (this.gameState !== GameState.Info) {
      this.reloadTutorial = false;
    }
  }

  @Watch('moduleId', { immediate: true })
  onModuleIdChanged(): void {
    if (this.moduleId) {
      moduleService.registerGetModuleById(
        this.moduleId,
        this.updateModule,
        EndpointAuthorisationType.PARTICIPANT,
        60 * 60
      );
    }
  }

  updateModule(module: Module): void {
    this.module = module;

    if (this.module.parameter.showTutorial === 0) {
      this.infoRead();
    }
  }

  updateHighScore(votes: Vote[]): void {
    if (votes.length > 0) this.highscore = votes[0];
    else this.highscore = null;
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateModule);
    cashService.deregisterAllGet(this.updateHighScore);
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateGame);
    cashService.deregisterAllGet(this.checkGames);
    if (this.trackingManager) this.trackingManager.deregisterAll();
  }

  unmounted(): void {
    document.body.style.removeProperty('overflow-y');
    this.deregisterAll();
    unregisterDomElement(this.domKey);
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      this.trackingManager = new TrackingManager(this.taskId, {
        gameStep: GameStep.Join,
        game: {
          cardsPlayed: [],
          pointsSpent: 0,
          co2: 0,
          electricity: 0,
          lifetime: 0,
          water: 0,
          money: 0,
          winReason: '',
        },
        rate: null,
      });
      taskService.registerGetTaskById(
        this.taskId,
        this.updateTask,
        EndpointAuthorisationType.PARTICIPANT,
        60 * 60
      );
    }
  }

  updateTask(task: Task): void {
    if (task.parameter.input.length > 0)
      this.inputTaskId = task.parameter.input[0].view.id;
  }

  selectionDone(option, id): void {
    switch (option) {
      case 'singleplayer':
        this.startSingleplayer();
        break;
      case 'multiplayer':
        this.player = 1;
        this.hostID = id;
        this.InstantiateGame();
        this.startGame();
        break;
      case 'joinMultiplayer':
        this.player = 2;
        this.joinID = id;
        this.joinGame();
        this.startGame();
        break;
    }
  }

  //eslint-disable-next-line @typescript-eslint/no-unused-vars
  async InstantiateGame() {
    await this.checkAvailability();
    await until(() => this.checkedAvailability);
    cashService.deregisterAllGet(this.checkGames);
    this.gameIdeaInstance = await ideaService.postIdea(
      this.taskId,
      {
        keywords: '' + this.hostID,
        parameter: {
          active: false,
          playerNum: 0,
          id: this.hostID,
          cards: this.shuffle(this.parseCards(gameConfig)),
          player1Hand: [],
          player2Hand: [],
          cardsPlayed: [],
          playersTurn: Math.random() >= 0.5 ? 2 : 1,
          ready: false,
        },
      },
      EndpointAuthorisationType.PARTICIPANT
    );
    await this.startGame();
  }

  async checkAvailability() {
    ideaService.registerGetIdeasForTask(
      this.taskId,
      null,
      null,
      this.checkGames,
      EndpointAuthorisationType.PARTICIPANT,
      1
    );
  }

  checkGames(game: Idea[]): void {
    for (let i = 0; i < game.length; i++) {
      if (parseInt(game[i].keywords) == this.hostID) {
        i = 0;
        this.hostID += 1;
        if (this.hostID > 9999) {
          this.hostID = 1000;
        }
      }
    }
    this.checkedAvailability = true;
  }

  //eslint-disable-next-line @typescript-eslint/no-unused-vars
  async joinGame() {
    ideaService.registerGetIdeasForTask(
      this.taskId,
      null,
      null,
      this.updateGame,
      EndpointAuthorisationType.PARTICIPANT,
      5
    );
  }

  updateGame(game: Idea[]): void {
    for (let i = 0; i < game.length; i++) {
      if (parseInt(game[i].keywords) == this.joinID) {
        this.gameIdeaInstance = game[i];
        this.startGame();
      }
    }

    if (
      this.gameIdeaInstance &&
      this.gameIdeaInstance.parameter.playerNum == 2
    ) {
      this.gameIdeaInstance.parameter.active = true;
      ideaService.putIdea(
        this.gameIdeaInstance,
        EndpointAuthorisationType.PARTICIPANT
      );
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

  async startGame() {
    if (this.gameIdeaInstance) {
      await ideaService.putIdea(
        this.gameIdeaInstance,
        EndpointAuthorisationType.PARTICIPANT
      );
      await delay(1000);
      switch (this.gameIdeaInstance.parameter.active) {
        case false:
          this.gameIdeaInstance.parameter.playerNum += 1;
          this.gameStep = GameStep.Play;
          cashService.deregisterAllGet(this.updateGame);
          break;
        case true:
          break;
      }
      if (this.trackingManager) {
        await this.trackingManager.createInstanceStep(
          null,
          TaskParticipantIterationStepStatesType.NEUTRAL,
          {
            game: {
              cardsPlayed: [],
              pointsSpent: 0,
              co2: 0,
              electricity: 0,
              lifetime: 0,
              water: 0,
              money: 0,
              winReason: '',
            },
            rate: null,
            finished: false,
          }
        );
        await this.trackingManager.saveIteration({
          gameStep: GameStep.Play,
        });
      }
    }
  }

  async startSingleplayer() {
    this.gameStep = GameStep.Singleplayer;
    if (this.trackingManager) {
      await this.trackingManager.createInstanceStep(
        null,
        TaskParticipantIterationStepStatesType.NEUTRAL,
        {
          game: {
            cardsPlayed: [],
            pointsSpent: 0,
            co2: 0,
            electricity: 0,
            lifetime: 0,
            water: 0,
            money: 0,
            winReason: '',
          },
          rate: null,
          finished: false,
        }
      );
      await this.trackingManager.saveIteration({
        gameStep: GameStep.Singleplayer,
      });
    }
  }

  async playFinished(
    win: boolean,
    winReason: string,
    cardsPlayed: [],
    pointsSpent: number,
    co2: number,
    electricity: number,
    lifetime: number,
    water: number,
    money: number
  ): Promise<void> {
    if (this.trackingManager) {
      const rate = this.calcRate(win, pointsSpent);
      await this.trackingManager.saveIterationStep(
        {
          game: {
            cardsPlayed: cardsPlayed,
            pointsSpent: pointsSpent,
            co2: co2,
            electricity: electricity,
            lifetime: lifetime,
            water: water,
            money: money,
            winReason: winReason,
          },
          rate: rate,
          finished: true,
        },
        null,
        this.calcRate(win, pointsSpent)
      );
      await this.trackingManager.saveIteration(
        {
          gameStep: GameStep.Join,
        },
        rate && rate >= 2
          ? TaskParticipantIterationStatesType.WIN
          : TaskParticipantIterationStatesType.LOOS,
        this.calcRate(win, pointsSpent)
      );
    }
    this.saveHighScore();
    this.clearAndReset();
    this.levelDone = true;
  }

  calcRate(win, pointsSpent): number | null {
    if (win) {
      return pointsSpent <= 90 ? 3 : pointsSpent <= 110 ? 2 : 1;
    } else if (!win && pointsSpent > 0) {
      return 0;
    } else if (!win && pointsSpent === 0) {
      return null;
    } else {
      return null;
    }
  }

  async playerLeft(): Promise<void> {
    if (this.trackingManager) {
      await this.trackingManager.saveIterationStep(
        {
          game: {
            cardsPlayed: null,
            pointsSpent: null,
            co2: null,
            electricity: null,
            lifetime: null,
            water: null,
            money: null,
            winReason: null,
          },
          rate: null,
        },
        null,
        this.calcRate(null, null)
      );
      await this.trackingManager.saveIteration({
        gameStep: GameStep.Join,
      });
    }
    this.gameStep = GameStep.Join;
    this.gameState = GameState.Left;
  }

  saveHighScore(): void {
    const parameter = this.trackingManager.iterationStep?.parameter;
    if (!parameter) return;
    if (this.highscore) {
      if (
        !this.highscore.parameter.rate ||
        this.highscore.parameter.rate <= parameter.rate
      ) {
        this.highscore.detailRating = parameter.rate;
        this.highscore.rating = parameter.rate;
        this.highscore.parameter.rate = parameter.rate;
        this.highscore.parameter.cardsPlayed =
          parameter.game.pointsSpent.length;
        this.highscore.parameter.pointsSpent = parameter.game.pointsSpent;
        this.highscore.parameter.co2 = parameter.game.co2;
        this.highscore.parameter.electricity = parameter.game.electricity;
        this.highscore.parameter.lifetime = parameter.game.lifetime;
        this.highscore.parameter.water = parameter.game.water;
        this.highscore.parameter.money = parameter.game.money;
        votingService.putVote(this.highscore).then(() => {
          cashService.refreshCash(
            `/${EndpointType.TASK}/${this.taskId}/${EndpointType.VOTES}`
          );
        });
      }
    } else {
      votingService
        .postVote(this.taskId, {
          rating: parameter.rate,
          detailRating: parameter.rate,
          parameter: {
            rate: parameter.rate,
            cardsPlayed: parameter.game.cardsPlayed.length,
            pointsSpent: parameter.game.pointsSpent,
            co2: parameter.game.co2,
            electricity: parameter.game.electricity,
            lifetime: parameter.game.lifetime,
            water: parameter.game.water,
            money: parameter.game.money,
          },
        })
        .then((vote) => {
          this.highscore = vote;
          cashService.refreshCash(
            `/${EndpointType.TASK}/${this.taskId}/${EndpointType.VOTES}`
          );
        });
    }
  }
}
</script>

<style lang="scss" scoped>
.gameSpace {
  position: relative;
}

.playerLeftScreen {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  font-size: var(--font-size-default);
  text-align: center;
  height: 100%;
  width: 100%;
  padding: 1rem;
}

.returnButton {
  position: absolute;
  bottom: 2rem;
}

.tutorialButton {
  position: absolute;
  margin: 0;
  bottom: 0.2rem;
  left: 0.2rem;
  text-align: center;
  background-color: transparent;
}
</style>
