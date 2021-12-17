<template>
  <el-popover placement="top" :width="300" trigger="manual" :visible="showStep">
    <div class="text">
      {{ $t(`tutorial.${type}.${step}`) }}
    </div>
    <div class="link check" v-on:click="stepDone">
      <font-awesome-icon icon="check"></font-awesome-icon>
    </div>
    <template #reference>
      <slot></slot>
    </template>
  </el-popover>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as tutorialService from '@/services/tutorial-service';
import { getTutorialSteps } from '@/services/auth-service';
import { Tutorial } from '@/types/api/Tutorial';
import { v4 as uuidv4 } from 'uuid';

const reservedTutorialSteps: {
  [key: string]: {
    tutorial: Tutorial;
    reservedBy: string;
    duplicate: string[];
  };
} = {};

@Options({
  components: {},
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TutorialStep extends Vue {
  @Prop({ default: '' }) step!: string;
  @Prop({ default: '' }) type!: string;
  @Prop({ default: 0 }) order!: number;
  @Prop({ default: false }) readonly disableTutorial!: boolean;
  @Prop({ default: 0 }) minDuplicates!: number;
  @Prop({ default: false }) displayAllDuplicates!: boolean;
  activateTutorial = false;
  tutorialSteps: Tutorial[] = [];
  uuid = uuidv4();

  get stepKey(): string {
    return `${this.type}.${this.step}`;
  }

  get tutorialItem(): Tutorial {
    return {
      step: this.step,
      type: this.type,
      order: this.order,
    };
  }

  mounted(): void {
    getTutorialSteps().then((steps) => (this.tutorialSteps = steps));
  }

  @Watch('disableTutorial', { immediate: true, deep: true })
  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  onDisableTutorialChanged(): void {
    if (!this.disableTutorial) {
      if (!reservedTutorialSteps[this.stepKey]) {
        reservedTutorialSteps[this.stepKey] = {
          tutorial: this.tutorialItem,
          reservedBy: this.uuid,
          duplicate: [],
        };
      } else {
        reservedTutorialSteps[this.stepKey].duplicate.push(this.uuid);
      }
    } else {
      this.removeFromReservationList();
    }
    setTimeout(() => {
      this.activateTutorial = !this.disableTutorial;
    }, 1000);
  }

  unmounted(): void {
    this.removeFromReservationList();
  }

  removeFromReservationList(): void {
    if (this.isReservedByUuid(this.uuid)) {
      delete reservedTutorialSteps[this.stepKey];
    }
  }

  isReservedByUuid(uuid: string): boolean {
    if (reservedTutorialSteps[this.stepKey])
      return reservedTutorialSteps[this.stepKey].reservedBy === uuid;
    return false;
  }

  getPreviousOrder(): number {
    const previousOrders = Object.values(reservedTutorialSteps)
      .filter(
        (reservation) =>
          reservation.tutorial.type === this.type &&
          reservation.tutorial.order < this.order
      )
      .map((reservation) => reservation.tutorial.order)
      .sort()
      .reverse();

    if (previousOrders.length > 0) return previousOrders[0];
    return -1;
  }

  getIncludeStep(): boolean {
    return !!this.tutorialSteps.find(
      (tutorial) => tutorial.step == this.step && tutorial.type == this.type
    );
  }

  calcShowStep(): boolean {
    const previousOrder = this.getPreviousOrder();
    const previousStepsDone =
      previousOrder == -1 ||
      !!this.tutorialSteps.find(
        (tutorial) =>
          tutorial.type == this.type && tutorial.order == previousOrder
      );
    return (
      !this.disableTutorial &&
      this.activateTutorial &&
      this.uuid.length > 0 &&
      (this.displayAllDuplicates || this.isReservedByUuid(this.uuid)) &&
      !this.getIncludeStep() &&
      previousStepsDone &&
      reservedTutorialSteps[this.stepKey].duplicate.length >= this.minDuplicates
    );
  }

  get showStep(): boolean {
    return this.calcShowStep();
  }

  stepDone(): void {
    const item = this.tutorialItem;
    if (!this.getIncludeStep()) {
      this.tutorialSteps.push(item);
      tutorialService.postStep(item);
    }
  }
}
</script>

<style lang="scss" scoped>
.text {
  word-break: break-word;
}

.check {
  color: var(--color-mint);
  width: 100%;
  display: inline-flex;
  justify-content: right;
}
</style>
