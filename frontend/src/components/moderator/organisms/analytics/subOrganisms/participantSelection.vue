<template>
  <div class="participantSelectionContainer">
    <font-awesome-icon
      v-for="participant in participants"
      :key="participant.id"
      :icon="participant.avatar.symbol"
      :style="{
        color: getParticipantColor(participant),
        transform: getParticipantScale(participant),
      }"
      class="participant"
      @click="participantSelectionChanged(participant.id)"
    />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
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

  defaultColor = 'var(--color-background-darker)';

  participantSelectionChanged(id: string): void {
    const isSelected = this.selectedParticipantIds.includes(id);
    const updatedSelection = isSelected
      ? this.selectedParticipantIds.filter(
          (participantId) => participantId !== id
        )
      : [...this.selectedParticipantIds, id];

    this.$emit('update:selectedParticipantIds', updatedSelection);
  }

  getParticipantColor(participant: ParticipantInfo): string {
    return this.selectedParticipantIds.includes(participant.id)
      ? participant.avatar.color
      : this.defaultColor;
  }

  getParticipantScale(participant: ParticipantInfo): string {
    return this.selectedParticipantIds.includes(participant.id)
      ? 'scale(1.15)'
      : 'scale(1)';
  }
}
</script>

<style lang="scss" scoped>
.participantSelectionContainer {
  display: flex;
  justify-content: center;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  padding: 1rem 2rem;
  font-size: var(--font-size-large);
}

.participant {
  transform: scale(1);
  transition: transform 0.3s ease, color 0.3s ease;
  cursor: pointer;

  &:hover {
    transform: scale(1.15) !important;
  }
}
</style>
