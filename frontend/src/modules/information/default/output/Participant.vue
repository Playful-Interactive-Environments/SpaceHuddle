<template>
  <ParticipantModuleDefaultContainer :task-id="taskId" :module="moduleName">
    <template v-slot:planet>
      <img
        src="@/assets/illustrations/planets/information.png"
        alt="planet"
        class="module-container__planet"
      />
    </template>
    TODO: implement information content!
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/ParticipantModuleDefaultContainer.vue';
import * as moduleService from '@/services/module-service';
import { Module } from '@/types/api/Module';

@Options({
  components: {
    ParticipantModuleDefaultContainer,
  },
})
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  module: Module | null = null;

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  @Watch('moduleId', { immediate: true })
  onModuleIdChanged(): void {
    this.getModule();
  }

  async getModule(): Promise<void> {
    if (this.moduleId) {
      await moduleService.getModuleById(this.moduleId).then((module) => {
        this.module = module;
      });
    }
  }
}
</script>

<style scoped></style>
