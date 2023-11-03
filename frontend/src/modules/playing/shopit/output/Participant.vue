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
    />
  </div>
</template>

<script lang="ts">
import {Options, Vue} from 'vue-class-component';
import {Prop, Watch} from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import ModuleInfo, {ModuleInfoEntryData,} from '@/components/participant/molecules/ModuleInfo.vue';
import * as moduleService from '@/services/module-service';
import {Module} from '@/types/api/Module';
import PlayState, {PlayStateResult,} from '@/modules/playing/shopit/organisms/PlayState.vue';
import JoinState from '@/modules/playing/shopit/organisms/JoinState.vue';
import SingleplayerState from '@/modules/playing/shopit/organisms/SingleplayerState.vue';
import {Idea} from '@/types/api/Idea';
import * as ideaService from '@/services/idea-service';
import gameConfig from '@/modules/playing/shopit/data/gameConfig.json';
import {until} from '@/utils/wait';

export enum GameStep {
  Join = 'join',
  Play = 'play',
  Singleplayer = 'singleplayer',
}

enum GameState {
  Info = 'info',
  Game = 'game',
}

@Options({
  components: {
    ModuleInfo,
    PlayState,
    SingleplayerState,
    JoinState,
  },
})
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
  player = 0;

  get tutorialList(): (string | ModuleInfoEntryData)[] {
    switch (this.gameStep) {
      case GameStep.Join:
        return [{ key: 'select', texture: 'tut.png' }];
      case GameStep.Play:
        return [{ key: 'play', texture: 'clothes.png' }];
    }
    return [];
  }

  mounted(): void {
    setTimeout(() => {
      const dom = this.$refs.gameContainer as HTMLElement;
      if (dom) {
        const targetWidth = dom.parentElement?.offsetWidth;
        const targetHeight = dom.parentElement?.offsetHeight;
        if (targetWidth && targetHeight) {
          (dom as any).style.width = `${targetWidth}px`;
          (dom as any).style.height = `${targetHeight}px`;
          document.body.style.overflowY = 'hidden';
        }
        this.sizeCalculated = true;
      }
    }, 500);
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
  }

  unmounted(): void {
    this.deregisterAll();
  }

  selectionDone(option, id): void {
    switch (option) {
      case 'singleplayer':
        this.startSingleplayer();
        break;
      case 'multiplayer':
        this.player = 1;
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

  async InstantiateGame(id) {
    console.log('Instantiating game: ' + id);
    this.gameIdeaInstance = await ideaService.postIdea(
      this.taskId,
      {
        keywords: id,
        parameter: {
          active: false,
          playerNum: 0,
          id: id,
          cards: this.shuffle(this.parseCards(gameConfig)),
          player1Hand: [],
          player2Hand: [],
          cardsPlayed: [],
          playersTurn: Math.random() >= 0.5 ? 2 : 1,
        },
      },
      EndpointAuthorisationType.PARTICIPANT
    );
    await this.startGame();
  }

  async joinGame(id) {
    console.log('Joining game: ' + id);
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
    if (this.gameIdeaInstance && this.gameIdeaInstance.parameter.playerNum == 2) {
      this.gameIdeaInstance.parameter.active = true;
      ideaService.putIdea(this.gameIdeaInstance, EndpointAuthorisationType.PARTICIPANT);
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
      await until(() =>
        setTimeout(() => {
          const i = 1;
        }, 5000)
      );
      switch (this.gameIdeaInstance.parameter.active) {
        case false:
          this.gameIdeaInstance.parameter.playerNum += 1;
          this.gameStep = GameStep.Play;
          break;
        case true:
          break;
      }
    }
  }

  startSingleplayer(): void {
    this.gameStep = GameStep.Singleplayer;
  }

  playFinished(result: PlayStateResult): void {
    this.gameStep = GameStep.Join;
    this.gameState = GameState.Info;
  }
}
</script>

<style lang="scss" scoped>
.gameSpace {
  position: relative;
}
</style>
