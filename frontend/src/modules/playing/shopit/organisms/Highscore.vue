<template>
  <table class="highscore-table">
    <tr>
      <th />
      <th>
        {{ $t('module.playing.shopit.participant.highscore.pointsSpent') }}
      </th>
      <th>
        {{ $t('module.playing.shopit.participant.highscore.co2') }}
      </th>
      <th>
        {{ $t('module.playing.shopit.participant.highscore.water') }}
      </th>
      <th>
        {{ $t('module.playing.shopit.participant.highscore.lifetime') }}
      </th>
      <th></th>
    </tr>
    <tr v-for="entry of highScoreList" :key="entry.avatar.symbol">
      <td>
        <font-awesome-icon
          :icon="entry.avatar.symbol"
          :style="{ color: entry.avatar.color }"
        ></font-awesome-icon>
      </td>
      <td>{{ entry.value.pointsSpent }}</td>
      <td>
        {{ entry.value.co2 }}
      </td>
      <td>
        {{ entry.value.water }}
      </td>
      <td>
        {{ entry.value.lifetime }}
      </td>
      <td>
        <el-rate
          v-model="entry.value.rate"
          size="large"
          :max="3"
          :disabled="true"
        />
      </td>
    </tr>
  </table>
</template>

<script lang="ts">
import { Prop } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Avatar } from '@/types/api/Participant';
import * as votingService from '@/services/voting-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import { VoteParameterResult } from '@/types/api/Vote';

@Options({
  components: { FontAwesomeIcon },
  emits: ['selectionDone'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Highscore extends Vue {
  @Prop() readonly taskId!: string;
  highScoreList: { value: number | any; avatar: Avatar }[] = [];

  mounted(): void {
    votingService.registerGetParameterResult(
      this.taskId,
      '-',
      this.updateHighScore,
      EndpointAuthorisationType.PARTICIPANT,
      5 * 60
    );
  }

  unmounted(): void {
    cashService.deregisterAllGet(this.updateHighScore);
  }

  updateHighScore(list: VoteParameterResult[]): void {
    for (const level of list) {
      if (level.details) {
        this.highScoreList = level.details.sort((a, b) => {
          if (b.value.stars === a.value.stars) {
            return a.value.pointsSpent - b.value.pointsSpent;
          }
          return b.value.stars - a.value.stars;
        });
        break;
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.highscore-table {
  color: var(--color-playing);
  width: 100%;
}

.highscore::v-deep(.footer) {
  text-align: center;
  background-color: unset;
}

.highscore {
  --footer-height: 4rem;
}
</style>