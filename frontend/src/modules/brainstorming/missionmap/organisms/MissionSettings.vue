<template>
  <IdeaSettings
    v-model:show-modal="showSettings"
    :taskId="taskId"
    :idea="idea"
    :title="title"
    :auth-header-typ="authHeaderTyp"
    @updateData="(data) => $emit('updateData', data)"
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
    <el-form-item
      :label="
        $t('module.brainstorming.missionmap.moderatorConfig.minParticipants')
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
      :label="$t('module.brainstorming.missionmap.moderatorConfig.minPoints')"
      :prop="`parameter.minPoints`"
    >
      <!--<el-slider
        v-model="ideaRange"
        range
        :step="100"
        :min="100"
        :max="idea.parameter.points"
        :show-stops="true"
        :marks="calculateMarks(1000, idea.parameter.points, 1000)"
      />-->
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
      :label="$t('module.brainstorming.missionmap.moderatorConfig.maxPoints')"
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
    </el-form-item>
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
    <el-form-item
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
    </el-form-item>
    <el-form-item
      v-if="authHeaderTyp === EndpointAuthorisationType.MODERATOR"
      :label="$t('module.brainstorming.missionmap.moderatorConfig.explanation')"
      :prop="`parameter.explanationList`"
    >
      <el-input
        v-for="(explanation, index) of idea.parameter.explanationList"
        :key="index"
        v-model="idea.parameter.explanationList[index]"
      >
        <template #prepend>
          <span style="width: 1.5rem">{{ index + 1 }}.</span>
        </template>
      </el-input>
    </el-form-item>
    <!--<el-form-item
      :label="$t('module.brainstorming.missionmap.moderatorContent.share')"
      :prop="`parameter.shareData`"
    >
      <el-switch v-model="idea.parameter.shareData" />
    </el-form-item>-->
    <el-form-item
      v-if="authHeaderTyp === EndpointAuthorisationType.MODERATOR"
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

@Options({
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
    IdeaSettings,
    ValidationForm,
    MglNavigationControl,
    MglMap,
    CustomMapMarker,
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

  map: Map | null = null;
  mapCenter = [...defaultCenter] as [number, number];
  mapZoom = 5;

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

  @Watch('moduleId', { immediate: true })
  onModuleIdChanged(): void {
    if (this.moduleId) {
      moduleService.registerGetModuleById(
        this.moduleId,
        this.updateModule,
        EndpointAuthorisationType.PARTICIPANT,
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
</style>
