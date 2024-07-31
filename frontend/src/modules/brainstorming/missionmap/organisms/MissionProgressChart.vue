<template>
  <div class="chart_container">
    <div
      v-if="
        missionProgressParameter === MissionProgressParameter.influenceAreas
      "
    >
      <div class="columns influenceAreas is-mobile">
        <div class="column">
          <img
            src="@/modules/brainstorming/missionmap/assets/eco.png"
            alt="eco"
          />
          <div class="influenceName">
            <font-awesome-icon icon="leaf" />
            {{ $t(`module.brainstorming.missionmap.gameConfig.eco`) }}
          </div>
          {{ convertInfluenceDataSet(0) }}
        </div>
        <div class="column">
          <img
            src="@/modules/brainstorming/missionmap/assets/social.png"
            alt="social"
          />
          <div class="influenceName">
            <font-awesome-icon icon="heart" />
            {{ $t(`module.brainstorming.missionmap.gameConfig.social`) }}
          </div>
          {{ convertInfluenceDataSet(1) }}
        </div>
        <div class="column">
          <img
            src="@/modules/brainstorming/missionmap/assets/business.png"
            alt="business"
          />
          <div class="influenceName">
            <font-awesome-icon icon="sack-dollar" />
            {{ $t(`module.brainstorming.missionmap.gameConfig.business`) }}
          </div>
          {{ convertInfluenceDataSet(2) }}
        </div>
      </div>
    </div>
    <div v-else-if="lineChartDataList.length > 0" class="columns">
      <div class="column electricityMix">
        <Doughnut
          :data="chartDataElectricityMixInitial"
          :options="{
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false,
                position: 'right',
                labels: {
                  color: '#000000',
                },
              },
              title: {
                display: true,
                text: $t(
                  'module.brainstorming.missionmap.participant.chart.y-electricity1'
                ),
              },
              tooltip: {
                callbacks: {
                  afterTitle: pieToolTip,
                },
              },
            },
          }"
        />
      </div>
      <div
        class="column electricityMix"
        v-if="lineChartDataList[0].data.datasets[0].data.length > 1"
      >
        <Doughnut
          :data="chartDataElectricityMixResult"
          :options="{
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false,
                position: 'right',
                labels: {
                  color: '#000000',
                },
              },
              title: {
                display: true,
                text: $t(
                  'module.brainstorming.missionmap.participant.chart.y-electricity2'
                ),
              },
              tooltip: {
                callbacks: {
                  afterTitle: pieToolTip,
                },
              },
            },
          }"
        />
      </div>
    </div>
    <div v-for="(chartData, index) in lineChartDataList" :key="index">
      <el-collapse>
        <el-collapse-item
          :title="
            $t(
              `module.brainstorming.missionmap.enum.progress.${chartData.title}`
            )
          "
        >
          <div class="cards_area columns is-mobile">
            <div
              v-for="element of chartData.items"
              :key="element.idea.id"
              class="column"
            >
              <IdeaCard
                :idea="element.idea"
                class="ideaCard"
                :is-editable="false"
                :show-state="false"
                :canChangeState="false"
                :handleEditable="false"
                :background-color="getIdeaColor(element.idea)"
                :authHeaderTyp="authHeaderTyp"
                fix-height="13rem"
                image-height="33%"
              >
                <template #icon>
                  <font-awesome-icon icon="person-booth" />
                </template>
                <template #image_overlay>
                  <div
                    class="media image_overlay"
                    v-if="getInfluenceAreasForIdea(element.idea).length > 0"
                  >
                    <div class="media-content"></div>
                    <div class="media-right">
                      <font-awesome-icon
                        v-for="parameter of getInfluenceAreasForIdea(
                          element.idea
                        )"
                        :key="parameter"
                        :style="{
                          color: gameConfig.parameter[parameter].color,
                        }"
                        :icon="gameConfig.parameter[parameter].icon"
                      />
                    </div>
                  </div>
                </template>
                <h2 v-if="element.percentage">
                  {{
                    $t('module.brainstorming.missionmap.participant.yourPart')
                  }}
                </h2>
                <div v-if="element.percentage">
                  <div>
                    <font-awesome-icon icon="coins" />
                    {{ element.points }} / {{ element.idea.parameter.points }}
                  </div>
                  <div>{{ element.percentage }} %</div>
                </div>
              </IdeaCard>
            </div>
          </div>
        </el-collapse-item>
      </el-collapse>
      <div class="chart_area">
        <el-tooltip
          :visible="!!hoverLabel && index === hoverChart"
          placement="left"
        >
          <template #content>
            {{ $t(`${toolTipTranslation}.${hoverLabel}`) }}
          </template>
          <div
            class="label-tooltip"
            :style="{
              '--x': `${labelPos[0]}px`,
              '--y': `${labelPos[1]}px`,
            }"
          ></div>
        </el-tooltip>
        <Line
          :data="chartData.data"
          :height="250"
          :options="{
            maintainAspectRatio: false,
            animation: {
              duration: 0,
            },
            scales: {
              x: {
                ticks: {
                  color: chartData.labelColors,
                },
                grid: {
                  display: false,
                },
                title: {
                  text: $t(
                    'module.brainstorming.missionmap.participant.chart.x'
                  ),
                  display: true,
                },
              },
              y: {
                stacked:
                  missionProgressParameter ===
                  MissionProgressParameter.electricity,
                ticks: {
                  precision: 0,
                },
                title: {
                  text: yLabel,
                  display: true,
                },
              },
            },
            plugins: {
              legend: {
                onHover: (evt, item) => handleHoverLegend(evt, item, index),
                onLeave: handleLeaveLegend,
                display: chartData.data.datasets.length > 1 && displayLabels,
                position: 'right',
                labels: {
                  color: colorPrimary,
                  usePointStyle: true,
                },
              },
              title: {
                display: lineChartDataList.length > 1,
                text: $t(
                  `module.brainstorming.missionmap.enum.progressLong.${chartData.title}`
                ),
              },
              annotation: {
                annotations: chartAnnotations(chartData),
              },
              tooltip: {
                callbacks: {
                  label: lineToolTip,
                  afterLabel: lineToolTipAfter,
                  afterTitle: (item) =>
                    lineToolTipDescription(item, chartData.items),
                },
              },
            },
          }"
        />
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import gameConfig from '@/modules/brainstorming/missionmap/data/gameConfig.json';
import gameConfigMoveIt from '@/modules/playing/moveit/data/gameConfig.json';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import * as themeColors from '@/utils/themeColors';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import { ChartData, TooltipItem } from 'chart.js/dist/types';
import { Idea } from '@/types/api/Idea';
import { Vote, VoteParameterResult } from '@/types/api/Vote';
import {
  calculateChartPerParameter,
  CalculationType,
  getCalculationForType,
} from '@/utils/statistic';
import * as progress from '@/modules/brainstorming/missionmap/utils/progress';
import { CombinedInputManager } from '@/types/input/CombinedInputManager';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
import * as cashService from '@/services/cash-service';
import { Module } from '@/types/api/Module';
import { delay } from '@/utils/wait';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';

export enum MissionProgressParameter {
  influenceAreas = 'influenceAreas',
  electricity = 'electricity',
}

interface LineChartData {
  title: string;
  data: ChartData;
  labelColors: string[] | string;
  items: {
    idea: Idea;
    percentage: number | null;
    points: number | null;
  }[];
}

@Options({
  computed: {
    gameConfig() {
      return gameConfig;
    },
  },
  components: {
    FontAwesomeIcon,
    Bar,
    Line,
    Doughnut,
    IdeaCard,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class MissionProgressChart extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: MissionProgressParameter.influenceAreas })
  readonly missionProgressParameter!: MissionProgressParameter;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  readonly authHeaderTyp!: EndpointAuthorisationType;
  @Prop({ default: '' }) readonly yLabel!: string;
  lineChartDataList: LineChartData[] = [];
  chartDataElectricityMixInitial: ChartData = {
    labels: [],
    datasets: [],
  };
  chartDataElectricityMixResult: ChartData = {
    labels: [],
    datasets: [],
  };
  module!: Module;
  ideas: Idea[] = [];
  inputManager!: CombinedInputManager;
  decidedIdeas: Idea[] = [];
  displayLabels = false;
  hoverLabel: string | null = null;
  labelPos: [number, number] = [0, 0];
  hoverChart = 0;

  MissionProgressParameter = MissionProgressParameter;

  get toolTipTranslation(): string {
    if (this.missionProgressParameter === MissionProgressParameter.electricity)
      return 'module.playing.moveit.enums.electricity';
    return 'module.brainstorming.missionmap.gameConfig';
  }

  chartAnnotations(chartData: LineChartData): any {
    if (
      this.missionProgressParameter === MissionProgressParameter.influenceAreas
    ) {
      const xMax = chartData.data.labels ? chartData.data.labels.length : 0;
      return {
        box1: {
          type: 'box',
          xMin: 0,
          xMax: xMax,
          yMin: 10,
          yMax: 3,
          backgroundColor: this.positiveColorTransparent,
          borderColor: 'transparent',
        },
        box2: {
          type: 'box',
          xMin: 0,
          xMax: xMax,
          yMin: 3,
          yMax: -2,
          backgroundColor: this.naturalColorTransparent,
          borderColor: 'transparent',
        },
        box3: {
          type: 'box',
          xMin: 0,
          xMax: xMax,
          yMin: -2,
          yMax: -10,
          backgroundColor: this.negativeColorTransparent,
          borderColor: 'transparent',
        },
      };
    }
    return {};
  }

  mounted(): void {
    this.displayLabels = true;
    setTimeout(() => (this.displayLabels = false), 10);
    document.fonts.onloadingdone = () => {
      this.displayLabels = true;
    };
    setTimeout(() => (this.displayLabels = true), 2000);
  }

  get colorPrimary(): string {
    return themeColors.convertToRGBA(themeColors.getContrastColor());
  }

  get negativeColorTransparent(): string {
    return themeColors.convertToRGBA(themeColors.getRedColor(), 0.25);
  }

  get naturalColorTransparent(): string {
    return themeColors.convertToRGBA(themeColors.getYellowColor(), 0.25);
  }

  get positiveColorTransparent(): string {
    return themeColors.convertToRGBA(themeColors.getGreenColor(), 0.25);
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      this.inputManager = new CombinedInputManager(
        this.taskId,
        IdeaSortOrder.TIMESTAMP,
        this.authHeaderTyp,
        true,
        'points'
      );
      this.inputManager.callbackUpdateIdeas = this.updateIdeas;
      this.inputManager.callbackUpdateVotes = this.updateVotes;
      taskService.registerGetTaskById(
        this.taskId,
        this.updateTask,
        this.authHeaderTyp
      );
    }
  }

  updateTask(task: Task): void {
    const module = task.modules.find((module) => module.name === 'missionmap');
    if (module) this.module = module;
    this.calculateCharts();
  }

  @Watch('missionProgressParameter', { immediate: true })
  onMissionProgressParameterChanged(): void {
    this.calculateCharts();
  }

  calculateCharts(): void {
    this.lineChartDataList = [];
    if (this.module) {
      this.calculateLineChartByProgression(this.decidedIdeas);
      if (this.authHeaderTyp === EndpointAuthorisationType.PARTICIPANT) {
        this.calculateLineChartByProgression(this.decidedIdeas, 'own', true);
        this.calculateLineChartByProgression(this.ideas, 'ownFuture', true);
      }
    }
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    if (this.inputManager) this.inputManager.deregisterAll();
  }

  unmounted(): void {
    this.deregisterAll();
  }

  updateIdeas(): void {
    const ideas = this.inputManager.ideas;
    this.ideas = ideas.filter((idea) => idea.parameter.shareData);
    this.calculateDecidedIdeas();
    this.calculateCharts();
  }

  updateVotes(): void {
    this.calculateDecidedIdeas();
    this.calculateCharts();
  }

  calculateDecidedIdeas(): void {
    if (this.inputManager) {
      this.decidedIdeas = progress.calculateDecidedIdeasFromResult(
        this.inputManager.votingResult,
        this.ideas
      );
    }
  }

  calculateLineChartByProgression(
    decidedIdeas: Idea[],
    title = 'general',
    mapOnlyOwnInfluence = false
  ): void {
    const ideaList: {
      idea: Idea;
      percentage: number | null;
      points: number | null;
    }[] = [];
    const displayParameter =
      this.missionProgressParameter === MissionProgressParameter.influenceAreas
        ? gameConfig.parameter
        : gameConfigMoveIt.electricity;
    let ideaVotes = decidedIdeas.map((idea) => {
      return {
        idea: idea,
        ownVotes: [] as Vote[],
        vote: undefined as VoteParameterResult | undefined,
      };
    });
    let getParameterValue = (item, parameter) => {
      return item.idea
        ? item.idea.parameter[this.missionProgressParameter][parameter]
        : 0;
    };
    let mapToValue = (list, parameter) =>
      list.map((item) => getParameterValue(item, parameter));
    if (mapOnlyOwnInfluence) {
      ideaVotes = decidedIdeas
        .map((idea) => {
          return {
            idea: idea,
            ownVotes: this.inputManager.votes.filter(
              (vote) => vote.ideaId === idea.id && vote.parameter.points
            ),
            vote: this.inputManager.votingResult.find(
              (vote) => vote.ideaId === idea.id
            ),
          };
        })
        .filter((item) => item.ownVotes.length > 0)
        .sort((a, b) =>
          (a.ownVotes[0] as Vote).timestamp.localeCompare(
            (b.ownVotes[0] as Vote).timestamp
          )
        );
      getParameterValue = (item, parameter) => {
        if (
          item.idea &&
          item.vote &&
          item.ownVotes &&
          item.ownVotes.length > 0
        ) {
          if (
            isNaN(item.idea.parameter[this.missionProgressParameter][parameter])
          )
            return item.idea.parameter[this.missionProgressParameter][
              parameter
            ];
          const ownSum = item.ownVotes.reduce(
            (sum, item) => sum + item.parameter.points,
            0
          );
          if (
            item.idea.parameter[this.missionProgressParameter] &&
            Object.hasOwn(
              item.idea.parameter[this.missionProgressParameter],
              parameter
            )
          ) {
            return (
              item.idea.parameter[this.missionProgressParameter][parameter] *
              (ownSum / item.vote.sum)
            );
          } else {
            return ownSum / item.vote.sum;
          }
        }
        return 0;
      };
      ideaList.push(
        ...ideaVotes.map((item) => {
          const ownSum = item.ownVotes.reduce(
            (sum, item) => sum + item.parameter.points,
            0
          );
          return {
            idea: item.idea,
            percentage: Math.round((ownSum / item.idea.parameter.points) * 100),
            points: ownSum,
          };
        })
      );
    } else {
      ideaList.push(
        ...decidedIdeas.map((item) => {
          return {
            idea: item,
            points: null,
            percentage: null,
          };
        })
      );
    }
    const ideaProgress: {
      index: number;
      idea: Idea | null;
      ownVotes: Vote[];
      vote: VoteParameterResult | undefined;
    }[] = [{ index: -1, idea: null, vote: undefined, ownVotes: [] }];
    const inputProgress: { [key: string]: number | string }[] = [];
    for (let i = 0; i < ideaVotes.length; i++) {
      ideaProgress.push({
        index: i,
        idea: ideaVotes[i].idea,
        ownVotes: ideaVotes[i].ownVotes,
        vote: ideaVotes[i].vote,
      });
      const progressValue: { [key: string]: number | string } = {};
      for (const key in ideaVotes[i].idea.parameter[
        this.missionProgressParameter
      ]) {
        progressValue[key] = getParameterValue(ideaVotes[i], key);
      }
      inputProgress.push(progressValue);
    }
    if (
      this.missionProgressParameter === MissionProgressParameter.electricity
    ) {
      const progressData = progress.getElectricityProgressSteps(
        inputProgress,
        this.module
      );
      mapToValue = (list, parameter) =>
        list.map((item, index) => {
          if (index > 0)
            return (
              progressData[index][parameter] -
              progressData[index - 1][parameter]
            );
          return 0;
        });
    }
    const labels = ideaProgress.map((item) =>
      item.idea
        ? item.idea.keywords.length < 20
          ? item.idea.keywords
          : `${item.idea.keywords.substring(0, 18)}...`
        : this.$t('module.brainstorming.missionmap.enum.progress.origin')
    );
    const datasets = calculateChartPerParameter(
      ideaProgress,
      Object.keys(displayParameter),
      ideaProgress,
      Object.values(displayParameter).map((item) => item.color),
      () => true,
      (item, progress) => item.index <= progress.index,
      null,
      (list, loopItem, parameter) => {
        const sum = getCalculationForType(CalculationType.Sum)(
          mapToValue(list, parameter)
        );
        if (
          this.missionProgressParameter ===
          MissionProgressParameter.influenceAreas
        )
          return this.module.parameter[parameter] + sum;
        else return displayParameter[parameter].value + sum;
      },
      (parameter) => {
        return displayParameter[parameter].iconCode;
      },
      10
    );
    for (const dataset of datasets) {
      (dataset as any).pointStyle = 'circle';
      if (
        this.missionProgressParameter === MissionProgressParameter.electricity
      ) {
        (dataset as any).fill = {
          target: 'stack',
          above: `${(dataset as any).borderColor}77`,
        };
      } else {
        (dataset as any).fill = false;
      }
    }
    this.lineChartDataList.push({
      title: title,
      data: {
        labels: labels,
        datasets: datasets,
      },
      labelColors: themeColors.getContrastColor(),
      items: ideaList,
    });

    this.chartDataElectricityMixInitial = {
      labels: [],
      datasets: [],
    };
    this.chartDataElectricityMixInitial.datasets.push({
      label: this.$t('module.playing.moveit.participant.electricity'),
      backgroundColor: [],
      data: [],
    });
    this.chartDataElectricityMixResult = {
      labels: [],
      datasets: [],
    };
    this.chartDataElectricityMixResult.datasets.push({
      label: this.$t('module.playing.moveit.participant.electricity'),
      backgroundColor: [],
      data: [],
    });
    const j = 0;
    for (let i = 0; i < this.lineChartDataList[0].data.datasets.length; i++) {
      const energySource = this.lineChartDataList[0].data.datasets[i];
      if (energySource.label && this.chartDataElectricityMixResult.labels) {
        this.chartDataElectricityMixResult.labels.push(energySource.label);
      }
      this.chartDataElectricityMixResult.datasets[j].data.push(
        energySource.data[energySource.data.length - 1 - j] as any
      );
      (
        this.chartDataElectricityMixResult.datasets[j]
          .backgroundColor as string[]
      ).push(energySource.backgroundColor as string);

      if (energySource.label && this.chartDataElectricityMixInitial.labels) {
        this.chartDataElectricityMixInitial.labels.push(energySource.label);
      }
      this.chartDataElectricityMixInitial.datasets[j].data.push(
        energySource.data[j] as any
      );
      (
        this.chartDataElectricityMixInitial.datasets[j]
          .backgroundColor as string[]
      ).push(energySource.backgroundColor as string);
    }
    /*if (this.lineChartDataList[0].data.labels) {
      for (let j = 0; j < this.lineChartDataList[0].data.labels.length; j++) {
        this.chartDataElectricityMix.datasets.push({
          label: this.$t('module.playing.moveit.participant.electricity'),
          backgroundColor: [],
          data: [],
        });
        for (
          let i = 0;
          i < this.lineChartDataList[0].data.datasets.length;
          i++
        ) {
          const energySource = this.lineChartDataList[0].data.datasets[i];
          if (
            energySource.label &&
            this.chartDataElectricityMix.labels &&
            j === 0
          ) {
            this.chartDataElectricityMix.labels.push(energySource.label);
          }
          this.chartDataElectricityMix.datasets[j].data.push(
            energySource.data[energySource.data.length - 1 - j] as any
          );
          (
            this.chartDataElectricityMix.datasets[j].backgroundColor as string[]
          ).push(energySource.backgroundColor as string);
        }
      }
    }*/
  }

  pieToolTip(tooltipItems: TooltipItem<any>[]): string {
    const displayParameter =
      this.missionProgressParameter === MissionProgressParameter.influenceAreas
        ? gameConfig.parameter
        : gameConfigMoveIt.electricity;
    for (const key of Object.keys(displayParameter)) {
      if (displayParameter[key].iconCode === tooltipItems[0].label)
        return this.$t(`${this.toolTipTranslation}.${key}`);
    }
    return '';
  }

  lineToolTip(tooltipItem: TooltipItem<any>): string {
    const displayParameter =
      this.missionProgressParameter === MissionProgressParameter.influenceAreas
        ? gameConfig.parameter
        : gameConfigMoveIt.electricity;
    const unit =
      this.missionProgressParameter === MissionProgressParameter.influenceAreas
        ? ' ' + this.$t('module.brainstorming.missionmap.participant.points')
        : '%';
    let name = '';
    for (const key of Object.keys(displayParameter)) {
      if (displayParameter[key].iconCode === tooltipItem.dataset.label) {
        name = this.$t(`${this.toolTipTranslation}.${key}`);
      }
    }
    return `${tooltipItem.dataset.label} ${name}: ${tooltipItem.formattedValue}${unit}`;
  }

  lineToolTipAfter(tooltipItem: TooltipItem<any>): string {
    return this.convertInfluenceValue(tooltipItem.raw as number);
  }

  convertInfluenceValue(value: number): string {
    if (
      this.missionProgressParameter === MissionProgressParameter.influenceAreas
    ) {
      const roundValue = isNaN(value) ? 0 : Math.round(value);
      return this.$t(
        `module.brainstorming.missionmap.enum.influenceResult.${roundValue}`
      );
    }
    return '';
  }

  convertInfluenceDataSet(index: number): string {
    const datasets = this.lineChartDataList[0]?.data.datasets;
    if (datasets && datasets.length > 0) {
      const value = datasets[datasets.length - 1].data;
      const indexValue = (value[index] as number) ?? 0;
      return this.convertInfluenceValue(indexValue);
    }
    return '';
  }

  lineToolTipDescription(
    tooltipItems: TooltipItem<any>[],
    ideas: any[]
  ): string | undefined {
    if (
      tooltipItems[0].dataIndex > 0 &&
      tooltipItems[0].dataIndex <= ideas.length
    ) {
      const description = ideas[tooltipItems[0].dataIndex - 1].idea.description
        .replaceAll('#', '')
        .replaceAll('*', '')
        .replaceAll('\n', ' ')
        .replaceAll('\r', '');
      if (description.length < 50) return description;
      return `${description.substring(0, 47)}...`;
    }
  }

  async handleHoverLegend(
    evt: MouseEvent,
    item: any,
    chartIndex
  ): Promise<void> {
    this.hoverChart = chartIndex;
    this.labelPos = [evt.x, evt.y];
    this.hoverLabel = null;
    await delay(100);
    if (
      this.missionProgressParameter === MissionProgressParameter.influenceAreas
    ) {
      for (const key of Object.keys(gameConfig.parameter)) {
        if (gameConfig.parameter[key].iconCode === item.text) {
          this.hoverLabel = key;
          break;
        }
      }
    } else {
      for (const key of Object.keys(gameConfigMoveIt.electricity)) {
        if (gameConfigMoveIt.electricity[key].iconCode === item.text) {
          this.hoverLabel = key;
          break;
        }
      }
    }
  }

  handleLeaveLegend() {
    this.hoverLabel = null;
  }

  getInfluenceAreasForIdea(idea: Idea): string[] {
    const areas: string[] = [];
    for (const parameter of Object.keys(gameConfig.parameter)) {
      if (idea.parameter.influenceAreas[parameter] > 0) areas.push(parameter);
    }
    return areas;
  }

  isDecided(ideaId: string): boolean {
    return !!this.decidedIdeas.find((idea) => idea.id === ideaId);
  }

  getIdeaColor(idea: Idea): string {
    if (idea.isOwn) return themeColors.getInformingColor('-light');
    if (this.isDecided(idea.id))
      return themeColors.getBrainstormingColor('-light');
    return '#ffffff';
  }
}
</script>

<style lang="scss" scoped>
.el-form-item::v-deep(.el-form-item__label) {
  color: var(--parameter-color);
}

.el-form-item .el-slider {
  --el-slider-runway-bg-color: color-mix(
    in srgb,
    var(--state-color) 30%,
    transparent
  );
  --el-slider-disabled-color: var(--state-color);
}

.el-tabs::v-deep(.el-tabs__nav-next),
.el-tabs::v-deep(.el-tabs__nav-prev) {
  line-height: unset;
}

.label-tooltip {
  position: absolute;
  top: var(--y);
  left: var(--x);
}

.chart_container {
  position: relative;
}

.chart_area {
  position: relative;
}

.cards_area {
  overflow: auto;

  > .column {
    max-width: 10rem;
    min-width: 10rem;
    padding: var(--column-padding);
  }
}

.columns {
  --column-padding: 0.25rem;
  margin-left: 0;
  margin-right: 0;
  margin-top: 0;
}

.columns:not(:last-child) {
  margin-bottom: var(--column-padding);
}

h1 {
  font-weight: var(--font-weight-bold);
  font-size: var(--font-size-large);
  text-align: center;
  padding-bottom: 0.5rem;
  color: var(--color-primary);
}

h2 {
  font-weight: var(--font-weight-bold);
  padding-bottom: 0.5rem;
  color: var(--color-primary);
}

.ideaCard {
  border: solid var(--color-dark-contrast) 3px;
}

.ideaCard::v-deep(.card__image__icon) {
  font-size: 1.5rem;
}

.image_overlay {
  padding: 0.2rem;
  background-color: color-mix(
    in srgb,
    var(--color-dark-contrast) 60%,
    transparent
  );

  .media-content {
    color: white;
    padding-left: 0.5rem;
  }

  .media-right {
    text-align: right;
  }

  svg {
    padding-right: 0.5rem;
  }
}

.electricityMix {
  height: 20vh;
}

.influenceName {
  font-weight: var(--font-weight-semibold);
}

.influenceAreas {
  .column {
    text-align: center;
    width: 33%;
    max-width: 20rem;
    flex-basis: unset;
    flex-grow: unset;
    flex-shrink: unset;
  }
}
</style>
