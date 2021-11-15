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
      <option v-for="type in SortOrderOptions" :key="type" :value="type">
        {{ $t(`enum.ideaSortOrder.${IdeaSortOrder[type]}`) }}
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
      <div class="layout__columns">
        <IdeaCard
          :idea="idea"
          v-for="(idea, index) in item.ideas.slice(0, item.displayCount)"
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
  DefaultDisplayCount,
} from '@/types/enum/IdeaSortOrder';
import CollapseTitle from '@/components/moderator/atoms/CollapseTitle.vue';
import FilterSection from '@/components/moderator/atoms/FilterSection.vue';
import { OrderGroupList } from '@/types/api/OrderGroup';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { reloadCollapseContent } from '@/utils/collapse';
import { convertToSaveVersion } from '@/types/api/Task';

@Options({
  components: {
    IdeaCard,
    CollapseTitle,
    FilterSection,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue {
  @Prop() readonly taskId!: string;
  ideas: Idea[] = [];
  orderGroupContent: OrderGroupList = {};
  readonly intervalTime = 10000;
  interval!: any;
  orderType = DefaultIdeaSortOrder;
  openTabs: string[] = [];

  IdeaSortOrder = IdeaSortOrder;

  get SortOrderOptions(): Array<keyof typeof IdeaSortOrder> {
    return Object.keys(IdeaSortOrder) as Array<keyof typeof IdeaSortOrder>;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getCollapseContent(true);
    taskService.getTaskById(this.taskId).then((task) => {
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
          switch (this.orderType) {
            case IdeaSortOrder.TIMESTAMP.toUpperCase():
            case IdeaSortOrder.ALPHABETICAL.toUpperCase():
              this.orderGroupContent = {
                '': {
                  ideas: result.ideas,
                  avatar: null,
                  color: null,
                  displayCount: DefaultDisplayCount,
                },
              };
              break;
            default:
              this.orderGroupContent = result.oderGroups;
          }
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
