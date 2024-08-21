<template>
  <div
    ref="gameArea"
    class="gameArea result"
    :style="{
      height: height,
      '--game-area-width': `${gameAreaSize[0]}px`,
      '--game-area-height': `${gameAreaSize[1]}px`,
    }"
  >
    <div v-if="hasWon">
      <h2 class="heading heading--medium">
        {{ $t('module.playing.findit.participant.win') }}
      </h2>
      <p>
        {{ $t('module.playing.findit.participant.winText') }}
      </p>
    </div>
    <div v-else>
      <h2 class="heading heading--medium">
        {{ $t('module.playing.findit.participant.lost') }}
      </h2>
      <p>
        {{ $t('module.playing.findit.participant.lostText') }}
      </p>
    </div>
    <draggable
      class="endObjects"
      v-if="this.endObjects.length !== 0"
      v-model="endObjects"
      group="item"
      item-key="name"
    >
      <template v-slot:item="{ element }">
        <div
          :id="`${element.type}#${element.name}`"
          class="endObject"
          :class="{
            objectContainerActive: activeObjectId === element.name,
          }"
          @click="activeObjectChanged(element, element.name, true)"
          @touchstart="activeObjectChanged(element, element.name, true)"
        >
          <p class="objectName">
            {{
              $t(
                `module.playing.findit.participant.placeables.${levelType}.${
                  element.type
                }.${getExplanationKey(element)}.name`
              )
            }}
          </p>
          <el-image
            v-if="
              levelTypeImages[element.type] &&
              levelTypeImages[element.type][element.name]
            "
            :src="levelTypeImages[element.type][element.name]"
            :alt="element.name"
            class="endObjectSprites"
            fit="contain"
          />
        </div>
      </template>
    </draggable>
    <div class="score heading--medium">
      <p>
        <span>
          {{ this.correctClassified.length }}
          <font-awesome-icon icon="circle-check" />
          / {{ this.endObjectCount }}
          <font-awesome-icon icon="trophy" />
        </span>
      </p>
    </div>
    <div class="classificationButtons grid" v-if="this.endObjectCount !== 0">
      <el-button
        v-for="key in this.collectKeys"
        :key="key"
        class="classificationButton -date-table-cell__text"
        @click="checkType(key, activeObjectId, activeObject?.type)"
      >
        <draggable
          :id="key"
          v-model="collectedItem[key]"
          item-key="name"
          group="item"
          class="classifiedObject"
          handle=".handle"
          @add="addToClassification(key, $event)"
        >
          <template #header>
            <div class="classificationType">
              <font-awesome-icon :icon="getClassificationIcon(key)" />
              {{ $t(`module.playing.findit.participant.collectKey.${key}`) }}
            </div>
          </template>
          <template v-slot:item="{ element }">
            <div
              :id="`${element.type}#${element.name}`"
              class="endObject"
              :class="{
                endObjectCorrect: isCorrectClassified(element.name),
                endObjectIncorrect:
                  !isCorrectClassified(element.name) &&
                  isClassified(element.name),
              }"
            >
              <el-image
                v-if="
                  levelTypeImages[element.type] &&
                  levelTypeImages[element.type][element.name]
                "
                :src="levelTypeImages[element.type][element.name]"
                :alt="element.name"
                fit="contain"
              />
            </div>
          </template>
        </draggable>
      </el-button>
    </div>
    <el-dialog v-if="activeObject" v-model="showInfo">
      <template #header>
        <p
          class="objectName"
          :class="{
            correctClassificationIcon: isCorrectClassified(activeObject.name),
            wrongClassificationIcon:
              isClassified(activeObject.name) &&
              !isCorrectClassified(activeObject.name),
          }"
        >
          <font-awesome-icon
            :icon="
              gameConfig[levelType].categories[activeObject.type].settings.icon
            "
          />
          {{
            $t(
              `module.playing.findit.participant.placeables.${levelType}.${
                activeObject.type
              }.${getExplanationKey(activeObject)}.name`
            )
          }}
        </p>
      </template>
      <el-image
        v-if="
          levelTypeImages[activeObject.type] &&
          levelTypeImages[activeObject.type][activeObject.name]
        "
        :src="levelTypeImages[activeObject.type][activeObject.name]"
        :alt="activeObject.name"
        fit="contain"
      />
      <div class="infoText" v-if="this.endObjectCount !== 0">
        <p
          class="marginTop"
          v-if="
            this.activeObject !== null &&
            this.classified.includes(this.activeObjectId)
          "
        >
          {{
            $t(
              `module.playing.findit.participant.placeables.${levelType}.${
                this.activeObject.type
              }.${getExplanationKey(this.activeObject)}.description`
            )
          }}
        </p>
      </div>
    </el-dialog>
    <el-button
      class="el-button--submit returnButton"
      @click="this.$emit('replayFinished', classified, correctClassified)"
      v-if="
        endObjects.map((x) => x.name).every((x) => classified.includes(x)) ||
        this.endObjectCount === 0
      "
    >
      {{ $t('module.playing.findit.participant.returnToMenu') }}
    </el-button>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { PlayStateResult } from '@/modules/playing/findit/organisms/PlayState.vue';
import * as placeable from '@/types/game/Placeable';
import * as pixiUtil from '@/utils/pixi';
import { Idea } from '@/types/api/Idea';
import * as configParameter from '@/utils/game/configParameter';
import gameConfig from '@/modules/playing/findit/data/gameConfig.json';
import { registerDomElement, unregisterDomElement } from '@/vunit';
import draggable from 'vuedraggable';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

@Options({
  components: { FontAwesomeIcon, draggable },
  emits: ['replayFinished'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class CollectedState extends Vue {
  @Prop({ default: null }) readonly level!: Idea | null;
  @Prop({ default: '100%' }) readonly height!: string;
  @Prop() readonly playStateResult!: PlayStateResult;

  gameWidth = window.innerWidth;
  gameHeight = window.innerHeight;

  levelType = '';
  levelTypeImages: { [key: string]: { [key: string]: string } } = {};
  textureToken = pixiUtil.createLoadingToken();

  endObjects: placeable.PlaceableBase[] = [];
  objectList: placeable.PlaceableBase[] = [];
  endObjectCount = 0;
  activeObjectId = '';
  activeObject: placeable.PlaceableBase | null = null;
  showInfo = false;

  correctClassified: string[] = [];
  classified: string[] = [];
  gameConfig = gameConfig;

  collectKeys: string[] = [];
  collectedItem: { [key: string]: placeable.PlaceableBase[] } = {};

  //#region get / set
  get hasWon(): boolean {
    return this.playStateResult.collected === this.playStateResult.total;
  }

  get gameConfigTypes(): string[] {
    return configParameter.getGameConfigTypes(
      gameConfig as any,
      this.levelType
    );
  }

  getCollectKeys(): string[] {
    const keys: string[] = [];
    for (const category in gameConfig[this.levelType].categories) {
      for (const item in gameConfig[this.levelType].categories[category]
        .items) {
        keys.push(
          gameConfig[this.levelType].categories[category].items[item].collectKey
        );
      }
    }
    return Array.from(new Set(keys)).filter((d) => d != null);
  }

  checkType(key: string, objectId: string, objectType: string) {
    const index = this.endObjects.findIndex((item) => item.name === objectId);
    if (index > -1) {
      this.collectedItem[key].push(this.endObjects[index]);
      this.endObjects.splice(index, 1);
    }
    if (objectType) {
      if (
        gameConfig[this.levelType].categories[objectType].items[objectId]
          .collectKey === key
      ) {
        if (
          !this.classified.includes(objectId) &&
          !this.correctClassified.includes(objectId)
        ) {
          this.correctClassified.push(objectId);
          this.classified.push(objectId);
          this.showInfo = true;
          return true;
        }
      }
      if (!this.classified.includes(objectId)) {
        this.classified.push(objectId);
        this.showInfo = true;
      }
      return false;
    }
  }

  isClassified(id: string): boolean {
    return this.classified.includes(id);
  }

  isCorrectClassified(id: string): boolean {
    return this.correctClassified.includes(id);
  }

  checkAllAnswered() {
    for (let i = 0; i < this.endObjects.length; i++) {
      if (!this.correctClassified.includes(this.endObjects[i].name)) {
        return false;
      }
    }
    return true;
  }

  getEndObjects(): placeable.PlaceableBase[] {
    const uniqueNamesSet = new Set();
    const list = [
      ...this.playStateResult.itemList,
      ...this.playStateResult.redHerringList,
    ];
    return list.filter((obj) => {
      if (!uniqueNamesSet.has(obj.name)) {
        uniqueNamesSet.add(obj.name);
        return true;
      }
      return false;
    });
  }

  getClassificationIcon(key: string): string {
    switch (key) {
      case 'dispose':
        return 'dumpster';
      case 'recycle':
        return 'recycle';
      case 'leave':
        return 'tree';
      case 'rescue':
        return 'truck-medical';
    }
    return 'dumpster';
  }
  //#endregion get / set

  //#region watch
  @Watch('level', { immediate: true })
  async onLevelChanged(): Promise<void> {
    if (this.level) {
      this.levelType = this.level.parameter.type
        ? this.level.parameter.type
        : configParameter.getDefaultLevelType(gameConfig as any);
    }
  }

  @Watch('levelType', { immediate: true })
  onLevelTypeChanged(): void {
    if (this.levelType) {
      for (const typeName of this.gameConfigTypes) {
        const settings =
          gameConfig[this.levelType].categories[typeName].settings;
        setTimeout(() => {
          if (settings && settings.spritesheet) {
            pixiUtil
              .loadTexture(settings.spritesheet, this.textureToken)
              .then((sheet) => {
                this.levelTypeImages[typeName] = {};
                pixiUtil.convertSpritesheetToBase64(
                  sheet,
                  this.levelTypeImages[typeName]
                );
              });
          }
        }, 100);
      }
    }
  }
  //#endregion watch

  //#region load / unload
  gameAreaSize: [number, number] = [0, 0];
  domKey = '';
  mounted(): void {
    this.objectList = this.getEndObjects();
    this.endObjects = this.shuffle([...this.objectList]);
    this.endObjectCount = this.endObjects.length;
    if (this.endObjectCount > 0) {
      this.collectKeys = this.getCollectKeys();
      for (const key of this.collectKeys) {
        this.collectedItem[key] = [];
      }
      this.activeObject = this.endObjects[0];
      this.activeObjectId = this.endObjects[0].name;
      this.activeObjectChanged(this.activeObject, this.activeObjectId);
    }
    this.domKey = registerDomElement(
      this.$refs.gameArea as HTMLElement,
      (targetWidth, targetHeight) => {
        this.gameAreaSize = [targetWidth, targetHeight];
      },
      500,
      false,
      () => {
        this.gameAreaSize = [0, 0];
      }
    );
  }

  unmounted(): void {
    pixiUtil.cleanupToken(this.textureToken);
    unregisterDomElement(this.domKey);
  }
  //#endregion load / unload

  //#region interaction
  activeObjectChanged(object, id, scroll = false) {
    if (this.activeObjectId !== id) {
      this.activeObjectId = id;
      this.activeObject = object;
      const element = document.getElementById(`${object.type}#${id}`);
      if (element && scroll) {
        element.scrollIntoView({
          behavior: 'smooth',
          block: 'center',
          inline: 'center',
        });
      }
    }
  }

  getExplanationKey(object: placeable.PlaceableBase): string {
    const placeableConfig =
      gameConfig[this.levelType].categories[object.type].items[object.name];
    return placeableConfig.explanationKey;
  }

  addToClassification(category: string, event: CustomEvent): void {
    const itemId = (event as any).item.id.split('#')[1];
    const itemType = (event as any).item.id.split('#')[0];
    const object = this.objectList.find(
      (item) => item.type === itemType && item.name === itemId
    );
    this.activeObjectChanged(object, itemId, true);
    this.checkType(category, itemId, itemType);
  }
  //#endregion interaction

  shuffle(array) {
    let currentIndex = array.length;
    let randomIndex;

    while (currentIndex > 0) {
      randomIndex = Math.floor(Math.random() * currentIndex);
      currentIndex--;

      [array[currentIndex], array[randomIndex]] = [
        array[randomIndex],
        array[currentIndex],
      ];
    }
    return array;
  }
}
</script>

<style scoped lang="scss">
.gameArea {
  --game-area-width: var(--app-width);
  --game-area-height: var(--app-height);
  height: calc(100%);
  width: 100%;
  position: relative;
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

.result {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  font-size: var(--font-size-default);
  text-align: center;
  padding-top: 2rem;
}

.endObjects {
  --end-objects-width: var(--game-area-width);
  --end-objects-height: calc(var(--game-area-height) * 0.32 - 3rem);

  display: flex;
  justify-content: flex-start;
  align-items: center;
  //height: 32%;
  width: 100%;
  min-height: var(--end-objects-height);
  z-index: 10;
  overflow-x: scroll;
  overflow-y: hidden;
  -ms-overflow-style: none; /* IE and Edge */
  //scrollbar-width: none; /* Firefox */
  margin: 2rem 0;
  background-color: color-mix(in srgb, var(--color-primary) 20%, transparent);
  outline: 0.5rem solid var(--color-primary);

  .el-image {
    display: flex;
  }

  .el-image::v-deep(.el-image__inner) {
    height: auto;
  }
}

.endObject {
  --end-object-width: calc((var(--end-objects-width) - 1rem) / 3);
  --end-object-height: calc((var(--end-objects-height) - 2rem));

  position: relative;
  margin: 1.5rem 0.5rem 0.5rem 0.5rem;
  transition: 0.3s;
  padding: 0.5rem;
  border: 0.3rem solid var(--color-primary);
  background-color: var(--color-background);
  border-radius: var(--border-radius-small);
  height: var(--end-object-height);
  width: var(--end-object-width);
  vertical-align: center;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  flex: 0 0 auto;

  .el-image {
    width: 100%;
    height: 100%;
  }
}
.endObjectCorrect {
  background-color: var(--color-brainstorming-light);
}

.endObjectIncorrect {
  background-color: var(--color-evaluating-light);
}

.objectContainerActive {
  z-index: 2;
  transform: translateY(-1rem);
  transition: 0.3s;
  border-color: var(--color-playing);
}

.endObjectSprites {
  pointer-events: none;
  cursor: pointer;
  max-height: calc(var(--end-object-height) - 3rem);
  max-width: calc(var(--end-object-width) - 3rem);
  vertical-align: center;
  margin: auto;
}

.marginTop {
  margin-top: 1rem;
  //padding: 0 1rem;
  text-align: left;
}

.returnButton {
  position: absolute;
  bottom: 2rem;
}

.infoText {
  //height: 2rem;
  //transition: 0.3s;
}

.classificationButtons {
  width: 100%;
  /*display: flex;
  justify-content: center;
  align-content: center;
  flex-direction: row;*/
  padding: 0 0.3rem;
}

.classificationButton {
  width: 100%;
  height: 6.5rem;
  border: 3px solid var(--color-dark-contrast);
  color: var(--color-dark-contrast);
  margin: 0.3rem;
  border-radius: var(--border-radius-small);
  font-size: var(--font-size-default);
  font-weight: var(--font-weight-semibold);
  background-color: var(--color-background);
  padding: 0;
  position: relative;
  transform: translate(0, 0);

  .classifiedObject {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    width: 100%;
    height: 100%;
    overflow: auto;
    padding-top: 2.5rem;
  }

  .endObject {
    margin: 0.1rem;
    --end-object-width: 3rem;
    --end-object-height: 3rem;
    border: 1px solid var(--color-primary);

    .endObjectSprites {
      max-height: unset;
      max-width: unset;
      margin: unset;
    }
  }

  .objectName {
    display: none;
  }

  .objectContainerActive {
    transform: unset;
  }
}

.el-dialog {
  .objectName {
    font-weight: var(--font-weight-bold);
    font-size: var(--font-size-large);
    //padding-top: 1rem;
  }

  .el-image {
    width: 100%;
    height: 30vh;
  }
}

.classificationButton::v-deep(> span) {
  //display: block;
  width: 100%;
  height: 100%;
}

.classifiedObject {
  //width: 40%;
  margin: 0 0.1rem;
}

.wrongClassificationIcon {
  color: var(--color-evaluating);
}

.correctClassificationIcon {
  color: var(--color-brainstorming);
}

.objectName {
  text-align: center;
  text-transform: capitalize;
}

.classificationType {
  --inner-border-radius: calc(var(--border-radius-small) - 5px);
  padding-top: 0.8rem;
  width: calc(100% - 2px);
  text-align: center;
  padding-bottom: 0.5rem;
  position: fixed;
  top: 0;
  z-index: 10;
  background-color: var(--color-background);
  border-radius: var(--inner-border-radius) var(--inner-border-radius) 0 0;
}
</style>
