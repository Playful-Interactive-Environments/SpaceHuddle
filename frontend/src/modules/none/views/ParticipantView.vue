<template>
  <ParticipantModuleDefaultContainer :task-id="taskId">
    <template v-slot:planet>
      <img
        src="@/assets/illustrations/planets/information.png"
        alt="planet"
        class="module-container__planet"
      />
    </template>
    <h2>{{ $t('module.none.participant.header') }}</h2>
    <p>{{ $t('module.none.participant.info') }}</p>
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/ParticipantModuleDefaultContainer.vue';

@Options({
  components: {
    ParticipantModuleDefaultContainer,
  },
})
export default class ParticipantView extends Vue {
  @Prop() readonly taskId!: string;
}
</script>

<style scoped></style>
