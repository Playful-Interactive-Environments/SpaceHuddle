<template>
  <ParticipantModuleDefaultContainer :task-id="taskId" :module="moduleName">
    <div v-if="hasWon">
      {{ module.parameter.winText }}
    </div>
    <div v-else>
      {{ $t('module.playing.raffle.participant.wait') }}
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
    this.hasWon = !!ideas.find(
      (item) => item.parameter.participant === authService.getParticipantId()
    );
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
