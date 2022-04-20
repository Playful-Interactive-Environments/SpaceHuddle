<template>
  <IdeaFilter
    :taskId="taskId"
    v-model="filter"
    @change="getCollapseContent(true)"
  />
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
            v-model:collapseIdeas="filter.collapseIdeas"
            @ideaDeleted="getCollapseContent()"
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
          v-model:collapseIdeas="filter.collapseIdeas"
          @ideaDeleted="getCollapseContent()"
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
import { OrderGroupList, OrderGroup } from '@/types/api/OrderGroup';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { reloadCollapseContent } from '@/utils/collapse';
import draggable from 'vuedraggable';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import { EventType } from '@/types/enum/EventType';
import { IModeratorContent } from '@/types/ui/IModeratorContent';
import IdeaFilter, {
  defaultFilterData,
  FilterData,
} from '@/components/moderator/molecules/IdeaFilter.vue';

@Options({
  components: {
    AddItem,
    IdeaCard,
    CollapseTitle,
    draggable,
    IdeaFilter,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue implements IModeratorContent {
  @Prop() readonly taskId!: string;
  ideas: Idea[] = [];
  orderGroupContent: OrderGroupList = {};
  readonly intervalTime = 10000;
  interval!: any;
  openTabs: string[] = [];
  filter: FilterData = { ...defaultFilterData };

  get orderIsChangeable(): boolean {
    return this.filter.orderType === IdeaSortOrder.ORDER;
  }

  @Watch('taskId', { immediate: true })
  reloadTaskSettings(): void {
    this.getCollapseContent(true);
  }

  async getCollapseContent(reloadTabState = false): Promise<void> {
    reloadCollapseContent(
      this.openTabs,
      Object.keys(this.orderGroupContent),
      this.getIdeas,
      reloadTabState
    ).then((tabs) => (this.openTabs = tabs));
  }

  async getIdeas(): Promise<string[]> {
    const taskId = this.taskId;
    const orderType = this.filter.orderType;
    if (taskId) {
      await ideaService
        .getOrderGroups(
          taskId,
          orderType,
          null,
          EndpointAuthorisationType.MODERATOR,
          this.orderGroupContent
        )
        .then((result) => {
          let orderGroupName = '';
          let orderGroupContent: OrderGroupList = {};
          switch (orderType) {
            case IdeaSortOrder.TIMESTAMP:
            case IdeaSortOrder.ALPHABETICAL:
            case IdeaSortOrder.ORDER:
              result.ideas = ideaService.filterIdeas(
                result.ideas,
                this.filter.stateFilter,
                this.filter.textFilter
              );
              orderGroupName = (this as any).$t(
                `module.brainstorming.default.moderatorContent.${orderType}`
              );
              orderGroupContent[orderGroupName] = new OrderGroup(result.ideas);
              break;
            default:
              for (const key of Object.keys(result.oderGroups)) {
                result.oderGroups[key].ideas = ideaService.filterIdeas(
                  result.oderGroups[key].ideas,
                  this.filter.stateFilter,
                  this.filter.textFilter
                );
              }
              orderGroupContent = result.oderGroups;
          }
          Object.keys(orderGroupContent).forEach((key) => {
            if (key in this.orderGroupContent)
              orderGroupContent[key].displayCount =
                this.orderGroupContent[key].displayCount;
          });
          this.orderGroupContent = orderGroupContent;
          this.ideas = result.ideas;
        });
    }
    return Object.keys(this.orderGroupContent);
  }

  async mounted(): Promise<void> {
    this.startInterval();

    this.eventBus.off(EventType.CHANGE_SETTINGS);
    this.eventBus.on(EventType.CHANGE_SETTINGS, async (taskId) => {
      if (this.taskId === taskId) {
        await this.getCollapseContent();
      }
    });
  }

  startInterval(): void {
    this.interval = setInterval(this.getCollapseContent, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }

  /* eslint-disable @typescript-eslint/explicit-module-boundary-types*/
  async dragDone(event: any): Promise<void> {
    const key = event.from.id;
    const ideas = this.orderGroupContent[key].filteredIdeas;
    ideas.forEach((idea) => {
      ideaService.putIdea(idea);
    });
  }
}
</script>

<style lang="scss" scoped>
.showMore {
  color: var(--color-purple-dark);
  border-color: var(--color-purple-dark);
  cursor: pointer;
}

.el-card::v-deep {
  .el-card__body {
    padding: 14px;
  }
}
</style>
