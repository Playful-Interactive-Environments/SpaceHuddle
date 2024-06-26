<template>
  <div ref="mapSpace" class="mapSpace">
    <mgl-map
      v-if="showMap"
      :center="mapCenter"
      :zoom="mapZoom"
      language="en"
      :double-click-zoom="false"
      @map:load="onLoad"
      @map:zoomend="changeSection"
      @map:rotateend="changeSection"
      @map:dragend="changeSection"
    >
      <CustomMapMarker
        :coordinates="convertCoordinates(idea.parameter.position, idea)"
        :draggable="canChangePosition(idea)"
        v-for="idea of ideas"
        :key="idea.id"
        v-on:dragend="(marker) => ideaPositionChanged(marker, idea)"
        v-on:click="ideaSelected(idea)"
        color="#cc0000"
        :scale="0.5"
        :clone="true"
      >
        <template v-slot:icon>
          <slot name="marker" :idea="idea"></slot>
          <el-tooltip placement="top" effect="light" :hide-after="0">
            <template #content>
              <IdeaCard
                style="max-width: 10rem"
                :idea="idea"
                :is-editable="false"
              />
            </template>
            <font-awesome-icon
              icon="location-dot"
              class="pin"
              :class="{
                highlight: highlightCondition && highlightCondition(idea),
              }"
              :style="{
                '--pin-color':
                  idea.id === selectedIdeaId
                    ? MarkerColorSelected
                    : MarkerColor,
              }"
            />
          </el-tooltip>
          <el-avatar
            v-if="idea.image"
            :size="20"
            :src="idea.image"
            :alt="idea.keywords"
            class="pin-image"
          />
          <el-avatar
            v-else-if="idea.link"
            :size="20"
            :src="idea.link"
            :alt="idea.keywords"
            class="pin-image"
          />
        </template>
      </CustomMapMarker>
      <mgl-navigation-control position="bottom-left" />
    </mgl-map>

    <div class="overlay">
      <div class="el-dropdown-link" v-on:click="calculateMapBounds">
        <font-awesome-icon icon="arrows-to-circle" />
      </div>
      <el-dropdown v-on:command="mapstyleChange($event)">
        <div class="el-dropdown-link">
          <font-awesome-icon icon="map" />
        </div>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item
              v-for="mapType in Object.values(MapStyleType)"
              :key="mapType"
              :command="mapType"
            >
              <img width="50" :src="getPreviewUrl(mapType)" alt="mapType" />
            </el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Idea } from '@/types/api/Idea';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import {
  MglNavigationControl,
  MglMap,
  MglEvent,
  MglMarker,
} from 'vue-maplibre-gl';
import { LngLatLike, LngLatBoundsLike, Map } from 'maplibre-gl';
import CustomMapMarker from '@/components/shared/atoms/CustomMapMarker.vue';
import * as turf from '@turf/turf';
import * as mapStyle from '@/utils/mapStyle';
import * as themeColors from '@/utils/themeColors';
import { registerDomElement, unregisterDomElement } from '@/vunit';

/*export enum MapStyles {
  STREETS = 'streets-v2',
  SATELLITE = 'hybrid',
  BASIC = 'basic-v2',
  OUTDOORS = 'outdoor-v2',
  LIGHT = 'dataviz-light',
  DARK = 'dataviz-dark',
  WINTER = 'winter-v2',
}*/

@Options({
  components: {
    MglMarker,
    CustomMapMarker,
    IdeaCard,
    FontAwesomeIcon,
    MglNavigationControl,
    MglMap,
  },
  emits: [
    'ideaPositionChanged',
    'update:selectedIdea',
    'visibleIdeasChanged',
    'selectionColorChanged',
  ],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class IdeaMap extends Vue {
  @Prop({ default: [] }) readonly ideas!: Idea[];
  @Prop({ default: {} }) readonly parameter!: any;
  @Prop({ default: () => true }) readonly canChangePosition!: (
    idea: Idea
  ) => boolean;
  @Prop({ default: true }) readonly calculateSize!: boolean;
  @Prop({ default: null }) readonly selectedIdea!: Idea | null;
  @Prop({ default: () => false }) readonly highlightCondition!: (
    idea: Idea
  ) => boolean;
  visibleIdeas: Idea[] = [];

  mapCenter: [number, number] = [0, 0];
  mapIdeaCenter: [number, number] = [0, 0];
  mapZoom = 14;
  mapBounds: LngLatBoundsLike | null = null;
  sizeCalculated = false;
  map: Map | null = null;

  MapStyleType = mapStyle.MapStyleType;
  mapStyle: mapStyle.MapStyleType = mapStyle.MapStyleType.Streets;

  showMap = true;

  get MarkerColor(): string {
    switch (this.mapStyle) {
      case mapStyle.MapStyleType.Light:
      case mapStyle.MapStyleType.Terrain:
      case mapStyle.MapStyleType.Streets:
      case mapStyle.MapStyleType.Basic:
        return themeColors.getContrastColor();
      case mapStyle.MapStyleType.Dark:
        return themeColors.getStructuringColor();
    }
    return themeColors.getContrastColor();
  }

  get MarkerColorSelected(): string {
    switch (this.mapStyle) {
      case mapStyle.MapStyleType.Terrain:
        return themeColors.getEvaluatingColor();
      case mapStyle.MapStyleType.Light:
      case mapStyle.MapStyleType.Streets:
      case mapStyle.MapStyleType.Dark:
      case mapStyle.MapStyleType.Basic:
        return themeColors.getInformingColor();
    }
    return themeColors.getInformingColor();
  }

  getPreviewUrl(key: string): string {
    switch (key) {
      case mapStyle.MapStyleType.Streets:
        key = 'streets-v2';
        break;
      case mapStyle.MapStyleType.Basic:
        key = 'basic-v2';
        break;
      case mapStyle.MapStyleType.Terrain:
        key = 'outdoor';
        break;
      case mapStyle.MapStyleType.Light:
        key = 'streets-v2-light';
        break;
      case mapStyle.MapStyleType.Dark:
        key = 'streets-v2-dark';
        break;
    }
    return `https://media.maptiler.com/old/img/cloud/slider/${key}.png`;
  }

  domKey = '';
  mounted(): void {
    mapStyle.setMapStyle(this.mapStyle);
    if (this.calculateSize) {
      this.domKey = registerDomElement(
        this.$refs.mapSpace as HTMLElement,
        () => {
          this.sizeCalculated = true;
        },
        500
      );
    }
  }

  unmounted(): void {
    unregisterDomElement(this.domKey);
  }

  convertCoordinates(
    position: [number, number],
    idea: Idea | null = null
  ): LngLatLike {
    if (position) {
      return {
        lng: position[0],
        lat: position[1],
      };
    }
    if (this.map && idea) {
      const bounds = this.map.getBounds();
      const pos = turf.randomPosition([
        bounds._sw.lng,
        bounds._sw.lat,
        bounds._ne.lng,
        bounds._ne.lat,
      ]);
      idea.parameter.position = pos;
      this.$emit('ideaPositionChanged', idea);
      return {
        lng: pos[0],
        lat: pos[1],
      };
    }
    if (this.parameter.mapCenter) {
      return {
        lng: this.parameter.mapCenter[0],
        lat: this.parameter.mapCenter[1],
      };
    }
    return {
      lng: 0,
      lat: 0,
    };
  }

  @Watch('parameter.mapCenter', { immediate: true })
  @Watch('parameter.mapZoom', { immediate: true })
  async onParameterChanged(): Promise<void> {
    if (this.parameter.mapCenter) {
      this.mapCenter = this.parameter.mapCenter;
    }
    if (this.parameter.mapZoom) {
      this.mapZoom = this.parameter.mapZoom;
    }
    this.fitZoomToBounds();
  }

  @Watch('ideas', { immediate: true, deep: true })
  @Watch('ideas.length', { immediate: true })
  onIdeasChanged(): void {
    this.calculateMapBounds();
  }

  /*@Watch('ideas.length', { immediate: true })
  onIdeasLengthChanged(): void {
    this.showMap = false;
    setTimeout(() => (this.showMap = true), 100);
  }*/

  @Watch('selectedIdea', { immediate: true })
  onSelectedIdeaIdChanged(): void {
    if (this.selectedIdea) {
      if (this.selectedIdeaId !== this.selectedIdea.id) {
        this.selectedIdeaId = this.selectedIdea.id;
        if (this.map) {
          this.map.setCenter(
            this.convertCoordinates(this.selectedIdea.parameter.position)
          );
          this.changeSection();
        }
      }
    } else this.selectedIdeaId = '';
  }

  calculateMapBounds(): void {
    const setBounds = (
      center: [number, number],
      min: [number, number],
      max: [number, number]
    ): void => {
      const delta = 0.02;
      const minLngLat = this.convertCoordinates([
        min[0] - delta,
        min[1] - delta,
      ]);
      const maxLngLat = this.convertCoordinates([
        max[0] + delta,
        max[1] + delta,
      ]);
      this.mapBounds = [minLngLat, maxLngLat];
      this.fitZoomToBounds();
      this.mapIdeaCenter = center;
      this.changeSection();
    };

    const ideas = this.ideas.filter((idea) => idea.parameter.position);
    if (ideas.length > 0) {
      const points = turf.points(ideas.map((idea) => idea.parameter.position));
      const center: [number, number] = turf.center(points).geometry
        .coordinates as [number, number];

      const bounds = turf.envelope(points).geometry.coordinates[0] as [
        number,
        number
      ][];
      const min = bounds[0];
      const max = bounds[2];
      setBounds(center, min, max);
    } else {
      if (this.parameter.mapCenter) {
        const center = [...this.parameter.mapCenter] as [number, number];
        const min: [number, number] = [...center];
        const max: [number, number] = [...center];
        setBounds(center, min, max);
      } else this.mapBounds = null;
    }
  }

  selectedIdeaId = '';
  ideaSelected(idea: Idea): void {
    this.selectedIdeaId = idea.id;
    this.$emit('update:selectedIdea', idea);
  }

  ideaPositionChanged(marker: any, idea: Idea): void {
    const lngLat = marker.target._lngLat;
    if (
      idea.parameter.position[0] !== lngLat.lng ||
      idea.parameter.position[1] !== lngLat.lat
    ) {
      idea.parameter.position = [lngLat.lng, lngLat.lat];
      this.$emit('ideaPositionChanged', idea);
    }
  }

  fitZoomToBounds(waiteForLoading = true): void {
    if (this.map && this.mapBounds) {
      this.map.fitBounds(this.mapBounds, {
        duration: 0,
        animate: false,
        essential: true,
      });
    } else if (this.map) {
      this.map.setZoom(this.mapZoom);
      if (
        this.ideas.length > 0 &&
        this.mapIdeaCenter[0] !== 0 &&
        this.mapIdeaCenter[1] !== 0
      )
        this.map.setCenter(this.convertCoordinates(this.mapIdeaCenter));
      else this.map.setCenter(this.convertCoordinates(this.mapCenter));
    }

    if (waiteForLoading) {
      setTimeout(() => {
        this.fitZoomToBounds(false);
      }, 300);
    }
  }

  onLoad(e: MglEvent): void {
    this.map = e.map;
    this.fitZoomToBounds();
    this.mapstyleChange(this.mapStyle);
  }

  mapstyleChange(command: string): void {
    this.mapStyle = command as mapStyle.MapStyleType;
    if (this.map) {
      this.map.setStyle(mapStyle.getMapStyle(this.mapStyle), {
        diff: false,
      });
      setTimeout(() => {
        if (this.map && this.map.getStyle()) {
          const notNeededLayers = this.map.getStyle().layers.filter((layer) => {
            const layerCategory = layer['source-layer'];
            const layerType = layer['type'];
            if (layerCategory) {
              return layerType === 'symbol' && layerCategory !== 'place';
            }
            return false;
          });
          for (const layer of notNeededLayers) {
            this.map.removeLayer(layer.id);
          }
        }
      }, 500);
    }
    this.$emit('selectionColorChanged', this.MarkerColorSelected);
  }

  changeSection(): void {
    const oldVisibleIdeas = [...this.visibleIdeas];
    let visibilityAdded = false;
    if (this.map) {
      const visibleIdeas: Idea[] = [];
      const bounds = this.map.getBounds();
      for (const idea of this.ideas) {
        if (bounds.contains(this.convertCoordinates(idea.parameter.position))) {
          visibleIdeas.push(idea);
          if (!oldVisibleIdeas.find((old) => old.id === idea.id))
            visibilityAdded = true;
        }
      }
      this.visibleIdeas = visibleIdeas;
    } else this.visibleIdeas = [...this.ideas];
    if (
      oldVisibleIdeas.length !== this.visibleIdeas.length ||
      visibilityAdded
    ) {
      this.$emit('visibleIdeasChanged', this.visibleIdeas);
    }
  }
}
</script>

<style lang="scss" scoped>
@import '~maplibre-gl/dist/maplibre-gl.css';
@import '~vue-maplibre-gl/dist/vue-maplibre-gl.css';

.mapSpace {
  position: relative;
  height: 100%;
  width: 100%;

  .overlay {
    box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.1);
    background-color: white;
    border-radius: 0.5rem;
    position: absolute;
    z-index: 100;
    top: 0.5rem;
    right: 0.5rem;
    cursor: pointer;
  }

  .el-dropdown-link {
    background-color: white;
    border-radius: 50%;
    padding: 0.5rem;
  }

  .pin {
    --pin-color: var(--color-primary);
    font-size: var(--font-size-xxxlarge);
    color: var(--pin-color);
  }

  .pin-image {
    position: relative;
    left: -1.45rem;
    top: -0.5rem;
  }
}

.highlight::v-deep(path) {
  stroke: var(--color-informing-light);
  stroke-width: 5rem;
  stroke-linecap: round;
  stroke-linejoin: round;
  paint-order: stroke;
}
</style>
