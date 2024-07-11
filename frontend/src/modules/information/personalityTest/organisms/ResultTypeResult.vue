<template>
  <ResultChart :key="key" :task-id="taskId" :chart-type="chartType" />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ResultChart from '@/modules/information/personalityTest/organisms/ResultChart/default.vue';
import * as importService from '@/modules/information/personalityTest/utils/import';
import { ResultChartType } from '@/modules/information/personalityTest/types/ResultChartType';

@Options({
  components: {
    ResultChart: ResultChart,
  },
  emits: [],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ResultTypeResult extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly test!: string;
  @Prop({ default: ResultChartType.MODERATOR })
  readonly chartType!: ResultChartType;

  key = 0;

  @Watch('test', { immediate: true })
  onIdChanged(): void {
    if (this.test) {
      importService
        .importComponent('ResultChart', this.test)
        .then((component) => {
          if (this.$options.components) {
            this.$options.components['ResultChart'] = component as any;
            this.key++;
          }
        });
    }
  }
}
</script>

<style lang="scss" scoped></style>
