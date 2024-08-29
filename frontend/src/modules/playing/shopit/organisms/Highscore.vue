<template>
  <table class="highscore-table">
    <tr>
      <th />
      <th />
      <th @click="setSortColumn('pointsSpent')">
        {{ $t('module.playing.shopit.participant.highscore.pointsSpent') }}
      </th>
      <th @click="setSortColumn('co2')">
        {{ $t('module.playing.shopit.participant.highscore.co2') }}
      </th>
      <th @click="setSortColumn('water')">
        {{ $t('module.playing.shopit.participant.highscore.water') }}
      </th>
      <th @click="setSortColumn('lifetime')">
        {{ $t('module.playing.shopit.participant.highscore.lifetime') }}
      </th>
      <th @click="setSortColumn('rate')"></th>
    </tr>
    <tr
      v-for="(entry, index) of highScoreList
        .sort((a, b) => (b.value[sortColumn] - a.value[sortColumn]) * sortOrder)
        .slice(0, this.highScoreCount)"
      :key="entry.avatar.symbol"
    >
      <td>{{ index + 1 }}.</td>
      <td>
        <font-awesome-icon
          :icon="entry.avatar.symbol"
          :style="{ color: entry.avatar.color }"
        ></font-awesome-icon>
      </td>
      <td>{{ Math.round(entry.value.pointsSpent * 10) / 10 }}</td>
      <td>
        {{ Math.round(entry.value.co2 * 10) / 10 }}
      </td>
      <td>
        {{ Math.round(entry.value.water * 10) / 10 }}
      </td>
      <td>
        {{ Math.round(entry.value.lifetime * 10) / 10 }}
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
    <tr v-if="highScoreCount < highScoreList.length">
      <td>
        <el-button
          link
          @click="highScoreCount = highScoreList.length"
          class="text-button"
        >
          ...
        </el-button>
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
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  highScoreList: { value: number | any; avatar: Avatar }[] = [];
  sortColumn = 'rate';
  sortOrder = 1;

  mounted(): void {
    votingService.registerGetParameterResult(
      this.taskId,
      '-',
      this.updateHighScore,
      this.authHeaderTyp,
      5 * 60
    );
  }

  unmounted(): void {
    cashService.deregisterAllGet(this.updateHighScore);
  }

  setSortColumn(column: string): void {
    if (this.sortColumn === column) this.sortOrder *= -1;
    else this.sortOrder = 1;
    this.sortColumn = column;
  }

  highScoreCount = 0;
  updateHighScore(list: VoteParameterResult[]): void {
    for (const level of list) {
      if (level.details) {
        this.highScoreList = level.details.sort((a, b) => {
          if (b.value.stars === a.value.stars) {
            return a.value.pointsSpent - b.value.pointsSpent;
          }
          return b.value.stars - a.value.stars;
        });
        if (level.details.length > 5) {
          this.highScoreCount = this.highScoreCount ?? 5;
        } else this.highScoreCount = level.details.length;
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

.text-button {
  min-height: unset;
  margin: unset;
  padding: unset;
}
</style>
