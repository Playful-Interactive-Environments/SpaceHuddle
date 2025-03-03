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
          (computedSegments[index]?.length &&
            questionData.questionType === 'slider') ||
          questionData.questionType === 'number' ||
          questionData.questionType === 'rating'
        "
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
            <g
              v-for="(x, i) in [
                paddingSlider,
                parentWidth * barWidthPercentage * 0.25,
                parentWidth * barWidthPercentage * 0.5,
                parentWidth * barWidthPercentage * 0.75,
                parentWidth * barWidthPercentage - paddingSlider,
              ]"
              :key="i"
            >
              <line
                :x1="x"
                :x2="x"
                :y1="barHeight / 2.5"
                :y2="barHeight - barHeight / 2.5"
                :stroke="'var(--color-dark-contrast)'"
                stroke-width="2"
                :stroke-opacity="'25%'"
                stroke-linecap="round"
              />
              <text
                class="svgText"
                v-if="x == paddingSlider"
                :x="x"
                :y="barHeight"
                font-size="10.5"
                text-anchor="middle"
                :style="{
                  color: 'var(--color-dark-contrast)',
                  textAlign: 'center',
                }"
              >
                {{ questionData.parameter.minValue || 0 }}
              </text>
              <text
                v-else-if="
                  x == parentWidth * barWidthPercentage - paddingSlider
                "
                :x="x"
                :y="barHeight"
                font-size="10.5"
                text-anchor="middle"
                :style="{
                  color: 'var(--color-dark-contrast)',
                  textAlign: 'center',
                }"
              >
                {{ questionData.parameter.maxValue }}
              </text>
            </g>
            <g v-for="(segment, i) in computedSegments[index]" :key="i">
              <defs>
                <linearGradient
                  class="linearGradient"
                  :id="'gradient-' + index + '-' + i"
                  x1="0%"
                  y1="0%"
                  x2="100%"
                  y2="0%"
                >
                  <stop
                    class="linearGradientStop"
                    v-for="(color, j) in getColor(segment)"
                    :key="j"
                    :offset="
                      getColor(segment).length > 1
                        ? (j / (getColor(segment).length - 1)) * 100 + '%'
                        : '50%'
                    "
                    :stop-color="color"
                  />
                </linearGradient>
              </defs>
              <g class="circle">
                <circle
                    class="cursorPointer"
                  :cx="
                    (segment.answer / questionData.parameter.maxValue) *
                      (parentWidth * barWidthPercentage - 2 * paddingSlider) +
                    paddingSlider
                  "
                  :cy="barHeight / 2"
                  :r="circleRadius + (segment.avatars.length - 1)"
                  :fill="'url(#gradient-' + index + '-' + i + ')'"
                  @click="changeParticipantSelection(segment)"
                />
                <text
                  class="circleLabel"
                  :x="
                    (segment.answer / questionData.parameter.maxValue) *
                      (parentWidth * barWidthPercentage - 2 * paddingSlider) +
                    paddingSlider
                  "
                  :y="7.5"
                  font-size="10.5"
                  text-anchor="middle"
                  :style="{
                    color: 'var(--color-dark-contrast)',
                    textAlign: 'center',
                  }"
                >
                  {{ segment.answer || 0 }}
                </text>
              </g>
            </g>
          </g>
        </svg>
      </div>
      <div
        :style="{
          width: `${100 * barWidthPercentage}%`,
          height: `${barHeight * 3}`,
        }"
        v-else-if="
          computedSegments[index]?.length &&
          questionData.questionType === 'text'
        "
      >
        <el-carousel
          :interval="5000"
          type="card"
          :height="`${barHeight * 3}px`"
          :trigger="'click'"
        >
          <el-carousel-item
            v-for="(segment, i) in computedSegments[index]"
            :key="i"
          >
            <p>{{ segment.answer }}</p>
            <font-awesome-icon
              class="carouselColorItem cursorPointer"
              :icon="segment.avatars[0].symbol"
              :style="{
                color:
                  selectedParticipantIds.length <= 0
                    ? segment.avatars[0].color
                    : getColor(segment)[0],
              }"
              @click="changeParticipantSelection(segment)"
            ></font-awesome-icon>
          </el-carousel-item>
        </el-carousel>
      </div>
      <div
        class="barSegments"
        :style="{ width: `${100 * barWidthPercentage}%` }"
        v-else-if="computedSegments[index]?.length"
      >
        <svg :width="parentWidth * barWidthPercentage" :height="barHeight">
          <g
            v-for="(segment, i) in computedSegments[index]"
            :key="i"
            class="barSegmentElement cursorPointer"
            @click="changeParticipantSelection(segment)"
          >
            <defs>
              <linearGradient
                class="linearGradient"
                :id="'gradient-' + index + '-' + i"
                x1="0%"
                y1="0%"
                x2="100%"
                y2="0%"
              >
                <stop
                  class="linearGradientStop"
                  v-for="(color, j) in getColor(segment)"
                  :key="j"
                  :offset="
                    getColor(segment).length > 1
                      ? (j / (getColor(segment).length - 1)) * 100 + '%'
                      : '50%'
                  "
                  :stop-color="color"
                />
              </linearGradient>
            </defs>
            <rect
              :x="segment.x"
              :y="0"
              :width="segment.width >= 0 ? segment.width : 0"
              :height="barHeight"
              :fill="'var(--color-background)'"
              :rx="barHeight / 2"
              :ry="barHeight / 2"
            />
            <rect
              :x="segment.x"
              :y="0"
              :width="segment.width >= 0 ? segment.width : 0"
              :height="barHeight"
              :fill="'url(#gradient-' + index + '-' + i + ')'"
              :rx="barHeight / 2"
              :ry="barHeight / 2"
              :style="{
                strokeWidth: 6,
                stroke: 'var(--color-background)',
                backgroundColor: 'var(--color-background)',
              }"
            />
            <g class="barSegmentPercentages">
              <rect
                :x="segment.x + segment.width - 55"
                :y="0"
                :width="30"
                :height="12"
                :fill="'var(--color-background)'"
                :rx="12 / 2.5"
                :ry="12 / 2.5"
              />
              <text
                class="svgText"
                :x="segment.x + segment.width - 50"
                :y="7.5"
                font-size="10.5"
                text-anchor="left"
                :style="{
                  color: 'var(--color-dark-contrast)',
                  textAlign: 'center',
                }"
              >
                {{ Math.round(segment.percentage * 100) }}%
              </text>
            </g>
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
            textShadow:
              selectedParticipantIds.length > 0
                ? '-1px -1px 0 var(--color-background), 1px -1px 0 var(--color-background), -1px 1px 0 var(--color-background), 1px 1px 0 var(--color-background)'
                : 'unset',
          }"
        >
          <span>{{ segment.answer }}</span>
        </div>
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
  percentage: number;
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

  getAnswerSegments(answers: Answer[], questionType: string): AnswerSegment[] {
    if (!answers.length) return [];

    const answerDetails = answers.reduce<
      Record<string, { count: number; avatars: Avatar[] }>
    >((acc, { answer, avatar }) => {
      answer.forEach((response, index) => {
        if (!acc[response]) {
          acc[response] = { count: 0, avatars: [] };
        }
        acc[response].count +=
          questionType === 'order' ? answer.length - index - 1 : 1;
        if (questionType === 'order') {
          if (index === 0) {
            acc[response].avatars.push(avatar);
          }
        } else {
          acc[response].avatars.push(avatar);
        }
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
        percentage: count / total,
        color: this.colorTheme[i % this.colorTheme.length],
      };

      cumulativeX += width - overlap;
      return segment;
    });
  }

  get computedSegments() {
    return this.chartData.map((question) =>
      this.getAnswerSegments(question.answers, question.questionType)
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

  changeParticipantSelection(segment: AnswerSegment): void {
    const participantIds = segment.avatars.map((avatar) => avatar.id);
    this.participantSelectionChanged(participantIds);
  }

  participantSelectionChanged(ids: string[] | null) {
    if (ids) {
      if (JSON.stringify(this.selectedParticipantIds) === JSON.stringify(ids)) {
        this.$emit('update:selectedParticipantIds', []);
      } else {
        this.$emit('update:selectedParticipantIds', ids);
      }
    }
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

.svgText {
  font-family: var(--font-family);
}

.question-container:not(:last-child) {
  border-bottom: 2px solid var(--color-background-dark);
}

.segment-text {
  position: absolute;
  font-size: var(--font-size-small);
  font-weight: bold;
  text-align: center;
  padding: 0 2rem;
  color: var(--color-dark-contrast);
  transition: text-shadow 0.4s ease;

  pointer-events: none;
}

.barSegmentPercentages {
  opacity: 0;
  transition: opacity 0.3s ease;
}

.barSegmentElement:hover {
  .barSegmentPercentages {
    opacity: 1;
  }
}

.linearGradientStop {
  transition: stop-color 0.5s ease;
}

.el-carousel__item {
  height: 100%;
  padding: 1rem;
  overflow: scroll;

  display: flex;
  justify-content: center;
  text-align: center;
  font-weight: bold;
  font-size: var(--font-size-small);

  scrollbar-width: none;
  -ms-overflow-style: none;

  background-color: white;
  border: 2px solid #f1f1f1;
  border-radius: var(--border-radius);
}

.el-carousel__item::-webkit-scrollbar {
  display: none;
}

.carouselColorItem {
  position: absolute;
  top: 1rem;
  right: 1rem;
  width: 1.5rem;
  height: 1.5rem;
  transform: scale(1);
  transition: transform 0.15s ease;
}

.carouselColorItem:hover {
  transform: scale(1.15);
}

.circle {
  .circleLabel {
    opacity: 0;
    transition: opacity 0.3s ease;
  }
}

.circle:hover {
  .circleLabel {
    opacity: 1;
  }
}

.cursorPointer:hover {
  cursor: pointer;
}
</style>
