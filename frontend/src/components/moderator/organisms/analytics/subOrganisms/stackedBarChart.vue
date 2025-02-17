<template>
  <div class="chart-container">
    <div v-for="(questionData, index) in chartData" :key="index" class="question-container">
      <!-- Question Label -->
      <p class="question-label">{{ questionData.question }}</p>

      <!-- Stacked Bar Chart (SVG) -->
      <svg :width="width" :height="barHeight">
        <g v-if="questionData.answers.length">
          <rect
            v-for="(answer, i) in getAnswerSegments(questionData.answers)"
            :key="i"
            :x="answer.x + (i * gapSize)"
            :y="0"
            :width="answer.width - gapSize"
            :height="barHeight"
            :fill="'var(--color-evaluating)'"
            rx="6" ry="6"
          />
        </g>
      </svg>
    </div>
  </div>
</template>

<script lang="ts">
import { Vue } from "vue-class-component";
import { Prop } from "vue-property-decorator";

interface Avatar {
  id: string;
  name: string;
}

interface Answer {
  avatar: Avatar;
  answer: number | string | string[];
}

interface QuestionData {
  question: string;
  answers: Answer[];
}

interface AnswerSegment {
  x: number;
  width: number;
}

export default class StackedBarChart extends Vue {
  @Prop({ required: true }) chartData!: QuestionData[];

  width = 600;
  barHeight = 40;
  gapSize = 1;

  getAnswerSegments(answers: Answer[]): AnswerSegment[] {
    const answerCounts: Record<string, number> = {};

    answers.forEach(({ answer }) => {
      const key = JSON.stringify(answer);
      answerCounts[key] = (answerCounts[key] || 0) + 1;
    });

    const total = answers.length;
    let cumulativeX = 0;

    return Object.entries(answerCounts).map(([_, count], i) => {
      const width = (count / total) * this.width - this.gapSize;
      const segment = { x: cumulativeX, width };
      cumulativeX += width + this.gapSize;
      return segment;
    });
  }
}
</script>

<style scoped>
.chart-container {
  font-family: Arial, sans-serif;
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.question-container {
  display: flex;
  flex-direction: column;
}

.question-label {
  font-size: 16px;
  font-weight: bold;
  margin-bottom: 5px;
}
</style>
