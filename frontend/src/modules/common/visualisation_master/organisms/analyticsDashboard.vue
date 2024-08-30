<template>
  <div id="analyticsContainer">
    <div class="contentMain">
      <div class="contentAnimation">
        <div v-if="steps.length > 0" class="allAnimationContainer">
          <div
            v-for="stepCategory of steps"
            :key="stepCategory.module"
            :id="stepCategory.module"
            class="animationContainer"
            :ref="stepCategory.module"
          >
            <p v-if="stepCategory.module === 'shopit'" class="billboardText">
              {{
                $t(
                  'module.common.visualisation_master.visModules.analytics.module.billboard'
                )
              }}
            </p>
          </div>
          <div class="animationBackground">
            <div id="city" class="backgroundItem">
              <el-image :src="'/assets/animations/analytics/City.png'" />
            </div>
            <!--<div id="birds" class="backgroundItem">
              <video width="200" height="200" loop autoplay>
                <source
                  :src="'/assets/animations/analytics/birds.webm'"
                  type="video/webm"
                />
              </video>
            </div>-->
            <div id="hills" class="backgroundItem">
              <el-image :src="'/assets/animations/analytics/hills.png'" />
            </div>
            <div id="street" class="backgroundItem">
              <el-image :src="'/assets/animations/analytics/Street.png'" />
            </div>
          </div>
        </div>
      </div>
      <div class="contentSide">
        <h1>
          {{
            $t(
              'module.common.visualisation_master.visModules.analytics.module.highscore'
            )
          }}
        </h1>
        <el-carousel height="100%" :interval="10000">
          <el-carousel-item
            v-for="game of gameTasks"
            class="HighScoreContainer"
            :key="game.id"
          >
            <p class="highScoreHeading">{{ game.name }}</p>
            <coolItHighScore
              v-if="game.modules.find((module) => module.name === 'coolit')"
              :task-id="game.id"
            />
            <moveItHighScore
              v-if="game.modules.find((module) => module.name === 'moveit')"
              :task-id="game.id"
            />
            <shopItHighScore
              v-if="game.modules.find((module) => module.name === 'shopit')"
              :task-id="game.id"
            />
            <findItHighScore
              v-if="game.modules.find((module) => module.name === 'findit')"
              :task-id="game.id"
            />
          </el-carousel-item>
        </el-carousel>
      </div>
    </div>
    <div ref="contentBanner" class="contentBanner">
      <div class="contentModule">
        <Gallery
          v-if="ideas.length > 0"
          :task-id="taskId"
          :timeModifier="timeModifier"
          :ideas="ideas"
          :paused="paused"
        />
        <PublicScreenComponent
          v-else-if="task"
          :task-id="taskId"
          :key="componentLoadIndex"
          :authHeaderTyp="authHeaderTyp"
        />
      </div>
      <div
        v-if="session"
        class="contentSide media"
        :style="{
          '--connection-key-length': session.connectionKey.length,
          '--qr-code-size': qrCodeSize,
        }"
      >
        <h1 class="media-content">
          {{
            $t(
              'module.common.visualisation_master.visModules.analytics.module.join'
            )
          }}
        </h1>
        <div
          class="qrcode media-right"
          :style="{
            '--connection-key-length': session.connectionKey.length,
            '--qr-code-size': qrCodeSize,
          }"
        >
          {{ session.connectionKey }}
          <QrcodeVue
            :foreground="contrastColor"
            :background="backgroundColor"
            render-as="svg"
            :value="joinLink"
            :size="qrCodeSize"
            level="H"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import {
  defaultFilterData,
  FilterData,
} from '@/components/moderator/molecules/IdeaFilterBase.vue';
import { Task } from '@/types/api/Task';
import {
  getAsyncDefaultModule,
  getAsyncModule,
  getEmptyComponent,
} from '@/modules';
import ModuleComponentType from '@/modules/ModuleComponentType';
import TaskType from '@/types/enum/TaskType';
import * as taskService from '@/services/task-service';
import * as sessionService from '@/services/session-service';
import * as taskParticipantService from '@/services/task-participant-service';
import * as cashService from '@/services/cash-service';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import * as pixiUtil from '@/utils/pixi';
import * as PIXI from 'pixi.js';
import { createApp, h } from 'vue';
import shopItGameConfig from '@/modules/playing/shopit/data/gameConfig.json';
import moveItGameConfig from '@/modules/playing/moveit/data/gameConfig.json';
import Gallery from '@/modules/common/visualisation_master/organisms/gallery.vue';

import coolItHighScore from '@/modules/playing/coolit/organisms/Highscore.vue';
import moveItHighScore from '@/modules/playing/moveit/organisms/Highscore.vue';
import shopItHighScore from '@/modules/playing/shopit/organisms/Highscore.vue';
import findItHighScore from '@/modules/playing/findit/organisms/Highscore.vue';

import SpriteCanvas from '@/components/shared/atoms/game/SpriteCanvas.vue';
import { delay } from '@/utils/wait';
import { Session } from '@/types/api/Session';
import * as themeColors from '@/utils/themeColors';
import QrcodeVue from 'qrcode.vue';

/* eslint-disable @typescript-eslint/no-explicit-any*/

interface Card {
  CO2: number;
  category: string;
  condition: number;
  cost: number;
  energy: number;
  infoKey: string;
  lifetime: number;
  money: number;
  name: string;
  water: number;
}

interface VehicleData {
  vehicle: any;
  category: string;
  animation: PIXI.Texture[];
}

@Options({
  components: {
    SpriteCanvas,
    IdeaCard,
    coolItHighScore,
    moveItHighScore,
    shopItHighScore,
    findItHighScore,
    Gallery,
    QrcodeVue,
    PublicScreenComponent: getEmptyComponent(),
  },
})
export default class AnalyticsDashboard extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly task!: Task;
  @Prop({ default: 0 }) readonly timeModifier!: number;
  @Prop({ default: null }) readonly taskParameter!: any;
  @Prop({ default: [] }) readonly ideas!: Idea[];
  @Prop({ default: false }) readonly paused!: boolean;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  filter: FilterData = { ...defaultFilterData };

  textureToken = pixiUtil.createLoadingToken();
  vehicleSpritesheet: PIXI.Spritesheet | null = null;
  cloudFolderPath = '/assets/animations/analytics/clouds/';

  tasks: Task[] = [];
  gameTasks: Task[] = [];
  session: Session | null = null;

  animationTimeInSeconds = 30;
  componentLoadIndex = 0;
  bannerHeight = 100;

  steps: {
    module: string;
    steps: TaskParticipantIterationStep[];
    newSteps: TaskParticipantIterationStep[];
  }[] = [];

  get topicId(): string | null {
    if (this.task) return this.task.topicId;
    return null;
  }

  get sessionId(): string | null {
    if (this.task) return this.task.sessionId;
    return null;
  }

  get joinLink(): string {
    if (this.session)
      return `${window.location.origin}/join/${this.session.connectionKey}`;
    return `${window.location.origin}/join/`;
  }

  get contrastColor(): string {
    return themeColors.getContrastColor();
  }

  get backgroundColor(): string {
    return themeColors.getBackgroundColor();
  }

  get qrCodeSize(): number {
    return this.bannerHeight * 0.65;
  }

  mounted() {
    pixiUtil
      .loadTexture(
        '/assets/games/moveit/vehicle/vehicle_animation.json',
        this.textureToken
      )
      .then(async (sheet) => {
        this.vehicleSpritesheet = sheet;
        for (const vehicle of this.vehicleList) {
          if (vehicle.animation.length === 0)
            vehicle.animation = this.getAnimationForVehicle(vehicle.vehicle);
        }
        await delay(100);
      });

    getAsyncDefaultModule(ModuleComponentType.PUBLIC_SCREEN).then(
      (component) => {
        if (this.$options.components && this.componentLoadIndex === 0) {
          this.$options.components['PublicScreenComponent'] = component;
          this.componentLoadIndex++;
        }
      }
    );

    const banner = this.$refs.contentBanner as HTMLElement;
    if (banner) {
      this.bannerHeight = banner.clientHeight;
    }
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTasks);
    cashService.deregisterAllGet(this.updateSession);
    this.deregisterSteps();
  }

  deregisterSteps(): void {
    for (const task of this.gameTasks) {
      taskParticipantService.deregisterGetIterationStepList(task.id);
    }
  }

  unmounted(): void {
    this.deregisterAll();
    pixiUtil.cleanupToken(this.textureToken);
  }

  getModuleName(task: Task): string[] {
    if (task && task.modules && task.modules.length > 0)
      return task.modules.map((module) => module.name);
    return ['default'];
  }

  @Watch('task', { immediate: true })
  async onTaskChanged(): Promise<void> {
    if (this.$options.components) {
      const taskType = TaskType[this.task.taskType];
      await getAsyncModule(
        ModuleComponentType.PUBLIC_SCREEN,
        taskType,
        this.getModuleName(this.task),
        false
      ).then((component) => {
        if (this.$options.components) {
          this.$options.components['PublicScreenComponent'] = component;
          this.componentLoadIndex++;
        }
      });
    }

    if (this.topicId) {
      cashService.deregisterAllGet(this.updateTasks);
      taskService.registerGetTaskList(
        this.topicId,
        this.updateTasks,
        this.authHeaderTyp,
        30 * 60
      );
    }

    if (this.sessionId) {
      cashService.deregisterAllGet(this.updateSession);
      sessionService.registerGetById(
        this.sessionId,
        this.updateSession,
        this.authHeaderTyp,
        30 * 60
      );
    }
  }

  @Watch('gameTasks', { immediate: true })
  onGameTasksChanged(): void {
    for (const task of this.gameTasks) {
      taskParticipantService.registerGetIterationStepList(
        task.id,
        (steps: TaskParticipantIterationStep[]) => {
          this.updateIterationSteps(task.modules[0].name, steps);
        },
        EndpointAuthorisationType.MODERATOR,
        15
      );
    }
  }

  //eslint-disable-next-line @typescript-eslint/no-unused-vars
  updateTasks(tasks: Task[], topicId: string): void {
    this.deregisterSteps();
    this.tasks = tasks;
    this.gameTasks = this.tasks
      .filter((task) => task.taskType === 'PLAYING')
      .sort();
  }

  updateSession(session: Session): void {
    this.session = session;
  }

  handleNewEntries(moduleName: string, steps: TaskParticipantIterationStep[]) {
    const refArray = this.$refs[moduleName];

    // Ensure the refArray is not undefined and is an array
    if (Array.isArray(refArray) && refArray.length > 0) {
      const element = refArray[0] as HTMLElement; // Assuming you want to target the first element

      switch (moduleName) {
        case 'coolit':
          this.createElementsCoolit(steps, element);
          break;
        case 'moveit':
          this.createElementsMoveit(steps, element);
          break;
        case 'shopit':
          this.createElementsShopit(steps, element);
          break;
        case 'findit':
          this.createElementsFindit(steps, element);
          break;
      }
    }
  }

  updateIterationSteps(
    moduleName: string,
    steps: TaskParticipantIterationStep[]
  ): void {
    let newEntries: TaskParticipantIterationStep[] = [];
    const step = this.steps.find((entry) => entry.module === moduleName);
    if (step) {
      newEntries = this.findNewIterations(step.steps, steps);
      if (newEntries.length > 0) {
        this.handleNewEntries(moduleName, newEntries);
      }
    }

    const stepsEntry = {
      module: moduleName,
      steps: [...steps],
      newSteps: newEntries,
    };
    const index = this.steps.findIndex(
      (entry) => entry.module === stepsEntry.module
    );
    if (index != -1 && stepsEntry.newSteps.length > 0) {
      this.steps[index] = stepsEntry;
    } else if (index === -1) {
      this.steps.push(stepsEntry);
    }
  }

  findNewIterations(steps1: any, steps2: any) {
    const countMap: { [key: string]: number } = {};

    steps1 = steps1.filter((step) => step.parameter.finished);
    steps2 = steps2.filter((step) => step.parameter.finished);

    for (const step of steps1) {
      countMap[step.id] = (countMap[step.id] || 0) + 1;
    }

    for (const step of steps2) {
      countMap[step.id] = (countMap[step.id] || 0) - 1;
    }

    const extraElements: any = [];
    if (steps1.length === steps2.length) {
      for (let i = 0; i < steps1.length; i++) {
        if (!this.deepEqual(steps1[i], steps2[i])) {
          if (steps2[i].parameter.finished) {
            extraElements.push(steps2[i]);
          }
        }
      }
    } else {
      for (const step of steps2) {
        if (countMap[step.id] < 0) {
          if (step.parameter.finished) {
            extraElements.push(step);
          }
          countMap[step.id] = 0;
        }
      }
    }
    return extraElements;
  }

  deepEqual(obj1: any, obj2: any): boolean {
    if (obj1 === obj2) {
      return true;
    }

    if (
      typeof obj1 !== 'object' ||
      obj1 === null ||
      typeof obj2 !== 'object' ||
      obj2 === null
    ) {
      return false;
    }

    const keys1 = Object.keys(obj1);
    const keys2 = Object.keys(obj2);

    if (keys1.length !== keys2.length) {
      return false;
    }

    for (const key of keys1) {
      if (!keys2.includes(key)) {
        return false;
      }
      if (!this.deepEqual(obj1[key], obj2[key])) {
        return false;
      }
    }

    return true;
  }

  createElementsCoolit(
    steps: TaskParticipantIterationStep[],
    parent: HTMLElement
  ) {
    //eslint-disable-next-line @typescript-eslint/no-unused-vars
    steps.forEach((step, index) => {
      //move it car check
      let imgSource = '';
      if (step.parameter.gameplayResult) {
        imgSource = this.cloudFolderPath;
        if (step.parameter.gameplayResult.stars >= 3) {
          imgSource +=
            this.cloudFolderPath +
            'LightCloud_' +
            Math.round(Math.random()) +
            '.png';
        } else {
          imgSource +=
            step.parameter.gameplayResult.stars === 2
              ? 'MidCloud_' + Math.round(Math.random()) + '.png'
              : 'DarkCloud_' + Math.round(Math.random()) + '.png';
        }
      }

      const divElement = document.createElement('div');
      divElement.setAttribute('key', step.id + Date.now());

      const imgElement = document.createElement('img');
      imgElement.setAttribute('src', imgSource);
      imgElement.style.objectFit = 'contain';

      divElement.appendChild(imgElement);
      parent.appendChild(divElement);

      divElement.style.animationDuration =
        this.animationTimeInSeconds + 's !important';
      divElement.style.top = Math.round(Math.random() * 80) + '%';
      divElement.classList.add('animateMoveLeftRight');
      divElement.classList.add('coolItAnimatedContainer');

      setTimeout(() => {
        parent.removeChild(divElement);
      }, this.animationTimeInSeconds * 1000);
    });
  }

  createElementsMoveit(
    steps: TaskParticipantIterationStep[],
    parent: HTMLElement
  ) {
    //eslint-disable-next-line @typescript-eslint/no-unused-vars
    steps.forEach((step, index) => {
      const divElement = document.createElement('div');
      divElement.setAttribute('key', step.id + Date.now());

      // Dynamically create a Vue app instance for the SpriteCanvas component
      const vehicle = this.getVehicleByType(step.parameter.vehicle.type);
      const app = createApp({
        render() {
          return h(SpriteCanvas, {
            texture: vehicle.animation || [],
            width: 200,
            height: 100,
            backgroundAlpha: 0,
          });
        },
      });

      // Mount the Vue instance into the divElement
      app.mount(divElement);

      const pElement = document.createElement('p');
      pElement.innerHTML =
        this.$t(
          `module.common.visualisation_master.visModules.analytics.module.drivingStats.maxSpeed`
        ) +
        ': ' +
        +Math.round(step.parameter.drive.maxSpeed) +
        ' ' +
        this.$t('module.playing.moveit.enums.units.km/h') +
        '<br />' +
        this.$t(
          `module.common.visualisation_master.visModules.analytics.module.drivingStats.avgSpeed`
        ) +
        ': ' +
        +Math.round(step.parameter.drive.averageSpeed) +
        ' ' +
        this.$t('module.playing.moveit.enums.units.km/h') +
        '<br />' +
        this.$t(
          `module.common.visualisation_master.visModules.analytics.module.drivingStats.distance`
        ) +
        ': ' +
        Math.round(step.parameter.drive.drivenPathLength * 10) / 10 +
        ' ' +
        this.$t('module.playing.moveit.enums.units.km') +
        '<br />' +
        this.$t(
          `module.common.visualisation_master.visModules.analytics.module.drivingStats.consumption`
        ) +
        ': ' +
        Math.round(step.parameter.drive.consumption * 100) / 100 +
        ' ' +
        this.$t('module.playing.moveit.enums.units.liters');

      pElement.classList.add('drivingStats');
      divElement.appendChild(pElement);

      parent.appendChild(divElement);

      // Apply styles and classes
      divElement.style.animationDuration =
        this.animationTimeInSeconds + 's !important';
      divElement.classList.add('animateMoveLeftRight');
      divElement.classList.add('moveItAnimatedContainer');

      // Cleanup after animation ends
      setTimeout(() => {
        app.unmount(); // Unmount the Vue instance
        parent.removeChild(divElement);
      }, this.animationTimeInSeconds * 1000);
    });
  }

  getVehicleByType(type: string) {
    return this.vehicleList[
      this.vehicleList.findIndex(
        (vehicleEntry) => vehicleEntry.vehicle.name === type
      )
    ];
  }

  createElementsShopit(
    steps: TaskParticipantIterationStep[],
    parent: HTMLElement
  ) {
    const elementsToRemove = parent.getElementsByClassName(
      'shopItAnimatedContainer'
    ) as HTMLCollectionOf<HTMLElement>;
    for (const element of elementsToRemove) {
      setTimeout(() => {
        parent.removeChild(element);
      }, 2000);
    }

    //eslint-disable-next-line @typescript-eslint/no-unused-vars
    steps.forEach((step, index) => {
      const divElement = document.createElement('div');
      let columnCount = 1;
      divElement.setAttribute('key', step.id + Date.now());
      if (step.parameter.game.cardsPlayed) {
        const mostExpensiveCards = this.calculateMostExpensiveCards(
          step.parameter.game.cardsPlayed
        );
        for (const card of mostExpensiveCards) {
          const imgSource =
            shopItGameConfig.gameValues.spriteFolder + card.name + '.png';

          const imgElement = document.createElement('img');
          imgElement.setAttribute('src', imgSource);
          imgElement.style.objectFit = 'contain';

          divElement.appendChild(imgElement);
          columnCount += 1;
        }
      }
      divElement.style.columns = columnCount + '';
      parent.appendChild(divElement);

      divElement.style.animationDuration =
        this.animationTimeInSeconds + 's !important';
      //divElement.classList.add('animateMoveLeftRight');
      divElement.classList.add('shopItAnimatedContainer');
    });
  }

  createElementsFindit(
    //eslint-disable-next-line @typescript-eslint/no-unused-vars
    steps: TaskParticipantIterationStep[],
    //eslint-disable-next-line @typescript-eslint/no-unused-vars
    parent: HTMLElement
  ) {
    return null;
  }

  calculateMostExpensiveCards(cardsArray: Card[]): Card[] {
    const cards: any = [];

    const clothing = cardsArray.filter((card) => card.category === 'clothing');
    const electronics = cardsArray.filter(
      (card) => card.category === 'electronics'
    );
    const food = cardsArray.filter((card) => card.category === 'food');

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

    console.log(clothingItem);
    if (clothingItem) {
      clothingItem.infoKey = this.$t(
        'module.playing.shopit.participant.cardCategories.clothing'
      );
    }

    console.log(electronicsItem);
    if (electronicsItem) {
      electronicsItem.infoKey = this.$t(
        'module.playing.shopit.participant.cardCategories.electronics'
      );
    }

    console.log(foodItem);
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

  get vehicleList(): VehicleData[] {
    const list: VehicleData[] = [];
    for (const category of Object.keys(moveItGameConfig.vehicles)) {
      list.push(
        ...moveItGameConfig.vehicles[category].types.map((vehicle) => {
          return {
            vehicle: vehicle,
            category: category,
            animation: this.getAnimationForVehicle(vehicle),
          };
        })
      );
    }
    return list;
  }

  getAnimationForVehicle(vehicle: any): PIXI.Texture[] {
    if (this.vehicleSpritesheet) {
      const animationName = vehicle.image.slice(0, -4);
      return this.vehicleSpritesheet.animations[animationName];
    }
    return [];
  }
}
</script>

<style lang="scss" scoped>
#analyticsContainer {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  .contentBanner {
    height: 20%;
    width: 100%;
    display: flex;
    flex-direction: row;
    overflow: hidden;
  }
  .contentMain {
    padding-bottom: 0.5rem;
    height: 80%;
    width: 100%;
    display: flex;
    flex-direction: row;
  }
}

.contentAnimation {
  position: relative;
  height: 100%;
  width: 80%;
}

.contentModule {
  height: 100%;
  width: 80%;
  overflow: auto;
}

.contentSide {
  height: 100%;
  width: 30%;
}

.allAnimationContainer {
  position: relative;
  width: 100%;
  height: 100%;
  overflow: hidden;

  background-color: var(--color-informing-light);
  .el-image {
    object-fit: contain;
    height: 100%;
    width: 100%;
    z-index: 0;
  }
}

.animationBackground {
  position: absolute;
  display: flex;
  flex-direction: column;
  width: 100%;
  height: 100%;
  justify-content: flex-end;
  align-items: center;
  bottom: 0;
  .backgroundItem {
    position: absolute;
  }
  #street {
    bottom: 0;
    height: 12%;
    width: 100%;
    z-index: 5;
  }
  #hills {
    bottom: 11%;
    height: 20%;
    width: 100%;
    z-index: 2;
  }
  #city {
    bottom: 11.5%;
    width: auto;
    height: 55%;
    z-index: 3;
    display: flex;
    justify-content: center;
    align-items: flex-end;

    .el-image {
      object-fit: contain;
      max-height: 100%;
      width: 100%;
    }
  }
  #birds {
    z-index: 100;
  }
}

#coolit {
  height: 45%;
  position: absolute;
  top: 0;
  width: 100%;
  background-size: cover;
  z-index: 1;
}

#moveit {
  position: absolute;
  bottom: 0;
  width: 100%;
  height: 12%;
  z-index: 10;
  display: flex;
  align-items: center;
}

#shopit {
  position: absolute;
  border: 5px solid var(--color-evaluating-dark);
  padding: 0.5rem 5px;
  background-color: var(--color-structuring-light);
  width: 15rem;
  height: 10rem;
  border-radius: var(--border-radius-small);
  bottom: 14%;
  right: 12.5%;
  z-index: 4;
  filter: drop-shadow(-10px 0 0 var(--color-brown));
  overflow: hidden;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  .billboardText {
    color: var(--color-evaluating-dark);
    font-weight: var(--font-weight-bold);
    filter: drop-shadow(-1px 0 0 var(--color-brown));
  }
}

.HighScoreContainer {
  width: 100%;
  height: 100%;
  background-color: var(--color-background);
  padding: 0.5rem;
  overflow-y: auto;
  text-align: center;
  .highScoreHeading {
    font-size: var(--font-size-xlarge);
    font-weight: var(--font-weight-bold);
    text-align: center;
    margin-bottom: 1rem;
  }
}

.birdsFlyingLeftRightAnimation {
  animation: birdFlyingLeftRight 15s linear forwards;
}

@keyframes birdFlyingLeftRight {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.el-carousel {
  height: calc(100% - var(--font-size-xlarge));
}

.media-right {
  width: 30%;
}

.qrcode {
  padding-right: 0.5rem;
  font-size: calc(1.8px * var(--qr-code-size) / var(--connection-key-length));
  font-family: monospace;
  svg {
    display: flex;
  }
}

.media-content {
  margin: auto;
}

h1 {
  font-weight: var(--font-weight-bold);
  font-size: var(--font-size-xlarge);
  color: var(--color-primary);
  text-align: center;
}
</style>

<style lang="scss">
.animateMoveLeftRight {
  animation: moveLeftRight 300s forwards linear !important;
}

@keyframes moveLeftRight {
  0% {
    transform: translateX(-75%);
  }
  100% {
    transform: translateX(100%);
  }
}

.coolItAnimatedContainer {
  position: absolute;
  height: 40% !important;
  width: 100%;
  left: 0;
  top: 0;
  box-sizing: border-box;
  display: flex;
  justify-content: flex-start;
  align-items: flex-start;
  animation: moveLeftRight 30s forwards linear !important;
  img {
    position: relative;
    height: 100%;
    width: 100%;
    object-fit: contain;
  }
}

.moveItAnimatedContainer {
  position: absolute;
  height: auto;
  width: 100%;
  left: 0;
  bottom: 25%;
  box-sizing: border-box;
  display: flex;
  justify-content: center;
  align-items: flex-end;
  animation: moveLeftRight 30s forwards linear !important;
  .drivingStats {
    position: absolute;
    top: -80%;
    padding: 0.5rem;
    background-color: var(--color-structuring-light);
    border-radius: var(--border-radius-xs);
    border: 2px solid var(--color-evaluating-dark);
  }
}

/*.shopItAnimatedContainer {
  position: absolute;
  height: 65% !important;
  width: auto;
  min-width: 100%;
  box-sizing: border-box;
  display: flex;
  justify-content: flex-start;
  flex-wrap: nowrap;
  flex-direction: row;
  align-items: center;
  gap: 0.5rem;
  animation: moveLeftRightShopIt 200s forwards linear !important;
  img {
    position: relative;
    height: 100%;
    width: auto;
    object-fit: contain;
    border: 2px solid var(--color-evaluating-dark);
    border-radius: var(--border-radius-xs);
  }
}*/
.shopItAnimatedContainer {
  position: absolute;
  height: 70%;
  width: 95%;
  column-gap: 0.2rem;
  bottom: 0.5rem;
  opacity: 0;
  background-color: var(--color-structuring-light);
  animation: appear 2s ease forwards;
  img {
    object-fit: contain;
    border: 2px solid var(--color-evaluating-dark);
    border-radius: var(--border-radius-xs);
  }
}

@keyframes appear {
  0% {
    opacity: 0;
  }
  100% {
    opacity: 1;
  }
}
</style>
