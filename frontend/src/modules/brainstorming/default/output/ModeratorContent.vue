<template>
  <FilterSection>
    <label for="orderType" class="heading heading--xs">{{
      $t('module.brainstorming.default.moderatorContent.sortOrder')
    }}</label>
    <select
      v-model="orderType"
      id="orderType"
      class="select select--fullwidth"
      @change="getIdeas(true)"
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
      <div class="layout__4columns">
        <IdeaCard
          :idea="idea"
          v-for="(idea, index) in item.ideas.slice(0, item.displayCount)"
          :key="index"
          @ideaDeleted="getIdeas()"
        />
      </div>
    </el-collapse-item>
  </el-collapse>
  <!--<Expand v-for="(item, key) in orderGroupContent" :key="key">
    <template v-slot:title>{{ key.toUpperCase() }}</template>
    <template v-slot:content>
      <main class="layout__4columns">
        <IdeaCard
          :idea="idea"
          v-for="(idea, index) in item"
          :key="index"
          @ideaDeleted="getIdeas"
        />
      </main>
    </template>
  </Expand>-->
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import * as ideaService from '@/services/idea-service';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import Expand from '@/components/shared/atoms/Expand.vue';
import CollapseTitle from '@/components/moderator/atoms/CollapseTitle.vue';
import FilterSection from '@/components/moderator/atoms/FilterSection.vue';
import { OrderGroupList } from '@/types/api/OrderGroup';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

@Options({
  components: {
    IdeaCard,
    Expand,
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
  orderType = this.SortOrderOptions[0];
  openTabs: string[] = [];

  IdeaSortOrder = IdeaSortOrder;

  get SortOrderOptions(): Array<keyof typeof IdeaSortOrder> {
    return Object.keys(IdeaSortOrder) as Array<keyof typeof IdeaSortOrder>;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getIdeas(true);
  }

  async getIdeas(reloadTabState = false): Promise<void> {
    const oldKeys = Object.keys(this.orderGroupContent);
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
          this.orderGroupContent = result.oderGroups;
          this.ideas = result.ideas;
        });
    }
    const newKeys = Object.keys(this.orderGroupContent);
    if (reloadTabState) this.openTabs = newKeys;
    else {
      const addedKeys = newKeys.filter((item) => oldKeys.indexOf(item) < 0);
      this.openTabs = this.openTabs.concat(addedKeys);
    }
  }

  async mounted(): Promise<void> {
    this.startIdeaInterval();
  }

  startIdeaInterval(): void {
    this.interval = setInterval(this.getIdeas, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }
}
</script>

<style lang="scss" scoped></style>
