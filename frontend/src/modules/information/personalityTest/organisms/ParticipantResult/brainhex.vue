<template>
  <div id="submitScreen" class="markdown">
    <div>
      {{
        $t(`module.information.personalityTest.${test}.participant.primary`)
      }}:
      {{
        $t(
          `module.information.personalityTest.${test}.result.${resultValue.resultTypes[0]}.name`
        )
      }}
    </div>
    <div>
      {{
        $t(`module.information.personalityTest.${test}.participant.secondary`)
      }}:
      {{
        $t(
          `module.information.personalityTest.${test}.result.${resultValue.resultTypes[1]}.name`
        )
      }}
    </div>
    <el-image
      :src="`/assets/games/brainhex/${resultValue.resultTypes[0]}${resultValue.resultTypes[1]}.png`"
    />
    <div>
      {{ $t(`module.information.personalityTest.${test}.participant.youLike`) }}
      {{
        $t(
          `module.information.personalityTest.${test}.result.${resultValue.resultTypes[0]}.description`
        )
      }}
      {{ $t(`module.information.personalityTest.${test}.participant.asWell`) }}
      {{
        $t(
          `module.information.personalityTest.${test}.result.${resultValue.resultTypes[1]}.description`
        )
      }}.
    </div>
    <div v-if="resultValue.resultTypeExceptions.length > 0">
      {{ $t(`module.information.personalityTest.${test}.participant.dislike`) }}
      <ul>
        <li v-for="item in resultValue.resultTypeExceptions" :key="item">
          {{
            $t(
              `module.information.personalityTest.${test}.result.${item}.exception`
            )
          }}:
          {{
            $t(
              `module.information.personalityTest.${test}.result.${item}.exceptionDescription`
            )
          }}
        </li>
      </ul>
    </div>
    <div v-else>
      {{
        $t(`module.information.personalityTest.${test}.participant.noDislike`)
      }}
    </div>
    <div>
      {{ $t(`module.information.personalityTest.${test}.participant.score`) }}
    </div>
    <div>
      <div
        v-for="item of Object.keys(resultValue.resultTypeValues)"
        :key="item"
      >
        <el-link
          @click="
            () => {
              dialogResultType = item;
              showResultType = true;
            }
          "
        >
          {{
            $t(
              `module.information.personalityTest.${test}.result.${item}.name`
            )
          }}:
        </el-link>
        {{ resultValue.resultTypeValues[item] }}
      </div>
    </div>
  </div>
  <el-dialog v-model="showResultType">
    <template #header>
      <h1>
        {{
          $t(
            `module.information.personalityTest.${test}.result.${dialogResultType}.name`
          )
        }}
      </h1>
    </template>
    <el-image :src="`/assets/games/brainhex/${dialogResultType}.png`" />
    <div>
      {{
        $t(
          `module.information.personalityTest.${test}.result.${dialogResultType}.detailDescription`
        )
      }}
    </div>
  </el-dialog>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { BrainhexValue } from '@/modules/information/personalityTest/types/BrainhexType';

@Options({
  components: {},
  emits: [],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ParticipantResult extends Vue {
  @Prop() readonly test!: string;
  @Prop() readonly resultValue!: BrainhexValue;

  showResultType = false;
  dialogResultType = '';
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
</style>
