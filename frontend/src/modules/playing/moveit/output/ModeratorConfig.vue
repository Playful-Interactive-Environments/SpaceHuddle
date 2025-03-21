<template>
  <div>
    <el-form-item
      :label="$t('module.playing.moveit.moderatorConfig.replayable')"
      :prop="`${rulePropPath}.replayable`"
    >
      <el-switch class="level-item" v-model="modelValue.replayable" />
    </el-form-item>
    <el-form-item
      :label="$t('module.playing.moveit.moderatorConfig.showTutorial')"
      :prop="`${rulePropPath}.showTutorial`"
    >
      <el-radio-group v-model="modelValue.showTutorial">
        <el-radio-button :label="0">{{
          $t('module.playing.moveit.moderatorConfig.showTutorialDisabled')
        }}</el-radio-button>
        <el-radio-button :label="1">{{
          $t('module.playing.moveit.moderatorConfig.showTutorialOnce')
        }}</el-radio-button>
        <el-radio-button :label="2">{{
          $t('module.playing.moveit.moderatorConfig.showTutorialAlways')
        }}</el-radio-button>
      </el-radio-group>
    </el-form-item>
    <el-form-item
      :label="$t('module.playing.moveit.moderatorConfig.mapSection')"
      :prop="`${rulePropPath}.mapSection`"
    >
      <div style="height: var(--map-settings-height)">
        <mgl-map
          :center="mapCenter"
          :zoom="mapZoom"
          :double-click-zoom="false"
          @map:zoomend="changeSection"
          @map:dragend="changeSection"
        >
          <CustomMapMarker
            :coordinates="mapStart"
            :draggable="true"
            v-on:dragend="startPositionChanged"
          >
            <template v-slot:icon>
              <font-awesome-icon icon="location-crosshairs" class="pin" />
            </template>
          </CustomMapMarker>

          <mgl-navigation-control position="bottom-left" />
        </mgl-map>
      </div>
    </el-form-item>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import { MglNavigationControl, MglMap } from 'vue-maplibre-gl';
import CustomMapMarker from '@/components/shared/atoms/CustomMapMarker.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import * as mapStyle from '@/utils/mapStyle';
import { defaultCenter } from '@/utils/map';

mapStyle.setMapStyleStreets();

@Options({
  components: {
    FontAwesomeIcon,
    MglNavigationControl,
    MglMap,
    CustomMapMarker,
  },
  emits: ['update'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorConfig extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  @Prop() readonly rulePropPath!: string;

  @Prop() readonly moduleId!: string;
  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop({ default: {} }) modelValue!: any;
  @Prop({ default: {} }) formData!: any;
  @Prop({ default: {} }) taskType!: any;

  mapCenter = [...defaultCenter];
  mapStart = [...defaultCenter];
  mapZoom = 5;

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue) {
      if (!this.modelValue.mapCenter) {
        this.modelValue.mapCenter = [...this.mapCenter];
      } else {
        this.mapCenter = [...this.modelValue.mapCenter];
      }
      if (!this.modelValue.mapZoom) {
        this.modelValue.mapZoom = this.mapZoom;
      } else {
        this.mapZoom = this.modelValue.mapZoom;
      }
      if (!this.modelValue.mapStart) {
        this.modelValue.mapStart = [...this.mapStart];
      } else {
        this.mapStart = [...this.modelValue.mapStart];
      }
      if (!('replayable' in this.modelValue)) {
        this.modelValue.replayable = true;
      }
      if (!('showTutorial' in this.modelValue)) {
        this.modelValue.showTutorial = 1;
      }
    }
  }

  changeSection(event: any): void {
    if (this.modelValue) {
      const center = event.map.getCenter();
      this.mapCenter = [center.lng, center.lat];
      this.mapZoom = event.map.getZoom();
      if (
        this.modelValue.mapCenter[0] === this.modelValue.mapStart[0] &&
        this.modelValue.mapCenter[1] === this.modelValue.mapStart[1]
      ) {
        this.mapStart = [...this.mapCenter];
        this.modelValue.mapStart = [...this.mapCenter];
      }
      this.modelValue.mapCenter = [...this.mapCenter];
      this.modelValue.mapZoom = this.mapZoom;
    }
  }

  startPositionChanged(marker: any): void {
    const lngLat = marker.target._lngLat;
    this.modelValue.mapStart = [lngLat.lng, lngLat.lat];
  }
}
</script>

<style lang="scss" scoped>
.pin {
  --pin-color: var(--color-primary);
  font-size: var(--font-size-xxxlarge);
  color: var(--pin-color);
}
</style>
