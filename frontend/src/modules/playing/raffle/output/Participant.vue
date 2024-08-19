<template>
  <ParticipantModuleDefaultContainer :task-id="taskId" :module="moduleName">
    <div v-if="hasWon && module.parameter.winText">
      {{ module.parameter.winText }}
    </div>
    <div v-else-if="hasWon">
      {{ $t('module.playing.raffle.participant.win') }}
    </div>
    <div v-else>
      {{ $t('module.playing.raffle.participant.wait') }}
    </div>

    <br />
    <div v-if="hasWon">
      {{ $t('module.playing.raffle.participant.raffle') }}: {{ winRaffle }}
    </div>
    <div v-if="hasWon">
      {{ $t('module.playing.raffle.participant.index') }}:
      {{ winRaffleIndex + 1 }}
    </div>
    <div v-if="hasWon">
      {{ $t('module.playing.raffle.participant.count') }}: #{{ winCount }}
    </div>
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/layout/ParticipantModuleDefaultContainer.vue';
import * as moduleService from '@/services/module-service';
import { Module } from '@/types/api/Module';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import * as ideaService from '@/services/idea-service';
import { Idea } from '@/types/api/Idea';
import * as authService from '@/services/auth-service';

@Options({
  components: {
    ParticipantModuleDefaultContainer,
  },
})
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  @Prop({ default: false }) readonly useFullSize!: boolean;
  @Prop({ default: '' }) readonly backgroundClass!: string;
  module: Module | null = null;
  hasWon = false;
  winRaffle = 0;
  winCount = 0;
  winRaffleIndex = 0;

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  mounted(): void {
    ideaService.registerGetIdeasForTask(
      this.taskId,
      null,
      null,
      this.updateIdeas,
      EndpointAuthorisationType.PARTICIPANT,
      60 * 60
    );
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

  updateIdeas(ideas: Idea[]): void {
    const myState = ideas.find(
      (item) => item.parameter.participant === authService.getParticipantId()
    );
    if (myState) {
      const raffleList = ideas
        .filter((item) => item.parameter.raffle === myState.parameter.raffle)
        .sort((a, b) => a.parameter.count - b.parameter.count);
      const index = raffleList.findIndex(
        (item) => item.parameter.participant === authService.getParticipantId()
      );
      this.winRaffle = myState.parameter.raffle + 1;
      this.winRaffleIndex = index;
      this.winCount = myState.parameter.count;
    }
    this.hasWon = !!myState;
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateModule);
    cashService.deregisterAllGet(this.updateIdeas);
  }

  unmounted(): void {
    this.deregisterAll();
  }
}
</script>

<style lang="scss" scoped>
.fill::v-deep(.el-loading-mask) {
  background-color: unset;
}
</style>
