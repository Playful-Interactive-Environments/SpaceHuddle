<template>
  <div id="submitScreen" class="markdown">
    <h1>
      {{
        $t(
          `module.information.personalityTest.${test}.result.${resultValue.resultType}.name`
        )
      }}
    </h1>
    <div>
      {{
        $t(
          `module.information.personalityTest.${test}.result.${resultValue.resultType}.description`
        )
      }}
    </div>
    <h1>
      {{ $t(`module.information.personalityTest.${test}.participant.tips`) }}
    </h1>
    <ul>
      <li v-for="tip of tips" :key="tip">
        {{ tip }}
      </li>
    </ul>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { ShoppingValue } from '@/modules/information/personalityTest/types/ShoppingType';

@Options({
  components: {},
  emits: [],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ParticipantResult extends Vue {
  @Prop() readonly test!: string;
  @Prop() readonly resultValue!: ShoppingValue;

  get tips(): string[] {
    return this.$t(
      `module.information.personalityTest.${this.test}.result.${this.resultValue.resultType}.tips`
    )
      .split('#')
      .map((item) => item.trim())
      .filter((item) => item.length > 0);
  }
}
</script>

<style lang="scss" scoped>
#submitScreen {
  text-align: center;
}

#submitScreen > div {
  padding-bottom: 1rem;
  text-align: left;
}

.markdown .el-image::v-deep(img) {
  max-height: unset;
  padding: unset;
}

h1 {
  font-size: var(--font-size-large);
  font-weight: var(--font-weight-bold);
  padding-bottom: 1rem;
}

li {
  text-align: left;
}
</style>
