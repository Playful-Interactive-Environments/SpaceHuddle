<template>
  <div class="module-count" v-if="session">
    <div class="module-count__item">
      <span class="module-count__count">{{ session.topicCount }}</span>
      {{ $t('moderator.molecule.moduleCount.topics') }}
    </div>
    <div class="module-count__item">
      <span class="module-count__count">{{ session.taskCount }}</span>
      {{ $t('moderator.molecule.moduleCount.tasks') }}
    </div>
    <div class="module-count__item">
      <span class="module-count__count">{{ participants.length }}</span>
      {{ $t('moderator.molecule.moduleCount.participants') }}
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Session } from '@/types/api/Session';
import * as sessionService from '@/services/session-service';
import { ParticipantInfo } from '@/types/api/Participant';

@Options({
  components: {},
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModuleCount extends Vue {
  @Prop() session!: Session;
  participants: ParticipantInfo[] = [];
  readonly intervalTime = 3000;
  interval!: any;

  mounted(): void {
    this.startInterval();
  }

  startInterval(): void {
    this.interval = setInterval(this.getParticipants, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }

  @Watch('session', { immediate: true })
  async onSessionChanged(): Promise<void> {
    this.getParticipants();
  }

  async getParticipants(): Promise<void> {
    if (this.session) {
      sessionService.getParticipants(this.session.id).then((queryResult) => {
        this.participants = queryResult;
      });
    }
  }
}
</script>

<style lang="scss" scoped>
.module-count {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: var(--font-size-small);
  text-transform: uppercase;
  letter-spacing: 2px;
  margin-top: 1rem;
  line-height: 1;
  flex-wrap: wrap;
  gap: 0.6rem;

  &__item {
    white-space: nowrap;
  }

  &__count {
    background-color: var(--color-mint-light);
    color: white;
    padding: 0.1rem 0.7rem;
    letter-spacing: 0;
    font-size: var(--font-size-default);
    border-radius: 100px;
    margin-right: 0.2rem;
  }
}
</style>
