<template>
  <div id="analyticsContainer">
    <div class="contentTop"></div>
    <div class="contentBottom">
      <div class="contentLeft">
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
            <div id="hills" class="backgroundItem">
              <el-image :src="'/assets/animations/analytics/hills.png'" />
            </div>
            <div id="street" class="backgroundItem">
              <el-image :src="'/assets/animations/analytics/Street.png'" />
            </div>
          </div>
        </div>
      </div>
      <div class="contentRight"></div>
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
import { hasModule } from '@/modules';
import ModuleComponentType from '@/modules/ModuleComponentType';
import TaskType from '@/types/enum/TaskType';
import * as taskService from '@/services/task-service';
import * as taskParticipantService from '@/services/task-participant-service';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import { getRandomColorList } from '@/utils/colors';
import * as pixiUtil from '@/utils/pixi';
import * as PIXI from 'pixi.js';
import {createApp, h } from 'vue';
import shopItGameConfig from '@/modules/playing/shopit/data/gameConfig.json';
import moveItGameConfig from '@/modules/playing/moveit/data/gameConfig.json';
import SpriteCanvas from '@/components/shared/atoms/game/SpriteCanvas.vue';
import TaskParticipantIterationStatesType from '@/types/enum/TaskParticipantIterationStatesType';
import { delay } from '@/utils/wait';
import { registerDomElement } from '@/vunit';

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
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
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
  vehicleWidth = 150;
  vehicleHeight = 200;
  cloudFolderPath = '/assets/animations/analytics/clouds/';

  tasks: Task[] = [];
  gameTasks: Task[] = [];

  animationTimeInSeconds = 30;

  steps: {
    module: string;
    steps: TaskParticipantIterationStep[];
    newSteps: TaskParticipantIterationStep[];
  }[] = [];

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
  }

  get topicId(): string | null {
    if (this.task) return this.task.topicId;
    return null;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.topicId) {
      taskService.registerGetTaskList(
        this.topicId,
        this.updateTasks,
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
    console.log(this.steps);
  }

  updateTasks(tasks: Task[], topicId: string): void {
    this.tasks = tasks;
    this.gameTasks = this.tasks
      .filter((task) => task.taskType === 'PLAYING')
      .sort();
  }

  handleNewEntries(taskId: string, steps: TaskParticipantIterationStep[]) {
    const refArray = this.$refs[taskId];

    // Ensure the refArray is not undefined and is an array
    if (Array.isArray(refArray) && refArray.length > 0) {
      const element = refArray[0] as HTMLElement; // Assuming you want to target the first element

      switch (taskId) {
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
    taskId: string,
    steps: TaskParticipantIterationStep[]
  ): void {
    let newEntries: TaskParticipantIterationStep[] = [];
    if (this.steps.find((entry) => entry.module === taskId)) {
      newEntries = this.findNewIterations(
        this.steps.find((entry) => entry.module === taskId)?.steps,
        steps
      );
      if (newEntries.length > 0) {
        this.handleNewEntries(taskId, newEntries);
      }
    }

    const stepsEntry = { module: taskId, steps: steps, newSteps: newEntries };
    if (
      this.steps.findIndex((entry) => entry.module === stepsEntry.module) !=
        -1 &&
      stepsEntry.newSteps.length > 0
    ) {
      this.steps[
        this.steps.findIndex((entry) => entry.module === stepsEntry.module)
      ] = stepsEntry;
    } else if (
      this.steps.findIndex((entry) => entry.module === stepsEntry.module) === -1
    ) {
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
  createElementsMoveit(steps: TaskParticipantIterationStep[], parent: HTMLElement) {
    steps.forEach((step, index) => {
      const divElement = document.createElement('div');
      divElement.setAttribute('key', step.id + Date.now());

      // Dynamically create a Vue app instance for the SpriteCanvas component
      const vehicle = this.getVehicleByType(step.parameter.vehicle.type);
      console.log(vehicle);
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

      parent.appendChild(divElement);

      // Apply styles and classes
      divElement.style.animationDuration = this.animationTimeInSeconds + 's !important';
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
    return this.vehicleList[this.vehicleList.findIndex((vehicleEntry) => vehicleEntry.vehicle.name === type)];
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
    steps: TaskParticipantIterationStep[],
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
  .contentTop {
    height: 20%;
    width: 100%;
    border: 1px solid magenta;
  }
  .contentBottom {
    height: 80%;
    width: 100%;
    display: flex;
    flex-direction: row;
    .contentLeft {
      position: relative;
      height: 100%;
      width: 80%;
    }
    .contentRight {
      height: 100%;
      width: 20%;
      border: 1px solid brown;
    }
  }
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
  height: 40% !important;
  width: 100%;
  left: 0;
  bottom: 25%;
  box-sizing: border-box;
  display: flex;
  justify-content: center;
  align-items: flex-end;
  animation: moveLeftRight 30s forwards linear !important;
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
