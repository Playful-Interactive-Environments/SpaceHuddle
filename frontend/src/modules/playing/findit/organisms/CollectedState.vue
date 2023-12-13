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
    <div class="endObjects">
      <div
        v-for="object in endObjects"
        :key="object.name"
        :id="object.name"
        class="endObject"
        @click="activeObjectChanged(object, object.name, true)"
      >
        <img
          v-if="
            levelTypeImages[object.type] &&
            levelTypeImages[object.type][object.name]
          "
          :src="levelTypeImages[object.type][object.name]"
          :alt="object.name"
          class="endObjectSprites"
        />
      </div>
    </div>
    <h2 class="heading heading--medium" v-if="this.activeObject !== null">
      {{
        $t(
          'module.playing.findit.participant.placeables.' +
            levelType +
            '.' +
            this.activeObject.type +
            '.' +
            getExplanationKey(this.activeObject) +
            '.name'
        )
      }}
    </h2>
    <div class="infoText">
      <p class="marginTop" v-if="this.activeObject !== null">
        {{
          $t(
            `module.playing.findit.participant.endCardTexts.${getExplanationKey(
              this.activeObject
            )}`
          )
        }}
      </p>
    </div>
    <el-button
      class="el-button--submit returnButton"
      @click="this.$emit('replayFinished')"
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

  endObjects: placeable.PlaceableBase[] = [];
  activeObjectId = '';
  activeObject: placeable.PlaceableBase | null = null;

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

  getEndObjects(): placeable.PlaceableBase[] {
    const uniqueNamesSet = new Set();
    return this.playStateResult.itemList.filter((obj) => {
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
              .loadTexture(settings.spritesheet, this.eventBus)
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
  mounted(): void {
    this.endObjects = this.getEndObjects();
    setTimeout(() => {
      const dom = this.$refs.gameArea as HTMLElement;
      if (dom) {
        const targetWidth = dom.offsetWidth;
        const targetHeight = dom.offsetHeight;
        if (targetWidth && targetHeight) {
          this.gameAreaSize = [targetWidth, targetHeight];
        }
      }
    }, 500);
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
    if (object.name === 'man' || object.name === 'woman') {
      return 'person';
    } else if (object.name.substring(0, 6) === 'bottle') {
      return 'bottle';
    } else {
      return object.name;
    }
  }
  //#endregion interaction
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
  scrollbar-width: none; /* Firefox */
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
</style>
