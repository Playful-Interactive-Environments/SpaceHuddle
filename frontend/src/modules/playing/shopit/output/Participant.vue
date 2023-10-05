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
    <PlayState
      v-if="/*gameStep === GameStep.Play && */ gameState === GameState.Game"
      :taskId="taskId"
      @playFinished="playFinished"
    />
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
import PlayState, {
  PlayStateResult,
} from '@/modules/playing/shopit/organisms/PlayState.vue';

export enum GameStep {
  Select = 'select',
  Play = 'play',
}

enum GameState {
  Info = 'info',
  Game = 'game',
}

@Options({
  components: {
    ModuleInfo,
    PlayState,
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

  gameStep = GameStep.Select;
  GameStep = GameStep;
  gameState = GameState.Info;
  GameState = GameState;

  get tutorialList(): (string | ModuleInfoEntryData)[] {
    switch (this.gameStep) {
      case GameStep.Select:
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
  }

  unmounted(): void {
    this.deregisterAll();
  }

  playFinished(result: PlayStateResult): void {
    this.gameStep = GameStep.Select;
    this.gameState = GameState.Info;
  }
}
</script>

<style lang="scss" scoped>
.gameSpace {
  position: relative;
}
</style>
