<template>
  <ParticipantModuleDefaultContainer :task-id="taskId">
    <template v-slot:planet>
      <img
        src="@/assets/illustrations/planets/brainstorming.png"
        alt="planet"
        class="module-container__planet"
      />
    </template>
    TODO: implement game!
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
