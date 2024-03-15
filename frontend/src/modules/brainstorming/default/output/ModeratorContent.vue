<template>
  <IdeaFilter :taskId="taskId" v-model="filter" @change="reloadIdeas(true)" />
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
            :is-sharable="false"
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
          <AddItem
            :text="$t('module.brainstorming.default.moderatorContent.add')"
            :is-column="true"
            @addNew="showSettings = true"
          />
        </template>
      </draggable>
      <div class="layout__columns" v-else>
        <IdeaCard
          :idea="idea"
          v-for="(idea, index) in item.filteredIdeas"
          :key="index"
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
        <AddItem
          :text="$t('module.brainstorming.default.moderatorContent.add')"
          :is-column="true"
          @addNew="showSettings = true"
        />
      </div>
    </el-collapse-item>
  </el-collapse>
  <IdeaSettings
    v-model:show-modal="showSettings"
    :taskId="taskId"
    :idea="addIdea"
    :title="$t('module.information.default.moderatorContent.settingsTitle')"
    @updateData="addData"
  />
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
import IdeaFilter from '@/components/moderator/molecules/IdeaFilter.vue';
import {
  defaultFilterData,
  FilterData,
} from '@/components/moderator/molecules/IdeaFilterBase.vue';
import * as cashService from '@/services/cash-service';
import IdeaSettings from '@/components/moderator/organisms/settings/IdeaSettings.vue';

@Options({
  components: {
    AddItem,
    IdeaCard,
    CollapseTitle,
    draggable,
    IdeaFilter,
    IdeaSettings,
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
  addIdea: any = {
    keywords: '',
    description: '',
    link: null,
    image: null, // the datebase64 url of created image
  };
  showSettings = false;

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
    ).then((tabs) => {
      this.openTabs = tabs;
    });
    this.reloadTabState = false;
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

  @Watch('showSettings', { immediate: true })
  onShowSettingsChanged(): void {
    if (this.showSettings) {
      this.addIdea.order = this.ideas.length;
      this.addIdea.parameter = [];
    }
  }

  addData(newIdea: Idea): void {
    this.addIdea.keywords = '';
    this.addIdea.description = '';
    this.addIdea.image = null;
    this.addIdea.link = null;
    this.ideas.push(newIdea);
  }
}
</script>

<style lang="scss" scoped>
.showMore {
  color: var(--color-highlight-dark);
  border-color: var(--color-highlight-dark);
  cursor: pointer;
}

.el-card::v-deep(.el-card__body) {
  padding: 14px;
}
</style>
