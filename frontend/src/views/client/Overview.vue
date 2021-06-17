<template>
  <div class="overview container--fullheight">
    <div class="container">
      <MenuBar />
      <SessionInfo
        :title="'Session title'"
        :description="'Lorem ipsum si dolor description here ...'"
      />
    </div>
    <TopicExpand v-for="topic in topics" :key="topic" :isRow="true">
      <template v-slot:title>Topic Uno</template>
      <template v-slot:content>
        <li class="overview__module" v-for="module in topic" :key="module.type">
          <ModuleCard :type="module.type" :isClient="true" />
        </li>

        <AddItem text="Add module" @addNew="addModule" />
      </template>
    </TopicExpand>
  </div>
</template>

<script lang="ts">
import { Vue, Options } from 'vue-class-component';
import MenuBar from '@/components/client/molecules/Menubar.vue';
import SessionInfo from '@/components/client/molecules/SessionInfo.vue';
import TopicExpand from '@/components/shared/atoms/TopicExpand.vue';
import ModuleCard from '@/components/shared/molecules/ModuleCard.vue';
import ModuleType from '../../types/ModuleType';

@Options({
  components: {
    MenuBar,
    SessionInfo,
    TopicExpand,
    ModuleCard,
  },
})
export default class ModuleOverview extends Vue {
  public topics = [
    [
      { type: ModuleType.BRAINSTORMING },
      { type: ModuleType.SELECTION },
      { type: ModuleType.VOTING },
    ],
    [{ type: ModuleType.BRAINSTORMING }, { type: ModuleType.CATEGORIZATION }],
  ];
}
</script>

<style lang="scss" scoped>
.overview {
  color: #fff;
  background: var(--color-darkblue);
  background-image: url('../../assets/illustrations/stars-background.png');
  background-attachment: fixed;
  background-repeat: no-repeat;
  background-size: cover;

  &__module + .overview__module {
    margin-left: 1.5rem;
  }
}
</style>
