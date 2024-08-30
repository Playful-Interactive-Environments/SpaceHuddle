<template>
  <div id="analyticsContainer">
    <div class="contentMain">
      <div ref="contentAnimation" class="contentAnimation">
        <div v-if="steps.length > 0" class="allAnimationContainer">
          <Application
            ref="pixi"
            :width="canvasWidth"
            :height="canvasHeight"
            backgroundColor="#a0d4d9"
          >
            <container v-if="dashboardSpritesheet">
              <sprite
                :texture="dashboardSpritesheet.textures['hills.png']"
                :width="canvasWidth"
                :height="
                  getTextureHeight(dashboardSpritesheet.textures['hills.png'])
                "
                :anchor="[0, 1]"
                :x="0"
                :y="
                  canvasHeight -
                  getTextureHeight(dashboardSpritesheet.textures['Street.png'])
                "
              />
              <sprite
                :texture="dashboardSpritesheet.textures['Street.png']"
                :width="canvasWidth"
                :height="
                  getTextureHeight(dashboardSpritesheet.textures['Street.png'])
                "
                :anchor="[0, 1]"
                :x="0"
                :y="canvasHeight"
              />
              <sprite
                :texture="dashboardSpritesheet.textures['City.png']"
                :width="
                  getTextureWidth(dashboardSpritesheet.textures['City.png']) *
                  0.55
                "
                :height="canvasHeight * 0.55"
                :anchor="[0.5, 1]"
                :x="canvasWidth / 2"
                :y="
                  canvasHeight -
                  getTextureHeight(dashboardSpritesheet.textures['Street.png'])
                "
              />
            </container>
            <container v-if="dashboardSpritesheet">
              <container
                v-for="(step, index) in coolItAnimations"
                :key="step.id"
              >
                <sprite
                  v-if="step.texture"
                  :texture="step.texture"
                  :animation-speed="0.1"
                  :width="getTextureWidth(step.texture) * 0.4"
                  :height="canvasHeight * 0.4"
                  :anchor="[0.5, 0]"
                  :x="cloudX[index]"
                  :y="0"
                />
                <External tag="div">
                  <div
                    class="infoTop"
                    :style="{
                      '--x': `${cloudX[index]}px`,
                      '--y': `${canvasHeight * 0.1}px`,
                    }"
                  >
                    <p>
                      {{
                        $t(
                          `module.common.visualisation_master.visModules.analytics.module.coolItStats.temperature`
                        )
                      }}: {{ Math.round(step.parameter.state.temperature) }}
                      °C
                    </p>
                    <p>
                      {{
                        $t(
                          `module.common.visualisation_master.visModules.analytics.module.coolItStats.normalisedTime`
                        )
                      }}: {{ Math.round(step.parameter.state.normalisedTime) }}
                    </p>
                    <p>
                      {{
                        $t(
                          `module.common.visualisation_master.visModules.analytics.module.coolItStats.moleculeHitCount`
                        )
                      }}:
                      {{ step.parameter.state.moleculeHitCount }}
                    </p>
                  </div>
                </External>
              </container>
            </container>
            <container v-if="vehicleSpritesheet">
              <container
                v-for="(step, index) in moveItAnimations"
                :key="step.id"
              >
                <animated-sprite
                  v-if="step.animation.length > 0"
                  :textures="step.animation"
                  :animation-speed="0.1"
                  :width="getTextureWidth(step.animation[0]) * 0.1"
                  :height="canvasHeight * 0.1"
                  playing
                  :anchor="[0, 1]"
                  :x="vehicleX[index]"
                  :y="
                    canvasHeight -
                    getTextureHeight(
                      dashboardSpritesheet.textures['Street.png']
                    ) /
                      2
                  "
                />
                <External tag="div">
                  <div
                    class="info"
                    :style="{
                      '--x': `${vehicleX[index]}px`,
                      '--y': `${
                        getTextureHeight(
                          dashboardSpritesheet.textures['Street.png']
                        ) +
                        canvasHeight * 0.1
                      }px`,
                    }"
                  >
                    <p>
                      {{
                        $t(
                          `module.common.visualisation_master.visModules.analytics.module.drivingStats.maxSpeed`
                        )
                      }}: {{ Math.round(step.parameter.drive.maxSpeed) }}
                      {{ $t('module.playing.moveit.enums.units.km/h') }}
                    </p>
                    <p>
                      {{
                        $t(
                          `module.common.visualisation_master.visModules.analytics.module.drivingStats.avgSpeed`
                        )
                      }}: {{ Math.round(step.parameter.drive.averageSpeed) }}
                      {{ $t('module.playing.moveit.enums.units.km/h') }}
                    </p>
                    <p>
                      {{
                        $t(
                          `module.common.visualisation_master.visModules.analytics.module.drivingStats.distance`
                        )
                      }}:
                      {{
                        Math.round(step.parameter.drive.drivenPathLength * 10) /
                        10
                      }}
                      {{ $t('module.playing.moveit.enums.units.km') }}
                    </p>
                    <p>
                      {{
                        $t(
                          `module.common.visualisation_master.visModules.analytics.module.drivingStats.consumption`
                        )
                      }}:
                      {{
                        Math.round(step.parameter.drive.consumption * 100) / 100
                      }}
                      {{ $t('module.playing.moveit.enums.units.liters') }}
                    </p>
                  </div>
                </External>
              </container>
            </container>
            <External tag="div">
              <div
                v-for="stepCategory of steps"
                :key="stepCategory.module"
                :id="stepCategory.module"
                class="animationContainer"
                :ref="stepCategory.module"
              >
                <div v-if="stepCategory.module === 'shopit'">
                  <p class="billboardText">
                    {{
                      $t(
                        'module.common.visualisation_master.visModules.analytics.module.billboard'
                      )
                    }}
                  </p>
                  <img
                    v-for="(item, index) in mostExpensiveCards"
                    :key="index"
                    :src="getCardImage(item)"
                    :alt="item.cardName"
                  />
                </div>
              </div>
            </External>
          </Application>
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
      <div v-if="session" class="contentSide media">
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
import shopItGameConfig from '@/modules/playing/shopit/data/gameConfig.json';
import moveItGameConfig from '@/modules/playing/moveit/data/gameConfig.json';
import Gallery from '@/modules/common/visualisation_master/organisms/gallery.vue';

import coolItHighScore from '@/modules/playing/coolit/organisms/Highscore.vue';
import moveItHighScore from '@/modules/playing/moveit/organisms/Highscore.vue';
import shopItHighScore from '@/modules/playing/shopit/organisms/Highscore.vue';
import findItHighScore from '@/modules/playing/findit/organisms/Highscore.vue';

import SpriteCanvas from '@/components/shared/atoms/game/SpriteCanvas.vue';
import { delay, until } from '@/utils/wait';
import { Session } from '@/types/api/Session';
import * as themeColors from '@/utils/themeColors';
import QrcodeVue from 'qrcode.vue';
import { Application, External } from 'vue3-pixi';
import * as TWEEDLE from 'tweedle.js';

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
    Application,
    External,
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

  textureTokenVehicles = pixiUtil.createLoadingToken();
  textureTokenDashboad = pixiUtil.createLoadingToken();
  vehicleSpritesheet: PIXI.Spritesheet | null = null;
  dashboardSpritesheet: PIXI.Spritesheet | null = null;

  tasks: Task[] = [];
  gameTasks: Task[] = [];
  session: Session | null = null;

  animationTimeInSeconds = 30;
  componentLoadIndex = 0;
  bannerHeight = 100;
  canvasWidth = 100;
  canvasHeight = 100;

  steps: {
    module: string;
    steps: TaskParticipantIterationStep[];
    newSteps: TaskParticipantIterationStep[];
  }[] = [];

  moveItAnimations: {
    id: string;
    parameter: any;
    animation: PIXI.Texture[];
    position: {
      x: number;
    };
    tween: TWEEDLE.Tween<{ x: number }>;
  }[] = [];
  vehicleX: number[] = [];

  coolItAnimations: {
    id: string;
    parameter: any;
    texture: PIXI.Texture | undefined;
    position: {
      x: number;
    };
    tween: TWEEDLE.Tween<{ x: number }>;
  }[] = [];
  cloudX: number[] = [];

  mostExpensiveCards: Card[] = [];

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
    return this.bannerHeight * 0.7;
  }

  getTextureHeight(texture: PIXI.Texture): number {
    return this.canvasWidth / pixiUtil.getTextureAspect(texture);
  }

  getTextureWidth(texture: PIXI.Texture): number {
    return this.canvasHeight * pixiUtil.getTextureAspect(texture);
  }

  getCardImage(card: Card): string {
    return shopItGameConfig.gameValues.spriteFolder + card.name + '.png';
  }

  async mounted(): Promise<void> {
    until(() => this.$refs.pixi).then(() => {
      const app = (this.$refs.pixi as any).app as PIXI.Application;
      app.ticker.add(() => TWEEDLE.Group.shared.update());
    });

    pixiUtil
      .loadTexture(
        '/assets/animations/analytics/gameDashboard.json',
        this.textureTokenDashboad
      )
      .then(async (sheet) => {
        this.dashboardSpritesheet = sheet;
      });
    pixiUtil
      .loadTexture(
        '/assets/games/moveit/vehicle/vehicle_animation.json',
        this.textureTokenVehicles
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
    this.calcSize();
    await delay(2000);
    this.calcSize();
  }

  calcSize(): void {
    const banner = this.$refs.contentBanner as HTMLElement;
    if (banner) {
      this.bannerHeight = banner.clientHeight;
    }

    const contentAnimation = this.$refs.contentAnimation as HTMLElement;
    if (contentAnimation) {
      this.canvasWidth = contentAnimation.clientWidth;
      this.canvasHeight = contentAnimation.clientHeight;
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
    pixiUtil.cleanupToken(this.textureTokenVehicles);
    pixiUtil.cleanupToken(this.textureTokenDashboad);
  }

  getModuleName(task: Task): string[] {
    if (task && task.modules && task.modules.length > 0)
      return task.modules.map((module) => module.name);
    return ['default'];
  }

  getModuleSteps(moduleName: string): TaskParticipantIterationStep[] {
    const data = this.steps.find((item) => item.module === moduleName);
    if (data) return data.newSteps;
    return [];
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

  updateIterationSteps(
    moduleName: string,
    steps: TaskParticipantIterationStep[]
  ): void {
    let newEntries: TaskParticipantIterationStep[] = [];
    const step = this.steps.find((entry) => entry.module === moduleName);
    if (step) {
      newEntries = this.findNewIterations(step.steps, steps);
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
      if (moduleName === 'coolit' || moduleName === 'moveit') {
        this.steps[index].steps = stepsEntry.steps;
        this.steps[index].newSteps.push(...stepsEntry.newSteps);
      } else {
        this.steps[index] = stepsEntry;
      }
    } else if (index === -1) {
      this.steps.push(stepsEntry);
    }
    this.setupCoolIt();
    this.setupMoveIt();
    this.setupShopIt();
  }

  setupCoolIt(): void {
    const moduleName = 'coolit';
    const coolIt = this.getModuleSteps(moduleName);
    this.coolItAnimations = coolIt.map((item) => {
      const stars = item.parameter.gameplayResult.stars;
      let textureName = `DarkCloud_${Math.round(Math.random())}.png`;
      if (stars === 2)
        textureName = `MidCloud_${Math.round(Math.random())}.png`;
      else if (stars === 3)
        textureName = `LightCloud_${Math.round(Math.random())}.png`;
      const texture = this.dashboardSpritesheet?.textures[textureName];
      const startX = texture ? -this.getTextureWidth(texture) * 0.4 : 0;
      const position = {
        x: startX,
      };
      const tween = new TWEEDLE.Tween(position)
        .easing(TWEEDLE.Easing.Quadratic.InOut)
        .from({ x: startX / 2 })
        .to(
          { x: this.canvasWidth - startX / 2 },
          this.animationTimeInSeconds * 1000
        )
        .onComplete(() => this.animationend(moduleName, item.id))
        .onUpdate(() => this.animationUpdate(moduleName))
        .start();
      return {
        id: item.id,
        parameter: item.parameter,
        texture: texture,
        position: position,
        tween: tween,
      };
    });
    this.cloudX = this.coolItAnimations.map((item) => item.position.x);
  }

  setupMoveIt(): void {
    const moduleName = 'moveit';
    const moveIt = this.getModuleSteps(moduleName);
    this.moveItAnimations = moveIt.map((item) => {
      const animation = this.getVehicleByType(
        item.parameter.vehicle.type
      ).animation;
      const startX = -this.getTextureWidth(animation[0]) * 0.1;
      const position = {
        x: startX,
      };
      const tween = new TWEEDLE.Tween(position)
        .easing(TWEEDLE.Easing.Quadratic.InOut)
        .from({ x: startX })
        .to({ x: this.canvasWidth }, this.animationTimeInSeconds * 1000)
        .onComplete(() => this.animationend(moduleName, item.id))
        .onUpdate(() => this.animationUpdate(moduleName))
        .start();
      return {
        id: item.id,
        parameter: item.parameter,
        animation: animation,
        position: position,
        tween: tween,
      };
    });
    this.vehicleX = this.moveItAnimations.map((item) => item.position.x);
  }

  setupShopIt(): void {
    const moduleName = 'shopit';
    const shopIt = this.getModuleSteps(moduleName);
    this.mostExpensiveCards = [];
    for (const step of shopIt) {
      this.mostExpensiveCards.push(
        ...this.calculateMostExpensiveCards(step.parameter.game.cardsPlayed)
      );
    }
  }

  animationUpdate(moduleName: string): void {
    if (moduleName === 'moveit') {
      this.vehicleX = this.moveItAnimations.map((item) => item.position.x);
    }
    if (moduleName === 'coolit') {
      this.cloudX = this.coolItAnimations.map((item) => item.position.x);
    }
  }

  animationend(moduleName: string, id: string): void {
    if (moduleName === 'moveit') {
      const index = this.moveItAnimations.findIndex((item) => item.id === id);
      if (index > -1) {
        this.moveItAnimations.splice(index, 1);
      }
    }
    if (moduleName === 'coolit') {
      const index = this.coolItAnimations.findIndex((item) => item.id === id);
      if (index > -1) {
        this.coolItAnimations.splice(index, 1);
      }
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

  getVehicleByType(type: string) {
    return this.vehicleList[
      this.vehicleList.findIndex(
        (vehicleEntry) => vehicleEntry.vehicle.name === type
      )
    ];
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
  div {
    height: 100%;
  }
  img {
    object-fit: contain;
    border: 2px solid var(--color-evaluating-dark);
    border-radius: var(--border-radius-xs);
    width: 30%;
    height: 40%;
    margin-top: 0.8rem;
    margin-right: 0.3rem;
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

.info {
  position: absolute;
  z-index: 20;
  width: auto;
  bottom: var(--y);
  left: var(--x);
  padding: 0.5rem;
  background-color: var(--color-structuring-light);
  border-radius: var(--border-radius-xs);
  border: 2px solid var(--color-evaluating-dark);
}

.infoTop {
  position: absolute;
  z-index: 20;
  width: auto;
  top: var(--y);
  left: var(--x);
  padding: 0.5rem;
  background-color: var(--color-structuring-light);
  border-radius: var(--border-radius-xs);
  border: 2px solid var(--color-evaluating-dark);
}

h1 {
  font-weight: var(--font-weight-bold);
  font-size: var(--font-size-xlarge);
  color: var(--color-primary);
  text-align: center;
}
</style>
