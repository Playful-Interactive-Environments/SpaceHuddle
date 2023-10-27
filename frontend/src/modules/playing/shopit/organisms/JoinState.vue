<template>
  <div id="selectContainer">
    <div class="selectOption" @click="optionSelected('singleplayer', null)">
      <p>Singleplayer</p>
    </div>
    <div class="selectOption">
      <div
        id="ownID"
        class="selectOption"
        @click="optionSelected('multiplayer', this.ownPlayID)"
      >
        <p>Multiplayer ID: {{ this.ownPlayID }}</p>
      </div>
      <div id="joinID" class="selectOption">
        <p>Join</p>
        <input v-model="inputID" placeholder="ID" />
        <button @click="optionSelected('joinMultiplayer', inputID)">
          Join
        </button>
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

  inputID = 0;
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
#selectContainer {
  height: 100%;
  width: 100%;
}

.selectOption {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  height: 50%;
  width: 100%;
  border: 1px solid red;
}
</style>
