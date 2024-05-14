<template>
  <el-collapse v-model="openHighScoreCategories">
    <el-collapse-item
      v-for="(categoryData, category) of highScoreList"
      :key="category"
      :title="$t(`module.playing.moveit.enums.vehicles.${category}.category`)"
      :name="category"
    >
      <el-collapse-item
        v-for="(vehicleData, vehicle) of categoryData"
        :key="vehicle"
        :title="
          $t(`module.playing.moveit.enums.vehicles.${category}.${vehicle}`)
        "
        :name="vehicle"
      >
        <table class="highscore-table">
          <tr>
            <th />
            <th />
            <th>
              <el-tooltip
                :content="
                  $t('module.playing.moveit.participant.drivingStats.collected')
                "
              >
                <font-awesome-icon icon="atom" />
              </el-tooltip>
            </th>
            <th>
              <el-tooltip
                :content="
                  $t('module.playing.moveit.participant.drivingStats.avgSpeed')
                "
              >
                <font-awesome-icon icon="gauge" />
              </el-tooltip>
            </th>
            <th>
              <el-tooltip
                :content="
                  $t('module.playing.moveit.participant.drivingStats.maxSpeed')
                "
              >
                <font-awesome-icon icon="gauge-high" />
              </el-tooltip>
            </th>
            <th>
              <el-tooltip
                :content="
                  $t(
                    'module.playing.moveit.participant.drivingStats.consumption'
                  )
                "
              >
                <font-awesome-icon icon="gas-pump" />
              </el-tooltip>
            </th>
            <th v-if="category === 'bus'">
              <el-tooltip
                :content="
                  $t('module.playing.moveit.participant.drivingStats.persons')
                "
              >
                <font-awesome-icon icon="people-group" />
              </el-tooltip>
            </th>
          </tr>
          <tr
            v-for="(entry, index) of vehicleData.slice(
              0,
              highScoreCount[category][vehicle]
            )"
            :key="entry.avatar.symbol"
          >
            <td>{{ index + 1 }}.</td>
            <td>
              <font-awesome-icon
                :icon="entry.avatar.symbol"
                :style="{ color: entry.avatar.color }"
              ></font-awesome-icon>
            </td>
            <td>
              {{ entry.value.collectedCount }} / {{ entry.value.totalCount }}
            </td>
            <td>
              {{ Math.round(entry.value.averageSpeed) }}
              {{ $t('module.playing.moveit.enums.units.km/h') }}
            </td>
            <td>
              {{ Math.round(entry.value.maxSpeed) }}
              {{ $t('module.playing.moveit.enums.units.km/h') }}
            </td>
            <td>
              {{ Math.round(entry.value.consumption * 1000) / 1000 }}
              <span v-if="isElectric(category, vehicle)">
                {{ $t('module.playing.moveit.enums.units.kw') }}
              </span>
              <span v-else>
                {{ $t('module.playing.moveit.enums.units.liters') }}
              </span>
            </td>
            <td v-if="category === 'bus'">
              {{ Math.round(entry.value.persons) }}
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
          <tr v-if="highScoreCount[category][vehicle] < vehicleData.length">
            <td>
              <el-button
                link
                @click="highScoreCount[category][vehicle] = vehicleData.length"
                class="text-button"
              >
                ...
              </el-button>
            </td>
          </tr>
        </table>
      </el-collapse-item>
    </el-collapse-item>
  </el-collapse>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { VoteParameterResult } from '@/types/api/Vote';
import * as cashService from '@/services/cash-service';
import * as votingService from '@/services/voting-service';
import * as gameConfig from '@/modules/playing/moveit/data/gameConfig.json';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

interface HighscoreData {
  [key: string]: {
    [key: string]: {
      avatar: { color: string; symbol: string };
      value: {
        percentage: number;
        rate: number;
        totalCount: number;
        collectedCount: number;
      };
    }[];
  };
}

@Options({
  components: { FontAwesomeIcon },
})
export default class Highscore extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  highScoreList: HighscoreData = {};
  openHighScoreCategories: string[] = [];

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      votingService.registerGetParameterResult(
        this.taskId,
        '-',
        this.updateHighScore,
        this.authHeaderTyp,
        5 * 60
      );
    }
  }

  unmounted(): void {
    cashService.deregisterAllGet(this.updateHighScore);
  }

  highScoreCount: {
    [key: string]: {
      [key: string]: number;
    };
  } = {};
  updateHighScore(list: VoteParameterResult[]): void {
    const data: HighscoreData = {};
    for (const level of list) {
      if (level.details) {
        for (const detail of level.details) {
          if (!data[detail.value.vehicle.category])
            data[detail.value.vehicle.category] = {};
          if (!data[detail.value.vehicle.category][detail.value.vehicle.type])
            data[detail.value.vehicle.category][detail.value.vehicle.type] = [];
          data[detail.value.vehicle.category][detail.value.vehicle.type].push({
            avatar: detail.avatar,
            value: detail.value,
          });
        }
      }
    }
    for (const category of Object.keys(data)) {
      if (Object.keys(this.highScoreList).length === 0)
        this.openHighScoreCategories.push(category);
      if (!this.highScoreCount[category]) this.highScoreCount[category] = {};
      for (const vehicle of Object.keys(data[category])) {
        if (Object.keys(this.highScoreList).length === 0)
          this.openHighScoreCategories.push(vehicle);
        data[category][vehicle] = data[category][vehicle].sort(
          (a, b) => b.value.percentage - a.value.percentage
        );
        if (data[category][vehicle].length > 5) {
          const oldCount = this.highScoreCount[category][vehicle];
          this.highScoreCount[category][vehicle] = oldCount ?? 5;
        } else
          this.highScoreCount[category][vehicle] =
            data[category][vehicle].length;
      }
    }
    this.highScoreList = data;
  }

  isElectric(category: string, type: string): boolean {
    const vehicle = gameConfig.vehicles[category].types.find(
      (item) => item.name === type
    );
    if (vehicle) return vehicle.fuel === 'electricity';
    return false;
  }
}
</script>

<style lang="scss" scoped>
.highscore-table {
  color: var(--color-playing);
  width: 100%;
}

.text-button {
  min-height: unset;
  margin: unset;
  padding: unset;
}
</style>
