<template>
  <header class="content_filter">
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
  </header>
  <el-collapse v-model="openTabs">
    <el-collapse-item
      v-for="(item, key) in orderGroupContent"
      :key="key"
      :name="key"
    >
      <template #title>
        <span class="layout__level>">{{ key.toUpperCase() }}</span>
      </template>
      <div class="layout__4columns">
        <IdeaCard
          :idea="idea"
          v-for="(idea, index) in item"
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
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import Expand from '@/components/shared/atoms/Expand.vue';
import {warn} from "element-plus/es/utils/error";

@Options({
  components: {
    IdeaCard,
    Expand,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue {
  @Prop() readonly taskId!: string;
  ideas: Idea[] = [];
  orderGroupContent: { [name: string]: Idea[] } = {};
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
        .getIdeasForTask(this.taskId, this.orderType)
        .then((ideas) => {
          this.orderGroupContent = {};
          this.ideas = ideas;
          ideas.forEach((ideaItem) => {
            if (ideaItem.order) {
              const orderGroup = this.orderGroupContent[ideaItem.order];
              if (!orderGroup) {
                this.orderGroupContent[ideaItem.order] = [ideaItem];
              } else {
                orderGroup.push(ideaItem);
              }
            }
          });
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

<style lang="scss" scoped>
</style>
