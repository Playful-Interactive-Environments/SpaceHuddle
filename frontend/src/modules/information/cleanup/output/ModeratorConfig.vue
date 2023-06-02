<template>
  test
  <el-form-item
    :label="$t('module.brainstorming.map.moderatorConfig.mapSection')"
    :prop="`${rulePropPath}.mapSection`"
  >
    <div style="height: 200px">
      <mgl-map
        v-model:center="mapCenter"
        v-model:zoom="mapZoom"
        v-on:map:zoomend="changeSection"
        v-on:map:dragend="changeSection"
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
        <CustomMapMarker
          :coordinates="mapEnd"
          :draggable="true"
          v-on:dragend="endPositionChanged"
        >
          <template v-slot:icon>
            <font-awesome-icon icon="flag-checkered" class="pin" />
          </template>
        </CustomMapMarker>

        <mgl-navigation-control position="bottom-left" />
      </mgl-map>
    </div>
  </el-form-item>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import { MglDefaults, MglNavigationControl, MglMap } from 'vue-maplibre-gl';
import CustomMapMarker from '@/components/shared/atoms/CustomMapMarker.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

MglDefaults.style = `https://api.maptiler.com/maps/streets/style.json?key=${process.env.VUE_APP_MAPTILER_KEY}`;

@Options({
  components: {
    FontAwesomeIcon,
    MglNavigationControl,
    MglMap,
    CustomMapMarker,
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

  mapCenter = [14.511986682000128, 48.36875256196966];
  mapStart = [14.511986682000128, 48.36875256196966];
  mapEnd = [14.511986682000128, 48.36875256196966];
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
      if (!this.modelValue.mapEnd) {
        this.modelValue.mapEnd = [...this.mapEnd];
      } else {
        this.mapEnd = [...this.modelValue.mapEnd];
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
      if (
        this.modelValue.mapCenter[0] === this.modelValue.mapEnd[0] &&
        this.modelValue.mapCenter[1] === this.modelValue.mapEnd[1]
      ) {
        this.mapEnd = [...this.mapCenter];
        this.modelValue.mapEnd = [...this.mapCenter];
      }
      this.modelValue.mapCenter = [...this.mapCenter];
      this.modelValue.mapZoom = this.mapZoom;
    }
  }

  startPositionChanged(marker: any): void {
    const lngLat = marker.target._lngLat;
    this.modelValue.mapStart = [lngLat.lng, lngLat.lat];
  }

  endPositionChanged(marker: any): void {
    const lngLat = marker.target._lngLat;
    this.modelValue.mapEnd = [lngLat.lng, lngLat.lat];
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
