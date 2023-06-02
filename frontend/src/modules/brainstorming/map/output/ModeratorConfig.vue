<template>
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

MglDefaults.style = `https://api.maptiler.com/maps/streets/style.json?key=${process.env.VUE_APP_MAPTILER_KEY}`;

@Options({
  components: {
    MglNavigationControl,
    MglMap,
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
  mapZoom = 5;

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue) {
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
