<template>
  <IdeaFilter :taskId="taskId" v-model="filter" @change="reloadIdeas(true)" />
  <div class="mapSpace">
    <mapbox-map
      v-if="MapboxKey"
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
  <el-collapse v-model="openTabs">
    <el-collapse-item
      v-for="(item, key) in orderGroupContent"
      :key="key"
      :name="key"
    >
      <template #title>
        <CollapseTitle :text="key" :avatar="item.avatar">
          <span
            role="button"
            class="awesome-icon"
            v-if="item.ideas.length > item.displayCount"
            v-on:click="item.displayCount = 1000"
          >
            <font-awesome-icon icon="ellipsis-h" />
          </span>
        </CollapseTitle>
      </template>
      <draggable
        v-model="item.filteredIdeas"
        :id="key"
        item-key="id"
        class="layout__columns"
        v-if="orderIsChangeable"
        @end="dragDone"
      >
        <template v-slot:item="{ element }">
          <IdeaCard
            :idea="element"
            :isDraggable="true"
            :isSelected="element.id === selectedIdeaId"
            v-model:collapseIdeas="filter.collapseIdeas"
            @ideaDeleted="reloadIdeas()"
          />
        </template>
        <template v-slot:footer>
          <AddItem
            v-if="item.ideas.length > item.displayCount"
            :text="
              $t('module.brainstorming.default.moderatorContent.displayAll')
            "
            :isColumn="false"
            @addNew="item.displayCount = 1000"
            class="showMore"
          />
        </template>
      </draggable>
      <div class="layout__columns" v-else>
        <IdeaCard
          :idea="idea"
          v-for="(idea, index) in item.filteredIdeas"
          :key="index"
          :isSelected="idea.id === selectedIdeaId"
          v-model:collapseIdeas="filter.collapseIdeas"
          @ideaDeleted="reloadIdeas()"
        />
        <AddItem
          v-if="item.ideas.length > item.displayCount"
          :text="$t('module.brainstorming.default.moderatorContent.displayAll')"
          :isColumn="false"
          @addNew="item.displayCount = 1000"
          class="showMore"
        />
      </div>
    </el-collapse-item>
  </el-collapse>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import * as ideaService from '@/services/idea-service';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import CollapseTitle from '@/components/moderator/atoms/CollapseTitle.vue';
import { OrderGroup, OrderGroupList } from '@/types/api/OrderGroup';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { reloadCollapseTabs } from '@/utils/collapse';
import draggable from 'vuedraggable';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import { EventType } from '@/types/enum/EventType';
import { IModeratorContent } from '@/types/ui/IModeratorContent';
import IdeaFilter, {
  defaultFilterData,
  FilterData,
} from '@/components/moderator/molecules/IdeaFilter.vue';
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
    AddItem,
    IdeaCard,
    CollapseTitle,
    draggable,
    IdeaFilter,
    MapboxMap,
    MapboxNavigationControl,
    MapboxMarker,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue implements IModeratorContent {
  @Prop() readonly taskId!: string;
  ideas: Idea[] = [];
  orderGroupContent: OrderGroupList = {};
  openTabs: string[] = [];
  filter: FilterData = { ...defaultFilterData };
  cashEntry!: cashService.SimplifiedCashEntry<Idea[]>;

  map: Map | null = null;
  MapStyles = MapStyles;
  mapStyle = MapStyles.OUTDOORS;
  mapZoomDefault = 14;
  mapCenter: number[] = [0, 0];
  mapBounds: LngLatBoundsLike | null = null;
  mapZoom = this.mapZoomDefault;

  get orderIsChangeable(): boolean {
    return this.filter.orderType === IdeaSortOrder.ORDER;
  }

  @Watch('taskId', { immediate: true })
  reloadTaskSettings(): void {
    this.deregisterAll();
    this.cashEntry = ideaService.registerGetIdeasForTask(
      this.taskId,
      this.filter.orderType,
      null,
      this.updateIdeas,
      EndpointAuthorisationType.MODERATOR,
      20
    );
  }

  updateIdeas(ideas: Idea[]): void {
    const orderType = this.filter.orderType;
    const dataList = ideaService.getOrderGroups(
      ideas,
      this.filter.orderAsc,
      this.orderGroupContent
    );
    let orderGroupName = '';
    let orderGroupContent: OrderGroupList = {};
    switch (orderType) {
      case IdeaSortOrder.TIMESTAMP:
      case IdeaSortOrder.ALPHABETICAL:
      case IdeaSortOrder.ORDER:
        dataList.ideas = ideaService.filterIdeas(
          dataList.ideas,
          this.filter.stateFilter,
          this.filter.textFilter
        );
        orderGroupName = (this as any).$t(
          `module.brainstorming.default.moderatorContent.${orderType}`
        );
        orderGroupContent[orderGroupName] = new OrderGroup(dataList.ideas);
        break;
      default:
        for (const key of Object.keys(dataList.oderGroups)) {
          dataList.oderGroups[key].ideas = ideaService.filterIdeas(
            dataList.oderGroups[key].ideas,
            this.filter.stateFilter,
            this.filter.textFilter
          );
        }
        orderGroupContent = dataList.oderGroups;
    }
    Object.keys(orderGroupContent).forEach((key) => {
      if (key in this.orderGroupContent)
        orderGroupContent[key].displayCount =
          this.orderGroupContent[key].displayCount;
    });
    const oldTabs = Object.keys(this.orderGroupContent);
    this.orderGroupContent = orderGroupContent;
    this.ideas = dataList.ideas;
    const newTabs = Object.keys(this.orderGroupContent);

    reloadCollapseTabs(
      this.openTabs,
      oldTabs,
      newTabs,
      this.reloadTabState
    ).then((tabs) => (this.openTabs = tabs));
    this.reloadTabState = false;

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
  }

  reloadTabState = true;
  reloadIdeas(reloadTabState = false): void {
    this.cashEntry.parameter.urlParameter = ideaService.getIdeaListParameter(
      this.filter.orderType,
      null
    );
    this.reloadTabState = reloadTabState;
    this.cashEntry.refreshData(false);
  }

  async mounted(): Promise<void> {
    this.eventBus.off(EventType.CHANGE_SETTINGS);
    this.eventBus.on(EventType.CHANGE_SETTINGS, async (taskId) => {
      if (this.taskId === taskId) {
        this.reloadIdeas();
      }
    });
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateIdeas);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  /* eslint-disable @typescript-eslint/explicit-module-boundary-types*/
  async dragDone(event: any): Promise<void> {
    const key = event.from.id;
    const ideas = this.orderGroupContent[key].filteredIdeas;
    ideas.forEach((idea) => {
      ideaService.putIdea(idea, EndpointAuthorisationType.MODERATOR, false);
    });
  }

  refreshIdeas(): void {
    this.cashEntry.refreshData();
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
}
</script>

<style lang="scss" scoped>
.showMore {
  color: var(--color-purple-dark);
  border-color: var(--color-purple-dark);
  cursor: pointer;
}

.el-card::v-deep(.el-card__body) {
  padding: 14px;
}

.mapSpace {
  height: 20rem;
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
    right: 0.5rem;
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
