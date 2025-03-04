<template>
  <div class="chart-container" ref="chartContainer">
    <div
      v-for="(questionData, index) in chartData"
      :key="index"
      class="question-container"
    >
      <p class="questionText twoLineText" :style="questionTextStyle">
        <ToolTip :content="questionData.question" :show-after="200">
          <span>{{ questionData.question }}</span>
        </ToolTip>
      </p>
      <div
        v-if="shouldRenderSlider(questionData, index)"
        class="barSegments"
        :style="barSegmentsStyle"
      >
        <svg :width="barWidth" :height="barHeight">
          <g>
            <line
              v-bind="sliderLineProps()"
              stroke-width="2"
              stroke-linecap="round"
            />
            <g v-for="(x, i) in sliderPositions" :key="i">
              <line
                v-bind="sliderTickProps(x)"
                stroke-width="2"
                stroke-linecap="round"
              />
              <text
                v-if="isSliderEdge(x)"
                class="svgText"
                v-bind="sliderTextProps(x, questionData)"
                text-anchor="middle"
              />
            </g>
            <g v-for="(segment, i) in computedSegments[index]" :key="i">
              <defs>
                <linearGradient
                  :id="gradientId(index, i)"
                  v-bind="gradientProps"
                >
                  <stop
                    class="linearGradientStop"
                    v-for="(color, j) in getColor(segment)"
                    :key="j"
                    v-bind="gradientStopProps(segment, j)"
                    :stop-color="color"
                  />
                </linearGradient>
              </defs>
              <g class="circle">
                <circle
                  class="cursorPointer"
                  v-bind="circleProps(segment, questionData)"
                  @click="
                    participantSelectionChanged(
                      segment.avatars.map((avatar) => avatar.id)
                    )
                  "
                />
                <text
                  class="circleLabel"
                  v-bind="circleTextProps(segment, questionData)"
                  text-anchor="middle"
                >
                  {{ segment.answer || 0 }}
                </text>
              </g>
            </g>
          </g>
        </svg>
        <div
          v-for="(segment, i) in computedSegments[index]"
          :key="'text-' + i"
          class="segment-avatars-circle"
          :style="{
            left: `${
              calculateCircleX(segment, questionData) - segment.width / 2
            }px`,
            width: `${segment.width}px`,
          }"
        >
          <font-awesome-icon
            class="segment-avatars-icon"
            v-for="avatar in filteredAvatars(segment)"
            :key="avatar.id"
            :icon="avatar.symbol"
            :style="{ color: avatar.color }"
            @click="participantSelectionChanged([avatar.id])"
          />
        </div>
      </div>
      <div
        v-else-if="shouldRenderTextCarousel(questionData, index)"
        :style="carouselStyle"
      >
        <el-carousel
          :interval="5000"
          type="card"
          :height="carouselHeight"
          trigger="click"
        >
          <el-carousel-item
            class="carouselItem"
            v-for="(segment, i) in computedSegments[index]"
            :key="i"
          >
            <p>{{ segment.answer }}</p>
            <font-awesome-icon
              class="carouselColorItem cursorPointer"
              :icon="segment.avatars[0].symbol"
              :style="carouselIconStyle(segment)"
              @click="
                participantSelectionChanged(
                  segment.avatars.map((avatar) => avatar.id)
                )
              "
            />
          </el-carousel-item>
        </el-carousel>
      </div>
      <div
        v-else-if="shouldRenderBarSegments(index)"
        class="barSegments"
        :style="barSegmentsStyle"
      >
        <svg :width="barWidth" :height="barHeight">
          <g
            v-for="(segment, i) in computedSegments[index]"
            :key="i"
            class="barSegmentElement cursorPointer"
            @click="
              participantSelectionChanged(
                segment.avatars.map((avatar) => avatar.id)
              )
            "
          >
            <defs>
              <linearGradient :id="gradientId(index, i)" v-bind="gradientProps">
                <stop
                  v-for="(color, j) in getColor(segment)"
                  :key="j"
                  class="linearGradientStop"
                  v-bind="gradientStopProps(segment, j)"
                  :stop-color="color"
                />
              </linearGradient>
            </defs>
            <rect v-bind="barRectProps(segment)" />
            <rect
              class="barRectGradient"
              v-bind="barRectGradientProps(index, i, segment)"
            />
            <g class="barSegmentPercentages">
              <rect v-bind="percentageRectProps(segment)" />
              <text
                class="svgText"
                v-bind="percentageTextProps(segment)"
                text-anchor="left"
              >
                {{ Math.round(segment.percentage * 100) }}%
              </text>
            </g>
          </g>
        </svg>
        <div
          v-for="(segment, i) in computedSegments[index]"
          :key="'text-' + i"
          class="segment-avatars"
          :style="avatarStyle(segment)"
        >
          <font-awesome-icon
            class="segment-avatars-icon"
            v-for="avatar in filteredAvatars(segment)"
            :key="avatar.id"
            :icon="avatar.symbol"
            :style="{ color: avatar.color }"
            @click="participantSelectionChanged([avatar.id])"
          />
        </div>
        <div
          v-for="(segment, i) in computedSegments[index]"
          :key="'text-' + i"
          class="segment-text"
          :style="segmentTextStyle(segment)"
          @click="
            participantSelectionChanged(
              segment.avatars.map((avatar) => avatar.id)
            )
          "
        >
          <ToolTip
            :content="segment.answer"
            :show-after="200"
            class="segment-text-toolTip"
          >
            <div class="segment-text-toolTip oneLineText">
              <span class="oneLineText">{{ segment.answer }}</span>
            </div>
          </ToolTip>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { debounce } from 'lodash';
import { Avatar } from '@/types/api/Participant';
import ToolTip from '@/components/shared/atoms/ToolTip.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

interface Answer {
  avatar: Avatar;
  answer: string[];
  correct?: boolean[];
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

@Options({
  components: {
    FontAwesomeIcon,
    ToolTip,
  },
})
export default class StackedBarChart extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: () => false }) readonly hasCorrect!: boolean;
  @Prop({ required: true }) readonly chartData!: QuestionData[];
  @Prop({ default: () => ['var(--color-informing)'] })
  readonly colorTheme!: string[];
  @Prop({ default: () => 'var(--color-brainstorming)' })
  readonly colorCorrect!: string;
  @Prop({ default: () => 'var(--color-evaluating)' })
  readonly colorIncorrect!: string;
  @Prop({ default: () => [] }) selectedParticipantIds!: string[];

  barHeight = 40;
  barWidthPercentage = 0.65;
  gapSize = 1;
  parentWidth = 0;
  resizeObserver: ResizeObserver | null = null;

  circleRadius = 9;

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
      Record<
        string,
        { count: number; avatars: Avatar[]; isCorrect: boolean | null }
      >
    >((acc, { answer, avatar, correct }) => {
      answer.forEach((response, index) => {
        if (!acc[response]) {
          acc[response] = { count: 0, avatars: [], isCorrect: null };
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
        if (correct && correct.length > 0) {
          acc[response].isCorrect = correct[index];
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

    return Object.entries(answerDetails).map(
      ([key, { count, avatars, isCorrect }], i) => {
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
          color: this.hasCorrect
            ? isCorrect
              ? this.colorCorrect
              : this.colorIncorrect
            : this.colorTheme[i % this.colorTheme.length],
        };

        cumulativeX += width - overlap;
        return segment;
      }
    );
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

  participantSelectionChanged(ids: string[] | null) {
    if (ids) {
      if (JSON.stringify(this.selectedParticipantIds) === JSON.stringify(ids)) {
        this.$emit('update:selectedParticipantIds', []);
      } else {
        this.$emit('update:selectedParticipantIds', ids);
      }
    }
  }

  calculateCircleX(segment: AnswerSegment, questionData: QuestionData): number {
    return (
      (Number(segment.answer) / questionData.parameter.maxValue) *
        (this.parentWidth * this.barWidthPercentage - 2 * this.paddingSlider) +
      this.paddingSlider
    );
  }

  get sliderPositions() {
    return [
      this.paddingSlider,
      this.parentWidth * this.barWidthPercentage * 0.25,
      this.parentWidth * this.barWidthPercentage * 0.5,
      this.parentWidth * this.barWidthPercentage * 0.75,
      this.parentWidth * this.barWidthPercentage - this.paddingSlider,
    ];
  }

  get questionTextStyle() {
    return { width: `${100 - 100 * this.barWidthPercentage - 2}%` };
  }

  get barSegmentsStyle() {
    return { width: `${100 * this.barWidthPercentage}%` };
  }

  get carouselStyle() {
    return {
      width: `${100 * this.barWidthPercentage}%`,
      height: `${this.barHeight * 3}px`,
    };
  }

  get carouselHeight() {
    return `${this.barHeight * 3}px`;
  }

  get barWidth() {
    return this.parentWidth * this.barWidthPercentage;
  }

  get gradientProps() {
    return {
      x1: '0%',
      y1: '0%',
      x2: '100%',
      y2: '0%',
    };
  }

  gradientId(index: number, i: number) {
    return `gradient-${index}-${i}-${this.taskId}`;
  }

  gradientStopProps(segment: AnswerSegment, j: number) {
    return {
      offset:
        this.getColor(segment).length > 1
          ? (j / (this.getColor(segment).length - 1)) * 100 + '%'
          : '50%',
    };
  }

  sliderLineProps() {
    return {
      x1: this.paddingSlider,
      x2: this.parentWidth * this.barWidthPercentage - this.paddingSlider,
      y1: this.barHeight / 2,
      y2: this.barHeight / 2,
      stroke: 'var(--color-dark-contrast)',
      opacity: '25%',
    };
  }

  sliderTickProps(x: number) {
    return {
      x1: x,
      x2: x,
      y1: this.barHeight / 2.5,
      y2: this.barHeight - this.barHeight / 2.5,
      stroke: 'var(--color-dark-contrast)',
      opacity: '25%',
    };
  }

  sliderTextProps(x: number, questionData: QuestionData) {
    return {
      x: x,
      y: this.barHeight,
      fontSize: '10.5',
      style: {
        color: 'var(--color-dark-contrast)',
        textAlign: 'center',
      },
    };
  }

  circleProps(segment: AnswerSegment, questionData: QuestionData) {
    return {
      cx: this.calculateCircleX(segment, questionData),
      cy: this.barHeight / 2,
      r: this.circleRadius + (segment.avatars.length - 1),
      fill: `url(#${this.gradientId(
        this.indexOfSegment(segment),
        this.indexOfAnswer(segment)
      )})`,
      style: {
        strokeWidth: 3,
        stroke: 'var(--color-background)',
        backgroundColor: 'var(--color-background)',
      }
    };
  }

  circleTextProps(segment: AnswerSegment, questionData: QuestionData) {
    return {
      x: this.calculateCircleX(segment, questionData),
      y: this.barHeight/2 + 5.25,
      fontSize: '10.5',
      style: {
        color: 'var(--color-dark-contrast)',
        textAlign: 'center',
      },
    };
  }

  barRectProps(segment: AnswerSegment) {
    return {
      x: segment.x,
      y: 0,
      width: segment.width >= 0 ? segment.width : 0,
      height: this.barHeight,
      fill: 'var(--color-background)',
      rx: this.barHeight / 2,
      ry: this.barHeight / 2,
    };
  }

  barRectGradientProps(index: number, i: number, segment: AnswerSegment) {
    return {
      x: segment.x,
      y: 0,
      width: segment.width >= 0 ? segment.width : 0,
      height: this.barHeight,
      fill: `url(#${this.gradientId(index, i)})`,
      rx: this.barHeight / 2,
      ry: this.barHeight / 2,
      style: {
        strokeWidth: 6,
        stroke: 'var(--color-background)',
        backgroundColor: 'var(--color-background)',
      },
    };
  }

  percentageRectProps(segment: AnswerSegment) {
    return {
      x: segment.x + segment.width - 55,
      y: 0,
      width: 30,
      height: 12,
      fill: 'var(--color-background)',
      rx: 12 / 2.5,
      ry: 12 / 2.5,
    };
  }

  percentageTextProps(segment: AnswerSegment) {
    return {
      x: segment.x + segment.width - 50,
      y: 7.5,
      fontSize: '10.5',
      style: {
        color: 'var(--color-dark-contrast)',
        textAlign: 'center',
      },
    };
  }

  avatarStyle(segment: AnswerSegment) {
    return {
      left: `${segment.x}px`,
      width: `${segment.width}px`,
    };
  }

  segmentTextStyle(segment: AnswerSegment) {
    return {
      left: `${segment.x}px`,
      width: `${segment.width}px`,
      top: '50%',
      transform: 'translateY(-50%)',
      textShadow:
        this.selectedParticipantIds.length > 0
          ? '-1px -1px 0 var(--color-background), 1px -1px 0 var(--color-background), -1px 1px 0 var(--color-background), 1px 1px 0 var(--color-background)'
          : 'unset',
    };
  }

  carouselIconStyle(segment: AnswerSegment) {
    return {
      color:
        this.selectedParticipantIds.length <= 0
          ? segment.avatars[0].color
          : this.getColor(segment)[0],
    };
  }

  filteredAvatars(segment: AnswerSegment) {
    return segment.avatars.filter((av) =>
      this.selectedParticipantIds.includes(av.id)
    );
  }

  shouldRenderSlider(questionData: QuestionData, index: number) {
    return (
      (this.computedSegments[index]?.length &&
        questionData.questionType === 'slider') ||
      questionData.questionType === 'number' ||
      questionData.questionType === 'rating'
    );
  }

  shouldRenderTextCarousel(questionData: QuestionData, index: number) {
    return (
      this.computedSegments[index]?.length &&
      questionData.questionType === 'text'
    );
  }

  shouldRenderBarSegments(index: number) {
    return !!this.computedSegments[index]?.length;
  }

  isSliderEdge(x: number) {
    return (
      x === this.paddingSlider ||
      x === this.parentWidth * this.barWidthPercentage - this.paddingSlider
    );
  }

  indexOfSegment(segment: AnswerSegment) {
    return this.computedSegments.findIndex((segments) =>
      segments.includes(segment)
    );
  }

  indexOfAnswer(segment: AnswerSegment) {
    return this.computedSegments[this.indexOfSegment(segment)].indexOf(segment);
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

  width: 100%;
  height: 100%;

  color: var(--color-dark-contrast);
  transition: text-shadow 0.4s ease;
  cursor: pointer;
  .segment-text-toolTip {
    width: 100%;
    height: 100%;
    padding: 0 2rem;

    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: nowrap;
    span {
      width: 100%;
    }
  }
}

.segment-avatars {
  position: absolute;
  padding: 0 2rem;
  display: flex;
  justify-content: right;
  align-items: center;
  top: 75%;

  transform: translateX(0.2rem);
}

.segment-avatars-circle {
  position: absolute;
  display: flex;
  justify-content: center;
  align-items: center;
  top: 60%;

  transform: translateX(0.2rem);
}

.segment-avatars-icon {
  background-color: var(--color-background);
  padding: 0.3rem;
  border-radius: 50%;
  margin-left: -0.4rem;

  cursor: pointer;
  transform: scale(1);
  transition: transform 0.3s ease;
}
.segment-avatars-icon:hover {
  z-index: 10;
  transform: scale(1.15);
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
  padding: 1rem 3rem 1rem 1rem;
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
  transition: transform 0.15s ease, color 0.3s ease;
}

.carouselColorItem:hover {
  transform: scale(1.15);
}

.circle {
  .circleLabel {
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
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
