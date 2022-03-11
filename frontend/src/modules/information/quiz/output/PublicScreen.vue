<template>
  <PublicBase
    :taskId="taskId"
    :authHeaderTyp="authHeaderTyp"
    v-on:changePublicAnswers="(answers) => (publicAnswerList = answers)"
  >
    <template #answers>
      <el-space direction="vertical" class="fill">
        <div
          v-for="answer in publicAnswerList"
          :key="answer.answer.id"
          class="answer"
          :class="{
            correct: answer.answer.parameter.isCorrect && answer.isFinished,
            wrong: !answer.answer.parameter.isCorrect && answer.isFinished,
            plain: answer.isHighlightedTemporarily,
          }"
        >
          {{ answer.answer.keywords }}
        </div>
      </el-space>
    </template>
  </PublicBase>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import PublicBase, {
  PublicAnswerData,
} from '@/modules/information/quiz/organisms/PublicBase.vue';

@Options({
  components: {
    PublicBase,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  publicAnswerList: PublicAnswerData[] = [];

  get isModerator(): boolean {
    return this.authHeaderTyp === EndpointAuthorisationType.MODERATOR;
  }
}
</script>

<style lang="scss" scoped>
.answer {
  --el-button-text-color: var(--el-color-white);
  --el-button-bg-color: var(--color-darkblue);
  --el-button-border-color: var(--color-darkblue-light);

  border: 3px solid var(--color-primary);
  border-radius: var(--border-radius);
  padding: 1rem 3rem;
  font-weight: var(--font-weight-semibold);
  text-transform: uppercase;
  //text-align: center;
  //color: white;
  //background-color: var(--color-primary);
  border-color: var(--el-button-border-color);
  color: var(--el-button-text-color);
  background-color: var(--el-button-bg-color);
}

.plain {
  --el-button-text-color: var(--el-color-primary);
  --el-button-bg-color: var(--el-color-primary-light-9);
  --el-button-border-color: var(--color-darkblue-light);
}

.correct {
  --el-button-border-color: var(--color-mint);
}

.wrong {
  --el-button-border-color: var(--color-red);
}
</style>
