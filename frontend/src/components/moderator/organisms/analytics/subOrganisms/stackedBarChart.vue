<template>
  <div class="chart-container" ref="chartContainer">
    <div
      v-for="(questionData, index) in chartData"
      :key="index"
      class="question-container"
    >
      <p
        class="questionText twoLineText"
        :style="{ width: `${100 - 100 * barWidthPercentage - 2}%` }"
      >
        {{ questionData.question }}
      </p>
      <div
        class="barSegments"
        :style="{ width: `${100 * barWidthPercentage}%` }"
        v-if="
          computedSegments[index]?.length &&
          questionData.questionType !== 'slider' &&
          questionData.questionType !== 'number'
        "
      >
        <svg :width="parentWidth * barWidthPercentage" :height="barHeight">
          <g v-for="(segment, i) in computedSegments[index]" :key="i">
            <defs>
              <linearGradient
                :id="'gradient-' + index + '-' + i"
                x1="0%"
                y1="0%"
                x2="100%"
                y2="0%"
              >
                <stop
                  v-for="(color, j) in getColor(segment)"
                  :key="j"
                  :offset="
                    getColor(segment).length > 1
                      ? (j / (getColor(segment).length - 1)) * 100 + '%'
                      : '0%'
                  "
                  :stop-color="color"
                />
              </linearGradient>
            </defs>
            <rect
              :x="segment.x"
              :y="0"
              :width="segment.width"
              :height="barHeight"
              :fill="'url(#gradient-' + index + '-' + i + ')'"
              :rx="barHeight / 2"
              :ry="barHeight / 2"
              :style="{ strokeWidth: 6, stroke: 'var(--color-background)' }"
            />
          </g>
        </svg>
        <div
          v-for="(segment, i) in computedSegments[index]"
          :key="'text-' + i"
          class="segment-text oneLineText"
          :style="{
            left: segment.x + 'px',
            width: segment.width + 'px',
            top: '50%',
            transform: 'translateY(-50%)',
            color: 'var(--color-dark-contrast)',
          }"
        >
          {{ segment.answer }}
        </div>
      </div>
      <div
        class="barSegments"
        :style="{ width: `${100 * barWidthPercentage}%` }"
        v-else-if="computedSegments[index]?.length"
      >
        <svg :width="parentWidth * barWidthPercentage" :height="barHeight">
          <g>
            <line
              :x1="paddingSlider"
              :x2="parentWidth * barWidthPercentage - paddingSlider"
              :y1="barHeight / 2"
              :y2="barHeight / 2"
              :stroke="'var(--color-dark-contrast)'"
              :stroke-opacity="'25%'"
              stroke-width="2"
              stroke-linecap="round"
            />
            <line
              v-for="(x, i) in [
                paddingSlider,
                parentWidth * barWidthPercentage * 0.25,
                parentWidth * barWidthPercentage * 0.5,
                parentWidth * barWidthPercentage * 0.75,
                parentWidth * barWidthPercentage - paddingSlider,
              ]"
              :key="i"
              :x1="x"
              :x2="x"
              :y1="barHeight / 2.5"
              :y2="barHeight - barHeight / 2.5"
              :stroke="'var(--color-dark-contrast)'"
              stroke-width="2"
              :stroke-opacity="'25%'"
              stroke-linecap="round"
            />
            <g v-for="(segment, i) in computedSegments[index]" :key="i">
              <defs>
                <linearGradient
                  :id="'gradient-' + index + '-' + i"
                  x1="0%"
                  y1="0%"
                  x2="100%"
                  y2="0%"
                >
                  <stop
                    v-for="(color, j) in getColor(segment)"
                    :key="j"
                    :offset="(j / (getColor(segment).length - 1)) * 100 + '%'"
                    :stop-color="color"
                  />
                </linearGradient>
              </defs>
              <circle
                class="circle"
                :cx="
                  (segment.answer / questionData.parameter.maxValue) *
                    (parentWidth * barWidthPercentage - 2 * paddingSlider) +
                  paddingSlider
                "
                :cy="barHeight / 2"
                :r="circleRadius + (segment.avatars.length - 1)"
                :fill="getColor(segment)"
              />
            </g>
          </g>
        </svg>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { debounce } from 'lodash';
import { Avatar } from '@/types/api/Participant';

interface Answer {
  avatar: Avatar;
  answer: string[];
}

interface QuestionData {
  question: string;
  questionType: string;
  parameter: any;
  answers: Answer[];
}

interface AnswerSegment {
  answer: string;
  x: number;
  width: number;
  isLargest: boolean;
  color: string;
  avatars: Avatar[];
}

export default class StackedBarChart extends Vue {
  @Prop({ required: true }) readonly chartData!: QuestionData[];
  @Prop({ default: () => ['var(--color-evaluating)'] })
  readonly colorTheme!: string[];
  @Prop({ default: () => [] }) selectedParticipantIds!: string[];

  barHeight = 40;
  barWidthPercentage = 0.65;
  gapSize = 1;
  parentWidth = 0;
  resizeObserver: ResizeObserver | null = null;

  circleRadius = 8;

  paddingSlider = this.circleRadius * 1.5;

  mounted() {
    this.setupResizeObserver();
  }

  beforeDestroy() {
    this.resizeObserver?.disconnect();
  }

  setupResizeObserver() {
    const parentElement = this.$refs.chartContainer as HTMLElement;
    if (!parentElement) return;
    this.parentWidth = parentElement.clientWidth;
    this.resizeObserver = new ResizeObserver(
      debounce(this.calculateParentWidth, 100)
    );
    this.resizeObserver.observe(parentElement);
  }

  calculateParentWidth() {
    const parentElement = this.$refs.chartContainer as HTMLElement;
    if (parentElement)
      this.parentWidth = Math.max(20, parentElement.clientWidth);
  }

  getAnswerSegments(answers: Answer[]): AnswerSegment[] {
    if (!answers.length) return [];

    const answerDetails = answers.reduce<
      Record<string, { count: number; avatars: Avatar[] }>
    >((acc, { answer, avatar }) => {
      answer.forEach((response) => {
        if (!acc[response]) {
          acc[response] = { count: 0, avatars: [] };
        }
        acc[response].count += 1;
        acc[response].avatars.push(avatar);
      });
      return acc;
    }, {});

    const total = Object.values(answerDetails).reduce(
      (sum, { count }) => sum + count,
      0
    );
    let cumulativeX = 0;
    const maxCount = Math.max(
      ...Object.values(answerDetails).map((d) => d.count)
    );
    const overlap = 10;

    return Object.entries(answerDetails).map(([key, { count, avatars }], i) => {
      const width =
        (count / total) * (this.parentWidth * this.barWidthPercentage) -
        this.gapSize +
        (i > 0 ? overlap : 0);

      const segment = {
        answer: key,
        avatars: avatars,
        x: cumulativeX - (i > 0 ? overlap : 0),
        width: width + (i > 0 ? overlap : 0),
        isLargest: count === maxCount,
        color: this.colorTheme[i % this.colorTheme.length],
      };

      cumulativeX += width - overlap;
      return segment;
    });
  }

  get computedSegments() {
    return this.chartData.map((question) =>
      this.getAnswerSegments(question.answers)
    );
  }

  @Watch('chartData', { deep: true })
  onChartDataChanged() {
    this.calculateParentWidth();
  }

  getColor(segment: AnswerSegment): string[] {
    const colors: string[] = [];

    if (this.selectedParticipantIds.length <= 0) {
      colors.push(segment.color);
    } else {
      for (const avatar of segment.avatars) {
        if (this.selectedParticipantIds.includes(avatar.id)) {
          colors.push(avatar.color);
        }
      }
    }

    if (colors.length === 0) {
      colors.push('var(--color-background-darker)');
    }

    return colors;
  }
}
</script>

<style lang="scss" scoped>
.chart-container {
  display: flex;
  flex-direction: column;
  width: 100%;
  position: relative;
}

.questionText {
  font-size: var(--font-size-small);
}

.question-container {
  position: relative;
  display: flex;
  align-items: center;
  text-align: left;
  flex-direction: row;
  justify-content: space-between;
  padding: 0.5rem 0;
  .barSegments {
    position: relative;
    display: flex;
    align-items: center;
  }
}

.question-container:not(:last-child) {
  border-bottom: 2px solid var(--color-background-dark);
}

.segment-text {
  position: absolute;
  font-size: 12px;
  font-weight: bold;
  text-align: center;
  padding: 0 2rem;
}
</style>
