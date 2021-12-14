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
          :class="{ plain: !answer.isHighlighted }"
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
  border: 1px solid var(--color-primary);
  border-radius: var(--border-radius);
  padding: 1rem;
  font-weight: var(--font-weight-semibold);
  text-transform: uppercase;
  text-align: center;
  color: white;
  background-color: var(--color-primary);
}

.plain {
  color: var(--color-primary);
  background-color: var(--color-darkblue-light);
}
</style>