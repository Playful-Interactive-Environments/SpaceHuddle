<template>
  <div id="selectContainer">
    <div
        class="opponentHand"
        :style="{
        backgroundImage: 'url(' + gameConfig.gameValues.opponentHand + ')',
      }"
    ></div>
    <div id="cardContainer">
      <div class="selectOption" id="singleplayer">
        <h2 class="heading heading--medium">{{ $t('module.playing.shopit.participant.joinState.singleplayer') }}</h2>
        <p>{{ $t('module.playing.shopit.participant.joinState.singleplayerDesc') }}</p>
        <div class="options">
          <label class="el-form-item__label">{{ $t('module.playing.shopit.participant.joinState.singleplayerLabel') }}</label>
          <el-button
            class="el-button--submit"
            @click="optionSelected('singleplayer', null)"
          >
            {{ $t('module.playing.shopit.participant.joinState.singleplayerButton') }}
          </el-button>
        </div>
      </div>
      <div class="selectOption" id="multiplayer">
        <h2 class="heading heading--medium">{{ $t('module.playing.shopit.participant.joinState.multiplayer') }}</h2>
        <p>{{ $t('module.playing.shopit.participant.joinState.multiplayerDesc') }}</p>
        <div class="options">
          <label class="el-form-item__label">{{ $t('module.playing.shopit.participant.joinState.multiplayerLabelHost') }}</label>
          <el-button
            class="el-button--submit"
            @click="optionSelected('multiplayer', this.ownPlayID)"
          >
            {{ $t('module.playing.shopit.participant.joinState.multiplayerButtonHost') }}
          </el-button>
        </div>
        <div class="options">
          <label class="el-form-item__label">{{ $t('module.playing.shopit.participant.joinState.multiplayerLabelJoin') }}</label>
          <el-input v-model="inputID" placeholder="ID" />
          <el-button
            class="el-button--submit"
            @click="optionSelected('joinMultiplayer', inputID)"
          >
            {{ $t('module.playing.shopit.participant.joinState.multiplayerButtonJoin') }}
          </el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import * as cashService from '@/services/cash-service';
import * as authService from '@/services/auth-service';
import * as ideaService from '@/services/idea-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as taskParticipantService from '@/services/task-participant-service';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import { GameStep } from '@/modules/playing/findit/output/Participant.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import * as configParameter from '@/utils/game/configParameter';
import { LevelWorkflowType } from '@/types/game/LevelWorkflowType';
import gameConfig from '@/modules/playing/shopit/data/gameConfig.json';

@Options({
  components: { FontAwesomeIcon },
  emits: ['selectionDone'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class SelectState extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly trackingManager!: TrackingManager;
  ideas: Idea[] = [];
  result: TaskParticipantIterationStep[] = [];
  mapping: { [key: string]: number } = {};

  gameConfig = gameConfig;
  inputID = '';
  ownPlayID = this.createPlayID();

  unmounted(): void {
    cashService.deregisterAllGet(this.updateIdeas);
  }

  createPlayID() {
    let id = Math.floor(Math.random() * 9999);
    if (id < 1000) {
      id += 1000;
    }
    return id;
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
    this.ideas = ideas.filter(
      (item) => item.parameter.state === LevelWorkflowType.approved
    );
  }

  optionSelected(option, id) {
    if (
      option === 'multiplayer' ||
      (option === 'joinMultiplayer' && id >= 1000 && id <= 9999)
    ) {
      this.$emit('selectionDone', option, id);
    } else if (option === 'singleplayer') {
      this.$emit('selectionDone', option, null);
    }
  }
}
</script>

<style lang="scss">
.select-new-level-type {
  width: 90%;
}
</style>

<style lang="scss" scoped>

#cardContainer {
  padding: 3rem;
  position: absolute;
  bottom: 0;
  min-height: 70%;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;

  border-radius: var(--el-border-radius-base) var(--el-border-radius-base) 0 0;
  background-color: white;
}

.opponentHand {
  height: 7%;
  width: 100%;
  background-repeat: no-repeat;
  background-position: top center;
  background-size: auto 100%;
  position: absolute;
  top: 0;
}

.selectOption {
  width: 100%;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  flex-direction: column;
  height: auto;
  border-radius: var(--border-radius);
  position: relative;

  p {
    margin-bottom: 0.5rem;
  }
}

.selectOption#singleplayer {
  margin-bottom: 2rem;
}

.options {
  width: 100%;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: flex-start;
}

.el-input {
  width: 100%;
  height: 2rem;
}

.el-button {
  width: 100%;
  height: 2rem;
}

label {
  height: auto;
  margin-bottom: 0.3rem;
  margin-top: 1rem;
}
</style>
