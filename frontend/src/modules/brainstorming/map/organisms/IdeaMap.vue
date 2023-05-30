<template>
  <div ref="mapSpace" class="mapSpace">
    <mapbox-map
      v-if="MapboxKey && (sizeCalculated || !calculateSize)"
      :accessToken="MapboxKey"
      :center="mapCenter"
      :zoom="mapZoom"
      v-on:loaded="mapLoaded"
      v-on:zoomend="changeSection"
      v-on:dragend="changeSection"
    >
      <mapbox-marker
        :lngLat="idea.parameter.position"
        :draggable="canChangePosition"
        v-for="idea of ideas"
        :key="idea.id"
        v-on:dragend="(marker) => ideaPositionChanged(marker, idea)"
        v-on:click="ideaSelected(idea)"
      >
        <template v-slot:icon>
          <el-tooltip placement="top" effect="light" :hide-after="0">
            <template #content>
              <IdeaCard
                style="max-width: 10vw"
                :idea="idea"
                :is-editable="false"
              />
            </template>
            <font-awesome-icon
              icon="location-dot"
              class="pin"
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
      </mapbox-marker>

      <mapbox-navigation-control position="bottom-left" />
    </mapbox-map>

    <div class="overlay">
      <el-dropdown v-on:command="mapstyleChange($event)">
        <div class="el-dropdown-link">
          <font-awesome-icon icon="map" />
        </div>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item
              v-for="mapType in Object.values(MapStyles)"
              :key="mapType"
              :command="mapType"
            >
              <img
                width="50"
                :src="`/assets/images/mapstyles/${mapType}.png`"
                alt="mapType"
              />
            </el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
      <div class="el-dropdown-link" v-on:click="calculateMapBounds">
        <font-awesome-icon icon="arrows-to-circle" />
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import {
  MapboxMap,
  MapboxMarker,
  MapboxNavigationControl,
} from 'vue-mapbox-ts';
import { Map, LngLat, LngLatBoundsLike, LngLatBounds } from 'mapbox-gl';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Idea } from '@/types/api/Idea';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';

export enum MapStyles {
  OUTDOORS = 'outdoors-v12',
  SATELLITE = 'satellite-v9',
  STREETS = 'streets-v12',
  LIGHT = 'light-v11',
  DARK = 'dark-v11',
}

@Options({
  components: {
    IdeaCard,
    FontAwesomeIcon,
    MapboxMap,
    MapboxNavigationControl,
    MapboxMarker,
  },
  emits: ['ideaPositionChanged', 'update:selectedIdea', 'visibleIdeasChanged'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class IdeaMap extends Vue {
  @Prop({ default: [] }) readonly ideas!: Idea[];
  @Prop({ default: {} }) readonly parameter!: any;
  @Prop({ default: true }) readonly canChangePosition!: boolean;
  @Prop({ default: true }) readonly calculateSize!: boolean;
  @Prop({ default: null }) readonly selectedIdea!: Idea | null;
  visibleIdeas: Idea[] = [];

  map: Map | null = null;
  MapStyles = MapStyles;
  mapStyle = MapStyles.OUTDOORS.toString();
  mapZoomDefault = 14;
  mapCenter: number[] = [0, 0];
  mapBounds: LngLatBoundsLike | null = null;
  mapZoom = this.mapZoomDefault;
  sizeCalculated = false;

  get MarkerColor(): string {
    switch (this.mapStyle) {
      case MapStyles.LIGHT:
      case MapStyles.OUTDOORS:
      case MapStyles.STREETS:
        return '#1d2948';
      case MapStyles.DARK:
        return '#67c2d0';
      case MapStyles.SATELLITE:
        return 'white';
    }
    return '#1d2948';
  }

  get MarkerColorSelected(): string {
    switch (this.mapStyle) {
      case MapStyles.OUTDOORS:
        return '#fe6e5d';
      case MapStyles.LIGHT:
      case MapStyles.STREETS:
      case MapStyles.DARK:
      case MapStyles.SATELLITE:
        return '#f3a40a';
    }
    return '#f3a40a';
  }

  mounted(): void {
    if (this.calculateSize) {
      setTimeout(() => {
        const dom = this.$refs.mapSpace as HTMLElement;
        if (dom) {
          const targetWidth = dom.parentElement?.offsetWidth;
          const targetHeight = dom.parentElement?.offsetHeight;
          if (targetWidth && targetHeight) {
            (dom as any).style.width = `${targetWidth}px`;
            (dom as any).style.height = `${targetHeight}px`;
          }
          this.sizeCalculated = true;
        }
      }, 500);
    }
  }

  @Watch('parameter', { immediate: true, deep: true })
  onParameterChanged(): void {
    if (
      this.parameter.mapCenter &&
      this.mapCenter[0] === 0 &&
      this.mapCenter[1] === 0
    ) {
      this.mapCenter = this.parameter.mapCenter;
    }
    if (this.parameter.mapZoom && this.mapZoom === this.mapZoomDefault) {
      this.mapZoom = this.parameter.mapZoom;
    }
  }

  @Watch('ideas', { immediate: true, deep: true })
  @Watch('ideas.length', { immediate: true })
  onIdeasChanged(): void {
    this.calculateMapBounds();
  }

  @Watch('selectedIdea', { immediate: true })
  onSelectedIdeaIdChanged(): void {
    if (this.selectedIdea) {
      if (this.selectedIdeaId !== this.selectedIdea.id) {
        this.selectedIdeaId = this.selectedIdea.id;
        if (this.map) {
          this.map.setCenter(
            new LngLat(
              this.selectedIdea.parameter.position[0],
              this.selectedIdea.parameter.position[1]
            )
          );
          this.changeSection();
        }
      }
    } else this.selectedIdeaId = '';
  }

  calculateMapBounds(): void {
    const center = [0, 0];
    if (this.ideas.length > 0) {
      const min = [...this.ideas[0].parameter.position];
      const max = [...this.ideas[0].parameter.position];
      for (const idea of this.ideas) {
        center[0] += idea.parameter.position[0];
        center[1] += idea.parameter.position[1];

        if (min[0] > idea.parameter.position[0])
          min[0] = idea.parameter.position[0];
        if (min[1] > idea.parameter.position[1])
          min[1] = idea.parameter.position[1];
        if (max[0] < idea.parameter.position[0])
          max[0] = idea.parameter.position[0];
        if (max[1] < idea.parameter.position[1])
          max[1] = idea.parameter.position[1];
      }
      center[0] /= this.ideas.length;
      center[1] /= this.ideas.length;

      if (this.ideas.length > 1) {
        const delta = 0.02;
        const minLngLat = new LngLat(min[0] - delta, min[1] - delta);
        const maxLngLat = new LngLat(max[0] + delta, max[1] + delta);
        this.mapBounds = new LngLatBounds(minLngLat, maxLngLat);
        this.fitZoomToBounds();
      }
    }
    this.mapCenter = center;
    this.changeSection();
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

  /**
   * map related functions
   */
  get MapboxKey(): string {
    return process.env.VUE_APP_MAPBOX_KEY;
  }

  fitZoomToBounds(): void {
    if (this.map && this.mapBounds) {
      this.map.fitBounds(this.mapBounds);
      /*setTimeout(() => {
        if (this.map) {
          this.mapZoom = this.map.getZoom();
          this.map.setZoom(this.mapZoom);
          this.map.setCenter(new LngLat(this.mapCenter[0], this.mapCenter[1]));
        }
      }, 300);*/
    }
  }

  mapLoaded(map: Map): void {
    this.map = map;
    /*console.log(this.map.getStyle().layers);
    console.log(
      this.map
        .getStyle()
        .layers.map((layer) => layer['source-layer'])
        .filter((x, i, a) => a.indexOf(x) === i)
    );*/
    this.fitZoomToBounds();
    this.mapstyleChange(this.mapStyle);
  }

  mapstyleChange(command: string): void {
    this.mapStyle = command;
    if (this.map) {
      this.map.setStyle(`mapbox://styles/mapbox/${this.mapStyle}`);
      setTimeout(() => {
        if (this.map) {
          const notNeededLayers = this.map.getStyle().layers.filter((layer) => {
            const layerCategory = layer['source-layer'];
            const layerType = layer['type'];
            if (layerCategory) {
              return layerType === 'symbol' && layerCategory !== 'place_label';
            }
            return false;
          });
          for (const layer of notNeededLayers) {
            this.map.removeLayer(layer.id);
          }
        }
      }, 500);
    }
  }

  changeSection(): void {
    const oldVisibleIdeas = [...this.visibleIdeas];
    let visibilityAdded = false;
    if (this.map) {
      const visibleIdeas: Idea[] = [];
      const bounds = this.map.getBounds();
      for (const idea of this.ideas) {
        if (
          bounds.contains(
            new LngLat(idea.parameter.position[0], idea.parameter.position[1])
          )
        ) {
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
.mapSpace {
  position: relative;

  .overlay {
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
</style>
