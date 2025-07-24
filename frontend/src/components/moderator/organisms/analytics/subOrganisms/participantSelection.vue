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
        :icon="['fas', 'circle']"
        class="selectedIndicator"
        :style="{
          color: getParticipantColor(participant),
          opacity: selectedParticipantIds.includes(participant.id) ? 1 : 0,
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
    font-size: 0.3rem;
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
