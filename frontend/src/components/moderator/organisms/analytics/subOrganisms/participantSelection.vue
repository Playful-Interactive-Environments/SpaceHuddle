<template>
  <div class="participantSelectionContainer">
    <div
      class="participantIcon"
      v-for="participant in participants"
      :key="participant.id"
    >
      <font-awesome-icon
        :icon="participant.avatar.symbol"
        :style="{
          color: getParticipantColor(participant),
        }"
        class="participant"
        :class="{
          participantSelected: selectedParticipantIds.includes(participant.id),
        }"
        @click="participantSelectionChanged(participant.id)"
      />
      <font-awesome-icon
        :icon="['fas', 'magnifying-glass']"
        class="selectedIndicator"
        @click="participantSelectionFocused(participant.id)"
        :style="{
          color: getParticipantColor(participant),
          opacity:
            selectedParticipantIds.includes(participant.id) &&
            selectedParticipantIds.length > 1
              ? 1
              : 0,
          pointerEvents:
            selectedParticipantIds.includes(participant.id) &&
            selectedParticipantIds.length
              ? 'auto'
              : 'none',
        }"
      />
    </div>
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

    this.emitUpdatedSelection(updatedSelection);
  }

  participantSelectionFocused(id: string): void {
    this.emitUpdatedSelection([id]);
  }

  emitUpdatedSelection(updatedSelection: string[]): void {
    this.$emit('update:selectedParticipantIds', updatedSelection);
  }

  getParticipantColor(participant: ParticipantInfo): string {
    return this.selectedParticipantIds.includes(participant.id)
      ? participant.avatar.color
      : this.defaultColor;
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
  padding: 1rem 2rem 0.5rem 2rem;
  font-size: var(--font-size-large);
}

.participantIcon {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  .selectedIndicator {
    margin-top: -0.6rem;
    margin-right: -0.8rem;
    font-size: 10pt;
    padding: 0.2rem;
    border-radius: 50%;
    z-index: 1;
    background-color: var(--color-background);
    transform: scale(1);
    transition: transform 0.3s ease, color 0.3s ease;
    cursor: pointer;
  }
  .selectedIndicator:hover {
    transform: scale(1.15) !important;
  }
}

.participantSelected {
  transform: scale(1.15) !important;
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
