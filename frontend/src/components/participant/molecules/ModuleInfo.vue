<template>
  <ParticipantTutorial
    :module-info-entry-data-list="
      mappedModuleInfoEntryDataList.map((item) => item.key)
    "
    :info-type="infoType"
    :show-tutorial-only-once="showTutorialOnlyOnce"
    :active="active"
    @infoRead="() => $emit('infoRead')"
    @tutorialNotShown="() => $emit('tutorialNotShown')"
    @sizeChanged="sizeChanged"
    @activeTabIndexChanged="(index) => (activeTabIndex = index)"
  >
    <template #prefix>
      <SpriteCanvas
        v-if="
          mappedModuleInfoEntryDataList.length > 0 &&
          mappedModuleInfoEntryDataList[activeTabIndex] &&
          !isDefaultImage(mappedModuleInfoEntryDataList[activeTabIndex].texture)
        "
        class="pixiCanvas info-image"
        :width="gameWidth"
        :height="gameHeight / 2"
        :texture="mappedModuleInfoEntryDataList[activeTabIndex].texture"
        :background-color="backgroundColor"
      />
    </template>
    <template v-slot="entry">
      <el-image
        v-if="keyHasDefaultImage(entry.key)"
        class="info-image"
        :src="getImageForKey(entry.key)"
        :alt="entry.key"
        :preview-src-list="[getImageForKey(entry.key)]"
        :hide-on-click-modal="true"
        fit="contain"
      />
      <div class="info-text">
        <p
          v-html="replaceLinebreaks($t(`${translationPath}.${entry.key}`))"
        ></p>
      </div>
    </template>
  </ParticipantTutorial>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import SpriteCanvas from '@/components/shared/atoms/game/SpriteCanvas.vue';
import * as PIXI from 'pixi.js';
import * as themeColors from '@/utils/themeColors';
import ParticipantTutorial from '@/components/participant/molecules/ParticipantTutorial.vue';

export interface ModuleInfoEntryData {
  key: string;
  texture: string | PIXI.Texture | PIXI.Texture[] | string[];
}

@Options({
  components: { ParticipantTutorial, SpriteCanvas },
  emits: ['infoRead', 'tutorialNotShown'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModuleInfo extends Vue {
  @Prop({ default: [] })
  readonly moduleInfoEntryDataList!: (string | ModuleInfoEntryData)[];
  @Prop({ default: '' }) readonly translationPath!: string;
  @Prop({ default: '' }) readonly imageDirectory!: string;
  @Prop({ default: 'jpg' }) readonly fileExtension!: string;
  @Prop({ default: 'moduleInfo' }) readonly infoType!: string;
  @Prop({ default: true }) readonly showTutorialOnlyOnce!: boolean;
  @Prop({ default: true }) readonly active!: boolean;
  gameWidth = 0;
  gameHeight = 0;
  activeTabIndex = 0;

  replaceLinebreaks(text): string {
    return text.replace(/\n/g, '<br><br>');
  }

  get mappedModuleInfoEntryDataList(): ModuleInfoEntryData[] {
    return this.moduleInfoEntryDataList.map((entry) => {
      if (typeof entry === 'string')
        return {
          key: entry,
          texture: `${this.imageDirectory}/${entry}.${this.fileExtension}`,
        };
      else if (
        typeof entry.texture === 'string' &&
        !entry.texture.includes('\\')
      ) {
        return {
          key: entry.key,
          texture: `${this.imageDirectory}/${entry.texture}`,
        };
      }
      return entry;
    });
  }

  get backgroundColor(): string {
    return themeColors.getBackgroundColor();
  }

  isDefaultImage(
    texture: string | PIXI.Texture | PIXI.Texture[] | string[]
  ): boolean {
    if (typeof texture === 'string') return !texture.endsWith('.json');
    return false;
  }

  keyHasDefaultImage(key: string): boolean {
    const entry = this.mappedModuleInfoEntryDataList.find(
      (item) => item.key === key
    );
    if (entry && typeof entry.texture === 'string')
      return !entry.texture.endsWith('.json');
    return false;
  }

  getImageForKey(
    key: string
  ): string | PIXI.Texture | PIXI.Texture[] | string[] | null {
    const entry = this.mappedModuleInfoEntryDataList.find(
      (item) => item.key === key
    );
    if (entry) return entry.texture;
    return null;
  }

  sizeChanged(width: number, height: number): void {
    this.gameWidth = width;
    this.gameHeight = height;
  }
}
</script>

<style scoped lang="scss">
.pixiCanvas {
  position: absolute;
  top: 0;
  right: 0;
}

.el-image {
  max-height: 50%;
  height: 50%;
  width: 100%;
}

.info-image {
  max-height: 50vh;
  height: 50vh;
}

.info-text {
  padding: 2rem;
  position: absolute;
  top: calc(52vh);
  height: calc(30vh);
  overflow: auto;
  margin: 2vh 1vh 1vh 1vh;
}

.infoArea::v-deep(.next) {
  top: calc(50vh);
  bottom: unset;
}

.infoArea::v-deep(.prev) {
  top: calc(50vh);
  bottom: unset;
}
</style>
