<template>
  <FilterSection>
    <label for="orderType" class="heading heading--xs">{{
      $t('module.brainstorming.default.moderatorContent.sortOrder')
    }}</label>
    <select
      v-model="orderType"
      id="orderType"
      class="select select--fullwidth"
      @change="getCollapseContent(true)"
    >
      <option
        v-for="type in sortOrderOptions"
        :key="type.orderType"
        :value="
          type.ref ? `${type.orderType}&refId=${type.ref.id}` : type.orderType
        "
      >
        <span>
          {{ $t(`enum.ideaSortOrder.${type.orderType}`) }}
        </span>
        <span v-if="type.ref"> - {{ type.ref.name }} </span>
      </option>
    </select>
  </FilterSection>
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
            class="icon"
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
            @ideaDeleted="getCollapseContent()"
          />
        </template>
        <template v-slot:footer>
          <IdeaCard
            v-if="item.ideas.length > item.displayCount"
            :idea="{ keywords: '...' }"
            :is-editable="false"
            v-on:click="item.displayCount = 1000"
            class="showMore"
          />
        </template>
      </draggable>
      <div class="layout__columns" v-else>
        <IdeaCard
          :idea="idea"
          v-for="(idea, index) in item.filteredIdeas"
          :key="index"
          @ideaDeleted="getCollapseContent()"
        />
        <IdeaCard
          v-if="item.ideas.length > item.displayCount"
          :idea="{ keywords: '...' }"
          :is-editable="false"
          v-on:click="item.displayCount = 1000"
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
import * as taskService from '@/services/task-service';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import IdeaSortOrder, {
  DefaultIdeaSortOrder,
} from '@/types/enum/IdeaSortOrder';
import CollapseTitle from '@/components/moderator/atoms/CollapseTitle.vue';
import FilterSection from '@/components/moderator/atoms/FilterSection.vue';
import {
  OrderGroupList,
  OrderGroup,
  SortOrderOption,
} from '@/types/api/OrderGroup';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { reloadCollapseContent } from '@/utils/collapse';
import { convertToSaveVersion } from '@/types/api/Task';
import draggable from 'vuedraggable';

@Options({
  components: {
    IdeaCard,
    CollapseTitle,
    FilterSection,
    draggable,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue {
  @Prop() readonly taskId!: string;
  ideas: Idea[] = [];
  orderGroupContent: OrderGroupList = {};
  readonly intervalTime = 10000;
  interval!: any;
  orderType: string = DefaultIdeaSortOrder;
  openTabs: string[] = [];

  IdeaSortOrder = IdeaSortOrder;
  sortOrderOptions: SortOrderOption[] = [];

  /*get SortOrderOptions(): Array<keyof typeof IdeaSortOrder> {
    return Object.keys(IdeaSortOrder) as Array<keyof typeof IdeaSortOrder>;
  }*/

  get orderIsChangeable(): boolean {
    return this.orderType === IdeaSortOrder.ORDER;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getCollapseContent(true);
    taskService.getTaskById(this.taskId).then(async (task) => {
      await ideaService.getSortOrderOptions(task.topicId).then((options) => {
        this.sortOrderOptions = options;
        if (options.length > 0) this.orderType = options[0].orderType;
      });
      if (
        task.parameter &&
        task.parameter.orderType &&
        this.orderType != task.parameter.orderType
      ) {
        this.orderType = task.parameter.orderType;
        this.getCollapseContent(true);
      }
    });
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
    if (this.taskId) {
      await ideaService
        .getOrderGroups(
          this.taskId,
          this.orderType,
          null,
          EndpointAuthorisationType.MODERATOR,
          this.orderGroupContent
        )
        .then((result) => {
          let orderGroupName = '';
          let orderGroupContent: OrderGroupList = {};
          switch (this.orderType) {
            case IdeaSortOrder.TIMESTAMP:
            case IdeaSortOrder.ALPHABETICAL:
            case IdeaSortOrder.ORDER:
              orderGroupName = (this as any).$t(
                `module.brainstorming.default.moderatorContent.${this.orderType}`
              );
              orderGroupContent[orderGroupName] = new OrderGroup(result.ideas);
              break;
            default:
              orderGroupContent = result.oderGroups;
          }
          Object.keys(orderGroupContent).forEach((key) => {
            if (key in this.orderGroupContent)
              orderGroupContent[key].displayCount =
                this.orderGroupContent[key].displayCount;
          });
          this.orderGroupContent = orderGroupContent;
          this.ideas = result.ideas;
          taskService.getTaskById(this.taskId).then((task) => {
            task.parameter.orderType = this.orderType;
            taskService.putTask(this.taskId, convertToSaveVersion(task));
          });
        });
    }
    return Object.keys(this.orderGroupContent);
  }

  async mounted(): Promise<void> {
    this.startIdeaInterval();
  }

  startIdeaInterval(): void {
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
      ideaService.putIdea(idea.id, idea);
    });
  }
}
</script>

<style lang="scss" scoped>
.showMore {
  color: var(--color-purple-dark);
  border-color: var(--color-purple-dark);
  background-color: var(--color-purple-light);
  cursor: pointer;
}
</style>
