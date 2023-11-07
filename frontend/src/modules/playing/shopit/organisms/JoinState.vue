<template>
  <div id="selectContainer">
    <div id="cardContainer">
      <div class="selectOption" id="singleplayer">
        <h2 class="heading heading--medium">Singleplayer</h2>
        <p>Play against an artificial opponent and get familiar with the cards!</p>
        <div class="options">
          <label class="el-form-item__label">Play alone</label>
          <el-button
            class="el-button--submit"
            @click="optionSelected('singleplayer', null)"
          >
            Play!
          </el-button>
        </div>
      </div>
      <div class="selectOption" id="multiplayer">
        <h2 class="heading heading--medium">Multiplayer</h2>
        <p>Play against a friend and see who is better at keeping their emissions balanced! Host or join a game.</p>
        <div class="options">
          <label class="el-form-item__label">Host Game</label>
          <el-button
            class="el-button--submit"
            @click="optionSelected('multiplayer', this.ownPlayID)"
          >
            Host Game: {{ this.ownPlayID }}
          </el-button>
        </div>
        <div class="options">
          <label class="el-form-item__label">Join Game</label>
          <el-input v-model="inputID" placeholder="ID" />
          <el-button
            class="el-button--submit"
            @click="optionSelected('joinMultiplayer', inputID)"
          >
            Join
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

@Options({
  components: { FontAwesomeIcon },
  emits: ['selectionDone'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class SelectState extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: {} }) readonly gameConfig!: any;
  @Prop() readonly trackingManager!: TrackingManager;
  ideas: Idea[] = [];
  result: TaskParticipantIterationStep[] = [];
  mapping: { [key: string]: number } = {};

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

  levelSelected(level: Idea | null, levelType: string | null = null) {
    if (!level) {
      this.$emit('selectionDone', levelType, null);
    } else {
      const levelType = configParameter.getTypeForLevel(this.gameConfig, level);
      this.$emit('selectionDone', levelType, level);
    }
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
