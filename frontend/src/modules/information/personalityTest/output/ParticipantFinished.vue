<template>
  <ParticipantModuleDefaultContainer
    :task-id="taskId"
    :module="moduleName"
    :key="resultKey"
  >
    <ParticipantResult
      v-if="trackingManager?.state?.parameter"
      :test="test"
      :result-value="trackingManager?.state?.parameter"
    />
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
import draggable from 'vuedraggable';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { convertToI18nKey } from '@/modules/information/personalityTest/types/ResultType';
import ParticipantResult from '@/modules/information/personalityTest/organisms/ParticipantResult/default.vue';
import * as importService from '@/modules/information/personalityTest/utils/import';

@Options({
  methods: { convertToI18nKey },
  components: {
    FontAwesomeIcon,
    ParticipantModuleDefaultContainer,
    draggable,
    ParticipantResult: ParticipantResult,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  @Prop({ default: false }) readonly useFullSize!: boolean;
  @Prop({ default: '' }) readonly backgroundClass!: string;
  module: Module | null = null;
  test = '';
  EndpointAuthorisationType = EndpointAuthorisationType;

  trackingManager!: TrackingManager;

  resultKey = 0;

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    if (this.taskId) {
      this.trackingManager = new TrackingManager(this.taskId, {});
    }
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
    this.test = module.parameter.test;
    importService
      .importComponent('ParticipantResult', this.test)
      .then((component) => {
        if (this.$options.components) {
          this.$options.components['ParticipantResult'] = component as any;
          this.resultKey++;
        }
      });
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateModule);
  }

  unmounted(): void {
    this.deregisterAll();
    if (this.trackingManager) this.trackingManager.deregisterAll();
  }
}
</script>

<style lang="scss" scoped></style>
