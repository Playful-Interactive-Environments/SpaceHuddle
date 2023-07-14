<template>
  <div ref="gameContainer" class="mapSpace">
    <!-- Tutorial/Info for Edit-Mode -->
    <module-info
      v-if="gameState === GameState.Info && module"
      translation-path="module.information.forestfires.participant.tutorial"
      image-directory="/assets/games/forestfires/tutorial"
      :module-info-entry-data-list="tutorialList"
      file-extension="svg"
      @infoRead="gameState = GameState.Game"
      :showTutorialOnlyOnce="module.parameter.showTutorialOnlyOnce"
    />
    <div v-if="gameStep === GameStep.Select && gameState === GameState.Game">
      <div class="link new" @click="levelSelected(null)">
        {{ $t('module.information.forestfires.participant.newLevel') }}
      </div>
      <div
        class="link"
        :class="{ own: isOwnLevel(idea) }"
        v-for="idea of ideas"
        :key="idea.id"
        @click="levelSelected(idea)"
      >
        {{ idea.keywords }}
      </div>
    </div>
    <!-- Edit-Mode -->
    <PlacementState
      v-if="gameStep === GameStep.Build && gameState === GameState.Game"
      :taskId="taskId"
      @editFinished="editFinished"
    />
    <!-- Play-Mode -->
    <PlayState
      v-if="gameStep === GameStep.Play && gameState === GameState.Game"
      :taskId="taskId"
      :level-data="selectedLevel"
      @playFinished="playFinished"
    />
  </div>
</template>

<script lang="ts">
// Vue
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';

// spaceHuddle API and services
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as moduleService from '@/services/module-service';
import { Module } from '@/types/api/Module';
import * as cashService from '@/services/cash-service';

// Custom Modules
import PlacementState from '@/modules/information/forestfires/organisms/PlacementState.vue';
import PlayState from '@/modules/information/forestfires/organisms/PlayState.vue';
import ModuleInfo from '@/components/participant/molecules/ModuleInfo.vue';
import * as ideaService from '@/services/idea-service';
import { Idea } from '@/types/api/Idea';
import Placeable from '@/modules/information/forestfires/types/Placeable';
import * as authService from '@/services/auth-service';

enum GameStep {
  Select = 'select',
  Build = 'build',
  Play = 'play',
}

enum GameState {
  Info = 'info',
  Game = 'game',
}

@Options({
  components: {
    ModuleInfo,
    PlacementState,
    PlayState,
  },
  emits: ['update:useFullSize'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  @Prop({ default: false }) readonly useFullSize!: boolean;
  @Prop({ default: '' }) readonly backgroundClass!: string;
  module: Module | null = null;
  ideas: Idea[] = [];
  selectedLevel: Placeable[] = [];

  // Flag which indicates if the window size has finished calculating.
  sizeCalculated = false;

  // The general state of the game (tutorial, playing, ...) and smaller steps (edit-mode, play-mode, ...) within those states.
  gameStep = GameStep.Select;
  GameStep = GameStep;
  gameState = GameState.Info;
  GameState = GameState;

  // Vue Callbacks for mounting and unmounting / loading and unloading.
  mounted(): void {
    setTimeout(() => {
      const dom = this.$refs.gameContainer as HTMLElement;
      if (dom) {
        const targetWidth = dom.parentElement?.offsetWidth;
        const targetHeight = dom.parentElement?.offsetHeight;
        if (targetWidth && targetHeight) {
          (dom as any).style.width = `${targetWidth}px`;
          (dom as any).style.height = `${targetHeight}px`;
        }
        this.sizeCalculated = true;
      }
    }, 500);
  }

  unmounted(): void {
    cashService.deregisterAllGet(this.updateModule);
    cashService.deregisterAllGet(this.updateIdeas);
  }

  isOwnLevel(level: Idea): boolean {
    return level.participantId === authService.getParticipantId();
  }

  levelSelected(level: Idea | null) {
    this.gameState = GameState.Info;
    if (!level) {
      this.gameStep = GameStep.Build;
      this.selectedLevel = [];
    } else {
      this.gameStep = GameStep.Play;
      this.selectedLevel = level.parameter as Placeable[];
    }
  }

  // Watch and update the current module in case any changes happen.
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

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      ideaService.registerGetIdeasForTask(
        this.taskId,
        null,
        null,
        this.updateIdeas,
        EndpointAuthorisationType.PARTICIPANT,
        3
      );
    }
  }

  updateIdeas(ideas: Idea[]): void {
    this.ideas = ideas;
  }

  updateModule(module: Module): void {
    this.module = module;
  }

  // Get the filenames for the tutorial images. Should all have the same file extension!
  get tutorialList(): string[] {
    switch (this.gameStep) {
      case GameStep.Build:
        return ['click', 'place', 'play', 'remove'];
      case GameStep.Play:
        return ['collect', 'hazards', 'info'];
    }
    return [];
  }

  // Callbacks when stages are finished.
  editFinished(): void {
    this.gameStep = GameStep.Select;
    this.gameState = GameState.Info;
  }

  playFinished(): void {
    this.gameStep = GameStep.Select;
    this.gameState = GameState.Info;
  }
}
</script>

<style lang="scss" scoped>
.mapSpace {
  position: relative;
}

.link {
  background-color: var(--color-primary);
  color: white;
  border-radius: var(--border-radius);
  margin: 1rem;
  padding: 1rem;
}

.new {
  background-color: var(--color-darkblue-light);
}

.own {
  background-color: var(--color-yellow);
}
</style>
