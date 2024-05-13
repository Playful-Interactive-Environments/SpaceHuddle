<template>
  <Highscore :task-id="taskId" />
  <LevelStatistic :task-id="taskId" />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import LevelStatistic from '@/modules/playing/coolit/organisms/LevelStatistic.vue';
import Highscore from '@/modules/playing/coolit/organisms/Highscore.vue';

@Options({
  components: { LevelStatistic, Highscore },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModuleStatistic extends Vue {
  @Prop() readonly taskId!: string;
}
</script>
