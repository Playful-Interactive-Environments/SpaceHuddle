<template>
  <el-container ref="container">
    <el-aside width="70vw" class="mapSpace">
      <mapbox-map
        v-if="MapboxKey && sizeLoaded"
        :accessToken="MapboxKey"
        :center="mapCenter"
        :zoom="mapZoom"
        @loaded="mapLoaded"
        v-on:zoomend="changeSection"
        v-on:dragend="changeSection"
      >
        <mapbox-marker
          :lngLat="idea.parameter.position"
          v-for="idea of ideas"
          :key="idea.id"
          v-on:click="editIdea(idea)"
        >
          <template v-slot:icon>
            <font-awesome-icon
              icon="location-dot"
              class="pin"
              :style="{ '--pin-color': idea.parameter.color }"
            />
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

      <el-radio-group
        v-model="mapStyle"
        v-on:change="mapstyleChange"
        class="overlay"
      >
        <el-radio-button
          v-for="mapType in Object.values(MapStyles)"
          :key="mapType"
          :label="mapType"
        >
          <img
            width="50"
            :src="`/assets/images/mapstyles/${mapType}.png`"
            alt="mapType"
          />
        </el-radio-button>
      </el-radio-group>
    </el-aside>
    <el-main>
      <section v-if="ideas.length === 0" class="centered public-screen__error">
        <p>{{ $t('module.brainstorming.default.publicScreen.noIdeas') }}</p>
      </section>
      <div v-else class="public-screen__content">
        <section class="layout__columns">
          <IdeaCard
            v-for="(idea, index) in visibleIdeas"
            :idea="idea"
            :key="index"
            :is-editable="false"
            :isSelected="idea.id === selectedIdeaId"
            v-model:collapseIdeas="filter.collapseIdeas"
            v-model:fadeIn="ideaTransform[idea.id]"
          />
        </section>
      </div>
    </el-main>
  </el-container>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as ideaService from '@/services/idea-service';
import * as taskService from '@/services/task-service';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import {
  defaultFilterData,
  FilterData,
  getFilterForTask,
} from '@/components/moderator/molecules/IdeaFilter.vue';
import { Task } from '@/types/api/Task';
import * as cashService from '@/services/cash-service';
import {
  MapboxMap,
  MapboxMarker,
  MapboxNavigationControl,
} from 'vue-mapbox-ts';
import { Map, LngLat, LngLatBoundsLike, LngLatBounds } from 'mapbox-gl';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

export enum MapStyles {
  OUTDOORS = 'outdoors-v11',
  SATELLITE = 'satellite-streets-v11',
  STREETS = 'streets-v11',
}

@Options({
  components: {
    FontAwesomeIcon,
    IdeaCard,
    MapboxMap,
    MapboxNavigationControl,
    MapboxMarker,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  ideas: Idea[] = [];
  ideaTransform: { [id: string]: boolean } = {};
  readonly newTimeSpan = 10000;
  filter: FilterData = { ...defaultFilterData };
  sizeLoaded = false;
  visibleIdeas: Idea[] = [];

  map: Map | null = null;
  MapStyles = MapStyles;
  mapStyle = MapStyles.OUTDOORS;
  mapZoomDefault = 14;
  mapCenter: number[] = [0, 0];
  mapBounds: LngLatBoundsLike | null = null;
  mapZoom = this.mapZoomDefault;

  ideaCash!: cashService.SimplifiedCashEntry<Idea[]>;
  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.MODERATOR,
      30
    );
    this.ideaCash = ideaService.registerGetIdeasForTask(
      this.taskId,
      this.filter.orderType,
      null,
      this.updateIdeas,
      this.authHeaderTyp,
      20
    );
  }

  updateTask(task: Task): void {
    this.filter = getFilterForTask(task);
    this.ideaCash.parameter.urlParameter = ideaService.getIdeaListParameter(
      this.filter.orderType,
      null
    );
  }

  updateIdeas(ideas: Idea[]): void {
    const currentDate = new Date();
    ideas = this.filter.orderAsc ? ideas : ideas.reverse();
    ideas = ideaService.filterIdeas(
      ideas,
      this.filter.stateFilter,
      this.filter.textFilter
    );
    this.ideas = ideas;

    this.ideaTransform = Object.assign(
      {},
      ...this.ideas.map((idea) => {
        const timeSpan =
          currentDate.getTime() - new Date(idea.timestamp).getTime();
        return { [idea.id]: timeSpan <= this.newTimeSpan };
      })
    );

    const center = [0, 0];
    if (ideas.length > 0) {
      const min = [...ideas[0].parameter.position];
      const max = [...ideas[0].parameter.position];
      for (const idea of ideas) {
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
      center[0] /= ideas.length;
      center[1] /= ideas.length;

      if (ideas.length > 1) {
        const minLngLat = new LngLat(min[0], min[1]);
        const maxLngLat = new LngLat(max[0], max[1]);
        this.mapBounds = new LngLatBounds(minLngLat, maxLngLat);
        this.fitZoomToBounds();
      }
    }
    this.mapCenter = center;
    this.changeSection();
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateIdeas);
  }

  mounted(): void {
    setTimeout(() => {
      const dom = (this.$refs.container as any)?.$el as HTMLElement;
      if (dom) {
        const targetWidth = dom.parentElement?.offsetWidth;
        const targetHeight = dom.parentElement?.offsetHeight;
        if (targetWidth && targetHeight) {
          (dom as any).style.width = `${targetWidth}px`;
          (dom as any).style.height = `${targetHeight - 100}px`;
        }
        this.sizeLoaded = true;
      }
    }, 1000);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  selectedIdeaId = '';
  editIdea(idea: Idea): void {
    this.selectedIdeaId = idea.id;
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
      setTimeout(() => {
        if (this.map) {
          this.mapZoom = this.map.getZoom() - 2;
          this.map.setZoom(this.mapZoom);
          this.map.setCenter(new LngLat(this.mapCenter[0], this.mapCenter[1]));
        }
      }, 300);
    }
  }

  mapLoaded(map: Map): void {
    this.map = map;
    this.fitZoomToBounds();
    this.mapstyleChange();
  }

  mapstyleChange(): void {
    if (this.map) {
      this.map.setStyle(`mapbox://styles/mapbox/${this.mapStyle}`);
    }
  }

  changeSection(): void {
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
        }
      }
      this.visibleIdeas = visibleIdeas;
    } else this.visibleIdeas = [...this.ideas];
  }
}
</script>

<style lang="scss" scoped>
.public-screen__content {
  //display: grid;
  //grid-template-columns: 80% 20%;
}

.new {
  padding-left: 1rem;
}

.mapSpace {
  height: 100%;
  margin-right: 1rem;
  position: relative;

  .el-radio-button::v-deep(.el-radio-button__inner) {
    padding: 0;
    padding-right: 2px;
    font-size: unset;
    border: unset;
    background-color: unset;
    box-shadow: unset;

    img {
      opacity: 0.5;
    }
  }
  .is-active.el-radio-button::v-deep(.el-radio-button__inner) {
    img {
      opacity: 1;
    }
  }

  .overlay {
    background-color: white;
    padding: 0.5rem;
    border-radius: 1rem;
    position: absolute;
    z-index: 100;
    top: 0.5rem;
    right: 1.5rem;
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
