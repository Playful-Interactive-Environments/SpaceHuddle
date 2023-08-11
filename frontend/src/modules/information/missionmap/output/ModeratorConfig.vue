<template>
  <el-form-item
    :label="$t('module.information.missionmap.moderatorConfig.mapSection')"
    :prop="`${rulePropPath}.mapSection`"
  >
    <div style="height: 200px">
      <mgl-map
        :center="mapCenter"
        :zoom="mapZoom"
        @map:zoomend="changeSection"
        @map:dragend="changeSection"
      >
        <mgl-navigation-control position="bottom-left" />
      </mgl-map>
    </div>
  </el-form-item>
  <el-form-item
    v-for="parameter of Object.keys(gameConfig.parameter)"
    :key="parameter"
    :label="$t(`module.information.missionmap.gameConfig.${parameter}`)"
    :prop="`${rulePropPath}.${parameter}`"
    :style="{ '--parameter-color': gameConfig.parameter[parameter].color }"
  >
    <template #label>
      {{ $t(`module.information.missionmap.gameConfig.${parameter}`) }}
      <font-awesome-icon :icon="gameConfig.parameter[parameter].icon" />
    </template>
    <el-slider
      v-if="modelValue"
      v-model="modelValue[parameter]"
      :min="-10"
      :max="10"
      show-stops
    />
  </el-form-item>
  <el-form-item
    :label="
      $t('module.information.missionmap.moderatorConfig.effectElectricity')
    "
    :prop="`${rulePropPath}.effectElectricity`"
  >
    <el-switch class="level-item" v-model="modelValue.effectElectricity" />
  </el-form-item>
  <el-form-item
    :label="$t('module.information.missionmap.moderatorConfig.minParticipants')"
    :prop="`${rulePropPath}.minParticipants`"
  >
    <el-input-number v-model="modelValue.minParticipants" :min="1" :max="100" />
  </el-form-item>
  <el-form-item
    :label="$t('module.information.missionmap.moderatorConfig.minPoints')"
    :prop="`${rulePropPath}.minPoints`"
  >
    <el-input-number
      v-model="modelValue.minPoints"
      :min="100"
      :max="modelValue.maxPoints"
    />
  </el-form-item>
  <el-form-item
    :label="$t('module.information.missionmap.moderatorConfig.maxPoints')"
    :prop="`${rulePropPath}.maxPoints`"
  >
    <el-input-number
      v-model="modelValue.maxPoints"
      :min="modelValue.minPoints"
      :max="10000"
    />
  </el-form-item>
  <el-form-item
    :label="$t('module.information.missionmap.moderatorConfig.explanation')"
    :prop="`${rulePropPath}.explanationList`"
  >
    <el-input
      v-for="(explanation, index) of modelValue.explanationList"
      :key="index"
      v-model="modelValue.explanationList[index]"
    >
      <template #prepend>
        <span style="width: 1.5rem">{{ index + 1 }}.</span>
      </template>
    </el-input>
  </el-form-item>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import { MglNavigationControl, MglMap } from 'vue-maplibre-gl';
import * as mapStyle from '@/utils/mapStyle';
import gameConfig from '@/modules/information/missionmap/data/gameConfig.json';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { defaultCenter } from '@/utils/map';

mapStyle.setMapStyleStreets();

@Options({
  computed: {
    gameConfig() {
      return gameConfig;
    },
  },
  components: {
    MglNavigationControl,
    MglMap,
    FontAwesomeIcon,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorConfig extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  @Prop() readonly rulePropPath!: string;

  @Prop() readonly moduleId!: string;
  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop({ default: {} }) modelValue!: any;

  mapCenter = [...defaultCenter] as [number, number];
  mapZoom = 5;

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue) {
      for (const parameter of Object.keys(gameConfig.parameter)) {
        if (!(parameter in this.modelValue)) {
          this.modelValue[parameter] = 0;
        }
      }
      if (!('effectElectricity' in this.modelValue)) {
        this.modelValue.effectElectricity = false;
      }
      if (!('minParticipants' in this.modelValue)) {
        this.modelValue.minParticipants = 3;
      }
      if (!('minPoints' in this.modelValue)) {
        this.modelValue.minPoints = 100;
      }
      if (!('maxPoints' in this.modelValue)) {
        this.modelValue.maxPoints = 1000;
      }
      if (!('explanationList' in this.modelValue)) {
        this.modelValue.explanationList = ['', '', ''];
      }
      if (!this.modelValue.mapCenter) {
        this.modelValue.mapCenter = this.mapCenter;
      } else {
        this.mapCenter = this.modelValue.mapCenter;
      }
      if (!this.modelValue.mapZoom) {
        this.modelValue.mapZoom = this.mapZoom;
      } else {
        this.mapZoom = this.modelValue.mapZoom;
      }
    }
  }

  changeSection(event: any): void {
    if (this.modelValue) {
      const center = event.map.getCenter();
      this.mapCenter = [center.lng, center.lat];
      this.mapZoom = event.map.getZoom();
      this.modelValue.mapCenter = this.mapCenter;
      this.modelValue.mapZoom = this.mapZoom;
    }
  }
}
</script>

<style lang="scss" scoped>
.el-form-item::v-deep(.el-form-item__label) {
  color: var(--parameter-color);
}
</style>
