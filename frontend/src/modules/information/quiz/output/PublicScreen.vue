<template>
  <PublicBase
    :taskId="taskId"
    :authHeaderTyp="authHeaderTyp"
    :default-question-state="QuestionState.RESULT_STATISTICS"
    v-on:changePublicQuestion="changePublicQuestion"
    v-on:changePublicAnswers="changePublicAnswers"
    v-on:changeQuizState="changeQuizState"
  >
    <template #answers>
      <el-space
        direction="vertical"
        class="fill"
        v-if="
          activeQuestionType === QuestionType.MULTIPLECHOICE ||
          activeQuestionType === QuestionType.SINGLECHOICE
        "
      >
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
          <img
            v-if="answer.answer.image"
            :src="answer.answer.image"
            class="question-image"
            alt=""
          />
          <img
            v-if="answer.answer.link && !answer.answer.image"
            :src="answer.answer.link"
            class="question-image"
            alt=""
          />
        </div>
      </el-space>
      <el-space
        direction="vertical"
        class="fill"
        v-if="activeQuestionType === QuestionType.ORDER"
      >
        <div
          v-for="(answer, index) in orderAnswers"
          :key="answer.answer.id"
          class="answer"
          :class="{ correct: state === QuestionState.RESULT_ANSWER }"
        >
          {{ index + 1 }}.
          {{ answer.answer.keywords }}
          <img
            v-if="answer.answer.image"
            :src="answer.answer.image"
            class="question-image"
            alt=""
          />
          <img
            v-if="answer.answer.link && !answer.answer.image"
            :src="answer.answer.link"
            class="question-image"
            alt=""
          />
        </div>
      </el-space>
      <div class="slider" v-if="activeQuestionType === QuestionType.SLIDER">
        <p>{{ activeQuestion.parameter.minValue }}</p>
        <el-slider
          :min="activeQuestion.parameter.minValue"
          :max="activeQuestion.parameter.maxValue"
          :disabled="true"
          size="large"
          input-size="large"
          v-model="answerValue"
          :marks="marks"
        />
        <p>{{ activeQuestion.parameter.maxValue }}</p>
      </div>
      <div class="numbers" v-if="activeQuestionType === QuestionType.NUMBER">
        <div
          :class="{
            'numbers-correct':
              answerValue === activeQuestion.parameter.correctValue &&
              state === QuestionState.RESULT_ANSWER,
          }"
        >
          <p>{{ answerValue }}</p>
        </div>
      </div>
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
import { Hierarchy } from '@/types/api/Hierarchy';
import {
  getQuestionTypeFromHierarchy,
  QuestionType,
} from '@/modules/information/quiz/types/Question';
import { QuestionState } from '@/modules/information/quiz/types/QuestionState';

@Options({
  computed: {
    QuestionState() {
      return QuestionState;
    },
  },
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
  activeQuestion: Hierarchy | null = null;

  QuestionType = QuestionType;

  orderAnswers: PublicAnswerData[] = [];
  answerValue = 0;

  get isModerator(): boolean {
    return this.authHeaderTyp === EndpointAuthorisationType.MODERATOR;
  }

  get activeQuestionType(): QuestionType {
    return getQuestionTypeFromHierarchy(this.activeQuestion);
  }

  changePublicQuestion(question: Hierarchy | null): void {
    this.activeQuestion = question;
  }

  marks = {};
  changePublicAnswers(answers: PublicAnswerData[]): void {
    this.publicAnswerList = answers;
    if (this.activeQuestionType === QuestionType.ORDER) {
      if (
        this.orderAnswers.length !== this.publicAnswerList.length ||
        (this.publicAnswerList.length > 0 &&
          !this.orderAnswers.find(
            (answer) => this.publicAnswerList[0].answer.id === answer.answer.id
          ))
      ) {
        this.orderAnswers = this.publicAnswerList;
      }
      if (this.state !== QuestionState.RESULT_ANSWER) {
        this.orderAnswers = this.orderAnswers.sort(() => 0.5 - Math.random());
      } else {
        this.orderAnswers = this.orderAnswers.sort(
          (a, b) => (a.answer.order as number) - (b.answer.order as number)
        );
      }
    } else if (
      this.activeQuestionType === QuestionType.SLIDER ||
      this.activeQuestionType === QuestionType.NUMBER
    ) {
      if (this.activeQuestion) {
        if (this.state === QuestionState.RESULT_ANSWER) {
          this.answerValue = this.activeQuestion.parameter.correctValue;
          this.marks[this.answerValue] = this.answerValue.toString();
        } else {
          this.marks = {};
          const min = this.activeQuestion.parameter.minValue;
          const max = this.activeQuestion.parameter.maxValue;
          this.answerValue = Math.round(Math.random() * (max - min) + min);
        }
      }
    }
  }

  state: QuestionState = QuestionState.ACTIVE_WAIT_FOR_VOTE;
  changeQuizState(state: QuestionState): void {
    this.state = state;
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
  display: flex;
  justify-content: space-between;
  align-items: center;

  img {
    background-color: white;
  }
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

.question-image {
  height: 5rem;
  object-fit: contain;
  background-color: var(--color-primary);
  border-radius: 0.8rem;
}

.slider {
  display: flex;
  align-items: center;
  width: 40vw;
  gap: 1.5rem;

  div {
    flex-grow: 1;
  }

  p {
    font-size: 1.5rem;
    font-weight: bold;
    font-style: italic;
  }
}

.numbers div {
  width: 10vw;
  min-height: 5rem;
  font-size: 1.5rem;
  background-color: var(--color-darkblue);
  color: white;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 10px;
  text-align: center;
}

.numbers div.numbers-correct {
  background-color: var(--color-mint);
}
</style>
