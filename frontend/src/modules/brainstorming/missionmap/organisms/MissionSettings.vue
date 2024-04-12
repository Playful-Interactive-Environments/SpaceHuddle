<template>
  <IdeaSettings
    v-model:show-modal="showSettings"
    :taskId="taskId"
    :idea="idea"
    :title="title"
    :auth-header-typ="authHeaderTyp"
    @updateData="updateData"
    ref="ideaSettings"
  >
    <el-form-item
      :label="$t('module.brainstorming.missionmap.moderatorContent.points')"
      :prop="`parameter.points`"
    >
      <el-slider
        v-model="idea.parameter.points"
        :min="500"
        :max="10000"
        :step="500"
        :show-stops="true"
        :marks="calculateMarks(1000, 10000, 1000)"
      />
    </el-form-item>
    <!--<el-form-item
      :label="
        $t('module.brainstorming.missionmap.moderatorContent.minParticipants')
      "
      :prop="`parameter.minParticipants`"
    >
      <el-slider
        v-model="idea.parameter.minParticipants"
        :min="2"
        :max="30"
        :step="1"
        :show-stops="true"
        :marks="calculateMarks(5, 30, 5)"
      />
    </el-form-item>
    <el-form-item
      :label="$t('module.brainstorming.missionmap.moderatorContent.minPoints')"
      :prop="`parameter.minPoints`"
    >
      <el-slider
        v-model="idea.parameter.minPoints"
        :min="100"
        :max="idea.parameter.maxPoints"
        :step="100"
        :show-stops="true"
        :marks="calculateMarks(100, idea.parameter.maxPoints, 100, 10)"
      />
    </el-form-item>
    <el-form-item
      :label="$t('module.brainstorming.missionmap.moderatorContent.maxPoints')"
      :prop="`parameter.maxPoints`"
    >
      <el-slider
        v-model="idea.parameter.maxPoints"
        :min="idea.parameter.minPoints"
        :max="idea.parameter.points"
        :step="100"
        :show-stops="true"
        :marks="
          calculateMarks(
            idea.parameter.minPoints,
            idea.parameter.points,
            100,
            10
          )
        "
      />
    </el-form-item>-->
    <el-form-item
      v-for="parameter of Object.keys(gameConfig.parameter)"
      :key="parameter"
      :label="$t(`module.brainstorming.missionmap.gameConfig.${parameter}`)"
      :prop="`parameter.influenceAreas.${parameter}`"
      :style="{ '--parameter-color': gameConfig.parameter[parameter].color }"
    >
      <template #label>
        {{ $t(`module.brainstorming.missionmap.gameConfig.${parameter}`) }}
        <font-awesome-icon :icon="gameConfig.parameter[parameter].icon" />
      </template>
      <el-slider
        v-if="idea.parameter.influenceAreas"
        v-model="idea.parameter.influenceAreas[parameter]"
        :min="-5"
        :max="5"
        :show-stops="true"
        :marks="calculateMarks(-5, 5, 1)"
      />
    </el-form-item>
    <!--
      :rules="[{ validator: validateElectricity }]"
    --->
    <!--<el-form-item
      v-for="parameter of Object.keys(additionalParameter)"
      :key="parameter"
      :label="$t(`module.playing.moveit.enums.electricity.${parameter}`)"
      :prop="`parameter.electricity.${parameter}`"
      :style="{
        '--parameter-color': additionalParameter[parameter].color,
      }"
    >
      <template #label>
        {{ $t(`module.playing.moveit.enums.electricity.${parameter}`) }}
        <font-awesome-icon :icon="additionalParameter[parameter].icon" />
      </template>
      <el-input-number
        v-if="idea.parameter.electricity"
        v-model="idea.parameter.electricity[parameter]"
        :min="-100"
        :max="100"
      />
    </el-form-item>-->
    <el-form-item
      v-if="effectElectricity && idea.parameter.electricity"
      :label="
        $t(
          'module.brainstorming.missionmap.moderatorContent.electricityInfluence'
        )
      "
      :prop="`parameter.electricity`"
      class="electricity"
    >
      <el-select v-model="idea.parameter.electricity.influence">
        <el-option
          v-for="item of Object.values(ElectricityInfluence)"
          :key="item"
          :value="item"
          :label="
            $t(
              `module.brainstorming.missionmap.enum.electricityInfluence.${item}`
            )
          "
        />
      </el-select>
      <el-select
        v-if="
          idea.parameter.electricity.influence ===
          ElectricityInfluence.CHANGE_ELECTRICITY_SUPPLY
        "
        v-model="idea.parameter.electricity.type"
      >
        <el-option
          v-for="item in Object.keys(additionalParameter)"
          :key="item"
          :value="item"
          :label="$t(`module.playing.moveit.enums.electricity.${item}`)"
          :style="{ color: additionalParameter[item].color }"
        >
          {{ $t(`module.playing.moveit.enums.electricity.${item}`) }}
          <font-awesome-icon :icon="additionalParameter[item].icon" />
        </el-option>
      </el-select>
      <el-select v-else v-model="idea.parameter.electricity.type">
        <el-option
          v-for="item in Object.keys(ElectricityConsumption)"
          :key="item"
          :value="item"
          :label="
            $t(
              `module.brainstorming.missionmap.enum.electricityConsumption.${item}`
            )
          "
          :style="{ color: ElectricityConsumption[item].color }"
        />
      </el-select>
      <el-slider
        v-if="
          idea.parameter.electricity.influence ===
          ElectricityInfluence.CHANGE_ELECTRICITY_SUPPLY
        "
        v-model="idea.parameter.electricity.value"
        :min="0"
        :max="50"
        :marks="{
          0: $t(
            'module.brainstorming.missionmap.moderatorContent.electricity.none'
          ),
          50: {
            style: {
              color: getGreenColor(),
              '--translate': '-100%',
            },
            label: `50%${$t(
              'module.brainstorming.missionmap.moderatorContent.electricity.max-supply'
            )}`,
          },
        }"
        @change="calculateElectricityMix"
      />
      <el-slider
        v-else
        v-model="idea.parameter.electricity.value"
        :min="-100"
        :max="100"
        :marks="{
          '-100': {
            style: {
              color: getRedColor(),
              '--translate': 0,
            },
            label: `-100%${$t(
              'module.brainstorming.missionmap.moderatorContent.electricity.min-demand'
            )}`,
          },
          '-50': {
            style: {
              color: getRedColor(),
            },
            label: `-50%`,
          },
          0: $t(
            'module.brainstorming.missionmap.moderatorContent.electricity.none'
          ),
          50: {
            style: {
              color: getGreenColor(),
            },
            label: `50%`,
          },
          100: {
            style: {
              color: getGreenColor(),
              '--translate': '-100%',
            },
            label: `100%${$t(
              'module.brainstorming.missionmap.moderatorContent.electricity.max-demand'
            )}`,
          },
        }"
        @change="calculateElectricityMix"
      />
      <div class="columns">
        <div class="chart column">
          <Doughnut
            ref="chartElectricityMixRef"
            :data="chartDataElectricityMix"
            :options="{
              responsive: true,
              maintainAspectRatio: false,
              animation: {
                duration: 0,
              },
              plugins: {
                legend: {
                  position: 'right',
                  labels: {
                    color: '#000000',
                  },
                },
                title: {
                  display: true,
                  text: $t(
                    'module.playing.moveit.participant.info.electricity.scale'
                  ),
                },
              },
            }"
          />
        </div>
        <div class="chart column">
          <Bar
            ref="chartElectricityAmountRef"
            :data="chartDataElectricityAmount"
            :height="150"
            :options="{
              maintainAspectRatio: true,
              animation: {
                duration: 0,
              },
              scales: {
                x: {
                  stacked: true,
                },
                y: {
                  stacked: true,
                  ticks: {
                    precision: 0,
                  },
                },
              },
              plugins: {
                legend: {
                  position: 'right',
                  labels: {
                    color: '#000000',
                  },
                },
                title: {
                  display: true,
                  text: $t(
                    'module.brainstorming.missionmap.participant.chart.y-electricityAmount'
                  ),
                },
              },
            }"
          />
        </div>
      </div>
    </el-form-item>
    <el-form-item
      v-if="authHeaderTyp === EndpointAuthorisationType.MODERATOR"
      :label="
        $t('module.brainstorming.missionmap.moderatorContent.explanation')
      "
      :prop="`parameter.explanationList`"
    >
      <div
        v-for="(explanation, index) of idea.parameter.explanationList"
        :key="index"
      >
        <el-input v-model="idea.parameter.explanationList[index]">
          <template #prepend>
            <span style="width: 1.5rem">{{ index + 1 }}.</span>
            <div @click="() => idea.parameter.explanationList.splice(index, 1)">
              <font-awesome-icon icon="trash" />
            </div>
          </template>
        </el-input>
      </div>
      <AddItem
        :text="
          $t('module.brainstorming.missionmap.moderatorContent.addExplanation')
        "
        @addNew="() => idea.parameter.explanationList.push('')"
      />
    </el-form-item>
    <!--<el-form-item
      :label="$t('module.brainstorming.missionmap.moderatorContent.share')"
      :prop="`parameter.shareData`"
    >
      <el-switch v-model="idea.parameter.shareData" />
    </el-form-item>-->
    <el-form-item
      v-if="
        authHeaderTyp === EndpointAuthorisationType.MODERATOR &&
        idea.participantId
      "
      :label="$t('module.brainstorming.missionmap.moderatorContent.evaluation')"
      :prop="`parameter.shareData`"
    >
      <span
        class="link"
        :class="{
          'is-active': idea.state?.toLowerCase() === IdeaStates.THUMBS_UP,
        }"
        @click="
          () => {
            idea.parameter.shareData = true;
            idea.state = IdeaStates.THUMBS_UP;
          }
        "
      >
        <font-awesome-icon icon="thumbs-up" />
      </span>
      <span
        class="link"
        :class="{
          'is-active': idea.state?.toLowerCase() === IdeaStates.THUMBS_DOWN,
        }"
        @click="
          () => {
            idea.parameter.shareData = false;
            idea.state = IdeaStates.THUMBS_DOWN;
          }
        "
      >
        <font-awesome-icon icon="thumbs-down" />
      </span>
      <div v-if="idea.state?.toLowerCase() === IdeaStates.THUMBS_DOWN">
        <el-input
          v-model="idea.parameter.reasonsForRejection"
          :rows="3"
          type="textarea"
          :placeholder="
            $t(
              'module.brainstorming.missionmap.moderatorContent.reasonsForRejection'
            )
          "
        />
      </div>
    </el-form-item>
    <el-form-item
      v-if="authHeaderTyp === EndpointAuthorisationType.PARTICIPANT"
      :label="
        $t('module.brainstorming.missionmap.moderatorContent.mapLocation')
      "
      :prop="`parameter.mapLocation`"
    >
      <div style="height: var(--map-settings-height)">
        <mgl-map
          :center="mapCenter"
          :zoom="mapZoom"
          :double-click-zoom="false"
          @map:load="onLoad"
        >
          <CustomMapMarker
            :coordinates="convertCoordinates(idea.parameter.position)"
            :draggable="true"
            v-on:dragend="positionChanged"
          >
            <template v-slot:icon>
              <font-awesome-icon icon="location-crosshairs" class="pin" />
            </template>
          </CustomMapMarker>

          <mgl-navigation-control position="bottom-left" />
        </mgl-map>
      </div>
    </el-form-item>
  </IdeaSettings>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import IdeaStates from '@/types/enum/IdeaStates';
import { ValidationRules } from '@/types/ui/ValidationRule';
import IdeaSettings from '@/components/moderator/organisms/settings/IdeaSettings.vue';
import ValidationForm from '@/components/shared/molecules/ValidationForm.vue';
import gameConfigMoveIt from '@/modules/playing/moveit/data/gameConfig.json';
import * as cashService from '@/services/cash-service';
import { Module } from '@/types/api/Module';
import { calculateMarks } from '@/utils/element-plus';
import gameConfig from '@/modules/brainstorming/missionmap/data/gameConfig.json';
import { MglEvent, MglMap, MglNavigationControl } from 'vue-maplibre-gl';
import CustomMapMarker from '@/components/shared/atoms/CustomMapMarker.vue';
import { LngLatLike, Map } from 'maplibre-gl';
import * as turf from '@turf/turf';
import { until } from '@/utils/wait';
import { defaultCenter } from '@/utils/map';
import * as moduleService from '@/services/module-service';
import { ElectricityInfluence } from '@/modules/brainstorming/missionmap/types/ElectricityInfluence';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import { getGreenColor, getRedColor } from '@/utils/themeColors';
import { Bar, Doughnut } from 'vue-chartjs';
import { ChartData } from 'chart.js';
import * as progress from '@/modules/brainstorming/missionmap/utils/progress';
import { ElectricityConsumption } from '@/modules/brainstorming/missionmap/types/ElectricityConsumption';

@Options({
  methods: { getRedColor, getGreenColor },
  computed: {
    EndpointAuthorisationType() {
      return EndpointAuthorisationType;
    },
    IdeaStates() {
      return IdeaStates;
    },
    gameConfig() {
      return gameConfig;
    },
  },
  components: {
    FontAwesomeIcon,
    IdeaSettings,
    ValidationForm,
    MglNavigationControl,
    MglMap,
    CustomMapMarker,
    AddItem,
    Doughnut,
    Bar,
  },
  emits: ['update:showModal', 'updateData'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class MissionSettings extends Vue {
  @Prop({ default: false }) showModal!: boolean;
  @Prop({ default: null }) title!: string | null;
  @Prop({ default: null }) taskId!: string | null;
  @Prop({ default: null }) moduleId!: string | null;
  @Prop() idea!: Idea;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;

  module: Module | null = null;
  calculateMarks = calculateMarks;
  ElectricityInfluence = ElectricityInfluence;
  ElectricityConsumption = ElectricityConsumption;

  map: Map | null = null;
  mapCenter = [...defaultCenter] as [number, number];
  mapZoom = 5;

  chartDataElectricityMix: ChartData = {
    labels: [],
    datasets: [],
  };
  chartDataElectricityAmount: ChartData = {
    labels: [],
    datasets: [],
  };

  get effectElectricity(): boolean | null {
    return this.module && this.module.parameter.effectElectricity;
  }

  get additionalParameter(): any {
    if (this.module && this.module.parameter.effectElectricity)
      return gameConfigMoveIt.electricity;
    return {};
  }

  get showSettings(): boolean {
    return this.showModal;
  }

  set showSettings(value: boolean) {
    this.$emit('update:showModal', value);
  }

  get ideaRange(): [number, number] {
    if (this.idea && this.idea.parameter)
      return [this.idea.parameter.minPoints, this.idea.parameter.maxPoints];
    return [0, 100];
  }

  set ideaRange(value: [number, number]) {
    if (this.idea && this.idea.parameter) {
      this.idea.parameter.minPoints = value[0];
      this.idea.parameter.maxPoints = value[1];
    }
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateModule);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  @Watch('idea.parameter.points', { immediate: true })
  onPointsChanged(): void {
    if (this.idea.parameter.points) {
      this.idea.parameter.maxPoints =
        Math.round(
          Math.min(
            (this.idea.parameter.points / 3) * 2,
            this.idea.parameter.points -
              (this.idea.parameter.minParticipants - 1) *
                this.idea.parameter.minPoints
          ) / 10
        ) * 10;
      if (this.idea.parameter.maxPoints < this.idea.parameter.minPoints * 2)
        this.idea.parameter.maxPoints = this.idea.parameter.minPoints * 2;
      if (
        this.module &&
        this.module.parameter.maxPoints &&
        this.module.parameter.maxPoints < this.idea.parameter.maxPoints
      ) {
        this.idea.parameter.maxPoints = this.module.parameter.maxPoints;
      }
    }
  }

  @Watch('moduleId', { immediate: true })
  onModuleIdChanged(): void {
    if (this.moduleId) {
      moduleService.registerGetModuleById(
        this.moduleId,
        this.updateModule,
        this.authHeaderTyp,
        60 * 60
      );
    }
  }

  updateModule(module: Module): void {
    this.module = module;

    if (module.parameter.mapCenter) {
      this.mapCenter = module.parameter.mapCenter;
    }
    if (module.parameter.mapZoom) {
      this.mapZoom = module.parameter.mapZoom;
    }
  }

  validateElectricity(
    rule: ValidationRules,
    value: string,
    // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
    callback: any
  ): boolean {
    const parameterList = Object.keys(this.additionalParameter);
    if (parameterList.length === 0) {
      callback();
      return true;
    }
    let sum = 0;
    for (const parameterName of parameterList) {
      const parameterValue = this.idea.parameter.electricity[parameterName];
      sum += parameterValue;
    }
    const form = (this.$refs.ideaSettings as IdeaSettings).$refs
      .dataForm as ValidationForm;
    form.clearValidate();
    if (sum === 0) {
      callback();
      return true;
    } else {
      const errorText = (this as any).$t(
        'module.brainstorming.missionmap.moderatorContent.electricityValidationErrors'
      );
      callback(new Error(`${errorText} ${sum}`));
      return false;
    }
  }

  @Watch('idea.parameter.electricity.influence', { immediate: true })
  onInfluenceChanged(): void {
    if (this.idea?.parameter?.electricity) {
      if (
        this.idea.parameter.electricity.influence ===
        ElectricityInfluence.INCREASE_ELECTRICITY_DEMAND
      ) {
        this.idea.parameter.electricity.type = Object.keys(
          ElectricityConsumption
        )[0];
      } else {
        this.idea.parameter.electricity.type = Object.keys(
          gameConfigMoveIt.electricity
        )[0];
      }
    }
  }

  @Watch('idea.parameter.electricity.influence', { immediate: true })
  @Watch('idea.parameter.electricity.type', { immediate: true })
  calculateElectricityMix(): void {
    if (this.idea?.parameter?.electricity) {
      const data: ChartData = {
        labels: [],
        datasets: [
          {
            label: this.$t('module.playing.moveit.participant.electricity'),
            backgroundColor: [],
            data: [],
          },
        ],
      };
      const progressData = progress.getElectricityDevelopment(
        this.idea.parameter.electricity.influence,
        this.idea.parameter.electricity.type,
        this.idea.parameter.electricity.value
      );
      for (const energySource of Object.keys(progressData)) {
        if (data.labels)
          data.labels.push(
            this.$t(`module.playing.moveit.enums.electricity.${energySource}`)
          );
        data.datasets[0].data.push(progressData[energySource]);
        (data.datasets[0].backgroundColor as string[]).push(
          gameConfigMoveIt.electricity[energySource].color
        );
      }
      this.chartDataElectricityMix = data;
      if (this.$refs.chartElectricityMixRef) {
        const chartRef = this.$refs.chartElectricityMixRef as any;
        if (chartRef.chart) {
          chartRef.chart.data = this.chartDataElectricityMix;
          chartRef.chart.update();
        }
      }
    }
    this.calculateElectricityAmount();
  }

  calculateElectricityAmount(): void {
    if (!this.idea?.parameter?.electricity) return;
    const labels: string[] = [
      '2022',
      this.$t('module.brainstorming.missionmap.moderatorContent.future'),
    ];
    const datasets: any[] = [];
    let residualValue = 0;
    let totalElectricityAmount = 0;
    for (const sector of Object.keys(ElectricityConsumption)) {
      totalElectricityAmount += ElectricityConsumption[sector].value;
    }
    for (const sector of Object.keys(ElectricityConsumption)) {
      const valueNew = ElectricityConsumption[sector].value;
      const valueFuture =
        sector === this.idea.parameter.electricity.type
          ? valueNew +
            (this.idea.parameter.electricity.value * totalElectricityAmount) /
              100
          : valueNew;
      if (valueFuture < 0) {
        residualValue = valueFuture;
      }
      const data: number[] = [valueNew, valueFuture > 0 ? valueFuture : 0];
      datasets.push({
        label: (this as any).$t(
          `module.brainstorming.missionmap.enum.electricityConsumption.${sector}`
        ),
        backgroundColor: ElectricityConsumption[sector].color,
        data: data,
      });
    }
    if (residualValue < 0) {
      for (const dataset of datasets) {
        dataset.data[1] += residualValue;
        if (dataset.data[1] < 0) {
          residualValue = dataset.data[1];
          dataset.data[1] = 0;
        } else break;
      }
    }
    this.chartDataElectricityAmount = {
      labels: labels,
      datasets: datasets,
    };
    if (this.$refs.chartElectricityAmountRef) {
      const chartRef = this.$refs.chartElectricityAmountRef as any;
      if (chartRef.chart) {
        chartRef.chart.data = this.chartDataElectricityAmount;
        chartRef.chart.update();
      }
    }
  }

  convertCoordinates(position: [number, number]): LngLatLike {
    if (position) {
      return {
        lng: position[0],
        lat: position[1],
      };
    }
    if (this.map) {
      const bounds = this.map.getBounds();
      const pos = turf.randomPosition([
        bounds._sw.lng,
        bounds._sw.lat,
        bounds._ne.lng,
        bounds._ne.lat,
      ]);
      this.idea.parameter.position = pos;
      return {
        lng: pos[0],
        lat: pos[1],
      };
    } else {
      until(() => this.map).then(() => {
        if (this.map) {
          const bounds = this.map.getBounds();
          this.idea.parameter.position = turf.randomPosition([
            bounds._sw.lng,
            bounds._sw.lat,
            bounds._ne.lng,
            bounds._ne.lat,
          ]);
        }
      });
    }
    if (this.mapCenter) {
      return {
        lng: this.mapCenter[0],
        lat: this.mapCenter[1],
      };
    }
    return {
      lng: 0,
      lat: 0,
    };
  }

  onLoad(e: MglEvent): void {
    this.map = e.map;
  }

  positionChanged(marker: any): void {
    const lngLat = marker.target._lngLat;
    this.idea.parameter.position = [lngLat.lng, lngLat.lat];
  }

  updateData(data: Idea): void {
    data.parameter.explanationList = data.parameter.explanationList.filter(
      (item) => item
    );
    this.$emit('updateData', data);
  }
}
</script>

<style lang="scss" scoped>
.el-form-item::v-deep(.el-form-item__label) {
  color: var(--parameter-color);
}

.link {
  font-size: var(--font-size-xxlarge);
  color: var(--color-dark-contrast-light);
  padding-right: 0.5rem;
}

.is-active {
  color: var(--color-dark-contrast-dark);
}

.pin {
  --pin-color: var(--color-primary);
  font-size: var(--font-size-xxxlarge);
  color: var(--pin-color);
}

.electricity {
  .el-select {
    margin-right: 1rem;
  }
}

.el-slider::v-deep(.el-slider__marks-text) {
  --translate: -50%;
  transform: translateX(var(--translate));
}

.chart {
  padding-top: 1rem;
}
</style>
