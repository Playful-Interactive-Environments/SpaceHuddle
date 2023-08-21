<template>
  <el-tabs v-model="activeTab">
    <el-tab-pane
      v-for="tabName of progressTabs"
      :key="tabName"
      :label="$t(`module.information.missionmap.enum.progress.${tabName}`)"
      :name="tabName"
    >
      <el-form label-position="top" :status-icon="true">
        <el-form-item
          v-for="parameter of Object.keys(gameConfig.parameter)"
          :key="parameter"
          :label="$t(`module.information.missionmap.gameConfig.${parameter}`)"
          :prop="`parameter.influenceAreas.${parameter}`"
          :style="{
            '--parameter-color': gameConfig.parameter[parameter].color,
            '--state-color': getStateColor(progress[parameter][tabName]),
          }"
        >
          <template #label>
            {{ $t(`module.information.missionmap.gameConfig.${parameter}`) }}
            <font-awesome-icon :icon="gameConfig.parameter[parameter].icon" />
          </template>
          <el-slider
            v-model="progress[parameter][tabName]"
            :min="-10"
            :max="10"
            disabled
          />
        </el-form-item>
      </el-form>
    </el-tab-pane>
  </el-tabs>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import gameConfig from '@/modules/information/missionmap/data/gameConfig.json';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import * as themeColors from '@/utils/themeColors';

interface ProgressValues {
  origin: number;
  general: number;
}

@Options({
  computed: {
    gameConfig() {
      return gameConfig;
    },
  },
  components: {
    FontAwesomeIcon,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class MissionProgress extends Vue {
  @Prop() readonly progress!: { [key: string]: ProgressValues };
  @Prop({ default: [] }) readonly progressTabs!: string[];
  activeTab = 'general';

  getStateColor(state: number): string {
    if (state < 0) return themeColors.getRedColor();
    if (state < 2) return themeColors.getYellowColor();
    return themeColors.getGreenColor();
  }

  async mounted(): Promise<void> {
    //
  }

  deregisterAll(): void {
    //
  }

  unmounted(): void {
    this.deregisterAll();
  }
}
</script>

<style lang="scss" scoped>
.el-form-item::v-deep(.el-form-item__label) {
  color: var(--parameter-color);
}

.el-form-item .el-slider {
  --el-slider-runway-bg-color: color-mix(
    in srgb,
    var(--state-color) 30%,
    transparent
  );
  --el-slider-disabled-color: var(--state-color);
}

.el-tabs::v-deep(.el-tabs__nav-next),
.el-tabs::v-deep(.el-tabs__nav-prev) {
  line-height: unset;
}
</style>
