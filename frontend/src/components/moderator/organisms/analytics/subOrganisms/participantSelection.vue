<template>
  <div class="participantSelectionContainer">
    <font-awesome-icon
      v-for="participant in participants"
      :key="participant.id"
      :icon="participant.avatar.symbol"
      :style="{ color: getParticipantColor(participant) }"
      class="participant"
      @click="participantSelectionChanged(participant.id)"
    />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { ParticipantInfo } from '@/types/api/Participant';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

@Options({
  emits: ['update:selectedParticipantIds'],
  components: {
    FontAwesomeIcon,
  },
})
export default class ParticipantSelection extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: () => [] }) readonly participants!: ParticipantInfo[];
  @Prop({ default: () => [] }) selectedParticipantIds!: string[];

  participantSelectionChanged(id: string): void {
    const isSelected = this.selectedParticipantIds.includes(id);
    const updatedSelection = isSelected
      ? this.selectedParticipantIds.filter(participantId => participantId !== id)
      : [...this.selectedParticipantIds, id];

    this.$emit('update:selectedParticipantIds', updatedSelection);
  }

  getParticipantColor(participant: ParticipantInfo): string {
    return this.selectedParticipantIds.includes(participant.id)
      ? participant.avatar.color
      : 'var(--color-background-darker)';
  }
}
</script>

<style lang="scss" scoped>
.participantSelectionContainer {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  padding: 1rem 2rem;
  font-size: var(--font-size-large);
}

.participant {
  transform: scale(1);
  transition: transform 0.3s ease, color 0.3s ease;
  cursor: pointer;

  &:hover {
    transform: scale(1.15);
  }
}
</style>
