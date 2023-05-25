<template>
  <ParticipantModuleDefaultContainer :task-id="taskId" :module="moduleName">
    <template #footer>
      <el-carousel height="15rem" type="card">
        <el-carousel-item v-for="idea of ideas" :key="idea.id">
          <IdeaCard
            class="ideaCard"
            :idea="idea"
            :is-selectable="false"
            :is-editable="true"
            :show-state="false"
            :canChangeState="false"
            :handleEditable="false"
            :authHeaderTyp="EndpointAuthorisationType.PARTICIPANT"
            @ideaDeleted="refreshIdeas"
            @ideaStartEdit="editIdea(idea)"
          >
          </IdeaCard>
        </el-carousel-item>
      </el-carousel>
      <div v-on:click="editNewImage" class="button">
        <font-awesome-icon icon="circle-plus" />
      </div>
    </template>

    <div ref="mapSpace">
      <mapbox-map
        v-if="MapboxKey && sizeCalculated"
        :accessToken="MapboxKey"
        @loaded="mapLoaded"
        :center="mapCenter"
        :zoom="mapZoom"
      >
        <mapbox-marker
          :lngLat="idea.parameter.position"
          :draggable="true"
          v-for="idea of ideas"
          :key="idea.id"
          v-on:dragend="(marker) => saveIdea(marker, idea)"
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
    </div>

    <IdeaSettings
      v-model:show-modal="showIdeaSettings"
      :taskId="taskId"
      :idea="settingsIdea"
      :title="$t('module.information.default.moderatorContent.settingsTitle')"
      :authHeaderTyp="EndpointAuthorisationType.PARTICIPANT"
      @updateData="addData"
    />
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/layout/ParticipantModuleDefaultContainer.vue';
import * as ideaService from '@/services/idea-service';
import * as taskService from '@/services/task-service';
import * as moduleService from '@/services/module-service';
import { Idea } from '@/types/api/Idea';
import { Module } from '@/types/api/Module';
import { Task } from '@/types/api/Task';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import IdeaSettings from '@/components/moderator/organisms/settings/IdeaSettings.vue';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import { defaultFormRules, ValidationRuleDefinition } from '@/utils/formRules';
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
    IdeaSettings,
    ParticipantModuleDefaultContainer,
    MapboxMap,
    MapboxNavigationControl,
    MapboxMarker,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  @Prop({ default: false }) readonly useFullSize!: boolean;
  @Prop({ default: '' }) readonly backgroundClass!: string;
  module: Module | null = null;
  task: Task | null = null;
  ideas: Idea[] = [];
  showIdeaSettings = false;
  EndpointAuthorisationType = EndpointAuthorisationType;

  addIdea: any = {
    keywords: '',
    description: '',
    link: null,
    image: null, // the datebase64 url of created image
  };
  settingsIdea = this.addIdea;

  map: Map | null = null;
  MapStyles = MapStyles;
  mapStyle = MapStyles.OUTDOORS;
  mapZoomDefault = 14;
  mapCenter: number[] = [0, 0];
  mapBounds: LngLatBoundsLike | null = null;
  mapZoom = this.mapZoomDefault;
  sizeCalculated = false;

  ideaCash!: cashService.SimplifiedCashEntry<Idea[]>;
  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.PARTICIPANT,
      60 * 60
    );
    this.ideaCash = ideaService.registerGetIdeasForTask(
      this.taskId,
      IdeaSortOrder.TIMESTAMP,
      null,
      this.updateIdeas,
      EndpointAuthorisationType.PARTICIPANT,
      30
    );
  }

  editNewImage(): void {
    this.settingsIdea = this.addIdea;
    this.addIdea.keywords = '';
    this.addIdea.description = '';
    this.addIdea.image = null;
    this.addIdea.link = null;
    this.addIdea.order = this.ideas.length;
    if (this.module && this.module.parameter.mapCenter) {
      this.addIdea.parameter = {
        position: this.module.parameter.mapCenter,
      };
    }
    this.showIdeaSettings = true;
  }

  editIdea(idea: Idea): void {
    this.settingsIdea = idea;
    this.showIdeaSettings = true;
  }

  addData(newIdea: Idea): void {
    if (!this.settingsIdea.id) this.ideas.push(newIdea);
  }

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  updateTask(task: Task): void {
    this.task = task;
  }

  updateIdeas(ideas: Idea[]): void {
    this.ideas = ideas.filter((idea) => idea.isOwn).reverse();
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
        const minLngLat = new LngLat(min[0], min[1]);
        const maxLngLat = new LngLat(max[0], max[1]);
        this.mapBounds = new LngLatBounds(minLngLat, maxLngLat);
        this.fitZoomToBounds();
      }
    }
    this.mapCenter = center;
  }

  refreshIdeas(): void {
    this.ideaCash.refreshData();
  }

  saveIdea(marker: any, idea: Idea): void {
    const lngLat = marker.target._lngLat;
    if (
      idea.parameter.position[0] !== lngLat.lng ||
      idea.parameter.position[1] !== lngLat.lat
    ) {
      idea.parameter.position = [lngLat.lng, lngLat.lat];
      ideaService
        .putIdea(idea, EndpointAuthorisationType.PARTICIPANT)
        .then(() => {
          this.refreshIdeas();
        });
    }
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
    if (this.mapCenter[0] === 0 && this.mapCenter[1] === 0) {
      this.mapCenter = this.module.parameter.mapCenter;
    }
    if (this.mapZoom === this.mapZoomDefault) {
      this.mapZoom = this.module.parameter.mapZoom;
    }
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateModule);
    cashService.deregisterAllGet(this.updateIdeas);
  }

  mounted(): void {
    setTimeout(() => {
      const dom = this.$refs.mapSpace as HTMLElement;
      if (dom) {
        const targetWidth = dom.parentElement?.offsetWidth;
        const targetHeight = dom.parentElement?.offsetHeight;
        this.sizeCalculated = true;
        if (targetWidth && targetHeight) {
          (dom as any).style.width = `${targetWidth}px`;
          (dom as any).style.height = `${targetHeight}px`;
        }
      }
    }, 1000);
  }

  unmounted(): void {
    this.deregisterAll();
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
          this.mapZoom = this.map.getZoom() - 0.3;
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
}
</script>

<style lang="scss" scoped>
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
  top: 1.4rem;
  right: 1.4rem;
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

.button {
  border: unset;
  background-color: unset;
  padding: unset;
  font-size: var(--font-size-xxxlarge);
  color: var(--color-primary);
  position: absolute;
  bottom: 0.5rem;
  right: 1rem;
}

.ideaCard {
  max-width: 12rem;
  margin: auto;
}
</style>
