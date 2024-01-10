<template>
  <div ref="gameContainer" class="gameSpace">
    <module-info
      v-if="gameState === GameState.Info && module"
      translation-path="module.playing.shopit.participant.tutorial"
      image-directory="/assets/games/shopit/tutorial"
      :module-info-entry-data-list="tutorialList"
      @infoRead="gameState = GameState.Game"
      :info-type="`shop-it-${gameStep}`"
      :showTutorialOnlyOnce="module.parameter.showTutorialOnlyOnce"
    />
    <JoinState
      v-if="gameStep === GameStep.Join && gameState === GameState.Game"
      :task-id="taskId"
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
import { Idea } from '@/types/api/Idea';
import * as ideaService from '@/services/idea-service';
import gameConfig from '@/modules/playing/shopit/data/gameConfig.json';
import { until, delay } from '@/utils/wait';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import { registerDomElement, unregisterDomElement } from '@/vunit';

export enum GameStep {
  Join = 'join',
  Play = 'play',
  Singleplayer = 'singleplayer',
}

enum GameState {
  Info = 'info',
  Game = 'game',
  Left = 'left',
}

@Options({
  components: {
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

  sizeCalculated = false;

  gameStep = GameStep.Join;
  GameStep = GameStep;
  gameState = GameState.Info;
  GameState = GameState;

  cards = this.shuffle(this.parseCards(gameConfig));

  gameIdeaInstance: Idea | null = null;
  joinID = 0;
  hostID = 0;
  player = 0;
  checkedAvailability = false;

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
  }

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
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
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateModule);
    cashService.deregisterAllGet(this.updateGame);
    cashService.deregisterAllGet(this.checkAvailability);
    if (this.trackingManager) this.trackingManager.deregisterAll();
  }

  unmounted(): void {
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
        this.InstantiateGame(id);
        this.startGame();
        break;
      case 'joinMultiplayer':
        this.player = 2;
        this.joinID = id;
        this.joinGame(id);
        this.startGame();
        break;
    }
  }

  //eslint-disable-next-line @typescript-eslint/no-unused-vars
  async InstantiateGame(id) {
    await this.checkAvailability();
    await until(() => this.checkedAvailability);
    cashService.deregisterAllGet(this.checkAvailability);
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
  async joinGame(id) {
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
      await delay(5000);
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
          rate: this.calcRate(win, pointsSpent),
        },
        null,
        this.calcRate(win, pointsSpent)
      );
      await this.trackingManager.saveIteration({
        gameStep: GameStep.Join,
      });
    }
    this.clearAndReset();
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
</style>
