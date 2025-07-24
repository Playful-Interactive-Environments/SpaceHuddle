<template>
  <draggable
    v-if="hasData"
    v-model="tableElementsData"
    v-bind="dragOptions"
    item-key="id"
    class="highscoreContainer"
    :group="{
      name: 'TablesSelection',
      pull: 'true',
      put: false,
    }"
  >
    <template v-slot:item="{ element, index }">
      <el-card
        :id="element.taskData.taskId + 'tableCard'"
        class="highScoreSelectionContainer"
        shadow="never"
        body-style="text-align: center"
        :class="{ addOn__boarder: !element }"
      >
        <div class="highscoreModuleSelection">
          <el-dropdown
            v-if="hasAxes"
            @command="(command) => updateTableElements(index, command)"
            trigger="click"
            placement="bottom"
          >
            <div class="el-dropdown-link">
              <font-awesome-icon
                v-if="element"
                class="highscoreModuleIcon"
                :icon="getIconOfAxis(element)"
                :style="{ color: getColorOfAxis(element) }"
              />
              <ToolTip>
                <template #content>
                  <div :style="{ textAlign: 'center' }">
                    <p class="heading heading--white">
                      {{
                        `${$t('moderator.molecule.moduleCount.topics')} ${
                          element.taskData.topicOrder + 1
                        }: ${element.taskData.topicName}`
                      }}
                    </p>
                    <p>{{ element.taskData.taskName }}</p>
                  </div>
                </template>
                <p class="oneLineText highscoreModuleName">
                  T{{ element.taskData.topicOrder + 1 }}:&nbsp;{{
                    element.taskData.taskName
                  }}
                </p>
              </ToolTip>
              <font-awesome-icon :icon="['fas', 'angle-down']" />
              <span class="participant-count highscoreModuleName"
                ><font-awesome-icon icon="user" />&nbsp;{{
                  getParticipantCount(element)
                }}
              </span>
            </div>
            <template #dropdown>
              <el-dropdown-menu>
                <template v-for="(ax, index) in axes" :key="ax.taskData.taskId">
                  <el-dropdown-item
                    v-if="isTopicHeading(index)"
                    class="heading oneLineText"
                    :divided="true"
                    :style="{ pointerEvents: 'none' }"
                    disabled
                  >
                    {{ ax.taskData.topicName }}
                  </el-dropdown-item>
                  <el-dropdown-item
                    :command="ax"
                    :divided="isTopicHeading(index)"
                  >
                    <font-awesome-icon
                      class="axisIcon"
                      :icon="getIconOfAxis(ax)"
                      :style="{ color: getColorOfAxis(ax) }"
                    />
                    <span>&nbsp;{{ ax.taskData.taskName }}</span>
                  </el-dropdown-item>
                </template>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
          <font-awesome-icon
            v-if="element"
            :icon="['fas', 'trash']"
            class="trashButton"
            @click="removeFromTableElements(index)"
          />
        </div>
        <Highscore
          v-if="element"
          class="highscore"
          :task-id="element.taskData.taskId"
          :table-data="filterParticipantData(element.taskData.taskId)"
          v-model:selectedParticipantIds="participantIds"
          @update:selected-participant-ids="updateSelectedParticipantIds"
          :translation-path="getTranslationPath(element)"
        />
        <ToolTip
          :text="
            this.expandedElements.find(
              (elId) => elId === element.taskData.taskId + 'tableCard'
            )
              ? $t('moderator.organism.analytics.collapse')
              : $t('moderator.organism.analytics.expand')
          "
          :placement="'bottom'"
        >
          <div
            class="expandCard"
            @click="handleExpandClick(element.taskData.taskId + 'tableCard')"
          >
            <font-awesome-icon
              v-if="
                !this.expandedElements.find(
                  (elId) => elId === element.taskData.taskId + 'tableCard'
                )
              "
              :icon="['fas', 'chevron-right']"
              class="expandIcon"
            />
            <font-awesome-icon
              v-else
              :icon="['fas', 'chevron-left']"
              class="expandIcon"
            />
          </div>
        </ToolTip>
      </el-card>
    </template>
  </draggable>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import Highscore from '@/components/moderator/organisms/analytics/Highscore.vue';
import { ParticipantInfo } from '@/types/api/Participant';
import TaskType from '@/types/enum/TaskType';
import { getColorOfType, getIconOfType } from '@/types/enum/TaskCategory';
import { HighScoreEntry } from '@/components/moderator/organisms/analytics/Highscore.vue';
import { Idea } from '@/types/api/Idea';
import ToolTip from '@/components/shared/atoms/ToolTip.vue';
import draggable from 'vuedraggable';

interface SubAxis {
  id: string;
  range: number;
}

interface Axis {
  taskData: {
    taskId: string;
    taskType: TaskType;
    taskName: string;
    topicName: string;
    topicOrder: number;
    moduleName: string;
    initOrder: number;
  };
  axisValues: (SubAxis | null)[];
  categoryActive: string;
  active: boolean;
  available: boolean;
}

interface AxisValue {
  id: string;
  value: number | null;
  ideas?: Idea[];
}

interface DataEntry {
  participant: ParticipantInfo;
  axes: {
    taskId: string;
    axisValues: AxisValue[];
  }[];
}

@Options({
  components: { draggable, ToolTip, Highscore },
  emits: [
    'update:selectedParticipantIds',
    'update:tableElements',
    'hasExpandedElement',
  ],
})
export default class Tables extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly axes!: Axis[];
  @Prop() readonly participantData!: DataEntry[];
  @Prop({ default: () => [] }) selectedParticipantIds!: string[];
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;

  @Prop({ default: () => [] }) tableElements!: Axis[];

  tableElementsData: Axis[] = [];

  expandedElements: string[] = [];

  chartData: DataEntry[] = [];
  participantIds: string[] = [];

  get hasData(): boolean {
    return !!this.axes && this.chartData.length > 0;
  }

  get hasAxes(): boolean {
    return this.axes.length >= 1;
  }

  get dragOptions(): object {
    return {
      animation: 200,
      group: 'description',
      ghostClass: 'ghost',
    };
  }

  hasExpandedElement(): boolean {
    return document.getElementsByClassName('expanded').length > 0;
  }

  @Watch('participantData', { immediate: true })
  onChartDataChanged(): void {
    if (this.participantData) {
      this.chartData = this.participantData.filter((entry) =>
        entry.axes.some((axis) =>
          axis.axisValues.some((value) => value.value != null)
        )
      );
    }
  }

  @Watch('selectedParticipantIds', { immediate: true })
  onSelectedParticipantIdsChanged(): void {
    this.participantIds = this.selectedParticipantIds;
  }

  updateSelectedParticipantIds(): void {
    this.$emit('update:selectedParticipantIds', this.participantIds);
  }

  updateHasExpandedElements(): void {
    this.$emit('hasExpandedElement', this.hasExpandedElement());
  }

  getTranslationPath(axis: Axis): string {
    return `module.${axis.taskData.taskType.toLowerCase()}.${
      axis.taskData.moduleName
    }.analytics.highscore.`;
  }

  getIconOfAxis(axis: Axis): string | undefined {
    return axis.taskData.taskType
      ? getIconOfType(TaskType[axis.taskData.taskType.toUpperCase()])
      : undefined;
  }

  getColorOfAxis(axis: Axis): string | undefined {
    return axis.taskData.taskType
      ? getColorOfType(TaskType[axis.taskData.taskType.toUpperCase()])
      : undefined;
  }

  @Watch('tableElements', { deep: true })
  onValueChanged(newValue: Axis[]) {
    this.tableElementsData = [...newValue];
    this.$emit('update:tableElements', newValue);
  }

  addToTableElements(axis: Axis): void {
    this.tableElements.push(axis);
  }

  removeFromTableElements(index: number): void {
    this.tableElements.splice(index, 1);
  }

  updateTableElements(index: number, axis: Axis): void {
    this.tableElements.splice(index, 1, axis);
  }

  filterParticipantData(taskId: string): HighScoreEntry[] {
    return this.chartData
      .filter((entry) =>
        entry.axes.some(
          (a) =>
            a.taskId === taskId &&
            a.axisValues.some((value) => value.value != null)
        )
      )
      .map((entry) => ({
        values:
          entry.axes
            .find((axis) => axis.taskId === taskId)
            ?.axisValues.sort(
              (a, b) =>
                (['stars', 'rate'].includes(a.id) ? 1 : -1) -
                (['stars', 'rate'].includes(b.id) ? 1 : -1)
            ) || [],
        participant: entry.participant,
      }));
  }

  isTopicHeading(index: number): boolean {
    return (
      (this.axes[index - 1] &&
        this.axes[index].taskData.topicOrder !==
          this.axes[index - 1].taskData.topicOrder) ||
      index === 0
    );
  }

  getParticipantCount(axis: Axis): number {
    const taskId = axis.taskData.taskId;
    const category = axis.categoryActive;

    return this.participantData.reduce((counter, partData) => {
      const partAxis = partData.axes.find(
        (partAxis) => partAxis.taskId === taskId
      );
      if (partAxis) {
        counter += partAxis.axisValues.reduce((count, value) => {
          return (
            count + (value.id === category && value.value !== null ? 1 : 0)
          );
        }, 0);
      }
      return counter;
    }, 0);
  }

  handleExpandClick(id: string): void {
    const element = document.getElementById(id);
    if (element) {
      if (element.classList.contains('expanded')) {
        element.classList.remove('expanded');
        this.expandedElements = this.expandedElements.filter(
          (elId) => elId !== id
        );
      } else {
        element.classList.add('expanded');
        this.expandedElements.push(id);
      }
      this.updateHasExpandedElements();
    }
  }
}
</script>

<style lang="scss" scoped>
.highscoreContainer {
  display: flex;
  justify-content: flex-start;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 3rem;
  width: 100%;
}

.highScoreSelectionContainer {
  cursor: move;
  min-width: 700px;
  width: calc(50% - 1.5rem);
  overflow: visible;
  position: relative;

  transition: width 0.4s ease;

  @media (max-width: calc((700px * 2) + 12rem)) {
    width: 100%;
  }

  &::-webkit-scrollbar {
    display: none;
  }
}

.expanded {
  width: 100% !important;
}

.highscore {
  margin-top: 1rem;
}

.el-dropdown-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  border-radius: var(--border-radius-xs);
  border: 2px solid transparent;
  padding: 0.2rem 0.6rem;
  transition: border 0.3s ease;
  cursor: pointer;

  &:hover {
    border: 2px solid var(--color-background-darker);
  }
}

.highscoreModuleSelection {
  display: flex;
  justify-content: space-between;
  align-items: center;

  .trashButton {
    background-color: transparent;
    padding: 0;
    margin: 0;
    font-size: var(--font-size-small);
    cursor: pointer;
  }
}

.highscoreModuleName {
  font-size: var(--font-size-default);
  font-weight: var(--font-weight-bold);
}

.highscoreModuleIcon {
  font-size: var(--font-size-xlarge);
}

.addOn__boarder {
  border: 2px dashed var(--color-primary);
  display: flex;
  justify-content: center;
  align-items: center;
}

.el-card {
  background-color: transparent;
}

.TableSelectionHeadline {
  font-size: var(--font-size-xlarge);
  font-weight: var(--font-weight-bold);
  margin-bottom: 0.5rem;
}

.participant-count {
  margin-left: 1rem;
}

.expandCard {
  position: absolute;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.2rem;
  height: 1.2rem;
  right: -1.2rem;
  top: 1rem;
  font-size: 0.7rem;
  transition: background-color 0.4s ease;
  color: var(--color-background);
  background-color: var(--color-background-darker);
  border-radius: 0 var(--border-radius-small) var(--border-radius-small) 0;
  cursor: pointer;
  .expandIcon {
    display: flex;
    align-items: center;
    justify-content: center;
  }
}
.expandCard:hover {
  background-color: var(--color-evaluating);
}

.ghost {
  opacity: 0.5;
  background: var(--color-background-dark);
}
</style>
