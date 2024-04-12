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
    <div class="endObjects" v-if="this.endObjects.length !== 0">
      <div
        v-for="object in endObjects"
        :key="object.name"
        :id="object.name"
        class="endObject"
        :class="{
          endObjectCorrect: correctClassified.includes(object.name),
          endObjectIncorrect:
            !correctClassified.includes(object.name) &&
            classified.includes(object.name),
        }"
        @click="activeObjectChanged(object, object.name, true)"
      >
        <p class="objectName">
          {{
            $t(
              `module.playing.findit.participant.placeables.${levelType}.${
                object.type
              }.${getExplanationKey(object)}.name`
            )
          }}
        </p>
        <img
          v-if="
            levelTypeImages[object.type] &&
            levelTypeImages[object.type][object.name]
          "
          :src="levelTypeImages[object.type][object.name]"
          :alt="object.name"
          class="endObjectSprites"
        />
        <font-awesome-icon
          :icon="gameConfig[levelType].categories[object.type].settings.icon"
          class="categoryIcon"
          :class="{
            hazardIcon: checkType(true, object.name),
            noHazardIcon: checkType(false, object.name),
          }"
          v-if="this.classified.includes(object.name)"
        />
      </div>
    </div>
    <div class="score heading--medium" v-if="this.activeObject !== null">
      <p>
        <span
          >{{ this.correctClassified.length }} /
          {{ this.endObjects.length }}</span
        >
      </p>
    </div>
    <h2 class="heading heading--medium" v-if="this.activeObject !== null">
      {{
        $t(
          `module.playing.findit.participant.placeables.${levelType}.${
            this.activeObject.type
          }.${getExplanationKey(this.activeObject)}.name`
        )
      }}
    </h2>
    <div class="classificationButtons" v-if="this.endObjects.length !== 0">
      <el-button
        v-for="key in this.collectKeys"
        :key="key"
        :id="key"
        class="classificationButton"
        @click="checkType(key, this.activeObjectId)"
        >{{ key }}</el-button
      >
    </div>
    <div class="infoText" v-if="this.endObjects.length !== 0">
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
    <el-button
      class="el-button--submit returnButton"
      @click="this.$emit('replayFinished')"
      v-if="
        endObjects
          .map((x) => x.name)
          .every((x) => classified.includes(x)) ||
        this.endObjects.length === 0
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

@Options({
  components: {},
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
  activeObjectId = '';
  activeObject: placeable.PlaceableBase | null = null;

  correctClassified: string[] = [];
  classified: string[] = [];
  gameConfig = gameConfig;

  collectKeys: string[] = [];

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

  checkType(key: string, id: string) {
    if (this.activeObject) {
      if (
        gameConfig[this.levelType].categories[this.activeObject.type].items[
          this.activeObject.name
        ].collectKey === key
      ) {
        if (!this.classified.includes(id)) {
          this.correctClassified.push(id);
          this.classified.push(id);
          return true;
        }
      }
      this.classified.push(id);
      return false;
    }
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
    this.endObjects = this.shuffle(this.getEndObjects());
    if (this.endObjects.length > 0) {
      this.collectKeys = this.getCollectKeys();
      this.activeObject = this.endObjects[0];
      this.activeObjectId = this.endObjects[0].name;
      /*const element = document.getElementById(this.activeObjectId);
      if (element) {
        element.classList.add('objectContainerActive');
      }*/
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
    let element = document.getElementById(this.activeObjectId);
    if (element) {
      element.classList.remove('objectContainerActive');
    }
    this.activeObjectId = id;
    this.activeObject = object;
    element = document.getElementById(id);
    if (element) {
      element.classList.add('objectContainerActive');
      if (scroll) {
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
  --end-objects-height: calc(var(--game-area-height) * 0.32);

  position: relative;
  display: flex;
  justify-content: flex-start;
  align-items: center;
  height: 32%;
  width: 100%;
  z-index: 10;
  overflow-x: scroll;
  overflow-y: hidden;
  -ms-overflow-style: none; /* IE and Edge */
  //scrollbar-width: none; /* Firefox */
  margin: 2rem 0;
  background-color: var(--color-brown-xlight);
  outline: 0.5rem solid var(--color-brown);
}

.endObject {
  --end-object-width: calc((var(--end-objects-width) - 1rem) / 3);
  --end-object-height: calc((var(--end-objects-height) - 5rem));

  position: relative;
  margin: 1rem;
  transition: 0.3s;
  padding: 0.5rem;
  border: 0.3rem solid var(--color-brown);
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
  padding: 0 1rem;
}

.returnButton {
  position: absolute;
  bottom: 2rem;
}

.infoText {
  height: 2rem;
  transition: 0.3s;
}

.classificationButtons {
  width: 100%;
  display: flex;
  justify-content: center;
  align-content: center;
  flex-direction: row;
  padding: 0 0.3rem;
}

.classificationButton {
  width: 40%;
  height: 3rem;
  border: 3px solid var(--color-dark-contrast);
  color: var(--color-dark-contrast);
  margin: 0.3rem;
  border-radius: var(--border-radius-small);
  font-size: var(--font-size-default);
  font-weight: var(--font-weight-semibold);
  background-color: var(--color-background);
}

.hazardIcon {
  color: var(--color-evaluating);
}

.noHazardIcon {
  color: var(--color-brainstorming);
}

.categoryIcon {
  position: absolute;
  bottom: 0.5rem;
  right: 0.5rem;
  width: 2rem;
  height: 2rem;
}

.objectName {
  position: absolute;
  text-align: center;
  top: 0.2rem;
  text-transform: capitalize;
}
</style>
